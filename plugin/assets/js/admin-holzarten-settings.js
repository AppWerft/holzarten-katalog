
document.addEventListener('DOMContentLoaded', function () {
    const lupeCheckbox = document.getElementById('hk-holzarten-lupe');
    const descriptionCheckbox = document.getElementById('hk-holzarten-description');
    const latCheckbox = document.getElementById('hk-holzarten-lat');
    const gerCheckbox = document.getElementById('hk-holzarten-ger');

    const sizeInput = document.getElementById('hk-wood-size');
    const roundInput = document.getElementById('hk-holzarten-round');
    const preview = document.getElementById('hk-wood-preview');

    const description = document.getElementById('hk_beschreibung');
    const botanicalName = document.getElementById('hk_botname');
    const woodName = document.getElementById('hk_holzname');

    // Prüfen, ob alle benötigten Elemente vorhanden sind
    if (
        !lupeCheckbox ||
        !descriptionCheckbox ||
        !latCheckbox ||
        !gerCheckbox ||
        !sizeInput ||
        !roundInput ||
        !preview ||
        !description ||
        !botanicalName ||
        !woodName
    ) {
        return;
    }

    // Lupenfunktion
    const updateZoomCursor = () => {
        preview.classList.toggle(
            'hk-wood-zoom',
            lupeCheckbox.checked
        );
    };

    // Bild- und Textvorschau
    const updatePreview = () => {
        const size = parseInt(sizeInput.value, 10) || 120;

        preview.style.width = size + 'px';
        preview.style.height = size + 'px';
        preview.style.aspectRatio = '1 / 1';
        preview.style.borderRadius = roundInput.checked ? '50%' : '0';

        description.style.display =
            descriptionCheckbox.checked ? 'block' : 'none';

        botanicalName.style.display =
            latCheckbox.checked ? 'block' : 'none';

        woodName.style.display =
            gerCheckbox.checked ? 'block' : 'none';
    };

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

        // Gemeinsame Schließfunktion
        const closeZoom = () => {
            overlay.remove();
            document.removeEventListener('keydown', closeOnEscape);
        };
        // ESC schließt die Vergrößerung
        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                closeZoom();
            }
        };
        document.addEventListener('keydown', closeOnEscape);
        // Schließen durch Klick
        overlay.addEventListener('click', closeZoom);
    };

    // Live-Aktualisierung
    sizeInput.addEventListener('input', updatePreview);
    [
        roundInput,
        descriptionCheckbox,
        latCheckbox,
        gerCheckbox
    ].forEach(element => {
        element.addEventListener('change', updatePreview);
    });
    lupeCheckbox.addEventListener('change', updateZoomCursor);
    // Klick auf Vorschaubild
    preview.addEventListener('click', openZoom);

    // Initiale Darstellung
    updatePreview();
    updateZoomCursor();
});