
document.addEventListener('DOMContentLoaded', function () {

    const preview = document.getElementById('settings-preview-map');
    const heightInput = document.getElementById('hk-map-height');
    const widthInput = document.getElementById('hk-map-width');
    const radiusInput = document.getElementById('hk-map-radius');
    const providerSelect = document.getElementById('hk-tile-provider');

    if (!preview || !heightInput || !widthInput) {
        return;
    }

    // Darstellung des Kartencontainers
    const updatePreviewSize = () => {
        const w = parseInt(widthInput.value, 10) || 100;
        const h = parseInt(heightInput.value, 10) || 200;
        const r = parseInt(radiusInput?.value, 10) || 0;

        preview.style.height = h + 'px';
        preview.style.width = w + '%';
        preview.style.borderRadius = r + 'px';

        map.invalidateSize();
    };

    // Leaflet-Karte initialisieren
    const map = L.map(preview, {
        zoomControl: false
    }).setView([51.3406751, 12.4256434], 12);

    // Kartenprovider wechseln
    let tileLayer;

    const setTileProvider = (provider) => {

        // Vorhandenen Layer entfernen
        if (tileLayer) {
            map.removeLayer(tileLayer);
        }

        // Neuen Provider laden
        tileLayer = L.tileLayer.provider(provider);

        tileLayer.addTo(map);
    };

    // Initiale Darstellung
    updatePreviewSize();

    setTileProvider(
        providerSelect?.value || 'OpenStreetMap.Mapnik'
    );

    // Ereignisse
    heightInput.addEventListener('change', updatePreviewSize);
    widthInput.addEventListener('change', updatePreviewSize);
    radiusInput?.addEventListener('change', updatePreviewSize);

    // Provider live wechseln
    providerSelect?.addEventListener('change', function () {
        setTileProvider(this.value);
    });

    // Nach dem Rendern Größe aktualisieren
    setTimeout(() => map.invalidateSize(), 100);
});
