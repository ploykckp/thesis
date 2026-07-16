<?php
// ================================================
//  entre_dashboard.php — Entrepreneur Dashboard
// ================================================
session_start();
require_once 'connect.php';

// Auth check
if (!isset($_SESSION['entre_id'])) {
    header('Location: form-login.php');
    exit;
}

$entre_id = (int)$_SESSION['entre_id'];

// Fetch entrepreneur info
$entre = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM account_entre WHERE entre_id = :id LIMIT 1");
        $stmt->execute([':id' => $entre_id]);
        $entre = $stmt->fetch();
    } catch (PDOException $e) { $entre = null; }
}

// Fetch my places
$myPlaces = [];
$totalPlaces = 0;
$countApproved = 0;
$countPending = 0;
$countRejected = 0;

if ($pdo) {
    try {
        // Assuming places table has entre_id column linking to entrepreneur
        $stmt = $pdo->prepare("SELECT * FROM places WHERE entre_id = :eid ORDER BY place_id DESC");
        $stmt->execute([':eid' => $entre_id]);
        $myPlaces = $stmt->fetchAll();
        $totalPlaces = count($myPlaces);
        foreach ($myPlaces as $p) {
            $st = $p['status'] ?? 'pending';
            if ($st === 'approved') $countApproved++;
            elseif ($st === 'rejected') $countRejected++;
            else $countPending++;
        }
    } catch (PDOException $e) { $myPlaces = []; }
}

$hasPlaces = $totalPlaces > 0;

// นับ category (รองรับหลาย category ต่อสถานที่)
$categoryCounts = ['โรงแรม'=>0,'คาเฟ่'=>0,'ร้านอาหาร'=>0,'อาบน้ำ ตัดขน'=>0,'โรงพยาบาลสัตว์'=>0];
foreach ($myPlaces as $p) {
    $cats = array_map('trim', explode(',', $p['category'] ?? ''));
    foreach ($cats as $cat) {
        if (isset($categoryCounts[$cat])) $categoryCounts[$cat]++;
    }
}

// นับรีวิวทั้งหมด (pending+approved+rejected) ของสถานที่ผู้ประกอบการนี้
$totalReviews   = 0;
$pendingReviews = 0;
$approvedReviews = 0;
if ($pdo && count($myPlaces) > 0) {
    try {
        $placeIds = array_column($myPlaces, 'place_id');
        $in = implode(',', array_fill(0, count($placeIds), '?'));
        $rvStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM reviews WHERE place_id IN ($in) GROUP BY status");
        $rvStmt->execute($placeIds);
        foreach ($rvStmt->fetchAll() as $row) {
            $totalReviews += $row['cnt'];
            if ($row['status'] === 'pending')  $pendingReviews  = $row['cnt'];
            if ($row['status'] === 'approved') $approvedReviews = $row['cnt'];
        }
    } catch (PDOException $e) {}
}

// ยอดเข้าชมรายวันในเดือนที่เลือก
$currentYear  = (int)date('Y');
$currentMonth = (int)date('n');
$selectedYear  = (int)($_GET['year']  ?? $currentYear);
$selectedMonth = (int)($_GET['month'] ?? $currentMonth);
if ($selectedMonth < 1)  $selectedMonth = 1;
if ($selectedMonth > 12) $selectedMonth = 12;

$thaiMonthNames = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
$visitLabels = [];
$visitData   = [];
if ($pdo && count($myPlaces) > 0) {
    try {
        // Auto-create table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `place_views` (
            `view_id` int(11) NOT NULL AUTO_INCREMENT,
            `place_id` int(11) NOT NULL,
            `viewer_ip` varchar(45) DEFAULT NULL,
            `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`view_id`),
            KEY `idx_place_month` (`place_id`, `viewed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
        $placeIds = array_column($myPlaces, 'place_id');
        $in = implode(',', array_fill(0, count($placeIds), '?'));
        $params = array_merge($placeIds, [$selectedYear, $selectedMonth]);
        $vStmt = $pdo->prepare("
            SELECT DAY(viewed_at) AS d, COUNT(*) AS cnt
            FROM place_views
            WHERE place_id IN ($in)
              AND YEAR(viewed_at)  = ?
              AND MONTH(viewed_at) = ?
            GROUP BY DAY(viewed_at)
            ORDER BY d ASC
        ");
        $vStmt->execute($params);
        $dayMap = [];
        foreach ($vStmt->fetchAll() as $r) $dayMap[(int)$r['d']] = (int)$r['cnt'];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $visitLabels[] = (string)$d;
            $visitData[]   = $dayMap[$d] ?? 0;
        }
    } catch (PDOException $e) {
        for ($d = 1; $d <= 31; $d++) { $visitLabels[] = (string)$d; $visitData[] = 0; }
    }
}


// Fetch reviews for this entrepreneur's places
$myReviews = [];
if ($pdo && count($myPlaces) > 0) {
    try {
        $placeIds = array_column($myPlaces, 'place_id');
        $in = implode(',', array_fill(0, count($placeIds), '?'));
        $rStmt = $pdo->prepare("
            SELECT r.*,
                   CONCAT(u.firstname_account, ' ', u.lastname_account) AS username,
                   p.place_name
            FROM reviews r
            LEFT JOIN account_user u ON r.user_id = u.user_id
            LEFT JOIN places p ON r.place_id = p.place_id
            WHERE r.place_id IN ($in) AND r.status = 'approved'
            ORDER BY r.created_at DESC
        ");
        $rStmt->execute($placeIds);
        $myReviews = $rStmt->fetchAll();
    } catch (PDOException $e) { $myReviews = []; }
}

// Provinces list
$provinces = [
    'กรุงเทพมหานคร','กระบี่','กาญจนบุรี','กาฬสินธุ์','กำแพงเพชร','ขอนแก่น','จันทบุรี','ฉะเชิงเทรา',
    'ชลบุรี','ชัยนาท','ชัยภูมิ','ชุมพร','เชียงราย','เชียงใหม่','ตรัง','ตราด','ตาก','นครนายก',
    'นครปฐม','นครพนม','นครราชสีมา','นครศรีธรรมราช','นครสวรรค์','นนทบุรี','นราธิวาส','น่าน',
    'บึงกาฬ','บุรีรัมย์','ปทุมธานี','ประจวบคีรีขันธ์','ปราจีนบุรี','ปัตตานี','พระนครศรีอยุธยา',
    'พะเยา','พังงา','พัทลุง','พิจิตร','พิษณุโลก','เพชรบุรี','เพชรบูรณ์','แพร่','ภูเก็ต',
    'มหาสารคาม','มุกดาหาร','แม่ฮ่องสอน','ยโสธร','ยะลา','ร้อยเอ็ด','ระนอง','ระยอง','ราชบุรี',
    'ลพบุรี','ลำปาง','ลำพูน','เลย','ศรีสะเกษ','สกลนคร','สงขลา','สตูล','สมุทรปราการ',
    'สมุทรสงคราม','สมุทรสาคร','สระแก้ว','สระบุรี','สิงห์บุรี','สุโขทัย','สุพรรณบุรี',
    'สุราษฎร์ธานี','สุรินทร์','หนองคาย','หนองบัวลำภู','อ่างทอง','อำนาจเจริญ','อุดรธานี',
    'อุตรดิตถ์','อุทัยธานี','อุบลราชธานี'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Pawlands Entrepreneur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="entre_dashboard.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="logo_w.png" alt="Pawlands" style="height:40px;object-fit:contain;">
    </div>

    <nav class="sidebar-nav">
        <button class="nav-item active" onclick="showPage('dashboard')" id="nav-dashboard">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>
            </span>
            Dashboard
        </button>

        <button class="nav-item nav-item-add" onclick="showPage('add-place'); setStep(1)" id="nav-add">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
            </span>
            เพิ่มสถานที่
        </button>

        <button class="nav-item" onclick="showPage('my-places')" id="nav-myplaces">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="3"/></svg>
            </span>
            สถานที่ของฉัน
        </button>

        <button class="nav-item" onclick="showPage('my-reviews')" id="nav-myreviews">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            รีวิว
        </button>
    </nav>

    <div class="sidebar-logout">
        <a href="logout.php" class="logout-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Log Out
        </a>
    </div>
</aside>

<!-- ══ MAIN CONTENT ══ -->
<main class="main-content">

    <!-- ════════════════════════════════════════
         DASHBOARD PAGE
    ════════════════════════════════════════ -->
    <section class="page-section active" id="page-dashboard">
        <?php if (!$hasPlaces): ?>
        <div class="dashboard-empty">
            <div class="dashboard-empty-icon"></div>
            <h3>ยังไม่มีสถานที่</h3>
            <p>คลิก "เพิ่มสถานที่" เพื่อเริ่มต้นลงทะเบียนสถานที่ของคุณ</p>
        </div>
        <?php else: ?>

        <!-- ── Row 1: stat cards ── -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px;">
            <div style="background:#fff;border-radius:14px;padding:20px 18px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
                <div style="font-size:12px;color:#94a3b8;margin-bottom:6px;">สถานที่ทั้งหมด</div>
                <div style="font-size:32px;font-weight:700;color:#1e3a5f;"><?= $totalPlaces ?></div>
                <div style="font-size:12px;color:#64748b;">สถานที่</div>
            </div>
            <div style="background:#fff;border-radius:14px;padding:20px 18px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
                <div style="font-size:12px;color:#94a3b8;margin-bottom:6px;">ยืนยันแล้ว</div>
                <div style="font-size:32px;font-weight:700;color:#10b981;"><?= $countApproved ?></div>
                <div style="font-size:12px;color:#64748b;">สถานที่</div>
            </div>
            <div style="background:#fff;border-radius:14px;padding:20px 18px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
                <div style="font-size:12px;color:#94a3b8;margin-bottom:6px;">รอการยืนยัน</div>
                <div style="font-size:32px;font-weight:700;color:#f59e0b;"><?= $countPending ?></div>
                <div style="font-size:12px;color:#64748b;">สถานที่</div>
            </div>
            <div style="background:#fff;border-radius:14px;padding:20px 18px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
                <div style="font-size:12px;color:#94a3b8;margin-bottom:6px;">รีวิวทั้งหมด</div>
                <div style="font-size:32px;font-weight:700;color:#6366f1;"><?= $totalReviews ?></div>
                <div style="font-size:12px;color:#64748b;">รีวิว (อนุมัติแล้ว <?= $approvedReviews ?>)</div>
            </div>
        </div>

        <!-- ── Row 2: category breakdown + donut ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">

            <!-- Category table -->
            <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
                <div style="font-size:15px;font-weight:600;color:#1e293b;margin-bottom:16px;">ประเภทสถานที่</div>
                <?php
                $catIcons = ['โรงแรม'=>'','คาเฟ่'=>'','ร้านอาหาร'=>'','อาบน้ำ ตัดขน'=>'','โรงพยาบาลสัตว์'=>''];
                $catColors = ['โรงแรม'=>'#6366f1','คาเฟ่'=>'#f59e0b','ร้านอาหาร'=>'#ef4444','อาบน้ำ ตัดขน'=>'#10b981','โรงพยาบาลสัตว์'=>'#3b82f6'];
                foreach ($categoryCounts as $catName => $cnt):
                    $pct = $totalPlaces > 0 ? round($cnt / $totalPlaces * 100) : 0;
                    $color = $catColors[$catName] ?? '#94a3b8';
                ?>
                <div style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <span style="font-size:13px;color:#374151;"><?= $catIcons[$catName] ?? '' ?> <?= $catName ?></span>
                        <span style="font-size:13px;font-weight:600;color:#1e293b;"><?= $cnt ?> ที่</span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:99px;transition:width .5s;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- View chart with month+year selector -->
            <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                    <div style="font-size:15px;font-weight:600;color:#1e293b;">ยอดการเข้าชมสถานที่</div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <select id="monthSelector" onchange="changeMonthYear()"
                            style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;outline:none;cursor:pointer;">
                            <?php foreach($thaiMonthNames as $mIdx => $mName): if($mIdx===0) continue; ?>
                            <option value="<?= $mIdx ?>" <?= $selectedMonth === $mIdx ? 'selected' : '' ?>><?= $mName ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="yearSelector" onchange="changeMonthYear()"
                            style="padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;outline:none;cursor:pointer;">
                            <?php for ($y = 2026; $y >= 2024; $y--): ?>
                            <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y + 543 ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div style="position:relative;height:160px;">
                    <canvas id="viewChart"></canvas>
                </div>
                <div style="margin-top:10px;font-size:12px;color:#94a3b8;text-align:center;">
                    รวม <?= $thaiMonthNames[$selectedMonth] ?> <?= $selectedYear + 543 ?>:
                    <strong style="color:#1e3a5f;"><?= array_sum($visitData) ?></strong> ครั้ง
                </div>
            </div>
        </div>

        <!-- ── Row 3: review chart ── -->
        <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
            <div style="font-size:15px;font-weight:600;color:#1e293b;margin-bottom:16px;">ยอดรีวิวรายเดือน (<?= $selectedYear + 543 ?>)</div>
            <div style="position:relative;height:200px;">
                <canvas id="reviewChart"></canvas>
            </div>
        </div>

        <?php endif; ?>

        <!-- pass data to JS -->
        <?php
        // คำนวณ review รายเดือนของปีที่เลือก
        $reviewMonthMap = [];
        if ($pdo && count($myPlaces) > 0) {
            try {
                $placeIds2 = array_column($myPlaces, 'place_id');
                $in2 = implode(',', array_fill(0, count($placeIds2), '?'));
                $params2 = array_merge($placeIds2, [$selectedYear]);
                $rvMStmt = $pdo->prepare("
                    SELECT MONTH(created_at) AS mo, COUNT(*) AS cnt
                    FROM reviews
                    WHERE place_id IN ($in2) AND YEAR(created_at) = ?
                    GROUP BY MONTH(created_at)
                ");
                $rvMStmt->execute($params2);
                foreach ($rvMStmt->fetchAll() as $r) $reviewMonthMap[(int)$r['mo']] = (int)$r['cnt'];
            } catch (PDOException $e) {}
        }
        $reviewMonthData = [];
        for ($m = 1; $m <= 12; $m++) $reviewMonthData[] = $reviewMonthMap[$m] ?? 0;
        $thaiMonthsJs = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        ?>
        <script>
        const ENTRE_CATEGORY_DATA = {
            labels: <?= json_encode(array_keys($categoryCounts), JSON_UNESCAPED_UNICODE) ?>,
            data:   <?= json_encode(array_values($categoryCounts)) ?>,
            colors: <?= json_encode(array_values($catColors)) ?>,
            reviewLabels: <?= json_encode($thaiMonthsJs, JSON_UNESCAPED_UNICODE) ?>,
            reviewData:   <?= json_encode($reviewMonthData) ?>,
        };
        const ENTRE_REVIEW_DATA = {
            labels: <?= json_encode($visitLabels, JSON_UNESCAPED_UNICODE) ?>,
            data:   <?= json_encode($visitData) ?>,
        };

        // ── Category checkbox helpers (ต้องอยู่ก่อน checkbox HTML) ──
        const dashDocDefinitions = {
            'โรงแรม':          ['หนังสือรับรองบริษัท / ทะเบียนพาณิชย์','ใบอนุญาตโรงแรม','ใบอนุญาตประกอบกิจการที่พัก'],
            'คาเฟ่':           ['หนังสือรับรองบริษัท / ทะเบียนพาณิชย์','ใบอนุญาตจำหน่ายอาหาร'],
            'ร้านอาหาร':       ['หนังสือรับรองบริษัท / ทะเบียนพาณิชย์','ใบอนุญาตร้านอาหาร','เอกสารสุขาภิบาล (ถ้ามี)'],
            'อาบน้ำ ตัดขน':   ['หนังสือรับรองบริษัท / ทะเบียนพาณิชย์','ใบจดทะเบียนธุรกิจ','ใบรับรองการอบรม Grooming (ถ้ามี)'],
            'โรงพยาบาลสัตว์': ['หนังสือรับรองบริษัท / ทะเบียนพาณิชย์','ใบอนุญาตสถานพยาบาลสัตว์','ใบประกอบวิชาชีพสัตวแพทย์','รายชื่อสัตวแพทย์'],
        };
        window.dashUploadedDocs = {};

        function onCategoryChange() {
            const pills = document.querySelectorAll('input[name="place_category_cb"]');
            const checkedVals = [];
            pills.forEach(cb => {
                const pill = cb.closest('label');
                if (cb.checked) {
                    pill.style.borderColor = '#123451';
                    pill.style.background  = '#eef2f7';
                    pill.style.fontWeight  = '600';
                    checkedVals.push(cb.value);
                } else {
                    pill.style.borderColor = '#e2e8f0';
                    pill.style.background  = '';
                    pill.style.fontWeight  = '';
                }
            });
            document.getElementById('place_category').value = checkedVals.join(',');
            renderDashDocumentSection(checkedVals);
        }

        function renderDashDocumentSection(categoryInput) {
            const section   = document.getElementById('dash_document_section');
            const container = document.getElementById('dash_document_fields');
            const categories = Array.isArray(categoryInput)
                ? categoryInput : (categoryInput ? [categoryInput] : []);

            if (categories.length === 0) {
                section.style.display = 'none';
                window.dashUploadedDocs = {};
                return;
            }

            // รวมเอกสารทุก category ตัดซ้ำออก
            const seen = new Set();
            const allDocs = [];
            categories.forEach(cat => {
                const docs = dashDocDefinitions[cat];
                if (!docs) return;
                docs.forEach(docName => {
                    if (!seen.has(docName)) {
                        seen.add(docName);
                        allDocs.push({ catLabel: cat, docName });
                    }
                });
            });

            if (allDocs.length === 0) { section.style.display = 'none'; return; }

            section.style.display = 'block';
            container.innerHTML   = '';
            window.dashUploadedDocs = {};

            const showCatLabel = categories.length > 1;
            let lastCat = null;

            allDocs.forEach((item, idx) => {
                const key = 'ddoc_' + idx;
                window.dashUploadedDocs[key] = [];

                if (showCatLabel && item.catLabel !== lastCat) {
                    lastCat = item.catLabel;
                    const header = document.createElement('div');
                    header.style.cssText = 'font-size:13px;font-weight:700;color:#1e3a5f;margin:14px 0 6px;padding-bottom:4px;border-bottom:2px solid #e2e8f0;';
                    header.textContent = ' เอกสารสำหรับ' + item.catLabel;
                    container.appendChild(header);
                }

                const div = document.createElement('div');
                div.style.cssText = 'border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:12px;background:#f9fafb;';
                div.innerHTML = `
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">${item.docName}</label>
                    <button type="button" class="upload-btn" onclick="document.getElementById('ddfile_${key}').click()">
                        <span>+</span> เพิ่มรูปภาพ / ไฟล์
                    </button>
                    <input type="file" id="ddfile_${key}" accept="image/*,.pdf" multiple style="display:none"
                        onchange="handleDashDocUpload(event,'${key}')">
                    <div id="ddpreview_${key}" class="uploaded-files" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;"></div>
                `;
                container.appendChild(div);
            });
        }
        </script>
    </section>

    <!-- ════════════════════════════════════════
         ADD PLACE — STEP 1
    ════════════════════════════════════════ -->
    <section class="page-section" id="page-add-place">

        <!-- STEP 1: Basic Info -->
        <div id="step-1" class="form-card" style="display:none">
            <div class="form-group">
                <label class="form-label">ชื่อสถานที่ :</label>
                <input type="text" class="form-input form-input-sm" id="place_name" placeholder="">
                <span class="error-msg" id="err_name">กรุณากรอกชื่อสถานที่</span>
            </div>

            <div class="form-group">
                <label class="form-label">ประเภทสถานที่ : <span style="font-weight:400;font-size:12px;color:#64748b;">(เลือกได้หลายประเภท)</span></label>
                <div id="place_category_wrap" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;">
                    <?php foreach ([
                        'โรงแรม'          => 'fa6-solid:hotel',
                        'คาเฟ่'           => 'carbon:cafe',
                        'ร้านอาหาร'       => 'material-symbols:restaurant',
                        'อาบน้ำ ตัดขน'   => 'ion:cut',
                        'โรงพยาบาลสัตว์' => 'mingcute:hospital-fill',
                    ] as $catVal => $catIcon): ?>
                    <label class="cat-checkbox-pill" style="display:flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;user-select:none;transition:all .15s;"
                           onmouseover="this.style.borderColor='#123451'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                        <input type="checkbox" value="<?= $catVal ?>" name="place_category_cb"
                               style="display:none"
                               onchange="onCategoryChange()">
                        <span class="iconify" data-icon="<?= $catIcon ?>" data-width="16" data-height="16"></span>
                        <?= $catVal ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <span class="error-msg" id="err_category">กรุณาเลือกประเภทสถานที่อย่างน้อย 1 ประเภท</span>
            </div>
            <!-- hidden input สำหรับ document section (ใช้ primary category แรกที่เลือก) -->
            <input type="hidden" id="place_category">

            <div class="form-group">
                <label class="form-label">คำอธิบาย :</label>
                <textarea class="form-textarea" id="place_description" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">เบอร์โทร :</label>
                <input type="tel" class="form-input form-input-sm" id="place_phone" placeholder="">
                <span class="error-msg" id="err_phone">กรุณากรอกเบอร์โทร</span>
            </div>

            <div class="form-group">
                <label class="form-label">เวลาเปิด - ปิด</label>
                <!-- 24 ชั่วโมง toggle -->
                <label style="display:inline-flex;align-items:center;gap:8px;margin-bottom:10px;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;user-select:none;">
                    <input type="checkbox" id="open24Check" onchange="toggle24Hour(this)"
                           style="width:16px;height:16px;accent-color:#123451;cursor:pointer;">
                    <span>เปิด 24 ชั่วโมง</span>
                </label>
                <div class="time-row" id="timeRow">
                    <div class="time-input">
                        <input type="time" id="open_time" value="12:00">
                        <span></span>
                    </div>
                    <span class="time-sep">-</span>
                    <div class="time-input">
                        <input type="time" id="close_time" value="12:00">
                        <span></span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ใบยืนยันการเป็นผู้ประกอบการ</label>
                <button class="upload-btn" onclick="document.getElementById('license_file').click()">
                    <span>+</span> เพิ่มรูปภาพ
                </button>
                <input type="file" id="license_file" accept="image/*,.pdf" style="display:none" onchange="handleLicenseUpload(event)">
                <div class="uploaded-files" id="license-preview"></div>
                <span class="error-msg" id="err_license">กรุณาอัปโหลดใบยืนยัน</span>
            </div>

            <!-- Dynamic document uploads based on place_category -->
            <div id="dash_document_section" style="display:none;">
                <div class="form-label" style="font-weight:600; margin-bottom:10px; color:#374151;">เอกสารยืนยันประเภทสถานที่</div>
                <div id="dash_document_fields"></div>
            </div>

            <div class="form-nav">
                <span></span>
                <button class="btn-next" onclick="validateStep1()">ถัดไป →</button>
            </div>
        </div>

        <!-- STEP 2: Location -->
        <div id="step-2" class="form-card" style="display:none">
            <div class="form-group">
                <label class="form-label">ที่อยู่ :</label>
                <input type="text" class="form-input" id="place_address" placeholder="">
                <span class="error-msg" id="err_address">กรุณากรอกที่อยู่</span>
            </div>

            <div class="form-group">
                <label class="form-label">จังหวัด :</label>
                <select class="province-select" id="place_province">
                    <option value="">เลือกจังหวัด</option>
                    <?php foreach ($provinces as $pv): ?>
                    <option value="<?= htmlspecialchars($pv) ?>"><?= htmlspecialchars($pv) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="error-msg" id="err_province">กรุณาเลือกจังหวัด</span>
            </div>

            <div class="form-group">
                <div class="map-search-row">
                    <input type="text" class="map-search-input" id="map_search" placeholder="ค้นหาหรือพิมพ์ชื่อสถานที่...">
                </div>

                <label class="form-label">ปักหมุดแผนที่</label>
                <div class="map-container" id="addMap"></div>

                <div class="lat-lng-row">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">latitude</label>
                        <input type="text" class="form-input" id="place_lat" placeholder="" readonly>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">longitude</label>
                        <input type="text" class="form-input" id="place_lng" placeholder="" readonly>
                    </div>
                </div>
            </div>

            <div class="form-nav">
                <button class="btn-back" onclick="setStep(1)">← ย้อนกลับ</button>
                <button class="btn-next" onclick="validateStep2()">ถัดไป →</button>
            </div>
        </div>

        <!-- STEP 3: Pet Conditions -->
        <div id="step-3" class="form-card" style="display:none">
            <div class="section-title" style="font-size:20px;margin-bottom:24px">เงื่อนไขเกี่ยวกับสัตว์เลี้ยง</div>

            <div class="form-group">
                <label class="form-label" style="font-size:16px;font-weight:600">รับสัตว์เลี้ยงหรือไม่</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="pet_allowed" value="yes" id="pet_yes" onchange="togglePetSection()"> ใช่
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="pet_allowed" value="no" id="pet_no" onchange="togglePetSection()"> ไม่ใช่
                    </label>
                </div>
            </div>

            <div id="pet-details-section">
                <div class="form-group">
                    <label class="form-label" style="font-weight:600">ประเภทสัตว์เลี้ยงที่รับ</label>
                    <select class="form-select" id="pet_type" onchange="toggleExoticInput(this.value)">
                        <option value="">เลือกประเภทสัตว์ที่รับ</option>
                        <option value="สุนัข (หมา)">สุนัข (หมา)</option>
                        <option value="แมว">แมว</option>
                        <option value="นก">นก</option>
                        <option value="exotic pets ทุกประเภท">exotic pets ทุกประเภท</option>
                        <option value="exotic pets บางประเภท">exotic pets บางประเภท (ระบุเพิ่มเติม)</option>
                        <option value="สุนัข (หมา) และ แมว">สุนัข (หมา) และ แมว</option>
                        <option value="รับทุกประเภทที่กำหนดมา">รับทุกประเภทที่กำหนดมา</option>
                    </select>
                    <div id="exotic_custom_wrap" style="display:none; margin-top:8px;">
                        <input type="text" class="form-input form-input-sm" id="exotic_custom_input" placeholder="ระบุประเภท exotic pets ที่รับ">
                    </div>
                </div>

                <div class="section-title" style="font-size:18px;margin-bottom:14px">ขนาดที่รับ</div>
                <div class="checkbox-list">
                    <label class="checkbox-label">
                        <input type="checkbox" name="pet_size" value="รับเฉพาะขนาดเล็ก (≤ 10 กิโลกรัม)">
                        รับเฉพาะขนาดเล็ก (≤ 10 กิโลกรัม)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="pet_size" value="รับเฉพาะขนาดเล็ก-กลาง (≤ 25 กิโลกรัม)">
                        รับเฉพาะขนาดเล็ก–กลาง (≤ 25 กิโลกรัม)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="pet_size" value="รับเฉพาะขนาดใหญ่">
                        รับเฉพาะขนาดใหญ่
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="pet_size" value="รับทุกขนาด">
                        รับทุกขนาด
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="pet_size" value="custom" id="size_custom_check" onchange="toggleCustomWeight()">
                        <span class="weight-input">
                            รับสูงสุดไม่เกิน
                            <input type="number" id="custom_weight" placeholder="__" min="1" max="200" style="display:none">
                            กิโลกรัม
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size:15px;font-weight:600">มีค่าใช้จ่ายเพิ่มเติมหรือไม่ ? (ถ้ามีโปรดระบุอย่างละเอียด)</label>
                <input type="text" class="form-input" id="extra_cost" placeholder="ระบุค่าใช้จ่ายเพิ่มเติม (ถ้ามี)">
            </div>

            <div class="form-nav">
                <button class="btn-back" onclick="setStep(2)">← ย้อนกลับ</button>
                <button class="btn-next" onclick="validateStep3()">ถัดไป →</button>
            </div>
        </div>

        <!-- STEP 4: Images & Amenities -->
        <div id="step-4" class="form-card" style="display:none">

            <div class="amenity-section">
                <div class="section-title">รูปสถานที่</div>
                <button class="upload-btn" onclick="document.getElementById('place_images').click()">
                    + เพิ่มรูปภาพ
                </button>
                <input type="file" id="place_images" accept="image/*" multiple style="display:none" onchange="handlePlaceImages(event)">
                <div class="image-preview-grid" id="place-images-preview"></div>
            </div>

            <div class="amenity-section">
                <div class="section-title">บริการและสิ่งอำนวยความสะดวก</div>
                <div class="amenity-tags" id="general-amenities">
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">Wi-Fi ฟรี</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">รับฝากสัมภาระ</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">ห้องพักสัตว์เลี้ยง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">แผนกต้อนรับ 24 ชั่วโมง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">ที่จอดรถฟรี</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">อยู่ใจกลางเมือง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">ร้านอาหารในบริเวณ</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'general')">สระว่ายน้ำ</span>
                </div>
            </div>

            <div class="amenity-section">
                <div class="section-title">บริการและสิ่งอำนวยความสะดวกสำหรับสัตว์เลี้ยง</div>
                <div class="amenity-tags" id="pet-amenities">
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'pet')">สนามวิ่งเล่นสำหรับสัตว์เลี้ยง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'pet')">มีอาหารสำหรับสัตว์เลี้ยง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'pet')">สระว่ายน้ำสำหรับสัตว์เลี้ยง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'pet')">ถาดอาหารสำหรับสัตว์เลี้ยง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'pet')">ไดร์เป่าขนสำหรับสัตว์เลี้ยง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'pet')">ที่นอนสำหรับสัตว์เลี้ยง</span>
                    <span class="amenity-tag" onclick="toggleAmenity(this, 'pet')">บริการอาบน้ำสัตว์เลี้ยง</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">กฎสำหรับสัตว์เลี้ยง (ถ้ามี)</label>
                <textarea class="form-textarea" id="pet_rules" rows="3" placeholder="ระบุกฎระเบียบสำหรับสัตว์เลี้ยง..."></textarea>
            </div>

            <div class="form-nav">
                <button class="btn-back" onclick="setStep(3)">← ย้อนกลับ</button>
                <button class="btn-preview" onclick="showPreview()">พรีวิว →</button>
            </div>
        </div>

        <!-- STEP PREVIEW -->
        <div id="step-preview" class="preview-page" style="display:none">
            <h2 class="preview-header">พรีวิวสถานที่</h2>

            <div class="preview-name-card">
                <div>
                    <div class="preview-place-name" id="prev-name"></div>
                    <div class="preview-address" id="prev-address"></div>
                </div>
                <span style="font-size:24px;color:#94a3b8;cursor:pointer">♡</span>
            </div>

            <!-- Gallery -->
            <div class="preview-gallery" id="prev-gallery">
                <div class="preview-gallery-main" id="prev-main-img">
                    <div class="preview-gallery-placeholder">ไม่มีรูปภาพ</div>
                </div>
                <div class="preview-gallery-thumb" id="prev-thumb-1">
                    <div class="preview-gallery-placeholder"></div>
                </div>
                <div class="preview-gallery-thumb" id="prev-thumb-2">
                    <div class="preview-gallery-placeholder"></div>
                </div>
                <div class="preview-gallery-thumb" id="prev-thumb-3">
                    <div class="preview-gallery-placeholder"></div>
                </div>
                <div class="preview-gallery-thumb" id="prev-thumb-4">
                    <div class="preview-gallery-placeholder"></div>
                </div>
            </div>
            <div class="preview-see-all">ดูรูปทั้งหมด</div>

            <!-- Tabs -->
            <div class="preview-tabs">
                <button class="preview-tab active" onclick="switchPreviewTab('info', this)">รายละเอียดที่พัก</button>
                <button class="preview-tab" onclick="switchPreviewTab('location', this)">ตำแหน่งที่ตั้ง</button>
            </div>

            <!-- Info Tab -->
            <div id="prev-tab-info">
                <div class="preview-info-grid">
                    <div class="preview-amenities-box">
                        <h4>บริการ / สิ่งอำนวยความสะดวก</h4>
                        <div id="prev-amenities-list"></div>
                    </div>
                    <div>
                        <div class="preview-rating-box" style="margin-bottom:16px">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                                <span class="preview-rating-score">-</span>
                                <div>
                                    <div class="preview-rating-label">ยังไม่มีรีวิว</div>
                                    <div class="stars-display">☆☆☆☆☆</div>
                                </div>
                            </div>
                            <div class="preview-pills" id="prev-rating-pills"></div>
                        </div>
                        <div class="preview-rating-box">
                            <h4 style="font-size:15px;font-weight:600;margin-bottom:12px">สำหรับสัตว์เลี้ยง</h4>
                            <div class="preview-pills" id="prev-pet-pills"></div>
                            <div class="preview-pet-info" id="prev-pet-info"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Tab -->
            <div id="prev-tab-location" style="display:none">
                <div class="preview-map-block">
                    <h4>ตำแหน่งที่ตั้ง</h4>
                    <div class="preview-address" id="prev-map-address"></div>
                    <div class="preview-map-container" id="previewMap"></div>
                    <button class="preview-map-btn" id="prev-gmap-btn">คลิกเพื่อดูบนแผนที่</button>
                </div>
            </div>

            <button class="preview-confirm-btn" onclick="confirmSubmit()">ยืนยัน</button>

            <div class="form-nav" style="margin-top:16px">
                <button class="btn-back" onclick="setStep(4)">← แก้ไขข้อมูล</button>
            </div>
        </div>

    </section><!-- /page-add-place -->

    <!-- ════════════════════════════════════════
         MY PLACES PAGE
    ════════════════════════════════════════ -->
    <section class="page-section" id="page-my-places">
        <div class="page-header">
            <div>
                <div class="page-title">สถานที่ทั้งหมด</div>
                <div class="page-subtitle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="3"/></svg>
                    Total <?= $totalPlaces ?>
                </div>
            </div>
            <div class="page-actions">
                <div class="search-box">
                    <input type="text" placeholder="Search" id="places-search" oninput="filterPlaces()">
                    <span><span class="iconify" data-icon="mdi:magnify"></span></span>
                </div>
                <select class="filter-btn" id="status-filter" onchange="filterPlaces()">
                    <option value="">Status ▼</option>
                    <option value="pending">Pending</option>
                    <option value="approved">ยืนยันแล้ว</option>
                    <option value="rejected">ถูกปฏิเสธ</option>
                </select>
            </div>
        </div>

        <div class="places-table">
            <div class="table-header">
                <span>ที่</span>
                <span>ชื่อสถานที่</span>
                <span>ประเภท</span>
                <span>สถานะ</span>
                <span>รายละเอียด</span>
            </div>
            <div id="places-tbody">
                <?php if (empty($myPlaces)): ?>
                <div class="table-row" style="grid-template-columns:1fr;color:#94a3b8;justify-content:center">
                    ยังไม่มีสถานที่
                </div>
                <?php else: ?>
                <?php foreach ($myPlaces as $idx => $p):
                    $st = $p['status'] ?? 'pending';
                    $badge = $st === 'approved' ? 'status-approved' : ($st === 'rejected' ? 'status-rejected' : 'status-pending');
                    $stLabel = $st === 'approved' ? 'ยืนยันแล้ว' : ($st === 'rejected' ? 'ถูกปฏิเสธ' : 'รอยืนยัน');
                    $pJson = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                ?>
                <div class="table-row place-row" data-name="<?= htmlspecialchars(strtolower($p['place_name'])) ?>" data-status="<?= $st ?>">
                    <span><?= $idx + 1 ?></span>
                    <span><?= htmlspecialchars($p['place_name']) ?></span>
                    <span><?= htmlspecialchars($p['category'] ?? '-') ?></span>
                    <span><span class="status-badge <?= $badge ?>"><?= $stLabel ?></span></span>
                    <span>
                        <button onclick='openPlaceDetail(<?= $pJson ?>)'
                            style="padding:5px 14px;background:#1e3a5f;color:#fff;border:none;border-radius:7px;font-family:'Kanit',sans-serif;font-size:12px;cursor:pointer;">
                            ดูรายละเอียด
                        </button>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════
         MY REVIEWS PAGE
    ══════════════════════════════════════ -->
    <section class="page-section" id="page-my-reviews">
        <div class="page-header">
            <div>
                <div class="page-title">รีวิวสถานที่ของฉัน</div>
                <div class="page-subtitle">รีวิวที่ได้รับอนุมัติแล้ว</div>
            </div>
        </div>

        <?php if (empty($myReviews)): ?>
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p style="font-size:15px;margin:0;">ยังไม่มีรีวิวที่ได้รับอนุมัติ</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($myReviews as $rv): ?>
            <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 6px rgba(0,0,0,0.07);border:1px solid #f1f5f9;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                    <div>
                        <span style="font-weight:600;font-size:14px;color:#1e293b;"><?= htmlspecialchars($rv['username'] ?? 'ผู้ใช้งาน') ?></span>
                        <span style="font-size:12px;color:#94a3b8;margin-left:8px;">รีวิว <?= htmlspecialchars($rv['place_name'] ?? '-') ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="color:#f59e0b;font-size:16px;"><?= str_repeat('★', (int)$rv['rating']) ?><?= str_repeat('☆', 5-(int)$rv['rating']) ?></span>
                        <span style="font-size:12px;color:#64748b;">(<?= $rv['rating'] ?>/5)</span>
                    </div>
                </div>
                <p style="font-size:14px;color:#374151;margin:0 0 10px;line-height:1.6;"><?= nl2br(htmlspecialchars($rv['comment'] ?? '')) ?></p>
                <span style="font-size:12px;color:#94a3b8;"><?= date('d M Y, H:i', strtotime($rv['created_at'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

</main><!-- /main-content -->

<!-- ══ PLACE DETAIL PANEL ══ -->
<div id="eplaceOverlay" onclick="closePlaceDetail()"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:400;"></div>

<div id="eplacePanel"
    style="position:fixed;top:0;right:0;width:420px;max-width:95vw;height:100vh;background:#fff;z-index:401;
           box-shadow:-4px 0 30px rgba(0,0,0,0.12);overflow-y:auto;transform:translateX(100%);transition:transform .28s ease;">

    <!-- Header -->
    <div style="background:#1e3a5f;padding:20px 22px;color:#fff;display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
            <div id="epTitle" style="font-size:17px;font-weight:600;margin-bottom:3px;"></div>
            <div id="epSub"   style="font-size:13px;opacity:.75;"></div>
        </div>
        <button onclick="closePlaceDetail()"
            style="background:none;border:none;color:#fff;font-size:22px;cursor:pointer;line-height:1;opacity:.8;">✕</button>
    </div>

    <!-- Gallery -->
    <div id="epGallery" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:3px;max-height:180px;overflow:hidden;"></div>

    <!-- Add Images + Edit buttons -->
    <div style="padding:10px 22px 0;display:flex;justify-content:flex-end;gap:8px;">
        <button id="epEditBtn" onclick="openEditPlaceModal()"
            style="display:flex;align-items:center;gap:6px;padding:7px 16px;background:#1e3a5f;border:none;border-radius:8px;color:#fff;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;font-weight:500;">
            ✏️ แก้ไขข้อมูล
        </button>
        <button id="epAddImgBtn" onclick="openAddImagesModal()"
            style="display:flex;align-items:center;gap:6px;padding:7px 16px;background:#f0f7ff;border:1.5px solid #bfdbfe;border-radius:8px;color:#1e3a5f;font-family:'Kanit',sans-serif;font-size:13px;cursor:pointer;font-weight:500;">
            📷 เพิ่มรูปภาพ
        </button>
    </div>

    <!-- Body -->
    <div id="epBody" style="padding:20px 22px;font-family:'Kanit',sans-serif;font-size:14px;color:#374151;"></div>
</div>

<!-- ══ EDIT PLACE MODAL ══ -->
<div id="editPlaceOverlay"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:600;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 0;">
    <div style="background:#fff;border-radius:16px;width:94%;max-width:600px;position:relative;box-shadow:0 8px 40px rgba(0,0,0,0.2);font-family:'Kanit',sans-serif;margin:auto;">

        <!-- Header -->
        <div style="background:#1e3a5f;padding:18px 22px;border-radius:16px 16px 0 0;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:1;">
            <span style="color:#fff;font-size:16px;font-weight:600;">✏️ แก้ไขข้อมูลสถานที่</span>
            <button onclick="closeEditPlaceModal()" style="background:none;border:none;color:#fff;font-size:22px;cursor:pointer;opacity:.8;">✕</button>
        </div>

        <div style="padding:22px 24px;overflow-y:auto;">

            <!-- ── ชื่อสถานที่ ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">ชื่อสถานที่</label>
                <input id="ep_place_name" type="text" class="ep-input">
            </div>

            <!-- ── ประเภท ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">ประเภทสถานที่ <span style="font-weight:400;font-size:12px;color:#94a3b8;">(เลือกได้หลายประเภท)</span></label>
                <div id="ep_cat_pills" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                    <?php foreach ([
                        'โรงแรม'          => 'fa6-solid:hotel',
                        'คาเฟ่'           => 'carbon:cafe',
                        'ร้านอาหาร'       => 'material-symbols:restaurant',
                        'อาบน้ำ ตัดขน'   => 'ion:cut',
                        'โรงพยาบาลสัตว์' => 'mingcute:hospital-fill',
                    ] as $cv => $ci): ?>
                    <label class="ep-cat-pill" onmouseover="this.style.borderColor='#123451'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                        <input type="checkbox" name="ep_cat_cb" value="<?= $cv ?>" style="display:none" onchange="epCatChange()">
                        <span class="iconify" data-icon="<?= $ci ?>" data-width="14"></span> <?= $cv ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── คำอธิบาย ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">คำอธิบาย</label>
                <textarea id="ep_description" rows="4" class="ep-input" style="resize:vertical;"></textarea>
            </div>

            <!-- ── เบอร์โทร ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">เบอร์โทร</label>
                <input id="ep_phone" type="tel" class="ep-input">
            </div>

            <!-- ── เวลาเปิด-ปิด ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">เวลาเปิด - ปิด</label>
                <label style="display:inline-flex;align-items:center;gap:7px;margin-bottom:8px;cursor:pointer;font-size:13px;color:#374151;">
                    <input type="checkbox" id="ep_open24" onchange="epToggle24(this)" style="width:15px;height:15px;accent-color:#123451;cursor:pointer;"> เปิด 24 ชั่วโมง
                </label>
                <div id="ep_time_row" style="display:flex;align-items:center;gap:10px;">
                    <input type="time" id="ep_open_time" class="ep-input" style="width:auto;">
                    <span style="color:#94a3b8;">—</span>
                    <input type="time" id="ep_close_time" class="ep-input" style="width:auto;">
                </div>
            </div>

            <!-- ── ที่อยู่ ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">ที่อยู่</label>
                <textarea id="ep_address" rows="2" class="ep-input" style="resize:vertical;"></textarea>
            </div>

            <!-- ── จังหวัด ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">จังหวัด</label>
                <select id="ep_province" class="ep-input">
                    <option value="">เลือกจังหวัด</option>
                    <?php foreach ($provinces as $pv): ?>
                    <option value="<?= htmlspecialchars($pv) ?>"><?= htmlspecialchars($pv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ── ตำแหน่งแผนที่ ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">ตำแหน่งแผนที่ <span style="font-weight:400;font-size:12px;color:#94a3b8;">(ค้นหาหรือคลิกที่แผนที่เพื่อปักหมุด)</span></label>
                <!-- Search box -->
                <div style="position:relative;margin-bottom:8px;">
                    <input id="ep_map_search" type="text" placeholder="🔍 ค้นหาสถานที่..." class="ep-input"
                        style="padding-left:14px;">
                </div>
                <div id="ep_map" style="width:100%;height:250px;border-radius:10px;border:1.5px solid #e2e8f0;margin-bottom:8px;cursor:crosshair;"></div>
                <div style="display:flex;gap:10px;">
                    <div style="flex:1;">
                        <label class="ep-label" style="font-size:12px;">Latitude</label>
                        <input id="ep_lat" type="text" class="ep-input" style="font-size:13px;" readonly>
                    </div>
                    <div style="flex:1;">
                        <label class="ep-label" style="font-size:12px;">Longitude</label>
                        <input id="ep_lng" type="text" class="ep-input" style="font-size:13px;" readonly>
                    </div>
                </div>
            </div>

            <!-- ── รับสัตว์เลี้ยง ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">รับสัตว์เลี้ยงหรือไม่</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:14px;">
                        <input type="radio" name="ep_pet_allowed" value="yes" style="accent-color:#123451;" onchange="epTogglePetSection()"> ใช่
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:14px;">
                        <input type="radio" name="ep_pet_allowed" value="no" style="accent-color:#123451;" onchange="epTogglePetSection()"> ไม่ใช่
                    </label>
                </div>
            </div>

            <!-- ── pet section (แสดงเมื่อรับสัตว์) ── -->
            <div id="ep_pet_section">
                <div style="margin-bottom:16px;">
                    <label class="ep-label">ประเภทสัตว์เลี้ยงที่รับ</label>
                    <select id="ep_pet_type" class="ep-input" onchange="epToggleExotic(this.value)">
                        <option value="">เลือกประเภทสัตว์ที่รับ</option>
                        <option value="สุนัข (หมา)">สุนัข (หมา)</option>
                        <option value="แมว">แมว</option>
                        <option value="นก">นก</option>
                        <option value="exotic pets ทุกประเภท">exotic pets ทุกประเภท</option>
                        <option value="exotic pets บางประเภท">exotic pets บางประเภท (ระบุเพิ่มเติม)</option>
                        <option value="สุนัข (หมา) และ แมว">สุนัข (หมา) และ แมว</option>
                        <option value="รับทุกประเภทที่กำหนดมา">รับทุกประเภทที่กำหนดมา</option>
                    </select>
                    <div id="ep_exotic_wrap" style="display:none;margin-top:8px;">
                        <input type="text" id="ep_exotic_custom" class="ep-input" placeholder="ระบุประเภท exotic pets ที่รับ">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="ep-label">ขนาดที่รับ</label>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
                        <?php foreach ([
                            'รับเฉพาะขนาดเล็ก (≤ 10 กิโลกรัม)',
                            'รับเฉพาะขนาดเล็ก-กลาง (≤ 25 กิโลกรัม)',
                            'รับเฉพาะขนาดใหญ่',
                            'รับทุกขนาด',
                        ] as $sz): ?>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                            <input type="checkbox" name="ep_pet_size" value="<?= $sz ?>" style="accent-color:#123451;"> <?= $sz ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ── ค่าใช้จ่ายเพิ่มเติม ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">ค่าใช้จ่ายเพิ่มเติม (ถ้ามี)</label>
                <input id="ep_extra_cost" type="text" class="ep-input">
            </div>

            <!-- ── สิ่งอำนวยความสะดวก ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">บริการและสิ่งอำนวยความสะดวก</label>
                <div id="ep_general_amenities" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                    <?php foreach (['Wi-Fi ฟรี','รับฝากสัมภาระ','ห้องพักสัตว์เลี้ยง','แผนกต้อนรับ 24 ชั่วโมง','ที่จอดรถฟรี','อยู่ใจกลางเมือง','ร้านอาหารในบริเวณ','สระว่ายน้ำ'] as $am): ?>
                    <span class="ep-amenity-tag" onclick="epToggleAmenity(this,'general')"><?= $am ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── สิ่งอำนวยความสะดวกสัตว์เลี้ยง ── -->
            <div style="margin-bottom:16px;">
                <label class="ep-label">สิ่งอำนวยความสะดวกสำหรับสัตว์เลี้ยง</label>
                <div id="ep_pet_amenities" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                    <?php foreach (['สนามวิ่งเล่นสำหรับสัตว์เลี้ยง','มีอาหารสำหรับสัตว์เลี้ยง','สระว่ายน้ำสำหรับสัตว์เลี้ยง','ถาดอาหารสำหรับสัตว์เลี้ยง','ไดร์เป่าขนสำหรับสัตว์เลี้ยง','ที่นอนสำหรับสัตว์เลี้ยง','บริการอาบน้ำสัตว์เลี้ยง'] as $pa): ?>
                    <span class="ep-amenity-tag" onclick="epToggleAmenity(this,'pet')"><?= $pa ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── กฎสัตว์เลี้ยง ── -->
            <div style="margin-bottom:20px;">
                <label class="ep-label">กฎ / เงื่อนไขสัตว์เลี้ยง (ถ้ามี)</label>
                <textarea id="ep_pet_rules" rows="3" class="ep-input" style="resize:vertical;" placeholder="ระบุกฎระเบียบสำหรับสัตว์เลี้ยง..."></textarea>
            </div>

            <!-- Message -->
            <div id="editPlaceMsg" style="display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"></div>

            <!-- Buttons -->
            <div style="display:flex;gap:10px;padding-bottom:8px;">
                <button onclick="closeEditPlaceModal()"
                    style="flex:1;padding:12px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;">ยกเลิก</button>
                <button onclick="submitEditPlace()" id="editPlaceSubmitBtn"
                    style="flex:2;padding:12px;background:#1e3a5f;color:#fff;border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:15px;font-weight:600;cursor:pointer;">บันทึกการแก้ไข</button>
            </div>
        </div>
    </div>
</div>

<style>
.ep-label { display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px; }
.ep-input { width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;box-sizing:border-box;outline:none; }
.ep-input:focus { border-color:#123451; }
.ep-cat-pill { display:flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-size:13px;color:#374151;user-select:none;transition:all .15s;font-family:'Kanit',sans-serif; }
.ep-amenity-tag { padding:6px 14px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-size:13px;color:#374151;font-family:'Kanit',sans-serif;transition:all .15s;user-select:none; }
.ep-amenity-tag.selected { background:#1e3a5f;color:#fff;border-color:#1e3a5f; }
</style>

<script>
// ══ EDIT PLACE MODAL ══
let _epMap = null, _epMarker = null;
let _epSelectedGeneralAmenities = [];
let _epSelectedPetAmenities     = [];

function openEditPlaceModal() {
    const pl = window._currentPlace;
    if (!pl) return;

    // basic fields
    document.getElementById('ep_place_name').value  = pl.place_name  || '';
    document.getElementById('ep_description').value = pl.description || '';
    document.getElementById('ep_phone').value       = pl.phone       || '';
    document.getElementById('ep_address').value     = pl.address     || '';
    document.getElementById('ep_extra_cost').value  = pl.extra_cost  || '';
    document.getElementById('ep_pet_rules').value   = pl.pet_rules   || '';

    // province
    const provSel = document.getElementById('ep_province');
    provSel.value = pl.province || '';

    // lat/lng
    document.getElementById('ep_lat').value = pl.latitude  || '';
    document.getElementById('ep_lng').value = pl.longitude || '';

    // category pills
    const cats = (pl.category || '').split(',').map(s => s.trim());
    document.querySelectorAll('input[name="ep_cat_cb"]').forEach(cb => {
        const on = cats.includes(cb.value);
        cb.checked = on;
        const pill = cb.closest('label');
        pill.style.borderColor = on ? '#123451' : '#e2e8f0';
        pill.style.background  = on ? '#eef2f7' : '';
        pill.style.fontWeight  = on ? '600' : '';
    });

    // เวลา
    const is24 = (pl.open_time === '00:00' && pl.close_time === '23:59');
    document.getElementById('ep_open24').checked   = is24;
    document.getElementById('ep_open_time').value  = pl.open_time  || '09:00';
    document.getElementById('ep_close_time').value = pl.close_time || '18:00';
    epToggle24(document.getElementById('ep_open24'));

    // pet allowed
    const petVal = pl.pet_allowed === 'yes' ? 'yes' : 'no';
    document.querySelectorAll('input[name="ep_pet_allowed"]').forEach(r => r.checked = (r.value === petVal));
    document.getElementById('ep_pet_section').style.display = petVal === 'yes' ? 'block' : 'none';

    // pet type
    const ptSel = document.getElementById('ep_pet_type');
    ptSel.value = pl.pet_type_allowed || '';
    epToggleExotic(ptSel.value);

    // pet size checkboxes
    const sizes = (pl.pet_size_allowed || '').split(',').map(s => s.trim());
    document.querySelectorAll('input[name="ep_pet_size"]').forEach(cb => {
        cb.checked = sizes.includes(cb.value);
    });

    // amenities
    _epSelectedGeneralAmenities = (pl.amenities || '').split(',').map(s => s.trim()).filter(Boolean);
    _epSelectedPetAmenities     = (pl.pet_amenities || '').split(',').map(s => s.trim()).filter(Boolean);
    document.querySelectorAll('#ep_general_amenities .ep-amenity-tag').forEach(tag => {
        const on = _epSelectedGeneralAmenities.includes(tag.textContent.trim());
        tag.classList.toggle('selected', on);
    });
    document.querySelectorAll('#ep_pet_amenities .ep-amenity-tag').forEach(tag => {
        const on = _epSelectedPetAmenities.includes(tag.textContent.trim());
        tag.classList.toggle('selected', on);
    });

    document.getElementById('editPlaceMsg').style.display = 'none';
    document.getElementById('editPlaceOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    if (window.Iconify) Iconify.scan(document.getElementById('ep_cat_pills'));

    // init map after overlay visible
    setTimeout(() => epInitMap(pl.latitude || 13.7563, pl.longitude || 100.5018), 200);
}

function closeEditPlaceModal() {
    document.getElementById('editPlaceOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

function epInitMap(lat, lng) {
    if (!window.google) return;

    lat = parseFloat(lat);
    lng = parseFloat(lng);
    const validCoords = (Math.abs(lat) > 0.5 && Math.abs(lng) > 0.5);
    if (!validCoords) { lat = 13.7563; lng = 100.5018; }

    const pos = { lat, lng };

    if (_epMap) {
        _epMap.setCenter(pos);
        _epMarker.setPosition(pos);
        document.getElementById('ep_lat').value = lat.toFixed(6);
        document.getElementById('ep_lng').value = lng.toFixed(6);
        return;
    }

    // ใช้ google.maps.Marker (legacy) เพราะ draggable + click ทำงานได้เสถียรกว่า
    _epMap = new google.maps.Map(document.getElementById('ep_map'), {
        center: pos, zoom: 15,
        mapTypeControl: false, streetViewControl: false,
        fullscreenControl: false,
    });

    _epMarker = new google.maps.Marker({
        position: pos,
        map: _epMap,
        draggable: true,
        title: 'ลากเพื่อย้ายตำแหน่ง',
        animation: google.maps.Animation.DROP,
    });

    function updateLatLng(latLng) {
        const la = latLng.lat().toFixed(6);
        const ln = latLng.lng().toFixed(6);
        document.getElementById('ep_lat').value = la;
        document.getElementById('ep_lng').value = ln;
    }

    // คลิกแผนที่ → ย้าย marker
    _epMap.addListener('click', e => {
        _epMarker.setPosition(e.latLng);
        updateLatLng(e.latLng);
    });

    // ลาก marker
    _epMarker.addListener('dragend', e => updateLatLng(e.latLng));

    // set ค่าเริ่มต้น
    document.getElementById('ep_lat').value = lat.toFixed(6);
    document.getElementById('ep_lng').value = lng.toFixed(6);

    // ── Places Autocomplete search ──
    const searchInput = document.getElementById('ep_map_search');
    if (searchInput && window.google.maps.places) {
        const autocomplete = new google.maps.places.Autocomplete(searchInput, {
            fields: ['geometry', 'name', 'formatted_address'],
        });
        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            if (!place.geometry?.location) return;
            const loc = place.geometry.location;
            _epMap.setCenter(loc);
            _epMap.setZoom(16);
            _epMarker.setPosition(loc);
            updateLatLng(loc);
        });
    }
}

function epCatChange() {
    document.querySelectorAll('input[name="ep_cat_cb"]').forEach(cb => {
        const pill = cb.closest('label');
        pill.style.borderColor = cb.checked ? '#123451' : '#e2e8f0';
        pill.style.background  = cb.checked ? '#eef2f7' : '';
        pill.style.fontWeight  = cb.checked ? '600' : '';
    });
}

function epToggle24(cb) {
    const o = document.getElementById('ep_open_time');
    const c = document.getElementById('ep_close_time');
    const tr = document.getElementById('ep_time_row');
    o.disabled = c.disabled = cb.checked;
    tr.style.opacity = cb.checked ? '0.4' : '1';
    tr.style.pointerEvents = cb.checked ? 'none' : '';
}

function epTogglePetSection() {
    const yes = document.querySelector('input[name="ep_pet_allowed"]:checked')?.value === 'yes';
    document.getElementById('ep_pet_section').style.display = yes ? 'block' : 'none';
}

function epToggleExotic(val) {
    document.getElementById('ep_exotic_wrap').style.display =
        val === 'exotic pets บางประเภท' ? 'block' : 'none';
}

function epToggleAmenity(el, type) {
    el.classList.toggle('selected');
    const arr = type === 'general' ? _epSelectedGeneralAmenities : _epSelectedPetAmenities;
    const txt = el.textContent.trim();
    const idx = arr.indexOf(txt);
    if (idx === -1) arr.push(txt); else arr.splice(idx, 1);
}

async function submitEditPlace() {
    const pl  = window._currentPlace;
    const btn = document.getElementById('editPlaceSubmitBtn');

    const cats    = Array.from(document.querySelectorAll('input[name="ep_cat_cb"]:checked')).map(cb => cb.value);
    const sizes   = Array.from(document.querySelectorAll('input[name="ep_pet_size"]:checked')).map(cb => cb.value);
    const is24    = document.getElementById('ep_open24').checked;
    const petType = document.getElementById('ep_pet_type').value;
    const exotic  = document.getElementById('ep_exotic_custom')?.value.trim() || '';
    const finalPetType = (petType === 'exotic pets บางประเภท' && exotic) ? exotic : petType;

    const fd = new FormData();
    fd.append('place_id',     pl.place_id);
    fd.append('place_name',   document.getElementById('ep_place_name').value.trim());
    fd.append('category',     cats.join(','));
    fd.append('description',  document.getElementById('ep_description').value.trim());
    fd.append('phone',        document.getElementById('ep_phone').value.trim());
    fd.append('open_time',    is24 ? '00:00' : document.getElementById('ep_open_time').value);
    fd.append('close_time',   is24 ? '23:59' : document.getElementById('ep_close_time').value);
    fd.append('address',      document.getElementById('ep_address').value.trim());
    fd.append('province',     document.getElementById('ep_province').value);
    fd.append('latitude',     document.getElementById('ep_lat').value);
    fd.append('longitude',    document.getElementById('ep_lng').value);
    fd.append('pet_allowed',  document.querySelector('input[name="ep_pet_allowed"]:checked')?.value || 'no');
    fd.append('pet_type',     finalPetType);
    fd.append('pet_size',     sizes.join(', '));
    fd.append('extra_cost',   document.getElementById('ep_extra_cost').value.trim());
    fd.append('amenities',    _epSelectedGeneralAmenities.join(','));
    fd.append('pet_amenities',_epSelectedPetAmenities.join(','));
    fd.append('pet_rules',    document.getElementById('ep_pet_rules').value.trim());

    btn.disabled = true; btn.textContent = 'กำลังบันทึก...';

    try {
        const res  = await fetch('update_place.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            window._currentPlace = {
                ...window._currentPlace,
                place_name: document.getElementById('ep_place_name').value.trim(),
                category:   cats.join(','), description: document.getElementById('ep_description').value.trim(),
                phone:      document.getElementById('ep_phone').value.trim(),
                open_time:  is24 ? '00:00' : document.getElementById('ep_open_time').value,
                close_time: is24 ? '23:59' : document.getElementById('ep_close_time').value,
                address:    document.getElementById('ep_address').value.trim(),
                province:   document.getElementById('ep_province').value,
                latitude:   document.getElementById('ep_lat').value,
                longitude:  document.getElementById('ep_lng').value,
                pet_allowed: document.querySelector('input[name="ep_pet_allowed"]:checked')?.value || 'no',
                pet_type_allowed: finalPetType, pet_size_allowed: sizes.join(', '),
                extra_cost: document.getElementById('ep_extra_cost').value.trim(),
                amenities:  _epSelectedGeneralAmenities.join(','),
                pet_amenities: _epSelectedPetAmenities.join(','),
                pet_rules:  document.getElementById('ep_pet_rules').value.trim(),
                status:     'pending',
            };
            showEditMsg('✅ บันทึกสำเร็จ! สถานที่จะกลับสู่สถานะ "รอยืนยัน" จนกว่าแอดมินจะอนุมัติอีกครั้ง', 'success');
            setTimeout(() => { closeEditPlaceModal(); openPlaceDetail(window._currentPlace); }, 2000);
        } else {
            showEditMsg(data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch(e) {
        showEditMsg('เชื่อมต่อไม่สำเร็จ', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'บันทึกการแก้ไข';
    }
}

function showEditMsg(msg, type) {
    const el = document.getElementById('editPlaceMsg');
    el.textContent = msg; el.style.display = 'block';
    el.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
    el.style.color      = type === 'success' ? '#065f46' : '#991b1b';
    el.style.border     = `1px solid ${type === 'success' ? '#6ee7b7' : '#fca5a5'}`;
}

document.getElementById('editPlaceOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeEditPlaceModal();
});
</script>

<!-- ══ ADD IMAGES MODAL ══ -->
<div id="addImgOverlay"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:500;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px 24px;width:92%;max-width:460px;position:relative;box-shadow:0 8px 40px rgba(0,0,0,0.2);font-family:'Kanit',sans-serif;">
        <button onclick="closeAddImagesModal()"
            style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;">✕</button>

        <h3 style="font-size:17px;font-weight:600;color:#1e293b;margin:0 0 4px;">เพิ่มรูปภาพ</h3>
        <p id="addImgPlaceName" style="font-size:13px;color:#64748b;margin:0 0 18px;"></p>

        <!-- Drop zone -->
        <div id="addImgDropzone"
            onclick="document.getElementById('addImgInput').click()"
            style="border:2px dashed #cbd5e1;border-radius:12px;padding:28px 16px;text-align:center;cursor:pointer;background:#f8fafc;transition:border-color .2s;"
            ondragover="event.preventDefault();this.style.borderColor='#3b82f6'"
            ondragleave="this.style.borderColor='#cbd5e1'"
            ondrop="handleAddImgDrop(event)">
            <div style="font-size:32px;margin-bottom:8px;">🖼️</div>
            <div style="font-size:14px;color:#64748b;">คลิกหรือลากรูปมาวางที่นี่</div>
            <div style="font-size:12px;color:#94a3b8;margin-top:4px;">JPG, PNG, WEBP ขนาดไม่เกิน 5MB ต่อรูป</div>
        </div>
        <input type="file" id="addImgInput" accept="image/*" multiple style="display:none" onchange="handleAddImgSelect(event)">

        <!-- Preview -->
        <div id="addImgPreview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;"></div>

        <!-- Count info -->
        <div id="addImgInfo" style="font-size:12px;color:#94a3b8;margin-top:8px;"></div>

        <!-- Message -->
        <div id="addImgMsg" style="display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-top:12px;"></div>

        <!-- Buttons -->
        <div style="display:flex;gap:10px;margin-top:18px;">
            <button onclick="closeAddImagesModal()"
                style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;">
                ยกเลิก
            </button>
            <button onclick="submitAddImages()" id="addImgSubmitBtn"
                style="flex:2;padding:11px;background:#1e3a5f;color:#fff;border:none;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">
                บันทึกรูปภาพ
            </button>
        </div>
    </div>
</div>

<script>
// ══ ADD IMAGES MODAL ══
let _addImgPlaceId   = null;
let _addImgFiles     = [];
let _addImgTotal     = 0; // current total images of this place

function openAddImagesModal() {
    // ดึง place_id และ total จาก state ที่ openPlaceDetail set ไว้
    if (!window._currentPlace) return;
    const pl = window._currentPlace;
    _addImgPlaceId = pl.place_id;
    const existing = pl.all_images ? pl.all_images.split(',').filter(Boolean).length
                   : (pl.place_image ? 1 : 0);
    _addImgTotal   = existing;
    _addImgFiles   = [];

    document.getElementById('addImgPlaceName').textContent = pl.place_name || '';
    document.getElementById('addImgPreview').innerHTML     = '';
    document.getElementById('addImgMsg').style.display     = 'none';
    document.getElementById('addImgInfo').textContent      = `มีรูปอยู่แล้ว ${existing} รูป (สูงสุด 10 รูป)`;

    const overlay = document.getElementById('addImgOverlay');
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddImagesModal() {
    document.getElementById('addImgOverlay').style.display = 'none';
    document.body.style.overflow = '';
    _addImgFiles = [];
}

function handleAddImgSelect(event) {
    addFiles(Array.from(event.target.files));
    event.target.value = '';
}

function handleAddImgDrop(event) {
    event.preventDefault();
    document.getElementById('addImgDropzone').style.borderColor = '#cbd5e1';
    addFiles(Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/')));
}

function addFiles(files) {
    const maxAdd = 10 - _addImgTotal;
    const canAdd = maxAdd - _addImgFiles.length;
    if (canAdd <= 0) {
        showAddImgMsg('เพิ่มรูปได้อีกสูงสุด ' + maxAdd + ' รูปเท่านั้น', 'error');
        return;
    }
    files.slice(0, canAdd).forEach(f => {
        if (f.size > 5 * 1024 * 1024) { showAddImgMsg('รูป "' + f.name + '" ใหญ่เกิน 5MB', 'error'); return; }
        _addImgFiles.push(f);
    });
    renderAddImgPreview();
}

function renderAddImgPreview() {
    const preview = document.getElementById('addImgPreview');
    preview.innerHTML = '';
    _addImgFiles.forEach((file, i) => {
        const url  = URL.createObjectURL(file);
        const wrap = document.createElement('div');
        wrap.style.cssText = 'position:relative;width:72px;height:72px;';
        wrap.innerHTML = `
            <img src="${url}" style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
            <button onclick="removeAddImg(${i})" type="button"
                style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;
                       background:#ef4444;color:#fff;border:none;font-size:11px;cursor:pointer;
                       display:flex;align-items:center;justify-content:center;">✕</button>
        `;
        preview.appendChild(wrap);
    });
    const total = _addImgTotal + _addImgFiles.length;
    document.getElementById('addImgInfo').textContent =
        `จะมีรูปทั้งหมด ${total}/10 รูป (เพิ่มใหม่ ${_addImgFiles.length} รูป)`;
}

function removeAddImg(idx) {
    URL.revokeObjectURL(URL.createObjectURL(_addImgFiles[idx]));
    _addImgFiles.splice(idx, 1);
    renderAddImgPreview();
}

async function submitAddImages() {
    if (_addImgFiles.length === 0) {
        showAddImgMsg('กรุณาเลือกรูปภาพก่อน', 'error');
        return;
    }

    const btn = document.getElementById('addImgSubmitBtn');
    btn.disabled    = true;
    btn.textContent = 'กำลังอัปโหลด...';

    const fd = new FormData();
    fd.append('place_id', _addImgPlaceId);
    _addImgFiles.forEach(f => fd.append('new_images[]', f));

    try {
        const res  = await fetch('update_place_images.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showAddImgMsg(data.message, 'success');
            // อัปเดต gallery ใน panel ทันที
            if (window._currentPlace) {
                window._currentPlace.all_images  = data.all_images;
                window._currentPlace.place_image = window._currentPlace.place_image || data.new_images[0];
                refreshEpGallery();
            }
            _addImgFiles = [];
            _addImgTotal = data.total;
            renderAddImgPreview();
            setTimeout(closeAddImagesModal, 1500);
        } else {
            showAddImgMsg(data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch (e) {
        showAddImgMsg('เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่', 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'บันทึกรูปภาพ';
    }
}

function showAddImgMsg(msg, type) {
    const el = document.getElementById('addImgMsg');
    el.textContent    = msg;
    el.style.display  = 'block';
    el.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
    el.style.color      = type === 'success' ? '#065f46' : '#991b1b';
    el.style.border     = `1px solid ${type === 'success' ? '#6ee7b7' : '#fca5a5'}`;
}

function refreshEpGallery() {
    const pl  = window._currentPlace;
    const esc = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const imgs = pl.all_images ? pl.all_images.split(',').filter(Boolean) : (pl.place_image ? [pl.place_image] : []);
    const gallery = document.getElementById('epGallery');
    if (imgs.length > 0) {
        gallery.style.display = 'grid';
        gallery.innerHTML = imgs.slice(0,5).map((src, i) =>
            `<img src="${esc(src)}" style="width:100%;height:${i>0?'88':'180'}px;object-fit:cover;" onerror="this.style.display='none'">`
        ).join('');
    }
}

// ปิด modal เมื่อคลิกพื้นหลัง
document.getElementById('addImgOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeAddImagesModal();
});
</script>

<!-- ══ SUCCESS MODAL ══ -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <div class="modal-icon"></div>
        <div class="modal-title">ส่งข้อมูลสำเร็จ!</div>
        <div class="modal-text">
            ข้อมูลสถานที่ของคุณถูกส่งให้แอดมินตรวจสอบแล้ว<br>
            สถานะจะแสดงเป็น <strong>"pending"</strong> จนกว่าแอดมินจะอนุมัติ
        </div>
        <button class="modal-btn" onclick="afterConfirm()">ตกลง</button>
    </div>
</div>

<!-- ══ TOAST ERROR ══ -->
<div class="toast-error" id="toastError"></div>

<!-- ══ SCRIPTS ══ -->
<script>
    const ENTRE_ID = <?= $entre_id ?>;
    const HAS_PLACES = <?= $hasPlaces ? 'true' : 'false' ?>;
    const PLACES_DATA = <?= json_encode($myPlaces) ?>;
</script>

<script>
function handleDashDocUpload(event, key) {
    const files = Array.from(event.target.files);
    window.dashUploadedDocs[key] = (window.dashUploadedDocs[key] || []).concat(files);
    renderDashDocPreview(key);
}

function renderDashDocPreview(key) {
    const preview = document.getElementById('ddpreview_' + key);
    if (!preview) return;
    preview.innerHTML = '';
    (window.dashUploadedDocs[key] || []).forEach((file, i) => {
        const chip = document.createElement('div');
        chip.className = 'file-chip';
        chip.innerHTML = `
            <span>${file.type.includes('pdf') ? '' : ''}</span>
            <span>${file.name}</span>
            <button class="file-chip-remove" onclick="removeDashDoc('${key}',${i})">×</button>
        `;
        preview.appendChild(chip);
    });
}

function removeDashDoc(key, idx) {
    window.dashUploadedDocs[key].splice(idx, 1);
    renderDashDocPreview(key);
}
</script>
<script>
function openPlaceDetail(pl) {
    const esc = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    // เก็บ place ปัจจุบันไว้ใช้ใน modal เพิ่มรูป
    window._currentPlace = pl;

    // แสดง/ซ่อนปุ่มเพิ่มรูป (approved เท่านั้น)
    const addBtn = document.getElementById('epAddImgBtn');
    if (addBtn) addBtn.style.display = (pl.status === 'approved') ? 'flex' : 'none';

    document.getElementById('epTitle').textContent = pl.place_name || '—';
    const cat  = pl.category || '';
    const prov = pl.province || '';
    document.getElementById('epSub').textContent = [cat, prov].filter(Boolean).join(' • ');

    // Gallery
    const imgs = pl.all_images ? pl.all_images.split(',').filter(Boolean) : (pl.place_image ? [pl.place_image] : []);
    const gallery = document.getElementById('epGallery');
    if (imgs.length > 0) {
        gallery.style.display = 'grid';
        gallery.innerHTML = imgs.slice(0,5).map((src, i) =>
            `<img src="${esc(src)}" style="width:100%;height:180px;object-fit:cover;${i>0?'height:88px;':''}" onerror="this.style.display='none'">`
        ).join('');
    } else {
        gallery.style.display = 'none';
    }

    // Status badge
    const stMap  = { approved:' ยืนยันแล้ว', pending:' รอยืนยัน', rejected:' ถูกปฏิเสธ' };
    const stColor = { approved:'#d1fae5', pending:'#fff7e0', rejected:'#fdecea' };
    const stTxt  = { approved:'#065f46', pending:'#a06c00', rejected:'#b02a2a' };
    const st = pl.status || 'pending';

    // License
    const licHtml = pl.license_file
        ? `<img src="${esc(pl.license_file)}" onclick="window.open('${esc(pl.license_file)}','_blank')"
               style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;cursor:pointer;border:1px solid #e2e8f0;" onerror="this.style.display='none'">`
        : '<span style="color:#94a3b8;font-size:13px;">ไม่มีไฟล์</span>';

    // Category docs
    let catDocsHtml = '<span style="color:#94a3b8;font-size:13px;">ไม่มีเอกสาร</span>';
    if (pl.category_docs) {
        try {
            const docs = JSON.parse(pl.category_docs);
            if (docs && docs.length > 0) {
                catDocsHtml = '<div style="display:flex;flex-wrap:wrap;gap:8px;">' + docs.map(src => {
                    const isPdf = src.toLowerCase().endsWith('.pdf');
                    return isPdf
                        ? `<a href="${esc(src)}" target="_blank" style="padding:7px 12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1e40af;font-size:12px;text-decoration:none;">📄 PDF</a>`
                        : `<img src="${esc(src)}" onclick="window.open('${esc(src)}','_blank')" style="width:80px;height:80px;object-fit:cover;border-radius:8px;cursor:pointer;border:1px solid #e2e8f0;" onerror="this.style.display='none'">`;
                }).join('') + '</div>';
            }
        } catch(e) {}
    }

    // Pet allowed
    const petAllowed = pl.pet_allowed === 'yes' ? ' รับสัตว์เลี้ยง' : ' ไม่รับสัตว์เลี้ยง';

    // Rejection reason
    const rejHtml = pl.rejection_reason
        ? `<div style="background:#fdecea;color:#b02a2a;padding:10px 14px;border-radius:8px;font-size:13px;margin-top:4px;">${esc(pl.rejection_reason)}</div>`
        : '';

    const row = (label, val) => val
        ? `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;gap:12px;">
               <span style="color:#94a3b8;font-size:13px;white-space:nowrap;">${label}</span>
               <span style="font-size:13px;color:#1e293b;text-align:right;">${val}</span>
           </div>`
        : '';

    const sec = (title, content) =>
        `<div style="margin-bottom:18px;">
             <div style="font-size:13px;font-weight:600;color:#1e3a5f;letter-spacing:.4px;margin-bottom:8px;padding-bottom:4px;border-bottom:2px solid #e2e8f0;">${title}</div>
             ${content}
         </div>`;

    document.getElementById('epBody').innerHTML = `
        <div style="margin-bottom:18px;">
            <span style="padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;
                background:${stColor[st]};color:${stTxt[st]};">
                ${stMap[st] || st}
            </span>
            ${rejHtml}
        </div>
        ${sec('ข้อมูลทั่วไป', `
            ${row('เบอร์โทร', esc(pl.phone||'—'))}
            ${row('เวลาเปิด–ปิด', (() => {
                const o = pl.open_time || '', c = pl.close_time || '';
                if (o === '00:00' && c === '23:59') return '🕐 เปิด 24 ชั่วโมง';
                return o && c ? esc(o) + ' — ' + esc(c) : '—';
            })())}
            ${row('ประเภท', esc(pl.category||'—'))}
            ${row('คำอธิบาย', esc(pl.description||'—'))}
        `)}
        ${sec('ที่ตั้ง', `
            ${row('ที่อยู่', esc(pl.address||'—'))}
            ${row('จังหวัด', esc(pl.province||'—'))}
            ${pl.latitude && pl.longitude
                ? `<a href="https://maps.google.com/?q=${pl.latitude},${pl.longitude}" target="_blank"
                       style="display:flex;align-items:center;gap:6px;margin-top:8px;padding:8px 14px;background:#f0f7ff;border-radius:8px;color:#1e3a5f;font-size:13px;text-decoration:none;border:1px solid #bfdbfe;">
                     ดูบน Google Maps
                   </a>` : ''}
        `)}
        ${sec('นโยบายสัตว์เลี้ยง', `
            ${row('รับสัตว์เลี้ยง', petAllowed)}
            ${row('ประเภทสัตว์', esc(pl.pet_type_allowed||'—'))}
            ${row('ขนาดที่รับ', esc(pl.pet_size_allowed||'—'))}
            ${row('ค่าใช้จ่ายเพิ่ม', esc(pl.extra_cost||'ไม่มี'))}
            ${pl.pet_rules ? row('กฎสัตว์เลี้ยง', esc(pl.pet_rules)) : ''}
        `)}
        ${sec('ใบยืนยันการเป็นผู้ประกอบการ', licHtml)}
        ${sec('เอกสารยืนยันประเภทสถานที่', catDocsHtml)}
    `;

    document.getElementById('eplaceOverlay').style.display = 'block';
    document.getElementById('eplacePanel').style.transform = 'translateX(0)';
    document.body.style.overflow = 'hidden';
}

function closePlaceDetail() {
    document.getElementById('eplacePanel').style.transform = 'translateX(100%)';
    document.getElementById('eplaceOverlay').style.display = 'none';
    document.body.style.overflow = '';
}
</script>
<script src="entre_dashboard.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCv4OB-oXGw-sb2tGpF5yPOyK2tchEc2y0&v=beta&libraries=places,marker&callback=initGoogleMaps" async defer></script>
</body>
</html>