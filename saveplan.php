<?php
// ================================================
//  saveplan.php — บันทึกแพลนลงฐานข้อมูล
// ================================================
session_start();
require_once 'db.php';

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

// ══════════════════════════════════════════════════════
//  SCHEMA SETUP — ทำก่อน transaction เพราะ DDL ใน MySQL
//  จะ auto-commit ทำให้ rollBack() พัง
// ══════════════════════════════════════════════════════

// 1) Create travel_plan table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `travel_plan` (
        `plan_id`    INT AUTO_INCREMENT PRIMARY KEY,
        `trip_name`  VARCHAR(255) NOT NULL,
        `user_id`    INT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 2) Add extra columns to travel_plan_place if missing
$extraCols = [
    'trip_name'  => "ALTER TABLE `travel_plan_place` ADD COLUMN `trip_name`  VARCHAR(255) NULL AFTER `plan_id`",
    'check_in'   => "ALTER TABLE `travel_plan_place` ADD COLUMN `check_in`   DATE NULL AFTER `visit_date`",
    'check_out'  => "ALTER TABLE `travel_plan_place` ADD COLUMN `check_out`  DATE NULL AFTER `check_in`",
    'place_name' => "ALTER TABLE `travel_plan_place` ADD COLUMN `place_name` VARCHAR(255) NULL AFTER `place_id`",
    'order_num'  => "ALTER TABLE `travel_plan_place` ADD COLUMN `order_num`  SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `check_out`",
];
foreach ($extraCols as $col => $sql) {
    $exists = $pdo->query("SHOW COLUMNS FROM `travel_plan_place` LIKE '$col'")->fetch();
    if (!$exists) $pdo->exec($sql);
}

// 3) Fix PRIMARY KEY — ถ้า PK มีแค่คอลัมน์เดียวจะเกิด Duplicate entry เมื่อบันทึกหลาย place
$pkCols = $pdo->query("
    SELECT COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'travel_plan_place'
      AND CONSTRAINT_NAME = 'PRIMARY'
    ORDER BY ORDINAL_POSITION
")->fetchAll(PDO::FETCH_COLUMN);

if (count($pkCols) === 1) {
    $colName = $pkCols[0];

    // ถ้าคอลัมน์มี AUTO_INCREMENT ต้องเอาออกก่อน ไม่งั้น DROP PRIMARY KEY ไม่ได้
    $hasAutoInc = $pdo->query("
        SELECT COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'travel_plan_place'
          AND COLUMN_NAME  = '$colName'
          AND EXTRA LIKE '%auto_increment%'
    ")->fetch();

    if ($hasAutoInc) {
        $colType = $pdo->query("
            SELECT COLUMN_TYPE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'travel_plan_place'
              AND COLUMN_NAME  = '$colName'
        ")->fetchColumn();
        $pdo->exec("ALTER TABLE `travel_plan_place` MODIFY COLUMN `$colName` $colType NOT NULL");
    }

    $pdo->exec("ALTER TABLE `travel_plan_place` DROP PRIMARY KEY");
    $pdo->exec("ALTER TABLE `travel_plan_place` ADD PRIMARY KEY (`plan_id`, `order_num`)");
}

// ══════════════════════════════════════════════════════
//  DATA INSERT — ทำใน transaction
// ══════════════════════════════════════════════════════
try {
    $pdo->beginTransaction();

    // Insert into travel_plan
    $stmt = $pdo->prepare("INSERT INTO `travel_plan` (trip_name, user_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$tripName, $userId]);
    $planId = (int)$pdo->lastInsertId();

    // Insert each place
    $stmtP = $pdo->prepare("
        INSERT INTO `travel_plan_place`
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