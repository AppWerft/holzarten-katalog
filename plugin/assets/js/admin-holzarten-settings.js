document.addEventListener('DOMContentLoaded', function () {
    const sizeInput = document.getElementById('hk-wood-size');
    const roundInput = document.getElementById('hk-holzarten-round');
    const preview = document.getElementById('hk-wood-preview');

    if (!sizeInput || !roundInput || !preview) {
        return;
    }

    function updatePreview() {
        const size = parseInt(sizeInput.value, 10) || 120;

        preview.style.setProperty('width', size + 'px');
        preview.style.setProperty('height', size + 'px');
        preview.style.setProperty('aspect-ratio', '1 / 1');
        preview.style.setProperty(
            'border-radius',
            roundInput.checked ? '50%' : '0'
        );
    }

    // Live-Aktualisierung
    sizeInput.addEventListener('input', updatePreview);
    roundInput.addEventListener('change', updatePreview);

    // Initiale Darstellung
    updatePreview();
});

