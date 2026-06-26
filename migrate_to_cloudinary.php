<?php
// ============================================================
// migrate_to_cloudinary.php
// วาง script นี้ที่ root ของ Pawland แล้วเปิดใน browser
// หรือรันผ่าน CLI: php migrate_to_cloudinary.php
// ============================================================

require_once __DIR__ . '/cloudinary_config.php';

// --- DB connection (แก้ให้ตรงกับ config จริง) ---
$host   = '127.0.0.1';
$dbname = 'pawland';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// Base path ของ XAMPP (แก้ถ้าต่างกัน)
define('BASE_PATH', __DIR__ . '/');

$log  = [];
$ok   = 0;
$skip = 0;
$fail = 0;

// ============================================================
// ฟังก์ชันหลัก: migrate path → Cloudinary URL แล้ว update DB
// ============================================================
function migrateField(
    PDO $pdo, string $table, string $idCol, int $id,
    string $field, string $value, string $folder
): array {
    global $ok, $skip, $fail;

    if (empty($value)) return ['skip', $value];

    // ถ้าเป็น URL ภายนอก (http/https) ให้ upload จาก URL
    if (str_starts_with($value, 'http')) {
        if (isCloudinaryUrl($value)) {
            $skip++;
            return ['skip', $value]; // migrate ไปแล้ว
        }
        $newUrl = cloudinaryUploadFromUrl($value, $folder);
    } else {
        // local path
        $absPath = BASE_PATH . ltrim($value, '/');
        if (!file_exists($absPath)) {
            $fail++;
            return ['fail', "ไม่พบไฟล์: $absPath"];
        }
        $newUrl = cloudinaryUpload($absPath, $folder);
    }

    if (!$newUrl) {
        $fail++;
        return ['fail', "upload ล้มเหลว: $value"];
    }

    $pdo->prepare("UPDATE `$table` SET `$field` = ? WHERE `$idCol` = ?")
        ->execute([$newUrl, $id]);
    $ok++;
    return ['ok', $newUrl];
}

// ============================================================
// 1. places.place_image
// ============================================================
$rows = $pdo->query("SELECT place_id, place_image FROM places WHERE place_image IS NOT NULL AND place_image != ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    [$status, $msg] = migrateField($pdo, 'places', 'place_id', $row['place_id'], 'place_image', $row['place_image'], 'pawland/places');
    $log[] = "[places.place_image #{$row['place_id']}] $status: $msg";
}

// ============================================================
// 2. places.all_images (JSON array)
// ============================================================
$rows = $pdo->query("SELECT place_id, all_images FROM places WHERE all_images IS NOT NULL AND all_images != '' AND all_images != 'null'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $images = json_decode($row['all_images'], true);
    if (!is_array($images)) continue;
    $updated = [];
    foreach ($images as $img) {
        if (empty($img)) { $updated[] = $img; continue; }
        if (str_starts_with($img, 'http')) {
            if (isCloudinaryUrl($img)) { $updated[] = $img; $skip++; continue; }
            $newUrl = cloudinaryUploadFromUrl($img, 'pawland/places');
        } else {
            $absPath = BASE_PATH . ltrim($img, '/');
            if (!file_exists($absPath)) { $updated[] = $img; $fail++; $log[] = "[places.all_images #{$row['place_id']}] fail: ไม่พบ $absPath"; continue; }
            $newUrl = cloudinaryUpload($absPath, 'pawland/places');
        }
        if ($newUrl) { $updated[] = $newUrl; $ok++; $log[] = "[places.all_images #{$row['place_id']}] ok: $newUrl"; }
        else         { $updated[] = $img;    $fail++; $log[] = "[places.all_images #{$row['place_id']}] fail: $img"; }
    }
    $pdo->prepare("UPDATE places SET all_images = ? WHERE place_id = ?")
        ->execute([json_encode($updated, JSON_UNESCAPED_UNICODE), $row['place_id']]);
}

// ============================================================
// 3. account_entre.business_image
// ============================================================
$rows = $pdo->query("SELECT entre_id, business_image FROM account_entre WHERE business_image IS NOT NULL AND business_image != ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    [$status, $msg] = migrateField($pdo, 'account_entre', 'entre_id', $row['entre_id'], 'business_image', $row['business_image'], 'pawland/business');
    $log[] = "[account_entre.business_image #{$row['entre_id']}] $status: $msg";
}

// ============================================================
// 4. events.image
// ============================================================
$rows = $pdo->query("SELECT id, image FROM events WHERE image IS NOT NULL AND image != ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    [$status, $msg] = migrateField($pdo, 'events', 'id', $row['id'], 'image', $row['image'], 'pawland/events');
    $log[] = "[events.image #{$row['id']}] $status: $msg";
}

// ============================================================
// 5. news.image
// ============================================================
$rows = $pdo->query("SELECT id, image FROM news WHERE image IS NOT NULL AND image != ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    [$status, $msg] = migrateField($pdo, 'news', 'id', $row['id'], 'image', $row['image'], 'pawland/news');
    $log[] = "[news.image #{$row['id']}] $status: $msg";
}

// ============================================================
// 6. reviews.images (JSON array)
// ============================================================
$rows = $pdo->query("SELECT review_id, images FROM reviews WHERE images IS NOT NULL AND images != '' AND images != 'null'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $images = json_decode($row['images'], true);
    if (!is_array($images)) continue;
    $updated = [];
    foreach ($images as $img) {
        if (empty($img)) { $updated[] = $img; continue; }
        if (str_starts_with($img, 'http')) {
            if (isCloudinaryUrl($img)) { $updated[] = $img; $skip++; continue; }
            $newUrl = cloudinaryUploadFromUrl($img, 'pawland/reviews');
        } else {
            $absPath = BASE_PATH . ltrim($img, '/');
            if (!file_exists($absPath)) { $updated[] = $img; $fail++; $log[] = "[reviews.images #{$row['review_id']}] fail: ไม่พบ $absPath"; continue; }
            $newUrl = cloudinaryUpload($absPath, 'pawland/reviews');
        }
        if ($newUrl) { $updated[] = $newUrl; $ok++; $log[] = "[reviews.images #{$row['review_id']}] ok: $newUrl"; }
        else         { $updated[] = $img;    $fail++; $log[] = "[reviews.images #{$row['review_id']}] fail: $img"; }
    }
    $pdo->prepare("UPDATE reviews SET images = ? WHERE review_id = ?")
        ->execute([json_encode($updated, JSON_UNESCAPED_UNICODE), $row['review_id']]);
}

// ============================================================
// แสดงผล
// ============================================================
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8"><title>Cloudinary Migration</title>
<style>
body{font-family:sans-serif;padding:20px;background:#111;color:#eee}
h1{color:#f90}
.ok{color:#4f4}
.fail{color:#f44}
.skip{color:#888}
.summary{font-size:1.2em;margin:16px 0;padding:12px;background:#222;border-radius:8px}
pre{background:#222;padding:12px;border-radius:8px;font-size:.8em;max-height:600px;overflow:auto}
</style>
</head>
<body>
<h1>🐾 Pawland — Cloudinary Migration</h1>
<div class="summary">
  ✅ สำเร็จ: <strong class="ok"><?= $ok ?></strong> &nbsp;
  ⏭ ข้าม: <strong class="skip"><?= $skip ?></strong> &nbsp;
  ❌ ล้มเหลว: <strong class="fail"><?= $fail ?></strong>
</div>
<pre><?php foreach ($log as $line) {
    $cls = str_contains($line, '] ok:') ? 'ok' : (str_contains($line, '] fail:') ? 'fail' : 'skip');
    echo "<span class=\"$cls\">" . htmlspecialchars($line) . "</span>\n";
} ?></pre>
</body>
</html>
