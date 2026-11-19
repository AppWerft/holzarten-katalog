document.addEventListener('DOMContentLoaded', function () {

    const preview = document.getElementById('settings-preview-map');
    const heightInput = document.getElementById('hk-map-height');
    const widthInput = document.getElementById('hk-map-width');
    const radiusInput = document.getElementById('hk-map-radius');
    const providerSelect = document.getElementById('hk-tile-provider');

    if (!preview || !heightInput || !widthInput) {
        return;
    }

    const updatePreviewSize = () => {
        const w = parseInt(widthInput.value, 10) || 100;
        const h = parseInt(heightInput.value, 10) || 200;
        const r = parseInt(radiusInput?.value, 10) || 0;

        preview.style.height = h + 'px';
        preview.style.width = w + '%';
        preview.style.borderRadius = r + 'px';
    };

    updatePreviewSize();

    const map = L.map(preview, {
        zoomControl: false
    }).setView([51.3406751, 12.4256434], 12);

    let tileLayer = null;

    const setTileProvider = (provider) => {

        if (tileLayer) {
            map.removeLayer(tileLayer);
        }

        tileLayer = L.tileLayer.provider(provider);
        tileLayer.addTo(map);
    };

    // Gespeicherten Anbieter verwenden
    setTileProvider(
        providerSelect?.value || 'OpenStreetMap.Mapnik'
    );

    const updatePreview = () => {
        updatePreviewSize();
        map.invalidateSize();
    };

    heightInput.addEventListener('change', updatePreview);
    widthInput.addEventListener('change', updatePreview);
    radiusInput?.addEventListener('change', updatePreview);

    // Anbieter unmittelbar bei Auswahl wechseln
    providerSelect?.addEventListener('change', function () {
        setTileProvider(this.value);
    });

    setTimeout(() => map.invalidateSize(), 100);
});