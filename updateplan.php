<?php
// ================================================
//  updateplan.php — แก้ไขชื่อทริปและวันที่ของแต่ละสถานที่
//  POST JSON: { plan_id, trip_name, places: [{place_id, order_num, visit_date, check_in, check_out}] }
// ================================================
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$pdo) {
    echo json_encode(['success' => false]); exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$planId = (int)($body['plan_id'] ?? 0);
$name   = trim($body['trip_name'] ?? '');
$places = $body['places'] ?? [];

if (!$planId || !$name) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']); exit;
}

try {
    $pdo->beginTransaction();

    // Update trip name
    $stmt = $pdo->prepare("UPDATE `travel_plan` SET trip_name = :n, updated_at = NOW() WHERE plan_id = :id");
    $stmt->execute([':n' => $name, ':id' => $planId]);

    // Update dates for each place if provided
    if (!empty($places)) {
        $stmtU = $pdo->prepare("
            UPDATE `travel_plan_place`
            SET visit_date = :vd, check_in = :ci, check_out = :co
            WHERE plan_id = :pid AND order_num = :on
        ");
        foreach ($places as $p) {
            $orderNum  = (int)($p['order_num'] ?? 0);
            $visitDate = $p['visit_date'] ?? $p['check_in'] ?? null;
            $checkIn   = $p['check_in']   ?? $visitDate;
            $checkOut  = $p['check_out']  ?? $checkIn;
            if (!$orderNum) continue;
            $stmtU->execute([
                ':vd'  => $visitDate ?: null,
                ':ci'  => $checkIn   ?: null,
                ':co'  => $checkOut  ?: null,
                ':pid' => $planId,
                ':on'  => $orderNum,
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}