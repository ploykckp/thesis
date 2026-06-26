<?php

session_start();
require_once 'connect.php';

// Load สถานที่แนะนำ — เรียงตามคะแนนรีวิวเฉลี่ย + จำนวนรีวิว (approved เท่านั้น)
$popular_places = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT p.*,
                   COUNT(r.review_id)         AS review_count,
                   COALESCE(AVG(r.rating), 0) AS avg_rating
            FROM places p
            LEFT JOIN reviews r
                   ON r.place_id = p.place_id
                  AND r.status   = 'approved'
            WHERE p.status = 'approved'
            GROUP BY p.place_id
            ORDER BY avg_rating DESC, review_count DESC, p.place_id DESC
            LIMIT 9
        ");
        $popular_places = $stmt->fetchAll();
    } catch (PDOException $e) {
        $popular_places = [];
    }
}

function renderStars(int $count = 4): string {
    $html = '';
    for ($i = 0; $i < 5; $i++) {
        $color = $i < $count ? '#1e293b' : '#cbd5e1';
        $html .= '<span class="iconify" data-icon="material-symbols:star" 
                        data-width="18" data-height="18" style="color:' . $color . '"></span>';
    }
    return $html;
}

$fallback_imgs = [
    'uploads/placeholder.jpg',
    'uploads/placeholder.jpg',
    'uploads/placeholder.jpg',
    'uploads/placeholder.jpg',
    'uploads/placeholder.jpg',
    'uploads/placeholder.jpg',
];

// ไม่ต้องใช้ proxy แล้ว เพราะรูปเป็น local file
function proxyImg(?string $url): string {
    return $url ?? '';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — หน้าแรก</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="footer.css">
</head>
<body>

<!-- ══════════ HEADER ══════════ -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <img src="logo.png" alt="Logo" width="136" height="136">
            </div>
            <nav class="nav">
                <a href="home.php" class="nav-link">หน้าแรก</a>
                <a href="plantrip.php" class="nav-link">แผนเที่ยว</a>
                <a href="petinfo.php" class="nav-link">ข้อมูลสัตว์เลี้ยง</a>
                <a href="nearme.php" class="nav-link">ใกล้ฉัน</a>
            </nav>
            <div class="header-right">
                <div class="language-switch">
                    <span class="lang-active">TH</span>
                    <span class="lang-separator">|</span>
                    <span class="lang-inactive">EN</span>
                </div>
                <?php include 'header_user_icon.php'; ?>
            </div>
        </div>
    </div>
</header>

<!-- ══════════ MAIN ══════════ -->
<main class="main">
    <div class="container">

        <!-- HERO -->
        <section class="hero">
            <div class="hero-overlay">
                <h1 class="hero-title">"เที่ยวได้ทุกที่.. ถ้ามีเพื่อนชี้สี่ขาอยู่ด้วย"</h1>
                <div class="hero-buttons">
                    <button class="btn btn-primary">ผู้ใช้ทั่วไป</button>
                    <a href="form-register.php">
                        <button class="btn btn-secondary">สมัครสมาชิก</button>
                    </a>
                </div>
            </div>
        </section>

        <!-- ══ SEARCH BAR — ส่งไป Search.php เมื่อกด Enter หรือปุ่มค้นหา ══ -->
        <section class="search-section">
            <form method="GET" action="Search.php" id="searchForm">
                <div class="search-container">
                    <input
                        type="text"
                        name="search"
                        id="searchInput"
                        class="search-input"
                        placeholder="จังหวัด / สถานที่ท่องเที่ยว / โรงแรม"
                        autocomplete="off"
                    >
                    <button type="button" class="filter-btn">
                        <span class="iconify" data-icon="mage:filter" data-width="24" data-height="24"></span>
                    </button>
                    <button type="submit" class="search-btn">
                        <span class="iconify" data-icon="material-symbols:search-rounded" data-width="28" data-height="28"></span>
                    </button>
                </div>
            </form>
        </section>

        <!-- CATEGORIES — คลิกเพื่อกรองตามหมวดหมู่ใน Search.php -->
        <section class="categories">
            <div class="category-grid">
                <?php
                $cats = [
                    ['icon' => 'fa6-solid:hotel',            'label' => 'โรงแรม',          'val' => 'โรงแรม'],
                    ['icon' => 'carbon:cafe',                 'label' => 'คาเฟ่',           'val' => 'คาเฟ่'],
                    ['icon' => 'material-symbols:restaurant', 'label' => 'ร้านอาหาร',       'val' => 'ร้านอาหาร'],
                    ['icon' => 'ion:cut',                     'label' => 'อาบน้ำ ตัดขน',   'val' => 'อาบน้ำ ตัดขน'],
                    ['icon' => 'mingcute:hospital-fill',      'label' => 'โรงพยาบาลสัตว์', 'val' => 'โรงพยาบาลสัตว์'],
                ];
                // Map category name → iconify icon (same icons as category grid above)
                $category_icon_map = [
                    'โรงแรม'          => 'fa6-solid:hotel',
                    'คาเฟ่'           => 'carbon:cafe',
                    'ร้านอาหาร'       => 'material-symbols:restaurant',
                    'อาบน้ำ ตัดขน'   => 'ion:cut',
                    'โรงพยาบาลสัตว์' => 'mingcute:hospital-fill',
                ];
                foreach ($cats as $c): ?>
                <a href="Search.php?category=<?= urlencode($c['val']) ?>" class="category-card">
                    <div class="category-icon">
                        <span class="iconify" data-icon="<?= $c['icon'] ?>" data-width="64" data-height="64"></span>
                    </div>
                    <span class="category-label"><?= $c['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- POPULAR PLACES -->
        <section class="hotels-section">
            <div class="section-header">
                <h2 class="section-title">แนะนำสถานที่ยอดนิยม</h2>
                <p class="section-subtitle">ที่คนรักสัตว์ ต้องไป!!</p>
            </div>

            <div class="hotels-grid" id="hotelsGrid">
                <?php if (count($popular_places) > 0): ?>
                    <?php foreach ($popular_places as $i => $place): ?>
                    <a href="place_detail.php?id=<?= $place['place_id'] ?>" style="text-decoration:none;color:inherit;">
                    <div class="hotel-card<?= $i >= 6 ? ' hidden' : '' ?>" style="cursor:pointer">
                        <div class="hotel-image" <?= empty($place['place_image']) ? 'style="background:#d4e7f7"' : '' ?>>
                            <?php if (!empty($place['place_image'])): ?>
                            <img
                                src="<?= htmlspecialchars(proxyImg($place['place_image'])) ?>"
                                alt="<?= htmlspecialchars($place['place_name']) ?>"
                                onerror="this.parentElement.style.background='#d4e7f7';this.remove()"
                            >
                            <?php endif; ?>
                        </div>
                        <div class="hotel-info">
                            <h3 class="hotel-name"><?= htmlspecialchars($place['place_name']) ?></h3>
                            <div class="hotel-badge-icons">
                                <?php
                                $place_cats = array_map('trim', explode(',', $place['category'] ?? ''));
                                foreach ($place_cats as $pc):
                                    $ic = $category_icon_map[$pc] ?? null;
                                    if ($ic): ?>
                                        <span class="iconify place-cat-icon" data-icon="<?= htmlspecialchars($ic) ?>" data-width="20" data-height="20" title="<?= htmlspecialchars($pc) ?>"></span>
                                    <?php endif;
                                endforeach; ?>
                            </div>
                            <p class="hotel-location">
                                จังหวัด : <?= htmlspecialchars($place['province'] ?? '-') ?>
                                <?php if (!empty($place['pet_size_allowed'])): ?>
                                    <br>รองรับ : <?= htmlspecialchars($place['pet_size_allowed']) ?>
                                <?php endif; ?>
                            </p>
                            <div class="hotel-rating"><?= renderStars((int)round($place['avg_rating'])) ?></div>
                            <?php if ($place['review_count'] > 0): ?>
                            <p style="font-size:12px;color:#64748b;margin-top:4px"><?= number_format($place['avg_rating'],1) ?> (<?= $place['review_count'] ?> รีวิว)</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    </a>
                    <?php endforeach; ?>

                <?php else: ?>
                    <!-- Fallback static cards when DB is empty -->
                    <?php for ($i = 0; $i < 9; $i++): ?>
                    <div class="hotel-card<?= $i >= 6 ? ' hidden' : '' ?>">
                        <div class="hotel-image">
                            <img src="<?= $fallback_imgs[$i % count($fallback_imgs)] ?>" alt="โรงแรม">
                        </div>
                        <div class="hotel-info">
                            <h3 class="hotel-name">ชื่อโรงแรม</h3>
                            <div class="hotel-badge-icons">
                                <span class="iconify place-cat-icon" data-icon="fa6-solid:hotel" data-width="20" data-height="20" title="โรงแรม"></span>
                            </div>
                            <p class="hotel-location">รองรับ : สุนัข, แมว</p>
                            <div class="hotel-rating"><?= renderStars(4) ?></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>

            <div class="load-more-container">
                <button class="btn-load-more" id="loadMoreBtn">ดูเพิ่มเติม</button>
            </div>
        </section>

        <!-- FEATURED CARDS -->
        <section class="featured-cards-section">
            <a href="plantrip.php" style="text-decoration:none; display:block;">
            <div class="featured-card">
                <img src="2.jpg" alt="วางแผนทริป">
                <div class="featured-card-label">วางแผนทริป...ในทริปเดียว</div>
            </div>
            </a>
            <a href="donation.php" style="text-decoration:none; display:block;">
            <div class="featured-card">
                <img src="headbanner.jpeg" alt="สถานที่รับบริจาค">
                <div class="featured-card-label">สถานที่รับบริจาคสำหรับสัตว์เลี้ยง</div>
            </div>
            </a>
        </section>

        <!-- FOUNDATION — slide-left from donation locations -->
        <section class="foundation-section" id="foundationSection" style="overflow:hidden;">
            <div class="foundation-image" style="overflow:hidden;">
                <img id="fImg" src="foundation/f3/foundation-cover-sdf.jpg" alt="มูลนิธิ"
                     onerror="this.style.display='none'"
                     style="width:100%; height:100%; object-fit:cover; display:block;">
            </div>
            <div class="foundation-content" id="fContent">
                <h2>มูลนิธิสำหรับสัตว์</h2>
                <h3 id="fName"></h3>
                <p id="fDesc"></p>
                <p id="fAddr" style="font-size:18px; font-weight:400; color:#475569;"></p>
                <a href="donation.php"><button class="foundation-button">ดูข้อมูลเพิ่มเติม</button></a>
            </div>
        </section>

        <style>
        @keyframes slideInLeft {
            from { transform: translateX(60px); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
        @keyframes slideOutLeft {
            from { transform: translateX(0);     opacity: 1; }
            to   { transform: translateX(-60px); opacity: 0; }
        }
        .f-slide-out {
            animation: slideOutLeft 0.45s ease forwards;
        }
        .f-slide-in {
            animation: slideInLeft 0.45s ease forwards;
        }
        </style>

        <script>
        const foundations = [
            {
                name: 'มูลนิธิบ้านสงเคราะห์สัตว์พิการ',
                desc: 'ช่วยเหลือและดูแลสัตว์พิการที่ถูกทอดทิ้ง รับบริจาคทั้งเงินและสิ่งของเพื่อให้สัตว์มีคุณภาพชีวิตที่ดีขึ้น',
                addr: 'อ.ปากเกร็ด จ.นนทบุรี · 11:00–17:00 น.',
                img: 'foundation/f1/m1.jpg',
            },
            {
                name: 'ป้าจุ๊ บ้านพักสี่ขาเพื่อหมาจร',
                desc: 'บ้านพักพิงสำหรับสุนัขจรจัด ดูแลด้วยความรักและมุ่งหาบ้านใหม่ที่อบอุ่นให้น้องทุกตัว',
                addr: 'ลำลูกกา จ.ปทุมธานี · 11:00–17:00 น.',
                img: 'foundation/f2/123f296c94d2442b84c31338d1534d2d.jpg',
            },
            {
                name: 'มูลนิธิเพื่อสุนัขในซอย (Soi Dog)',
                desc: 'องค์กรช่วยเหลือสัตว์ที่ใหญ่ที่สุดแห่งหนึ่งในไทย รับช่วยสุนัขและแมวจรจัดทั่วประเทศ มีโปรแกรมทำหมันและรับบริจาคทั้งเงินและของ',
                addr: 'อ.ถลาง จ.ภูเก็ต',
                img: 'foundation/f3/foundation-cover-sdf.jpg',
            },
            {
                name: 'The ARK Chiangmai',
                desc: 'ศูนย์ช่วยเหลือสัตว์ในภาคเหนือ ดูแลสุนัขและแมวที่ถูกทอดทิ้ง พร้อมรับบริจาคและอาสาสมัคร',
                addr: 'อ.ดอยสะเก็ด จ.เชียงใหม่',
                img: 'foundation/f4/ARK-1.jpg',
            },
            {
                name: 'มูลนิธิรักษ์แมว ปันน้ำใจให้แมวจร',
                desc: 'ดูแลและหาบ้านให้แมวจรจัด รับบริจาคอาหาร ยา และสิ่งของจำเป็น เพื่อให้แมวทุกตัวมีโอกาสใหม่ในชีวิต',
                addr: 'เขตบางซื่อ กทม.',
                img: 'foundation/f5/761456.jpg',
            },
            {
                name: 'บ้านนางฟ้าของสัตว์จร',
                desc: 'บ้านพักพิงสำหรับสัตว์จรจัดในจังหวัดสระบุรี รับบริจาคทุกรูปแบบเพื่อเลี้ยงดูสัตว์นับร้อยชีวิต',
                addr: 'อ.มวกเหล็ก จ.สระบุรี',
                img: 'foundation/f6/20190125153252.jpg',
            },
            {
                name: 'บ้านหมา ป้ามณี',
                desc: 'บ้านพักสุนัขที่ดูแลด้วยหัวใจ รับบริจาคอาหารและสิ่งของเพื่อเลี้ยงดูน้องหมาให้มีความสุข',
                addr: 'อ.ลำลูกกา จ.ปทุมธานี · 10:00–17:00 น.',
                img: 'foundation/f7/14188392_1055727494474500_391225900942964193_o.jpg',
            },
            {
                name: 'มูลนิธิสันติสุขเพื่อสุนัขและแมวจรจัด',
                desc: 'ช่วยเหลือสุนัขและแมวจรจัดในภาคเหนือ มุ่งมั่นสร้างความสุขและหาบ้านที่อบอุ่นให้สัตว์ทุกตัว',
                addr: 'อ.ดอยสะเก็ด จ.เชียงใหม่',
                img: 'foundation/f10/20191021132704.jpg',
            },
        ];

        let fCurrent = 0;
        let fAnimating = false;

        function slideToFoundation(index) {
            if (fAnimating) return;
            fAnimating = true;

            const imgEl    = document.getElementById('fImg');
            const content  = document.getElementById('fContent');

            // slide out both image and content
            imgEl.classList.add('f-slide-out');
            content.classList.add('f-slide-out');

            setTimeout(() => {
                const d = foundations[index];

                // swap content
                imgEl.src = d.img;
                imgEl.onerror = function() {
                    this.style.display = 'none';
                };
                document.getElementById('fName').textContent = d.name;
                document.getElementById('fDesc').textContent = d.desc;
                document.getElementById('fAddr').innerHTML = '<span class="iconify" data-icon="mdi:map-marker"></span> ' + d.addr;

                // slide in
                imgEl.classList.remove('f-slide-out');
                content.classList.remove('f-slide-out');
                imgEl.classList.add('f-slide-in');
                content.classList.add('f-slide-in');

                setTimeout(() => {
                    imgEl.classList.remove('f-slide-in');
                    content.classList.remove('f-slide-in');
                    fAnimating = false;
                }, 450);

            }, 450);
        }

        setInterval(() => {
            fCurrent = (fCurrent + 1) % foundations.length;
            slideToFoundation(fCurrent);
        }, 4000);

        slideToFoundation(0);
        </script>

        <!-- ARTICLES -->
        <section class="articles-section">
            <h2 class="articles-header">บทความและความรู้</h2>
            <div class="articles-container">
                <div class="articles-list">
                    <a href="article_travel.php" style="text-decoration:none;">
                    <div class="article-item">
                        <span>วิธีพาสัตว์เลี้ยงเที่ยวอย่างปลอดภัย</span>
                        <span class="iconify" data-icon="mdi:chevron-right"></span>
                    </div>
                    </a>
                    <a href="article_travel.php#section-prepare" style="text-decoration:none;">
                    <div class="article-item">
                        <span>ทริคเตรียมตัวก่อนเดินทาง</span>
                        <span class="iconify" data-icon="mdi:chevron-right"></span>
                    </div>
                    </a>
                    <a href="article_news.php" style="text-decoration:none;">
                    <div class="article-item">
                        <span>ข่าวท่องเที่ยวเชิงสัตว์เลี้ยง</span>
                        <span class="iconify" data-icon="mdi:chevron-right"></span>
                    </div>
                    </a>
                    <a href="article_events.php" style="text-decoration:none;">
                    <div class="article-item">
                        <span>รวมงานอีเวนต์สำหรับสัตว์เลี้ยง</span>
                        <span class="iconify" data-icon="mdi:chevron-right"></span>
                    </div>
                    </a>
                </div>
                <div class="articles-carousel">
                    <img class="carousel-image" src="8.jpg" alt="Dogs on beach">
                    <div class="carousel-dots">
                        <div class="carousel-dot active"></div>
                        <div class="carousel-dot"></div>
                        <div class="carousel-dot"></div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</main>

<?php include 'footer.php'; ?>

<script src="script.js"></script>
<script>
    // Submit search form on Enter key
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchForm').submit();
        }
    });
</script>
</body>
</html>