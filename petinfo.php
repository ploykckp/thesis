<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: form-login.php?auth=required');
    exit;
}
require_once 'connect.php';

// Load ALL pets for this user
$pets = [];
if (!empty($_SESSION['user_id']) && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pets WHERE user_id = ? ORDER BY pet_id ASC");
        $stmt->execute([$_SESSION['user_id']]);
        $pets = $stmt->fetchAll();
    } catch (PDOException $e) { $pets = []; }
}

function hv($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$allTags    = ['เป็นมิตร','ไม่เป็นมิตร','ขี้กลัว','ขี้เล่น','ไม่ชอบเสียงดัง','ชอบพื้นที่กว้าง','หลับง่าย','ขี้อ้อน','ชอบเดิน'];
$genderLabel = ['male' => 'เพศชาย', 'female' => 'เพศหญิง'];
$typeIcon    = ['สุนัข' => 'mdi:dog', 'แมว' => 'mdi:cat', 'อื่นๆ' => 'mdi:paw'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลสัตว์เลี้ยง - Pawlands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="petinfo.css">
    <link rel="stylesheet" href="footer.css">
    <style>
        /* ── Pet Photo Upload ── */
        .pet-bubble-avatar { position: relative; }
        .pet-bubble-avatar img.pet-photo {
            width: 100%; height: 100%;
            border-radius: 50%; object-fit: cover;
            position: absolute; inset: 0;
        }
        .pet-bubble-avatar .camera-overlay {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(0,0,0,.45);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s; cursor: pointer;
        }
        .pet-bubble:hover .camera-overlay { opacity: 1; }
        .pet-bubble-avatar input[type=file] { display: none; }
        .pet-modal-avatar img.pet-photo {
            width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
        }
        .pet-modal-avatar .camera-overlay-modal {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(0,0,0,.5);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 3px;
            opacity: 0; transition: opacity .2s; cursor: pointer;
        }
        .pet-modal-avatar:hover .camera-overlay-modal { opacity: 1; }
        .pet-modal-avatar { position: relative; cursor: pointer; }
        #pmAvatarFileInput { display: none; }

        /* ── Pet Profile Bubbles ── */
        .pet-profiles-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 36px;
        }
        .pet-bubble {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: transform .2s;
        }
        .pet-bubble:hover { transform: translateY(-4px); }
        .pet-bubble-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #123451;
            border: 3px solid #64b5f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .pet-bubble:hover .pet-bubble-avatar {
            border-color: #42a5f5;
            box-shadow: 0 4px 16px rgba(100,181,246,.4);
        }
        .pet-bubble-name {
            font-size: 13px;
            font-weight: 600;
            color: #123451;
            text-align: center;
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── Pet Profile Popup Modal ── */
        .pet-modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s;
        }
        .pet-modal-bg.open { opacity: 1; pointer-events: all; }
        .pet-modal {
            background: #fff;
            border-radius: 24px;
            width: 95%;
            max-width: 480px;
            box-shadow: 0 24px 80px rgba(0,0,0,.25);
            transform: translateY(24px);
            transition: transform .25s;
            overflow: hidden;
        }
        .pet-modal-bg.open .pet-modal { transform: translateY(0); }
        .pet-modal-header {
            background: linear-gradient(135deg, #123451, #1e5a8a);
            padding: 28px 28px 20px;
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
        }
        .pet-modal-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            border: 3px solid rgba(255,255,255,.4);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; color: #fff;
        }
        .pet-modal-petname { font-size: 22px; font-weight: 700; color: #fff; }
        .pet-modal-pettype { font-size: 14px; color: rgba(255,255,255,.75); margin-top: 4px; }
        .pet-modal-close {
            position: absolute; top: 14px; right: 16px;
            background: rgba(255,255,255,.15); border: none; border-radius: 50%;
            width: 32px; height: 32px; font-size: 16px; color: #fff;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .pet-modal-close:hover { background: rgba(255,255,255,.3); }
        .pet-modal-body { padding: 24px 28px; }
        .pet-modal-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; margin-bottom: 18px;
        }
        .pet-modal-item {
            background: #f0f7ff; border-radius: 12px;
            padding: 12px 14px; border: 1.5px solid #d4e7f7;
        }
        .pet-modal-key { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px; }
        .pet-modal-val { font-size: 16px; font-weight: 600; color: #1e293b; }
        .pet-modal-val.empty { color: #cbd5e1; font-weight: 400; font-style: italic; font-size: 14px; }
        .pet-modal-tags { display: flex; flex-wrap: wrap; gap: 7px; }
        .pet-modal-tag { padding: 5px 14px; background: #dbeeff; color: #123451; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .pet-modal-footer { padding: 0 28px 22px; display: flex; gap: 10px; }
        .pet-modal-btn-edit {
            flex: 1; padding: 11px; background: #123451; color: #fff;
            border: none; border-radius: 12px; font-family: 'Kanit',sans-serif;
            font-size: 15px; font-weight: 600; cursor: pointer; transition: background .2s;
        }
        .pet-modal-btn-edit:hover { background: #1e4f77; }

        /* ── Form Modal (Add/Edit) ── */
        .form-modal-bg {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 2100;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity .25s;
            padding: 16px;
        }
        .form-modal-bg.open { opacity: 1; pointer-events: all; }
        .form-modal {
            background: #dbeeff; border-radius: 20px;
            width: 100%; max-width: 700px;
            max-height: 90vh; overflow-y: auto;
            padding: 36px 40px 32px;
            box-shadow: 0 24px 80px rgba(0,0,0,.25);
            transform: translateY(24px); transition: transform .25s;
            position: relative;
        }
        .form-modal-bg.open .form-modal { transform: translateY(0); }
        .form-modal-title {
            font-size: 22px; font-weight: 700; color: #123451;
            margin-bottom: 24px;
        }
        .form-modal-close {
            position: absolute; top: 16px; right: 18px;
            background: rgba(18,52,81,.1); border: none; border-radius: 50%;
            width: 34px; height: 34px; font-size: 18px; color: #123451;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .form-modal-close:hover { background: #fee2e2; color: #ef4444; }

        /* Weight with kg unit */
        .weight-input-wrap { position: relative; display: flex; align-items: center; }
        .weight-input-wrap .form-input { padding-right: 52px; }
        .weight-unit {
            position: absolute; right: 14px;
            font-size: 14px; font-weight: 600; color: #64748b;
            pointer-events: none;
        }

        /* Behavior tags inside form */
        .form-behavior-section { margin-top: 16px; }

        /* Photo upload in edit form */
        .form-photo-section {
            display: flex; flex-direction: column; align-items: center;
            gap: 10px; margin-bottom: 24px;
        }
        .form-photo-avatar {
            width: 110px; height: 110px; border-radius: 50%;
            background: #c8dff5; border: 3px dashed #64b5f6;
            display: flex; align-items: center; justify-content: center;
            position: relative; cursor: pointer; overflow: hidden;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-photo-avatar:hover { border-color: #1e5a8a; box-shadow: 0 0 0 4px rgba(100,181,246,.25); }
        .form-photo-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .form-photo-overlay {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(0,0,0,.45);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 4px;
            opacity: 0; transition: opacity .2s;
            font-size: 12px; color: #fff; font-weight: 600;
        }
        .form-photo-avatar:hover .form-photo-overlay { opacity: 1; }
        .form-photo-hint { font-size: 13px; color: #64748b; }
        .form-behavior-label { font-size: 25px; font-weight: bold; color: #1e293b; margin-bottom: 10px; }
        .form-tags { display: flex; flex-wrap: wrap; gap: 10px; }
        .form-tag {
            padding: 8px 20px; background: #fff; color: #123451;
            border: 1.5px solid #93c5e8; border-radius: 50px;
            font-family: 'Kanit',sans-serif; font-size: 14px; font-weight: 500;
            cursor: pointer; transition: all .2s;
        }
        .form-tag.active { background: #123451; color: #fff; border-color: #123451; }
        .form-tag:hover:not(.active) { background: #dbeeff; }

        /* Full-width form group */
        .form-group--full {
            grid-column: 1 / -1;
        }

        /* Date input styling */
        input[type="date"].form-input {
            cursor: pointer;
            color: #1e293b;
        }
        input[type="date"].form-input::-webkit-calendar-picker-indicator {
            opacity: 0.6;
            cursor: pointer;
        }

        /* Age display badge */
        .age-display {
            display: inline-block;
            padding: 3px 12px;
            background: #dbeeff;
            border-radius: 20px;
            color: #1e5a8a;
            font-size: 13px;
            font-weight: 600;
        }
        .age-display:empty { display: none; }

        /* Toast */
        .pet-toast {
            position: fixed; bottom: 28px; right: 28px; z-index: 9999;
            padding: 14px 22px; border-radius: 12px;
            font-family: 'Kanit',sans-serif; font-size: 15px; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
            transform: translateY(60px); opacity: 0;
            transition: all .3s cubic-bezier(.34,1.56,.64,1);
            box-shadow: 0 6px 20px rgba(0,0,0,.15); pointer-events: none;
        }
        .pet-toast.show { transform: translateY(0); opacity: 1; }
        .pet-toast.ts { background: #123451; color: #fff; }
        .pet-toast.te { background: #ef4444; color: #fff; }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo"><img src="logo.png" alt="Logo" width="136" height="136"></div>
            <nav class="nav">
                <a href="home.php" class="nav-link">หน้าแรก</a>
                <a href="plantrip.php" class="nav-link">แผนเที่ยว</a>
                <a href="petinfo.php" class="nav-link nav-link--active">ข้อมูลสัตว์เลี้ยง</a>
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

<!-- ===== MAIN ===== -->
<main class="main">

    <!-- Page Title -->
    <section class="page-title-section">
        <h1 class="page-title">กรอกข้อมูลสัตว์เลี้ยงของคุณ</h1>
        <p class="page-subtitle">เพื่อค้นหาสถานที่ที่ดีสำหรับเพื่อนซี้ของคุณ!!</p>
    </section>

    <?php if (!empty($pets)): ?>
    <!-- ── Pet Profile Bubbles (shown when at least 1 pet saved) ── -->
    <div class="pet-profiles-row" id="petProfilesRow">
        <?php foreach ($pets as $p): ?>
        <div class="pet-bubble" onclick="openPetModal(<?= (int)$p['pet_id'] ?>)">
            <div class="pet-bubble-avatar">
                <?php if (!empty($p['pet_image'])): ?>
                <img class="pet-photo" src="<?= hv($p['pet_image']) ?>" alt="pet">
                <?php else: ?>
                <span class="iconify" data-icon="<?= hv($typeIcon[$p['pet_type']] ?? 'mdi:paw') ?>" data-width="36" data-height="36"></span>
                <?php endif; ?>
                <input type="file" id="photoInput_<?= (int)$p['pet_id'] ?>" accept="image/*" onchange="uploadPetPhoto(<?= (int)$p['pet_id'] ?>, this)" style="display:none;">
            </div>
            <div class="pet-bubble-name"><?= hv($p['pet_name']) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="pet-bubble" onclick="openFormModal(null)">
            <div class="pet-bubble-avatar" style="background:#e0f0ff;border-color:#93c5e8;">
                <span class="iconify" data-icon="mdi:plus" data-width="36" data-height="36" style="color:#123451;"></span>
            </div>
            <div class="pet-bubble-name" style="color:#64748b;">เพิ่มสัตว์เลี้ยง</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== FORM WRAPPER (shown when no pets yet) ===== -->
    <?php if (empty($pets)): ?>
    <div class="forms-wrapper" id="formsWrapper">
        <div class="pet-form-card" id="petForm1">

            <!-- Photo upload inside the card -->
            <div class="form-photo-row" style="display:flex; justify-content:center; margin-bottom:24px;">
                <div class="form-photo-wrap" id="formPhotoWrap" onclick="document.getElementById('formPhotoInput').click()" title="เพิ่มรูปสัตว์เลี้ยง" style="width:110px; height:110px; border-radius:50%; background:#123451; border:4px solid #93c5e8; display:flex; align-items:center; justify-content:center; cursor:pointer; overflow:hidden; box-shadow:0 4px 16px rgba(18,52,81,0.25);">
                    <img id="formPhotoPreview" src="" alt="preview" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:50%;">
                    <span class="iconify" id="formPhotoCamIcon" data-icon="mdi:camera" data-width="44" data-height="44" style="color:#fff;"></span>
                </div>
                <input type="file" id="formPhotoInput" accept="image/*" style="display:none;"
                    onchange="
                        const f=this.files[0]; if(!f) return;
                        const r=new FileReader();
                        r.onload=e=>{
                            document.getElementById('formPhotoPreview').src=e.target.result;
                            document.getElementById('formPhotoPreview').style.display='block';
                            document.getElementById('formPhotoCamIcon').style.display='none';
                        };
                        r.readAsDataURL(f);
                    ">
            </div>

            <?php echo buildFormHTML(null, $allTags, 1); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== when pets exist: show add button only ===== -->
    <?php if (!empty($pets)): ?>
    <div style="text-align:center; margin: 24px 0 60px;">
        <button class="btn-save-profile" onclick="openFormModal(null)" style="padding:12px 32px; font-size:16px;">
            <span class="iconify" data-icon="mdi:plus" data-width="18"></span> เพิ่มสัตว์เลี้ยง
        </button>
    </div>
    <?php endif; ?>

</main>

<?php include 'footer.php'; ?>

<!-- ===== PET PROFILE POPUP ===== -->
<div class="pet-modal-bg" id="petModalBg" onclick="if(event.target===this)closePetModal()">
    <div class="pet-modal">
        <div class="pet-modal-header">
            <div class="pet-modal-avatar" id="pmAvatar">
                <span class="iconify" data-icon="mdi:paw" data-width="40"></span>
            </div>
            <input type="file" id="pmAvatarFileInput" accept="image/*" onchange="uploadPetPhotoFromModal(this)" style="display:none;">
            <div>
                <div class="pet-modal-petname" id="pmName"></div>
                <div class="pet-modal-pettype" id="pmType"></div>
            </div>
            <button class="pet-modal-close" onclick="closePetModal()">✕</button>
        </div>
        <div class="pet-modal-body">
            <div class="pet-modal-grid" id="pmGrid"></div>
            <div style="font-size:13px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">ลักษณะนิสัย</div>
            <div class="pet-modal-tags" id="pmTags"></div>
        </div>
        <div class="pet-modal-footer">
            <button class="pet-modal-btn-edit" id="pmEditBtn" onclick="editCurrentPet()"><span class="iconify" data-icon="mdi:pencil"></span> แก้ไขข้อมูล</button>
        </div>
    </div>
</div>

<!-- ===== FORM MODAL (Add / Edit) ===== -->
<div class="form-modal-bg" id="formModalBg" onclick="if(event.target===this)closeFormModal()">
    <div class="form-modal" id="formModal">
        <button class="form-modal-close" onclick="closeFormModal()">✕</button>
        <div class="form-modal-title" id="formModalTitle">เพิ่มสัตว์เลี้ยง</div>
        <div id="formModalBody"></div>
    </div>
</div>

<!-- ===== TOAST ===== -->
<div class="pet-toast" id="petToast"></div>

<!-- ===== PET DATA (PHP → JS) ===== -->
<script>
const ALL_PETS = <?= json_encode($pets, JSON_UNESCAPED_UNICODE) ?>;
const ALL_TAGS = <?= json_encode($allTags, JSON_UNESCAPED_UNICODE) ?>;
const TYPE_ICONS = { 'สุนัข':'mdi:dog', 'แมว':'mdi:cat', 'อื่นๆ':'mdi:paw' };

const DOG_BREEDS = ['อัฟกัน ฮาวด์','อลาสกัน มาลามิวท์','บาเซนจิ','บีเกิ้ล','บิชอง ฟริเซ่','บูลด็อก','คาวาเลียร์ คิง ชาลส์ สแปเนียล','ชิวาวา','เชาเชา','บอร์เดอร์ คอลลี่','ดัชชุน','เฟรนช์ บูลด็อก','เยอรมันเชพเพิร์ด','เกรย์ฮาวด์','ชิบะ','มอลทีส','ปอมเมอเรเนียน','พุดเดิ้ลทอย','ปั๊ก','โกลเด้น รีทริฟเวอร์','ลาบราดอร์ รีทรีฟเวอร์','เชทแลนด์ ชีพด็อก','ชิสุห์','ไซบีเรียน ฮัสกี','ยอร์กเชียร์ เทอร์เรียร์','บูล เทอร์เรียร์','ลาซา แอพโช','บอร์ซอย','ซามอยด์','แจ็ค รัสเซลล์ เทอร์เรีย','เวลช์ คอร์กี้'];
const CAT_BREEDS = ['อะบิสซิเนียน','บริติช ช็อตแฮร์','แมวสีสวาด','เอ็กซ์โซติก ช็อตแฮร์','แมวขาวมณี','มันช์กิ้น','เมนคูน','อ็อกซี่แคท','นอร์วีเจียน ฟอเรสต์','เปอร์เซีย','รัสเซียนบลู','แร็กดอล','สก๊อตทิช โฟลด์','วิเชียรมาศ','สฟิงซ์','อเมริกันช็อตแฮร์','ออสเตรเลียน มิสต์','รากามัฟฟิน','ชินชิล่า','เบอร์มิลลา','เบอร์แมน','เบงกอล'];

let _currentPetId = null; // which pet is open in profile modal
let _formPetId    = null; // null=new, id=edit

// ───────────────────────────────────────────
// PROFILE POPUP
// ───────────────────────────────────────────
function openPetModal(petId) {
    const p = ALL_PETS.find(x => x.pet_id == petId);
    if (!p) return;
    _currentPetId = petId;

    const icon = TYPE_ICONS[p.pet_type] || 'mdi:paw';
    const avatarContent = p.pet_image
        ? `<img class="pet-photo" src="${p.pet_image}" alt="pet">`
        : `<span class="iconify" data-icon="${icon}" data-width="40"></span>`;
    document.getElementById('pmAvatar').innerHTML = avatarContent;
    document.getElementById('pmName').textContent = p.pet_name || '';
    document.getElementById('pmType').textContent =
        [p.pet_type, p.pet_breed].filter(Boolean).join(' · ');

    const gLabel = {male:'เพศชาย', female:'เพศหญิง'};
    const bdFormatted  = formatThaiDate(p.pet_birthday);
    const ageCalc      = calcAge(p.pet_birthday);
    const rows = [
        ['ประเภท',              p.pet_type,                              true],
        ['สายพันธุ์',           p.pet_breed,                             true],
        ['เพศ',                 gLabel[p.pet_gender] || p.pet_gender,   true],
        ['วันเกิด',             bdFormatted,                             false],  // ซ่อนถ้าว่าง
        ['อายุ',                ageCalc !== null ? ageCalc + ' ปี' : '', false],  // ซ่อนถ้าว่าง
        ['น้ำหนัก',             p.pet_weight ? p.pet_weight + ' กก.' : '', true],
        ['หยอดเห็บหมัดล่าสุด', p.flea_tick ? formatThaiDate(p.flea_tick) : '', false],
        ['เลขไมโครชิพ',        p.microship_number || '',                false],
        ['ฝังไมโครชิพล่าสุด',  p.microship_date ? formatThaiDate(p.microship_date) : '', false],
    ];
    document.getElementById('pmGrid').innerHTML = rows
        .filter(([k, v, required]) => required || v)   // ซ่อน row ที่ไม่ required และว่าง
        .map(([k, v]) =>
            `<div class="pet-modal-item">
                <div class="pet-modal-key">${k}</div>
                <div class="pet-modal-val${v ? '' : ' empty'}">${v || 'ไม่ระบุ'}</div>
            </div>`
        ).join('');

    const tags = (p.pet_behaviors||'').split(',').map(t=>t.trim()).filter(Boolean);
    document.getElementById('pmTags').innerHTML = tags.length
        ? tags.map(t=>`<span class="pet-modal-tag">${t}</span>`).join('')
        : '<span style="color:#94a3b8;font-size:14px;font-style:italic;">ยังไม่ได้ระบุ</span>';

    document.getElementById('petModalBg').classList.add('open');
    if (window.Iconify) Iconify.scan(document.getElementById('petModalBg'));
}
function closePetModal() {
    document.getElementById('petModalBg').classList.remove('open');
}
function editCurrentPet() {
    closePetModal();
    openFormModal(_currentPetId);
}

// ───────────────────────────────────────────
// FORM MODAL (build dynamically)
// ───────────────────────────────────────────
function buildBreedOptions(selectId) {
    const dogOpts = DOG_BREEDS.map(b=>
        `<div class="select-option breed-item" onclick="selectBreed('${selectId}','${b}')">${b}</div>`
    ).join('');
    const catOpts = CAT_BREEDS.map(b=>
        `<div class="select-option breed-item" onclick="selectBreed('${selectId}','${b}')">${b}</div>`
    ).join('');
    return `
        <div class="breed-type-header" onclick="toggleBreedGroup('${selectId}','dog')">
            <span>สุนัข</span>
            <span class="iconify breed-sub-arrow" data-icon="mdi:chevron-right" data-width="18"></span>
        </div>
        <div class="breed-sub-list" id="${selectId}-dog" style="display:none">${dogOpts}</div>
        <div class="breed-type-header" onclick="toggleBreedGroup('${selectId}','cat')">
            <span>แมว</span>
            <span class="iconify breed-sub-arrow" data-icon="mdi:chevron-right" data-width="18"></span>
        </div>
        <div class="breed-sub-list" id="${selectId}-cat" style="display:none">${catOpts}</div>`;
}

function openFormModal(petId) {
    _formPetId = petId;
    const p = petId ? ALL_PETS.find(x => x.pet_id == petId) : null;
    const uid = 'fm'; // unique prefix for form inputs

    document.getElementById('formModalTitle').innerHTML =
        p ? `<span class="iconify" data-icon="mdi:pencil"></span> แก้ไขข้อมูล: ${escapeHtml(p.pet_name)}` : 'เพิ่มสัตว์เลี้ยง';

    const gSel = `${uid}Gender`, brSel = `${uid}Breed`;
    const genderVal = p ? (p.pet_gender === 'male' ? 'เพศชาย' : 'เพศหญิง') : '';
    const breedVal  = p ? (p.pet_breed || '') : '';
    const savedT    = p ? (p.pet_behaviors||'').split(',').map(t=>t.trim()).filter(Boolean) : [];

    document.getElementById('formModalBody').innerHTML = `
        ${p ? `
        <div class="form-photo-section">
            <div class="form-photo-avatar" id="fmPhotoAvatar" onclick="document.getElementById('fmPhotoInput').click()">
                ${p.pet_image
                    ? `<img src="${esc(p.pet_image)}" alt="pet">`
                    : `<span class="iconify" data-icon="mdi:camera-plus" data-width="40" style="color:#64b5f6;"></span>`
                }
                <div class="form-photo-overlay">
                    <span class="iconify" data-icon="mdi:camera" data-width="28" style="color:#fff;"></span>
                    <span>${p.pet_image ? 'เปลี่ยนรูป' : 'เพิ่มรูป'}</span>
                </div>
            </div>
            <input type="file" id="fmPhotoInput" accept="image/*" style="display:none;"
                onchange="uploadFormPhoto(this, ${p.pet_id})">
            <div class="form-photo-hint">แตะที่รูปเพื่อ${p.pet_image ? 'เปลี่ยน' : 'เพิ่ม'}รูปภาพสัตว์เลี้ยง</div>
        </div>
        ` : ''}
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">ชื่อสัตว์เลี้ยง</label>
                <input type="text" id="${uid}Name" class="form-input"
                    placeholder="กรอกชื่อสัตว์เลี้ยงของคุณ"
                    value="${p ? esc(p.pet_name) : ''}">
            </div>
            <div class="form-group">
                <label class="form-label">เพศ</label>
                <div class="custom-select" id="${gSel}">
                    <div class="select-selected" onclick="toggleDropdown('${gSel}')">
                        <span class="select-placeholder${genderVal?' selected':''}">${genderVal||'เลือกเพศของสัตว์เลี้ยง'}</span>
                        <span class="iconify select-arrow" data-icon="mdi:chevron-down" data-width="22"></span>
                    </div>
                    <div class="select-options">
                        <div class="select-option" onclick="selectOption('${gSel}','เพศหญิง')">เพศหญิง</div>
                        <div class="select-option" onclick="selectOption('${gSel}','เพศชาย')">เพศชาย</div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">ประเภทสัตว์เลี้ยง <span class="label-note">(สำคัญ)</span></label>
                <div class="custom-select" id="${uid}TypeSel">
                    <div class="select-selected" onclick="toggleDropdown('${uid}TypeSel')">
                        <span class="select-placeholder${p&&p.pet_type?' selected':''}">${(p&&p.pet_type)?esc(p.pet_type):'เลือกประเภทสัตว์เลี้ยง'}</span>
                        <span class="iconify select-arrow" data-icon="mdi:chevron-down" data-width="22"></span>
                    </div>
                    <div class="select-options">
                        ${['สุนัข','แมว','กระต่าย','หนูแฮมสเตอร์','กระรอก','นกแก้ว','นกคอกคาเทล','เต่าญี่ปุ่น','งู','กิ้งก่า']
                            .map(t=>`<div class="select-option" onclick="selectOption('${uid}TypeSel','${t}')">${t}</div>`).join('')}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">วันเกิดสัตว์เลี้ยง</label>
                <input type="date" id="${uid}Birthday" class="form-input"
                    max="${new Date().toISOString().split('T')[0]}"
                    value="${p && p.pet_birthday && p.pet_birthday.split(' ')[0] !== '0000-00-00' ? p.pet_birthday.split(' ')[0] : ''}"
                    onchange="calcAgeFromBirthday('${uid}')">
                <div class="age-display" id="${uid}AgeDisplay" style="margin-top:6px;font-size:13px;color:#1e5a8a;font-weight:600;min-height:20px;">
                    ${(() => { const a = calcAge(p?.pet_birthday); return a !== null ? 'อายุ ' + a + ' ปี' : ''; })()}
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">สายพันธุ์</label>
                <div class="custom-select breed-select" id="${brSel}">
                    <div class="select-selected" onclick="toggleBreedDropdown('${brSel}')">
                        <span class="select-placeholder${breedVal?' selected':''}">${breedVal||'เลือกสายพันธุ์'}</span>
                        <span class="iconify select-arrow" data-icon="mdi:chevron-down" data-width="22"></span>
                    </div>
                    <div class="select-options breed-options">${buildBreedOptions(brSel)}</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">น้ำหนัก <span class="label-note">(สำคัญ)</span></label>
                <div class="weight-input-wrap">
                    <input type="number" id="${uid}Weight" class="form-input"
                        placeholder="กรอกน้ำหนัก" min="0" max="200"
                        value="${p ? esc(String(p.pet_weight||'')) : ''}">
                    <span class="weight-unit">กก.</span>
                </div>
            </div>
            <div class="form-group form-group--full">
                <label class="form-label">หยอดเห็บหมัดล่าสุด</label>
                <input type="date" id="${uid}FleaTick" class="form-input"
                    max="${new Date().toISOString().split('T')[0]}"
                    value="${p && p.flea_tick ? p.flea_tick.split(' ')[0] : ''}">
            </div>
            <div class="form-group">
                <label class="form-label">หมายเลขไมโครชิพ</label>
                <input type="text" id="${uid}MicroNum" class="form-input"
                    placeholder="กรอกหมายเลขไมโครชิพ 15 หลัก"
                    maxlength="20"
                    value="${p ? esc(p.microship_number||'') : ''}">
            </div>
            <div class="form-group">
                <label class="form-label">วันที่ฝังไมโครชิพล่าสุด</label>
                <input type="date" id="${uid}MicroDate" class="form-input"
                    max="${new Date().toISOString().split('T')[0]}"
                    value="${p && p.microship_date ? p.microship_date.split(' ')[0] : ''}">
            </div>
        </div>

        <div class="form-behavior-section">
            <div class="form-behavior-label">ลักษณะนิสัย</div>
            <div class="form-tags" id="${uid}Tags">
                ${ALL_TAGS.map(t=>`<button type="button" class="form-tag${savedT.includes(t)?' active':''}" onclick="this.classList.toggle('active')">${t}</button>`).join('')}
            </div>
        </div>

        <div class="save-row" style="margin-top:28px;">
            <button class="btn-save" onclick="submitFormModal()">บันทึกข้อมูล</button>
        </div>`;

    document.getElementById('formModalBg').classList.add('open');
    if (window.Iconify) Iconify.scan(document.getElementById('formModal'));
}
function closeFormModal() {
    document.getElementById('formModalBg').classList.remove('open');
}

async function submitFormModal() {
    const uid = 'fm';
    const name     = document.getElementById(`${uid}Name`)?.value.trim() || '';
    const tEl      = document.querySelector(`#${uid}TypeSel .select-placeholder`);
    const type     = tEl?.classList.contains('selected') ? tEl.textContent.trim() : '';
    const birthday = document.getElementById(`${uid}Birthday`)?.value || '';
    const weight   = document.getElementById(`${uid}Weight`)?.value.trim() || '';
    const fleaTick = document.getElementById(`${uid}FleaTick`)?.value || '';
    const microNum = document.getElementById(`${uid}MicroNum`)?.value.trim() || '';
    const microDate= document.getElementById(`${uid}MicroDate`)?.value || '';

    const gEl = document.querySelector(`#${uid}Gender .select-placeholder`);
    const gender = gEl?.classList.contains('selected')
        ? (gEl.textContent.trim() === 'เพศชาย' ? 'male' : 'female') : '';
    const bEl = document.querySelector(`#${uid}Breed .select-placeholder`);
    const breed = bEl?.classList.contains('selected') ? bEl.textContent.trim() : '';

    const tags = [...document.querySelectorAll(`#${uid}Tags .form-tag.active`)]
        .map(b => b.textContent.trim()).join(', ');

    if (!name) { showToast('กรุณากรอกชื่อสัตว์เลี้ยง', 'error'); return; }
    if (!type) { showToast('กรุณากรอกประเภทสัตว์เลี้ยง', 'error'); return; }

    const fd = new FormData();
    if (_formPetId) fd.append('pet_id', _formPetId);
    fd.append('pet_name', name); fd.append('pet_type', type);
    fd.append('pet_breed', breed); fd.append('pet_gender', gender);
    fd.append('pet_birthday', birthday); fd.append('pet_weight', weight);
    fd.append('pet_behaviors', tags);
    fd.append('flea_tick', fleaTick);
    fd.append('microship_number', microNum);
    fd.append('microship_date', microDate);

    try {
        const res  = await fetch('savepet.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('บันทึกข้อมูลสำเร็จ!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('เกิดข้อผิดพลาด: ' + (data.message || ''), 'error');
        }
    } catch(e) {
        showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
}

// ───────────────────────────────────────────
// MAIN FORM (shown when 0 pets)
// ───────────────────────────────────────────
function savePetFromMain() {
    _formPetId = null;
    const name     = document.getElementById('petNameInput')?.value.trim() || '';
    const tEl2     = document.querySelector('#typeSelect1 .select-placeholder');
    const type     = tEl2?.classList.contains('selected') ? tEl2.textContent.trim() : '';
    const birthday = document.getElementById('petBirthdayInput')?.value || '';
    const weight   = document.getElementById('petWeightInput')?.value.trim() || '';
    const fleaTick = document.getElementById('petFleaTickInput')?.value || '';
    const microNum = document.getElementById('petMicroNumInput')?.value.trim() || '';
    const microDate= document.getElementById('petMicroDateInput')?.value || '';
    const gEl      = document.querySelector('#genderSelect1 .select-placeholder');
    const gender   = gEl?.classList.contains('selected')
        ? (gEl.textContent.trim() === 'เพศชาย' ? 'male' : 'female') : '';
    const bEl      = document.querySelector('#breedSelect1 .select-placeholder');
    const breed    = bEl?.classList.contains('selected') ? bEl.textContent.trim() : '';
    const tags     = [...document.querySelectorAll('#behaviorTags .tag-btn--active')]
        .map(b => b.textContent.trim()).join(', ');

    if (!name) { showToast('กรุณากรอกชื่อสัตว์เลี้ยง', 'error'); return; }
    if (!type) { showToast('กรุณากรอกประเภทสัตว์เลี้ยง', 'error'); return; }

    const fd = new FormData();
    fd.append('pet_name', name); fd.append('pet_type', type);
    fd.append('pet_breed', breed); fd.append('pet_gender', gender);
    fd.append('pet_birthday', birthday); fd.append('pet_weight', weight);
    fd.append('pet_behaviors', tags);
    fd.append('flea_tick', fleaTick);
    fd.append('microship_number', microNum);
    fd.append('microship_date', microDate);

    fetch('savepet.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast('บันทึกสำเร็จ! <span class="iconify" data-icon="mdi:paw"></span>', 'success'); setTimeout(() => location.reload(), 1200); }
            else showToast('เกิดข้อผิดพลาด: ' + (data.message||''), 'error');
        })
        .catch(() => showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error'));
}



// ───────────────────────────────────────────
// SHARED DROPDOWN HELPERS
// ───────────────────────────────────────────
function toggleDropdown(id) {
    const el = document.getElementById(id);
    document.querySelectorAll('.custom-select').forEach(s => { if (s.id !== id) s.classList.remove('open'); });
    el.classList.toggle('open');
}
function selectOption(id, value) {
    const el = document.getElementById(id);
    const ph = el.querySelector('.select-placeholder');
    ph.textContent = value; ph.classList.add('selected');
    el.classList.remove('open');
}
function toggleBreedDropdown(id) {
    const el = document.getElementById(id);
    document.querySelectorAll('.custom-select').forEach(s => { if (s.id !== id) s.classList.remove('open'); });
    el.classList.toggle('open');
}
function toggleBreedGroup(selectId, group) {
    const list = document.getElementById(selectId + '-' + group);
    const arrow = list.previousElementSibling.querySelector('.breed-sub-arrow');
    const isOpen = list.style.display === 'block';
    list.style.display = isOpen ? 'none' : 'block';
    if (arrow) arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
}
function selectBreed(selectId, value) {
    const el = document.getElementById(selectId);
    const ph = el.querySelector('.select-placeholder');
    ph.textContent = value; ph.classList.add('selected');
    el.classList.remove('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.custom-select'))
        document.querySelectorAll('.custom-select').forEach(s => s.classList.remove('open'));
});

function toggleTag(btn) { btn.classList.toggle('tag-btn--active'); }

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// Age calculation helpers
function calcAge(birthday) {
    if (!birthday) return null;
    const raw = birthday.split(' ')[0];
    if (!raw || raw === '0000-00-00') return null;   // ← ไม่มีวันเกิด
    const bd = new Date(raw);
    if (isNaN(bd)) return null;
    const now = new Date();
    let age = now.getFullYear() - bd.getFullYear();
    const m = now.getMonth() - bd.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < bd.getDate())) age--;
    return Math.max(0, age);
}

function calcAgeFromBirthday(inputId, displayId) {
    // Called from static form: calcAgeFromBirthday('main', 'mainAgeDisplay') → input id = petBirthdayInput
    // Called from modal form: calcAgeFromBirthday('fm') → input id = fmBirthday, display id = fmAgeDisplay
    let inputEl, dispEl;
    if (displayId) {
        // static form (first-pet card)
        inputEl = document.getElementById('petBirthdayInput');
        dispEl  = document.getElementById(displayId);
    } else {
        // modal form
        inputEl = document.getElementById(`${inputId}Birthday`);
        dispEl  = document.getElementById(`${inputId}AgeDisplay`);
    }
    if (!dispEl) return;
    const val = inputEl?.value;
    if (val) {
        const age = calcAge(val);
        dispEl.textContent = age !== null ? `อายุ ${age} ปี` : '';
    } else {
        dispEl.textContent = '';
    }
}

function formatThaiDate(dateStr) {
    if (!dateStr) return '';
    const raw = dateStr.split(' ')[0];
    if (!raw || raw === '0000-00-00') return '';      // ← ไม่มีวันที่
    const d = new Date(raw);
    if (isNaN(d)) return '';
    const thMonths = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return `${d.getDate()} ${thMonths[d.getMonth()]} ${d.getFullYear() + 543}`;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function showToast(msg, type) {
    const t = document.getElementById('petToast');
    t.innerHTML = msg;
    t.className = 'pet-toast ' + (type === 'error' ? 'te' : 'ts') + ' show';
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 2800);
}

// ───────────────────────────────────────────
// PET PHOTO UPLOAD
// ───────────────────────────────────────────
function triggerPhotoUpload(petId) {
    document.getElementById('photoInput_' + petId)?.click();
}

async function uploadPetPhoto(petId, input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { showToast('ไฟล์ต้องไม่เกิน 5MB', 'error'); return; }

    const fd = new FormData();
    fd.append('pet_id', petId);
    fd.append('pet_image', file);

    showToast('กำลังอัปโหลด...', 'success');
    try {
        const res  = await fetch('uploadpetimage.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('อัปโหลดรูปสำเร็จ! ', 'success');
            // Update bubble avatar immediately
            const bubble = input.closest('.pet-bubble-avatar');
            let img = bubble.querySelector('img.pet-photo');
            if (!img) {
                img = document.createElement('img');
                img.className = 'pet-photo';
                bubble.insertBefore(img, bubble.firstChild);
                bubble.querySelector('.iconify')?.remove();
            }
            img.src = data.image_url + '?t=' + Date.now();
            // Update ALL_PETS cache
            const p = ALL_PETS.find(x => x.pet_id == petId);
            if (p) p.pet_image = data.image_url;
            // If modal is open for this pet, refresh avatar
            if (_currentPetId == petId) {
                const av = document.getElementById('pmAvatar');
                let mImg = av.querySelector('img.pet-photo');
                if (!mImg) {
                    mImg = document.createElement('img');
                    mImg.className = 'pet-photo';
                    av.querySelector('.iconify')?.remove();
                    av.insertBefore(mImg, av.firstChild);
                }
                mImg.src = data.image_url + '?t=' + Date.now();
            }
        } else {
            showToast('อัปโหลดไม่สำเร็จ: ' + (data.message || ''), 'error');
        }
    } catch(e) {
        showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
    input.value = '';
}

function uploadPetPhotoFromModal(input) {
    if (_currentPetId) uploadPetPhoto(_currentPetId, input);
}

async function uploadFormPhoto(input, petId) {
    const file = input.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('pet_id', petId);
    fd.append('pet_image', file);
    showToast('กำลังอัปโหลด...', 'success');
    try {
        const res  = await fetch('uploadpetimage.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success && data.image_url) {
            showToast('อัปโหลดรูปสำเร็จ!', 'success');
            // Update preview inside edit form
            const av = document.getElementById('fmPhotoAvatar');
            if (av) {
                av.innerHTML = `<img src="${data.image_url}?t=${Date.now()}" alt="pet">
                    <div class="form-photo-overlay">
                        <span class="iconify" data-icon="mdi:camera" data-width="28" style="color:#fff;"></span>
                        <span>เปลี่ยนรูป</span>
                    </div>`;
            }
            // Update in-memory pet data
            const p = ALL_PETS.find(x => x.pet_id == petId);
            if (p) p.pet_image = data.image_url;
        } else {
            console.error('Upload error:', data);
            showToast('<span class="iconify" data-icon="mdi:close-circle"></span> ' + (data.message || 'อัปโหลดไม่สำเร็จ'), 'error');
        }
    } catch(e) {
        showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
    input.value = '';
}
</script>

<?php
// Helper: build the static form HTML for card #1 (no pets yet)
function buildFormHTML($pet, $allTags, $idx) {
    $pName   = hv($pet['pet_name']   ?? '');
    $pType   = hv($pet['pet_type']   ?? '');
    $rawBd   = $pet['pet_birthday'] ?? '';
    $pBirthday = ($rawBd && $rawBd !== '0000-00-00') ? hv($rawBd) : '';
    $pOld    = hv($pet['pet_old']    ?? '');
    $pWeight = hv($pet['pet_weight'] ?? '');
    $pGender = $pet['pet_gender'] ?? '';
    $pBreed  = hv($pet['pet_breed']  ?? '');
    $savedTags = array_filter(array_map('trim', explode(',', $pet['pet_behaviors'] ?? '')));
    $gSel = "genderSelect{$idx}";
    $bSel = "breedSelect{$idx}";
    ob_start(); ?>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">ชื่อสัตว์เลี้ยง</label>
            <input type="text" id="petNameInput" class="form-input" placeholder="กรอกชื่อสัตว์เลี้ยงของคุณ" value="<?= $pName ?>">
        </div>
        <div class="form-group">
            <label class="form-label">เพศ</label>
            <div class="custom-select" id="<?= $gSel ?>">
                <div class="select-selected" onclick="toggleDropdown('<?= $gSel ?>')">
                    <span class="select-placeholder<?= $pGender?' selected':'' ?>"><?= $pGender?($pGender==='male'?'เพศชาย':'เพศหญิง'):'เลือกเพศของสัตว์เลี้ยง' ?></span>
                    <span class="iconify select-arrow" data-icon="mdi:chevron-down" data-width="22"></span>
                </div>
                <div class="select-options">
                    <div class="select-option" onclick="selectOption('<?= $gSel ?>','เพศหญิง')">เพศหญิง</div>
                    <div class="select-option" onclick="selectOption('<?= $gSel ?>','เพศชาย')">เพศชาย</div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">ประเภทสัตว์เลี้ยง <span class="label-note">(สำคัญ)</span></label>
            <div class="custom-select" id="typeSelect1">
                <div class="select-selected" onclick="toggleDropdown('typeSelect1')">
                    <span class="select-placeholder<?= $pType?' selected':'' ?>"><?= $pType?:' เลือกประเภทสัตว์เลี้ยง' ?></span>
                    <span class="iconify select-arrow" data-icon="mdi:chevron-down" data-width="22"></span>
                </div>
                <div class="select-options">
                    <?php foreach(['สุนัข','แมว','กระต่าย','หนูแฮมสเตอร์','กระรอก','นกแก้ว','นกคอกคาเทล','เต่าญี่ปุ่น','งู','กิ้งก่า'] as $t): ?>
                    <div class="select-option" onclick="selectOption('typeSelect1','<?= $t ?>')"><?= $t ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">วันเกิดสัตว์เลี้ยง</label>
            <input type="date" id="petBirthdayInput" class="form-input"
                max="<?= date('Y-m-d') ?>"
                value="<?= $pBirthday ?>"
                onchange="calcAgeFromBirthday('main', 'mainAgeDisplay')">
            <div id="mainAgeDisplay" style="margin-top:6px;font-size:13px;color:#1e5a8a;font-weight:600;min-height:20px;">
                <?php if ($pBirthday):
                    $bd = new DateTime($pBirthday);
                    $age = (int)$bd->diff(new DateTime())->y;
                    echo '<span class="iconify" data-icon="mdi:cake-variant"></span> อายุ ' . $age . ' ปี';
                endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">สายพันธุ์</label>
            <div class="custom-select breed-select" id="<?= $bSel ?>">
                <div class="select-selected" onclick="toggleBreedDropdown('<?= $bSel ?>')">
                    <span class="select-placeholder<?= $pBreed?' selected':'' ?>"><?= $pBreed?:'เลือกสายพันธุ์' ?></span>
                    <span class="iconify select-arrow" data-icon="mdi:chevron-down" data-width="22"></span>
                </div>
                <div class="select-options breed-options">
                    <div class="breed-type-header" onclick="toggleBreedGroup('<?= $bSel ?>','dog')">
                        <span>สุนัข</span>
                        <span class="iconify breed-sub-arrow" data-icon="mdi:chevron-right" data-width="18"></span>
                    </div>
                    <div class="breed-sub-list" id="<?= $bSel ?>-dog" style="display:none">
                        <?php foreach(['อัฟกัน ฮาวด์','อลาสกัน มาลามิวท์','บาเซนจิ','บีเกิ้ล','บิชอง ฟริเซ่','บูลด็อก','คาวาเลียร์ คิง ชาลส์ สแปเนียล','ชิวาวา','เชาเชา','บอร์เดอร์ คอลลี่','ดัชชุน','เฟรนช์ บูลด็อก','เยอรมันเชพเพิร์ด','เกรย์ฮาวด์','ชิบะ','มอลทีส','ปอมเมอเรเนียน','พุดเดิ้ลทอย','ปั๊ก','โกลเด้น รีทริฟเวอร์','ลาบราดอร์ รีทรีฟเวอร์','เชทแลนด์ ชีพด็อก','ชิสุห์','ไซบีเรียน ฮัสกี','ยอร์กเชียร์ เทอร์เรียร์','บูล เทอร์เรียร์','ลาซา แอพโช','บอร์ซอย','ซามอยด์','แจ็ค รัสเซลล์ เทอร์เรีย','เวลช์ คอร์กี้'] as $b): ?>
                        <div class="select-option breed-item" onclick="selectBreed('<?= $bSel ?>','<?= hv($b) ?>')"><?= hv($b) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="breed-type-header" onclick="toggleBreedGroup('<?= $bSel ?>','cat')">
                        <span>แมว</span>
                        <span class="iconify breed-sub-arrow" data-icon="mdi:chevron-right" data-width="18"></span>
                    </div>
                    <div class="breed-sub-list" id="<?= $bSel ?>-cat" style="display:none">
                        <?php foreach(['อะบิสซิเนียน','บริติช ช็อตแฮร์','แมวสีสวาด','เอ็กซ์โซติก ช็อตแฮร์','แมวขาวมณี','มันช์กิ้น','เมนคูน','อ็อกซี่แคท','นอร์วีเจียน ฟอเรสต์','เปอร์เซีย','รัสเซียนบลู','แร็กดอล','สก๊อตทิช โฟลด์','วิเชียรมาศ','สฟิงซ์','อเมริกันช็อตแฮร์','ออสเตรเลียน มิสต์','รากามัฟฟิน','ชินชิล่า','เบอร์มิลลา','เบอร์แมน','เบงกอล'] as $b): ?>
                        <div class="select-option breed-item" onclick="selectBreed('<?= $bSel ?>','<?= hv($b) ?>')"><?= hv($b) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">น้ำหนัก <span class="label-note">(สำคัญ)</span></label>
            <div class="weight-input-wrap">
                <input type="number" id="petWeightInput" class="form-input" placeholder="กรอกน้ำหนัก" min="0" value="<?= $pWeight ?>">
                <span class="weight-unit">กก.</span>
            </div>
        </div>
        <div class="form-group form-group--full">
            <label class="form-label">หยอดเห็บหมัดล่าสุด</label>
            <input type="date" id="petFleaTickInput" class="form-input"
                max="<?= date('Y-m-d') ?>"
                value="<?= hv($pet['flea_tick'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">หมายเลขไมโครชิพ</label>
            <input type="text" id="petMicroNumInput" class="form-input"
                placeholder="กรอกหมายเลขไมโครชิพ 15 หลัก" maxlength="20"
                value="<?= hv($pet['microship_number'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">วันที่ฝังไมโครชิพล่าสุด</label>
            <input type="date" id="petMicroDateInput" class="form-input"
                max="<?= date('Y-m-d') ?>"
                value="<?= hv($pet['microship_date'] ?? '') ?>">
        </div>
    </div>

    <!-- Behavior tags inside the same card -->
    <div class="form-behavior-section">
        <div class="form-behavior-label">ลักษณะนิสัย</div>
        <div class="form-tags" id="behaviorTags">
            <?php foreach ($allTags as $tag):
                $active = in_array($tag, $savedTags) ? ' active' : '';
            ?>
            <button type="button" class="form-tag<?= $active ?>" onclick="this.classList.toggle('active')"><?= hv($tag) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="save-row">
        <button class="btn-save" id="btnSaveForm" onclick="savePetFromMain()">บันทึกข้อมูล</button>
    </div>
    <?php
    return ob_get_clean();
}
?>
</body>
</html>