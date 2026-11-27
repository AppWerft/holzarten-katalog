
document.addEventListener('DOMContentLoaded', () => {
    console.log('hkoriginpicker');
    document.querySelectorAll('.geopositions_map').forEach(mapContainer => {
        console.log('querySelectorAll')
        const [lat, lng] = mapContainer.dataset.geo
            .split(',')
            .map(Number);

        const zoom = parseInt(mapContainer.dataset.zoom, 10) || 6;
        const tileProvider = mapContainer.dataset.tileProvider;
        console.log(tileProvider)
        if (
            !Number.isFinite(lat) ||
            !Number.isFinite(lng)
        ) {
            console.error('Ungültige Geoposition:', mapContainer.dataset.geo);
            return;
        }

        if (typeof L === 'undefined') {
            console.error('Leaflet ist nicht geladen.');
            return;
        }

        // --------------------------------------------------
        // Kartengröße aus PHP übernehmen
        // --------------------------------------------------

        mapContainer.style.display = 'block';

        // --------------------------------------------------
        // Karte initialisieren
        // --------------------------------------------------

        const map = L.map(mapContainer).setView(
            [lat, lng],
            zoom
        );

        console.log('BE-tileProvider=', tileProvider)
        L.tileLayer.provider(tileProvider).addTo(map);

        // --------------------------------------------------
        // PingLayer initialisieren
        // --------------------------------------------------

        const pingLayer = L.pingLayer({
            duration: 2500,
            fps: 32,
            radiusRange: [5, 130],
            opacityRange: [0.5, 0]
        }).addTo(map);

        // --------------------------------------------------
        // Ping-Farbe
        // --------------------------------------------------

        const pingColor = mapContainer.dataset.pingcolor || '#4285F4';

        function darkenColor(hex, factor = 0.75) {
            hex = hex.replace('#', '');

            const r = parseInt(hex.substring(0, 2), 16),
                g = parseInt(hex.substring(2, 4), 16),
                b = parseInt(hex.substring(4, 6), 16),
                toHex = value =>
                    Math.round(value * factor)
                        .toString(16)
                        .padStart(2, '0');

            return '#' + toHex(r) + toHex(g) + toHex(b);
        }

        function addPing() {
            pingLayer.ping([lng, lat], 'origin');

            const pings = pingLayer.data();

            if (pings.length) {
                const ping = pings[pings.length - 1];

                ping.c
                    .attr('fill', pingColor)
                    .attr('stroke', darkenColor(pingColor));
            }
        }

        addPing();

        setInterval(addPing, 2500);

        // --------------------------------------------------
        // Karte nach dem Aufbau aktualisieren
        // --------------------------------------------------

        requestAnimationFrame(() => map.invalidateSize());
    });
});
