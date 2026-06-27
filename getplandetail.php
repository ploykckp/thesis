<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json; charset=utf-8');

$planId = (int)($_GET['plan_id'] ?? 0);

if (!$planId) { 
    echo json_encode(['success' => false, 'msg' => 'no plan_id']); 
    exit; 
}

if (!$pdo) { 
    echo json_encode(['success' => false, 'msg' => 'pdo_null']); 
    exit; 
}

try {
    $stmt = $pdo->prepare("SELECT * FROM travel_plan WHERE plan_id = :id LIMIT 1");
    $stmt->execute([':id' => $planId]);
    $plan = $stmt->fetch();

    if (!$plan) { 
        echo json_encode(['success' => false, 'message' => 'ไม่พบแพลน']); 
        exit; 
    }

    $stmtP = $pdo->prepare("
        SELECT tpp.*,
               COALESCE(pl.place_name, tpp.place_name) AS place_name,
               pl.place_image
        FROM travel_plan_place tpp
        LEFT JOIN places pl ON pl.place_id = tpp.place_id
        WHERE tpp.plan_id = :pid
        ORDER BY tpp.visit_date ASC, tpp.order_num ASC
    ");
    $stmtP->execute([':pid' => $planId]);
    $places = $stmtP->fetchAll();

    echo json_encode(['success' => true, 'plan' => $plan, 'places' => $places]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}