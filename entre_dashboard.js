// ================================================
//  entre_dashboard.js — Entrepreneur Dashboard JS
// ================================================

/* ════════════════════════════════════════════
   STATE
════════════════════════════════════════════ */
const state = {
    step: 1,
    page: 'dashboard',
    // Form data
    place_name: '',
    category: '',
    description: '',
    phone: '',
    open_time: '12:00',
    close_time: '12:00',
    licenseFiles: [],
    // Step 2
    address: '',
    province: '',
    lat: null,
    lng: null,
    // Step 3
    pet_allowed: null,
    pet_type: '',
    pet_sizes: [],
    custom_weight: '',
    extra_cost: '',
    // Step 4
    placeImages: [],
    placeImageUrls: [],
    selectedGeneralAmenities: [],
    selectedPetAmenities: [],
    pet_rules: '',
};

let addMap = null;
let addMapMarker = null;
let previewMap = null;

/* ════════════════════════════════════════════
   PAGE NAVIGATION
════════════════════════════════════════════ */
function showPage(page) {
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

    state.page = page;

    if (page === 'dashboard') {
        document.getElementById('page-dashboard').classList.add('active');
        document.getElementById('nav-dashboard').classList.add('active');
        initChart();
    } else if (page === 'add-place') {
        document.getElementById('page-add-place').classList.add('active');
        document.getElementById('nav-add').classList.add('active');
        setStep(1);
    } else if (page === 'my-places') {
        document.getElementById('page-my-places').classList.add('active');
        document.getElementById('nav-myplaces').classList.add('active');
    } else if (page === 'my-reviews') {
        document.getElementById('page-my-reviews').classList.add('active');
        document.getElementById('nav-myreviews').classList.add('active');
    }
}

/* ════════════════════════════════════════════
   STEP NAVIGATION
════════════════════════════════════════════ */
function setStep(n) {
    // Hide all steps
    ['step-1','step-2','step-3','step-4','step-preview'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    state.step = n;
    const stepId = n === 'preview' ? 'step-preview' : `step-${n}`;
    const el = document.getElementById(stepId);
    if (el) el.style.display = 'block';

    // Init map on step 2
    if (n === 2) {
        setTimeout(initAddMap, 100);
    }
}

/* ════════════════════════════════════════════
   VALIDATE STEP 1
════════════════════════════════════════════ */
function validateStep1() {
    const name  = document.getElementById('place_name').value.trim();
    const phone = document.getElementById('place_phone').value.trim();
    // อ่าน checkbox ที่ติ๊กไว้
    const checkedCats = Array.from(
        document.querySelectorAll('input[name="place_category_cb"]:checked')
    ).map(cb => cb.value);
    const cat = checkedCats.join(',');
    let valid = true;

    clearErrors();

    if (!name)  { showError('err_name');     markError('place_name');  valid = false; }
    if (!cat)   { showError('err_category');                            valid = false; }
    if (!phone) { showError('err_phone');    markError('place_phone'); valid = false; }
    if (state.licenseFiles.length === 0) { showError('err_license'); valid = false; }

    if (!valid) {
        showToast('กรุณากรอกข้อมูลให้ครบทุกช่อง');
        return;
    }

    // Save state
    state.place_name  = name;
    state.category    = cat;
    state.description = document.getElementById('place_description').value.trim();
    state.phone       = phone;

    // ถ้าติ๊ก 24 ชั่วโมง ให้ส่งค่า '00:00' - '23:59'
    const is24 = document.getElementById('open24Check')?.checked;
    state.open_time  = is24 ? '00:00' : document.getElementById('open_time').value;
    state.close_time = is24 ? '23:59' : document.getElementById('close_time').value;

    setStep(2);
}

// ── 24 ชั่วโมง toggle ──────────────────────────────────────────────────────
function toggle24Hour(cb) {
    const openInput  = document.getElementById('open_time');
    const closeInput = document.getElementById('close_time');
    const timeRow    = document.getElementById('timeRow');

    if (cb.checked) {
        // ปิด input และแสดง label แทน
        openInput.disabled  = true;
        closeInput.disabled = true;
        timeRow.style.opacity      = '0.4';
        timeRow.style.pointerEvents = 'none';
    } else {
        openInput.disabled  = false;
        closeInput.disabled = false;
        timeRow.style.opacity      = '1';
        timeRow.style.pointerEvents = '';
    }
}

// อัปเดตสไตล์ pill + hidden input + doc section เมื่อติ๊ก checkbox
// onCategoryChange และ renderDashDocumentSection ถูกย้ายไปใน entre_dashboard.php แล้ว
/* ════════════════════════════════════════════
   VALIDATE STEP 2
════════════════════════════════════════════ */
function validateStep2() {
    const addr = document.getElementById('place_address').value.trim();
    const prov = document.getElementById('place_province').value;
    let valid = true;

    clearErrors();

    if (!addr) { showError('err_address'); markError('place_address'); valid = false; }
    if (!prov) { showError('err_province'); markError('place_province'); valid = false; }

    if (!valid) {
        showToast('กรุณากรอกข้อมูลให้ครบทุกช่อง');
        return;
    }

    state.address  = addr;
    state.province = prov;
    state.lat = parseFloat(document.getElementById('place_lat').value) || null;
    state.lng = parseFloat(document.getElementById('place_lng').value) || null;

    setStep(3);
}

/* ════════════════════════════════════════════
   VALIDATE STEP 3
════════════════════════════════════════════ */
function validateStep3() {
    const petAllowed = document.querySelector('input[name="pet_allowed"]:checked');
    if (!petAllowed) {
        showToast('กรุณาเลือกว่ารับสัตว์เลี้ยงหรือไม่');
        return;
    }

    state.pet_allowed = petAllowed.value;
    const petTypesArr = Array.from(document.querySelectorAll('.pet-type-cb:checked')).map(cb => cb.value);
    const otherCheck = document.getElementById('pet_type_other_check');
    if (otherCheck && otherCheck.checked) {
        const otherVal = document.getElementById('pet_type_other_input')?.value.trim() || '';
        if (otherVal) petTypesArr.push(otherVal);
    }
    state.pet_type = petTypesArr.join(', ');
    state.extra_cost = document.getElementById('extra_cost').value.trim();

    // Collect pet sizes
    state.pet_sizes = [];
    document.querySelectorAll('input[name="pet_size"]:checked').forEach(cb => {
        if (cb.value !== 'custom') state.pet_sizes.push(cb.value);
    });

    const customCheck = document.getElementById('size_custom_check');
    if (customCheck && customCheck.checked) {
        const w = document.getElementById('custom_weight').value;
        if (w) {
            state.custom_weight = w;
            state.pet_sizes.push(`รับสูงสุดไม่เกิน ${w} กิโลกรัม`);
        }
    }

    setStep(4);
}

/* ════════════════════════════════════════════
   SHOW PREVIEW
════════════════════════════════════════════ */
function showPreview() {
    // Save step 4 data
    state.pet_rules = document.getElementById('pet_rules').value.trim();

    // Build preview
    document.getElementById('prev-name').textContent = state.place_name;
    document.getElementById('prev-address').textContent = `${state.address}, ${state.province}`;
    document.getElementById('prev-map-address').textContent = `${state.address}, ${state.province}`;

    // Gallery
    const mainImg = document.getElementById('prev-main-img');
    const thumbIds = ['prev-thumb-1','prev-thumb-2','prev-thumb-3','prev-thumb-4'];

    if (state.placeImageUrls.length > 0) {
        mainImg.innerHTML = `<img src="${state.placeImageUrls[0]}" alt="">`;
        thumbIds.forEach((id, i) => {
            const el = document.getElementById(id);
            if (state.placeImageUrls[i+1]) {
                el.innerHTML = `<img src="${state.placeImageUrls[i+1]}" alt="">`;
            } else {
                el.innerHTML = `<div class="preview-gallery-placeholder"></div>`;
            }
        });
    } else {
        mainImg.innerHTML = `<div class="preview-gallery-placeholder" style="height:100%">ไม่มีรูปภาพ</div>`;
        thumbIds.forEach(id => {
            document.getElementById(id).innerHTML = `<div class="preview-gallery-placeholder"></div>`;
        });
    }

    // Amenities list
    const amenList = document.getElementById('prev-amenities-list');
    amenList.innerHTML = '';
    state.selectedGeneralAmenities.forEach(a => {
        amenList.innerHTML += `<div class="preview-amenity-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64b5f6" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            ${a}
        </div>`;
    });

    // Pet pills
    const petPills = document.getElementById('prev-pet-pills');
    petPills.innerHTML = '';
    state.selectedPetAmenities.forEach(a => {
        petPills.innerHTML += `<span class="preview-pill" style="background:#1e4f7a">${a}</span>`;
    });

    // Pet info
    const petInfo = document.getElementById('prev-pet-info');
    petInfo.innerHTML = '';
    if (state.pet_allowed === 'yes') {
        petInfo.innerHTML += `<p>🐾 ประเภทสัตว์: ${state.pet_type}</p>`;
        if (state.pet_sizes.length > 0) {
            petInfo.innerHTML += `<p>📐 ขนาด: ${state.pet_sizes.join(', ')}</p>`;
        }
        const costText = state.extra_cost || '-';
        petInfo.innerHTML += `<p>💰 ค่าใช้จ่ายเพิ่มเติม: ${costText}</p>`;
    } else {
        petInfo.innerHTML = `<p style="color:#ef4444">ไม่รับสัตว์เลี้ยง</p>`;
    }

    // Google Maps link
    if (state.lat && state.lng) {
        document.getElementById('prev-gmap-btn').onclick = () => {
            window.open(`https://www.google.com/maps?q=${state.lat},${state.lng}`, '_blank');
        };
    }

    setStep('preview');

    // Init preview map
    if (state.lat && state.lng) {
        setTimeout(initPreviewMap, 200);
    }
}

/* ════════════════════════════════════════════
   PREVIEW TABS
════════════════════════════════════════════ */
function switchPreviewTab(tab, btn) {
    document.querySelectorAll('.preview-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    document.getElementById('prev-tab-info').style.display = tab === 'info' ? 'block' : 'none';
    document.getElementById('prev-tab-location').style.display = tab === 'location' ? 'block' : 'none';

    if (tab === 'location' && state.lat && state.lng) {
        setTimeout(() => {
            if (previewMap) previewMap.invalidateSize();
        }, 100);
    }
}

/* ════════════════════════════════════════════
   CONFIRM SUBMIT
════════════════════════════════════════════ */
function confirmSubmit() {
    const formData = new FormData();
    formData.append('place_name', state.place_name);
    formData.append('category', state.category);
    formData.append('description', state.description);
    formData.append('phone', state.phone);
    formData.append('open_time', state.open_time);
    formData.append('close_time', state.close_time);
    formData.append('address', state.address);
    formData.append('province', state.province);
    formData.append('latitude', state.lat || 0);
    formData.append('longitude', state.lng || 0);
    formData.append('pet_allowed', state.pet_allowed);
    formData.append('pet_type', state.pet_type);
    formData.append('pet_size', state.pet_sizes.join(', '));
    formData.append('extra_cost', state.extra_cost);
    formData.append('pet_rules', state.pet_rules);
    formData.append('amenities', state.selectedGeneralAmenities.join(','));
    formData.append('pet_amenities', state.selectedPetAmenities.join(','));

    // Append license files
    if (state.licenseFiles.length > 0) {
        formData.append('license_file', state.licenseFiles[0]);
    }

    // Append category verification documents
    Object.entries(window.dashUploadedDocs).forEach(([key, files]) => {
        files.forEach(file => formData.append('category_docs[]', file));
    });

    // Append place images
    state.placeImages.forEach(img => {
        formData.append('place_images[]', img);
    });

    fetch('saveplace.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('successModal').classList.add('open');
        } else {
            showToast(data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
        }
    })
    .catch(() => {
        showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่');
    });
}

function afterConfirm() {
    document.getElementById('successModal').classList.remove('open');
    // Reset form
    resetForm();
    showPage('my-places');
    // Add a mock pending entry to the table
    addPendingToTable(state.place_name || 'สถานที่ใหม่');
}

function addPendingToTable(name) {
    const tbody = document.getElementById('places-tbody');
    const rows = tbody.querySelectorAll('.place-row');
    const newNum = rows.length + 1;
    const row = document.createElement('div');
    row.className = 'table-row place-row';
    row.dataset.name = name.toLowerCase();
    row.dataset.status = 'pending';
    row.innerHTML = `
        <span>${newNum}</span>
        <span>${name}</span>
        <span><span class="status-badge status-pending">pending</span></span>
    `;
    // Remove empty message if exists
    const empty = tbody.querySelector('[style*="1fr"]');
    if (empty) empty.remove();
    tbody.appendChild(row);
}

function resetForm() {
    // Reset all state
    Object.assign(state, {
        step: 1, place_name: '', category: '', description: '',
        phone: '', open_time: '12:00', close_time: '12:00',
        licenseFiles: [], address: '', province: '', lat: null, lng: null,
        pet_allowed: null, pet_type: '', pet_sizes: [], custom_weight: '',
        extra_cost: '', placeImages: [], placeImageUrls: [],
        selectedGeneralAmenities: [], selectedPetAmenities: [], pet_rules: ''
    });

    // Reset form fields
    ['place_name','place_category','place_description','place_phone',
     'place_address','place_province','extra_cost','pet_rules'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    document.querySelectorAll('input[name="pet_allowed"]').forEach(r => r.checked = false);
    document.querySelectorAll('input[name="pet_size"]').forEach(c => c.checked = false);
    document.querySelectorAll('.amenity-tag').forEach(t => t.classList.remove('selected'));
    document.getElementById('license-preview').innerHTML = '';
    document.getElementById('place-images-preview').innerHTML = '';

    // Reset document uploads
    window.dashUploadedDocs = {};
    const docSection = document.getElementById('dash_document_section');
    if (docSection) docSection.style.display = 'none';
    const docFields = document.getElementById('dash_document_fields');
    if (docFields) docFields.innerHTML = '';

    // Reset pet type checkboxes
    document.querySelectorAll('.pet-type-cb').forEach(c => c.checked = false);
    const otherWrap = document.getElementById('pet_type_other_wrap');
    if (otherWrap) otherWrap.style.display = 'none';
    const otherCheck = document.getElementById('pet_type_other_check');
    if (otherCheck) otherCheck.checked = false;
    const otherInput = document.getElementById('pet_type_other_input');
    if (otherInput) otherInput.value = '';
}

/* ════════════════════════════════════════════
   MAP INIT
════════════════════════════════════════════ */
/* ── Google Maps callback (called after API loads) ── */
let googleMapsReady = false;
function initGoogleMaps() {
    googleMapsReady = true;
}

function initAddMap() {
    if (!googleMapsReady) { setTimeout(initAddMap, 150); return; }

    const mapEl = document.getElementById('addMap');
    if (!mapEl) return;

    const defaultLat = 13.7563;
    const defaultLng = 100.5018;

    const center = { lat: defaultLat, lng: defaultLng };

    addMap = new google.maps.Map(mapEl, {
        center: center,
        zoom: 13,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true,
        zoomControl: true,
    });

    addMapMarker = new google.maps.Marker({
        position: center,
        map: addMap,
        draggable: true,
        title: 'ลากเพื่อย้ายตำแหน่ง',
        animation: google.maps.Animation.DROP,
    });

    updateLatLng(defaultLat, defaultLng);

    // Drag marker
    addMapMarker.addListener('dragend', function(e) {
        updateLatLng(e.latLng.lat(), e.latLng.lng());
    });

    // Click on map
    addMap.addListener('click', function(e) {
        addMapMarker.setPosition(e.latLng);
        updateLatLng(e.latLng.lat(), e.latLng.lng());
    });

    // Places Autocomplete on search input
    const input = document.getElementById('map_search');
    if (input) {
        const autocomplete = new google.maps.places.Autocomplete(input, {
            fields: ['geometry', 'name', 'formatted_address'],
        });
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (!place.geometry || !place.geometry.location) {
                showToast('ไม่พบสถานที่ที่เลือก');
                return;
            }
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            addMap.setCenter({ lat, lng });
            addMap.setZoom(16);
            addMapMarker.setPosition({ lat, lng });
            updateLatLng(lat, lng);
        });
    }
}

function updateLatLng(lat, lng) {
    document.getElementById('place_lat').value = lat.toFixed(6);
    document.getElementById('place_lng').value = lng.toFixed(6);
    state.lat = lat;
    state.lng = lng;
}

function searchMap() {
    // Kept for compatibility — search is now handled by Places Autocomplete
    const query = document.getElementById('map_search').value.trim();
    if (!query || !googleMapsReady) return;
    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ address: query }, function(results, status) {
        if (status === 'OK' && results[0]) {
            const loc = results[0].geometry.location;
            const lat = loc.lat();
            const lng = loc.lng();
            addMap.setCenter({ lat, lng });
            addMap.setZoom(16);
            addMapMarker.setPosition({ lat, lng });
            updateLatLng(lat, lng);
        } else {
            showToast('ไม่พบสถานที่ที่ค้นหา <span class="iconify" data-icon="mdi:paw"></span>');
        }
    });
}

function initPreviewMap() {
    if (!googleMapsReady) { setTimeout(initPreviewMap, 150); return; }

    const mapEl = document.getElementById('previewMap');
    if (!mapEl || !state.lat || !state.lng) return;

    const pos = { lat: state.lat, lng: state.lng };

    previewMap = new google.maps.Map(mapEl, {
        center: pos,
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: false,
        zoomControl: true,
        fullscreenControl: false,
    });

    const marker = new google.maps.Marker({
        position: pos,
        map: previewMap,
        title: state.place_name || 'สถานที่',
        animation: google.maps.Animation.DROP,
    });

    const infoWindow = new google.maps.InfoWindow({
        content: `<strong>${state.place_name || 'สถานที่'}</strong>`,
    });
    infoWindow.open(previewMap, marker);
}

/* ════════════════════════════════════════════
   OTHER PET TYPE INPUT TOGGLE
════════════════════════════════════════════ */
function toggleOtherPetInput() {
    const checked = document.getElementById('pet_type_other_check').checked;
    const wrap = document.getElementById('pet_type_other_wrap');
    if (wrap) wrap.style.display = checked ? 'block' : 'none';
}

/* ════════════════════════════════════════════
   DYNAMIC DOCUMENT UPLOAD (step-1 add-place)
════════════════════════════════════════════ */

function handleLicenseUpload(event) {
    const files = Array.from(event.target.files);
    state.licenseFiles = files;
    const preview = document.getElementById('license-preview');
    preview.innerHTML = '';
    files.forEach((file, idx) => {
        const chip = document.createElement('div');
        chip.className = 'file-chip';
        chip.innerHTML = `
            <span>${file.type.includes('pdf') ? '📄' : '🖼️'}</span>
            <span>${file.name}</span>
            <button class="file-chip-remove" onclick="removeLicense(${idx})">×</button>
        `;
        preview.appendChild(chip);
    });
}

function removeLicense(idx) {
    state.licenseFiles.splice(idx, 1);
    // Re-render
    const preview = document.getElementById('license-preview');
    preview.innerHTML = '';
    state.licenseFiles.forEach((file, i) => {
        const chip = document.createElement('div');
        chip.className = 'file-chip';
        chip.innerHTML = `
            <span>${file.type.includes('pdf') ? '📄' : '🖼️'}</span>
            <span>${file.name}</span>
            <button class="file-chip-remove" onclick="removeLicense(${i})">×</button>
        `;
        preview.appendChild(chip);
    });
}

function handlePlaceImages(event) {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        state.placeImages.push(file);
        const url = URL.createObjectURL(file);
        state.placeImageUrls.push(url);
    });
    renderPlaceImages();
}

function renderPlaceImages() {
    const grid = document.getElementById('place-images-preview');
    grid.innerHTML = '';
    state.placeImageUrls.forEach((url, i) => {
        const item = document.createElement('div');
        item.className = 'image-preview-item';
        item.innerHTML = `
            <img src="${url}" alt="">
            <button class="image-preview-remove" onclick="removePlaceImage(${i})">×</button>
        `;
        grid.appendChild(item);
    });
}

function removePlaceImage(idx) {
    state.placeImages.splice(idx, 1);
    URL.revokeObjectURL(state.placeImageUrls[idx]);
    state.placeImageUrls.splice(idx, 1);
    renderPlaceImages();
}

/* ════════════════════════════════════════════
   AMENITY TOGGLES
════════════════════════════════════════════ */
function toggleAmenity(el, type) {
    el.classList.toggle('selected');
    const key = type === 'general' ? 'selectedGeneralAmenities' : 'selectedPetAmenities';
    const text = el.textContent.trim();
    const idx = state[key].indexOf(text);
    if (idx === -1) state[key].push(text);
    else state[key].splice(idx, 1);
}

/* ════════════════════════════════════════════
   PET SECTION TOGGLE
════════════════════════════════════════════ */
function togglePetSection() {
    const petYes = document.getElementById('pet_yes');
    const section = document.getElementById('pet-details-section');
    if (section) {
        section.style.display = petYes.checked ? 'block' : 'none';
    }
}

function toggleCustomWeight() {
    const check = document.getElementById('size_custom_check');
    const input = document.getElementById('custom_weight');
    if (input) input.style.display = check.checked ? 'inline-block' : 'none';
}

/* ════════════════════════════════════════════
   ERROR HELPERS
════════════════════════════════════════════ */
function showError(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('show');
}

function markError(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('error');
}

function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(e => e.classList.remove('show'));
    document.querySelectorAll('.form-input, .form-select').forEach(e => e.classList.remove('error'));
}

function showToast(msg) {
    const toast = document.getElementById('toastError');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

/* ════════════════════════════════════════════
   CHART INIT
════════════════════════════════════════════ */
let chartInstance    = null;
let viewInstance     = null;
let reviewInstance   = null;

function initChart() {
    if (!HAS_PLACES) return;

    // ── Bar: view per month ──
    const viewCanvas = document.getElementById('viewChart');
    if (viewCanvas && typeof ENTRE_REVIEW_DATA !== 'undefined') {
        if (viewInstance) { viewInstance.destroy(); viewInstance = null; }
        viewInstance = new Chart(viewCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ENTRE_REVIEW_DATA.labels,
                datasets: [{
                    label: 'ยอดเข้าชม',
                    data:  ENTRE_REVIEW_DATA.data,
                    backgroundColor: 'rgba(30,58,95,0.12)',
                    borderColor:     '#1e3a5f',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(30,58,95,0.28)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family: 'Kanit', size: 11 } } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Kanit', size: 11 } }, grid: { color: 'rgba(0,0,0,0.04)' } }
                }
            }
        });
    }

    // ── Bar: review per month ──
    const reviewCanvas = document.getElementById('reviewChart');
    if (reviewCanvas && typeof ENTRE_CATEGORY_DATA !== 'undefined') {
        if (reviewInstance) { reviewInstance.destroy(); reviewInstance = null; }
        reviewInstance = new Chart(reviewCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ENTRE_CATEGORY_DATA.reviewLabels || [],
                datasets: [{
                    label: 'รีวิว',
                    data:  ENTRE_CATEGORY_DATA.reviewData || [],
                    backgroundColor: 'rgba(99,102,241,0.15)',
                    borderColor:     '#6366f1',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(99,102,241,0.35)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family: 'Kanit', size: 11 } } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Kanit', size: 11 } }, grid: { color: 'rgba(0,0,0,0.04)' } }
                }
            }
        });
    }
}

function changeYear(year) {
    const url = new URL(window.location.href);
    url.searchParams.set('year', year);
    window.location.href = url.toString();
}

/* ════════════════════════════════════════════
   PLACES FILTER
════════════════════════════════════════════ */
function filterPlaces() {
    const search = document.getElementById('places-search').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    document.querySelectorAll('.place-row').forEach(row => {
        const name = row.dataset.name || '';
        const st   = row.dataset.status || '';
        const matchSearch = !search || name.includes(search);
        const matchStatus = !status || st === status;
        row.style.display = matchSearch && matchStatus ? '' : 'none';
    });
}

/* ════════════════════════════════════════════
   INIT
════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    // Init pet section visibility
    const section = document.getElementById('pet-details-section');
    if (section) section.style.display = 'block';

    // ถ้ามี ?year= ใน URL ให้เปิดหน้า dashboard อัตโนมัติ
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('year')) {
        showPage('dashboard');
    }

    // Init chart if on dashboard with places
    if (HAS_PLACES) {
        setTimeout(initChart, 200);
    }
});