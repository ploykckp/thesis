// ================================================
//  live-search.js — พิมพ์แล้วขึ้นผลลัพธ์ทันที (debounce 300ms)
//  ใช้ร่วมกันได้ทุกหน้าที่มี #searchInput + #liveSearchDropdown
// ================================================
(function () {
    const input    = document.getElementById('searchInput');
    const dropdown = document.getElementById('liveSearchDropdown');
    if (!input || !dropdown) return;

    const categoryIcon = {
        'โรงแรม':          'fa6-solid:hotel',
        'คาเฟ่':           'carbon:cafe',
        'ร้านอาหาร':       'material-symbols:restaurant',
        'อาบน้ำ ตัดขน':    'mdi:content-cut',
        'โรงพยาบาลสัตว์':  'mdi:hospital-box',
    };

    let debounceTimer;
    let currentController;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function renderResults(results) {
        if (!results.length) {
            dropdown.innerHTML = '';
            dropdown.style.display = 'none';
            return;
        }
        dropdown.innerHTML = results.map(r => `
            <a href="place_detail.php?id=${encodeURIComponent(r.place_id)}" class="live-search-item">
                <div class="live-search-thumb">
                    ${r.place_image
                        ? `<img src="${escapeHtml(r.place_image)}" alt="">`
                        : `<span class="iconify" data-icon="${categoryIcon[r.category] || 'mdi:map-marker'}" data-width="20" data-height="20"></span>`
                    }
                </div>
                <div class="live-search-info">
                    <div class="live-search-name">${escapeHtml(r.place_name)}</div>
                    <div class="live-search-meta">${escapeHtml(r.category || '')} · ${escapeHtml(r.province || '')}</div>
                </div>
            </a>
        `).join('');
        dropdown.style.display = 'block';
        if (window.Iconify && Iconify.scan) Iconify.scan(dropdown);
    }

    input.addEventListener('input', function () {
        const q = input.value.trim();
        clearTimeout(debounceTimer);

        if (q.length < 1) {
            dropdown.innerHTML = '';
            dropdown.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            if (currentController) currentController.abort();
            currentController = new AbortController();

            fetch('live_search.php?q=' + encodeURIComponent(q), { signal: currentController.signal })
                .then(res => res.json())
                .then(data => renderResults(data.results || []))
                .catch(err => { if (err.name !== 'AbortError') console.error('live search error:', err); });
        }, 300);
    });

    // ปิด dropdown เมื่อคลิกนอกช่องค้นหา
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // เปิด dropdown กลับมาถ้ามีผลลัพธ์ค้างอยู่แล้วตอน focus ช่องค้นหาอีกครั้ง
    input.addEventListener('focus', function () {
        if (input.value.trim().length >= 1 && dropdown.innerHTML.trim() !== '') {
            dropdown.style.display = 'block';
        }
    });
})();