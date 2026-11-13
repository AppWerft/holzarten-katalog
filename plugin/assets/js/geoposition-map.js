jQuery('.geopositions_map').each(function () {
    const $mapContainer = jQuery(this);
    const [lat, lng] = $mapContainer.data('geo').split(',').map(Number);

    $mapContainer
        .css('display', 'block');
        .css('height','200px');
        .css('width','100%')

    const map = L.map($mapContainer[0]).setView([lat, lng], 6);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap-Mitwirkende'
        }
    ).addTo(map);

    const pingLayer = L.pingLayer({
        duration: 2500,
        fps: 32,
        radiusRange: [5, 130],
        opacityRange: [0.6, 0]
    }).addTo(map);
});