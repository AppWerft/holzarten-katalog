document.addEventListener('DOMContentLoaded', function () {
    const preview = document.getElementById('admin-map-preview');
    alert(preview)
    if (!preview) {
        return;
    }
    preview
        .css('display', 'block')
        .css('height','200px')
        .css('width','100%');

    const map = L.map(preview.setView([53.5,10.0], 6));

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap-Mitwirkende'
        }
    ).addTo(map);
   

    
    updatePreview();
   
});