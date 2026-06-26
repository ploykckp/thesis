<?php
// ================================================
//  fetch_place_address.php (v2 — ภาษาไทย)
//  ดึงที่อยู่ภาษาไทยจาก Google Places API (New)
//  ⚠️ ลบไฟล์นี้ออกหลังใช้งาน
// ================================================
set_time_limit(300);

$GOOGLE_API_KEY = 'AIzaSyB3H13UP43DsR6ED2NWaQXK9VaUqSKcCTA';

$pdo = null;
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pawland;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die('DB Error: ' . $e->getMessage()); }

function curlPost(string $url, array $body, array $headers = []): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

function curlGet(string $url, array $headers = []): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

function fetchThaiAddress(string $name, string $province, string $category, string $apiKey): ?string {
    // ค้นหาด้วยชื่อ + จังหวัด + ประเทศไทย (ไม่ใส่ประเภทที่ไม่เกี่ยว)
    $query = $name . ($province ? ' ' . $province : '') . ' ประเทศไทย';

    $data = curlPost(
        'https://places.googleapis.com/v1/places:searchText',
        [
            'textQuery'       => $query,
            'maxResultCount'  => 1,
            'languageCode'    => 'th',   // ← ขอภาษาไทย
        ],
        [
            'X-Goog-Api-Key: '   . $apiKey,
            'X-Goog-FieldMask: places.id,places.formattedAddress,places.displayName',
        ]
    );

    $placeId = $data['places'][0]['id'] ?? null;
    if (!$placeId) return null;

    // Place Details — ขอภาษาไทย
    $detail = curlGet(
        "https://places.googleapis.com/v1/places/{$placeId}?languageCode=th",
        [
            'X-Goog-Api-Key: '   . $apiKey,
            'X-Goog-FieldMask: formattedAddress',
        ]
    );

    return $detail['formattedAddress']
        ?? $data['places'][0]['formattedAddress']
        ?? null;
}

$places = $pdo->query(
    "SELECT place_id, place_name, province, category FROM places WHERE status='approved' ORDER BY place_id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$results    = [];
$updateStmt = $pdo->prepare("UPDATE places SET address=?, updated_at=NOW() WHERE place_id=?");

foreach ($places as $place) {
    $pid      = $place['place_id'];
    $name     = $place['place_name'];
    $province = $place['province'] ?? '';
    $category = $place['category'] ?? '';

    $address = fetchThaiAddress($name, $province, $category, $GOOGLE_API_KEY);

    if (!$address) {
        $results[] = ['id'=>$pid,'name'=>$name,'status'=>'❌ ไม่พบ','address'=>''];
        usleep(300000);
        continue;
    }

    $updateStmt->execute([$address, $pid]);
    $results[] = ['id'=>$pid,'name'=>$name,'status'=>'✅ สำเร็จ','address'=>$address];
    usleep(300000);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Fetch Place Address (ภาษาไทย)</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body  { font-family:'Kanit',sans-serif; background:#f1f5f9; padding:40px 24px; }
        h1    { color:#123451; margin-bottom:4px; }
        .sub  { color:#64748b; font-size:14px; margin-bottom:28px; }
        table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        th    { background:#123451; color:#fff; padding:12px 16px; text-align:left; font-size:13px; }
        td    { padding:10px 16px; font-size:13px; border-bottom:1px solid #e2e8f0; }
        tr:last-child td { border-bottom:none; }
        .ok   { color:#065f46; font-weight:600; }
        .err  { color:#991b1b; }
        .summary { margin-top:20px; background:#d1fae5; border-radius:10px; padding:14px 20px; font-size:14px; color:#065f46; }
        .warnbox { margin-top:12px; background:#fef3c7; border-radius:10px; padding:14px 20px; font-size:13px; color:#92400e; }
    </style>
</head>
<body>
<h1>📍 Fetch ที่อยู่ภาษาไทยจาก Google</h1>
<p class="sub">ดึงที่อยู่ภาษาไทยจาก Google Places API แล้วอัปเดต DB</p>

<table>
    <thead>
        <tr><th>ID</th><th>ชื่อสถานที่</th><th>สถานะ</th><th>ที่อยู่ภาษาไทย</th></tr>
    </thead>
    <tbody>
    <?php foreach ($results as $r): ?>
    <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td class="<?= str_contains($r['status'],'✅') ? 'ok' : 'err' ?>"><?= $r['status'] ?></td>
        <td><?= htmlspecialchars($r['address']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$ok   = count(array_filter($results, fn($r) => str_contains($r['status'],'✅')));
$fail = count($results) - $ok;
?>
<div class="summary">✅ สำเร็จ <strong><?= $ok ?></strong> สถานที่ &nbsp;|&nbsp; ❌ ไม่พบ <strong><?= $fail ?></strong> สถานที่</div>
<div class="warnbox">⚠️ <strong>ลบไฟล์นี้ออกหลังใช้งาน</strong> → <code>C:\xampp\htdocs\pawland\fetch_place_address.php</code></div>
</body>
</html>