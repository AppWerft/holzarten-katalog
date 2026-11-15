document.addEventListener('DOMContentLoaded', function () {

    const preview = document.getElementById('settings-preview-map');
    const heightInput = document.getElementById('hk-map-height');
    const widthInput = document.getElementById('hk-map-width');

    if (!preview) {
        return;
    }

    // Darstellung des Kartencontainers
    const updatePreview = () => {
        const w = parseInt(widthInput.value, 10) || 120;
        const h = parseInt(heightInput.value, 10) || 50;
        preview.style.display = 'block';
        preview.style.height = h+'%';
        preview.style.border = '2px dotted darkgreen';
        preview.style.width = w;
    }
    // Leaflet-Karte initialisieren
    const map = L.map(preview, {
        zoomControl: false
    }).setView([51.3406751, 12.4256434], 12);

    // OpenStreetMap-Kacheln
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap-Mitwirkende'
        }
    ).addTo(map);
    heightInput.addEventListener('change', updatePreview);
    widthInput.addEventListener('change', updatePreview);

});