<?php
// ============================================================
// upload_handler.php
// ใช้แทน local file_move ทุกที่ใน Pawland
// ============================================================

require_once __DIR__ . '/cloudinary_config.php';

// ============================================================
// อัพโหลดไฟล์จาก $_FILES ไป Cloudinary
// $fileKey   : key ใน $_FILES เช่น 'place_image'
// $folder    : Cloudinary folder
// $oldUrl    : URL เก่า (ถ้ามี) จะ return กลับถ้าไม่มีไฟล์ใหม่
// Returns    : secure_url string, หรือ $oldUrl ถ้าไม่มีไฟล์ใหม่
// Throws     : Exception ถ้า upload ล้มเหลว
// ============================================================
function handleImageUpload(
    string $fileKey,
    string $folder = 'pawland',
    string $oldUrl = ''
): string {
    // ถ้าไม่มีไฟล์ใหม่ → คืน URL เก่า
    if (empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return $oldUrl;
    }

    $file     = $_FILES[$fileKey];
    $tmpPath  = $file['tmp_name'];
    $mimeType = mime_content_type($tmpPath);

    // ตรวจ MIME
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mimeType, $allowed, true)) {
        throw new Exception('ประเภทไฟล์ไม่รองรับ: ' . $mimeType);
    }

    // ตรวจขนาด (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('ไฟล์ใหญ่เกินไป (สูงสุด 10MB)');
    }

    $url = cloudinaryUpload($tmpPath, $folder);
    if (!$url) {
        throw new Exception('อัพโหลดรูปภาพไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    }

    return $url;
}

// ============================================================
// อัพโหลดหลายรูปจาก $_FILES (input multiple)
// $fileKey   : key ใน $_FILES เช่น 'gallery_images'
// $folder    : Cloudinary folder
// $existing  : array ของ URL เก่า (จะ merge กับรูปใหม่)
// Returns    : array ของ secure_url ทั้งหมด
// ============================================================
function handleMultipleImageUpload(
    string $fileKey,
    string $folder = 'pawland',
    array $existing = []
): array {
    $urls = $existing;

    if (empty($_FILES[$fileKey]['tmp_name'])) return $urls;

    $files = $_FILES[$fileKey];

    // normalize: ถ้า input ไม่ใช่ multiple จะไม่เป็น array
    if (!is_array($files['tmp_name'])) {
        $files['tmp_name'] = [$files['tmp_name']];
        $files['error']    = [$files['error']];
        $files['size']     = [$files['size']];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    foreach ($files['tmp_name'] as $i => $tmpPath) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($tmpPath)) continue;
        if ($files['size'][$i] > 10 * 1024 * 1024) continue;

        $mimeType = mime_content_type($tmpPath);
        if (!in_array($mimeType, $allowed, true)) continue;

        $url = cloudinaryUpload($tmpPath, $folder);
        if ($url) $urls[] = $url;
    }

    return $urls;
}
