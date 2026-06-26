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

try {
    $pdo = new PDO('mysql:host=localhost;dbname=pawland;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล']);
    exit;
}

// Auto-create reviews table ถ้ายังไม่มี
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reviews` (
        `review_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `place_id` int(11) NOT NULL,
        `rating` int(11) NOT NULL,
        `comment` text DEFAULT NULL,
        `images` text DEFAULT NULL,
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `rejection_reason` varchar(255) DEFAULT NULL,
        `rejection_note` text DEFAULT NULL,
        `notified` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`review_id`),
        KEY `idx_place_id` (`place_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) { /* table already exists */ }

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

// บันทึกรีวิว + รูปภาพ
$imagePaths = [];
if (!empty($_FILES['review_images'])) {
    $uploadDir = 'uploads/reviews/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $files = $_FILES['review_images'];
    $count = is_array($files['name']) ? count($files['name']) : 1;
    $count = min($count, 5); // จำกัด 5 รูป

    for ($i = 0; $i < $count; $i++) {
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $origName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

        if ($error !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;

        $newName = 'review_' . $user_id . '_' . time() . '_' . $i . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (move_uploaded_file($tmpName, $dest)) {
            $imagePaths[] = $dest;
        }
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