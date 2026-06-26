<?php
// ================================================
//  check_entre_status.php — AJAX endpoint
//  ผู้ประกอบการ polling สถานะการอนุมัติ
// ================================================
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['entre_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$entre_id = (int)$_SESSION['entre_id'];

try {
    $stmt = $pdo->prepare("SELECT approval_status, rejection_reason FROM account_entre WHERE entre_id = :id");
    $stmt->execute([':id' => $entre_id]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Not found']);
        exit;
    }

    $_SESSION['entre_status'] = $row['approval_status'];

    echo json_encode([
        'status' => $row['approval_status'],
        'reason' => $row['rejection_reason'] ?? ''
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}