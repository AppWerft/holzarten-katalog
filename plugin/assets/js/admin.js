jQuery(document).ready(function ($) {

    $('.hk-status-toggle').on('change', function () {

        const checkbox = $(this);
        const id = checkbox.data('id');
        const active = checkbox.is(':checked');
        const label = checkbox.siblings('.hk-status-label');

        $.post(hkAdmin.ajaxUrl, {
            action: 'hk_toggle_status',
            nonce: hkAdmin.nonce,
            id: id,
            active: active ? '1' : '0'
        })

            .done(function (response) {

                if (response.success) {
                    label.text(active ? 'Aktiv' : 'Inaktiv');
                } else {
                    checkbox.prop('checked', !active);
                    alert(response.data.message || 'Speichern fehlgeschlagen.');
                }

            })

            .fail(function (xhr, status, error) {

                console.error('AJAX-Fehler');
                console.error('HTTP-Status:', xhr.status);
                console.error('Status:', status);
                console.error('Fehler:', error);
                console.error('Antwort:', xhr.responseText);

                checkbox.prop('checked', !active);

            });

    });

});

// Schalter für Filterung "aktiv"
jQuery(document).ready(function ($) {
    const $activeFilter = $('#hk-only-active');
    const $table = $('#hk-catalog-table');
    if (!$activeFilter.length || !$table.length) {
        return;
    }
    function filterActiveRows() {
        const onlyActive = $activeFilter.is(':checked');
        $table.find('tbody tr').each(function () {
            const row = $(this);
            const statusCheckbox = row.find('.hk-status-toggle').first();
            if (!statusCheckbox.length) {
                return;
            }
            row.toggle(
                !onlyActive || statusCheckbox.is(':checked')
            );
        });
    }

    // Filter bei Änderung des Schalters anwenden.
    $activeFilter.on('change', filterActiveRows);

    // Auch beim initialen Laden anwenden.
    filterActiveRows();

});

// Bild hochladen über Zwischenablage
jQuery(document).ready(function ($) {

    const $pasteArea = $('#hk-image-paste');
    const $preview = $('#hk-image-paste');
    const $fileInput = $('#image_file');

    let previewUrl = null;

    function showPreview(file) {

        // Alte Vorschau-URL freigeben.
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        $preview.css({
            padding: 0,
            overflow: 'hidden'
        });
        $preview.empty().append(
            $('<img>', {
                src: previewUrl,
                css: {
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover',
                    display: 'block'
                }
            })
        );
    }

    // Klassische Dateiauswahl.
    $fileInput.on('change', function () {

        const file = this.files[0];

        if (file) {
            showPreview(file);
        }
    });

    // Bild aus der Zwischenablage übernehmen.
    $pasteArea.on('paste', function (event) {

        event.preventDefault();

        const clipboard = event.originalEvent.clipboardData;

        for (const item of clipboard.items) {

            if (!item.type.startsWith('image/')) {
                continue;
            }

            const blob = item.getAsFile();

            if (!blob) {
                continue;
            }

            // Dateiendung anhand des MIME-Typs bestimmen.
            const extensions = {
                'image/jpeg': 'jpg',
                'image/png': 'png',
                'image/webp': 'webp'
            };
            const extension = extensions[blob.type];
            if (!extension) {
                continue;
            }
            const file = new File(
                [blob],
                'clipboard-image.' + extension,
                { type: blob.type }
            );
            // File-Input wie bei einer normalen Dateiauswahl befüllen.
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            $fileInput[0].files = dataTransfer.files;
            // Vorschau anzeigen.
            showPreview(file);

            // Vorhandene change-Handler auslösen.
            // $fileInput.trigger('change');
            console.log('Bild übernommen:', file);
            break;
        }
    });
    const $deleteButton = $('#hk-delete-image-button');
    const $deleteFlag = $('#hk-delete-image');

    // Bild löschen bzw. Auswahl verwerfen.
    $deleteButton.on('click', function () {

        // File-Input leeren.
        $fileInput.val('');

        // Vorschau entfernen.
        $preview.empty();

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        // Löschauftrag für ein bereits gespeichertes Bild setzen.
        $deleteFlag.val('1');

        // Aktuelle Bildanzeige ausblenden.
        $('#hk-current-image').hide();
    });

});


jQuery(document).ready(function ($){
    const selector = 'select.attribute_values[data-taxonomy="pa_holzart"]';
    
    // Bereits vorhandene Mehrfachauswahlen auf eine reduzieren.
    $(selector).each(function () {
        const values = $(this).val();

        if (values && values.length > 1) {
            $(this).val([values[0]]).trigger('change');
        }
    });
    // Bei einer neuen Auswahl die bisherige ersetzen.
    $(document).on('select2:select', selector, function (event) {
        const selected = event.params.data.id;
        $(this)
            .val([selected])
            .trigger('change');
    });
});

