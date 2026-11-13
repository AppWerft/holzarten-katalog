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

