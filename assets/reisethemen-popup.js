jQuery(document).ready(function($) {
    let syncWasClicked = false;
    // 1. Standard-Popup bei mehreren Themen
    $(document).on('click', '.editor-post-publish-button, .editor-post-publish-panel__header-publish-button', function(e) {
        if (!syncWasClicked) {
            alert('Sie haben vergessen zu synchronisieren. Bitte drücken Sie zuerst den Synchronisations-Button. Danke.');
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        const selected = $('input[name^="reisethemen_meta[\"][value=\"true\"]');
        if (selected.length >= 2) {
            
if (!confirm('Sie haben 2 oder mehr Reisethemen gewählt. Sind Sie sicher, dass Sie speichern möchten?')) {
    e.preventDefault();
    e.stopImmediatePropagation();

    setTimeout(() => {
        // JetEngine Checkboxen abwählen
        const jetCheckboxes = $('input[name^="reisethemen_meta["]');
        console.log(`[REISETHEMEN DEBUG] ${jetCheckboxes.length} JetEngine-Checkbox(en) gefunden`);
        jetCheckboxes.each(function(index, el) {
            const toggle = $(el).siblings('.cx-checkbox-item');
            if (toggle.length && el.checked) {

                toggle.trigger('click');
            }
        });

        // Gutenberg-Taxonomie-Checkboxen abwählen
        const wpCheckboxes = $('input.components-checkbox-control__input:checked');
        console.log(`[REISETHEMEN DEBUG] ${wpCheckboxes.length} WP-Taxonomie-Checkbox(en) gefunden`);
        wpCheckboxes.each(function(index, el) {

            el.click();
        });

    }, 100);

    return false;
}

            
        }
    });

    // 2. Sync-Button und Feedback erzeugen
    const syncButton = $('<button type="button" class="components-button is-primary">🔄 Synchronisieren & Speichern</button>');
    const syncStatus = $('<span style="margin-left:0.75em;font-weight:normal;font-size:0.9em;"></span>');

    syncButton.on('click', function() {
        syncWasClicked = true;
        const postId = wp.data.select('core/editor').getCurrentPostId();
        const btn = $(this);
        btn.prop('disabled', true).text('⏳ Synchronisiere...');
        syncStatus.text('');

        $.post(irSyncAjax.ajaxurl, {
            action: 'ir_manual_reisethemen_sync',
            post_id: postId
        }, function(response) {
            if (response.success) {
                btn.text('✅ Synchronisiert – speichere...');
                syncStatus.text('Letzte Synchronisation: erfolgreich');
                setTimeout(() => {
                    wp.data.dispatch('core/editor').savePost();
                    setTimeout(() => {
                        wp.data.dispatch('core/editor').savePost();
                        btn.prop('disabled', false).text('🔄 Synchronisieren & Speichern');
                    }, 800);
                }, 600);
            } else {
                btn.text('❌ Fehler');
                syncStatus.text('Letzte Synchronisation: fehlgeschlagen');
                setTimeout(() => {
                    btn.prop('disabled', false).text('🔄 Synchronisieren & Speichern');
                }, 3000);
            }
        });
    });

    // 3. Button in obere rechte Ecke (editor-header__settings) einfügen
    const insertButtonTopRight = () => {
        const controls = $('.editor-header__settings');
        if (controls.length && !controls.find('.ir-sync-button-added').length) {
            const wrapper = $('<div class="ir-sync-button-added" style="display:flex; align-items:center; gap:0.75em; margin-left:auto;"></div>');
            wrapper.append(syncButton).append(syncStatus);
            controls.append(wrapper);
        }
    };

    const observer = new MutationObserver(insertButtonTopRight);
    observer.observe(document.body, { childList: true, subtree: true });

    // Initialversuch
    insertButtonTopRight();
});




