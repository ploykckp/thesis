<?php
// ================================================
//  favorites.php — หน้ารายการโปรดของผู้ใช้
// ================================================
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: form-login.php');
    exit;
}

// เชื่อมต่อ DB โดยตรง
$pdo = null;
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pawland;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) { $pdo = null; }

$user_id   = (int)$_SESSION['user_id'];
$firstname = htmlspecialchars($_SESSION['firstname'] ?? '');
$favorites = [];

if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.place_id, p.place_name, p.category, p.province,
                   p.place_image, p.address, p.pet_type_allowed, p.pet_size_allowed
            FROM favorite f
            JOIN places p ON p.place_id = f.place_id
            WHERE f.user_id = ?
            ORDER BY f.favorite_id DESC
        ");
        $stmt->execute([$user_id]);
        $favorites = $stmt->fetchAll();
    } catch (PDOException $e) {
        $favorites = [];
    }
}

// Handle remove via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_place_id'])) {
    $remove_id = (int)$_POST['remove_place_id'];
    if ($pdo && $remove_id > 0) {
        try {
            $del = $pdo->prepare("DELETE FROM favorite WHERE user_id = ? AND place_id = ?");
            $del->execute([$user_id, $remove_id]);
        } catch (PDOException $e) {}
    }
    header('Location: favorites.php');
    exit;
}

$fallback_imgs = [
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=260&fit=crop',
    'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=400&h=260&fit=crop',
    'https://images.unsplash.com/photo-1568084680786-a84f91d1153c?w=400&h=260&fit=crop',
    'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400&h=260&fit=crop',
    'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&h=260&fit=crop',
];

$cat_icons = [
    'โรงแรม'           => 'fa6-solid:hotel',
    'คาเฟ่'            => 'carbon:cafe',
    'ร้านอาหาร'        => 'material-symbols:restaurant',
    'โรงพยาบาลสัตว์'  => 'mingcute:hospital-fill',
    'อาบน้ำ ตัดขน'     => 'ion:cut',
    'สถานที่ท่องเที่ยว' => 'mdi:map-marker',
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการโปรด — Pawlands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="footer.css">
    <style>
    /* ══ PAGE BODY ══ */
    .fav-main { padding: 48px 0 80px; }

    /* ══ PAGE HERO BAR ══ */
    .fav-hero {
        background: linear-gradient(135deg, #123451 0%, #1e5276 100%);
        border-radius: 24px;
        padding: 36px 40px;
        margin-bottom: 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        color: #fff;
    }
    .fav-hero-left { display: flex; align-items: center; gap: 18px; }
    .fav-hero-icon {
        width: 60px; height: 60px;
        background: rgba(255,255,255,0.15);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .fav-hero-title {
        font-size: 26px; font-weight: 700; margin: 0 0 4px;
    }
    .fav-hero-sub {
        font-size: 14px; opacity: .75; margin: 0;
    }
    .fav-hero-count {
        background: rgba(255,255,255,0.18);
        border: 1.5px solid rgba(255,255,255,0.3);
        border-radius: 16px;
        padding: 10px 24px;
        text-align: center;
        flex-shrink: 0;
    }
    .fav-hero-count-num {
        font-size: 32px; font-weight: 700; display: block; line-height: 1;
    }
    .fav-hero-count-label {
        font-size: 13px; opacity: .8;
    }

    /* ══ GRID ══ */
    .fav-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
    @media (max-width: 1100px) { .fav-grid { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 640px)  { .fav-grid { grid-template-columns: 1fr; } }

    /* ══ CARD ══ */
    .fav-card {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        transition: box-shadow .22s, transform .22s;
        display: flex;
        flex-direction: column;
    }
    .fav-card:hover {
        box-shadow: 0 8px 28px rgba(18,52,81,0.13);
        transform: translateY(-4px);
    }
    .fav-card-img-wrap { position: relative; overflow: hidden; }
    .fav-card-img {
        width: 100%; height: 200px;
        object-fit: cover; display: block;
        transition: transform .3s ease;
    }
    .fav-card:hover .fav-card-img { transform: scale(1.04); }

    .fav-card-cat-pill {
        position: absolute; top: 12px; left: 12px;
        background: rgba(18,52,81,0.82);
        color: #fff;
        font-size: 11px; font-weight: 500;
        padding: 4px 10px;
        border-radius: 20px;
        display: flex; align-items: center; gap: 5px;
        backdrop-filter: blur(4px);
    }

    .fav-card-body {
        padding: 16px 18px 18px;
        flex: 1;
        display: flex; flex-direction: column;
    }
    .fav-card-name {
        font-size: 16px; font-weight: 600; color: #1e293b;
        margin: 0 0 6px;
        text-decoration: none; display: block;
        line-height: 1.3;
    }
    .fav-card-name:hover { color: #123451; }

    .fav-card-location {
        font-size: 13px; color: #64748b;
        display: flex; align-items: center; gap: 4px;
        margin-bottom: 10px;
    }
    .fav-card-pets {
        font-size: 12px; color: #475569;
        background: #f0f7ff;
        border-radius: 8px;
        padding: 6px 10px;
        display: flex; align-items: center; gap: 5px;
        margin-bottom: 14px;
    }
    .fav-spacer { flex: 1; }
    .fav-card-actions {
        display: flex; gap: 10px;
    }
    .btn-view {
        flex: 1;
        background: #123451; color: #fff;
        border: none; border-radius: 10px;
        padding: 10px 0;
        font-family: 'Kanit', sans-serif;
        font-size: 14px; font-weight: 500;
        cursor: pointer; text-align: center;
        text-decoration: none; display: block;
        transition: background .2s;
    }
    .btn-view:hover { background: #1e4f77; color: #fff; }
    .btn-remove {
        background: none;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        font-family: 'Kanit', sans-serif;
        font-size: 13px; color: #94a3b8;
        cursor: pointer;
        transition: all .2s;
        display: flex; align-items: center; gap: 5px;
        white-space: nowrap;
    }
    .btn-remove:hover { border-color: #e53e3e; color: #e53e3e; background: #fff5f5; }

    /* ══ EMPTY STATE ══ */
    .fav-empty {
        text-align: center;
        padding: 80px 20px 100px;
    }
    .fav-empty-icon {
        width: 100px; height: 100px;
        background: linear-gradient(135deg, #fce4ec, #ffd6e0);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 28px;
    }
    .fav-empty h3 {
        font-size: 24px; font-weight: 700; color: #1e293b;
        margin: 0 0 10px;
    }
    .fav-empty p {
        font-size: 15px; color: #64748b;
        margin: 0 0 32px;
    }
    .btn-explore {
        display: inline-flex; align-items: center; gap: 8px;
        background: #123451; color: #fff;
        border-radius: 14px;
        padding: 14px 32px;
        font-family: 'Kanit', sans-serif;
        font-size: 15px; font-weight: 500;
        text-decoration: none;
        transition: background .2s, transform .15s;
    }
    .btn-explore:hover { background: #1e4f77; transform: translateY(-2px); }

    /* ── active nav fix for this page ── */
    .nav-link.active { color: #64b5f6 !important; font-weight: 500; }
    </style>
</head>
<body>

<!-- ══════════ HEADER ══════════ -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="home.php"><img src="logo.png" alt="Logo" width="136" height="136"></a>
            </div>
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

<!-- ══════════ MAIN ══════════ -->
<main class="fav-main">
    <div class="container">

        <!-- Hero bar -->
        <div class="fav-hero">
            <div class="fav-hero-left">
                <div class="fav-hero-icon">
                    <span class="iconify" data-icon="mdi:heart" data-width="32" data-height="32" style="color:#ff6b8a;"></span>
                </div>
                <div>
                    <h1 class="fav-hero-title">รายการโปรดของฉัน</h1>
                    <p class="fav-hero-sub">สถานที่ที่คุณบันทึกไว้ทั้งหมด</p>
                </div>
            </div>
            <div class="fav-hero-count">
                <span class="fav-hero-count-num"><?= count($favorites) ?></span>
                <span class="fav-hero-count-label">สถานที่</span>
            </div>
        </div>

        <?php if (count($favorites) === 0): ?>
        <!-- ── Empty State ── -->
        <div class="fav-empty">
            <div class="fav-empty-icon">
                <span class="iconify" data-icon="mdi:heart-outline" data-width="52" data-height="52" style="color:#e53e3e;opacity:.6;"></span>
            </div>
            <h3>ยังไม่มีรายการโปรด</h3>
            <p>กดไอคอน <span class="iconify" data-icon="mdi:heart"></span> ที่หน้ารายละเอียดสถานที่เพื่อบันทึกรายการโปรด</p>
            <a href="Search.php" class="btn-explore">
                <span class="iconify" data-icon="mdi:magnify" data-width="20"></span>
                ค้นหาสถานที่เลย
            </a>
        </div>

        <?php else: ?>
        <!-- ── Favorites Grid ── -->
        <div class="fav-grid">
            <?php foreach ($favorites as $i => $p):
                $img     = !empty($p['place_image'])
                    ? htmlspecialchars($p['place_image'])
                    : $fallback_imgs[$i % count($fallback_imgs)];
                $fb      = $fallback_imgs[$i % count($fallback_imgs)];
                $catIcon = $cat_icons[$p['category'] ?? ''] ?? 'mdi:map-marker';
            ?>
            <div class="fav-card">
                <!-- Image -->
                <div class="fav-card-img-wrap">
                    <a href="place_detail.php?id=<?= $p['place_id'] ?>">
                        <img class="fav-card-img"
                             src="<?= $img ?>"
                             alt="<?= htmlspecialchars($p['place_name']) ?>"
                             onerror="this.src='<?= $fb ?>'">
                    </a>
                    <?php if (!empty($p['category'])): ?>
                    <span class="fav-card-cat-pill">
                        <span class="iconify" data-icon="<?= $catIcon ?>" data-width="12"></span>
                        <?= htmlspecialchars($p['category']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="fav-card-body">
                    <a href="place_detail.php?id=<?= $p['place_id'] ?>" class="fav-card-name">
                        <?= htmlspecialchars($p['place_name']) ?>
                    </a>

                    <?php if (!empty($p['province'])): ?>
                    <div class="fav-card-location">
                        <span class="iconify" data-icon="mdi:map-marker-outline" data-width="14"></span>
                        <?= htmlspecialchars($p['province']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($p['pet_type_allowed'])): ?>
                    <div class="fav-card-pets">
                        <span class="iconify" data-icon="mdi:paw" data-width="14" style="color:#64b5f6;"></span>
                        รับ: <?= htmlspecialchars($p['pet_type_allowed']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="fav-spacer"></div>

                    <div class="fav-card-actions">
                        <a href="place_detail.php?id=<?= $p['place_id'] ?>" class="btn-view">
                            ดูรายละเอียด
                        </a>
                        <form method="POST" style="margin:0;display:flex;">
                            <input type="hidden" name="remove_place_id" value="<?= $p['place_id'] ?>">
                            <button type="submit" class="btn-remove"
                                    onclick="return confirm('ลบ \"<?= htmlspecialchars($p['place_name'], ENT_QUOTES) ?>\" ออกจากรายการโปรดใช่ไหม?')">
                                <span class="iconify" data-icon="mdi:heart-off-outline" data-width="15"></span>
                                ลบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>