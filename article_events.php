<?php
session_start();
require_once 'connect.php';   // เชื่อม PDO เหมือนไฟล์อื่นๆ

// ดึงอีเวนต์จาก DB เฉพาะที่ published
$events = [];
if ($pdo) {
    try {
        $events = $pdo->query("SELECT * FROM events WHERE status='published' AND (date_end IS NULL OR date_end >= CURDATE()) ORDER BY date_start ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $events = []; }
}

// helper: แปลงเดือนเป็นภาษาไทย
function thMonth(string $date): string {
    $m = (int)date('n', strtotime($date));
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $y = (int)date('Y', strtotime($date)) + 543 - 2500; // เลขปีสั้น พศ.
    return $thMonths[$m].' '.sprintf('%02d', $y);
}
function thDay(string $ds, ?string $de): string {
    $s = date('j', strtotime($ds));
    if (!$de || $de === $ds) return $s;
    return $s.'–'.date('j', strtotime($de));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — รวมงานอีเวนต์สัตว์เลี้ยง 2025–2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="footer.css">
    <style>

        .events-page {
            max-width: 1000px;
            margin: 0 auto;
            padding: 48px 24px 100px;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            display: flex; align-items: center; gap: 8px;
            font-size: 14px; color: #94a3b8; margin-bottom: 36px;
        }
        .breadcrumb a { color: #64b5f6; text-decoration: none; }
        .breadcrumb a:hover { color: #123451; }
        .breadcrumb-sep { color: #cbd5e1; }
        .breadcrumb-current { color: #1e293b; font-weight: 500; }

        /* ── Hero ── */
        .events-hero {
            border-radius: 28px;
            overflow: hidden;
            position: relative;
            height: 380px;
            margin-bottom: 56px;
            box-shadow: 0 12px 40px rgba(18,52,81,0.18);
        }
        .events-hero::before {
            content: '';
            position: absolute; inset: 0;
            background-image: url('image_account/3.jpg');
            background-size: cover;
            background-position: center 30%;
            filter: brightness(0.5);
        }
        .events-hero-content {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 32px;
        }
        .events-hero-emoji {
            font-size: 52px;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }
        .events-hero-title {
            font-size: 44px;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 3px 16px rgba(0,0,0,0.4);
            margin-bottom: 12px;
            line-height: 1.3;
        }
        .events-hero-sub {
            font-size: 18px;
            font-weight: 400;
            color: rgba(255,255,255,0.85);
        }

        /* ── Intro summary ── */
        .events-intro {
            background: linear-gradient(135deg, #C8E4FE 0%, #e8f5ff 100%);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 56px;
            font-size: 18px;
            font-weight: 400;
            color: #1e293b;
            line-height: 1.9;
            border-left: 5px solid #123451;
        }

        /* ── Section label ── */
        .section-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 700;
            color: #123451;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }
        .section-label::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, #C8E4FE, transparent);
            border-radius: 2px;
        }

        /* ── Calendar Timeline ── */
        .timeline {
            position: relative;
            padding-left: 32px;
            margin-bottom: 64px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0; bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #123451, #64b5f6, #C8E4FE);
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 28px;
        }
        .timeline-dot {
            position: absolute;
            left: -26px;
            top: 18px;
            width: 20px; height: 20px;
            background: #123451;
            border: 3px solid #C8E4FE;
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(18,52,81,0.1);
        }
        .timeline-card {
            background: #fff;
            border-radius: 18px;
            padding: 22px 26px;
            border: 1px solid #e2ecf7;
            box-shadow: 0 3px 14px rgba(18,52,81,0.07);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .timeline-card:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 28px rgba(18,52,81,0.13);
        }
        .timeline-date-block {
            background: #123451;
            color: #fff;
            border-radius: 14px;
            padding: 12px 18px;
            text-align: center;
            flex-shrink: 0;
            min-width: 90px;
        }
        .timeline-month {
            font-size: 13px;
            font-weight: 500;
            color: #C8E4FE;
            display: block;
            margin-bottom: 2px;
        }
        .timeline-day {
            font-size: 22px;
            font-weight: 700;
            line-height: 1;
        }
        .timeline-body {}
        .timeline-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f2236;
            margin-bottom: 6px;
            line-height: 1.4;
        }
        .timeline-venue {
            font-size: 15px;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }
        .timeline-badge {
            display: inline-block;
            background: #eaf4ff;
            color: #123451;
            border: 1px solid #b8d9f5;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .timeline-badge.highlight {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }

        /* ── Petopia Featured Card ── */
        .petopia-card {
            background: linear-gradient(135deg, #1a1235 0%, #3b1a6b 50%, #7b2d8b 100%);
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 64px;
            box-shadow: 0 12px 40px rgba(123,45,139,0.35);
            display: grid;
            grid-template-columns: 1fr 1.1fr;
        }
        .petopia-img {
            overflow: hidden;
        }
        .petopia-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            opacity: 0.9;
        }
        .petopia-body {
            padding: 40px 36px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .petopia-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: #ffd6f6;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
            width: fit-content;
        }
        .petopia-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 6px;
            line-height: 1.3;
        }
        .petopia-theme {
            font-size: 18px;
            font-weight: 500;
            color: #ffc2f0;
            margin-bottom: 20px;
            font-style: italic;
        }
        .petopia-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 24px;
        }
        .petopia-info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: rgba(255,255,255,0.85);
        }
        .petopia-info-row .iconify { color: #ffc2f0; }

        /* ── Activities Grid ── */
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 64px;
        }
        .activity-card {
            background: #fff;
            border: 1px solid #e2ecf7;
            border-radius: 18px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 3px 12px rgba(18,52,81,0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .activity-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 28px rgba(18,52,81,0.12);
        }
        .activity-emoji {
            font-size: 40px;
            display: block;
            margin-bottom: 12px;
        }
        .activity-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f2236;
            margin-bottom: 8px;
        }
        .activity-desc {
            font-size: 14px;
            color: #64748b;
            line-height: 1.7;
        }

        /* ── Note box ── */
        .note-box {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 14px;
            padding: 18px 24px;
            font-size: 15px;
            color: #5d4037;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.7;
        }
        .note-box-icon { font-size: 22px; flex-shrink: 0; }

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
            margin-top: 32px;
        }
        .btn-back:hover { background: #1e4f77; transform: translateY(-2px); }

        @media (max-width: 768px) {
            .events-hero { height: 260px; }
            .events-hero-title { font-size: 28px; }
            .petopia-card { grid-template-columns: 1fr; }
            .petopia-img { height: 220px; }
            .activities-grid { grid-template-columns: 1fr 1fr; }
            .timeline-card { flex-direction: column; gap: 12px; }
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
<div class="events-page">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="home.php">หน้าแรก</a>
        <span class="breadcrumb-sep">›</span>
        <span>บทความและความรู้</span>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">รวมงานอีเวนต์สัตว์เลี้ยง</span>
    </div>

    <!-- Hero -->
    <div class="events-hero">
        <div class="events-hero-content">
            <div class="events-hero-emoji"></div>
            <h1 class="events-hero-title">รวมงานอีเวนต์สัตว์เลี้ยง<br>ปี 2026</h1>
            <p class="events-hero-sub">งานที่คนรักสัตว์ห้ามพลาด! รวมไว้ให้ครบจบในที่เดียว</p>
        </div>
    </div>

    <!-- Intro -->
    <div class="events-intro">
        งานอีเวนต์สัตว์เลี้ยงปี 2026 มาแบบจัดเต็ม! ไม่ว่าจะเป็นงานยักษ์ใหญ่อย่าง <strong>Pet Expo Thailand 2026</strong>, งานคอนเสิร์ตผสมช้อปปิ้ง <strong>Premium Pet Expo</strong>, งานคนรักแมว <strong>Cat Expo Thailand</strong> หรืองานตาม Community Mall ทั่วกรุง เหมาะสำหรับ Pet Parent ที่อยากพาน้องขนฟูไปทำกิจกรรม สังสรรค์ และช้อปของตลอดปี <span class="iconify" data-icon="mdi:shopping"></span><span class="iconify" data-icon="mdi:dog"></span><span class="iconify" data-icon="mdi:cat"></span>
    </div>

    <!-- Calendar -->
    <div class="section-label">
        <span class="iconify" data-icon="mdi:calendar-star" data-width="16"></span>
        ปฏิทินงานอีเวนต์ปี 2569
    </div>

    <div class="timeline">

    <?php if (empty($events)): ?>
        <div style="text-align:center;padding:48px 0;color:#94a3b8;font-size:16px">
            ยังไม่มีอีเวนต์ที่เผยแพร่ในขณะนี้ — กลับมาใหม่เร็วๆ นี้ 🐾
        </div>
    <?php else: ?>
    <?php foreach ($events as $ev): ?>
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-card">
                <div class="timeline-date-block">
                    <span class="timeline-month"><?= htmlspecialchars(thMonth($ev['date_start'])) ?></span>
                    <span class="timeline-day"><?= htmlspecialchars(thDay($ev['date_start'], $ev['date_end'])) ?></span>
                </div>
                <div class="timeline-body">
                    <?php if ($ev['image']): ?>
                    <img src="<?= htmlspecialchars($ev['image']) ?>" alt="<?= htmlspecialchars($ev['title']) ?>" style="width:100%;max-height:160px;object-fit:cover;border-radius:12px;margin-bottom:10px;">
                    <?php endif; ?>
                    <div class="timeline-title"><?= htmlspecialchars($ev['title']) ?></div>
                    <div class="timeline-venue">
                        <span class="iconify" data-icon="mdi:map-marker" data-width="15"></span>
                        <?= htmlspecialchars($ev['location']) ?>
                    </div>
                    <?php if ($ev['tags']): ?>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">
                    <?php foreach (explode(',', $ev['tags']) as $tag): $tag = trim($tag); if (!$tag) continue; ?>
                        <span class="timeline-badge"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($ev['description']): ?>
                    <p style="margin-top:8px; font-size:14px; color:#475569; line-height:1.7"><?= nl2br(htmlspecialchars($ev['description'])) ?></p>
                    <?php endif; ?>
                    <?php if ($ev['link_url']): ?>
                    <a href="<?= htmlspecialchars($ev['link_url']) ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;margin-top:10px;font-size:13px;color:#123451;text-decoration:none;font-weight:500">
                        <span class="iconify" data-icon="mdi:open-in-new" data-width="14"></span>
                        ดูรายละเอียดเพิ่มเติม
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>

    </div>

    <!-- Featured Event (ดึงจาก DB) -->
    <?php
    $featured = null;
    if ($pdo) {
        try {
            $featured = $pdo->query("SELECT * FROM events WHERE status='published' AND featured=1 AND (date_end IS NULL OR date_end >= CURDATE()) ORDER BY date_start ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $featured = null; }
    }
    if ($featured):
        $ds = $featured['date_start'] ? date('d', strtotime($featured['date_start'])) : '';
        $de = $featured['date_end']   ? date('d', strtotime($featured['date_end']))   : '';
        $dayRange = $de && $de !== $ds ? $ds.'–'.$de : $ds;
        $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
        $monthFull = $featured['date_start'] ? $thMonthsFull[(int)date('n', strtotime($featured['date_start']))] : '';
        $yearThai  = $featured['date_start'] ? (int)date('Y', strtotime($featured['date_start'])) + 543 : '';
        $dateDisplay = $dayRange ? $dayRange.' '.$monthFull.' '.$yearThai : '';
        $tags = array_filter(array_map('trim', explode(',', $featured['tags'] ?? '')));
    ?>
    <div class="section-label">
        <span class="iconify" data-icon="mdi:star-shooting" data-width="16"></span>
        งานไฮไลท์
    </div>

    <div class="petopia-card" style="margin-bottom:64px;">
        <div class="petopia-img">
            <?php if ($featured['image']): ?>
                <img src="<?= htmlspecialchars($featured['image']) ?>" alt="<?= htmlspecialchars($featured['title']) ?>">
            <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#1e3a5f;">
                    <span style="font-size:64px">🐾</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="petopia-body">
            <?php if (!empty($tags)): ?>
            <div class="petopia-tag">
                <span class="iconify" data-icon="mdi:sparkles" data-width="14"></span>
                <?= htmlspecialchars($tags[array_key_first($tags)]) ?>
            </div>
            <?php endif; ?>
            <h2 class="petopia-title"><?= htmlspecialchars($featured['title']) ?></h2>
            <?php if ($featured['description']): ?>
            <p class="petopia-theme"><?= htmlspecialchars($featured['description']) ?></p>
            <?php endif; ?>
            <div class="petopia-info">
                <?php if ($dateDisplay): ?>
                <div class="petopia-info-row">
                    <span class="iconify" data-icon="mdi:calendar" data-width="18"></span>
                    <?= htmlspecialchars($dateDisplay) ?>
                </div>
                <?php endif; ?>
                <?php if ($featured['location']): ?>
                <div class="petopia-info-row">
                    <span class="iconify" data-icon="mdi:map-marker" data-width="18"></span>
                    <?= htmlspecialchars($featured['location']) ?>
                </div>
                <?php endif; ?>
                <?php if ($featured['link_url']): ?>
                <div class="petopia-info-row">
                    <span class="iconify" data-icon="mdi:open-in-new" data-width="18"></span>
                    <a href="<?= htmlspecialchars($featured['link_url']) ?>" target="_blank" rel="noopener" style="color:rgba(255,255,255,0.85);text-decoration:none;">เว็บไซต์งาน</a>
                </div>
                <?php endif; ?>
            </div>
            <?php if (count($tags) > 1): ?>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px">
                <?php foreach (array_slice($tags, 1) as $tag): ?>
                <span style="background:rgba(255,255,255,0.15);color:#fff;padding:3px 12px;border-radius:20px;font-size:12px"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Activities -->
    <div class="section-label">
        <span class="iconify" data-icon="mdi:party-popper" data-width="16"></span>
        กิจกรรมแนะนำภายในงาน
    </div>

    <div class="activities-grid" style="margin-bottom:56px;">
        <div class="activity-card">
            <span class="activity-emoji"></span>
            <div class="activity-title">Pet Health Check</div>
            <div class="activity-desc">ตรวจสุขภาพฟรีและบริการฝังไมโครชิปสำหรับสัตว์เลี้ยง</div>
        </div>
        <div class="activity-card">
            <span class="activity-emoji"></span>
            <div class="activity-title">Pet Playground</div>
            <div class="activity-desc">โซนวิ่งเล่นและ Workshop สนุกๆ สำหรับสัตว์เลี้ยงและเจ้าของ</div>
        </div>
        <div class="activity-card">
            <span class="activity-emoji"></span>
            <div class="activity-title">Pet Influencer Meet</div>
            <div class="activity-desc">พบปะเซเลบสัตว์เลี้ยงชื่อดัง ถ่ายรูปและรับลายเซ็นได้เลย!</div>
        </div>
        <div class="activity-card">
            <span class="activity-emoji"></span>
            <div class="activity-title">Shopping Zone</div>
            <div class="activity-desc">ร้านค้าสินค้า อาหาร และเวชภัณฑ์กว่า 100 บูธในที่เดียว</div>
        </div>
        <div class="activity-card">
            <span class="activity-emoji"></span>
            <div class="activity-title">Photo Booth</div>
            <div class="activity-desc">จุดถ่ายรูปสุดน่ารัก เก็บความทรงจำกับน้องขนฟูแสนรัก</div>
        </div>
        <div class="activity-card">
            <span class="activity-emoji"></span>
            <div class="activity-title">Pet Contest</div>
            <div class="activity-desc">การประกวดสัตว์เลี้ยง ชิงรางวัลและของขวัญสุดพิเศษ</div>
        </div>
    </div>

    <!-- Note -->
    <div class="note-box" style="margin-bottom:32px;">
        <div class="note-box-icon"></div>
        <div>
            <strong>หมายเหตุ:</strong> ตารางงานและรายละเอียดอาจมีการเปลี่ยนแปลง กรุณาตรวจสอบข้อมูลล่าสุดผ่านทาง <strong>Facebook</strong> หรือ <strong>เว็บไซต์ของผู้จัดงาน</strong> ก่อนเดินทางอีกครั้ง
        </div>
    </div>

    <a href="home.php" class="btn-back">
        <span class="iconify" data-icon="mdi:arrow-left" data-width="18"></span>
        กลับหน้าแรก
    </a>

</div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>