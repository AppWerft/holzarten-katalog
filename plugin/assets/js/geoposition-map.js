
jQuery('.geopositions_map').each(function () {
    const getTileprovider = () => {
        const tileProvider = $mapContainer.data('tile-provider');
        console.log('Provider aus HTML:', tileProvider);
        console.log('L.tileLayer:', L.tileLayer);
        console.log('L.tileLayer.provider:', L.tileLayer.provider);
        console.log(
            L.TileLayer.Provider.providers['Stadia.StamenWatercolor']
        );
        return L.tileLayer.provider(tileProvider)
    }
    const $mapContainer = jQuery(this);
    const [lat, lng] = $mapContainer.data('geo').split(',').map(Number);
    const zoom = Number($mapContainer.data('zoom')) || 6;
    const tileprovider = $mapContainer.data('tile-provider');
    $mapContainer.css({
        display: 'block'
    });

    const map = L.map($mapContainer[0]).setView([lat, lng], zoom);

    let tileProvider = getTileprovider(tileprovider);
    L.tileLayer(tileProvider.url, tileProvider.options).addTo(map);

    L.pingLayer({
        duration: 2500,
        fps: 32,
        radiusRange: [5, 130],
        opacityRange: [0.6, 0]
    }).addTo(map);
});
