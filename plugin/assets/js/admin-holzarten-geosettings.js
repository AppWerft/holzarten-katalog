document.addEventListener('DOMContentLoaded', function () {
    const id = 'settings-preview-map';
    const preview = document.getElementById(id);
    
    if (!preview) {
        alert('no preview map')
        return;
    }
    preview
        .css('display', 'block')
        .css('height','200px')
        .css('border','2px dotted darkgreen')
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