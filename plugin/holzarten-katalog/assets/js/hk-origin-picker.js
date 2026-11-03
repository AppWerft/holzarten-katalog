
document.addEventListener('DOMContentLoaded', function () {

    const button = document.getElementById('hk-origin-picker');
    const mapContainer = document.getElementById('hk-origin-map');

    const latField = document.getElementById('_hk_origin_lat');
    const lngField = document.getElementById('_hk_origin_lng');

    const searchInput = document.getElementById('hk-origin-search-input');
    const searchResults = document.getElementById('hk-origin-search-results');

    if (!button || !mapContainer) {
        return;
    }

    let map = null;
    let marker = null;
    let searchTimer = null;
    let searchController = null;
    let pingLayer = null;

    button.addEventListener('click', function () {

        mapContainer.style.display = 'block';

        if (!map) {

            const lat = parseFloat(latField.value) || 51.33988;
            const lng = parseFloat(lngField.value) || 12.42860;

            map = L.map(mapContainer).setView([lat, lng], 6);

            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap-Mitwirkende'
                }
            ).addTo(map);
            // Pinglayer bauen
            pingLayer = L.pingLayer({
                duration: 1500,
                fps: 32,
                color: 'red',
                radiusRange: [5, 130],
                opacityRange: [0.8, 0]
             }).addTo(map);

            
            pingLayer.ping([lng, lat], 'origin');
            map.on('click', function (event) {
                setPosition(event.latlng);
            });
            // Regelmäßig neue Pulse erzeugen.
            pingTimer = setInterval(function () {
                const currentLat = parseFloat(latField.value);
                const currentLng = parseFloat(lngField.value);

                pingLayer.ping([currentLng, currentLat], 'origin');
            }, 1400);
                        }

        map.invalidateSize();
    });

    function setPosition(position) {
        latField.value = position.lat.toFixed(6);
        lngField.value = position.lng.toFixed(6);

        pingLayer.ping([position.lng, position.lat], 'origin');
    }

    
    // Ortssuche mit Autovervollständigung.
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
        searchController = new AbortController();
        try {
            const response = await fetch(
                `https://photon.komoot.io/api/?q=${encodeURIComponent(searchTerm)}&limit=5&lang=de`,
                { signal: searchController.signal }
            );
            if (!response.ok) {
                throw new Error(`HTTP-Fehler: ${response.status}`);
            }
            const data = await response.json();
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

                item.textContent = name;
                item.style.padding = '6px 10px';
                item.style.cursor = 'pointer';
                item.style.borderBottom = '1px solid #ddd';

                item.addEventListener('click', function () {

                    const position = {
                        lat: coordinates[1],
                        lng: coordinates[0]
                    };

                    if (!map) {
                        button.click();
                    }

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
            }
        }
    }
});
