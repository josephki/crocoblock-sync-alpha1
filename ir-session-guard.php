<?php
/**
 * Sessionschutz-Zauberstein
 * Verhindert REST/API-Probleme durch PHP session_start()
 */

add_action('init', 'ir_guard_php_session', 1);

function ir_guard_php_session() {
    if (session_status() === PHP_SESSION_NONE &&
        !defined('DOING_AJAX') &&
        !(defined('REST_REQUEST') && REST_REQUEST === true)
    ) {
        session_start();

        if (!isset($_SESSION['ir_initialized'])) {
            $_SESSION['ir_initialized'] = time();
        }

        add_action('shutdown', function () {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        });
    }
}
