document.addEventListener('DOMContentLoaded', function () {
    const lupeCheckbox = document.getElementById('hk-holzarten-lupe');
    const sizeInput = document.getElementById('hk-wood-size');
    const roundInput = document.getElementById('hk-holzarten-round');
    const preview = document.getElementById('hk-wood-preview');

    if (!lupeCheckbox || !sizeInput || !roundInput || !preview) {
        return;
    }
    
    // Lupenfunktion
    const updateZoomCursor = () => {
        preview.classList.toggle(
            'hk-wood-zoom',
            lupeCheckbox.checked
        );
    };

    // Bildvorschau
    const updatePreview = () => {
        const size = parseInt(sizeInput.value, 10) || 120;

        preview.style.setProperty('width', size + 'px');
        preview.style.setProperty('height', size + 'px');
        preview.style.setProperty('aspect-ratio', '1 / 1');
        preview.style.setProperty(
            'border-radius',
            roundInput.checked ? '50%' : '0'
        );
    };

    // Live-Aktualisierung
    sizeInput.addEventListener('input', updatePreview);
    roundInput.addEventListener('change', updatePreview);
    lupeCheckbox.addEventListener('change', updateZoomCursor);
    // Zoom / Lightbox
    const openZoom = () => {
        if (!lupeCheckbox.checked) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.className = 'hk-zoom-overlay';

        const zoomImage = document.createElement('img');
        zoomImage.src = preview.src;
        zoomImage.alt = preview.alt;

        overlay.appendChild(zoomImage);
        document.body.appendChild(overlay);

        // Schließen durch Klick
        overlay.addEventListener('click', () => {
            overlay.remove();
        });

        // ESC schließt die Vergrößerung
        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                overlay.remove();
                document.removeEventListener('keydown', closeOnEscape);
            }
        };

        document.addEventListener('keydown', closeOnEscape);
    };

    // Klick auf Vorschaubild
    preview.addEventListener('click', openZoom);
    // Initiale Darstellung
    updatePreview();
    updateZoomCursor();
});

