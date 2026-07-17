<?php
// ================================================
//  review_action.php — Admin อนุมัติ/ปฏิเสธรีวิว
// ================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/connect.php';
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$review_id = (int)($_POST['review_id'] ?? 0);
$action    = $_POST['action'] ?? '';  // approve | reject
$reason    = trim($_POST['reason'] ?? '');

if ($review_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}
if ($action === 'reject' && empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุเหตุผลที่ปฏิเสธ']);
    exit;
}

$status = $action === 'approve' ? 'approved' : 'rejected';
$stmt = $pdo->prepare("
    UPDATE reviews
    SET status = :status,
        rejection_reason = :reason,
        updated_at = NOW()
    WHERE review_id = :id
");
$stmt->execute([
    ':status' => $status,
    ':reason' => $action === 'reject' ? $reason : null,
    ':id'     => $review_id,
]);

echo json_encode(['success' => true, 'status' => $status]);