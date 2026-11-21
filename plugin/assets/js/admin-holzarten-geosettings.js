
document.addEventListener('DOMContentLoaded', function () {

    const STANDARDPOSITION = [51.3406751, 12.4256434];

    const previewMap = document.getElementById('settings-preview-map');
    const previewWrapper = document.getElementById('settings-preview-wrapper');

    const heightInput = document.getElementById('hk-map-height');
    const widthInput = document.getElementById('hk-map-width');
    const radiusInput = document.getElementById('hk-map-radius');
    const positionSelect = document.getElementById('hk-map-position');

    const providerSelect = document.getElementById('hk-tile-provider');
    const pingcolorInput = document.getElementById('hk-map-pingcolor');

    if (!previewMap || !previewWrapper || !heightInput || !widthInput) {
        return;
    }

    // Vorschau initialisieren
    previewWrapper.style.display = 'flex';

    const updatePreviewSize = () => {

        const width = parseInt(widthInput.value, 10) || 100;
        const height = parseInt(heightInput.value, 10) || 200;
        const radius = parseInt(radiusInput?.value, 10) || 0;

        previewMap.style.width = width + '%';
        previewMap.style.height = height + 'px';
        previewMap.style.borderRadius = radius + 'px';

        const position = positionSelect?.value || 'left';

        previewWrapper.style.justifyContent =
            position === 'right' ? 'flex-end' :
                position === 'center' ? 'center' :
                    'flex-start';
    };

    updatePreviewSize();
    console.log('Preview:', previewMap);
    console.log('Leaflet:', typeof L);
    console.log('Container:', previewMap.getBoundingClientRect());

    // Leaflet-Karte initialisieren
    const map = L.map(previewMap, {
        zoomControl: false
    }).setView(STANDARDPOSITION, 12);
    console.log('Map initialized:', map);
    console.log('Tile provider:', providerSelect?.value);
    console.log(
        'Map dimensions:',
        previewMap.offsetWidth,
        previewMap.offsetHeight
    );
    let tileLayer = null;

    const setTileProvider = (provider) => {

        if (tileLayer) {
            map.removeLayer(tileLayer);
        }

        tileLayer = L.tileLayer.provider(provider);
        tileLayer.addTo(map);
    };

    setTileProvider(
        providerSelect?.value || 'OpenStreetMap.Mapnik'
    );

    // Vorschau aktualisieren
    const updatePreview = () => {
        updatePreviewSize();

        requestAnimationFrame(() => {
            map.invalidateSize();
        });
    };

    heightInput.addEventListener('input', updatePreview);
    widthInput.addEventListener('input', updatePreview);

    radiusInput?.addEventListener('input', updatePreview);
    positionSelect?.addEventListener('change', updatePreview);

    // Kartenanbieter wechseln
    providerSelect?.addEventListener('change', function () {
        setTileProvider(this.value);
    });

    // Initiale Größenberechnung
    setTimeout(() => map.invalidateSize(), 100);

});
