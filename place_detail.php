<?php
// ================================================
//  place_detail.php — รายละเอียดสถานที่
// ================================================
session_start();

// เชื่อมต่อ DB โดยตรง (ไม่ผ่าน connect file เพื่อป้องกัน die() ทำลาย session/output)
require_once 'connect.php';

$id    = (int)($_GET['id'] ?? 0);

// ── Favorites: check if user already saved this place ──
$_is_favorited = false;
if (isset($_SESSION['user_id']) && $id > 0 && $pdo) {
    try {
        $chk = $pdo->prepare("SELECT 1 FROM favorite WHERE user_id=? AND place_id=?");
        $chk->execute([$_SESSION['user_id'], $id]);
        $_is_favorited = (bool)$chk->fetchColumn();
    } catch (PDOException $e) { $_is_favorited = false; }
}
$place = null;

if ($pdo && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM places WHERE place_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $place = $stmt->fetch();
    } catch (PDOException $e) { $place = null; }
}

if (!$place) { header('Location: Search.php'); exit; }

// ── บันทึก view ──────────────────────────────
if ($pdo && $id > 0) {
    try {
        // Auto-create table ถ้ายังไม่มี
        $pdo->exec("CREATE TABLE IF NOT EXISTS `place_views` (
            `view_id` int(11) NOT NULL AUTO_INCREMENT,
            `place_id` int(11) NOT NULL,
            `viewer_ip` varchar(45) DEFAULT NULL,
            `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`view_id`),
            KEY `idx_place_month` (`place_id`, `viewed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // บันทึก 1 view ต่อ session ต่อ place (ป้องกัน spam)
        $sessKey = 'viewed_place_' . $id;
        if (empty($_SESSION[$sessKey])) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $vStmt = $pdo->prepare("INSERT INTO place_views (place_id, viewer_ip, viewed_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
            $vStmt->execute([$id, $ip]);
            $_SESSION[$sessKey] = true;
        }
    } catch (PDOException $e) {}
}

// ── Fetch reviews (approved only) ─────
$reviews = [];
if ($pdo) {
    try {
        $rStmt = $pdo->prepare(
            "SELECT r.*,
             CONCAT(u.firstname_account, ' ', u.lastname_account) AS username
             FROM reviews r
             LEFT JOIN account_user u ON r.user_id = u.user_id
             WHERE r.place_id = :pid AND r.status = 'approved'
             ORDER BY r.created_at DESC LIMIT 5"
        );
        $rStmt->execute([':pid' => $id]);
        $reviews = $rStmt->fetchAll();
    } catch (PDOException $e) { $reviews = []; }
}

// นับรีวิวทั้งหมด (สำหรับปุ่ม "ดูทั้งหมด")
$totalReviewCount = 0;
if ($pdo) {
    try {
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE place_id = ? AND status = 'approved'");
        $cStmt->execute([$id]);
        $totalReviewCount = (int)$cStmt->fetchColumn();
    } catch (PDOException $e) { $totalReviewCount = 0; }
}

$avgRating = count($reviews)
    ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1)
    : 0;
$isLoggedIn = isset($_SESSION['user_id']);

function ratingLabel(float $r): string {
    if ($r === 0.0)  return 'ยังไม่มีรีวิว';
    if ($r < 1.5)   return 'แย่มาก';
    if ($r < 2.5)   return 'พอใช้ได้';
    if ($r < 3.5)   return 'ปานกลาง';
    if ($r < 4.5)   return 'ดี';
    return 'ดีเยี่ยม';
}


// ── Helpers ──────────────────────────────────────
function renderStars(float $n, int $size = 22): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $icon  = $i <= $n ? 'material-symbols:star' : 'material-symbols:star-outline';
        $color = $i <= $n ? '#1e293b' : '#cbd5e1';
        $out  .= "<span class=\"iconify\" data-icon=\"$icon\"
                       data-width=\"$size\" data-height=\"$size\"
                       style=\"color:$color\"></span>";
    }
    return $out;
}

$fallback = '';

// ไม่ต้องใช้ proxy แล้ว เพราะรูปเป็น local file
function proxyImg(?string $url): string {
    return $url ?? '';
}

$mainImg = !empty($place['place_image']) ? htmlspecialchars(proxyImg($place['place_image'])) : $fallback;

// Build gallery from all_images field (comma-separated paths) + place_image as first
$gallery = [$mainImg];
if (!empty($place['all_images'])) {
    $extraImgs = array_filter(array_map('trim', explode(',', $place['all_images'])));
    foreach ($extraImgs as $img) {
        $imgUrl = htmlspecialchars(proxyImg($img));
        // ถ้าเป็น path สัมพัทธ์ให้เติม prefix, ถ้าเป็น URL เต็มใช้ตรงๆ
        if (!preg_match('/^https?:\/\//', $img)) {
            $imgUrl = htmlspecialchars(ltrim($img, '/'));
        }
        if ($imgUrl !== $mainImg) {
            $gallery[] = $imgUrl;
        }
    }
}

// อ่าน amenities จาก DB (comma-separated)
$amenities = !empty($place['amenities'])
    ? array_filter(array_map('trim', explode(',', $place['amenities'])))
    : [];

$petAmenities = !empty($place['pet_amenities'])
    ? array_filter(array_map('trim', explode(',', $place['pet_amenities'])))
    : [];

// Pass lat/lng to JS for map
$lat = (float)($place['latitude']  ?? 13.7563);
$lng = (float)($place['longitude'] ?? 100.5018);
$hasCoords = !empty($place['latitude']) && !empty($place['longitude']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — <?= htmlspecialchars($place['place_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB3H13UP43DsR6ED2NWaQXK9VaUqSKcCTA"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="place_detail.css">
    <link rel="stylesheet" href="footer.css">
</head>
<body>

<!-- ══ HEADER ══ -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo"><img src="logo.png" alt="Logo" width="136" height="136"></div>
            <nav class="nav">
                <a href="home.php"     class="nav-link">หน้าแรก</a>
                <a href="plantrip.php" class="nav-link">แผนเที่ยว</a>
                <a href="petinfo.php"  class="nav-link">ข้อมูลสัตว์เลี้ยง</a>
                <a href="nearme.php"   class="nav-link">ใกล้ฉัน</a>
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

<!-- ══ MAIN ══ -->
<main class="detail-main">
<div class="detail-container">

    <!-- ── DATE PICKER ── -->
    <div class="date-picker-wrapper">
        <div class="date-trigger" id="dateTrigger">
            <span class="iconify" data-icon="mynaui:calendar" data-width="22" data-height="22"></span>
            <span id="dateLabel">เลือกวันที่เข้าพัก</span>
            <span class="iconify date-arrow" data-icon="mdi:chevron-down" data-width="20"></span>
        </div>
        <!-- Calendar popup -->
        <div class="calendar-popup" id="calendarPopup">
            <div class="cal-months-row">
                <!-- Month 1 -->
                <div class="cal-month-block">
                    <div class="cal-nav-row">
                        <button class="cal-nav-btn" id="prevMonth">&#8249;</button>
                        <span class="cal-month-title" id="month1Title"></span>
                        <button class="cal-nav-btn cal-nav-btn--invisible"></button>
                    </div>
                    <div class="cal-day-headers">
                        <?php foreach(['อา','จ','อ','พ','พฤ','ศ','ส'] as $d): ?><span><?=$d?></span><?php endforeach; ?>
                    </div>
                    <div class="cal-grid" id="cal1Grid"></div>
                </div>
                <!-- Month 2 -->
                <div class="cal-month-block">
                    <div class="cal-nav-row">
                        <button class="cal-nav-btn cal-nav-btn--invisible"></button>
                        <span class="cal-month-title" id="month2Title"></span>
                        <button class="cal-nav-btn" id="nextMonth">&#8250;</button>
                    </div>
                    <div class="cal-day-headers">
                        <?php foreach(['อา','จ','อ','พ','พฤ','ศ','ส'] as $d): ?><span><?=$d?></span><?php endforeach; ?>
                    </div>
                    <div class="cal-grid" id="cal2Grid"></div>
                </div>
            </div>
            <div class="cal-footer">
                <span class="cal-hint" id="calHint">เลือกวันเช็คอิน</span>
                <div class="cal-footer-btns">
                    <button class="cal-clear-btn" id="calClear">ล้าง</button>
                    <button class="cal-confirm-btn" id="calConfirm">ยืนยัน</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── PLACE TITLE ── -->
    <div class="detail-title-block">
        <div class="detail-name-row">
            <h1 class="detail-name"><?= htmlspecialchars($place['place_name']) ?></h1>
            <button class="fav-btn <?= $_is_favorited ? 'is-fav' : '' ?>"
                    id="placeDetailFavBtn"
                    data-place-id="<?= $id ?>"
                    data-logged-in="<?= isset($_SESSION['user_id']) ? '1' : '0' ?>"
                    title="<?= $_is_favorited ? 'ลบออกจากรายการโปรด' : 'บันทึกรายการโปรด' ?>">
                <span class="iconify"
                      data-icon="<?= $_is_favorited ? 'mdi:heart' : 'mdi:heart-outline' ?>"
                      id="placeDetailFavIcon"
                      data-width="28" data-height="28"
                      style="<?= $_is_favorited ? 'color:#e53e3e' : '' ?>"></span>
            </button>
        </div>
        <?php if (!empty($place['address'])): ?>
        <p class="detail-address">
            <span class="iconify" data-icon="mdi:map-marker-outline" data-width="16"></span>
            <?= htmlspecialchars($place['address']) ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- ── PHOTO GALLERY ── -->
    <?php $galleryCount = count(array_filter($gallery)); ?>
    <?php if ($galleryCount > 0): ?>
    <div class="gallery-grid <?= $galleryCount === 1 ? 'gallery-single' : '' ?>">
        <div class="gallery-main" onclick="openGallery(0)" style="cursor:pointer;background:#e8f0f7">
            <img src="<?= $mainImg ?>" alt="<?= htmlspecialchars($place['place_name']) ?>"
                 loading="lazy"
                 onerror="this.parentElement.style.background='#d4e7f7';this.remove()"
                 style="opacity:0;transition:opacity 0.3s"
                 onload="this.style.opacity='1'">
        </div>
        <?php if ($galleryCount > 1): ?>
        <?php
        $thumbs    = array_values(array_filter(array_slice($gallery, 1, 4)));
        $remaining = $galleryCount - 5;
        ?>
        <div class="gallery-thumbs">
            <?php foreach ($thumbs as $i => $img):
                $isLast = ($i === count($thumbs) - 1) && $remaining > 0;
            ?>
            <div class="gallery-thumb <?= $isLast ? 'gallery-thumb--more' : '' ?>"
                 onclick="openGallery(<?= $i + 1 ?>)" style="cursor:pointer;background:#e8f0f7">
                <img src="<?= $img ?>" alt="รูป <?= $i+2 ?>"
                     loading="lazy"
                     onerror="this.parentElement.style.background='#d4e7f7';this.remove()"
                     style="opacity:0;transition:opacity 0.3s;<?= $isLast ? 'filter:brightness(0.45)' : '' ?>"
                     onload="this.style.opacity='1'">
                <?php if ($isLast): ?>
                <div class="gallery-more-overlay">
                    <span>+<?= $remaining ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── LIGHTBOX ── -->
    <div id="galleryLightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.92);z-index:9999;align-items:center;justify-content:center;flex-direction:column" onclick="handleLbClick(event)">
        <!-- ปิด -->
        <button onclick="closeLightbox()" style="position:absolute;top:18px;right:22px;background:none;border:none;color:#fff;font-size:30px;cursor:pointer;line-height:1;z-index:2">✕</button>
        <!-- ลูกศรซ้าย -->
        <button onclick="lbPrev(event)" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:28px;width:48px;height:48px;border-radius:50%;cursor:pointer;z-index:2">‹</button>
        <!-- รูปหลัก -->
        <img id="lbImg" src="" style="max-width:90vw;max-height:82vh;object-fit:contain;border-radius:8px;display:block">
        <!-- counter -->
        <div id="lbCounter" style="color:rgba(255,255,255,0.7);font-family:'Kanit',sans-serif;font-size:14px;margin-top:12px"></div>
        <!-- thumbnail strip -->
        <div id="lbStrip" style="display:flex;gap:6px;margin-top:12px;overflow-x:auto;max-width:90vw;padding-bottom:4px"></div>
        <!-- ลูกศรขวา -->
        <button onclick="lbNext(event)" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:28px;width:48px;height:48px;border-radius:50%;cursor:pointer;z-index:2">›</button>
    </div>

    <script>
    const _gallery = <?= json_encode(array_values($gallery)) ?>;
    let _lbIdx = 0;

    function openGallery(idx) {
        _lbIdx = idx;
        renderLb();
        const lb = document.getElementById('galleryLightbox');
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('galleryLightbox').style.display = 'none';
        document.body.style.overflow = '';
    }
    function handleLbClick(e) {
        if (e.target === document.getElementById('galleryLightbox')) closeLightbox();
    }
    function lbPrev(e) { e.stopPropagation(); _lbIdx = (_lbIdx - 1 + _gallery.length) % _gallery.length; renderLb(); }
    function lbNext(e) { e.stopPropagation(); _lbIdx = (_lbIdx + 1) % _gallery.length; renderLb(); }

    function renderLb() {
        document.getElementById('lbImg').src = _gallery[_lbIdx];
        document.getElementById('lbCounter').textContent = (_lbIdx + 1) + ' / ' + _gallery.length;
        // thumbnail strip
        const strip = document.getElementById('lbStrip');
        strip.innerHTML = '';
        _gallery.forEach((src, i) => {
            const img = document.createElement('img');
            img.src = src;
            img.style.cssText = `width:56px;height:56px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid ${i===_lbIdx?'#fff':'transparent'};opacity:${i===_lbIdx?'1':'0.55'};flex-shrink:0;transition:all 0.2s`;
            img.onclick = (e) => { e.stopPropagation(); _lbIdx = i; renderLb(); };
            strip.appendChild(img);
        });
        // scroll thumbnail ที่เลือกให้อยู่ในวิว
        const active = strip.children[_lbIdx];
        if (active) active.scrollIntoView({ inline: 'center', behavior: 'smooth' });
    }

    // keyboard navigation
    document.addEventListener('keydown', e => {
        if (document.getElementById('galleryLightbox').style.display === 'none') return;
        if (e.key === 'ArrowLeft')  { _lbIdx = (_lbIdx - 1 + _gallery.length) % _gallery.length; renderLb(); }
        if (e.key === 'ArrowRight') { _lbIdx = (_lbIdx + 1) % _gallery.length; renderLb(); }
        if (e.key === 'Escape')     closeLightbox();
    });
    </script>

    <!-- ── TAB NAV ── -->
    <div class="detail-tabs">
        <button class="detail-tab active" data-tab="info">รายละเอียดที่พัก</button>
        <button class="detail-tab" data-tab="location">ตำแหน่งที่ตั้ง</button>
        <button class="detail-tab" data-tab="match">? เหมาะกับสัตว์เลี้ยงของคุณหรือไม่</button>
        <button class="detail-tab" data-tab="plan">+ เพิ่มลงในแพลน</button>
    </div>

    <!-- ── INFO TAB ── -->
    <div class="tab-content active" id="tab-info">
        <div class="info-grid">

            <!-- LEFT: Amenities -->
            <div class="amenities-box">
                <h3>บริการ / สิ่งอำนวยความสะดวก</h3>
                <?php if (!empty($amenities)): ?>
                <ul class="amenities-list">
                    <?php foreach ($amenities as $a): ?>
                    <li>
                        <span class="iconify" data-icon="mdi:check-circle-outline" data-width="18"></span>
                        <?= htmlspecialchars($a) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p style="color:#94a3b8;font-size:14px;font-family:'Kanit',sans-serif;margin:8px 0 0;">ไม่มีข้อมูลสิ่งอำนวยความสะดวก</p>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Rating -->
            <div class="rating-box">
                <div class="rating-score-row">
                    <span class="rating-score"><?= number_format($avgRating, 1) ?></span>
                    <div class="rating-label-col">
                        <span class="rating-label-text"><?= ratingLabel($avgRating) ?></span>
                        <div class="rating-stars"><?= renderStars($avgRating) ?></div>
                        <a href="#reviews" class="rating-all-link">อ่านรีวิวทั้งหมด</a>
                    </div>
                </div>
                <!-- Category pills from pet_amenities -->
                <?php if (!empty($petAmenities)): ?>
                <div class="rating-pills">
                    <?php foreach (array_slice($petAmenities, 0, 4) as $pa): ?>
                    <span class="rating-pill"><?= htmlspecialchars($pa) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Pet section -->
                <div class="pet-section">
                    <h4>สำหรับสัตว์เลี้ยง</h4>
                    <?php if (!empty($petAmenities)): ?>
                    <div class="rating-pills">
                        <?php foreach ($petAmenities as $pa): ?>
                        <span class="rating-pill"><?= htmlspecialchars($pa) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="color:#94a3b8;font-size:13px;font-family:'Kanit',sans-serif;margin:4px 0;">ไม่มีข้อมูล</p>
                    <?php endif; ?>
                    <a href="#reviews" class="rating-all-link" style="margin-top:10px;display:inline-block;">อ่านรีวิวเพิ่มเติม</a>
                </div>

                <!-- Pet info row -->
                <?php if (!empty($place['pet_type_allowed']) || !empty($place['pet_size_allowed'])): ?>
                <div class="pet-info-row">
                    <?php if (!empty($place['pet_type_allowed'])): ?>
                    <div class="pet-info-item">
                        <span class="iconify" data-icon="mdi:paw" data-width="18"></span>
                        ประเภทสัตว์: <?= htmlspecialchars($place['pet_type_allowed']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($place['pet_size_allowed'])): ?>
                    <div class="pet-info-item">
                        <span class="iconify" data-icon="mdi:resize" data-width="18"></span>
                        ขนาด: <?= htmlspecialchars($place['pet_size_allowed']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Description -->
        <?php if (!empty($place['description'])): ?>
        <div class="detail-description">
            <h3>รายละเอียด</h3>
            <p><?= nl2br(htmlspecialchars($place['description'])) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── LOCATION TAB ── -->
    <div class="tab-content" id="tab-location">
        <div class="location-block">
            <h3>ตำแหน่งที่ตั้ง</h3>
            <?php if (!empty($place['address'])): ?>
            <p class="location-address">
                <?= htmlspecialchars($place['address']) ?>
                <?= !empty($place['province']) ? ', ' . htmlspecialchars($place['province']) : '' ?>
            </p>
            <?php endif; ?>
            <div id="detailMap" class="detail-map"></div>
            <a href="https://www.google.com/maps?q=<?= $lat ?>,<?= $lng ?>"
               target="_blank" class="map-link-btn">
                <span class="iconify" data-icon="mdi:map-outline" data-width="18"></span>
                คลิกเพื่อดูบนแผนที่
            </a>
        </div>
    </div>

    <!-- ── MATCH TAB ── -->
    <div class="tab-content" id="tab-match">
        <div class="match-block">
            <h3>เหมาะกับสัตว์เลี้ยงของคุณหรือไม่?</h3>
            <div class="match-grid">
                <div class="match-item">
                    <span class="iconify" data-icon="mdi:paw" data-width="32" style="color:#123451"></span>
                    <span>ประเภทสัตว์ที่รับ</span>
                    <strong><?= htmlspecialchars($place['pet_type_allowed'] ?? 'ไม่ระบุ') ?></strong>
                </div>
                <div class="match-item">
                    <span class="iconify" data-icon="mdi:dog" data-width="32" style="color:#123451"></span>
                    <span>ขนาดที่รับ</span>
                    <strong><?= htmlspecialchars($place['pet_size_allowed'] ?? 'ไม่ระบุ') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ── PLAN TAB ── -->
    <div class="tab-content" id="tab-plan">
        <div class="plan-block">
            <h3>เพิ่มลงในแผนการเดินทาง</h3>
            <p>เลือกวันที่และเพิ่มสถานที่นี้ลงในแผนการเดินทางของคุณ</p>
            <button class="plan-add-btn" id="planAddBtn">
                <span class="iconify" data-icon="mdi:plus-circle-outline" data-width="20"></span>
                เพิ่มลงในแพลน
            </button>
        </div>
    </div>

    <!-- ══ REVIEWS SECTION ══ -->
    <section class="reviews-section" id="reviews">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
            <h2 class="reviews-title" style="margin-bottom:0">รีวิวด้านสัตว์เลี้ยง
                <?php if ($totalReviewCount > 0): ?>
                <span style="font-size:18px;font-weight:400;color:#64748b">(<?= $totalReviewCount ?>)</span>
                <?php endif; ?>
            </h2>
            <?php if ($totalReviewCount > 5): ?>
            <a href="reviews.php?place_id=<?= $id ?>"
               style="display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#123451;padding:8px 20px;border-radius:50px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:500;text-decoration:none;transition:background 0.2s"
               onmouseover="this.style.background='#e2ecf7'" onmouseout="this.style.background='#f1f5f9'">
                ดูรีวิวทั้งหมด <?= $totalReviewCount ?> รายการ
                <span class="iconify" data-icon="mdi:arrow-right" data-width="16"></span>
            </a>
            <?php endif; ?>
        </div>

        <?php if (count($reviews) > 0): ?>
        <div class="reviews-carousel-track" id="reviewsTrack">
            <?php foreach ($reviews as $r): ?>
            <?php $imgs = !empty($r['images']) ? json_decode($r['images'], true) : []; ?>
            <div class="review-card" onclick="openReviewDetail(<?= htmlspecialchars(json_encode([
                'username' => $r['username'] ?? 'ผู้ใช้งาน',
                'rating'   => (int)($r['rating'] ?? 0),
                'comment'  => $r['comment'] ?? '',
                'date'     => date('d M Y', strtotime($r['created_at'])),
                'images'   => $imgs,
            ])) ?>)" style="cursor:pointer;">
                <div class="review-card-header">
                    <span class="review-username"><?= htmlspecialchars($r['username'] ?? 'ผู้ใช้งาน') ?></span>
                    <div class="review-stars"><?= renderStars((float)($r['rating'] ?? 4), 16) ?></div>
                </div>
                <p class="review-text" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= nl2br(htmlspecialchars($r['comment'] ?? '')) ?>
                </p>
                <?php if (!empty($imgs)): ?>
                <div style="display:flex;gap:4px;margin-top:8px;flex-wrap:wrap;">
                    <?php foreach (array_slice($imgs, 0, 3) as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
                    <?php endforeach; ?>
                    <?php if (count($imgs) > 3): ?>
                    <div style="width:52px;height:52px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:12px;color:#64748b;">+<?= count($imgs)-3 ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($r['created_at'])): ?>
                <span class="review-date"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- No reviews yet -->
        <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
            <div style="font-size:40px; margin-bottom:10px;"></div>
            <p style="font-family:'Kanit',sans-serif; font-size:15px; margin:0;">ยังไม่มีรีวิวสำหรับสถานที่นี้<br>เป็นคนแรกที่รีวิวได้เลย!</p>
        </div>
        <?php endif; ?>

        <!-- Write review button -->
        <div class="review-write-row">
            <?php if ($isLoggedIn): ?>
            <button class="review-write-btn" onclick="openReviewModal()">
                <span class="iconify" data-icon="mdi:pencil-outline" data-width="18"></span>
                เขียนรีวิว
            </button>
            <?php else: ?>
            <a href="form-login.php" class="review-write-btn">
                <span class="iconify" data-icon="mdi:pencil-outline" data-width="18"></span>
                เขียนรีวิว (กรุณาเข้าสู่ระบบ)
            </a>
            <?php endif; ?>
        </div>
    </section>

</div><!-- /detail-container -->

<!-- ══════════════════════════════════
     REVIEW MODAL POPUP
══════════════════════════════════ -->
<div id="reviewModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; overflow-y:auto; padding:20px 0;">
    <div style="background:#fff; border-radius:16px; padding:28px 24px; width:92%; max-width:480px; position:relative; box-shadow:0 8px 40px rgba(0,0,0,0.18); margin:auto;">
        <button onclick="closeReviewModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;">✕</button>
        <h3 style="font-family:'Kanit',sans-serif; font-size:18px; font-weight:600; color:#1e293b; margin:0 0 4px;">เขียนรีวิว</h3>
        <p style="font-family:'Kanit',sans-serif; font-size:13px; color:#64748b; margin:0 0 16px;" id="reviewPlaceName"></p>
        <!-- Star Rating -->
        <div style="margin-bottom:16px;">
            <label style="font-family:'Kanit',sans-serif; font-size:14px; font-weight:600; color:#374151; display:block; margin-bottom:8px;">ให้คะแนน</label>
            <div id="starContainer" style="display:flex; gap:6px;">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <span class="review-star" data-val="<?= $s ?>"
                    onclick="setRating(<?= $s ?>)"
                    onmouseover="hoverRating(<?= $s ?>)"
                    onmouseout="resetHover()"
                    style="font-size:32px; cursor:pointer; color:#d1d5db; transition:color 0.15s;">★</span>
                <?php endfor; ?>
            </div>
            <span id="ratingText" style="font-family:'Kanit',sans-serif; font-size:13px; color:#64748b; margin-top:4px; display:block;"></span>
        </div>
        <!-- Comment -->
        <div style="margin-bottom:16px;">
            <label style="font-family:'Kanit',sans-serif; font-size:14px; font-weight:600; color:#374151; display:block; margin-bottom:8px;">รีวิวของคุณ</label>
            <textarea id="reviewComment" rows="4" placeholder="เล่าประสบการณ์ของคุณ เช่น บริการ ความเป็นมิตรกับสัตว์เลี้ยง สภาพแวดล้อม..."
                style="width:100%; border:1.5px solid #e2e8f0; border-radius:10px; padding:10px 12px; font-family:'Kanit',sans-serif; font-size:14px; resize:vertical; outline:none; box-sizing:border-box; color:#1e293b;"></textarea>
        </div>
        <!-- Image Upload -->
        <div style="margin-bottom:16px;">
            <label style="font-family:'Kanit',sans-serif; font-size:14px; font-weight:600; color:#374151; display:block; margin-bottom:8px;">รูปภาพประกอบ <span style="font-weight:400;color:#94a3b8;">(ไม่เกิน 5 รูป)</span></label>
            <button type="button" onclick="document.getElementById('reviewImgInput').click()"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px dashed #cbd5e1;border-radius:10px;background:#f8fafc;color:#64748b;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;">
                📷 เพิ่มรูปภาพ
            </button>
            <input type="file" id="reviewImgInput" accept="image/*" multiple style="display:none" onchange="handleReviewImages(event)">
            <div id="reviewImgPreview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
        </div>
        <!-- Message -->
        <div id="reviewMsg" style="display:none; padding:10px 14px; border-radius:8px; font-family:'Kanit',sans-serif; font-size:13px; margin-bottom:14px;"></div>
        <!-- Submit -->
        <button onclick="submitReview()"
            style="width:100%; padding:13px; background:#1e3a5f; color:#fff; border:none; border-radius:10px; font-family:'Kanit',sans-serif; font-size:15px; font-weight:600; cursor:pointer;">
            ยืนยันส่งรีวิว
        </button>
    </div>
</div>

<!-- ══ REVIEW DETAIL MODAL ══ -->
<div id="reviewDetailOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:10000; align-items:center; justify-content:center; padding:20px 0; overflow-y:auto;">
    <div style="background:#fff; border-radius:16px; padding:28px 24px; width:92%; max-width:500px; position:relative; box-shadow:0 8px 40px rgba(0,0,0,0.2); margin:auto;">
        <button onclick="closeReviewDetail()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;">✕</button>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <div style="width:44px;height:44px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:20px;">👤</div>
            <div>
                <div id="rdUsername" style="font-family:'Kanit',sans-serif;font-size:15px;font-weight:600;color:#1e293b;"></div>
                <div id="rdDate" style="font-family:'Kanit',sans-serif;font-size:12px;color:#94a3b8;"></div>
            </div>
        </div>
        <div id="rdStars" style="font-size:26px;margin-bottom:4px;"></div>
        <div id="rdRatingLabel" style="font-family:'Kanit',sans-serif;font-size:13px;color:#64748b;margin-bottom:14px;"></div>
        <p id="rdComment" style="font-family:'Kanit',sans-serif;font-size:14px;color:#374151;line-height:1.75;margin:0 0 16px;white-space:pre-wrap;"></p>
        <div id="rdImages" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
    </div>
</div>

</main>

<?php include 'footer.php'; ?>

<script>
    const PLACE_ID   = <?= json_encode($id) ?>;
    const PLACE_LAT  = <?= $lat ?>;
    const PLACE_LNG  = <?= $lng ?>;
    const HAS_COORDS = <?= $hasCoords ? 'true' : 'false' ?>;
    const PLACE_NAME = <?= json_encode($place['place_name']) ?>;
    const PLACE_ADDR = <?= json_encode($place['address'] ?? '') ?>;
    const PLACE_IMG  = <?= json_encode($mainImg) ?>;

    // ── Review Modal ──
    let selectedRating = 0;
    let reviewImages = [];
    const ratingLabels = ['', 'แย่มาก', 'แย่', 'พอใช้', 'ดี', 'ดีเยี่ยม'];

    function openReviewModal() {
        selectedRating = 0;
        reviewImages = [];
        document.getElementById('reviewComment').value = '';
        document.getElementById('ratingText').textContent = '';
        document.getElementById('reviewMsg').style.display = 'none';
        document.getElementById('reviewPlaceName').textContent = PLACE_NAME;
        document.getElementById('reviewImgPreview').innerHTML = '';
        document.getElementById('reviewImgInput').value = '';
        resetStars();
        const overlay = document.getElementById('reviewModalOverlay');
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeReviewModal() {
        document.getElementById('reviewModalOverlay').style.display = 'none';
        document.body.style.overflow = '';
    }

    function setRating(val) {
        selectedRating = val;
        paintStars(val, '#f59e0b');
        document.getElementById('ratingText').textContent = ratingLabels[val];
    }

    function hoverRating(val) { paintStars(val, '#f59e0b'); }

    function resetHover() {
        paintStars(selectedRating, '#f59e0b');
        if (selectedRating === 0) resetStars();
    }

    function resetStars() {
        document.querySelectorAll('.review-star').forEach(s => s.style.color = '#d1d5db');
    }

    function paintStars(val, color) {
        document.querySelectorAll('.review-star').forEach(s => {
            s.style.color = parseInt(s.dataset.val) <= val ? color : '#d1d5db';
        });
    }

    // ── Image Upload ──
    function handleReviewImages(event) {
        const files = Array.from(event.target.files);
        const remaining = 5 - reviewImages.length;
        const toAdd = files.slice(0, remaining);
        toAdd.forEach(f => reviewImages.push(f));
        renderImgPreview();
        event.target.value = '';
    }

    function renderImgPreview() {
        const preview = document.getElementById('reviewImgPreview');
        preview.innerHTML = '';
        reviewImages.forEach((file, i) => {
            const url = URL.createObjectURL(file);
            const wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;width:70px;height:70px;';
            wrap.innerHTML = `
                <img src="${url}" style="width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
                <button onclick="removeReviewImg(${i})" type="button"
                    style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:#ef4444;color:#fff;border:none;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">✕</button>
            `;
            preview.appendChild(wrap);
        });
        if (reviewImages.length < 5) {
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.onclick = () => document.getElementById('reviewImgInput').click();
            addBtn.style.cssText = 'width:70px;height:70px;border:1.5px dashed #cbd5e1;border-radius:8px;background:#f8fafc;color:#94a3b8;font-size:22px;cursor:pointer;';
            addBtn.textContent = '+';
            preview.appendChild(addBtn);
        }
    }

    function removeReviewImg(idx) {
        reviewImages.splice(idx, 1);
        renderImgPreview();
    }

    async function submitReview() {
        const comment = document.getElementById('reviewComment').value.trim();
        if (selectedRating === 0) { showReviewMsg('กรุณาให้คะแนนก่อน', 'error'); return; }
        if (!comment) { showReviewMsg('กรุณาเขียนรีวิว', 'error'); return; }

        const fd = new FormData();
        fd.append('place_id', PLACE_ID);
        fd.append('rating', selectedRating);
        fd.append('comment', comment);
        reviewImages.forEach(img => fd.append('review_images[]', img));

        try {
            const res  = await fetch('submit_review.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showReviewMsg(data.message, 'success');
                setTimeout(closeReviewModal, 2000);
            } else {
                showReviewMsg(data.message, 'error');
            }
        } catch(e) {
            showReviewMsg('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
        }
    }

    // ── Review Detail Modal ──
    function openReviewDetail(data) {
        document.getElementById('rdUsername').textContent = data.username || 'ผู้ใช้งาน';
        document.getElementById('rdDate').textContent = data.date || '';
        document.getElementById('rdStars').textContent =
            '★'.repeat(data.rating) + '☆'.repeat(5 - data.rating);
        document.getElementById('rdStars').style.color = '#f59e0b';
        document.getElementById('rdRatingLabel').textContent = ratingLabels[data.rating] || '';
        document.getElementById('rdComment').textContent = data.comment || '';

        const imgWrap = document.getElementById('rdImages');
        imgWrap.innerHTML = '';
        if (data.images && data.images.length > 0) {
            data.images.forEach(src => {
                const img = document.createElement('img');
                img.src = src;
                img.onclick = () => window.open(src, '_blank');
                img.style.cssText = 'width:90px;height:90px;object-fit:cover;border-radius:10px;cursor:pointer;border:1px solid #e2e8f0;';
                imgWrap.appendChild(img);
            });
        }

        document.getElementById('reviewDetailOverlay').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeReviewDetail() {
        document.getElementById('reviewDetailOverlay').style.display = 'none';
        document.body.style.overflow = '';
    }

    function showReviewMsg(msg, type) {
        const el = document.getElementById('reviewMsg');
        el.textContent = msg;
        el.style.display = 'block';
        el.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
        el.style.color      = type === 'success' ? '#065f46' : '#991b1b';
        el.style.border     = `1px solid ${type === 'success' ? '#6ee7b7' : '#fca5a5'}`;
    }

    // Close modals on overlay click
    document.getElementById('reviewModalOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeReviewModal();
    });
    document.getElementById('reviewDetailOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeReviewDetail();
    });
</script>
<script src="place_detail.js"></script>
</body>
</html>