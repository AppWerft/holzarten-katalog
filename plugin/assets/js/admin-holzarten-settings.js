document.addEventListener('DOMContentLoaded', function () {
    
    const lupeCheckbox = document.getElementById('hk-holzarten-lupe');
    const sizeInput = document.getElementById('hk-wood-size');
    const roundInput = document.getElementById('hk-holzarten-round');
    const preview = document.getElementById('hk-wood-preview');

    if (lupeCheckbox || !sizeInput || !roundInput || !preview) {
        return;
    }
    function updateZoomCursor() {
        woodPreview.classList.toggle(
            'hk-wood-zoom',
            lupeCheckbox.checked
        );
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
    lupeCheckbox.addEventListener('change',updateZoomCursor)
    // Initiale Darstellung
    updatePreview();
    updateZoomCursor()
});

