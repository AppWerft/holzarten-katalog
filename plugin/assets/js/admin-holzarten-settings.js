document.addEventListener('DOMContentLoaded', function () {
    const lupeCheckbox = document.getElementById('hk-holzarten-lupe');
    const descriptionCheckbox = document.getElementById('hk-holzarten-description')
    const latCheckbox = document.getElementById('hk-holzarten-lat')
    const gerCheckbox = document.getElementById('hk-holzarten-ger')
    
    
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
        const descriptionEnabled = descriptionCheckbox.checked?'block':'none'
        const latEnabled = latCheckbox.checked?'block':'none'
        const gerEnabled = gerCheckbox.checked?'block':'none'
        

        preview.style.setProperty('width', size + 'px');
        preview.style.setProperty('height', size + 'px');
        preview.style.setProperty('aspect-ratio', '1 / 1');
        document.getElementById('hk_beschreibung').style.setProperty('display',descriptionEnabled)
        document.getElementById('hk_botname').style.setProperty('display',latEnabled)
        document.getElementById('hk_holzname').style.setProperty('display',gerEnabled)
        

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

