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
    const $preview = $('#hk-image-preview');

    $pasteArea.on('paste', function (event) {
        console.log(event)
        const clipboard = event.originalEvent.clipboardData;

        for (const item of clipboard.items) {

            if (!item.type.startsWith('image/')) {
                continue;
            }

            const blob = item.getAsFile();

            if (!blob) {
                continue;
            }

            const imageUrl = URL.createObjectURL(blob);

            $preview.empty().append(
                $('<img>', {
                    src: imageUrl,
                    css: {
                        maxWidth: '200px',
                        maxHeight: '200px'
                    }
                })
            );

            console.log('Bild übernommen:', blob);

            // Hier folgt später der Upload zu WordPress.
            break;
        }
    });

});