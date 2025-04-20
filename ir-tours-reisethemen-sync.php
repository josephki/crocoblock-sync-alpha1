<?php
/*
Plugin Name: IR Tours Reisethemen Sync (AJAX + Button)
Description: AJAX-Synchronisation der Reisethemen mit zusätzlichem Button und Feedback. Zeigt Hinweis bei zu alter PHP-Version.
Version: 3.9
Author: Joseph Kisler - Webwerkstatt, Freiung 16/2/4, A-4600 Wels
*/

// Fehlerunterdrückungslösung einbinden
require_once plugin_dir_path(__FILE__) . 'ir-error-suppression.php';

// Session Guard einbinden, aber mit Prüfung und unterdrückter Warnung
if (file_exists(plugin_dir_path(__FILE__) . 'ir-session-guard.php')) {
    @include_once plugin_dir_path(__FILE__) . 'ir-session-guard.php';
}

// PHP-Version prüfen und Admin-Warnung anzeigen
add_action('admin_init', function() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Achtung:</strong> Dieses Plugin benötigt mindestens PHP 7.4. Ihre aktuelle Version ist ' . PHP_VERSION . '.</p></div>';
        });
    }
});

/**
 * Verarbeitet die Reisethemen und synchronisiert sie mit der Taxonomie
 * 
 * @param int $post_id Die Post-ID
 * @return bool|array Ergebnis der Synchronisation
 */
function ir_sync_reisethemen($post_id) {
    if (!$post_id || get_post_type($post_id) !== 'ir-tours') {
        return false;
    }

    // Prüfen, ob Metadaten existieren
    if (!ir_meta_exists_safe($post_id, 'reisethemen_meta')) {
        return false;
    }

    // Sichere Metadatenabfrage verwenden
    $selected_terms = ir_get_post_meta_safe($post_id, 'reisethemen_meta', true);
    
    // Robustere Überprüfung für alle möglichen leeren Werte
    if (empty($selected_terms) || $selected_terms === 'false' || $selected_terms === false || $selected_terms === null) {
        wp_set_object_terms($post_id, [], 'reisethemen');
        return ['status' => 'cleared', 'terms' => []];
    }
    
    // Sicherstellen, dass es ein Array ist
    if (!is_array($selected_terms)) {
        $selected_terms = [$selected_terms];
    }

    // Ungültige Werte filtern
    $selected_terms = array_filter($selected_terms, function($term) {
        return !empty($term) && $term !== 'false';
    });

    // Alphabetisch sortieren
    $terms_sorted = [];
    foreach ($selected_terms as $term_id_or_slug) {
        // Fehler beim Zugriff auf Term-Eigenschaften vermeiden
        try {
            $term = null;
            if (is_numeric($term_id_or_slug)) {
                $term = @get_term_by('id', intval($term_id_or_slug), 'reisethemen');
            } else {
                $term = @get_term_by('slug', sanitize_text_field($term_id_or_slug), 'reisethemen');
            }
            
            if ($term && !is_wp_error($term) && isset($term->name) && isset($term->term_id)) {
                $terms_sorted[$term->name] = $term->term_id;
            }
        } catch (Exception $e) {
            // Fehler beim Term-Zugriff abfangen und ignorieren
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Fehler beim Zugriff auf Reisethemen-Term: ' . $e->getMessage());
            }
        }
    }

    // Wenn keine gültigen Terms gefunden wurden
    if (empty($terms_sorted)) {
        wp_set_object_terms($post_id, [], 'reisethemen');
        return ['status' => 'cleared', 'terms' => []];
    }

    // Sortieren und Taxonomie-Terme setzen
    ksort($terms_sorted);
    $result = wp_set_object_terms($post_id, array_values($terms_sorted), 'reisethemen', false);
    
    return [
        'status' => is_wp_error($result) ? 'error' : 'success',
        'terms' => array_values($terms_sorted),
        'error' => is_wp_error($result) ? $result->get_error_message() : null
    ];
}

// Vermeidung doppelter Speichervorgänge
function ir_prevent_duplicate_saves() {
    static $is_saving = false;
    
    if ($is_saving) {
        return false;
    }
    
    $is_saving = true;
    
    // Automatisches Reset nach 10 Sekunden (für den Fall eines Fehlers)
    register_shutdown_function(function() use (&$is_saving) {
        $is_saving = false;
    });
    
    return true;
}

// Synchronisation beim Speichern über Hook
add_action('save_post', function($post_id) {
    if (
        get_post_type($post_id) !== 'ir-tours' ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        wp_is_post_revision($post_id) || 
        wp_is_post_autosave($post_id)
    ) {
        return;
    }

    // Berechtigungen prüfen
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Doppelte Speichervorgänge verhindern
    if (!ir_prevent_duplicate_saves()) {
        return;
    }

    // Synchronisieren mit Fehlerunterdrückung
    @ir_sync_reisethemen($post_id);
}, 99);

// Hinweis bei mehreren Reisethemen
add_action('save_post', function($post_id) {
    if (
        get_post_type($post_id) === 'ir-tours' &&
        !wp_is_post_revision($post_id) && 
        !wp_is_post_autosave($post_id)
    ) {
        // Sichere Metadatenabfrage verwenden
        $reisethemen_meta = ir_get_post_meta_safe($post_id, 'reisethemen_meta', true);
        
        // Nur, wenn mehrere Themen ausgewählt sind
        if (
            !empty($reisethemen_meta) && 
            is_array($reisethemen_meta) &&
            count(array_filter($reisethemen_meta, function($term) { 
                return !empty($term) && $term !== 'false'; 
            })) >= 2
        ) {
            add_filter('redirect_post_location', function($location) {
                return add_query_arg('reisethemen_warning', '1', $location);
            });
        }
    }
}, 100);

// Gutenberg-Hinweis
add_action('admin_notices', function() {
    if (isset($_GET['reisethemen_warning']) && $_GET['reisethemen_warning'] === '1') {
        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>Achtung:</strong> Sie haben mehrere Reisethemen ausgewählt.</p>
        </div>';
    }
});

// AJAX-Handler für Fehler
add_action('wp_ajax_nopriv_ir_manual_reisethemen_sync', function() {
    wp_send_json_error('Sie müssen angemeldet sein, um diese Aktion durchführen zu können.');
});

// AJAX-Synchronisation (Button)
add_action('wp_ajax_ir_manual_reisethemen_sync', function() {
    // Nonce prüfen, falls vorhanden
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'ir_reisethemen_sync_nonce')) {
        wp_send_json_error('Sicherheitsüberprüfung fehlgeschlagen.');
        return;
    }

    // Berechtigungen prüfen
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Nicht erlaubt.');
        return;
    }

    // Post-ID validieren
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error('Keine gültige Post-ID angegeben.');
        return;
    }

    // Prüfen, ob es sich um einen ir-tours-Beitrag handelt
    if (get_post_type($post_id) !== 'ir-tours') {
        wp_send_json_error('Dies ist kein gültiger IR-Tours Beitrag.');
        return;
    }
    
    // Doppelte Speichervorgänge verhindern
    if (!ir_prevent_duplicate_saves()) {
        wp_send_json_error('Es läuft bereits ein Speichervorgang. Bitte warten Sie einen Moment.');
        return;
    }

    // Synchronisieren mit Fehlerunterdrückung
    $result = @ir_sync_reisethemen($post_id);
    
    if ($result === false) {
        wp_send_json_error('Synchronisation fehlgeschlagen. Post konnte nicht bearbeitet werden.');
        return;
    }
    
    if (isset($result['status']) && $result['status'] === 'error') {
        wp_send_json_error('Fehler beim Setzen der Taxonomie-Terme: ' . ($result['error'] ?? 'Unbekannter Fehler'));
        return;
    }
    
    if (isset($result['status']) && $result['status'] === 'cleared') {
        wp_send_json_success([
            'message' => 'Alle Reisethemen wurden entfernt.',
            'count' => 0
        ]);
        return;
    }
    
    $term_count = count($result['terms'] ?? []);
    wp_send_json_success([
        'message' => "Reisethemen erfolgreich synchronisiert. ($term_count Themen gesetzt)",
        'count' => $term_count
    ]);
});

// Editor-Button + JS
add_action('enqueue_block_editor_assets', function() {
    try {
        // Nur für ir-tours-Beiträge laden
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'ir-tours') {
            return;
        }
        
        // Prüfen, ob die Datei existiert
        $js_path = plugin_dir_path(__FILE__) . 'assets/reisethemen-popup.js';
        if (!file_exists($js_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('IR Tours: JavaScript-Datei nicht gefunden: ' . $js_path);
            }
            return;
        }
        
        wp_enqueue_script(
            'reisethemen-popup',
            plugin_dir_url(__FILE__) . 'assets/reisethemen-popup.js',
            ['jquery', 'wp-data', 'wp-editor'],
            filemtime($js_path), // Cache-Busting
            true
        );
    
        wp_localize_script('reisethemen-popup', 'irSyncAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ir_reisethemen_sync_nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__),
        ]);
    } catch (Exception $e) {
        // Fehler stumm abfangen und ggf. loggen
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Fehler beim Laden der Reisethemen-JS: ' . $e->getMessage());
        }
    }
});

// Elementor-Support hinzufügen
add_action('elementor/editor/before_enqueue_scripts', function() {
    try {
        // Prüfen, ob die Datei existiert
        $js_path = plugin_dir_path(__FILE__) . 'assets/reisethemen-popup.js';
        if (!file_exists($js_path) || get_post_type() !== 'ir-tours') {
            return;
        }
        
        wp_enqueue_script(
            'reisethemen-popup-elementor',
            plugin_dir_url(__FILE__) . 'assets/reisethemen-popup.js',
            ['jquery'],
            filemtime($js_path), // Cache-Busting
            true
        );
    
        wp_localize_script('reisethemen-popup-elementor', 'irSyncAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ir_reisethemen_sync_nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__),
            'isElementor' => 'true'
        ]);
    } catch (Exception $e) {
        // Fehler stumm abfangen
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Fehler beim Laden der Reisethemen-JS für Elementor: ' . $e->getMessage());
        }
    }
});

// Kontinent-Sync einbinden, falls vorhanden, mit Fehlerunterdrückung
if (file_exists(plugin_dir_path(__FILE__) . 'ir-kontinent-sync.php')) {
    @include_once plugin_dir_path(__FILE__) . 'ir-kontinent-sync.php';
}