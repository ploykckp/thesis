<?php
// ================================================
//  toggle_favorite.php — Add / Remove รายการโปรด
// ================================================
ob_start();
session_start();

// ── ส่ง JSON เสมอ ──
function send_json($data) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── ตรวจ method ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Method not allowed']);
}

// ── ตรวจ session ──
if (empty($_SESSION['user_id'])) {
    send_json(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
}

// ── ตรวจ place_id ──
$user_id  = (int)$_SESSION['user_id'];
$place_id = (int)($_POST['place_id'] ?? 0);

if ($place_id <= 0) {
    send_json(['success' => false, 'message' => 'place_id ไม่ถูกต้อง']);
}

// ── เชื่อมต่อ DB ──
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=pawland;charset=utf8mb4',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    send_json(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}

// ── Toggle favorite ──
try {
    $check = $pdo->prepare("SELECT favorite_id FROM favorite WHERE user_id = ? AND place_id = ?");
    $check->execute([$user_id, $place_id]);
    $exists = $check->fetch();

    if ($exists) {
        $pdo->prepare("DELETE FROM favorite WHERE user_id = ? AND place_id = ?")
            ->execute([$user_id, $place_id]);
        $action = 'removed';
    } else {
        $pdo->prepare("INSERT INTO favorite (user_id, place_id) VALUES (?, ?)")
            ->execute([$user_id, $place_id]);
        $action = 'added';
    }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM favorite WHERE user_id = ?");
    $cnt->execute([$user_id]);
    $count = (int)$cnt->fetchColumn();

    send_json(['success' => true, 'action' => $action, 'count' => $count]);

} catch (PDOException $e) {
    send_json(['success' => false, 'message' => $e->getMessage()]);
}