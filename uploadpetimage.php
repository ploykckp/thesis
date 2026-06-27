<?php
session_start();
require_once 'connect.php';
require_once 'cloudinary_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$pdo) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']); exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']); exit;
}

$petId = (int)($_POST['pet_id'] ?? 0);
if ($petId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลสัตว์เลี้ยง']); exit;
}

try {
    $stmt = $pdo->prepare("SELECT pet_id FROM pets WHERE pet_id = ? AND user_id = ?");
    $stmt->execute([$petId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์แก้ไข']); exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
}

if (empty($_FILES['pet_image']) || $_FILES['pet_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์รูปภาพ']); exit;
}

$file    = $_FILES['pet_image'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'ไฟล์ต้องไม่เกิน 5MB']); exit;
}
if (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะไฟล์รูปภาพ']); exit;
}

$imageUrl = cloudinaryUpload($file['tmp_name'], 'pawland/pets');
if (!$imageUrl) {
    echo json_encode(['success' => false, 'message' => 'อัพโหลดรูปไม่สำเร็จ']); exit;
}

try {
    $pdo->prepare("UPDATE pets SET pet_image = ? WHERE pet_id = ? AND user_id = ?")
        ->execute([$imageUrl, $petId, $userId]);
    echo json_encode(['success' => true, 'image_url' => $imageUrl]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}