<?php
// ================================================
//  download_photos.php
//  ดาวน์โหลดรูปจาก Google Places มาเก็บใน server
//  แล้ว UPDATE place_image + all_images ใน DB
//  ให้ชี้ไปที่ไฟล์ local แทน URL ของ Google
//  ⚠️ ลบไฟล์นี้ออกหลังใช้งาน
// ================================================
set_time_limit(600); // 10 นาที
ini_set('memory_limit', '256M');

$pdo = null;
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pawland;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die('DB Error: ' . $e->getMessage()); }

$saveDir = __DIR__ . '/uploads/places_google/';
if (!is_dir($saveDir)) mkdir($saveDir, 0755, true);

// ── ดาวน์โหลดรูปจาก URL ──────────────────────────
function downloadImg(string $url, string $savePath): bool {
    if (file_exists($savePath)) return true; // มีแล้วข้ามได้

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$data || $code !== 200) return false;
    return file_put_contents($savePath, $data) !== false;
}

// ── ดึงสถานที่ที่มี URL จาก Google ──────────────
$places = $pdo->query(
    "SELECT place_id, place_name, place_image, all_images
     FROM places
     WHERE status='approved'
     AND (place_image LIKE '%googleapis%' OR all_images LIKE '%googleapis%')
     ORDER BY place_id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$results    = [];
$updateStmt = $pdo->prepare("UPDATE places SET place_image=?, all_images=?, updated_at=NOW() WHERE place_id=?");

foreach ($places as $place) {
    $pid      = $place['place_id'];
    $name     = $place['place_name'];
    $imgUrls  = [];

    // รวม place_image + all_images
    if (!empty($place['place_image'])) {
        $imgUrls[] = $place['place_image'];
    }
    if (!empty($place['all_images'])) {
        foreach (explode(',', $place['all_images']) as $u) {
            $u = trim($u);
            if ($u && !in_array($u, $imgUrls)) $imgUrls[] = $u;
        }
    }

    $localUrls = [];
    $downloaded = 0;
    $failed     = 0;

    foreach ($imgUrls as $idx => $url) {
        if (!str_contains($url, 'googleapis')) {
            $localUrls[] = $url; // ไม่ใช่ Google URL ใช้เดิม
            continue;
        }

        $fname    = 'place_' . $pid . '_' . ($idx + 1) . '.jpg';
        $savePath = $saveDir . $fname;
        $localUrl = 'uploads/places_google/' . $fname;

        if (downloadImg($url, $savePath)) {
            $localUrls[] = $localUrl;
            $downloaded++;
        } else {
            $failed++;
        }
        usleep(100000); // 0.1s
    }

    if (empty($localUrls)) {
        $results[] = ['id'=>$pid,'name'=>$name,'status'=>'❌ ดาวน์โหลดไม่ได้','dl'=>0,'fail'=>$failed];
        continue;
    }

    $newMain      = $localUrls[0];
    $newAllImages = implode(',', $localUrls);
    $updateStmt->execute([$newMain, $newAllImages, $pid]);

    $results[] = ['id'=>$pid,'name'=>$name,'status'=>'✅ สำเร็จ','dl'=>$downloaded,'fail'=>$failed,'main'=>$newMain];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Download Place Photos</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body  { font-family:'Kanit',sans-serif; background:#f1f5f9; padding:40px 24px; }
        h1    { color:#123451; margin-bottom:4px; }
        .sub  { color:#64748b; font-size:14px; margin-bottom:28px; }
        table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        th    { background:#123451; color:#fff; padding:12px 16px; text-align:left; font-size:13px; }
        td    { padding:10px 16px; font-size:13px; border-bottom:1px solid #e2e8f0; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        .ok   { color:#065f46; font-weight:600; }
        .err  { color:#991b1b; }
        .thumb { width:64px; height:48px; object-fit:cover; border-radius:6px; }
        .summary  { margin-top:20px; background:#d1fae5; border-radius:10px; padding:14px 20px; font-size:14px; color:#065f46; }
        .warnbox  { margin-top:12px; background:#fef3c7; border-radius:10px; padding:14px 20px; font-size:13px; color:#92400e; }
    </style>
</head>
<body>
<h1>📥 Download รูปมาเก็บใน Server</h1>
<p class="sub">ดาวน์โหลดรูปจาก Google Places มาเก็บใน <code>uploads/places_google/</code> แล้วอัปเดต DB</p>

<table>
    <thead>
        <tr><th>ID</th><th>ชื่อสถานที่</th><th>สถานะ</th><th>โหลดได้</th><th>ล้มเหลว</th><th>Preview</th></tr>
    </thead>
    <tbody>
    <?php foreach ($results as $r): ?>
    <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td class="<?= str_contains($r['status'],'✅') ? 'ok' : 'err' ?>"><?= $r['status'] ?></td>
        <td><?= $r['dl'] ?> รูป</td>
        <td><?= $r['fail'] > 0 ? $r['fail'].' รูป' : '–' ?></td>
        <td>
            <?php if (!empty($r['main'])): ?>
            <img src="<?= htmlspecialchars($r['main']) ?>" class="thumb" onerror="this.style.display='none'">
            <?php else: ?>–<?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$ok   = count(array_filter($results, fn($r) => str_contains($r['status'],'✅')));
$fail = count($results) - $ok;
$totalDl = array_sum(array_column($results, 'dl'));
?>
<div class="summary">
    ✅ สำเร็จ <strong><?= $ok ?></strong> สถานที่ &nbsp;|&nbsp;
    ❌ ล้มเหลว <strong><?= $fail ?></strong> &nbsp;|&nbsp;
    📥 ดาวน์โหลดรูปทั้งหมด <strong><?= $totalDl ?></strong> รูป
</div>
<div class="warnbox">⚠️ <strong>ลบไฟล์นี้ออกหลังใช้งาน</strong> → <code>C:\xampp\htdocs\pawland\download_photos.php</code></div>
</body>
</html>