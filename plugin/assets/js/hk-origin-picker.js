
document.addEventListener('DOMContentLoaded', function () {
    function clearPing() {
        clearInterval(pingTimer);
        pingTimer = null;

        if (pingLayer) {
            pingLayer._data = [];
            pingLayer._updatePings(true);
        }
    }
    const mapContainer = document.getElementById('hk-origin-map');
    const latField = document.getElementById('_hk_origin_lat');
    const lngField = document.getElementById('_hk_origin_lng');

    let searchInput = null;
    let searchResults = null;

    if (!mapContainer || !latField || !lngField) return;

    if (typeof L === 'undefined') {
        console.error('Leaflet ist nicht geladen.');
        return;
    }

    let searchTimer = null;
    let searchController = null;
    let pingLayer = null;
    let pingTimer = null;

    // --------------------------------------------------
    // Karte sofort initialisieren
    // --------------------------------------------------

    const initialLat = parseFloat(latField.value);
    const initialLng = parseFloat(lngField.value);

    const lat = Number.isFinite(initialLat) ? initialLat : 51.33988;
    const lng = Number.isFinite(initialLng) ? initialLng : 12.42860;

    const map = L.map(mapContainer).setView([lat, lng], 6);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap-Mitwirkende'
        }
    ).addTo(map);

    // --------------------------------------------------
    // PingLayer initialisieren
    // --------------------------------------------------

    pingLayer = L.pingLayer({
        duration: 2500,
        fps: 32,
        radiusRange: [5, 130],
        opacityRange: [0.6, 0]
    }).addTo(map);

    // Ursprüngliche Position markieren
    pingLayer.ping([lng, lat], 'origin');

    // Regelmäßig neue Pulse erzeugen
    pingTimer = setInterval(function () {

        const currentLat = parseFloat(latField.value);
        const currentLng = parseFloat(lngField.value);

        if (
            Number.isFinite(currentLat) &&
            Number.isFinite(currentLng)
        ) {
            pingLayer.ping([currentLng, currentLat], 'origin');
        }

    }, 2500);

    // --------------------------------------------------
    // Ortssuche als Leaflet-Control oben rechts
    // --------------------------------------------------

    const SearchControl = L.Control.extend({

        options: {
            position: 'topright'
        },

        onAdd: function () {

            const container = L.DomUtil.create(
                'div',
                'hk-origin-search-control'
            );

            searchInput = L.DomUtil.create(
                'input',
                '',
                container
            );

            searchInput.type = 'search';
            searchInput.id = 'hk-origin-search-input';
            searchInput.placeholder = 'Ort oder Adresse suchen …';
            searchInput.autocomplete = 'off';

            searchResults = L.DomUtil.create(
                'div',
                '',
                container
            );

            searchResults.id = 'hk-origin-search-results';

            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.disableScrollPropagation(container);

            return container;
        }
    });

    map.addControl(new SearchControl());

    // Größe nach dem Aufbau nochmals aktualisieren
    requestAnimationFrame(function () {
        map.invalidateSize();
    });

    // --------------------------------------------------
    // Koordinaten übernehmen
    // --------------------------------------------------

    function setPosition(position) {

        latField.value = position.lat.toFixed(6);
        lngField.value = position.lng.toFixed(6);

        // Karte auf die neue Position zentrieren
        map.setView(
            [position.lat, position.lng],
            Math.max(map.getZoom(), 13)
        );

        // Alten Ping entfernen
        clearPing();

        // Neuen Ping setzen
        pingLayer.ping([position.lng, position.lat], 'origin');

        // Regelmäßige Pulse wieder starten
        pingTimer = setInterval(function () {
            pingLayer.ping([position.lng, position.lat], 'origin');
        }, 2500);
    }

    // Karte anklicken, um den Herkunftsort festzulegen
    map.on('click', function (event) {
        setPosition(event.latlng);
    });

    // --------------------------------------------------
    // Photon-Ortssuche
    // --------------------------------------------------

    if (searchInput && searchResults) {

        searchInput.addEventListener('input', function () {

            clearTimeout(searchTimer);

            const searchTerm = searchInput.value.trim();

            if (searchTerm.length < 3) {
                searchResults.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(function () {
                getPosition(searchTerm);
            }, 350);
        });
    }

    async function getPosition(searchTerm) {

        if (searchController) {
            searchController.abort();
        }

        const controller = new AbortController();
        searchController = controller;

        searchResults.innerHTML = `
            <div class="hk-search-loading">
                <span class="hk-search-spinner"></span>
                <span>Suche läuft …</span>
            </div>
        `;

        try {

            const response = await fetch(
                `https://photon.komoot.io/api/?q=${encodeURIComponent(searchTerm)}&limit=5&lang=de`,
                { signal: controller.signal }
            );

            if (!response.ok) {
                throw new Error(`HTTP-Fehler: ${response.status}`);
            }

            const data = await response.json();

            if (searchController !== controller) return;

            searchResults.innerHTML = '';

            data.features.forEach(function (feature) {

                const properties = feature.properties;
                const coordinates = feature.geometry.coordinates;

                const name = [
                    properties.name,
                    properties.street,
                    properties.housenumber,
                    properties.postcode,
                    properties.city,
                    properties.country
                ].filter(Boolean).join(', ');

                const item = document.createElement('div');

                item.className = 'hk-search-result';
                item.textContent = name;

                item.addEventListener('click', function () {

                    const position = {
                        lat: coordinates[1],
                        lng: coordinates[0]
                    };

                    map.setView(
                        [position.lat, position.lng],
                        13
                    );

                    setPosition(position);

                    searchInput.value = name;
                    searchResults.innerHTML = '';
                });

                searchResults.appendChild(item);
            });

        } catch (error) {

            if (error.name !== 'AbortError') {

                console.error('Fehler bei der Ortssuche:', error);

                if (searchController === controller) {
                    searchResults.textContent = 'Die Suche ist fehlgeschlagen.';
                }
            }

        } finally {

            if (searchController === controller) {
                searchController = null;
            }
        }
    }

});
