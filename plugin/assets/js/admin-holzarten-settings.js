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

