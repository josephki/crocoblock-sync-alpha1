<?php
/*
Plugin Name: IR Tours Reisethemen Sync (AJAX + Button)
Description: AJAX-Synchronisation der Reisethemen mit zusätzlichem Button und Feedback. Zeigt Hinweis bei zu alter PHP-Version.
Version: 3.2
Author: Joseph Kisler - Webwerkstatt, Freiung 16/2/4, A-4600 Wels
*/

require_once plugin_dir_path(__FILE__) . 'ir-session-guard.php';

// PHP-Version prüfen und Admin-Warnung anzeigen
add_action('admin_init', function() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Achtung:</strong> Dieses Plugin benötigt mindestens PHP 7.4. Ihre aktuelle Version ist ' . PHP_VERSION . '.</p></div>';
        });
    }
});

// Synchronisation beim Speichern über Hook
add_action('save_post', function($post_id) {
    if (
        get_post_type($post_id) !== 'ir-tours' ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    ) return;

    if (!metadata_exists('post', $post_id, 'reisethemen_meta')) return;

    $selected_terms = get_post_meta($post_id, 'reisethemen_meta', true);

    if (!$selected_terms || $selected_terms === 'false') {
        wp_set_object_terms($post_id, [], 'reisethemen');
        return;
    }

    if (!is_array($selected_terms)) {
        $selected_terms = [$selected_terms];
    }

    // Alphabetisch sortieren
    $terms_sorted = [];
    foreach ($selected_terms as $term_id_or_slug) {
        $term = is_numeric($term_id_or_slug)
            ? get_term_by('id', $term_id_or_slug, 'reisethemen')
            : get_term_by('slug', $term_id_or_slug, 'reisethemen');
        if ($term && !is_wp_error($term)) {
            $terms_sorted[$term->name] = $term->term_id;
        }
    }

    ksort($terms_sorted);
    wp_set_object_terms($post_id, array_values($terms_sorted), 'reisethemen', false);
}, 99);

// Hinweis bei mehreren Reisethemen
add_action('save_post', function($post_id) {
    if (
        get_post_type($post_id) === 'ir-tours' &&
        !empty(get_post_meta($post_id, 'reisethemen_meta', true)) &&
        is_array(get_post_meta($post_id, 'reisethemen_meta', true)) &&
        count(get_post_meta($post_id, 'reisethemen_meta', true)) >= 2
    ) {
        add_filter('redirect_post_location', function($location) {
            return add_query_arg('reisethemen_warning', '1', $location);
        });
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

// AJAX-Synchronisation (Button)
add_action('wp_ajax_ir_manual_reisethemen_sync', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Nicht erlaubt.');
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'ir-tours') {
        wp_send_json_error('Ungültige Anfrage.');
    }

    $selected_terms = get_post_meta($post_id, 'reisethemen_meta', true);

    if (!$selected_terms || $selected_terms === 'false') {
        wp_set_object_terms($post_id, [], 'reisethemen');
        wp_send_json_success('Reisethemen wurden entfernt.');
    }

    if (!is_array($selected_terms)) {
        $selected_terms = [$selected_terms];
    }

    // Alphabetisch sortieren
    $terms_sorted = [];
    foreach ($selected_terms as $term_id_or_slug) {
        $term = is_numeric($term_id_or_slug)
            ? get_term_by('id', $term_id_or_slug, 'reisethemen')
            : get_term_by('slug', $term_id_or_slug, 'reisethemen');
        if ($term && !is_wp_error($term)) {
            $terms_sorted[$term->name] = $term->term_id;
        }
    }

    ksort($terms_sorted);
    wp_set_object_terms($post_id, array_values($terms_sorted), 'reisethemen', false);

    wp_send_json_success('Reisethemen erfolgreich synchronisiert.');
});

// Editor-Button + JS
add_action('enqueue_block_editor_assets', function() {
    wp_enqueue_script(
        'reisethemen-popup',
        plugin_dir_url(__FILE__) . 'assets/reisethemen-popup.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('reisethemen-popup', 'irSyncAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
    ]);
});


require_once plugin_dir_path(__FILE__) . '/ir-kontinent-sync.php';
