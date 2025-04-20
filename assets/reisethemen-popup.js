jQuery(document).ready(function($) {
    let syncWasClicked = false;
    let isSyncing = false;

    // 1. Standard-Popup bei mehreren Themen
    $(document).on('click', '.editor-post-publish-button, .editor-post-publish-panel__header-publish-button', function(e) {
        // Nur prüfen, wenn noch nicht synchronisiert wurde
        if (!syncWasClicked) {
            // Prüfen, ob überhaupt Reisethemen ausgewählt sind, bevor wir warnen
            const selected = $('input[name^="reisethemen_meta[\"][value=\"true\"]');
            if (selected.length > 0) {
                alert('Sie haben vergessen zu synchronisieren. Bitte drücken Sie zuerst den Synchronisations-Button. Danke.');
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
            // Wenn keine Reisethemen ausgewählt sind, kein Blockieren notwendig
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
        // Verhindert Doppelklicks
        if (isSyncing) return;
        isSyncing = true;
        
        syncWasClicked = true;
        const postId = wp.data.select('core/editor')?.getCurrentPostId();
        
        if (!postId) {
            syncStatus.text('Fehler: Konnte Post-ID nicht ermitteln.');
            isSyncing = false;
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).text('⏳ Synchronisiere...');
        syncStatus.text('');

        $.post(irSyncAjax.ajaxurl, {
            action: 'ir_manual_reisethemen_sync',
            post_id: postId,
            nonce: irSyncAjax.nonce || '' // Nonce hinzufügen, falls vorhanden
        }).done(function(response) {
            if (response.success) {
                btn.text('✅ Synchronisiert – speichere...');
                syncStatus.text('Letzte Synchronisation: erfolgreich');
                
                // Nur einmal speichern mit Fehlerbehandlung
                try {
                    wp.data.dispatch('core/editor').savePost().then(() => {
                        btn.prop('disabled', false).text('🔄 Synchronisieren & Speichern');
                        // Nach Erfolg wieder aktivieren
                        setTimeout(() => { isSyncing = false; }, 1000);
                    }).catch(error => {
                        console.error('Speichern fehlgeschlagen:', error);
                        syncStatus.text('Fehler beim Speichern');
                        btn.prop('disabled', false).text('🔄 Synchronisieren & Speichern');
                        isSyncing = false;
                    });
                } catch (error) {
                    console.error('Fehler beim Speichern:', error);
                    btn.prop('disabled', false).text('🔄 Synchronisieren & Speichern');
                    isSyncing = false;
                }
            } else {
                btn.text('❌ Fehler');
                syncStatus.text('Letzte Synchronisation: fehlgeschlagen - ' + 
                    (response.data && typeof response.data === 'string' ? response.data : 'Unbekannter Fehler'));
                setTimeout(() => {
                    btn.prop('disabled', false).text('🔄 Synchronisieren & Speichern');
                    isSyncing = false;
                }, 3000);
            }
        }).fail(function(xhr, status, error) {
            btn.text('❌ Fehler');
            syncStatus.text('Letzte Synchronisation: AJAX-Fehler - ' + error);
            setTimeout(() => {
                btn.prop('disabled', false).text('🔄 Synchronisieren & Speichern');
                isSyncing = false;
            }, 3000);
        });
    });

    // 3. Button in Editor einfügen (verschiedene Gutenberg-Versionen testen)
    const insertButtonTopRight = () => {
        // Mehrere mögliche Selektoren testen
        const selectors = [
            '.editor-header__settings',
            '.edit-post-header__settings',
            '.edit-post-header-toolbar__right',
            '.editor-post-publish-panel__header',
            // JetEngine-spezifische Selektoren hinzugefügt
            '.jet-engine-meta-box-holder',
            '.elementor-panel-footer-content'
        ];
        
        let buttonInserted = false;
        
        // Durch alle Selektoren iterieren und den ersten verwenden, der gefunden wird
        for (const selector of selectors) {
            const controls = $(selector);
            if (controls.length && !controls.find('.ir-sync-button-added').length) {
                const wrapper = $('<div class="ir-sync-button-added" style="display:flex; align-items:center; gap:0.75em; margin-left:auto;"></div>');
                wrapper.append(syncButton).append(syncStatus);
                controls.append(wrapper);
                buttonInserted = true;
                console.log('IR Tours: Sync-Button eingefügt in', selector);
                break;
            }
        }
        
        // Fallback - in der Nähe des Speichern-Buttons platzieren
        if (!buttonInserted) {
            const saveBtn = $('.editor-post-publish-button__button');
            if (saveBtn.length && !$('.ir-sync-button-added').length) {
                const wrapper = $('<div class="ir-sync-button-added" style="display:flex; align-items:center; gap:0.75em; margin:10px 0;"></div>');
                wrapper.append(syncButton).append(syncStatus);
                saveBtn.parent().before(wrapper);
                console.log('IR Tours: Sync-Button als Fallback eingefügt');
            } else {
                // Zweiter Fallback: JetEngine-Checkboxen finden und danach platzieren
                const jetCheckboxes = $('input[name^="reisethemen_meta["]');
                if (jetCheckboxes.length && !$('.ir-sync-button-added').length) {
                    const firstCheckbox = jetCheckboxes.first();
                    const wrapper = $('<div class="ir-sync-button-added" style="display:flex; align-items:center; gap:0.75em; margin:10px 0 20px;"></div>');
                    wrapper.append(syncButton).append(syncStatus);
                    firstCheckbox.closest('.cx-control').after(wrapper);
                    console.log('IR Tours: Sync-Button neben Reisethemen-Checkbox eingefügt');
                }
            }
        }
    };

    // Beobachter für DOM-Änderungen
    const observer = new MutationObserver(function(mutations) {
        if (!$('.ir-sync-button-added').length) {
            insertButtonTopRight();
        }
    });
    
    // Beobachtungskonfiguration mit besserer Performance
    observer.observe(document.body, { 
        childList: true, 
        subtree: true,
        attributes: false,
        characterData: false
    });

    // Mehrere Versuche, um sicherzustellen, dass der Button eingefügt wird
    insertButtonTopRight();
    setTimeout(insertButtonTopRight, 1000);
    setTimeout(insertButtonTopRight, 3000);
});