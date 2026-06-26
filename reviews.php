<?php
// ================================================
//  reviews.php — รีวิวทั้งหมดของสถานที่
// ================================================
session_start();

require_once 'connect.php';

$id = (int)($_GET['place_id'] ?? 0);
if (!$id) { header('Location: Search.php'); exit; }

// ── ดึงข้อมูลสถานที่ ──────────────────────────
$place = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT place_id, place_name, place_image, province, category FROM places WHERE place_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $place = $stmt->fetch();
    } catch (PDOException $e) { $place = null; }
}
if (!$place) { header('Location: Search.php'); exit; }

// ── Filter & Sort params ──────────────────────
$filterStar = (int)($_GET['star'] ?? 0);   // 0 = ทั้งหมด, 1-5 = กรองดาว
$sortBy     = $_GET['sort'] ?? 'newest';   // newest | oldest | highest | lowest

// ── ดึงรีวิวทั้งหมด (approved) ───────────────
$reviews   = [];
$totalCount = 0;
$starCounts = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];

if ($pdo) {
    try {
        // นับทั้งหมดแยกตามดาว
        $cStmt = $pdo->prepare(
            "SELECT rating, COUNT(*) as cnt FROM reviews
             WHERE place_id = ? AND status = 'approved'
             GROUP BY rating"
        );
        $cStmt->execute([$id]);
        foreach ($cStmt->fetchAll() as $row) {
            $starCounts[(int)$row['rating']] = (int)$row['cnt'];
            $totalCount += (int)$row['cnt'];
        }

        // ORDER BY
        $orderClause = match($sortBy) {
            'oldest'  => 'r.created_at ASC',
            'highest' => 'r.rating DESC, r.created_at DESC',
            'lowest'  => 'r.rating ASC, r.created_at DESC',
            default   => 'r.created_at DESC',
        };

        $whereExtra = $filterStar ? 'AND r.rating = :star' : '';
        $sql = "SELECT r.*,
                CONCAT(u.firstname_account, ' ', u.lastname_account) AS username
                FROM reviews r
                LEFT JOIN account_user u ON r.user_id = u.user_id
                WHERE r.place_id = :pid AND r.status = 'approved'
                $whereExtra
                ORDER BY $orderClause";

        $rStmt = $pdo->prepare($sql);
        $rStmt->bindValue(':pid', $id, PDO::PARAM_INT);
        if ($filterStar) $rStmt->bindValue(':star', $filterStar, PDO::PARAM_INT);
        $rStmt->execute();
        $reviews = $rStmt->fetchAll();
    } catch (PDOException $e) { $reviews = []; }
}

// คำนวณ avgRating อย่างถูกต้อง
$avgRating = 0;
if ($totalCount > 0) {
    $weightedSum = 0;
    foreach ($starCounts as $star => $cnt) {
        $weightedSum += $star * $cnt;
    }
    $avgRating = round($weightedSum / $totalCount, 1);
}

$isLoggedIn = isset($_SESSION['user_id']);

function renderStars(float $n, int $size = 20): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $icon  = $i <= $n ? 'material-symbols:star' : 'material-symbols:star-outline';
        $color = $i <= $n ? '#1e293b' : '#cbd5e1';
        $out  .= "<span class=\"iconify\" data-icon=\"$icon\"
                    data-width=\"{$size}\" data-height=\"{$size}\"
                    style=\"color:{$color}\"></span>";
    }
    return $out;
}

function ratingLabel(float $r): string {
    if ($r === 0.0) return 'ยังไม่มีรีวิว';
    if ($r < 1.5)  return 'แย่มาก';
    if ($r < 2.5)  return 'พอใช้ได้';
    if ($r < 3.5)  return 'ปานกลาง';
    if ($r < 4.0)  return 'ดี';
    if ($r < 4.5)  return 'ดีมาก';
    return 'ดีเยี่ยม';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีวิวทั้งหมด — <?= htmlspecialchars($place['place_name']) ?> | Pawlands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="place_detail.css">
    <link rel="stylesheet" href="footer.css">
    <style>
        /* ── Page Layout ── */
        .rv-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 24px 100px;
        }

        /* ── Breadcrumb ── */
        .rv-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .rv-breadcrumb a { color: #64b5f6; text-decoration: none; }
        .rv-breadcrumb a:hover { color: #123451; }
        .rv-breadcrumb-sep { color: #cbd5e1; }

        /* ── Summary Box ── */
        .rv-summary {
            background: linear-gradient(135deg, #123451 0%, #1e4f7a 100%);
            border-radius: 20px;
            padding: 32px 36px;
            display: flex;
            align-items: center;
            gap: 40px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .rv-score-big {
            font-size: 64px;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }
        .rv-score-label {
            font-size: 16px;
            color: rgba(255,255,255,0.75);
            margin-top: 6px;
        }
        .rv-score-total {
            font-size: 13px;
            color: rgba(255,255,255,0.55);
            margin-top: 4px;
        }
        .rv-bars {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 200px;
        }
        .rv-bar-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .rv-bar-label {
            font-size: 13px;
            color: rgba(255,255,255,0.8);
            width: 32px;
            flex-shrink: 0;
        }
        .rv-bar-track {
            flex: 1;
            height: 8px;
            background: rgba(255,255,255,0.15);
            border-radius: 4px;
            overflow: hidden;
        }
        .rv-bar-fill {
            height: 100%;
            background: #C8E4FE;
            border-radius: 4px;
            transition: width 0.4s ease;
        }
        .rv-bar-count {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            width: 24px;
            text-align: right;
        }

        /* ── Controls ── */
        .rv-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .rv-filter-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .rv-pill {
            padding: 6px 16px;
            border-radius: 50px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            font-family: 'Kanit', sans-serif;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .rv-pill:hover { border-color: #123451; color: #123451; }
        .rv-pill.active { background: #123451; color: #fff; border-color: #123451; }

        .rv-sort {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
        }
        .rv-sort select {
            font-family: 'Kanit', sans-serif;
            font-size: 13px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            color: #1e293b;
            cursor: pointer;
            outline: none;
        }

        /* ── Review Count Label ── */
        .rv-count-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 16px;
        }
        .rv-count-label strong { color: #1e293b; }

        /* ── Review Cards Grid ── */
        .rv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 640px) { .rv-grid { grid-template-columns: 1fr; } }

        .rv-card {
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: box-shadow 0.2s;
        }
        .rv-card:hover { box-shadow: 0 4px 16px rgba(18,52,81,0.10); }

        .rv-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }
        .rv-card-user {
            font-size: 14px;
            font-weight: 600;
            color: #123451;
        }
        .rv-card-date {
            font-size: 12px;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .rv-card-stars { display: flex; }
        .rv-card-comment {
            font-size: 14px;
            color: #475569;
            line-height: 1.75;
        }
        .rv-card-imgs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .rv-card-imgs img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .rv-card-imgs img:hover { opacity: 0.85; }
        .rv-more-imgs {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }

        /* ── Empty State ── */
        .rv-empty {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
            grid-column: 1 / -1;
        }
        .rv-empty-icon { font-size: 48px; margin-bottom: 12px; }

        /* ── Write Review Button ── */
        .rv-write-row {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        /* ── Image Lightbox ── */
        #rvLightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        #rvLightbox.open { display: flex; }
        #rvLightbox img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 10px;
            object-fit: contain;
        }
        #rvLightbox .lbClose {
            position: absolute;
            top: 20px;
            right: 24px;
            font-size: 28px;
            color: #fff;
            cursor: pointer;
            line-height: 1;
        }
    </style>
</head>
<body>

<!-- ══ HEADER ══ -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="home.php"><img src="logo.png" alt="Logo" width="136" height="136"></a>
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

<!-- ══ MAIN ══ -->
<main class="main">
<div class="rv-page">

    <!-- Breadcrumb -->
    <div class="rv-breadcrumb">
        <a href="home.php">หน้าแรก</a>
        <span class="rv-breadcrumb-sep">›</span>
        <a href="place_detail.php?id=<?= $id ?>">
            <?= htmlspecialchars($place['place_name']) ?>
        </a>
        <span class="rv-breadcrumb-sep">›</span>
        <span>รีวิวทั้งหมด</span>
    </div>

    <!-- Place name header -->
    <h1 style="font-size:28px;font-weight:700;color:#1e293b;margin-bottom:6px">
        รีวิวทั้งหมด
    </h1>
    <p style="font-size:15px;color:#64748b;margin-bottom:28px">
        <?= htmlspecialchars($place['place_name']) ?>
        <?php if ($place['province']): ?>
        · <?= htmlspecialchars($place['province']) ?>
        <?php endif; ?>
    </p>

    <!-- Summary Box -->
    <div class="rv-summary">
        <div>
            <div class="rv-score-big"><?= number_format($avgRating, 1) ?></div>
            <div style="display:flex;margin-top:6px"><?= renderStars($avgRating, 22) ?></div>
            <div class="rv-score-label"><?= ratingLabel($avgRating) ?></div>
            <div class="rv-score-total"><?= $totalCount ?> รีวิวทั้งหมด</div>
        </div>
        <div class="rv-bars">
            <?php for ($s = 5; $s >= 1; $s--): ?>
            <?php $pct = $totalCount ? round($starCounts[$s] / $totalCount * 100) : 0; ?>
            <div class="rv-bar-row">
                <span class="rv-bar-label"><?= $s ?> ★</span>
                <div class="rv-bar-track">
                    <div class="rv-bar-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="rv-bar-count"><?= $starCounts[$s] ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Controls: filter + sort -->
    <div class="rv-controls">
        <div class="rv-filter-pills">
            <a href="?place_id=<?= $id ?>&sort=<?= $sortBy ?>"
               class="rv-pill<?= !$filterStar ? ' active' : '' ?>">ทั้งหมด (<?= $totalCount ?>)</a>
            <?php for ($s = 5; $s >= 1; $s--): ?>
            <a href="?place_id=<?= $id ?>&star=<?= $s ?>&sort=<?= $sortBy ?>"
               class="rv-pill<?= $filterStar === $s ? ' active' : '' ?>">
                <?= $s ?> ★ (<?= $starCounts[$s] ?>)
            </a>
            <?php endfor; ?>
        </div>
        <div class="rv-sort">
            <span>เรียงโดย</span>
            <select onchange="location.href='?place_id=<?= $id ?><?= $filterStar ? '&star='.$filterStar : '' ?>&sort='+this.value">
                <option value="newest"  <?= $sortBy==='newest'  ? 'selected' : '' ?>>ล่าสุด</option>
                <option value="oldest"  <?= $sortBy==='oldest'  ? 'selected' : '' ?>>เก่าสุด</option>
                <option value="highest" <?= $sortBy==='highest' ? 'selected' : '' ?>>คะแนนสูงสุด</option>
                <option value="lowest"  <?= $sortBy==='lowest'  ? 'selected' : '' ?>>คะแนนต่ำสุด</option>
            </select>
        </div>
    </div>

    <!-- Count label -->
    <div class="rv-count-label">
        แสดง <strong><?= count($reviews) ?></strong> รีวิว
        <?= $filterStar ? "คะแนน {$filterStar} ดาว" : '' ?>
    </div>

    <!-- Reviews Grid -->
    <div class="rv-grid">
        <?php if (empty($reviews)): ?>
        <div class="rv-empty">
            <div class="rv-empty-icon">🐾</div>
            <p style="font-family:'Kanit',sans-serif;font-size:15px">
                ยังไม่มีรีวิว<?= $filterStar ? "คะแนน {$filterStar} ดาว" : '' ?>
            </p>
        </div>
        <?php else: ?>
        <?php foreach ($reviews as $r):
            $imgs = !empty($r['images']) ? json_decode($r['images'], true) : [];
        ?>
        <div class="rv-card">
            <div class="rv-card-top">
                <div>
                    <div class="rv-card-user"><?= htmlspecialchars($r['username'] ?? 'ผู้ใช้งาน') ?></div>
                    <div class="rv-card-stars"><?= renderStars((float)($r['rating'] ?? 0), 16) ?></div>
                </div>
                <span class="rv-card-date"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
            </div>
            <?php if (!empty($r['comment'])): ?>
            <p class="rv-card-comment"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($imgs)): ?>
            <div class="rv-card-imgs">
                <?php foreach (array_slice($imgs, 0, 4) as $img): ?>
                <img src="<?= htmlspecialchars($img) ?>" onclick="openLightbox('<?= htmlspecialchars($img) ?>')">
                <?php endforeach; ?>
                <?php if (count($imgs) > 4): ?>
                <div class="rv-more-imgs">+<?= count($imgs)-4 ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Write Review + Back buttons -->
    <div class="rv-write-row" style="gap:12px;flex-wrap:wrap">
        <a href="place_detail.php?id=<?= $id ?>" class="review-write-btn" style="background:#64748b">
            <span class="iconify" data-icon="mdi:arrow-left" data-width="18"></span>
            กลับหน้าสถานที่
        </a>
        <?php if ($isLoggedIn): ?>
        <a href="place_detail.php?id=<?= $id ?>#reviews" class="review-write-btn">
            <span class="iconify" data-icon="mdi:pencil-outline" data-width="18"></span>
            เขียนรีวิว
        </a>
        <?php else: ?>
        <a href="form-login.php" class="review-write-btn">
            <span class="iconify" data-icon="mdi:pencil-outline" data-width="18"></span>
            เข้าสู่ระบบเพื่อรีวิว
        </a>
        <?php endif; ?>
    </div>

</div>
</main>

<!-- Lightbox -->
<div id="rvLightbox" onclick="closeLightbox()">
    <span class="lbClose">✕</span>
    <img id="rvLightboxImg" src="" alt="">
</div>

<?php include 'footer.php'; ?>

<script>
function openLightbox(src) {
    document.getElementById('rvLightboxImg').src = src;
    document.getElementById('rvLightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('rvLightbox').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>
</body>
</html>