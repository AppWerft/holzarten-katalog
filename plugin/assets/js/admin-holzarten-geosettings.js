document.addEventListener('DOMContentLoaded', function () {

    const preview = document.getElementById('settings-preview-map');

    if (!preview) {
        return;
    }

    // Darstellung des Kartencontainers
    preview.style.display = 'block';
    preview.style.height = '200px';
    preview.style.border = '2px dotted darkgreen';
    preview.style.width = '90%';

    // Leaflet-Karte initialisieren
    const map = L.map(preview).setView([51.3406751,12.4256434], 12);
    console.log(map)
    // OpenStreetMap-Kacheln
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap-Mitwirkende'
        }
    ).addTo(map);

});