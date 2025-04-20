<?php
/**
 * Fehlerunterdrückungslösung für IR Tours Reisethemen Sync
 * Verhindert verschiedene Fehlermeldungen und Warnungen im Zusammenhang mit Meta-Daten
 * 
 * @version 1.0
 */

// UMFASSENDE LÖSUNG: Alle Arten von Warnungen und Deprecated-Meldungen unterdrücken
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

/**
 * Sicherere Version von get_post_meta, die Fehler bei leeren Arrays verhindert
 * 
 * @param int    $post_id ID des Posts
 * @param string $key     Meta-Key
 * @param bool   $single  Einzeln oder als Array zurückgeben
 * @return mixed          Metawert oder leerer Standardwert
 */
function ir_get_post_meta_safe($post_id, $key, $single = true) {
    // Direkt mit try-catch umgeben, um jegliche Fehler zu unterdrücken
    try {
        // Direkte DB-Abfrage verwenden anstelle von get_post_meta
        global $wpdb;
        $meta_value = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
            $post_id,
            $key
        ));
        
        // Wenn kein Wert gefunden wurde
        if (null === $meta_value) {
            return $single ? '' : array();
        }
        
        // Wert deserialisieren
        $meta_value = maybe_unserialize($meta_value);
        
        // Leere Arrays behandeln
        if ($single && is_array($meta_value) && empty($meta_value)) {
            return '';
        }
        
        // Normale Rückgabe
        if ($single) {
            return $meta_value;
        } else {
            return array($meta_value);
        }
    } catch (Exception $e) {
        // Bei jedem Fehler einen sicheren Wert zurückgeben
        return $single ? '' : array();
    }
}

/**
 * Direkte Prüfung, ob ein Meta-Wert existiert, ohne WordPress-Funktionen zu verwenden
 * 
 * @param int    $post_id Die Post-ID
 * @param string $meta_key Der Meta-Key
 * @return bool            True wenn der Meta-Wert existiert, sonst false
 */
function ir_meta_exists_safe($post_id, $meta_key) {
    global $wpdb;
    $meta_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
        $post_id,
        $meta_key
    ));
    
    return !empty($meta_exists);
}

/**
 * Blockiert problematische Hooks von Drittanbieter-Plugins
 */
function ir_block_problematic_plugin_hooks() {
    global $wp_filter;
    
    // Liste von bekannten problematischen Hooks
    $hooks_to_check = [
        'init', 
        'admin_init',
        'wp_loaded',
        'admin_enqueue_scripts'
    ];
    
    foreach ($hooks_to_check as $hook) {
        if (!isset($wp_filter[$hook])) continue;
        
        // Durchlaufen aller Prioritäten
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $idx => $callback) {
                // Prüfen, ob es eine Callback-Funktion von Jet Data Importer oder Jet Theme Core ist
                if (is_array($callback['function']) && is_object($callback['function'][0])) {
                    $class_name = get_class($callback['function'][0]);
                    if (strpos($class_name, 'Jet_Data_Importer') !== false || 
                        strpos($class_name, 'Jet_Theme_Core') !== false) {
                        // Diese Callback-Funktion entfernen
                        unset($wp_filter[$hook]->callbacks[$priority][$idx]);
                    }
                }
            }
        }
    }
}

// Hooks für problematische Plugins blockieren
add_action('plugins_loaded', 'ir_block_problematic_plugin_hooks', 999);
