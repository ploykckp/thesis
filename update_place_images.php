<?php
// ================================================
//  update_place_images.php — เพิ่มรูปภาพให้สถานที่
// ================================================
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['entre_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$entre_id = (int)$_SESSION['entre_id'];
$place_id = (int)($_POST['place_id'] ?? 0);

if (!$place_id) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบ place_id']);
    exit;
}

// ตรวจสอบว่าเป็นสถานที่ของ entrepreneur คนนี้จริงๆ
try {
    $chk = $pdo->prepare("SELECT place_id, place_image, all_images FROM places WHERE place_id = ? AND entre_id = ?");
    $chk->execute([$place_id, $entre_id]);
    $place = $chk->fetch();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด']);
    exit;
}

if (!$place) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสถานที่ หรือไม่มีสิทธิ์แก้ไข']);
    exit;
}

// ตรวจว่ามีไฟล์ส่งมา
if (empty($_FILES['new_images']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเลือกรูปภาพอย่างน้อย 1 รูป']);
    exit;
}

// Upload directory
$uploadDir = __DIR__ . '/uploads/places/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// รูปเดิมทั้งหมด
$existingImages = array_filter(
    array_map('trim', explode(',', $place['all_images'] ?? ''))
);

// จำกัดไม่เกิน 10 รูปรวม
$maxTotal  = 10;
$remaining = $maxTotal - count($existingImages);
if ($remaining <= 0) {
    echo json_encode(['success' => false, 'message' => "ครบ {$maxTotal} รูปแล้ว ไม่สามารถเพิ่มได้อีก"]);
    exit;
}

// อัปโหลดรูปใหม่
$files     = $_FILES['new_images'];
$count     = count($files['name']);
$newImages = [];
$allowed   = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
$maxSize   = 5 * 1024 * 1024; // 5 MB

for ($i = 0; $i < min($count, $remaining); $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
    if ($files['size'][$i] > $maxSize) continue;

    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) continue;

    $fname = 'place_' . $place_id . '_' . time() . '_' . $i . '.' . $ext;
    $dest  = $uploadDir . $fname;

    if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
        $newImages[] = 'uploads/places/' . $fname;
    }
}

if (empty($newImages)) {
    echo json_encode(['success' => false, 'message' => 'อัปโหลดไม่สำเร็จ กรุณาตรวจสอบขนาดและประเภทไฟล์']);
    exit;
}

// รวมรูปเดิม + รูปใหม่
$allImages   = array_merge($existingImages, $newImages);
$allImagesStr = implode(',', $allImages);

// รูปหลัก: ถ้ายังไม่มีให้ใช้รูปแรก
$placeImage = !empty($place['place_image']) ? $place['place_image'] : $allImages[0];

try {
    $upd = $pdo->prepare("UPDATE places SET place_image = ?, all_images = ?, updated_at = NOW() WHERE place_id = ?");
    $upd->execute([$placeImage, $allImagesStr, $place_id]);

    echo json_encode([
        'success'      => true,
        'message'      => 'เพิ่มรูปภาพสำเร็จ ' . count($newImages) . ' รูป',
        'new_images'   => $newImages,
        'all_images'   => $allImagesStr,
        'total'        => count($allImages),
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()]);
}