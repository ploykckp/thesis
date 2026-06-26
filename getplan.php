<?php
// ================================================
//  getplans.php — ดึงรายการ travel_plan ทั้งหมด
// ================================================
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (!$pdo) { echo json_encode(['success' => false, 'plans' => []]); exit; }

$userId = $_SESSION['user_id'] ?? null;

$MONTHS_TH = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

try {
    // ── ดึง plans + นับจำนวน places ────────────────────
    if (!$userId) {
        echo json_encode(['success' => true, 'plans' => []]);
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT tp.plan_id, tp.trip_name, tp.created_at,
                COUNT(tpp.plan_id) AS place_count
         FROM travel_plan tp
         LEFT JOIN travel_plan_place tpp ON tpp.plan_id = tp.plan_id
         WHERE tp.user_id = :uid
         GROUP BY tp.plan_id
         ORDER BY tp.created_at DESC LIMIT 50"
    );
    $stmt->execute([':uid' => $userId]);

    $plans = $stmt->fetchAll();

    // format วันที่เป็นไทย
    foreach ($plans as &$pl) {
        $dt = new DateTime($pl['created_at']);
        $m  = (int)$dt->format('n');
        $pl['created_th'] = $dt->format('j') . ' ' . $MONTHS_TH[$m] . ' ' . ((int)$dt->format('Y') + 543);
    }

    echo json_encode(['success' => true, 'plans' => $plans]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'plans' => [], 'message' => $e->getMessage()]);
}