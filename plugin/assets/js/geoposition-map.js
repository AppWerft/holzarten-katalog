
jQuery('.geopositions_map').each(function () {
     console.log('DOMContentLoaded in geoposition-map')
    const getTileprovider = () => {
        const tileProvider = $mapContainer.data('tile-provider');
        console.log(tileProvider)
        const tileProviders = {
            'OpenStreetMap.Mapnik': {
                url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                options: {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap-Mitwirkende'
                }
            }
        };
        console.log(tileProvider)
        return tileProviders[tileProvider] || tileProviders['OpenStreetMap.Mapnik'];

    }
    const $mapContainer = jQuery(this);

    const [lat, lng] = $mapContainer.data('geo').split(',').map(Number);
    const zoom = Number($mapContainer.data('zoom')) || 6;

    $mapContainer.css({
        display: 'block'
    });

    const map = L.map($mapContainer[0]).setView([lat, lng], zoom);


    L.tileLayer(provider.url, provider.options).addTo(map);

    L.pingLayer({
        duration: 2500,
        fps: 32,
        radiusRange: [5, 130],
        opacityRange: [0.6, 0]
    }).addTo(map);
});
