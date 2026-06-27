<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: form-login.php?auth=required');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนทรีปของคุณเลย!! - Pawland</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="plantrip.css">
    <style>
        .trip-name-wrap{
            margin-bottom:32px}
        .trip-name-input{
            width:100%;max-width:480px;padding:14px 20px;font-family:'Kanit',sans-serif;font-size:18px;font-weight:500;color:#1e293b;background:#fff;border:2px solid #d4e7f7;border-radius:14px;outline:none;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:border-color .2s}
        .trip-name-input:focus{border-color:#4a90c4}
        .trip-name-input::placeholder{color:#94a3b8}
        /* search */
        .search-results{margin-top:16px;margin-bottom:8px;display:none}
        .src-card{background:#fff;border-radius:14px;padding:14px 18px;margin-bottom:10px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 8px rgba(0,0,0,.07);transition:all .2s}
        .src-card:hover{box-shadow:0 4px 14px rgba(0,0,0,.12);transform:translateY(-1px)}
        .src-thumb{width:60px;height:60px;border-radius:10px;object-fit:cover;flex-shrink:0}
        .src-thumb-ph{width:60px;height:60px;border-radius:10px;background:#2c4a63;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff}
        .src-info{flex:1;min-width:0}
        .src-name{font-size:15px;font-weight:600;color:#1e293b;text-decoration:none;display:block}
        .src-name:hover{color:#123451;text-decoration:underline}
        .src-meta{font-size:12px;color:#64748b;margin-top:3px}
        .src-cat{display:inline-block;font-size:11px;padding:2px 8px;background:#e0f0ff;color:#123451;border-radius:20px;font-weight:500;margin-top:4px}
        .src-add-btn{flex-shrink:0;background:#123451;color:#fff;border:none;border-radius:10px;cursor:pointer;padding:8px 16px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:500;transition:all .2s;white-space:nowrap}
        .src-add-btn:hover{background:#1e4f77}
        .src-add-btn.added{background:#16a34a;cursor:default}
        .search-loading{text-align:center;padding:20px;color:#94a3b8;display:none}
        .no-results{text-align:center;padding:32px 20px;color:#94a3b8;font-size:15px;display:none}
        /* inline date picker */
        .inline-picker{background:#fff;border-radius:14px;padding:20px 24px;margin:8px 0 16px;box-shadow:0 4px 16px rgba(0,0,0,.12);border:2px solid #d4e7f7}
        .inline-picker h4{font-size:15px;font-weight:600;color:#123451;margin-bottom:14px}
        .pk-row{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
        .pk-row label{font-size:12px;color:#64748b;display:block;margin-bottom:4px}
        .pk-row input[type=date]{padding:8px 12px;border:1.5px solid #d4e7f7;border-radius:10px;font-family:'Kanit',sans-serif;font-size:14px;outline:none}
        .btn-confirm{background:#123451;color:#fff;border:none;border-radius:10px;padding:9px 20px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:500;cursor:pointer}
        .btn-confirm:hover{background:#1e4f77}
        .btn-cancel{background:#f1f5f9;color:#1e293b;border:none;border-radius:10px;padding:9px 16px;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer}
        .btn-use-same{background:#e0f7f0;color:#0f5c3a;border:1.5px solid #6fcf97;border-radius:10px;padding:9px 18px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:500;cursor:pointer}
        /* plan list */
        .added-places-section{margin-bottom:32px}
        .added-places-title{font-size:22px;font-weight:700;color:#1e293b;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .plan-day-group{margin-bottom:28px}
        .plan-day-label{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:700;color:#123451;background:#e0f0ff;border-radius:10px;padding:7px 16px;margin-bottom:14px}
        .ap-card{background:#fff;border-radius:16px;padding:16px 20px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(0,0,0,.08);gap:12px;border-left:4px solid #123451;transition:box-shadow .2s}
        .ap-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12)}
        .ap-left{display:flex;align-items:center;gap:14px;flex:1;min-width:0}
        .ap-badge{width:28px;height:28px;background:#123451;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0}
        .ap-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;flex-shrink:0}
        .ap-icon{width:52px;height:52px;background:#2c4a63;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff}
        .ap-name{font-size:16px;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .ap-date{font-size:13px;color:#64748b;margin-top:3px;display:flex;align-items:center;gap:5px}
        .ap-rm{background:#fee2e2;border:none;border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#ef4444;transition:all .2s;flex-shrink:0}
        .ap-rm:hover{background:#ef4444;color:#fff}
        .empty-plan{text-align:center;padding:48px 20px;color:#94a3b8;font-size:16px}
        .empty-plan-icon{font-size:56px;margin-bottom:14px}
        .save-plan-btn{width:100%;max-width:440px;padding:16px 32px;font-family:'Kanit',sans-serif;font-size:20px;font-weight:600;color:#fff;background:#123451;border:none;border-radius:16px;cursor:pointer;transition:all .3s;box-shadow:0 4px 12px rgba(0,0,0,.2);display:block;margin:8px auto 0}
        .save-plan-btn:hover{background:#1e4f77;transform:translateY(-2px)}
        .sec-divider{border:none;border-top:2px dashed #d4e7f7;margin:44px 0}
        .saved-plans-title{font-size:22px;font-weight:700;color:#1e293b;margin-bottom:18px}
        .sv-card{background:#fff;border-radius:16px;padding:20px 24px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,.08);border-left:4px solid #64b5f6;transition:all .2s}
        .sv-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12)}
        .sv-name{font-size:17px;font-weight:600;color:#1e293b}
        .sv-meta{font-size:13px;color:#64748b;margin-top:4px}
        .sv-actions{display:flex;gap:8px;flex-shrink:0}
        .ic-btn{background:#f1f5f9;border:none;border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#1e293b;transition:all .2s}
        .ic-btn:hover{background:#e2e8f0}
        .ic-btn.del:hover{background:#fee2e2;color:#ef4444}
        .modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s}
        .modal-bg.open{opacity:1;pointer-events:all}
        .modal-box{background:#fff;border-radius:24px;padding:0;width:95%;max-width:820px;max-height:92vh;display:flex;flex-direction:column;transform:translateY(24px);transition:transform .25s;box-shadow:0 24px 80px rgba(0,0,0,.25);overflow:hidden}
        .modal-bg.open .modal-box{transform:translateY(0)}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:24px 28px 18px;border-bottom:1px solid #e8f0f8;flex-shrink:0}
        .modal-title{font-size:22px;font-weight:700;color:#123451}
        .modal-title-sub{font-size:13px;font-weight:400;color:#64748b;margin-top:3px}
        .modal-close{background:#f1f5f9;border:none;border-radius:50%;width:36px;height:36px;font-size:18px;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0}
        .modal-close:hover{background:#fee2e2;color:#ef4444}
        .modal-scroll{flex:1;overflow-y:auto;padding:0}
        #modal-export-area{padding:24px 28px}
        /* ── Trip Header ── */
        .trip-header-card{background:linear-gradient(135deg,#123451 0%,#1e5a8a 100%);border-radius:16px;padding:22px 24px;margin-bottom:28px;color:#fff}
        .trip-header-card h2{font-size:22px;font-weight:700;margin-bottom:6px}
        .trip-header-meta{display:flex;gap:20px;font-size:13px;opacity:.85;flex-wrap:wrap}
        .trip-header-meta span{display:flex;align-items:center;gap:5px}

        /* ── Timeline wrapper ── */
        .tl-wrap{padding:0 0 8px 0}

        /* ── Day group ── */
        .tl-day{position:relative;margin-bottom:0;padding-left:52px}

        /* vertical dashed line running through entire day block */
        .tl-day::before{
            content:'';
            position:absolute;
            left:19px;top:0;bottom:0;
            width:2px;
            border-left:2.5px dashed #93c5e8;
        }
        /* last day: line only goes to the last dot, not beyond */
        .tl-day:last-child::before{bottom:auto;height:36px}

        /* ── Date badge row ── */
        .tl-date-row{
            display:flex;align-items:center;gap:12px;
            margin-bottom:14px;position:relative;
        }
        /* big dot on the line */
        .tl-dot{
            position:absolute;left:-43px;top:50%;transform:translateY(-50%);
            width:14px;height:14px;border-radius:50%;
            background:#123451;border:3px solid #93c5e8;
            box-shadow:0 0 0 4px #dbeeff;
            flex-shrink:0;z-index:1;
        }
        .tl-date-badge{
            background:#123451;color:#fff;
            border-radius:12px;padding:6px 16px;
            display:flex;flex-direction:column;align-items:center;
            min-width:68px;flex-shrink:0;
        }
        .tl-date-month{font-size:11px;font-weight:500;opacity:.8;letter-spacing:.04em}
        .tl-date-day{font-size:22px;font-weight:700;line-height:1.1}
        .tl-date-label{font-size:14px;font-weight:600;color:#1e293b}
        .tl-count{margin-left:auto;font-size:12px;color:#94a3b8;white-space:nowrap}

        /* ── Place card ── */
        .tl-places{margin-bottom:20px}
        .tl-card{
            background:#fff;border-radius:14px;
            padding:14px 16px;margin-bottom:10px;
            display:flex;align-items:flex-start;gap:14px;
            box-shadow:0 2px 10px rgba(18,52,81,.08);
            border:1px solid #e8f0f8;
            transition:box-shadow .2s,transform .15s;
            position:relative;
        }
        .tl-card:hover{box-shadow:0 6px 20px rgba(18,52,81,.13);transform:translateY(-1px)}

        /* connector stub from line to card */
        .tl-card::before{
            content:'';
            position:absolute;
            left:-33px;top:24px;
            width:33px;height:2px;
            border-top:2px dashed #c7dff0;
        }

        .tl-num{
            width:28px;height:28px;border-radius:50%;
            background:#e8f4ff;color:#123451;
            font-size:12px;font-weight:700;
            display:flex;align-items:center;justify-content:center;
            flex-shrink:0;border:1.5px solid #b0d4ee;
        }
        .tl-thumb{width:64px;height:64px;border-radius:10px;object-fit:cover;flex-shrink:0}
        .tl-thumb-ph{
            width:64px;height:64px;border-radius:10px;
            background:linear-gradient(135deg,#1e5a8a,#2c7ab5);
            flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff;
        }
        .tl-info{flex:1;min-width:0}
        .tl-name{font-size:15px;font-weight:700;color:#1e293b;margin-bottom:6px}
        .tl-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:4px}
        .tl-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500}
        .tl-chip.date{background:#dbeeff;color:#1e5a8a}
        .tl-chip.nights{background:#fef3c7;color:#92400e}
        .tl-chip.cat{background:#f0fdf4;color:#166534}
        .tl-chip.prov{background:#fdf4ff;color:#7e22ce}
        .tl-addr{font-size:11px;color:#94a3b8;margin-top:3px;display:flex;align-items:center;gap:4px}

        /* modal-pr (edit form) stays unchanged */
        .modal-pr{display:flex;align-items:flex-start;gap:14px;padding:14px 16px;border-radius:12px;background:#f8fbff;margin-bottom:8px;border:1px solid #e8f0f8;transition:box-shadow .2s}
        .modal-pr:hover{box-shadow:0 3px 12px rgba(0,0,0,.08)}
        .modal-num{width:32px;height:32px;background:#123451;border-radius:50%;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
        .modal-pthumb{width:64px;height:64px;border-radius:10px;object-fit:cover;flex-shrink:0}
        .modal-pthumb-ph{width:64px;height:64px;border-radius:10px;background:#2c4a63;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff}
        .modal-day-block{margin-bottom:20px}
        .modal-dh{font-size:14px;font-weight:700;color:#123451;background:#f0f7ff;border-radius:10px;padding:10px 16px;margin:0 0 10px;display:flex;align-items:center;gap:8px;border-left:4px solid #4a90c4}
        .modal-pinfo{flex:1;min-width:0}
        .modal-pn{font-size:16px;font-weight:600;color:#1e293b}
        .modal-pd{font-size:12px;color:#64748b;margin-top:4px;display:flex;flex-wrap:wrap;gap:8px}
        .modal-pd-chip{display:inline-flex;align-items:center;gap:4px;background:#e0f0ff;color:#123451;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:500}
        .modal-pd-chip.nights{background:#fef3c7;color:#92400e}
        .modal-pd-chip.cat{background:#f0fdf4;color:#166534}
        .modal-en{width:100%;padding:12px 16px;font-family:'Kanit',sans-serif;font-size:16px;border:2px solid #d4e7f7;border-radius:12px;outline:none;margin-bottom:14px}
        .modal-foot{display:flex;gap:10px;padding:18px 28px;border-top:1px solid #e8f0f8;flex-shrink:0;flex-wrap:wrap}
        .modal-btns{display:flex;gap:10px;flex:1;flex-wrap:wrap}
        .modal-btn{flex:1;min-width:110px;padding:13px;font-family:'Kanit',sans-serif;font-size:15px;font-weight:600;border:none;border-radius:12px;cursor:pointer;transition:all .2s}
        .mb-p{background:#123451;color:#fff}.mb-p:hover{background:#1e4f77}
        .mb-s{background:#f1f5f9;color:#1e293b;border:1.5px solid #e2e8f0}.mb-s:hover{background:#e2e8f0}
        .export-btns{display:flex;gap:8px;flex-shrink:0}
        .exp-btn{display:flex;align-items:center;gap:6px;padding:10px 16px;font-family:'Kanit',sans-serif;font-size:14px;font-weight:600;border:none;border-radius:10px;cursor:pointer;transition:all .2s;white-space:nowrap}
        .exp-img{background:#0ea5e9;color:#fff}.exp-img:hover{background:#0284c7}
        .exp-pdf{background:#ef4444;color:#fff}.exp-pdf:hover{background:#dc2626}
        .exp-loading{opacity:.65;pointer-events:none}
        @media(max-width:600px){.modal-foot{flex-direction:column}.export-btns{width:100%}.exp-btn{flex:1;justify-content:center}.modal-btns{width:100%}}
        .toast{position:fixed;bottom:28px;right:28px;z-index:9999;padding:14px 22px;border-radius:12px;font-family:'Kanit',sans-serif;font-size:15px;font-weight:500;display:flex;align-items:center;gap:8px;transform:translateY(60px);opacity:0;transition:all .3s cubic-bezier(.34,1.56,.64,1);box-shadow:0 6px 20px rgba(0,0,0,.15);pointer-events:none}
        .toast.show{transform:translateY(0);opacity:1}
        .ts{background:#123451;color:#fff}
        .te{background:#ef4444;color:#fff}
        .category-btn.active{background:#1e4f77;box-shadow:0 0 0 3px rgba(100,181,246,.5)}
    </style>
</head>
<body>
<header class="header">
    <div class="container"><div class="header-content">
        <div class="logo"><img src="logo.png" alt="Logo" width="136" height="136"></div>
        <nav class="nav">
            <a href="home.php" class="nav-link">หน้าแรก</a>
            <a href="plantrip.php" class="nav-link">แผนเที่ยว</a>
            <a href="petinfo.php" class="nav-link">ข้อมูลสัตว์เลี้ยง</a>
            <a href="nearme.php" class="nav-link">ใกล้ฉัน</a>
        </nav>
        <div class="header-right">
            <div class="language-switch"><span class="lang-active">TH</span><span class="lang-separator">|</span><span class="lang-inactive">EN</span></div>
            <?php include 'header_user_icon.php'; ?>
        </div>
    </div></div>
</header>

<div class="container"><div class="main-container">
    <h1 class="page-title">แพลนทริปของคุณเลย!!</h1>

    <div class="trip-name-wrap">
        <input type="text" class="trip-name-input" id="tripName" placeholder="กรอกชื่อทริปของคุณ...">
    </div>

    <div class="category-section">
        <h2 class="section-title">ค้นหาและเพิ่มสถานที่</h2>
        <div class="category-buttons">
            <button class="category-btn" onclick="filterCat(this,'โรงแรม')">
                <span class="iconify" data-icon="fa6-solid:hotel" data-width="64" data-height="64"></span>
                <span class="category-label">โรงแรม</span>
            </button>
            <button class="category-btn" onclick="filterCat(this,'คาเฟ่')">
                <span class="iconify" data-icon="carbon:cafe" data-width="64" data-height="64"></span>
                <span class="category-label">คาเฟ่</span>
            </button>
            <button class="category-btn" onclick="filterCat(this,'ร้านอาหาร')">
                <span class="iconify" data-icon="material-symbols:restaurant" data-width="64" data-height="64"></span>
                <span class="category-label">ร้านอาหาร</span>
            </button>
            <button class="category-btn" onclick="filterCat(this,'อาบน้ำ ตัดขน')">
                <span class="iconify" data-icon="ion:cut" data-width="64" data-height="64"></span>
                <span class="category-label">อาบน้ำ ตัดขน</span>
            </button>
            <button class="category-btn" onclick="filterCat(this,'โรงพยาบาลสัตว์')">
                <span class="iconify" data-icon="mingcute:hospital-fill" data-width="64" data-height="64"></span>
                <span class="category-label">โรงพยาบาลสัตว์</span>
            </button>
        </div>
    </div>

    <div class="search-bar-section">
        <div class="search-bar">
            <input type="text" class="search-input" id="searchInput"
                   placeholder="ค้นหาชื่อสถานที่ จังหวัด ที่อยู่..."
                   oninput="onInput()" onkeydown="if(event.key==='Enter')doSearch()">
            <button class="search-icon-btn" onclick="doSearch()">
                <span class="iconify" data-icon="mdi:magnify" data-width="22"></span>
            </button>
        </div>
        <button class="filter-btn" onclick="clearSearch()">
            <span class="iconify" data-icon="mdi:close" data-width="18"></span> ล้าง
        </button>
        <button class="search-btn" onclick="doSearch()">ค้นหา</button>
    </div>

    <div class="search-loading" id="srchLoad">กำลังค้นหา...</div>
    <div class="search-results"  id="srchRes"></div>
    <div class="no-results"      id="noRes">ไม่พบสถานที่ที่ค้นหา <span class="iconify" data-icon="mdi:paw"></span></div>

    <div class="added-places-section" id="apSec" style="display:none;">
        <div class="added-places-title">
            <span class="iconify" data-icon="mdi:map-marker-path" data-width="26" style="color:#123451"></span>
            สถานที่ในแพลน <span id="apBadge" style="font-size:15px;color:#64b5f6;font-weight:500;"></span>
        </div>
        <div id="apList"></div>
    </div>

    <div class="empty-plan" id="emptyPlan">
        <div class="empty-plan-icon"></div>
        <div>ยังไม่มีสถานที่ในแพลน<br><small style="color:#b0bec5">ค้นหาสถานที่ด้านบนแล้วกดเพิ่มลงแพลนได้เลย</small></div>
    </div>

    <div id="savePlanWrap" style="display:none;">
        <button class="save-plan-btn" id="savePlanBtn" onclick="savePlan()">บันทึกแพลน</button>
    </div>

    <hr class="sec-divider">

    <div class="saved-plans-title">
        <span class="iconify" data-icon="mdi:bookmark-multiple-outline" data-width="22" style="color:#123451;vertical-align:middle;margin-right:6px;"></span>
        แพลนที่บันทึกไว้
    </div>
    <div id="svList"><div style="color:#94a3b8;padding:10px 0;">กำลังโหลด...</div></div>
</div></div>

<div class="modal-bg" id="planModal">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <div class="modal-title" id="mTitle">รายละเอียดแพลน</div>
                <div class="modal-title-sub" id="mSubTitle"></div>
            </div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-scroll">
            <div id="modal-export-area">
                <div id="mEditRow" style="display:none;"><input type="text" class="modal-en" id="mEditName" placeholder="ชื่อทริป"></div>
                <div id="mBody"></div>
            </div>
        </div>
        <div class="modal-foot">
            <div class="export-btns" id="mExportBtns" style="display:none;">
                <button class="exp-btn exp-img" onclick="exportImg()">
                    <span class="iconify" data-icon="mdi:image-outline" data-width="17"></span> บันทึกรูป
                </button>
                <button class="exp-btn exp-pdf" onclick="exportPDF()">
                    <span class="iconify" data-icon="mdi:file-pdf-box" data-width="17"></span> บันทึก PDF
                </button>
            </div>
            <div class="modal-btns" id="mBtns"></div>
        </div>
    </div>
</div>
<div class="toast" id="toast"></div>

<script>
var pp=[];try{pp=JSON.parse(sessionStorage.getItem('planPlaces')||'[]');}catch(e){}
var curCat='',srchT=null;

function fmtTH(d){if(!d)return'-';return new Date(d+'T00:00:00').toLocaleDateString('th-TH',{day:'numeric',month:'short',year:'numeric'});}
function fmtS(d){if(!d)return'-';return new Date(d+'T00:00:00').toLocaleDateString('th-TH',{day:'numeric',month:'short'});}
function nts(a,b){return Math.max(0,Math.round((new Date(b)-new Date(a))/86400000));}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

function filterCat(btn,cat){
    if(curCat===cat){curCat='';document.querySelectorAll('.category-btn').forEach(function(b){b.classList.remove('active');});}
    else{curCat=cat;document.querySelectorAll('.category-btn').forEach(function(b){b.classList.remove('active');});btn.classList.add('active');}
    doSearch();
}
function clearSearch(){
    curCat='';
    document.querySelectorAll('.category-btn').forEach(function(b){b.classList.remove('active');});
    document.getElementById('searchInput').value='';
    document.getElementById('srchLoad').style.display='none';
    document.getElementById('srchRes').style.display='none';
    document.getElementById('noRes').style.display='none';
    var p=document.getElementById('inline-picker');if(p)p.remove();
}
function onInput(){clearTimeout(srchT);srchT=setTimeout(doSearch,400);}

async function doSearch(){
    var q=document.getElementById('searchInput').value.trim(),cat=curCat;
    if(q===''&&cat===''){document.getElementById('srchRes').style.display='none';document.getElementById('noRes').style.display='none';return;}
    document.getElementById('srchLoad').style.display='block';
    document.getElementById('srchRes').style.display='none';
    document.getElementById('noRes').style.display='none';
    try{
        var p=new URLSearchParams();if(q)p.append('q',q);if(cat)p.append('cat',cat);
        var res=await fetch('searchplan.php?'+p.toString());var data=await res.json();
        document.getElementById('srchLoad').style.display='none';
        if(!data.places||!data.places.length){document.getElementById('noRes').style.display='block';return;}
        renderSrch(data.places);
    }catch(e){document.getElementById('srchLoad').style.display='none';document.getElementById('noRes').style.display='block';}
}

function renderSrch(places){
    var c=document.getElementById('srchRes');c.innerHTML='';c.style.display='block';
    places.forEach(function(p){
        var card=document.createElement('div');card.className='src-card';
        var th=p.place_image?'<img class="src-thumb" src="'+esc(p.place_image)+'" alt="" onerror="this.outerHTML=\'<div class=src-thumb-ph><span class=iconify data-icon=mdi:map-marker data-width=24></span></div>\'">'
            :'<div class="src-thumb-ph"><span class="iconify" data-icon="mdi:map-marker" data-width="24"></span></div>';
        var loc=[p.address,p.province].filter(Boolean).join(', ');
        var nm=esc(p.place_name).replace(/'/g,"\\'");
        var img=esc(p.place_image||'').replace(/'/g,"\\'");
        card.innerHTML=th+'<div class="src-info"><a href="place_detail.php?id='+p.place_id+'" class="src-name">'+esc(p.place_name)+'</a>'
            +'<div class="src-meta">'+esc(loc)+'</div>'
            +(p.category?'<span class="src-cat">'+esc(p.category)+'</span>':'')
            +'</div><button class="src-add-btn" id="ab'+p.place_id+'" onclick="clickAdd('+p.place_id+',\''+nm+'\',\''+img+'\')">+ เพิ่ม</button>';
        c.appendChild(card);
    });
    if(window.Iconify)Iconify.scan(c);
}

function clickAdd(id,name,img){
    var last=pp.length?pp[pp.length-1]:null;
    showPicker(id,name,img,last?last.visit_date:null);
}

function showPicker(id,name,img,lastDate){
    var old=document.getElementById('inline-picker');if(old)old.remove();
    var today=new Date().toISOString().split('T')[0];
    var div=document.createElement('div');div.id='inline-picker';div.className='inline-picker';
    var nm=esc(name).replace(/'/g,"\\'");var im=esc(img||'').replace(/'/g,"\\'");
    var sameBtn=lastDate?'<button class="btn-use-same" onclick="doAdd('+id+',\''+nm+'\',\''+im+'\',\''+lastDate+'\',\''+lastDate+'\')">ใช้วันเดิม ('+fmtS(lastDate)+')</button><span style="color:#94a3b8;font-size:13px;align-self:center;">หรือ</span>':'';
    div.innerHTML='<h4>เลือกวันที่ — <span style="font-weight:400;color:#64748b">'+esc(name)+'</span></h4>'
        +'<div class="pk-row">'+(sameBtn?sameBtn:'')
        +'<div><label>วันที่ไป</label><input type="date" id="pk-in" min="'+today+'"></div>'
        +'<div><label>วันกลับ (ถ้ามี)</label><input type="date" id="pk-out" min="'+today+'"></div>'
        +'<div style="margin-top:18px;display:flex;gap:8px;">'
        +'<button class="btn-confirm" onclick="confirmPk('+id+',\''+nm+'\',\''+im+'\')">เพิ่ม</button>'
        +'<button class="btn-cancel" onclick="document.getElementById(\'inline-picker\').remove()">ยกเลิก</button>'
        +'</div></div>';
    var ref=document.getElementById('srchRes');ref.parentNode.insertBefore(div,ref.nextSibling);
    div.scrollIntoView({behavior:'smooth',block:'nearest'});
    document.getElementById('pk-in').addEventListener('change',function(){
        var n=new Date(this.value+'T00:00:00');n.setDate(n.getDate()+1);
        document.getElementById('pk-out').min=n.toISOString().split('T')[0];
    });
}

function confirmPk(id,name,img){
    var ci=document.getElementById('pk-in').value,co=document.getElementById('pk-out').value||ci;
    if(!ci){showToast('กรุณาเลือกวันที่ก่อน','error');return;}
    doAdd(id,name,img,ci,co);
}

function doAdd(id,name,img,visitDate,checkOut){
    if(pp.some(function(p){return String(p.place_id)===String(id)&&p.visit_date===visitDate;})){showToast('สถานที่นี้มีในแพลนวันนั้นแล้ว <span class="iconify" data-icon="mdi:paw"></span>','error');return;}
    pp.push({place_id:id,name:name,image:img,visit_date:visitDate,check_in:visitDate,check_out:checkOut||visitDate,added_at:Date.now()});
    sessionStorage.setItem('planPlaces',JSON.stringify(pp));
    renderPP();showToast('เพิ่ม "'+name+'" ลงในแพลนแล้ว!','success');
    var b=document.getElementById('ab'+id);if(b){b.textContent='เพิ่มแล้ว';b.classList.add('added');b.disabled=true;}
    var pk=document.getElementById('inline-picker');if(pk)pk.remove();
}

function groupByDate(arr){
    var g={};
    arr.forEach(function(p){var k=p.visit_date||p.check_in||'?';if(!g[k])g[k]=[];g[k].push(p);});
    Object.values(g).forEach(function(v){v.sort(function(a,b){return(a.added_at||0)-(b.added_at||0);});});
    return Object.entries(g).sort(function(a,b){return a[0].localeCompare(b[0]);});
}

function renderPP(){
    var sec=document.getElementById('apSec'),list=document.getElementById('apList'),sw=document.getElementById('savePlanWrap'),em=document.getElementById('emptyPlan');
    document.getElementById('apBadge').textContent=pp.length?'('+pp.length+' แห่ง)':'';
    if(!pp.length){sec.style.display=sw.style.display='none';em.style.display='block';return;}
    sec.style.display=sw.style.display='block';em.style.display='none';
    list.innerHTML='';var grp=groupByDate(pp),gn=0;
    grp.forEach(function(e){
        var date=e[0],group=e[1];
        var blk=document.createElement('div');blk.className='plan-day-group';
        var lbl=document.createElement('div');lbl.className='plan-day-label';
        var n=nts(date,group[0].check_out||date);
        lbl.innerHTML='<span class="iconify" data-icon="mdi:calendar" data-width="15"></span> วันที่ '+fmtTH(date)+(n>0?' · '+n+' คืน':'');
        blk.appendChild(lbl);
        group.forEach(function(p){
            gn++;var oi=pp.indexOf(p);
            var card=document.createElement('div');card.className='ap-card';
            var th=p.image?'<img class="ap-thumb" src="'+esc(p.image)+'" alt="" onerror="this.style.display=\'none\'">':'<div class="ap-icon"><span class="iconify" data-icon="mdi:map-marker" data-width="24"></span></div>';
            card.innerHTML='<div class="ap-left"><div class="ap-badge">'+gn+'</div>'+th+'<div><div class="ap-name">'+esc(p.name)+'</div><div class="ap-date"><span class="iconify" data-icon="mdi:calendar-range" data-width="13"></span> '+fmtS(p.visit_date||p.check_in)+(p.check_out&&p.check_out!==(p.visit_date||p.check_in)?' → '+fmtS(p.check_out):'')+'</div></div></div><button class="ap-rm" onclick="rmP('+oi+')"><span class="iconify" data-icon="mdi:close" data-width="16"></span></button>';
            blk.appendChild(card);
        });
        list.appendChild(blk);
    });
    if(window.Iconify)Iconify.scan(list);
}

function rmP(i){pp.splice(i,1);sessionStorage.setItem('planPlaces',JSON.stringify(pp));renderPP();}

async function savePlan(){
    var nm=document.getElementById('tripName').value.trim();
    if(!nm){showToast('กรุณากรอกชื่อทริปก่อน','error');document.getElementById('tripName').focus();return;}
    if(!pp.length){showToast('ยังไม่มีสถานที่ในแพลนเลย!','error');return;}
    var btn=document.getElementById('savePlanBtn');btn.disabled=true;btn.textContent='กำลังบันทึก...';
    var sorted=pp.slice().sort(function(a,b){var d=(a.visit_date||a.check_in||'').localeCompare(b.visit_date||b.check_in||'');return d!==0?d:(a.added_at||0)-(b.added_at||0);});
    try{
        var res=await fetch('saveplan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({trip_name:nm,places:sorted})});
        var data=await res.json();
        if(data.success){showToast('บันทึกแพลนสำเร็จ!','success');sessionStorage.removeItem('planPlaces');pp=[];document.getElementById('tripName').value='';renderPP();loadSv();}
        else showToast('เกิดข้อผิดพลาด: '+(data.message||''),'error');
    }catch(e){showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้','error');}
    finally{btn.disabled=false;btn.textContent='บันทึกแพลน';}
}

async function loadSv(){
    var el=document.getElementById('svList');el.innerHTML='<div style="color:#94a3b8;padding:10px 0;">กำลังโหลด...</div>';
    try{var res=await fetch('getplan.php');var data=await res.json();renderSv(data.plans||[]);}
    catch(e){el.innerHTML='<div style="color:#94a3b8;padding:10px 0;">ไม่สามารถโหลดแพลนได้</div>';}
}
function renderSv(plans){
    var el=document.getElementById('svList');
    if(!plans.length){el.innerHTML='<div style="color:#94a3b8;padding:10px 0;">ยังไม่มีแพลนที่บันทึกไว้</div>';return;}
    el.innerHTML='';
    plans.forEach(function(plan){
        var c=document.createElement('div');c.className='sv-card';
        c.innerHTML='<div style="flex:1;min-width:0"><div class="sv-name">'+esc(plan.trip_name)+'</div><div class="sv-meta">'+plan.place_count+' สถานที่ · '+plan.created_th+'</div></div>'
            +'<div class="sv-actions">'
            +'<button class="ic-btn" onclick="viewPlan('+plan.plan_id+')" title="ดู"><span class="iconify" data-icon="mdi:eye-outline" data-width="18"></span></button>'
            +'<button class="ic-btn" onclick="editPlan('+plan.plan_id+',\''+esc(plan.trip_name)+'\')" title="แก้ไข"><span class="iconify" data-icon="mdi:pencil-outline" data-width="18"></span></button>'
            +'<button class="ic-btn del" onclick="delPlan('+plan.plan_id+')" title="ลบ"><span class="iconify" data-icon="mdi:trash-can-outline" data-width="18"></span></button>'
            +'</div>';
        el.appendChild(c);
    });
    if(window.Iconify)Iconify.scan(el);
}
async function viewPlan(id){
    openM('กำลังโหลด...',false);
    document.getElementById('mExportBtns').style.display='none';
    try{
        var res=await fetch('getplandetail.php?plan_id='+id);
        var data=await res.json();
        if(!data.success){document.getElementById('mBody').innerHTML='<p style="color:#ef4444;padding:16px;">โหลดไม่สำเร็จ</p>';return;}
        document.getElementById('mTitle').innerHTML='<span class="iconify" data-icon="mdi:map" data-width="22"></span> '+esc(data.plan.trip_name);
        var places=data.places||[];
        var days=new Set(places.map(function(p){return p.visit_date||'?';})).size;
        document.getElementById('mSubTitle').textContent=places.length+' สถานที่ · '+days+' วัน';
        document.getElementById('mEditRow').style.display='none';
        renderMP(data.places,data.plan.trip_name,data.plan.created_th||'');
        document.getElementById('mExportBtns').style.display='flex';
        document.getElementById('mBtns').innerHTML='<button class="modal-btn mb-s" onclick="closeModal()">ปิด</button><button class="modal-btn mb-p" onclick="editPlan('+data.plan.plan_id+',\''+esc(data.plan.trip_name)+'\')"><span class="iconify" data-icon="mdi:pencil"></span> แก้ไข</button>';
    }catch(e){document.getElementById('mBody').innerHTML='<p style="color:#ef4444;padding:16px;">เกิดข้อผิดพลาด</p>';}
}
function renderMP(places,tripName,createdTH){
    var g={};
    places.forEach(function(p){var k=p.visit_date||'?';if(!g[k])g[k]=[];g[k].push(p);});
    var s=Object.entries(g).sort(function(a,b){return a[0].localeCompare(b[0]);});
    var h='';

    // ── Trip header card ──
    if(tripName){
        h+='<div class="trip-header-card">'
          +'<h2><span class="iconify" data-icon="mdi:map"></span> '+esc(tripName)+'</h2>'
          +'<div class="trip-header-meta">'
          +(createdTH?'<span><span class="iconify" data-icon="mdi:calendar"></span> สร้างเมื่อ '+esc(createdTH)+'</span>':'')
          +'<span><span class="iconify" data-icon="mdi:map-marker-multiple"></span> '+places.length+' สถานที่</span>'
          +'<span><span class="iconify" data-icon="mdi:calendar-range"></span> '+s.length+' วัน</span>'
          +'</div></div>';
    }

    // ── Dashed Timeline ──
    var thMonths=['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    var thDays=['อา.','จ.','อ.','พ.','พฤ.','ศ.','ส.'];
    h+='<div class="tl-wrap">';
    var n=0;
    s.forEach(function(e,di){
        var dayDate=e[0], dayPlaces=e[1];
        var isLast=(di===s.length-1);
        var badgeMonth='', badgeDay='?', badgeLabel='ไม่ระบุวัน';
        if(dayDate&&dayDate!=='?'){
            var d=new Date(dayDate+'T00:00:00');
            var buddYear=d.getFullYear()+543;
            badgeMonth=thMonths[d.getMonth()]+' '+String(buddYear).slice(-2);
            badgeDay=d.getDate();
            badgeLabel='วัน'+thDays[d.getDay()]+' '+fmtTH(dayDate);
        }

        h+='<div class="tl-day'+(isLast?' tl-day-last':'')+'\">';
        h+='<div class="tl-date-row">'
          +'<div class="tl-dot"></div>'
          +'<div class="tl-date-badge">'
          +'<span class="tl-date-month">'+badgeMonth+'</span>'
          +'<span class="tl-date-day">'+badgeDay+'</span>'
          +'</div>'
          +'<div>'
          +'<div class="tl-date-label">'+badgeLabel+'</div>'
          +'</div>'
          +'<span class="tl-count">'+dayPlaces.length+' แห่ง</span>'
          +'</div>';

        h+='<div class="tl-places">';
        dayPlaces.forEach(function(p){
            n++;
            var nights=0;
            if(p.check_in&&p.check_out&&p.check_out!==p.check_in){nights=nts(p.check_in,p.check_out);}
            var thumb=(p.place_image||p.image)
                ?'<img class="tl-thumb" src="'+esc(p.place_image||p.image)+'" alt="" onerror="this.outerHTML=\'<div class=tl-thumb-ph><span class=iconify data-icon=mdi:map-marker-outline data-width=28></span></div>\'">'
                :'<div class="tl-thumb-ph"><span class="iconify" data-icon="mdi:map-marker-outline" data-width="28"></span></div>';
            var chips='<span class="tl-chip date"><span class="iconify" data-icon="mdi:calendar-range" data-width="12"></span>'+fmtS(p.visit_date||p.check_in)+'</span>';
            if(nights>0)chips+='<span class="tl-chip nights"><span class="iconify" data-icon="mdi:weather-night"></span> '+nights+' คืน</span>';
            if(p.category)chips+='<span class="tl-chip cat">'+esc(p.category)+'</span>';
            if(p.province)chips+='<span class="tl-chip prov">'+esc(p.province)+'</span>';
            h+='<div class="tl-card">'
              +'<div class="tl-num">'+n+'</div>'
              +thumb
              +'<div class="tl-info">'
              +'<div class="tl-name">'+esc(p.place_name||p.name||'')+'</div>'
              +'<div class="tl-chips">'+chips+'</div>'
              +(p.address?'<div class="tl-addr"><span class="iconify" data-icon="mdi:map-marker" data-width="11"></span>'+esc(p.address)+'</div>':'')
              +'</div></div>';
        });
        h+='</div>';  // tl-places
        h+='</div>';  // tl-day
    });
    h+='</div>';  // tl-wrap

    document.getElementById('mBody').innerHTML=h;
    if(window.Iconify)Iconify.scan(document.getElementById('mBody'));
}
var _editPlaces=[];
async function editPlan(id,name){
    openM('<span class="iconify" data-icon="mdi:pencil"></span> แก้ไขแพลน',true);document.getElementById('mEditName').value=name;
    document.getElementById('mExportBtns').style.display='none';
    document.getElementById('mSubTitle').textContent='';
    _editPlaces=[];
    try{
        var res=await fetch('getplandetail.php?plan_id='+id);
        var data=await res.json();
        if(data.success){_editPlaces=data.places;renderEditMP(data.places);}
    }catch(e){}
    document.getElementById('mBtns').innerHTML='<button class="modal-btn mb-s" onclick="closeModal()">ยกเลิก</button><button class="modal-btn mb-p" onclick="saveEdit('+id+')"><span class="iconify" data-icon="mdi:content-save"></span> บันทึก</button>';
}
function renderEditMP(places){
    var h='';
    places.forEach(function(p,i){
        var thumb=(p.place_image||p.image)
            ?'<img class="modal-pthumb" src="'+esc(p.place_image||p.image)+'" alt="" onerror="this.outerHTML=\'<div class=modal-pthumb-ph><span class=iconify data-icon=mdi:map-marker data-width=26></span></div>\'">'
            :'<div class="modal-pthumb-ph"><span class="iconify" data-icon="mdi:map-marker" data-width="26"></span></div>';
        var ci=p.check_in||p.visit_date||'';
        var co=p.check_out||p.visit_date||'';
        var isSameDay=(ci===co)||!co;
        h+='<div class="modal-pr" style="flex-wrap:wrap;gap:10px;">'
          +'<div class="modal-num">'+(i+1)+'</div>'
          +thumb
          +'<div class="modal-pinfo" style="min-width:160px;">'
          +'<div class="modal-pn">'+esc(p.place_name||p.name||'')+'</div>'
          +(p.category?'<span class="modal-pd-chip cat" style="margin-top:4px;display:inline-block;">'+esc(p.category)+'</span>':'')
          +'</div>'
          +'<div style="display:flex;flex-direction:column;gap:6px;min-width:200px;flex:1;">'
          +'<label style="font-size:12px;color:#64748b;font-weight:600;"><span class="iconify" data-icon="mdi:calendar"></span> วันที่เช็คอิน / วันที่เยี่ยมชม</label>'
          +'<input type="date" class="edit-date-in" data-idx="'+i+'" value="'+ci+'" style="padding:7px 10px;border:1.5px solid #d4e7f7;border-radius:10px;font-family:\'Kanit\',sans-serif;font-size:14px;outline:none;" onchange="syncCheckout(this,'+i+')">'
          +'<label style="font-size:12px;color:#64748b;font-weight:600;"><span class="iconify" data-icon="mdi:calendar"></span> วันที่เช็คเอาต์ <span style="font-weight:400;color:#94a3b8;">(โรงแรมเท่านั้น)</span></label>'
          +'<input type="date" class="edit-date-out" data-idx="'+i+'" value="'+(isSameDay?'':co)+'" style="padding:7px 10px;border:1.5px solid #d4e7f7;border-radius:10px;font-family:\'Kanit\',sans-serif;font-size:14px;outline:none;">'
          +'</div>'
          +'</div>';
    });
    if(!h)h='<div style="color:#94a3b8;padding:24px;text-align:center;">ไม่มีสถานที่ในแพลน</div>';
    document.getElementById('mBody').innerHTML=h;
    if(window.Iconify)Iconify.scan(document.getElementById('mBody'));
}
function syncCheckout(el,idx){
    // auto-fill checkout = checkin if checkout is empty or before checkin
    var coEl=document.querySelector('.edit-date-out[data-idx="'+idx+'"]');
    if(coEl&&(!coEl.value||coEl.value<el.value))coEl.value='';
    if(coEl)coEl.min=el.value;
}
async function saveEdit(id){
    var n=document.getElementById('mEditName').value.trim();
    if(!n){showToast('กรุณากรอกชื่อทริป','error');return;}
    // collect updated dates from inputs
    var updatedPlaces=_editPlaces.map(function(p,i){
        var ci=document.querySelector('.edit-date-in[data-idx="'+i+'"]');
        var co=document.querySelector('.edit-date-out[data-idx="'+i+'"]');
        var checkIn=ci?ci.value:p.check_in||p.visit_date||'';
        var checkOut=co&&co.value?co.value:checkIn;
        return {
            place_id: p.place_id,
            place_name: p.place_name||p.name||'',
            order_num: p.order_num||(i+1),
            visit_date: checkIn,
            check_in: checkIn,
            check_out: checkOut
        };
    });
    try{
        var res=await fetch('updateplan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({plan_id:id,trip_name:n,places:updatedPlaces})});
        var data=await res.json();
        if(data.success){showToast('บันทึกแล้ว!','success');closeModal();loadSv();}
        else showToast('เกิดข้อผิดพลาด: '+(data.message||''),'error');
    }catch(e){showToast('เกิดข้อผิดพลาด','error');}
}
async function delPlan(id){
    if(!confirm('ต้องการลบแพลนนี้ทั้งหมดหรือไม่?'))return;
    try{var res=await fetch('deleteplan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({plan_id:id})});
    var data=await res.json();if(data.success){showToast('ลบแพลนแล้ว','success');loadSv();}else showToast('เกิดข้อผิดพลาด','error');}catch(e){showToast('เกิดข้อผิดพลาด','error');}
}
function openM(t,e){document.getElementById('mTitle').textContent=t;document.getElementById('mSubTitle').textContent='';document.getElementById('mBody').innerHTML='<div style="color:#94a3b8;padding:24px;text-align:center;">กำลังโหลด...</div>';document.getElementById('mBtns').innerHTML='';document.getElementById('mEditRow').style.display=e?'block':'none';document.getElementById('planModal').classList.add('open');}
function closeModal(){document.getElementById('planModal').classList.remove('open');}
document.getElementById('planModal').addEventListener('click',function(e){if(e.target===this)closeModal();});
var _t;
function showToast(msg,type){var t=document.getElementById('toast');t.textContent=msg;t.className='toast '+(type==='error'?'te':'ts')+' show';clearTimeout(_t);_t=setTimeout(function(){t.classList.remove('show');},2800);}
renderPP();loadSv();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function exportImg(){
    var btn=document.querySelector('.exp-btn.exp-img');
    btn.classList.add('exp-loading');btn.textContent='⏳ กำลังสร้าง...';
    try{
        var el=document.getElementById('modal-export-area');
        var canvas=await html2canvas(el,{scale:2,useCORS:true,backgroundColor:'#ffffff',logging:false});
        var link=document.createElement('a');
        var tripName=document.getElementById('mTitle').textContent.replace(/[^\u0E00-\u0E7Fa-zA-Z0-9 _-]/g,'').trim()||'trip-plan';
        link.download=tripName+'.png';link.href=canvas.toDataURL('image/png');link.click();
    }catch(e){alert('ไม่สามารถสร้างรูปได้');}
    btn.classList.remove('exp-loading');
    btn.innerHTML='<span class="iconify" data-icon="mdi:image-outline" data-width="17"></span> บันทึกรูป';
    if(window.Iconify)Iconify.scan(btn);
}
async function exportPDF(){
    var btn=document.querySelector('.exp-btn.exp-pdf');
    btn.classList.add('exp-loading');btn.textContent='⏳ กำลังสร้าง...';
    try{
        var el=document.getElementById('modal-export-area');
        var canvas=await html2canvas(el,{scale:2,useCORS:true,backgroundColor:'#ffffff',logging:false});
        var imgData=canvas.toDataURL('image/png');
        var {jsPDF}=window.jspdf;
        var pdf=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
        var pdfW=pdf.internal.pageSize.getWidth();
        var pdfH=(canvas.height*pdfW)/canvas.width;
        var pageH=pdf.internal.pageSize.getHeight();
        var yOffset=0;
        while(yOffset<pdfH){if(yOffset>0)pdf.addPage();pdf.addImage(imgData,'PNG',0,-yOffset,pdfW,pdfH);yOffset+=pageH;}
        var tripName=document.getElementById('mTitle').textContent.replace(/[^\u0E00-\u0E7Fa-zA-Z0-9 _-]/g,'').trim()||'trip-plan';
        pdf.save(tripName+'.pdf');
    }catch(e){alert('ไม่สามารถสร้าง PDF ได้');}
    btn.classList.remove('exp-loading');
    btn.innerHTML='<span class="iconify" data-icon="mdi:file-pdf-box" data-width="17"></span> บันทึก PDF';
    if(window.Iconify)Iconify.scan(btn);
}
</script>
</body>
</html>