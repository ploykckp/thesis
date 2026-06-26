<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — วิธีพาสัตว์เลี้ยงเที่ยวอย่างปลอดภัย</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="footer.css">
    <style>
        /* ══ Article Page ══ */
        .article-page {
            max-width: 860px;
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
        .breadcrumb a {
            color: #64b5f6;
            text-decoration: none;
            transition: color 0.2s;
        }
        .breadcrumb a:hover { color: #123451; }
        .breadcrumb-sep { color: #cbd5e1; }
        .breadcrumb-current { color: #1e293b; font-weight: 500; }

        /* ── Hero Banner ── */
        .article-hero {
            border-radius: 24px;
            overflow: hidden;
            position: relative;
            height: 340px;
            margin-bottom: 48px;
            box-shadow: 0 8px 32px rgba(18,52,81,0.15);
        }
        .article-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=1200&h=400&fit=crop');
            background-size: cover;
            background-position: center;
            filter: brightness(0.55);
        }
        .article-hero-content {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 32px;
        }
        .article-hero-tag {
            background: rgba(200,228,254,0.25);
            border: 1px solid rgba(200,228,254,0.5);
            color: #C8E4FE;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
            backdrop-filter: blur(4px);
        }
        .article-hero-title {
            font-size: 36px;
            font-weight: 700;
            color: #fff;
            line-height: 1.4;
            text-shadow: 0 2px 12px rgba(0,0,0,0.4);
            margin-bottom: 12px;
        }
        .article-hero-meta {
            font-size: 14px;
            color: rgba(255,255,255,0.75);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .article-hero-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Summary Card ── */
        .article-summary {
            background: linear-gradient(135deg, #C8E4FE, #e8f4ff);
            border-left: 5px solid #123451;
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 48px;
            font-size: 18px;
            font-weight: 400;
            color: #1e293b;
            line-height: 1.9;
        }

        /* ── Section Block ── */
        .article-section {
            margin-bottom: 48px;
        }
        .article-section-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        .section-icon {
            width: 48px;
            height: 48px;
            background: #123451;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #fff;
        }
        .article-section-title {
            font-size: 26px;
            font-weight: 700;
            color: #123451;
        }

        /* ── Step Cards ── */
        .steps-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .step-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            box-shadow: 0 2px 12px rgba(18,52,81,0.07);
            border: 1px solid #e8f2fc;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .step-card:hover {
            transform: translateX(6px);
            box-shadow: 0 6px 20px rgba(18,52,81,0.12);
        }
        .step-num {
            width: 40px;
            height: 40px;
            background: #123451;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .step-content {}
        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .step-desc {
            font-size: 16px;
            font-weight: 400;
            color: #475569;
            line-height: 1.7;
        }

        /* ── Warning / Info box ── */
        .info-box {
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }
        .info-box.warning {
            background: #fff8e1;
            border: 1px solid #ffe082;
        }
        .info-box.danger {
            background: #fce4ec;
            border: 1px solid #f48fb1;
        }
        .info-box.success {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
        }
        .info-box-icon {
            font-size: 32px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .info-box-text strong {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .info-box-text p {
            font-size: 16px;
            color: #475569;
            line-height: 1.7;
        }

        /* ── Checklist ── */
        .checklist {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .checklist-item {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(18,52,81,0.06);
            border: 1px solid #e8f2fc;
            font-size: 16px;
            font-weight: 500;
            color: #1e293b;
        }
        .checklist-dot {
            width: 11px;
            height: 11px;
            background: #123451;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* ── Airline box ── */
        .airline-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 2px 12px rgba(18,52,81,0.07);
            border: 1px solid #e8f2fc;
        }
        .airline-card p {
            font-size: 17px;
            color: #475569;
            line-height: 1.9;
            margin-bottom: 12px;
        }
        .airline-card p:last-child { margin-bottom: 0; }
        .airline-card strong { color: #123451; }

        /* ── Back button ── */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #123451;
            color: #fff;
            padding: 12px 28px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
            margin-top: 16px;
        }
        .btn-back:hover {
            background: #1e4f77;
            transform: translateY(-2px);
        }

        /* ── Divider ── */
        .article-divider {
            border: none;
            border-top: 2px dashed #d4e7f7;
            margin: 48px 0;
        }

        @media (max-width: 640px) {
            .article-hero { height: 240px; }
            .article-hero-title { font-size: 26px; }
            .checklist { grid-template-columns: 1fr; }
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
<div class="article-page">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="home.php">หน้าแรก</a>
        <span class="breadcrumb-sep">›</span>
        <span>บทความและความรู้</span>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">วิธีพาสัตว์เลี้ยงเที่ยวอย่างปลอดภัย</span>
    </div>

    <!-- Hero -->
    <div class="article-hero">
        <div class="article-hero-content">
            <div class="article-hero-tag">บทความ · ความรู้</div>
            <h1 class="article-hero-title">วิธีพาสัตว์เลี้ยงเที่ยวอย่างปลอดภัย</h1>
            <div class="article-hero-meta">
                <span>
                    <span class="iconify" data-icon="mdi:clock-outline" data-width="15"></span>
                    อ่าน 5 นาที
                </span>
                <span>
                    <span class="iconify" data-icon="mdi:paw" data-width="15"></span>
                    สำหรับเจ้าของสัตว์เลี้ยงทุกคน
                </span>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="article-summary">
        การพาสัตว์เลี้ยงเที่ยวให้ปลอดภัยควรเริ่มจากการ <strong>ตรวจสุขภาพและฉีดวัคซีน</strong> เตรียมเอกสารสำคัญ ป้ายชื่อ-เบอร์โทรศัพท์ งดอาหารก่อนเดินทาง 2–3 ชั่วโมงเพื่อลดอาการเมารถ ใช้อุปกรณ์เซฟตี้ เช่น สายรัดนิรภัยหรือกรง พกอาหาร น้ำ และยาสามัญ พร้อมแวะพักทุก 2–3 ชั่วโมง
    </div>

    <!-- Section 1 -->
    <div class="article-section" id="section-prepare" style="scroll-margin-top: 130px;">
        <div class="article-section-header">
            <div class="section-icon">
                <span class="iconify" data-icon="mdi:clipboard-check" data-width="26"></span>
            </div>
            <h2 class="article-section-title">ขั้นตอนเตรียมตัวก่อนเดินทาง</h2>
        </div>
        <div class="steps-list">
            <div class="step-card">
                <div class="step-num">1</div>
                <div class="step-content">
                    <div class="step-title">ตรวจสุขภาพและเตรียมเอกสาร</div>
                    <div class="step-desc">พาไปหาหมอเพื่อเช็คความพร้อม โดยเฉพาะสัตว์เลี้ยงสูงอายุหรือมีโรคประจำตัว และพกสมุดวัคซีนให้พร้อม</div>
                </div>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <div class="step-content">
                    <div class="step-title">ฝึกให้ชินกับการเดินทาง</div>
                    <div class="step-desc">ฝึกสัตว์เลี้ยงนั่งในรถหรือกรงก่อนวันเดินทางจริง เพื่อลดความเครียดและความวิตกกังวล</div>
                </div>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <div class="step-content">
                    <div class="step-title">ติดป้ายชื่อ / ไมโครชิป</div>
                    <div class="step-desc">ป้องกันการพลัดหลง ป้ายชื่อต้องระบุชื่อเจ้าของและเบอร์โทรศัพท์ที่ติดต่อได้ชัดเจน</div>
                </div>
            </div>
            <div class="step-card">
                <div class="step-num">4</div>
                <div class="step-content">
                    <div class="step-title">งดอาหารก่อนเดินทาง</div>
                    <div class="step-desc">ควรงดอาหารก่อนเดินทาง 2–3 ชั่วโมง เพื่อป้องกันอาการเมารถและอาเจียนระหว่างทาง</div>
                </div>
            </div>
        </div>
    </div>

    <hr class="article-divider">

    <!-- Section 2 -->
    <div class="article-section">
        <div class="article-section-header">
            <div class="section-icon">
                <span class="iconify" data-icon="mdi:car" data-width="26"></span>
            </div>
            <h2 class="article-section-title">ความปลอดภัยระหว่างเดินทาง (Road Trip)</h2>
        </div>

        <div class="info-box warning" style="margin-bottom:16px;">
            <div class="info-box-icon"></div>
            <div class="info-box-text">
                <strong>อุปกรณ์เซฟตี้</strong>
                <p>ใช้สายรัดนิรภัยสำหรับสัตว์เลี้ยงหรือใส่กรงล็อกไว้ที่เบาะหลัง ห้ามให้สัตว์เลี้ยงนั่งเบาะหน้าหรือยื่นหัวออกนอกหน้าต่างเด็ดขาด</p>
            </div>
        </div>

        <div class="info-box success" style="margin-bottom:16px;">
            <div class="info-box-icon"></div>
            <div class="info-box-text">
                <strong>แวะพักระหว่างทาง</strong>
                <p>หยุดพักรถทุก 2–3 ชั่วโมง เพื่อให้สัตว์เลี้ยงขับถ่าย ยืดเส้นยืดสาย และดื่มน้ำอย่างเพียงพอ</p>
            </div>
        </div>

        <div class="info-box danger">
            <div class="info-box-icon"></div>
            <div class="info-box-text">
                <strong>ห้ามทิ้งไว้ในรถเด็ดขาด!</strong>
                <p>ห้ามทิ้งสัตว์เลี้ยงไว้ในรถที่จอดปิดกระจก เพราะความร้อนสะสมในรถสามารถทำให้เสียชีวิตได้ภายในเวลาไม่กี่นาที</p>
            </div>
        </div>
    </div>

    <hr class="article-divider">

    <!-- Section 3 -->
    <div class="article-section">
        <div class="article-section-header">
            <div class="section-icon">
                <span class="iconify" data-icon="mdi:bag-personal" data-width="26"></span>
            </div>
            <h2 class="article-section-title">สิ่งที่ต้องเตรียมพกไป</h2>
        </div>
        <div class="checklist">
            <div class="checklist-item"><div class="checklist-dot"></div>อาหาร น้ำดื่ม และชามพกพา</div>
            <div class="checklist-item"><div class="checklist-dot"></div>สายจูงและปลอกคอ</div>
            <div class="checklist-item"><div class="checklist-dot"></div>ยาประจำตัว / ยาแก้เมารถ / เบตาดีน</div>
            <div class="checklist-item"><div class="checklist-dot"></div>แผ่นรองฉี่ + ถุงเก็บอึ + ทิชชูเปียก</div>
            <div class="checklist-item"><div class="checklist-dot"></div>ของเล่นหรือตุ๊กตาตัวโปรด</div>
            <div class="checklist-item"><div class="checklist-dot"></div>สมุดวัคซีน + เอกสารสัตว์เลี้ยง</div>
        </div>
    </div>

    <hr class="article-divider">

    <!-- Section 4 -->
    <div class="article-section">
        <div class="article-section-header">
            <div class="section-icon">
                <span class="iconify" data-icon="mdi:airplane" data-width="26"></span>
            </div>
            <h2 class="article-section-title">การเตรียมตัวเมื่อขึ้นเครื่องบิน / ขนส่งสาธารณะ</h2>
        </div>
        <div class="airline-card">
            <p>ต้องตรวจสอบ <strong>กฎของแต่ละสายการบินหรือการขนส่ง</strong> อย่างเคร่งครัดเรื่องน้ำหนักและสายพันธุ์ที่อนุญาต</p>
            <p>ต้องใช้ <strong>กรงสำหรับเดินทางที่ได้มาตรฐาน IATA</strong> เท่านั้น เพื่อความปลอดภัยของสัตว์เลี้ยงและผู้โดยสารท่านอื่น</p>
        </div>
    </div>

    <!-- Back button -->
    <a href="home.php" class="btn-back">
        <span class="iconify" data-icon="mdi:arrow-left" data-width="18"></span>
        กลับหน้าแรก
    </a>

</div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>