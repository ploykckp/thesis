<?php
session_start();
require_once 'connect.php';

// Fetch published news from DB
$newsList = [];
if ($pdo) {
    try {
        $newsList = $pdo->query("SELECT * FROM news WHERE status='published' ORDER BY created_at DESC")
                        ->fetchAll();
    } catch (PDOException $e) { $newsList = []; }
}

// Fetch page config
$cfg = [];
if ($pdo) {
    try {
        $rows = $pdo->query("SELECT cfg_key, cfg_value FROM news_page_config")->fetchAll();
        foreach ($rows as $r) $cfg[$r['cfg_key']] = $r['cfg_value'];
    } catch (PDOException $e) {}
}
$cfg += [
    'header_tag'              => 'ข่าวและความเคลื่อนไหว',
    'header_title'            => 'ข่าวท่องเที่ยวเชิงสัตว์เลี้ยง (Pet Tourism)',
    'header_desc'             => '',
    'stat1_number'            => '', 'stat1_label' => '',
    'stat2_number'            => '', 'stat2_label' => '',
    'stat3_number'            => '', 'stat3_label' => '',
    'highlight_section_title' => 'ไฮไลท์ข่าวท่องเที่ยว Pet-friendly',
    'highlight_box_title'     => 'สรุปความเคลื่อนไหวสำคัญ',
    'highlight_items'         => '',
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — ข่าวท่องเที่ยวเชิงสัตว์เลี้ยง</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="footer.css">
    <style>

        /* ══ Layout ══ */
        .news-page {
            max-width: 980px;
            margin: 0 auto;
            padding: 48px 24px 100px;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 36px;
        }
        .breadcrumb a { color: #64b5f6; text-decoration: none; }
        .breadcrumb a:hover { color: #123451; }
        .breadcrumb-sep { color: #cbd5e1; }
        .breadcrumb-current { color: #1e293b; font-weight: 500; }

        /* ── Page Header ── */
        .news-header {
            border-bottom: 3px solid #123451;
            padding-bottom: 24px;
            margin-bottom: 48px;
        }
        .news-header-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #123451;
            color: #C8E4FE;
            padding: 4px 14px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .news-header-title {
            font-size: 52px;
            font-weight: 700;
            color: #0f2236;
            line-height: 1.3;
            margin-bottom: 12px;
        }
        .news-header-desc {
            font-size: 20px;
            font-weight: 400;
            color: #475569;
            line-height: 1.9;
            max-width: 780px;
        }

        /* ── Stats Row ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 56px;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid #dbeeff;
            border-radius: 14px;
            padding: 22px 24px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(18,52,81,0.06);
        }
        .stat-number {
            font-size: 40px;
            font-weight: 700;
            color: #123451;
            display: block;
            margin-bottom: 6px;
        }
        .stat-label {
            font-size: 16px;
            font-weight: 400;
            color: #64748b;
        }

        /* ── Section Label ── */
        .section-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #123451;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #dbeeff;
        }

        /* ── Highlight Box ── */
        .highlight-box {
            background: linear-gradient(135deg, #e8f4ff, #f0f8ff);
            border: 1px solid #b8d9f5;
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 56px;
        }
        .highlight-box h3 {
            font-size: 24px;
            font-weight: 700;
            color: #0f2236;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .highlight-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .highlight-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 17px;
            color: #1e293b;
            line-height: 1.8;
        }
        .hl-dot {
            width: 8px;
            height: 8px;
            background: #123451;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 8px;
        }
        .hl-sub {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
            display: block;
        }

        /* ── News Cards ── */
        .news-list {
            display: flex;
            flex-direction: column;
            gap: 48px;
        }

        .news-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2ecf7;
            box-shadow: 0 4px 20px rgba(18,52,81,0.07);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }
        .news-card:hover {
            box-shadow: 0 10px 36px rgba(18,52,81,0.13);
            transform: translateY(-4px);
        }

        /* Card with image on side */
        .news-card-inner {
            display: grid;
            grid-template-columns: 380px 1fr;
        }
        .news-card-inner.reverse {
            grid-template-columns: 1fr 380px;
        }

        .news-card-img {
            overflow: hidden;
            position: relative;
            min-height: 100%;
        }
        .news-card-inner.reverse .news-card-img {
            order: 2;
        }
        .news-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
            opacity: 0.88;
        }
        .news-card-img::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(18,52,81,0.18) 0%, rgba(0,0,0,0.08) 100%);
            pointer-events: none;
        }
        .news-card:hover .news-card-img img {
            transform: scale(1.04);
            opacity: 1;
        }

        .news-card-body {
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .news-card-inner.reverse .news-card-body {
            order: 1;
        }

        .news-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eaf4ff;
            color: #123451;
            border: 1px solid #b8d9f5;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
            width: fit-content;
        }

        .news-card-title {
            font-size: 26px;
            font-weight: 700;
            color: #0f2236;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .news-card-desc {
            font-size: 17px;
            font-weight: 400;
            color: #475569;
            line-height: 1.9;
            margin-bottom: 20px;
        }

        /* Sub highlights inside card */
        .news-card-highlights {
            background: #f8fbff;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 20px;
        }
        .news-card-highlights p {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .news-card-highlights ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .news-card-highlights ul li {
            font-size: 16px;
            color: #1e293b;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.7;
        }
        .news-card-highlights ul li::before {
            content: '›';
            color: #123451;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
            line-height: 1.4;
        }

        .news-card-footer {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-top: 16px;
            border-top: 1px solid #e8f2fc;
        }
        .news-card-source {
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Trend Section ── */
        .trend-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 16px;
        }
        .trend-item {
            background: #fff;
            border: 1px solid #e2ecf7;
            border-radius: 14px;
            padding: 20px 22px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            box-shadow: 0 2px 8px rgba(18,52,81,0.05);
        }
        .trend-icon {
            width: 44px;
            height: 44px;
            background: #eaf4ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #123451;
        }
        .trend-title {
            font-size: 18px;
            font-weight: 600;
            color: #0f2236;
            margin-bottom: 6px;
        }
        .trend-desc {
            font-size: 15px;
            color: #64748b;
            line-height: 1.7;
        }

        /* ── Back button ── */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #123451;
            color: #fff;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
            margin-top: 24px;
        }
        .btn-back:hover {
            background: #1e4f77;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .news-card-inner,
            .news-card-inner.reverse { grid-template-columns: 1fr; }
            .news-card-img { height: 220px; order: 0 !important; }
            .news-card-body { order: 1 !important; padding: 24px 20px; }
            .stats-row { grid-template-columns: 1fr; }
            .trend-grid { grid-template-columns: 1fr; }
            .news-header-title { font-size: 28px; }
        }
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
<div class="news-page">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="home.php">หน้าแรก</a>
        <span class="breadcrumb-sep">›</span>
        <span>บทความและความรู้</span>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">ข่าวท่องเที่ยวเชิงสัตว์เลี้ยง</span>
    </div>

    <!-- Page Header -->
    <div class="news-header">
        <div class="news-header-tag">
            <span class="iconify" data-icon="mdi:newspaper-variant" data-width="14"></span>
            <?= htmlspecialchars($cfg['header_tag']) ?>
        </div>
        <h1 class="news-header-title"><?= htmlspecialchars($cfg['header_title']) ?></h1>
        <?php if (!empty($cfg['header_desc'])): ?>
        <p class="news-header-desc"><?= htmlspecialchars($cfg['header_desc']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <?php if ($cfg['stat1_number'] || $cfg['stat2_number'] || $cfg['stat3_number']): ?>
    <div class="stats-row">
        <?php foreach ([1,2,3] as $si): ?>
        <?php if (!empty($cfg["stat{$si}_number"])): ?>
        <div class="stat-card">
            <span class="stat-number"><?= htmlspecialchars($cfg["stat{$si}_number"]) ?></span>
            <span class="stat-label"><?= htmlspecialchars($cfg["stat{$si}_label"]) ?></span>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Highlights -->
    <?php
    $hlItems = !empty($cfg['highlight_items'])
        ? array_filter(array_map('trim', explode("\n", $cfg['highlight_items'])))
        : [];
    if (!empty($hlItems)):
    ?>
    <div class="section-label">
        <span class="iconify" data-icon="mdi:star-four-points" data-width="14"></span>
        <?= htmlspecialchars($cfg['highlight_section_title']) ?>
    </div>
    <div class="highlight-box" style="margin-bottom: 56px;">
        <h3>
            <span class="iconify" data-icon="mdi:bullhorn" data-width="20" style="color:#123451;"></span>
            <?= htmlspecialchars($cfg['highlight_box_title']) ?>
        </h3>
        <ul class="highlight-list">
            <?php foreach ($hlItems as $item):
                $parts = explode('|', $item, 2);
                $bold  = htmlspecialchars($parts[0]);
                $rest  = htmlspecialchars($parts[1] ?? '');
            ?>
            <li>
                <div class="hl-dot"></div>
                <div>
                    <strong><?= $bold ?></strong><?= $rest ? ' — ' . $rest : '' ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- News Cards -->
    <div class="section-label">
        <span class="iconify" data-icon="mdi:newspaper" data-width="14"></span>
        ข่าวและความเคลื่อนไหว
    </div>

    <div class="news-list">
        <?php if (empty($newsList)): ?>
        <p style="text-align:center;color:#64748b;padding:40px 0">ยังไม่มีข่าว</p>
        <?php else: ?>
        <?php foreach ($newsList as $n):
            $reverse = !empty($n['reverse_layout']) ? ' reverse' : '';
            $hlLines = !empty($n['highlights']) ? array_filter(array_map('trim', explode("\n", $n['highlights']))) : [];
        ?>
        <div class="news-card">
            <div class="news-card-inner<?= $reverse ?>">
                <div class="news-card-img">
                    <?php if (!empty($n['image'])): ?>
                    <?php $_nimg = $n['image']; if (!preg_match('/^https?:\/\//', $_nimg)) { $_nimg = 'https://res.cloudinary.com/damzkmceb/image/upload/' . $_nimg; } ?><img src="<?= htmlspecialchars($_nimg) ?>" alt="<?= htmlspecialchars(stripslashes($n['title'])) ?>">
                    <?php else: ?>
                    <div style="width:100%;height:100%;background:#e8edf5;display:flex;align-items:center;justify-content:center;">
                        <span class="iconify" data-icon="mdi:newspaper-variant" data-width="48" style="color:#94a3b8"></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="news-card-body">
                    <?php if (!empty($n['badge'])): ?>
                    <div class="news-card-badge">
                        <span class="iconify" data-icon="mdi:tag" data-width="12"></span>
                        <?= htmlspecialchars($n['badge']) ?>
                    </div>
                    <?php endif; ?>
                    <h2 class="news-card-title"><?= htmlspecialchars(stripslashes($n['title'])) ?></h2>
                    <?php if (!empty($n['description'])): ?>
                    <p class="news-card-desc"><?= htmlspecialchars(stripslashes($n['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($hlLines)): ?>
                    <div class="news-card-highlights">
                        <?php if (!empty($n['highlights_title'])): ?>
                        <p><?= htmlspecialchars($n['highlights_title']) ?></p>
                        <?php endif; ?>
                        <ul>
                            <?php foreach ($hlLines as $hl): ?>
                            <li><?= htmlspecialchars($hl) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($n['source'])): ?>
                    <div class="news-card-footer">
                        <span class="news-card-source">
                            <span class="iconify" data-icon="mdi:office-building" data-width="14"></span>
                            <?= htmlspecialchars($n['source']) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- Trend Section -->
    <div style="margin-top: 64px;">
        <div class="section-label">
            <span class="iconify" data-icon="mdi:trending-up" data-width="14"></span>
            เทรนด์ที่น่าจับตามอง
        </div>
        <div class="trend-grid">
            <div class="trend-item">
                <div class="trend-icon">
                    <span class="iconify" data-icon="mdi:car" data-width="22"></span>
                </div>
                <div>
                    <div class="trend-title">Road Trip กับสัตว์เลี้ยง</div>
                    <div class="trend-desc">ยอดนิยมในกลุ่ม Gen Y–Z เน้นความคล่องตัวและประสบการณ์ใหม่ที่ไม่ต้องพึ่งทัวร์</div>
                </div>
            </div>
            <div class="trend-item">
                <div class="trend-icon">
                    <span class="iconify" data-icon="mdi:hotel" data-width="22"></span>
                </div>
                <div>
                    <div class="trend-title">Pet Zone ในโรงแรม</div>
                    <div class="trend-desc">โรงแรมปรับตัวเปิด Pet Zone มากขึ้น เช่น GO Hotel และเครือ Centara เพื่อรองรับ Pet Parent</div>
                </div>
            </div>
            <div class="trend-item">
                <div class="trend-icon">
                    <span class="iconify" data-icon="mdi:map-marker-multiple" data-width="22"></span>
                </div>
                <div>
                    <div class="trend-title">Pet-friendly Destination</div>
                    <div class="trend-desc">ไทยมุ่งสร้างมาตรฐาน Pet-friendly Tourism ระดับพรีเมียม รองรับนักท่องเที่ยวทั้งในและต่างประเทศ</div>
                </div>
            </div>
            <div class="trend-item">
                <div class="trend-icon">
                    <span class="iconify" data-icon="mdi:heart" data-width="22"></span>
                </div>
                <div>
                    <div class="trend-title">Pet Parent Lifestyle</div>
                    <div class="trend-desc">คนไทยมองสัตว์เลี้ยงเป็นสมาชิกครอบครัว พร้อมจ่ายเพื่อประสบการณ์ท่องเที่ยวร่วมกัน</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back -->
    <a href="home.php" class="btn-back">
        <span class="iconify" data-icon="mdi:arrow-left" data-width="18"></span>
        กลับหน้าแรก
    </a>

</div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>