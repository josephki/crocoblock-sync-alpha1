<?php
/**
 * Sessionschutz-Zauberstein
 * Verhindert REST/API-Probleme durch PHP session_start()
 * 
 * @version 2.0
 */

// Sicherstellen, dass die Funktionen nur einmal definiert werden
if (!function_exists('ir_guard_php_session')) {
    // Frühe Priorität 1, um vor anderen init-Hooks ausgeführt zu werden
    add_action('init', 'ir_guard_php_session', 1);

    /**
     * Startet eine PHP-Session nur, wenn nötig und sicher
     * 
     * @return void
     */
    function ir_guard_php_session() {
        // Session nur starten, wenn:
        // 1. Noch keine aktiv
        // 2. Kein REST-Request
        // 3. Kein Ajax-Request
        if (session_status() === PHP_SESSION_NONE &&
            !(defined('DOING_AJAX') && DOING_AJAX) &&
            !(defined('REST_REQUEST') && REST_REQUEST === true)
        ) {
            // Session starten
            @session_start();

            // Optional: Setze Standardwerte
            if (!isset($_SESSION['ir_initialized'])) {
                $_SESSION['ir_initialized'] = time();
            }

            // Logging der Session nur bei aktivem WP_DEBUG
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[IR SESSION] Session gestartet: ' . date('Y-m-d H:i:s'));
            }

            // Session beim Shutdown schließen
            add_action('shutdown', function () {
                // Log für das Schließen der Session
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[IR SESSION] Session geschlossen: ' . date('Y-m-d H:i:s'));
                }

                // Session nach Verarbeitung sofort freigeben
                if (session_status() === PHP_SESSION_ACTIVE) {
                    @session_write_close();
                }
            });
        }
    }

    // Health Check für die Session im Admin, nur für Admins sichtbar
    add_action('admin_bar_menu', 'ir_session_health_check', 100);

    /**
     * Fügt einen Indikator zur Admin-Leiste hinzu, der den Session-Status anzeigt
     * 
     * @param WP_Admin_Bar $wp_admin_bar Admin Bar Objekt
     * @return void
     */
    function ir_session_health_check($wp_admin_bar) {
        // Nur für Administratoren anzeigen
        if (!current_user_can('administrator')) {
            return;
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $wp_admin_bar->add_node(array(
                'id'    => 'ir_session_status',
                'title' => 'Session aktiv',
                'href'  => '#',
                'meta'  => array('class' => 'ir-session-health-check')
            ));
        } else {
            $wp_admin_bar->add_node(array(
                'id'    => 'ir_session_status',
                'title' => 'Keine aktive Session',
                'href'  => '#',
                'meta'  => array('class' => 'ir-session-health-check')
            ));
        }
    }
}