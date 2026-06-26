<?php
// ================================================
//  getplandetail.php — ดึงรายละเอียด trip + places
//  GET ?plan_id=X
// ================================================
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

$planId = (int)($_GET['plan_id'] ?? 0);
if (!$planId || !$pdo) { echo json_encode(['success' => false]); exit; }

try {
    // trip header
    $stmt = $pdo->prepare("SELECT * FROM `travel_plan` WHERE plan_id = :id LIMIT 1");
    $stmt->execute([':id' => $planId]);
    $plan = $stmt->fetch();

    if (!$plan) { echo json_encode(['success' => false, 'message' => 'ไม่พบแพลน']); exit; }

    // places — join กับ places table เพื่อดึงชื่อสถานที่จริง
    $stmtP = $pdo->prepare("
        SELECT tpp.*,
               COALESCE(pl.place_name, tpp.place_name) AS place_name,
               pl.place_image
        FROM `travel_plan_place` tpp
        LEFT JOIN `places` pl ON pl.place_id = tpp.place_id
        WHERE tpp.plan_id = :pid
        ORDER BY tpp.visit_date ASC, tpp.order_num ASC
    ");
    $stmtP->execute([':pid' => $planId]);
    $places = $stmtP->fetchAll();

    echo json_encode(['success' => true, 'plan' => $plan, 'places' => $places]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}