jQuery(function ($) {
    const selector =
        'select.attribute_values[data-taxonomy="pa_holzart"]';

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

document.addEventListener('DOMContentLoaded', function () {

    const sizeInput = document.getElementById('hk-wood-size');
    const roundInput = document.getElementById('hk-holzarten-round');
    const preview = document.getElementById('hk-wood-preview');

    if (!sizeInput || !roundInput || !preview) {
        return;
    }

    function updatePreview() {
        const size = parseInt(sizeInput.value, 10) || 120;

        preview.style.width = size + 'px';
        preview.style.height = size + 'px';
        preview.style.borderRadius = roundInput.checked ? '50%' : '0';

        // Optional: Größenwert neben dem Slider aktualisieren
        const sizeValue = document.getElementById('hk-wood-size-value');

        if (sizeValue) {
            sizeValue.textContent = size + ' px';
        }
    }

    // Live-Aktualisierung
    sizeInput.addEventListener('input', updatePreview);
    roundInput.addEventListener('change', updatePreview);

    // Initiale Darstellung
    updatePreview();
});

