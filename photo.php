<?php
// ================================================
//  photo.php — Proxy + Cache สำหรับ Google Places
// ================================================

$url = $_GET['url'] ?? '';
if (!$url || !str_contains($url, 'googleapis.com')) {
    http_response_code(400); exit;
}

$url      = urldecode($url);
$cacheDir = __DIR__ . '/uploads/photo_cache/';
$cacheKey = md5($url) . '.jpg';
$cachePath = $cacheDir . $cacheKey;

// สร้าง cache dir
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

// ส่งจาก cache ถ้ามี (7 วัน)
if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 604800) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=604800');
    header('X-Cache: HIT');
    readfile($cachePath);
    exit;
}

// ดึงจาก Google
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Pawland/1.0)',
]);
$data     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if (!$data || $httpCode !== 200) {
    http_response_code(404); exit;
}

// Cache ไว้ใน uploads/photo_cache/
file_put_contents($cachePath, $data);

header('Content-Type: ' . ($ctype ?: 'image/jpeg'));
header('Cache-Control: public, max-age=604800');
header('X-Cache: MISS');
echo $data;