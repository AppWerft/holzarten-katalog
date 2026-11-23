
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

    let pingLayer = null;
    let pingTimer = null;
    let tileLayer = null;

    previewWrapper.style.display = 'flex';


    // --------------------------------------------------
    // Vorschaugröße und Position
    // --------------------------------------------------

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

    // --------------------------------------------------
    // Leaflet-Karte
    // --------------------------------------------------

    const map = L.map(previewMap, {
        zoomControl: false
    }).setView(STANDARDPOSITION, 12);

    // --------------------------------------------------
    // Kartenanbieter
    // --------------------------------------------------

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

    // --------------------------------------------------
    // PingLayer
    // --------------------------------------------------

    pingLayer = L.pingLayer({
        duration: 2500,
        fps: 32,
        radiusRange: [5, 130],
        opacityRange: [0.5, 0]
    });
    function darkenColor(hex, factor = 0.75) {

        hex = hex.replace('#', '');

        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);

        const toHex = value =>
            Math.round(value * factor)
                .toString(16)
                .padStart(2, '0');

        return '#' + toHex(r) + toHex(g) + toHex(b);
    }

    function addPing() {

        pingLayer.ping(
            [STANDARDPOSITION[1], STANDARDPOSITION[0]],
            'preview'
        );

        const pings = pingLayer.data();

        if (pings.length) {

            const ping = pings[pings.length - 1];
            const pingColor = pingcolorInput?.value || '#555555';

            ping.c
                .attr('fill', pingColor)
                .attr('stroke', darkenColor(pingColor, 0.75));
        }
    }

    pingLayer.addTo(map);

    addPing();

    pingTimer = setInterval(addPing, 2500);

    // Farbe unmittelbar aktualisieren
    pingcolorInput?.addEventListener('input', function () {

        const pingColor = this.value || '#555555';

        pingLayer.data().forEach(ping => {
            ping.c
                .attr('fill', pingColor)
                .attr('stroke', pingColor);
        });
    });

    // --------------------------------------------------
    // Vorschau aktualisieren
    // --------------------------------------------------

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

    providerSelect?.addEventListener('change', function () {
        setTileProvider(this.value);
        const needKey = providerRequiresApiKey(this.value);
        const tr = document.getElementById('hk-map-apikey-wrapper');
        if (tr) tr.style.display = needKey ? '' : 'none';    
    });


    function providerRequiresApiKey(providerName) {
        const providers = L.TileLayer.Provider.providers;
        if (!providers || !providers[providerName]) {
            return false;
        }
        const provider = providers[providerName];
        return /\{(key|apikey|apiKey|accessToken|subscriptionKey)\}/
            .test(provider.url || '');
    }
    setTimeout(() => map.invalidateSize(), 100);

});
