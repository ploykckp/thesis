<?php
// ================================================
//  get_favorites.php — ดึงรายการโปรดของผู้ใช้
// ================================================
ob_start();

session_start();

$pdo = null;
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=pawland;charset=utf8',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'favorites' => [], 'count' => 0]);
    exit;
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'favorites' => [], 'count' => 0]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT p.place_id, p.place_name, p.category, p.province, p.place_image
        FROM favorite f
        JOIN places p ON p.place_id = f.place_id
        WHERE f.user_id = ?
        ORDER BY f.favorite_id DESC
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