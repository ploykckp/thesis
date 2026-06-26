<?php
// ================================================
//  update_place.php — แก้ไขข้อมูลสถานที่
// ================================================
session_start();
require_once 'connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['entre_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อน']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']); exit;
}

$entre_id   = (int)$_SESSION['entre_id'];
$place_id   = (int)($_POST['place_id']   ?? 0);

if (!$place_id) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบ place_id']); exit;
}

// ตรวจสิทธิ์
try {
    $chk = $pdo->prepare("SELECT place_id FROM places WHERE place_id=? AND entre_id=?");
    $chk->execute([$place_id, $entre_id]);
    if (!$chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์แก้ไขสถานที่นี้']); exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด']); exit;
}

// รับค่าจาก form
$place_name  = trim($_POST['place_name']  ?? '');
$category    = trim($_POST['category']    ?? '');
$description = trim($_POST['description'] ?? '');
$phone       = trim($_POST['phone']       ?? '');
$open_time   = trim($_POST['open_time']   ?? '');
$close_time  = trim($_POST['close_time']  ?? '');
$address     = trim($_POST['address']     ?? '');
$province    = trim($_POST['province']    ?? '');
$latitude    = (float)($_POST['latitude']  ?? 0);
$longitude   = (float)($_POST['longitude'] ?? 0);
$pet_allowed = trim($_POST['pet_allowed'] ?? 'no');
$pet_type    = trim($_POST['pet_type']    ?? '');
$pet_size    = trim($_POST['pet_size']    ?? '');
$extra_cost  = trim($_POST['extra_cost']  ?? '');
$pet_rules   = trim($_POST['pet_rules']   ?? '');
$amenities   = trim($_POST['amenities']   ?? '');
$pet_amenities = trim($_POST['pet_amenities'] ?? '');

if (!$place_name) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อสถานที่']); exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE places SET
            place_name=?, category=?, description=?, phone=?,
            open_time=?, close_time=?, address=?, province=?,
            latitude=?, longitude=?, pet_allowed=?,
            pet_type_allowed=?, pet_size_allowed=?,
            extra_cost=?, pet_rules=?, amenities=?, pet_amenities=?,
            status='pending',
            updated_at=NOW()
        WHERE place_id=? AND entre_id=?
    ");
    $stmt->execute([
        $place_name, $category, $description, $phone,
        $open_time, $close_time, $address, $province,
        $latitude, $longitude, $pet_allowed,
        $pet_type, $pet_size,
        $extra_cost, $pet_rules, $amenities, $pet_amenities,
        $place_id, $entre_id,
    ]);

    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลสำเร็จ กำลังรอแอดมินยืนยันอีกครั้ง']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()]);
}