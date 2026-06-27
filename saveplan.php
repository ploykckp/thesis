<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json; charset=utf-8');

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || empty($body['trip_name']) || empty($body['places'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้']);
    exit;
}

$tripName = trim($body['trip_name']);
$places   = $body['places'];
$userId   = $_SESSION['user_id'] ?? null;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO travel_plan (trip_name, user_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$tripName, $userId]);
    $planId = (int)$pdo->lastInsertId();

    $stmtP = $pdo->prepare("
        INSERT INTO travel_plan_place
            (plan_id, place_id, place_name, visit_date, check_in, check_out, order_num)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($places as $i => $p) {
        $placeId   = (int)($p['place_id'] ?? 0) ?: null;
        $pName     = trim($p['name'] ?? '');
        $visitDate = $p['visit_date'] ?? $p['check_in'] ?? null;
        $checkIn   = $p['check_in']   ?? $visitDate;
        $checkOut  = $p['check_out']  ?? $visitDate;
        $orderNum  = $i + 1;

        $stmtP->execute([$planId, $placeId, $pName, $visitDate, $checkIn, $checkOut, $orderNum]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'plan_id' => $planId]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("saveplan: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()]);
}