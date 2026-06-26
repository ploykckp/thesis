/**
 * nearme.js
 * 1. Geolocation — get user position
 * 2. Google Maps — show user + place markers
 * 3. Filter by category + 10km radius
 * 4. Render cards sorted by distance
 */

'use strict';

const RADIUS_KM  = 10;
const PAGE_SIZE  = 8;
const FALLBACK_IMGS = [
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop',
    'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=400&h=300&fit=crop',
    'https://images.unsplash.com/photo-1568084680786-a84f91d1153c?w=400&h=300&fit=crop',
    'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400&h=300&fit=crop',
    'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&h=300&fit=crop',
    'https://images.unsplash.com/photo-1562438668-bcf0ca6578f0?w=400&h=300&fit=crop',
];

// ── State ─────────────────────────────────────────────────────────────────────
let map           = null;
let userMarker    = null;
let radiusCircle  = null;
let placeMarkers  = [];
let userLat       = null;
let userLng       = null;
let sortedPlaces  = [];
let renderedCount = 0;
let isLoading     = false;
let infoWindow    = null;

// ── DOM ───────────────────────────────────────────────────────────────────────
const placesGrid     = document.getElementById('placesGrid');
const scrollLoader   = document.getElementById('scrollLoader');
const loadMoreWrap   = document.getElementById('loadMoreWrap');
const loadMoreBtn    = document.getElementById('loadMoreBtn');
const locationNotice = document.getElementById('locationNotice');
const sectionTitle   = document.getElementById('sectionTitle');

// ── Haversine ─────────────────────────────────────────────────────────────────
function distKm(lat1, lng1, lat2, lng2) {
    const R    = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a    = Math.sin(dLat / 2) ** 2
               + Math.cos(lat1 * Math.PI / 180)
               * Math.cos(lat2 * Math.PI / 180)
               * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function fmtDist(km) {
    return km < 1 ? `${Math.round(km * 1000)} ม.` : `${km.toFixed(1)} กม.`;
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function renderStars(count) {
    let html = '';
    for (let i = 0; i < 5; i++) {
        const color = i < count ? '#1e293b' : '#cbd5e1';
        html += `<span class="iconify" data-icon="material-symbols:star"
                      data-width="18" data-height="18" style="color:${color}"></span>`;
    }
    return html;
}

// ── Card builder ──────────────────────────────────────────────────────────────
function buildCard(place, index) {
    const img      = place.image || FALLBACK_IMGS[index % FALLBACK_IMGS.length];
    const fallback = FALLBACK_IMGS[index % FALLBACK_IMGS.length];
    const dist     = place._dist !== undefined
        ? `<span class="place-distance">${fmtDist(place._dist)}</span>`
        : '';
    const province = place.province ? `จังหวัด : ${escHtml(place.province)}` : '';
    const petSize  = place.petSize  ? `รองรับ : ${escHtml(place.petSize)}`   : '';

    return `
    <div class="hotel-card" data-id="${place.id}" onclick="openPlace(${place.id})">
        <div class="hotel-image">
            ${dist}
            <img src="${escHtml(img)}"
                 alt="${escHtml(place.name)}"
                 onerror="this.src='${fallback}'">
        </div>
        <div class="hotel-info">
            <h3 class="hotel-name">${escHtml(place.name)}</h3>
            <span class="hotel-badge">pet-friendly</span>
            <p class="hotel-location">
                ${province}
                ${province && petSize ? '<br>' : ''}
                ${petSize}
            </p>
            <div class="hotel-rating">${renderStars(4)}</div>
        </div>
    </div>`;
}

// ── Infinite scroll ───────────────────────────────────────────────────────────
function renderNextPage() {
    if (isLoading) return;
    if (renderedCount >= sortedPlaces.length) {
        loadMoreWrap.classList.add('hidden');
        scrollLoader.classList.add('hidden');
        return;
    }

    isLoading = true;
    scrollLoader.classList.remove('hidden');
    loadMoreWrap.classList.add('hidden');

    setTimeout(() => {
        const slice = sortedPlaces.slice(renderedCount, renderedCount + PAGE_SIZE);
        slice.forEach((place, i) => {
            const div  = document.createElement('div');
            div.innerHTML = buildCard(place, renderedCount + i).trim();
            const card = div.firstChild;
            placesGrid.appendChild(card);
            if (window.Iconify) Iconify.scan(card);
        });
        renderedCount += slice.length;
        isLoading = false;
        scrollLoader.classList.add('hidden');

        if (renderedCount < sortedPlaces.length) {
            loadMoreWrap.classList.remove('hidden');
        }
    }, 300);
}

function initCards() {
    placesGrid.innerHTML = '';
    renderedCount = 0;

    if (sortedPlaces.length === 0) {
        placesGrid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:48px 0;
                        color:#64748b;font-family:'Kanit',sans-serif">
                <div style="font-size:48px;margin-bottom:12px">📍</div>
                <div style="font-size:16px">ไม่พบสถานที่ในรัศมี ${RADIUS_KM} กม.</div>
            </div>`;
        loadMoreWrap.classList.add('hidden');
        return;
    }
    renderNextPage();
}

window.addEventListener('scroll', () => {
    const scrollBottom = window.innerHeight + window.scrollY;
    const docHeight    = document.documentElement.scrollHeight;
    if (scrollBottom >= docHeight - 300) renderNextPage();
});

if (loadMoreBtn) loadMoreBtn.addEventListener('click', renderNextPage);

// ── Google Map ────────────────────────────────────────────────────────────────
function initMap(centerLat, centerLng) {
    if (map) return;

    map = new google.maps.Map(document.getElementById('nearbyMap'), {
        center: { lat: centerLat, lng: centerLng },
        zoom: 13,
        mapId: 'pawland_nearme',
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
    });

    infoWindow = new google.maps.InfoWindow();
}

function clearMarkers() {
    placeMarkers.forEach(m => { m.map = null; });
    placeMarkers = [];
    if (radiusCircle) { radiusCircle.setMap(null); radiusCircle = null; }
}

function updateMap(lat, lng, places) {
    if (!map) return;

    map.setCenter({ lat, lng });

    const { AdvancedMarkerElement } = google.maps.marker;

    // User marker — วงกลมสีน้ำเงิน
    if (userMarker) {
        userMarker.position = { lat, lng };
    } else {
        const dot = document.createElement('div');
        dot.style.cssText = [
            'width:20px', 'height:20px', 'border-radius:50%',
            'background:#3b82f6', 'border:3px solid #fff',
            'box-shadow:0 2px 6px rgba(0,0,0,0.35)',
        ].join(';');

        userMarker = new AdvancedMarkerElement({
            position: { lat, lng },
            map,
            title: 'ตำแหน่งของคุณ',
            content: dot,
            zIndex: 999,
        });
        userMarker.addListener('click', () => {
            infoWindow.setContent('<b style="font-family:Kanit,sans-serif">ตำแหน่งของคุณ</b>');
            infoWindow.open(map, userMarker);
        });
    }

    // Radius circle
    clearMarkers();
    radiusCircle = new google.maps.Circle({
        map,
        center: { lat, lng },
        radius: RADIUS_KM * 1000,
        strokeColor: '#123451',
        strokeOpacity: 0.4,
        strokeWeight: 1.5,
        fillColor: '#123451',
        fillOpacity: 0.05,
    });

    // Place markers
    places.forEach(place => {
        if (!place.lat || !place.lng) return;

        const img = document.createElement('img');
        img.src   = 'logo.png';
        img.style.cssText = 'width:52px;height:52px;object-fit:contain;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.35));';

        const marker = new AdvancedMarkerElement({
            position: { lat: place.lat, lng: place.lng },
            map,
            title: place.name,
            content: img,
        });

        marker.addListener('click', () => {
            infoWindow.setContent(`
                <div style="font-family:'Kanit',sans-serif;min-width:150px;padding:4px 2px">
                    <strong style="color:#123451">${escHtml(place.name)}</strong><br>
                    <span style="font-size:12px;color:#64748b">${escHtml(place.category || '')}</span><br>
                    <span style="font-size:12px;color:#64748b">${escHtml(place.address || place.province)}</span><br>
                    ${place._dist !== undefined
                        ? `<span style="font-size:12px;color:#123451">📍 ห่าง ${fmtDist(place._dist)}</span>`
                        : ''}
                </div>
            `);
            infoWindow.open(map, marker);
        });

        placeMarkers.push(marker);
    });

    // Fit bounds
    if (places.length > 0) {
        const bounds = new google.maps.LatLngBounds();
        bounds.extend({ lat, lng });
        places.forEach(p => { if (p.lat && p.lng) bounds.extend({ lat: p.lat, lng: p.lng }); });
        map.fitBounds(bounds, { padding: 60 });
        google.maps.event.addListenerOnce(map, 'bounds_changed', () => {
            if (map.getZoom() > 14) map.setZoom(14);
        });
    }
}

// ── Filter + sort ─────────────────────────────────────────────────────────────
function getFilteredPlaces(lat, lng, catKey) {
    return ALL_PLACES
        .filter(p => {
            if (!p.lat || !p.lng) return false;
            // 'all' = ไม่กรอง category
            if (catKey !== 'all') {
                const catDb = CATEGORY_MAP[catKey] || '';
                const cats  = (p.category || '').split(',').map(c => c.trim());
                if (!cats.includes(catDb)) return false;
            }
            return true;
        })
        .map(p => ({ ...p, _dist: distKm(lat, lng, p.lat, p.lng) }))
        .filter(p => p._dist <= RADIUS_KM)
        .sort((a, b) => a._dist - b._dist);
}

// ── Switch category (click pill) ──────────────────────────────────────────────
window.switchCategory = function(catKey) {
    // กดซ้ำ = deselect กลับเป็น all
    if (ACTIVE_CAT === catKey) {
        ACTIVE_CAT = 'all';
        document.querySelectorAll('.nearby-cat-card').forEach(el => el.classList.remove('nearby-cat-card--active'));
        if (sectionTitle) sectionTitle.textContent = 'สถานที่ทั้งหมดใกล้ฉัน';
    } else {
        ACTIVE_CAT = catKey;
        document.querySelectorAll('.nearby-cat-card').forEach(el => {
            el.classList.toggle('nearby-cat-card--active', el.dataset.cat === catKey);
        });
        if (sectionTitle) sectionTitle.textContent = (CATEGORY_MAP[catKey] || '') + 'ใกล้ฉัน';
    }

    if (userLat !== null) {
        sortedPlaces = getFilteredPlaces(userLat, userLng, ACTIVE_CAT);
        updateMap(userLat, userLng, sortedPlaces);
    } else {
        sortedPlaces = [];
    }

    initCards();
};

// ── Bootstrap ─────────────────────────────────────────────────────────────────
function bootstrap(lat, lng) {
    userLat = lat;
    userLng = lng;

    sortedPlaces = getFilteredPlaces(lat, lng, ACTIVE_CAT);

    initMap(lat, lng);
    updateMap(lat, lng, sortedPlaces);
    initCards();
}

function requestLocation() {
    if (!navigator.geolocation) {
        bootstrapNoGeo();
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            locationNotice.classList.add('hidden');
            bootstrap(pos.coords.latitude, pos.coords.longitude);
        },
        (err) => {
            console.warn('Geolocation error:', err.code, err.message);
            bootstrapNoGeo();
        },
        { enableHighAccuracy: false, timeout: 15000, maximumAge: 0 }
    );
}

function bootstrapNoGeo() {
    locationNotice.classList.remove('hidden');
    locationNotice.innerHTML = `
        <span class="iconify" data-icon="mdi:map-marker-alert" data-width="20"></span>
        กรุณาอนุญาตการเข้าถึงตำแหน่งของคุณเพื่อดูสถานที่ใกล้ฉัน
        &nbsp;
        <button onclick="retryLocation()"
                style="padding:4px 14px;background:#123451;color:#fff;border:none;border-radius:20px;
                       font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;margin-left:8px">
            ลองใหม่
        </button>
    `;
    if (window.Iconify) Iconify.scan(locationNotice);
    initMap(13.7563, 100.5018);
    sortedPlaces = [];
    placesGrid.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:48px 0;
                    color:#64748b;font-family:'Kanit',sans-serif">
            <div style="font-size:16px">กรุณาอนุญาตการเข้าถึงตำแหน่งเพื่อดูสถานที่ใกล้ฉัน</div>
        </div>`;
    loadMoreWrap.classList.add('hidden');
}

window.retryLocation = function() {
    locationNotice.innerHTML = `
        <span class="iconify" data-icon="mdi:map-marker-alert" data-width="20"></span>
        กำลังขอตำแหน่ง...
    `;
    requestLocation();
};

// ── Entry ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    requestLocation();
});

window.openPlace = function(id) {
    window.location.href = `place_detail.php?id=${id}`;
};