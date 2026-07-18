<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — สถานที่รับบริจาคสำหรับสัตว์เลี้ยง</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="donation.css">
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
                </div>
                <?php include 'header_user_icon.php'; ?>
            </div>
        </div>
    </div>
</header>

<!-- ══════════ MAIN ══════════ -->
<main class="main">
    <div class="container">

        <!-- DONATION HERO -->
        <section class="donation-hero">
            <div class="donation-hero-content">
                <div class="donation-hero-icon"></div>
                <h1 class="donation-hero-title">สถานที่รับบริจาคสำหรับสัตว์เลี้ยง</h1>
                <p class="donation-hero-subtitle">ร่วมเป็นส่วนหนึ่งในการช่วยเหลือสัตว์ที่ต้องการความรักและการดูแล</p>
            </div>
        </section>

    </div><!-- end .container -->

    <!-- NOTICE -->
    <div class="donation-notice">
        <div class="notice-icon"></div>
        <div class="notice-text">
            <h3>ก่อนเดินทาง แนะนำโทรสอบถามล่วงหน้า</h3>
            <p>เวลาเปิด-ปิดอาจมีการเปลี่ยนแปลงตามสถานการณ์ กรุณาติดต่อสถานที่ก่อนเดินทางเสมอ และตรวจสอบรายการสิ่งของที่ต้องการบริจาค</p>
        </div>
    </div>

    <!-- SECTION HEADER -->
    <div class="donation-section-header">
        <h2 class="donation-section-title">มูลนิธิและบ้านพักสัตว์ทั่วไทย</h2>
        <p class="donation-section-subtitle">10 สถานที่ที่พร้อมรับการสนับสนุนจากคุณ</p>
    </div>

    <!-- ══ LOCATION CARDS ══ -->
    <div class="locations-wrapper">

        <?php
        $locations = [
            [
                'name'    => 'มูลนิธิบ้านสงเคราะห์สัตว์พิการ',
                'folder'  => 'f1',
                'fb'      => 'มูลนิธิบ้านสงเคราะห์สัตว์พิการ(home4animals)',
                'fb_url'  => 'https://www.facebook.com/home4animalsth',
                'tel'     => '02-961-5360, 02-584-4896, 065-887-4888',
                'address' => '15/1 ม.1 ซ.พระแม่มหาการุณย์ 25 ถ.ติวานนท์-ปากเกร็ด56 ต.บ้านใหม่ อ.ปากเกร็ด จ.นนทบุรี 11120',
                'hours'   => '11:00 – 17:00 น.',
            ],
            [
                'name'    => 'ป้าจุ๊ บ้านพักสี่ขาเพื่อหมาจร',
                'folder'  => 'f2',
                'fb'      => 'ป้าจุ๊ บ้านพักสี่ขาเพื่อหมาจร',
                'fb_url'  => 'https://www.facebook.com/pacuhomeforstraydogs',
                'tel'     => '086-7751151',
                'address' => 'บ้านพักสี่ขาลำลูกกาคลอง10 จ.ปทุมธานี / บ้านพักสี่ขาสระกระโจม อ.ดอนเจดีย์ จ.สุพรรณบุรี',
                'hours'   => '11:00 – 17:00 น.',
            ],
            [
                'name'    => 'มูลนิธิเพื่อสุนัขในซอย (Soi Dog)',
                'folder'  => 'f3',
                'fb'      => 'มูลนิธิเพื่อสุนัขในซอย, ประเทศไทย',
                'fb_url'  => 'https://www.facebook.com/SoiDogInThai',
                'tel'     => '062-2458949',
                'address' => '167/9 หมู่ 4 ซอยไม้ขาว 10 ต.ไม้ขาว อ.ถลาง ภูเก็ต 83110',
                'hours'   => 'โปรดติดต่อล่วงหน้า',
            ],
            [
                'name'    => 'The ARK Chiangmai',
                'folder'  => 'f4',
                'fb'      => 'The ARK Chiangmai',
                'fb_url'  => 'https://www.facebook.com/theark.cm',
                'tel'     => '088-5477393, 094-9919499',
                'address' => '247 หมู่ 8 บ้านวังตาล ต.หลวงเหนือ อ.ดอยสะเก็ด เชียงใหม่ 50220',
                'hours'   => 'โปรดติดต่อล่วงหน้า',
            ],
            [
                'name'    => 'มูลนิธิรักษ์แมว ปันน้ำใจให้แมวจร',
                'folder'  => 'f5',
                'fb'      => 'CatRoomPantip',
                'fb_url'  => 'https://www.facebook.com/catroompantip',
                'tel'     => '099-0452214',
                'address' => "CAT'S EYES HOTEL เลขที่ 130 ซอยรัชดาภิเษก 66 แขวงวงศ์สว่าง เขตบางซื่อ กทม. 10800",
                'hours'   => 'โปรดติดต่อล่วงหน้า',
            ],
            [
                'name'    => 'บ้านนางฟ้าของสัตว์จร',
                'folder'  => 'f6',
                'fb'      => 'บ้านนางฟ้าของสัตว์จร',
                'fb_url'  => 'https://www.facebook.com/CHSAThai',
                'tel'     => '089-099-6000',
                'address' => '85 หมู่ 13 ต.มวกเหล็ก อ.มวกเหล็ก จ.สระบุรี 18180',
                'hours'   => 'โทรสอบถามล่วงหน้า',
            ],
            [
                'name'    => 'บ้านหมา ป้ามณี',
                'folder'  => 'f7',
                'fb'      => 'มะหมาบ้านป้ามณี',
                'fb_url'  => 'https://www.facebook.com/maneesavedog',
                'tel'     => '085-356-9225',
                'address' => '93/6 ม.13 ซอยวัดประยูรกลาง ต.คูคต อ.ลำลูกกา ปทุมธานี 12130',
                'hours'   => '10:00 – 17:00 น.',
            ],
            [
                'name'    => 'บ้านสงเคราะห์สัตว์ป้าเจียม',
                'folder'  => 'f8',
                'fb'      => 'บ้านสงเคราะห์สัตว์ป้าเจียม',
                'fb_url'  => 'https://www.facebook.com/AuntyJeamAnimalFosterHome/',
                'tel'     => '082-770-1522',
                'address' => '38/3 ต.บ้านคลอง อ.เมือง จ.พิษณุโลก',
                'hours'   => 'โทรสอบถามล่วงหน้า',
            ],
            [
                'name'    => 'บ้านกัญญาภัทร — เพื่อหมาแมวที่ถูกทอดทิ้ง',
                'folder'  => 'f9',
                'fb'      => 'บ้านกัญญาภัทร-เพื่อหมาแมวที่ถูกทอดทิ้ง',
                'fb_url'  => 'https://www.facebook.com/profile.php?id=100064850913773',
                'tel'     => '081-359-2826',
                'address' => '75 หมู่ 6 ต.บึงกาสาม อ.หนองเสือ จ.ปทุมธานี',
                'hours'   => 'โทรสอบถามล่วงหน้า',
            ],
            [
                'name'    => 'มูลนิธิสันติสุขเพื่อสุนัขและแมวจรจัด',
                'folder'  => 'f10',
                'fb'      => 'มูลนิธิสันติสุขเพื่อสุนัขและแมวจรจัด',
                'fb_url'  => 'https://www.facebook.com/santisookdogandcat.org',
                'tel'     => '081-6382105, 083-7818439',
                'address' => '114/281 หมู่ 1 หมู่บ้านเวียงดอย ต.ป่าป้อง อ.ดอยสะเก็ด จ.เชียงใหม่ 50220',
                'hours'   => 'โทรสอบถามล่วงหน้า',
            ],
        ];

        $fallback_sets = [
            [
                'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=900&h=500&fit=crop',
                'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=900&h=500&fit=crop',
                'https://images.unsplash.com/photo-1560807707-8cc77767d783?w=900&h=500&fit=crop',
                'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=900&h=500&fit=crop',
            ],
            [
                'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=900&h=500&fit=crop',
                'https://images.unsplash.com/photo-1534361960057-19f4434a5d0f?w=900&h=500&fit=crop',
                'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=900&h=500&fit=crop',
                'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?w=900&h=500&fit=crop',
            ],
        ];

        foreach ($locations as $idx => $loc):
            $num      = $idx + 1;
            $folder   = $loc['folder'];
            $fallback = $fallback_sets[$idx % 2];

            // อ่านชื่อไฟล์จริงจากโฟลเดอร์
            $folderPath = __DIR__ . '/foundation/' . $folder;
            $images = [];
            if (is_dir($folderPath)) {
                $files = scandir($folderPath);
                foreach ($files as $file) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $images[] = 'foundation/' . $folder . '/' . $file;
                    }
                }
            }
            // ถ้าไม่มีรูป ใช้ fallback
            if (empty($images)) {
                $images = $fallback;
            }
            $total = count($images);
        ?>

        <div class="location-card" id="location-<?= $num ?>">

            <!-- Card Header -->
            <div class="location-header">
                <div class="location-header-left">
                    <div class="location-number"><?= $num ?></div>
                    <h2 class="location-name"><?= htmlspecialchars($loc['name']) ?></h2>
                </div>
                <span class="location-badge">รับบริจาค</span>
            </div>

            <!-- Image Gallery -->
            <div class="location-gallery" data-gallery="<?= $num ?>" data-total="<?= $total ?>">
                <div class="gallery-track" id="track-<?= $num ?>">
                    <?php foreach ($images as $imgPath): ?>
                    <div class="gallery-slide">
                        <img
                            src="<?= htmlspecialchars($imgPath) ?>"
                            alt="<?= htmlspecialchars($loc['name']) ?>"
                            loading="lazy"
                        >
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Prev / Next -->
                <button class="gallery-btn gallery-btn-prev" onclick="slideGallery(<?= $num ?>, -1)">
                    <span class="iconify" data-icon="mdi:chevron-left" data-width="26" data-height="26"></span>
                </button>
                <button class="gallery-btn gallery-btn-next" onclick="slideGallery(<?= $num ?>, 1)">
                    <span class="iconify" data-icon="mdi:chevron-right" data-width="26" data-height="26"></span>
                </button>

                <!-- Dots -->
                <div class="gallery-dots" id="dots-<?= $num ?>">
                    <?php for ($d = 0; $d < $total; $d++): ?>
                    <button
                        class="gallery-dot <?= $d === 0 ? 'active' : '' ?>"
                        onclick="goToSlide(<?= $num ?>, <?= $d ?>)"
                    ></button>
                    <?php endfor; ?>
                </div>

                <!-- Counter -->
                <div class="gallery-counter" id="counter-<?= $num ?>">1 / <?= $total ?></div>
            </div>

            <!-- Info -->
            <div class="location-info">
                <div class="info-group">
                    <div class="info-row">
                        <div class="info-icon">
                            <span class="iconify" data-icon="mdi:map-marker" data-width="20" data-height="20"></span>
                        </div>
                        <div class="info-text">
                            <span class="info-label">ที่อยู่</span>
                            <span class="info-value"><?= htmlspecialchars($loc['address']) ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon">
                            <span class="iconify" data-icon="mdi:phone" data-width="20" data-height="20"></span>
                        </div>
                        <div class="info-text">
                            <span class="info-label">โทรศัพท์</span>
                            <span class="info-value"><?= htmlspecialchars($loc['tel']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-row">
                        <div class="info-icon">
                            <span class="iconify" data-icon="mdi:facebook" data-width="20" data-height="20"></span>
                        </div>
                        <div class="info-text">
                            <span class="info-label">Facebook</span>
                            <span class="info-value">
                                <a href="<?= htmlspecialchars($loc['fb_url']) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($loc['fb']) ?>
                                </a>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon">
                            <span class="iconify" data-icon="mdi:clock-outline" data-width="20" data-height="20"></span>
                        </div>
                        <div class="info-text">
                            <span class="info-label">เวลาทำการ</span>
                            <span class="info-value">
                                <span class="hours-badge">
                                    <span class="iconify" data-icon="mdi:clock-check" data-width="14"></span>
                                    <?= htmlspecialchars($loc['hours']) ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="location-actions">
                <a href="<?= htmlspecialchars($loc['fb_url']) ?>" target="_blank" rel="noopener" class="btn-contact btn-contact-fb">
                    <span class="iconify" data-icon="mdi:facebook" data-width="18"></span>
                    ดู Facebook
                </a>
                <a href="tel:<?= preg_replace('/[^0-9]/', '', explode(',', $loc['tel'])[0]) ?>" class="btn-contact btn-contact-tel">
                    <span class="iconify" data-icon="mdi:phone" data-width="18"></span>
                    โทรหา
                </a>
                <a href="https://maps.google.com/?q=<?= urlencode($loc['address']) ?>" target="_blank" rel="noopener" class="btn-contact btn-contact-map">
                    <span class="iconify" data-icon="mdi:map-search" data-width="18"></span>
                    ดูแผนที่
                </a>
            </div>

        </div><!-- end .location-card -->

        <?php endforeach; ?>

    </div><!-- end .locations-wrapper -->

    <div class="container">
    </div>

</main>

<?php include 'footer.php'; ?>

<script src="script.js"></script>
<script>
    // ══ Gallery State ══
    const galleryState = {};

    function initGallery(id) {
        if (!galleryState[id]) {
            const el    = document.querySelector('[data-gallery="' + id + '"]');
            const total = el ? parseInt(el.dataset.total) || 4 : 4;
            galleryState[id] = { current: 0, total: total };
        }
    }

    function updateGallery(id) {
        initGallery(id);
        const state   = galleryState[id];
        const track   = document.getElementById('track-' + id);
        const counter = document.getElementById('counter-' + id);
        const dotsEl  = document.getElementById('dots-' + id);

        if (track)   track.style.transform = `translateX(-${state.current * 100}%)`;
        if (counter) counter.textContent   = `${state.current + 1} / ${state.total}`;
        if (dotsEl) {
            dotsEl.querySelectorAll('.gallery-dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === state.current);
            });
        }
    }

    function slideGallery(id, direction) {
        initGallery(id);
        const state = galleryState[id];
        state.current = (state.current + direction + state.total) % state.total;
        updateGallery(id);
    }

    function goToSlide(id, index) {
        initGallery(id);
        galleryState[id].current = index;
        updateGallery(id);
    }

    // Init all galleries on load
    document.addEventListener('DOMContentLoaded', () => {
        for (let i = 1; i <= 10; i++) initGallery(i);
    });

    // Touch / swipe support
    document.querySelectorAll('[data-gallery]').forEach(el => {
        let startX = 0;
        el.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
        el.addEventListener('touchend',   e => {
            const dx = e.changedTouches[0].clientX - startX;
            const id = parseInt(el.dataset.gallery);
            if (Math.abs(dx) > 50) slideGallery(id, dx < 0 ? 1 : -1);
        });
    });
</script>
</body>
</html>