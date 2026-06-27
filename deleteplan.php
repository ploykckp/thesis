<?php
// ================================================
//  deleteplan.php — ลบแพลนและ places ทั้งหมด
//  POST JSON: { plan_id }
// ================================================
session_start();
require_once 'connect.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$pdo) {
    echo json_encode(['success' => false]); exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$planId = (int)($body['plan_id'] ?? 0);

if (!$planId) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบ plan_id']); exit;
}

try {
    $pdo->beginTransaction();
    // ลบ places ก่อน (ในกรณีไม่มี CASCADE)
    $pdo->prepare("DELETE FROM `travel_plan_place` WHERE plan_id = ?")->execute([$planId]);
    // ลบ plan
    $pdo->prepare("DELETE FROM `travel_plan` WHERE plan_id = ?")->execute([$planId]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}