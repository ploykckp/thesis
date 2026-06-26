<?php
// ================================================
//  get_favorites.php — ดึงรายการโปรดของผู้ใช้
//  GET → JSON { success, favorites: [...], count }
// ================================================
session_start();
require_once 'connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'favorites' => [], 'count' => 0]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!$pdo) {
    echo json_encode(['success' => false, 'favorites' => [], 'count' => 0]);
    exit;
}

try {
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        fav_id    INT AUTO_INCREMENT PRIMARY KEY,
        user_id   INT NOT NULL,
        place_id  INT NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY uq_user_place (user_id, place_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare("
        SELECT p.place_id, p.place_name, p.category, p.province, p.place_image
        FROM favorites f
        JOIN places p ON p.place_id = f.place_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success'   => true,
        'favorites' => $rows,
        'count'     => count($rows)
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'favorites' => [], 'count' => 0]);
}