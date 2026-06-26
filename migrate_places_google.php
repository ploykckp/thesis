<?php
// ============================================================
// migrate_places_google.php
// Upload รูปใน uploads/places_google/ ขึ้น Cloudinary
// แล้ว update places.place_image และ places.all_images ใน DB
// ============================================================
set_time_limit(0);
ini_set('max_execution_time', 0);

require_once __DIR__ . '/cloudinary_config.php';

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

define('IMG_DIR', __DIR__ . '/uploads/places_google/');

$log  = [];
$ok   = 0;
$skip = 0;
$fail = 0;

// หา place_id ทั้งหมดจากชื่อไฟล์
$files = glob(IMG_DIR . 'place_*.jpg');
if (!$files) {
    die("ไม่พบไฟล์ใน " . IMG_DIR);
}

// จัดกลุ่มตาม place_id
$byPlace = [];
foreach ($files as $f) {
    $basename = basename($f);
    if (preg_match('/^place_(\d+)_(\d+)\.jpg$/', $basename, $m)) {
        $pid = (int)$m[1];
        $num = (int)$m[2];
        $byPlace[$pid][$num] = $f;
    }
}
ksort($byPlace);

foreach ($byPlace as $pid => $numMap) {
    ksort($numMap); // เรียง _1, _2, _3 ...

    // ดึงข้อมูล DB ปัจจุบัน
    $row = $pdo->prepare("SELECT place_image, all_images FROM places WHERE place_id=?");
    $row->execute([$pid]);
    $place = $row->fetch();
    if (!$place) {
        $log[] = "[place #$pid] skip: ไม่พบใน DB";
        $skip++;
        continue;
    }

    // ถ้า place_image ใน DB เป็น Cloudinary URL แล้ว → skip
    if (isCloudinaryUrl($place['place_image'] ?? '')) {
        $log[] = "[place #$pid] skip: migrate แล้ว";
        $skip++;
        continue;
    }

    $cloudUrls = [];
    foreach ($numMap as $num => $filePath) {
        $url = cloudinaryUpload($filePath, 'pawland/places');
        if ($url) {
            $cloudUrls[] = $url;
            $ok++;
            $log[] = "[place #$pid] ok: $url";
        } else {
            $fail++;
            $log[] = "[place #$pid] fail: " . basename($filePath);
        }
    }

    if ($cloudUrls) {
        $mainImage  = $cloudUrls[0];
        $allImages  = json_encode($cloudUrls, JSON_UNESCAPED_UNICODE);
        $pdo->prepare("UPDATE places SET place_image=?, all_images=?, updated_at=NOW() WHERE place_id=?")
            ->execute([$mainImage, $allImages, $pid]);
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8"><title>Cloudinary Migration — Places Google</title>
<style>
body{font-family:sans-serif;padding:20px;background:#111;color:#eee}
h1{color:#f90}
.ok{color:#4f4}.fail{color:#f44}.skip{color:#888}
.summary{font-size:1.2em;margin:16px 0;padding:12px;background:#222;border-radius:8px}
pre{background:#222;padding:12px;border-radius:8px;font-size:.8em;max-height:600px;overflow:auto}
</style>
</head>
<body>
<h1>🐾 Pawland — Cloudinary Migration (Places Google)</h1>
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
