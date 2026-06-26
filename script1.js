const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('searchForm').submit();
            }
        });
    }

    // ── 2. Clear search button ─────────────────────────
    window.clearSearch = function () {
        window.location.href = 'home.php';
    };

    // ── 3. Load More button ────────────────────────────
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            window.location.href = 'places.php';
        });
    }

    // ── 4. Articles Carousel ───────────────────────────
    const dots = document.querySelectorAll('.carousel-dot');
    const carouselImages = [
        'https://images.unsplash.com/photo-1530281700549-e82e7bf110d6?w=800',
        'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=800',
        'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=800',
    ];

    dots.forEach(function (dot, index) {
        dot.addEventListener('click', function () {
            dots.forEach(d => d.classList.remove('active'));
            dot.classList.add('active');

            const img = document.querySelector('.carousel-image');
            if (img && carouselImages[index]) {
                img.src = carouselImages[index];
            }
        });
    });

    // Auto-rotate carousel every 4 seconds
    let currentSlide = 0;
    if (dots.length > 0) {
        setInterval(function () {
            currentSlide = (currentSlide + 1) % dots.length;
            dots.forEach(d => d.classList.remove('active'));
            dots[currentSlide].classList.add('active');

            const img = document.querySelector('.carousel-image');
            if (img && carouselImages[currentSlide]) {
                img.src = carouselImages[currentSlide];
            }
        }, 4000);
    }