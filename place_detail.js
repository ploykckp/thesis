/**
 * place_detail.js
 * Handles:
 *  1. Date range picker (check-in / check-out calendar)
 *  2. Tab navigation
 *  3. Google Maps for location tab
 */

'use strict';

const THAI_MONTHS = [
    'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
    'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'
];
const THAI_MONTHS_SHORT = [
    'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.',
    'ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'
];

const dateTrigger   = document.getElementById('dateTrigger');
const calendarPopup = document.getElementById('calendarPopup');
const dateLabel     = document.getElementById('dateLabel');
const cal1Grid      = document.getElementById('cal1Grid');
const cal2Grid      = document.getElementById('cal2Grid');
const month1Title   = document.getElementById('month1Title');
const month2Title   = document.getElementById('month2Title');
const calHint       = document.getElementById('calHint');
const calClear      = document.getElementById('calClear');
const calConfirm    = document.getElementById('calConfirm');

let calBaseDate = new Date();
calBaseDate.setDate(1);

let checkIn  = null;
let checkOut = null;
let picking  = 'in';

// ── Toggle calendar ───────────────────────────────────
dateTrigger.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = calendarPopup.classList.contains('open');
    if (isOpen) {
        closeCalendar();
    } else {
        openCalendar();
    }
});

document.addEventListener('click', (e) => {
    if (!calendarPopup.contains(e.target) && e.target !== dateTrigger) {
        closeCalendar();
    }
});

function openCalendar() {
    calendarPopup.classList.add('open');
    dateTrigger.classList.add('open');
    renderBothMonths();
}

function closeCalendar() {
    calendarPopup.classList.remove('open');
    dateTrigger.classList.remove('open');
}

// ── Month navigation ──────────────────────────────────
document.getElementById('prevMonth').addEventListener('click', () => {
    calBaseDate.setMonth(calBaseDate.getMonth() - 1);
    renderBothMonths();
});

document.getElementById('nextMonth').addEventListener('click', () => {
    calBaseDate.setMonth(calBaseDate.getMonth() + 1);
    renderBothMonths();
});

// ── Render ────────────────────────────────────────────
function renderBothMonths() {
    const m2 = new Date(calBaseDate.getFullYear(), calBaseDate.getMonth() + 1, 1);
    month1Title.textContent = `${THAI_MONTHS[calBaseDate.getMonth()]} ${calBaseDate.getFullYear() + 543}`;
    month2Title.textContent = `${THAI_MONTHS[m2.getMonth()]} ${m2.getFullYear() + 543}`;
    renderMonth(cal1Grid, calBaseDate.getFullYear(), calBaseDate.getMonth());
    renderMonth(cal2Grid, m2.getFullYear(), m2.getMonth());
}

function renderMonth(grid, year, month) {
    grid.innerHTML = '';
    const today       = new Date(); today.setHours(0,0,0,0);
    const firstDay    = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const el = document.createElement('span');
        el.className = 'cal-day cal-day--empty';
        grid.appendChild(el);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const date = new Date(year, month, d);
        const el   = document.createElement('span');
        el.className = 'cal-day';
        el.textContent = d;

        if (date < today) {
            el.classList.add('cal-day--disabled');
        } else {
            const isCheckIn  = checkIn  && isSameDay(date, checkIn);
            const isCheckOut = checkOut && isSameDay(date, checkOut);
            const inRange    = checkIn && checkOut && date > checkIn && date < checkOut;

            if (isCheckIn)  el.classList.add('cal-day--checkin');
            if (isCheckOut) el.classList.add('cal-day--checkout');
            if (inRange)    el.classList.add('cal-day--range');

            el.addEventListener('click', () => onDayClick(date));
        }
        grid.appendChild(el);
    }
}

function onDayClick(date) {
    if (picking === 'in' || (checkIn && date <= checkIn)) {
        checkIn  = date;
        checkOut = null;
        picking  = 'out';
        calHint.textContent = 'เลือกวันเช็คเอาท์';
    } else {
        checkOut = date;
        picking  = 'in';
        calHint.textContent = formatDateRange(checkIn, checkOut);
    }
    renderBothMonths();
}

// ── Clear / Confirm ───────────────────────────────────
calClear.addEventListener('click', () => {
    checkIn  = null;
    checkOut = null;
    picking  = 'in';
    calHint.textContent   = 'เลือกวันเช็คอิน';
    dateLabel.textContent = 'เลือกวันที่เข้าพัก';
    renderBothMonths();
});

calConfirm.addEventListener('click', () => {
    if (checkIn && checkOut) {
        const checkinTh  = formatThai(checkIn);
        const checkoutTh = formatThai(checkOut);
        const nights     = nightsBetween(checkIn, checkOut);
        dateLabel.innerHTML =
            `วัน ${thaiDay(checkIn)} ${checkinTh} &ndash; วัน ${thaiDay(checkOut)} ${checkoutTh}&nbsp;&nbsp;(${nights} คืน)`;
    } else if (checkIn) {
        dateLabel.textContent = `เช็คอิน: ${formatThai(checkIn)}`;
    }
    closeCalendar();
});

// ── Utilities ─────────────────────────────────────────
function isSameDay(a, b) {
    return a.getFullYear() === b.getFullYear()
        && a.getMonth()    === b.getMonth()
        && a.getDate()     === b.getDate();
}
function formatThai(d) {
    return `${d.getDate()} ${THAI_MONTHS_SHORT[d.getMonth()]}`;
}
function thaiDay(d) {
    return ['อา.','จ.','อ.','พ.','พฤ.','ศ.','ส.'][d.getDay()];
}
function nightsBetween(a, b) {
    return Math.round((b - a) / 86400000);
}
function formatDateRange(a, b) {
    return `${formatThai(a)} – ${formatThai(b)} (${nightsBetween(a, b)} คืน)`;
}

renderBothMonths();


// ══════════════════════════════════════════════════════
//  2. TAB NAVIGATION
// ══════════════════════════════════════════════════════

const tabs   = document.querySelectorAll('.detail-tab');
const panels = document.querySelectorAll('.tab-content');
let mapInit  = false;

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t   => t.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));

        tab.classList.add('active');
        const target = document.getElementById('tab-' + tab.dataset.tab);
        if (target) target.classList.add('active');

        if (tab.dataset.tab === 'location' && !mapInit) {
            initDetailMap();
            mapInit = true;
        }
    });
});


// ══════════════════════════════════════════════════════
//  3. GOOGLE MAPS  (location tab)
// ══════════════════════════════════════════════════════

function initDetailMap() {
    const lat      = (typeof PLACE_LAT !== 'undefined' && PLACE_LAT !== 0) ? PLACE_LAT : null;
    const lng      = (typeof PLACE_LNG !== 'undefined' && PLACE_LNG !== 0) ? PLACE_LNG : null;
    const name     = typeof PLACE_NAME !== 'undefined' ? PLACE_NAME : 'สถานที่';
    const addr     = typeof PLACE_ADDR !== 'undefined' ? PLACE_ADDR : '';
    const mapEl    = document.getElementById('detailMap');

    if (lat && lng) {
        renderGoogleMap(mapEl, { lat, lng }, name, addr);
    } else if (addr) {
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: addr }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const loc = results[0].geometry.location;
                renderGoogleMap(mapEl, { lat: loc.lat(), lng: loc.lng() }, name, addr);
            } else {
                showNoMap(mapEl, addr);
            }
        });
    } else {
        showNoMap(mapEl, '');
    }
}

function renderGoogleMap(mapEl, center, name, addr) {
    const map = new google.maps.Map(mapEl, {
        center,
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
        zoomControlOptions: {
            position: google.maps.ControlPosition.RIGHT_CENTER,
        },
    });

    const marker = new google.maps.Marker({
        position: center,
        map,
        title: name,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            fillColor: '#123451',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3,
            scale: 10,
        },
    });

    const infoWindow = new google.maps.InfoWindow({
        content: `
            <div style="font-family:'Kanit',sans-serif;min-width:160px;padding:4px 2px">
                <strong style="color:#123451;font-size:14px">${name}</strong>
                ${addr ? `<br><span style="font-size:12px;color:#64748b">${addr}</span>` : ''}
            </div>
        `,
    });

    infoWindow.open(map, marker);
    marker.addListener('click', () => infoWindow.open(map, marker));
}

function showNoMap(mapEl, addr) {
    mapEl.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:center;
                    height:100%;color:#64748b;font-family:'Kanit',sans-serif;
                    flex-direction:column;gap:8px">
            <span style="font-size:32px">📍</span>
            <span>ไม่พบพิกัดสถานที่</span>
            ${addr ? `<small style="color:#94a3b8">${addr}</small>` : ''}
        </div>`;
}


// ══════════════════════════════════════════════════════
//  4. เพิ่มลงในแพลน
// ══════════════════════════════════════════════════════

function _iso(d) {
    const p = n => String(n).padStart(2,'0');
    return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`;
}

document.addEventListener('click', function(e) {
    if (e.target && (e.target.id === 'planAddBtn' || e.target.closest('#planAddBtn'))) {
        addToPlan();
    }
});

function addToPlan() {
    if (!checkIn) {
        showDetailToast('กรุณาเลือกวันที่เข้าพักก่อนนะ 🐾', 'error');
        openCalendar();
        return;
    }

    const placeId   = (typeof PLACE_ID   !== 'undefined') ? PLACE_ID   : 0;
    const placeName = (typeof PLACE_NAME !== 'undefined') ? PLACE_NAME : 'สถานที่';
    const placeImg  = (typeof PLACE_IMG  !== 'undefined') ? PLACE_IMG  : '';

    const visitDate   = _iso(checkIn);
    const checkInISO  = visitDate;
    const checkOutISO = checkOut ? _iso(checkOut) : visitDate;

    let stored = [];
    try { stored = JSON.parse(sessionStorage.getItem('planPlaces') || '[]'); } catch(e) {}

    if (stored.some(p => String(p.place_id) === String(placeId) && p.visit_date === visitDate)) {
        showDetailToast('สถานที่นี้มีในแพลนวันนั้นแล้ว 🐾', 'error');
        return;
    }

    stored.push({
        place_id  : placeId,
        name      : placeName,
        image     : placeImg,
        visit_date: visitDate,
        check_in  : checkInISO,
        check_out : checkOutISO,
        added_at  : Date.now(),
    });
    sessionStorage.setItem('planPlaces', JSON.stringify(stored));

    const btn = document.getElementById('planAddBtn');
    if (btn) {
        btn.innerHTML = `<span class="iconify" data-icon="mdi:check-circle" data-width="20"></span> เพิ่มแล้ว! กำลังไปหน้าแพลน...`;
        btn.style.background = '#16a34a';
        btn.disabled = true;
        if (window.Iconify) Iconify.scan(btn);
    }
    setTimeout(() => { window.location.href = 'plantrip.php'; }, 800);
}

let _dtt;
function showDetailToast(msg, type) {
    let t = document.getElementById('detailToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'detailToast';
        Object.assign(t.style, {
            position:'fixed', bottom:'28px', right:'28px', zIndex:'9999',
            padding:'14px 22px', borderRadius:'12px',
            fontFamily:"'Kanit',sans-serif", fontSize:'15px', fontWeight:'500',
            transform:'translateY(60px)', opacity:'0',
            transition:'all .3s cubic-bezier(.34,1.56,.64,1)',
            boxShadow:'0 6px 20px rgba(0,0,0,.15)', pointerEvents:'none',
        });
        document.body.appendChild(t);
    }
    t.textContent      = msg;
    t.style.background = (type === 'error') ? '#ef4444' : '#123451';
    t.style.color      = '#fff';
    t.style.transform  = 'translateY(0)';
    t.style.opacity    = '1';
    clearTimeout(_dtt);
    _dtt = setTimeout(() => {
        t.style.transform = 'translateY(60px)';
        t.style.opacity   = '0';
    }, 2800);
}


// ══════════════════════════════════════════════════════
//  5. FAVORITES
// ══════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('placeDetailFavBtn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        const loggedIn = btn.dataset.loggedIn === '1';
        if (!loggedIn) {
            showDetailToast('กรุณาเข้าสู่ระบบก่อนบันทึกรายการโปรด', 'error');
            setTimeout(() => { window.location.href = 'form-login.php'; }, 1200);
            return;
        }

        const placeId = btn.dataset.placeId;
        btn.disabled  = true;

        const fd = new FormData();
        fd.append('place_id', placeId);

        fetch('toggle_favorite.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    btn.disabled = false;
                    showDetailToast(d.message || 'เกิดข้อผิดพลาด', 'error');
                    return;
                }

                const icon = btn.querySelector('.iconify');

                if (d.action === 'added') {
                    showDetailToast('บันทึกรายการโปรดแล้ว 🐾 กำลังไปหน้ารายการโปรด...', 'success');
                    setTimeout(() => { window.location.href = 'favorites.php'; }, 900);
                } else {
                    btn.classList.remove('is-fav');
                    btn.title = 'บันทึกรายการโปรด';
                    btn.dataset.loggedIn = '1';
                    if (icon) {
                        icon.setAttribute('data-icon', 'mdi:heart-outline');
                        icon.style.color = '';
                        if (window.Iconify) Iconify.scan(btn);
                    }
                    btn.disabled = false;
                    showDetailToast('ลบออกจากรายการโปรดแล้ว', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                showDetailToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            });
    });
});