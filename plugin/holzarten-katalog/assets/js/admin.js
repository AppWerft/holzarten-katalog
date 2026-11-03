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

    if (!$activeFilter.length) {
        console.log('kein Schalter')
        return;
    }
    if (!$table.length) {
        console.log('table nicht da ')
        return;
    }
    function filterActiveRows() {
        console.log('filterActiveRows')
        const onlyActive = $activeFilter.is(':checked');

        $table.find('tbody tr').each(function () {

            const row = $(this);
            const statusCheckbox = row.find('.hk-item-active').first();

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