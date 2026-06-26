<?php
// uploadpetimage.php — Upload and save pet profile photo
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

// ── Debug info (remove after fixing) ──
$debug = [
    'method'    => $_SERVER['REQUEST_METHOD'],
    'pdo_ok'    => ($pdo !== null),
    'session'   => $_SESSION,
    'post'      => $_POST,
    'files_err' => $_FILES['pet_image']['error'] ?? 'no file key',
    'upload_dir_writable' => is_writable(__DIR__),
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$pdo) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง', 'debug' => $debug]); exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ', 'debug' => $debug]); exit;
}

$petId = (int)($_POST['pet_id'] ?? 0);
if ($petId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลสัตว์เลี้ยง', 'debug' => $debug]); exit;
}

// Verify pet belongs to user
try {
    $stmt = $pdo->prepare("SELECT pet_id, pet_image FROM pets WHERE pet_id = ? AND user_id = ?");
    $stmt->execute([$petId, $userId]);
    $pet = $stmt->fetch();
    if (!$pet) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์แก้ไข', 'debug' => $debug]); exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในฐานข้อมูล: ' . $e->getMessage(), 'debug' => $debug]); exit;
}

if (empty($_FILES['pet_image']) || $_FILES['pet_image']['error'] !== UPLOAD_ERR_OK) {
    $errCodes = [0=>'OK',1=>'INI_SIZE',2=>'FORM_SIZE',3=>'PARTIAL',4=>'NO_FILE',6=>'NO_TMP_DIR',7=>'CANT_WRITE',8=>'EXTENSION'];
    $errCode  = $_FILES['pet_image']['error'] ?? -1;
    echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์รูปภาพ: ' . ($errCodes[$errCode] ?? $errCode), 'debug' => $debug]); exit;
}

$file     = $_FILES['pet_image'];
$maxSize  = 5 * 1024 * 1024;
$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'ไฟล์ต้องไม่เกิน 5MB']); exit;
}

// Use $_FILES mime_type as fallback if finfo unavailable
if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} else {
    $mimeType = $file['type'];
}

if (!in_array($mimeType, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF, WEBP) — detected: ' . $mimeType]); exit;
}

$ext       = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mimeType];
$uploadDir = __DIR__ . '/uploads/pets/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เขียนไฟล์ใน uploads/pets/', 'dir' => $uploadDir]); exit;
}

// Delete old image file if exists
if (!empty($pet['pet_image'])) {
    $oldFile = __DIR__ . '/' . ltrim($pet['pet_image'], '/');
    if (file_exists($oldFile)) @unlink($oldFile);
}

$filename   = 'pet_' . $petId . '_' . time() . '.' . $ext;
$uploadPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'move_uploaded_file ล้มเหลว', 'path' => $uploadPath]); exit;
}

$imageUrl = 'uploads/pets/' . $filename;

try {
    $stmt = $pdo->prepare("UPDATE pets SET pet_image = ? WHERE pet_id = ? AND user_id = ?");
    $stmt->execute([$imageUrl, $petId, $userId]);
    echo json_encode(['success' => true, 'image_url' => $imageUrl]);
} catch (PDOException $e) {
    @unlink($uploadPath);
    echo json_encode(['success' => false, 'message' => 'บันทึกฐานข้อมูลไม่สำเร็จ: ' . $e->getMessage()]);
}