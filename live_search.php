<?php
// ================================================
//  live_search.php — Live search suggestions (พิมพ์แล้วขึ้นทันที)
//  GET ?q=keyword
//  Returns JSON { results: [...] }
// ================================================
require_once 'connect.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if ($q === '' || mb_strlen($q) < 1) {
    echo json_encode(['results' => []]);
    exit;
}

if (!$pdo) {
    echo json_encode(['results' => [], 'error' => 'db_error']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT place_id, place_name, category, province, place_image
         FROM places
         WHERE status = 'approved'
           AND (place_name LIKE :q OR province LIKE :q OR address LIKE :q OR category LIKE :q)
         ORDER BY
             CASE WHEN place_name LIKE :q_start THEN 0 ELSE 1 END,
             place_name ASC
         LIMIT 8"
    );
    $stmt->execute([
        ':q'       => '%' . $q . '%',
        ':q_start' => $q . '%',
    ]);
    $results = $stmt->fetchAll();

    echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("live_search.php error: " . $e->getMessage());
    echo json_encode(['results' => [], 'error' => $e->getMessage()]);
}