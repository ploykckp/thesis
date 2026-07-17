<?php
// ================================================
//  submit_review.php — รับข้อมูลรีวิวจาก user
// ================================================
session_start();
header('Content-Type: application/json');

// ตรวจสอบ login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนเขียนรีวิว']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/cloudinary_config.php';
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล']);
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$place_id = (int)($_POST['place_id'] ?? 0);
$rating   = (int)($_POST['rating']   ?? 0);
$comment  = trim($_POST['comment']   ?? '');

if ($place_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}
if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเขียนรีวิว']);
    exit;
}

// ตรวจสอบว่าสถานที่มีอยู่จริง
$chk = $pdo->prepare("SELECT place_id FROM places WHERE place_id = ? LIMIT 1");
$chk->execute([$place_id]);
if (!$chk->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสถานที่ดังกล่าว']);
    exit;
}

// ตรวจสอบไม่ให้รีวิวซ้ำ (pending/approved)
$dup = $pdo->prepare("SELECT review_id FROM reviews WHERE user_id=? AND place_id=? AND status IN ('pending','approved') LIMIT 1");
$dup->execute([$user_id, $place_id]);
if ($dup->fetch()) {
    echo json_encode(['success' => false, 'message' => 'คุณเคยรีวิวสถานที่นี้แล้ว กรุณารอการอนุมัติ']);
    exit;
}

// บันทึกรีวิว + รูปภาพ (upload ขึ้น Cloudinary แทนเก็บไฟล์ในเครื่อง)
$imagePaths = [];
if (!empty($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
    $files = $_FILES['review_images'];
    $count = is_array($files['name']) ? count($files['name']) : 1;
    $count = min($count, 5); // จำกัด 5 รูป

    for ($i = 0; $i < $count; $i++) {
        $tmpName  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $origName = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
        $error    = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];

        if ($error !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;

        $url = cloudinaryUpload($tmpName, 'pawland/reviews');
        if ($url) $imagePaths[] = $url;
    }
}

$stmt = $pdo->prepare("
    INSERT INTO reviews (user_id, place_id, rating, comment, images, status, created_at, updated_at)
    VALUES (:uid, :pid, :rating, :comment, :images, 'pending', NOW(), NOW())
");
$stmt->execute([
    ':uid'     => $user_id,
    ':pid'     => $place_id,
    ':rating'  => $rating,
    ':comment' => $comment,
    ':images'  => !empty($imagePaths) ? json_encode($imagePaths) : null,
]);

echo json_encode(['success' => true, 'message' => 'ส่งรีวิวสำเร็จ! กรุณารอการอนุมัติจาก Admin']);