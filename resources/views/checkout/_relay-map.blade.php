<script>
window.__relayMap = (function() {
    var map = null;
    var markers = [];

    function loadMapLibre(cb) {
        if (window.maplibregl) { cb(); return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css';
        document.head.appendChild(link);
        var script = document.createElement('script');
        script.src = 'https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js';
        script.onload = cb;
        document.head.appendChild(script);
    }

    function initMap(alpine) {
        var container = document.getElementById('relay-map');
        if (!container) return;
        if (map) { map.remove(); map = null; }
        markers = [];

        var points = alpine.relayPoints;
        var bounds = new maplibregl.LngLatBounds();
        points.forEach(function(p) {
            if (p.lat && p.lng) bounds.extend([p.lng, p.lat]);
        });

        map = new maplibregl.Map({
            container: container,
            style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            bounds: bounds,
            fitBoundsOptions: { padding: 40, maxZoom: 14 }
        });
        map.addControl(new maplibregl.NavigationControl(), 'top-right');

        points.forEach(function(point, idx) {
            if (!point.lat || !point.lng) return;

            var isChrono = point.network === 'CHRP_NETWORK';
            var el = document.createElement('div');
            el.className = 'relay-marker' + (isChrono ? ' relay-marker--chrono' : '');
            el.textContent = idx + 1;
            el.addEventListener('click', function() {
                alpine.selectRelayPoint(point);
                var item = document.getElementById('relay-item-' + point.code);
                if (item) item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });

            var popupHtml = '<strong class="relay-popup-name">' +
                point.name + '</strong><br><span class="relay-popup-addr">' +
                point.street + '<br>' + point.zipCode + ' ' + point.city + '</span>';

            var marker = new maplibregl.Marker({ element: el })
                .setLngLat([point.lng, point.lat])
                .setPopup(new maplibregl.Popup({ offset: 20 }).setHTML(popupHtml))
                .addTo(map);

            markers.push({ code: point.code, el: el, marker: marker });
        });
    }

    return {
        render: function(alpine) {
            loadMapLibre(function() { initMap(alpine); });
        },
        highlight: function(point) {
            if (map && point.lat && point.lng) {
                map.flyTo({ center: [point.lng, point.lat], zoom: 15 });
            }
            markers.forEach(function(m) {
                m.el.style.opacity = m.code === point.code ? '1' : '0.5';
                m.el.style.transform = m.code === point.code ? 'scale(1.3)' : 'scale(1)';
            });
        },
        destroy: function() {
            if (map) { map.remove(); map = null; }
            markers = [];
        }
    };
})();
</script>
<style>
.relay-marker {
    width: 28px; height: 28px; border-radius: 50%;
    background: #96154a; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,.3);
    transition: transform .2s, opacity .2s;
}
.relay-marker--chrono { background: #337ab7; }
.relay-popup-name { font-size: 13px; display: block; }
.relay-popup-addr { font-size: 12px; color: #666; }
.maplibregl-popup-close-button { font-size: 20px; width: 28px; height: 28px; line-height: 28px; padding: 0; }
</style>
