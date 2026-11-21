
document.addEventListener('DOMContentLoaded', function () {
    // 1. Layer deklarieren
    let pingLayer = null;
    let pingTimer = null;
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

    // Leaflet-Karte initialisieren
    const map = L.map(previewMap, {
        zoomControl: false
    }).setView(STANDARDPOSITION, 12);

    // 3. PingLayer erzeugen
    pingLayer = L.pingLayer({
        duration: 2500,
        fps: 32,
        radiusRange: [5, 130],
        opacityRange: [0.5, 0]
    });

    // PingLayer mit variabler Farbe
    const updatePingLayer = () => {

        if (pingLayer) {
            map.removeLayer(pingLayer);
        }

        const pingColor = pingcolorInput?.value || '#555555';


        // --------------------------------------------------
        // PingLayer initialisieren
        // --------------------------------------------------

        

        
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
                    .attr('stroke', pingColor);
            }
        }

        function startPing() {

            if (pingTimer) {
                clearInterval(pingTimer);
            }

            if (!map.hasLayer(pingLayer)) {
                pingLayer.addTo(map);
            }

            addPing();

            pingTimer = setInterval(addPing, 2500);
        }

        startPing();

        // Farbe unmittelbar bei Änderung aktualisieren
        pingcolorInput?.addEventListener('input', () => {

            const pingColor = pingcolorInput.value || '#555555';

            pingLayer.data().forEach(ping => {
                ping.c
                    .attr('fill', pingColor)
                    .attr('stroke', pingColor);
            });
        });

    };

    updatePingLayer();

    // Kartenanbieter
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

    // Pingfarbe unmittelbar aktualisieren
    pingcolorInput?.addEventListener('input', updatePingLayer);

    // Kartenanbieter wechseln
    providerSelect?.addEventListener('change', function () {
        setTileProvider(this.value);
    });

    setTimeout(() => map.invalidateSize(), 100);

});
