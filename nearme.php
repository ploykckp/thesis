<?php
// ================================================
//  nearme.php — ใกล้ฉัน Page (Nearby Places)
// ================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: form-login.php?auth=required');
    exit;
}
require_once 'connect.php';

// Category mapping
$categoryMap = [
    'hotel'    => ['label' => 'โรงแรม',           'db' => 'โรงแรม',           'icon' => 'fa6-solid:hotel'],
    'cafe'     => ['label' => 'คาเฟ่',            'db' => 'คาเฟ่',            'icon' => 'carbon:cafe'],
    'food'     => ['label' => 'ร้านอาหาร',        'db' => 'ร้านอาหาร',        'icon' => 'material-symbols:restaurant'],
    'grooming' => ['label' => 'อาบน้ำ ตัดขน',    'db' => 'อาบน้ำ ตัดขน',    'icon' => 'ion:cut'],
    'vet'      => ['label' => 'โรงพยาบาลสัตว์',   'db' => 'โรงพยาบาลสัตว์',   'icon' => 'mingcute:hospital-fill'],
];

// Active category — default = 'all' (ไม่กรอง)
$activeKey = isset($_GET['cat']) && array_key_exists($_GET['cat'], $categoryMap)
    ? $_GET['cat']
    : 'all';

// Fetch ALL approved places
$allPlaces = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM places WHERE status = 'approved' ORDER BY place_id DESC");
        $allPlaces = $stmt->fetchAll();
    } catch (PDOException $e) {
        $allPlaces = [];
    }
}

// Encode all places as JSON for JavaScript
$placesJson = json_encode(array_map(function($p) {
    return [
        'id'          => $p['place_id'],
        'name'        => $p['place_name'],
        'category'    => $p['category'],
        'address'     => $p['address']          ?? '',
        'province'    => $p['province']          ?? '',
        'lat'         => (float)($p['latitude']  ?? 0),
        'lng'         => (float)($p['longitude'] ?? 0),
        'image'       => $p['place_image']       ?? '',
        'petSize'     => $p['pet_size_allowed']  ?? '',
        'petType'     => $p['pet_type_allowed']  ?? '',
        'description' => $p['description']       ?? '',
    ];
}, $allPlaces), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — ใกล้ฉัน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB3H13UP43DsR6ED2NWaQXK9VaUqSKcCTA&v=beta&libraries=marker"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="nearme.css">
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
                <a href="home.php"     class="nav-link">หน้าแรก</a>
                <a href="plantrip.php" class="nav-link">แผนเที่ยว</a>
                <a href="petinfo.php"  class="nav-link">ข้อมูลสัตว์เลี้ยง</a>
                <a href="nearme.php"   class="nav-link nav-link--active">ใกล้ฉัน</a>
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
<main class="main nearby-main">
    <div class="container">

        <!-- ── CATEGORY PILLS ── -->
        <section class="nearby-categories">
            <?php foreach ($categoryMap as $key => $cat): ?>
            <div class="nearby-cat-card <?= $key === $activeKey ? 'nearby-cat-card--active' : '' ?>"
                 data-cat="<?= $key ?>" onclick="switchCategory('<?= $key ?>')">
                <div class="nearby-cat-icon">
                    <span class="iconify" data-icon="<?= $cat['icon'] ?>" data-width="52" data-height="52"></span>
                </div>
                <span class="nearby-cat-label"><?= $cat['label'] ?><br>ใกล้ฉัน</span>
            </div>
            <?php endforeach; ?>
        </section>

        <!-- ── MAP SECTION ── -->
        <section class="nearby-map-section">
            <div id="locationNotice" class="location-notice hidden">
                <span class="iconify" data-icon="mdi:map-marker-alert" data-width="20"></span>
                กรุณาอนุญาตการเข้าถึงตำแหน่งของคุณเพื่อดูสถานที่ใกล้ฉัน
            </div>
            <div id="nearbyMap" class="nearby-map"></div>
        </section>

        <!-- ── PLACE LIST ── -->
        <section class="nearby-places-section">
            <h2 class="nearby-section-title" id="sectionTitle">สถานที่ทั้งหมดใกล้ฉัน</h2>

            <div class="hotels-grid" id="placesGrid"></div>

            <div id="scrollLoader" class="scroll-loader hidden">
                <div class="loader-spinner"></div>
                <span>กำลังโหลด...</span>
            </div>

            <div class="load-more-container" id="loadMoreWrap">
                <button class="btn-load-more" id="loadMoreBtn">ดูเพิ่มเติม</button>
            </div>
        </section>

    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    const ALL_PLACES = <?= $placesJson ?>;
    const CATEGORY_MAP = <?= json_encode(array_map(fn($c) => $c['label'], $categoryMap)) ?>;
    let ACTIVE_CAT = <?= json_encode($activeKey) ?>;
</script>
<script src="nearme.js"></script>

</body>
</html>