document.addEventListener('DOMContentLoaded', function () {

    const button = document.getElementById('hk-origin-picker');
    const mapContainer = document.getElementById('hk-origin-map');

    const latField = document.getElementById('_hk_origin_lat');
    const lngField = document.getElementById('_hk_origin_lng');

    if (!button || !mapContainer) {
        return;
    }

    let map = null;
    let marker = null;

    button.addEventListener('click', function () {

        mapContainer.style.display = 'block';

        if (!map) {

            const lat = parseFloat(latField.value) || 51.0;
            const lng = parseFloat(lngField.value) || 10.0;

            map = L.map(mapContainer).setView([lat, lng], 6);

            // OpenStreetMap-Kacheln
            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap-Mitwirkende'
                }
            ).addTo(map);

            // Eigener Kreismarker statt Bilddateien.
            marker = L.circleMarker([lat, lng], {
                radius: 9,
                color: '#ffffff',
                weight: 2,
                fillColor: '#d33',
                fillOpacity: 1
            }).addTo(map);

            marker.bindPopup('Holzherkunft');

            // Klick auf Karte verschiebt den Marker.
            map.on('click', function (event) {
                setPosition(event.latlng);
            });

        } else {

            map.invalidateSize();
        }

        map.invalidateSize();
    });

    function setPosition(position) {

        marker.setLatLng(position);

        latField.value = position.lat.toFixed(6);
        lngField.value = position.lng.toFixed(6);
    }

});