
document.addEventListener('DOMContentLoaded', () => {

    const mapContainer = document.getElementById('hk-origin-map');
    const latField = document.getElementById('_hk_origin_lat');
    const lngField = document.getElementById('_hk_origin_lng');
    let clearButton = null;
    if (!mapContainer || !latField || !lngField) {
        console.error('fehlende Felder in der Form');
        return;
    }

    const pingColor = mapContainer.dataset.pingcolor || '#4285F4';

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

    const hasPosition =
        Number.isFinite(initialLat) &&
        Number.isFinite(initialLng);

    mapContainer.classList.toggle('is-inactive', !hasPosition);

    const lat = Number.isFinite(initialLat) ? initialLat : 51.33988;
    const lng = Number.isFinite(initialLng) ? initialLng : 12.42860;

    const map = L.map(mapContainer).setView([lat, lng], 6);

    map.on('zoomend', () => {
        document.getElementById('_hk_origin_zoom').value = map.getZoom();
    });

    // Hier den Zoomwert für die Produktattribut-Auswahl übernehmen.

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
        opacityRange: [0.5, 0]
    });


    // --------------------------------------------------
    // Ortssuche als Leaflet-Control oben rechts
    // --------------------------------------------------

    const SearchControl = L.Control.extend({

        options: {
            position: 'topright'
        },

        onAdd: () => {

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

            clearButton = L.DomUtil.create(
                'button',
                'hk-origin-clear',
                container
            );
            clearButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg"
                    width="19" height="19"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M8 6V4h8v2"/>
                    <path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 11v6"/>
                    <path d="M14 11v6"/>
                </svg>
`;
            clearButton.type = 'button';
            clearButton.title = 'Geoposition löschen';
            clearButton.setAttribute(
                'aria-label',
                'Geoposition löschen'
            );

            clearButton.hidden = !hasPosition;

            searchResults = L.DomUtil.create(
                'div',
                '',
                container
            );

            searchResults.id = 'hk-origin-search-results';

            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.disableScrollPropagation(container);

            L.DomEvent.on(clearButton, 'click', clearPosition);

            return container;
        }
    });

    map.addControl(new SearchControl());

    // Größe nach dem Aufbau nochmals aktualisieren
    requestAnimationFrame(() => map.invalidateSize());


    function clearPing() {
        if (pingTimer) {
            clearInterval(pingTimer);
            pingTimer = null;
        }

        if (!pingLayer) return;

        // Vorhandene SVG-Kreise entfernen
        const pings = pingLayer.data();

        if (Array.isArray(pings)) {
            pings.forEach(ping => ping.c.remove());
            pings.length = 0;
        }

        // Layer entfernen
        if (map.hasLayer(pingLayer)) {
            map.removeLayer(pingLayer);
        }
    }
    function addPing(position) {
        pingLayer.ping(
            [position.lng, position.lat],
            'origin'
        );

        const pings = pingLayer.data();

        if (pings.length) {
            const ping = pings[pings.length - 1];

            ping.c
                .attr('fill', pingColor)
                .attr('stroke', pingColor);
        }
    }
    function startPing(position) {
        clearPing();

        pingLayer.addTo(map);

        addPing(position);

        pingTimer = setInterval(() => {
            const currentLat = parseFloat(latField.value);
            const currentLng = parseFloat(lngField.value);

            if (
                Number.isFinite(currentLat) &&
                Number.isFinite(currentLng)
            ) {
                addPing({
                    lat: currentLat,
                    lng: currentLng
                });
            }
        }, 2500);
    }

    function setPosition(position) {
        clearPing();
        latField.value = position.lat.toFixed(6);
        lngField.value = position.lng.toFixed(6);
        mapContainer.classList.remove('is-inactive');
        clearButton.hidden = false;
        map.setView(
            [position.lat, position.lng],
            Math.max(map.getZoom(), 13),
            { animate: false }
        );
        requestAnimationFrame(() => startPing(position));
    }

    function clearPosition() {
        clearPing();
        latField.value = '';
        lngField.value = '';
        searchInput.value = '';
        searchResults.innerHTML = '';
        mapContainer.classList.add('is-inactive');
        clearButton.hidden = true;
        map.setView([51.33988, 12.42860], 6);
    }

    // Karte anklicken, um den Herkunftsort festzulegen
    /*item.addEventListener('click', function (event) {
 
         // Klick darf nicht bis zur Karte durchgereicht werden.
         L.DomEvent.stopPropagation(event);
 
         const position = {
             lat: coordinates[1],
             lng: coordinates[0]
         };
 
         setPosition(position);
 
         searchInput.value = name;
         searchResults.innerHTML = '';
     });*/

    // --------------------------------------------------
    // Photon-Ortssuche
    // --------------------------------------------------

    if (searchInput && searchResults) {

        searchInput.addEventListener('input', () => {
            clearPing();
            clearTimeout(searchTimer);

            const searchTerm = searchInput.value.trim();

            if (searchTerm.length < 3) {
                searchResults.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(() => getPosition(searchTerm), 350);
        });
        searchInput.addEventListener('focus', () => {
            searchInput.value = '';
            searchResults.innerHTML = '';
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

            data.features.forEach((feature) => {
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

                item.addEventListener('click', (event) => {
                    L.DomEvent.stopPropagation(event);

                    const position = {
                        lat: coordinates[1],
                        lng: coordinates[0]
                    };

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
