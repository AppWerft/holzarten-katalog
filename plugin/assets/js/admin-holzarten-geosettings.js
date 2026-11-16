document.addEventListener('DOMContentLoaded', function () {

    const preview = document.getElementById('settings-preview-map'),
    heightInput = document.getElementById('hk-map-height'),
    widthInput = document.getElementById('hk-map-width'),
    radiusInput = document.getElementById('hk-map-radius'),
    updatePreviewSize=()=> {
        const w = parseInt(widthInput.value, 10) || 100,
        h = parseInt(heightInput.value, 10) || 200,
        r = parseInt(radiusInput.value, 10) || 0;
        preview.style.height = h + 'px';
        preview.style.width = w + '%';
        preview.style.borderRadius = r + 'px';
    }   
    if (!preview || !heightInput || !widthInput) {
        return;
    }

    // Darstellung des Kartencontainers
    const updatePreview = () => {
        updatePreviewSize()
        // Leaflet-Größe neu berechnen
        map.invalidateSize();
    };

    // Initiale Größe setzen
    updatePreviewSize();

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
    radiusInput.addEventListener('change', updatePreview);
    
    

    // Nach dem Rendern Größe aktualisieren
    setTimeout(() => map.invalidateSize(), 100);
});