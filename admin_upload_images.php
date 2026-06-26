<?php
// ================================================
//  admin_upload_images.php — อัปโหลดรูปภาพสถานที่
// ================================================
session_start();
require_once 'connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$msg     = '';
$msgType = '';

// ── Handle Upload ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_id'])) {
    $place_id = (int)$_POST['place_id'];

    // ดึงข้อมูลเดิม
    $stmt = $pdo->prepare("SELECT place_image, all_images FROM places WHERE place_id = ?");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch();

    if (!$place) {
        $msg = 'ไม่พบสถานที่ที่เลือก';
        $msgType = 'error';
    } else {
        $uploadDir = 'uploads/places/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowedTypes = ['image/jpeg','image/jpg','image/png','image/webp','image/avif','image/gif'];
        $maxSize      = 5 * 1024 * 1024; // 5MB

        $uploadedFiles = [];
        $errors        = [];

        $files = $_FILES['images'] ?? [];
        $count = is_array($files['name']) ? count($files['name']) : 0;

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > $maxSize) {
                $errors[] = "ไฟล์ {$files['name'][$i]} ขนาดเกิน 5MB";
                continue;
            }
            if (!in_array($files['type'][$i], $allowedTypes)) {
                $errors[] = "ไฟล์ {$files['name'][$i]} ไม่ใช่รูปภาพที่รองรับ";
                continue;
            }

            $ext      = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = 'place_admin_' . $place_id . '_' . time() . '_' . $i . '.' . $ext;
            $destPath = $uploadDir . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
                $uploadedFiles[] = $destPath;
            } else {
                $errors[] = "ไม่สามารถอัปโหลดไฟล์ {$files['name'][$i]} ได้";
            }
        }

        if (!empty($uploadedFiles)) {
            // รวมรูปเก่า + ใหม่
            $existingAll = !empty($place['all_images'])
                ? array_filter(array_map('trim', explode(',', $place['all_images'])))
                : [];

            $allImages   = array_values(array_unique(array_merge($existingAll, $uploadedFiles)));
            $mainImage   = $place['place_image'] ?: $uploadedFiles[0];

            $stmt = $pdo->prepare("
                UPDATE places
                SET place_image = :main, all_images = :all, updated_at = NOW()
                WHERE place_id = :id
            ");
            $stmt->execute([
                ':main' => $mainImage,
                ':all'  => implode(',', $allImages),
                ':id'   => $place_id,
            ]);

            $msg = 'อัปโหลดรูปภาพสำเร็จ ' . count($uploadedFiles) . ' รูป';
            if (!empty($errors)) $msg .= ' (มีข้อผิดพลาด: ' . implode(', ', $errors) . ')';
            $msgType = 'success';
        } elseif (!empty($errors)) {
            $msg = implode('<br>', $errors);
            $msgType = 'error';
        }
    }
}

// ── Handle Delete Image ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $place_id  = (int)$_POST['place_id'];
    $deleteImg = $_POST['delete_image'];

    $stmt = $pdo->prepare("SELECT place_image, all_images FROM places WHERE place_id = ?");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch();

    if ($place) {
        $allImages = array_filter(array_map('trim', explode(',', $place['all_images'] ?? '')));
        $allImages = array_values(array_filter($allImages, fn($img) => $img !== $deleteImg));

        $newMain = $place['place_image'] === $deleteImg
            ? ($allImages[0] ?? '')
            : $place['place_image'];

        $pdo->prepare("UPDATE places SET place_image = ?, all_images = ?, updated_at = NOW() WHERE place_id = ?")
            ->execute([$newMain, implode(',', $allImages), $place_id]);

        // ลบไฟล์จริง (เฉพาะที่อยู่ใน uploads/places/)
        if (str_starts_with($deleteImg, 'uploads/') && file_exists($deleteImg)) {
            unlink($deleteImg);
        }

        $msg = 'ลบรูปภาพแล้ว';
        $msgType = 'success';
    }
}

// ── Handle Set Main Image ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_main'])) {
    $place_id = (int)$_POST['place_id'];
    $mainImg  = $_POST['set_main'];
    $pdo->prepare("UPDATE places SET place_image = ?, updated_at = NOW() WHERE place_id = ?")
        ->execute([$mainImg, $place_id]);
    $msg = 'ตั้งรูปหลักแล้ว';
    $msgType = 'success';
}

// ── Fetch All Places ─────────────────────────────
$places = $pdo->query("
    SELECT place_id, place_name, province, category, place_image, all_images
    FROM places
    WHERE status = 'approved'
    ORDER BY province ASC, place_name ASC
")->fetchAll();

// Group by province
$grouped = [];
foreach ($places as $p) {
    $grouped[$p['province']][] = $p;
}

// Selected place detail
$selectedPlace = null;
if (isset($_GET['pid'])) {
    $pid = (int)$_GET['pid'];
    foreach ($places as $p) {
        if ($p['place_id'] == $pid) {
            $selectedPlace = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>อัปโหลดรูปภาพสถานที่ — Pawlands Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#123451;--navy-dark:#0d2640;
  --bg:#f0f6ff;--card:#ffffff;
  --text:#1a2a3a;--muted:#6b8099;
  --border:rgba(18,52,81,0.12);
  --sidebar-w:220px;
  --green:#2e9e6e;--amber:#e09a1e;--red:#d44040;
}
body{font-family:'Kanit',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--navy);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100}
.sidebar-logo{padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:10px}
.sidebar-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:3px}
.nav-item{display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:10px;cursor:pointer;color:rgba(255,255,255,.6);font-size:14px;font-family:'Kanit',sans-serif;border:none;background:none;width:100%;text-align:left;transition:all .15s;text-decoration:none}
.nav-item:hover{background:rgba(255,255,255,.08);color:rgba(255,255,255,.9)}
.nav-item.active{background:rgba(255,255,255,.15);color:#fff;font-weight:500}
.sidebar-bottom{padding:14px 10px;border-top:1px solid rgba(255,255,255,.08)}
.logout-btn{display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:10px;cursor:pointer;color:rgba(255,255,255,.5);font-size:14px;font-family:'Kanit',sans-serif;background:none;border:none;width:100%;text-decoration:none;transition:all .15s}
.logout-btn:hover{color:rgba(255,255,255,.9);background:rgba(255,255,255,.08)}

/* MAIN */
.main{margin-left:var(--sidebar-w);flex:1;padding:28px;overflow-x:hidden}
.page-title{font-size:22px;font-weight:600;color:var(--navy);margin-bottom:4px}
.page-sub{font-size:13px;color:var(--muted);margin-bottom:24px}

/* LAYOUT */
.layout{display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start}

/* PLACE LIST */
.place-list-card{background:var(--card);border-radius:16px;overflow:hidden;position:sticky;top:24px;max-height:calc(100vh - 80px);display:flex;flex-direction:column}
.place-list-header{padding:16px 18px;border-bottom:1px solid var(--border)}
.search-input{width:100%;padding:9px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:'Kanit',sans-serif;background:#f8fafc;color:var(--text);outline:none}
.search-input:focus{border-color:#4a7aad;background:#fff}
.province-group{padding:8px 18px 2px;font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);font-weight:600;margin-top:6px}
.place-list-scroll{overflow-y:auto;flex:1}
.place-item{display:flex;align-items:center;gap:12px;padding:10px 18px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s}
.place-item:hover{background:#f0f7ff}
.place-item.selected{background:#e8f0ff}
.place-thumb{width:44px;height:44px;border-radius:8px;object-fit:cover;background:#dde8f5;flex-shrink:0;border:1px solid var(--border)}
.place-thumb-empty{width:44px;height:44px;border-radius:8px;background:#dde8f5;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:18px;border:1px solid var(--border)}
.place-item-name{font-size:14px;font-weight:500;color:var(--text);line-height:1.3}
.place-item-cat{font-size:12px;color:var(--muted);margin-top:2px}
.no-img-badge{font-size:10px;background:#fff3cd;color:#856404;padding:1px 7px;border-radius:10px;margin-top:3px;display:inline-block}

/* UPLOAD PANEL */
.upload-card{background:var(--card);border-radius:16px;padding:28px}
.card-title{font-size:17px;font-weight:600;color:var(--navy);margin-bottom:4px}
.card-sub{font-size:13px;color:var(--muted);margin-bottom:22px}

/* GALLERY */
.gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px}
.gallery-item{position:relative;border-radius:12px;overflow:hidden;background:#dde8f5;aspect-ratio:4/3;border:2px solid transparent;transition:border-color .15s}
.gallery-item.is-main{border-color:var(--navy)}
.gallery-item img{width:100%;height:100%;object-fit:cover;display:block}
.gallery-overlay{position:absolute;inset:0;background:rgba(0,0,0,0);display:flex;align-items:flex-end;padding:8px;gap:6px;transition:background .15s}
.gallery-item:hover .gallery-overlay{background:rgba(0,0,0,.45)}
.gallery-btn{opacity:0;padding:5px 10px;border-radius:7px;border:none;font-size:12px;font-family:'Kanit',sans-serif;cursor:pointer;transition:opacity .15s;white-space:nowrap}
.gallery-item:hover .gallery-btn{opacity:1}
.btn-main{background:#fff;color:var(--navy)}
.btn-del{background:#ef4444;color:#fff}
.main-badge{position:absolute;top:8px;left:8px;background:var(--navy);color:#fff;font-size:10px;padding:2px 8px;border-radius:20px}

/* DROPZONE */
.dropzone{border:2px dashed var(--border);border-radius:14px;padding:36px 24px;text-align:center;cursor:pointer;transition:all .2s;background:#fafcff}
.dropzone:hover,.dropzone.dragover{border-color:#4a7aad;background:#f0f6ff}
.dropzone-icon{font-size:40px;margin-bottom:12px}
.dropzone-title{font-size:15px;font-weight:500;color:var(--navy);margin-bottom:4px}
.dropzone-sub{font-size:13px;color:var(--muted)}
#fileInput{display:none}

/* PREVIEW */
.preview-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin:16px 0}
.preview-item{position:relative;border-radius:10px;overflow:hidden;aspect-ratio:4/3;background:#dde8f5}
.preview-item img{width:100%;height:100%;object-fit:cover}
.preview-remove{position:absolute;top:4px;right:4px;width:22px;height:22px;background:rgba(0,0,0,.55);border:none;border-radius:50%;color:#fff;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center}

/* BUTTONS */
.btn{padding:10px 24px;border-radius:10px;border:none;font-family:'Kanit',sans-serif;font-size:14px;cursor:pointer;font-weight:500;transition:all .15s}
.btn-primary{background:var(--navy);color:#fff}
.btn-primary:hover{background:var(--navy-dark)}
.btn-primary:disabled{opacity:.5;cursor:not-allowed}
.btn-secondary{background:#f1f5f9;color:var(--text)}
.btn-secondary:hover{background:#e2eaf5}

/* ALERT */
.alert{padding:12px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}

/* EMPTY STATE */
.empty-state{text-align:center;padding:60px 24px;color:var(--muted)}
.empty-icon{font-size:48px;margin-bottom:16px}
.empty-title{font-size:16px;font-weight:500;color:var(--text);margin-bottom:6px}

/* PROGRESS */
.progress-bar{width:100%;height:6px;background:#e2e8f0;border-radius:99px;margin:12px 0;overflow:hidden;display:none}
.progress-fill{height:100%;background:var(--navy);border-radius:99px;transition:width .3s;width:0%}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="logo_w.png" alt="Pawlands" style="height:40px;object-fit:contain;">
  </div>
  <nav class="sidebar-nav">
    <a class="nav-item" href="admin_dashboard.php">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>
      Dashboard
    </a>
    <a class="nav-item" href="admin_dashboard.php?page=places">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
      สถานที่
    </a>
    <a class="nav-item active" href="admin_upload_images.php">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      รูปภาพสถานที่
    </a>
  </nav>
  <div class="sidebar-bottom">
    <a href="admin_logout.php" class="logout-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Log Out
    </a>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="page-title">รูปภาพสถานที่</div>
  <div class="page-sub">เลือกสถานที่จากรายการแล้วอัปโหลดรูปภาพ (รองรับ JPG, PNG, WEBP, AVIF ไม่เกิน 5MB/รูป)</div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
    <?= $msgType === 'success' ? '✓' : '✕' ?> <?= $msg ?>
  </div>
  <?php endif; ?>

  <div class="layout">

    <!-- LEFT: Place List -->
    <div class="place-list-card">
      <div class="place-list-header">
        <input class="search-input" type="text" id="placeSearch" placeholder="ค้นหาสถานที่..." oninput="filterPlaces()">
        <div style="margin-top:10px;font-size:12px;color:var(--muted)">
          ทั้งหมด <?= count($places) ?> สถานที่ •
          <span style="color:#e09a1e">⚠ <?= count(array_filter($places, fn($p) => !$p['place_image'])) ?> ยังไม่มีรูป</span>
        </div>
      </div>
      <div class="place-list-scroll" id="placeListScroll">
        <?php foreach ($grouped as $province => $plist): ?>
        <div class="province-group province-label"><?= htmlspecialchars($province) ?></div>
        <?php foreach ($plist as $p):
          $isSelected = $selectedPlace && $selectedPlace['place_id'] == $p['place_id'];
          $hasImg = !empty($p['place_image']);
        ?>
        <div class="place-item <?= $isSelected ? 'selected' : '' ?> place-row"
             data-name="<?= htmlspecialchars(strtolower($p['place_name'])) ?>"
             data-prov="<?= htmlspecialchars(strtolower($province)) ?>"
             onclick="selectPlace(<?= $p['place_id'] ?>)">
          <?php if ($hasImg): ?>
          <img class="place-thumb" src="<?= htmlspecialchars($p['place_image']) ?>" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="place-thumb-empty" style="display:none">🏠</div>
          <?php else: ?>
          <div class="place-thumb-empty">🏠</div>
          <?php endif; ?>
          <div style="flex:1;min-width:0">
            <div class="place-item-name"><?= htmlspecialchars($p['place_name']) ?></div>
            <div class="place-item-cat"><?= htmlspecialchars($p['category']) ?></div>
            <?php if (!$hasImg): ?>
            <span class="no-img-badge">ยังไม่มีรูป</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- RIGHT: Upload Panel -->
    <div>
      <?php if ($selectedPlace): ?>
      <?php
        $allImgs = !empty($selectedPlace['all_images'])
            ? array_filter(array_map('trim', explode(',', $selectedPlace['all_images'])))
            : ($selectedPlace['place_image'] ? [$selectedPlace['place_image']] : []);
        $mainImg = $selectedPlace['place_image'] ?? '';
      ?>
      <div class="upload-card">
        <div class="card-title"><?= htmlspecialchars($selectedPlace['place_name']) ?></div>
        <div class="card-sub"><?= htmlspecialchars($selectedPlace['category'] . ' • ' . $selectedPlace['province']) ?></div>

        <!-- Current Gallery -->
        <?php if (!empty($allImgs)): ?>
        <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:12px;">
          รูปภาพที่มีอยู่ (<?= count($allImgs) ?> รูป)
          <span style="font-size:12px;color:var(--muted);font-weight:400;margin-left:6px;">กดที่รูปเพื่อตั้งเป็นรูปหลัก</span>
        </div>
        <div class="gallery">
          <?php foreach ($allImgs as $img): ?>
          <div class="gallery-item <?= $img === $mainImg ? 'is-main' : '' ?>">
            <img src="<?= htmlspecialchars($img) ?>" alt="" onerror="this.parentElement.style.background='#f1f5f9'">
            <?php if ($img === $mainImg): ?>
            <div class="main-badge">รูปหลัก</div>
            <?php endif; ?>
            <div class="gallery-overlay">
              <?php if ($img !== $mainImg): ?>
              <form method="POST" action="?pid=<?= $selectedPlace['place_id'] ?>" style="margin:0">
                <input type="hidden" name="place_id" value="<?= $selectedPlace['place_id'] ?>">
                <input type="hidden" name="set_main" value="<?= htmlspecialchars($img) ?>">
                <button type="submit" class="gallery-btn btn-main">ตั้งหลัก</button>
              </form>
              <?php endif; ?>
              <form method="POST" action="?pid=<?= $selectedPlace['place_id'] ?>" style="margin:0" onsubmit="return confirm('ลบรูปนี้?')">
                <input type="hidden" name="place_id" value="<?= $selectedPlace['place_id'] ?>">
                <input type="hidden" name="delete_image" value="<?= htmlspecialchars($img) ?>">
                <button type="submit" class="gallery-btn btn-del">ลบ</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin-bottom:22px">
        <?php endif; ?>

        <!-- Upload Form -->
        <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:12px;">เพิ่มรูปภาพใหม่</div>
        <form method="POST" action="?pid=<?= $selectedPlace['place_id'] ?>" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="place_id" value="<?= $selectedPlace['place_id'] ?>">
          <input type="file" id="fileInput" name="images[]" multiple accept="image/*" onchange="handleFiles(this.files)">

          <div class="dropzone" id="dropzone" onclick="document.getElementById('fileInput').click()"
               ondragover="event.preventDefault();this.classList.add('dragover')"
               ondragleave="this.classList.remove('dragover')"
               ondrop="event.preventDefault();this.classList.remove('dragover');handleFiles(event.dataTransfer.files)">
            <div class="dropzone-icon">📷</div>
            <div class="dropzone-title">คลิกหรือลากรูปมาวางที่นี่</div>
            <div class="dropzone-sub">JPG, PNG, WEBP, AVIF • สูงสุด 5MB ต่อรูป • อัปโหลดได้หลายรูปพร้อมกัน</div>
          </div>

          <div class="preview-grid" id="previewGrid" style="display:none"></div>
          <div class="progress-bar" id="progressBar"><div class="progress-fill" id="progressFill"></div></div>

          <div style="display:flex;gap:10px;margin-top:16px">
            <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
              อัปโหลด (<span id="fileCount">0</span> รูป)
            </button>
            <button type="button" class="btn btn-secondary" onclick="clearFiles()">ล้าง</button>
          </div>
        </form>
      </div>

      <?php else: ?>
      <div class="upload-card">
        <div class="empty-state">
          <div class="empty-icon">👈</div>
          <div class="empty-title">เลือกสถานที่จากรายการทางซ้าย</div>
          <div style="font-size:13px;color:var(--muted)">เพื่อดูรูปภาพที่มีอยู่และอัปโหลดรูปใหม่</div>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<script>
// ── Select Place ────────────────────────────────
function selectPlace(pid) {
  window.location.href = '?pid=' + pid;
}

// ── Filter Places ───────────────────────────────
function filterPlaces() {
  const q = document.getElementById('placeSearch').value.toLowerCase();
  document.querySelectorAll('.place-row').forEach(el => {
    const name = el.dataset.name || '';
    const prov = el.dataset.prov || '';
    el.style.display = (!q || name.includes(q) || prov.includes(q)) ? '' : 'none';
  });
  // show/hide province labels
  document.querySelectorAll('.province-label').forEach(label => {
    // check if any sibling place-row is visible after label
    let next = label.nextElementSibling;
    let hasVisible = false;
    while (next && next.classList.contains('place-row')) {
      if (next.style.display !== 'none') hasVisible = true;
      next = next.nextElementSibling;
    }
    label.style.display = hasVisible ? '' : 'none';
  });
}

// ── File Preview ────────────────────────────────
let selectedFiles = [];

function handleFiles(files) {
  for (const file of files) {
    if (!file.type.startsWith('image/')) continue;
    if (file.size > 5 * 1024 * 1024) {
      alert(`${file.name} ขนาดเกิน 5MB`);
      continue;
    }
    selectedFiles.push(file);
  }
  renderPreviews();
}

function renderPreviews() {
  const grid = document.getElementById('previewGrid');
  const btn  = document.getElementById('uploadBtn');
  const cnt  = document.getElementById('fileCount');

  if (selectedFiles.length === 0) {
    grid.style.display = 'none';
    btn.disabled = true;
    cnt.textContent = '0';
    return;
  }

  grid.style.display = 'grid';
  grid.innerHTML = '';
  btn.disabled = false;
  cnt.textContent = selectedFiles.length;

  selectedFiles.forEach((file, i) => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'preview-item';
      div.innerHTML = `
        <img src="${e.target.result}" alt="">
        <button class="preview-remove" onclick="removeFile(${i})">✕</button>
      `;
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function removeFile(i) {
  selectedFiles.splice(i, 1);
  renderPreviews();
}

function clearFiles() {
  selectedFiles = [];
  document.getElementById('fileInput').value = '';
  renderPreviews();
}

// Override form submit to use DataTransfer (sync selectedFiles array)
document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
  if (selectedFiles.length === 0) return;

  const dt = new DataTransfer();
  selectedFiles.forEach(f => dt.items.add(f));
  document.getElementById('fileInput').files = dt.files;

  // Progress animation
  const bar  = document.getElementById('progressBar');
  const fill = document.getElementById('progressFill');
  bar.style.display = 'block';
  let w = 0;
  const timer = setInterval(() => {
    w = Math.min(w + Math.random() * 15, 90);
    fill.style.width = w + '%';
  }, 200);

  // Allow form to submit naturally; cleanup after
  setTimeout(() => {
    clearInterval(timer);
    fill.style.width = '100%';
  }, 1500);
});

// Scroll to selected place on load
<?php if ($selectedPlace): ?>
document.addEventListener('DOMContentLoaded', () => {
  const selected = document.querySelector('.place-item.selected');
  if (selected) selected.scrollIntoView({ block: 'center', behavior: 'smooth' });
});
<?php endif; ?>
</script>
</body>
</html>