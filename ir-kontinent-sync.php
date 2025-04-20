<?php
/**
 * Plugin-Datei: Synchronisiert das Meta-Feld 'kontinent' mit der Taxonomy 'kontinent_taxon' beim Speichern eines Beitrags vom Typ 'ir-tours'
 */

// Sicherstellen, dass die Funktion nur einmal definiert wird
if (!function_exists('ir_sync_kontinent_with_taxonomy')) {
    add_action('save_post_ir-tours', 'ir_sync_kontinent_with_taxonomy');

    /**
     * Synchronisiert das Meta-Feld "kontinent" mit der Taxonomie "kontinent_taxon"
     * 
     * @param int $post_id Die Post-ID
     * @return void
     */
    function ir_sync_kontinent_with_taxonomy($post_id) {
        // Verhindere Endlosschleifen oder Autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (wp_is_post_autosave($post_id)) return;

        // Prüfen, ob der Benutzer Bearbeitungsrechte hat
        if (!current_user_can('edit_post', $post_id)) return;

        // Hole den Kontinent-Wert aus dem Metafeld
        $kontinent = get_post_meta($post_id, 'kontinent', true);

        // Fallback: Falls leer, versuche es über $_POST
        if (empty($kontinent) && isset($_POST['kontinent'])) {
            $kontinent = sanitize_text_field($_POST['kontinent']);
        }

        if (!empty($kontinent)) {
            // Stelle sicher, dass die Taxonomie existiert
            if (!taxonomy_exists('kontinent_taxon')) {
                return;
            }

            // Stelle sicher, dass der Begriff in der Taxonomie existiert
            $term = term_exists($kontinent, 'kontinent_taxon');
            if (!$term) {
                $term = wp_insert_term($kontinent, 'kontinent_taxon');
            }

            // Setze den Beitrag in der Taxonomie (ersetze vorherige Zuordnungen)
            if (!is_wp_error($term)) {
                wp_set_object_terms($post_id, intval($term['term_id']), 'kontinent_taxon', false);
            } else {
                error_log('Fehler beim Setzen der Kontinent-Taxonomie: ' . $term->get_error_message());
            }
        } else {
            // Wenn kein Kontinent ausgewählt ist, entferne alle Tax-Begriffe aus dieser Taxonomie
            wp_set_object_terms($post_id, array(), 'kontinent_taxon');
        }
    }
}