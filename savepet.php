<?php
// savepet.php — INSERT new pet (always adds, never updates)
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$pdo) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']); exit;
}

$userId         = $_SESSION['user_id'] ?? null;
$petId          = (int)($_POST['pet_id']          ?? 0);  // >0 = edit existing
$petName        = trim($_POST['pet_name']          ?? '');
$petType        = trim($_POST['pet_type']          ?? '');
$petBreed       = trim($_POST['pet_breed']         ?? '');
$petGender      = trim($_POST['pet_gender']        ?? '');
$petBirthday    = trim($_POST['pet_birthday']      ?? '') ?: null;  // DATE yyyy-mm-dd
$petWeight      = (int)($_POST['pet_weight']       ?? 0);
$petBehav       = trim($_POST['pet_behaviors']     ?? '');
$fleaTick       = trim($_POST['flea_tick']         ?? '') ?: null;  // DATE yyyy-mm-dd
$microNum       = trim($_POST['microship_number']  ?? '') ?: null;
$microDate      = trim($_POST['microship_date']    ?? '') ?: null;  // DATE yyyy-mm-dd

// Calculate age from birthday
$petOld = 0;
if ($petBirthday) {
    try {
        $bd = new DateTime($petBirthday);
        $now = new DateTime();
        $petOld = (int)$bd->diff($now)->y;
    } catch (Exception $e) { $petOld = 0; }
}

if (!$petName || !$petType) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อและประเภทสัตว์เลี้ยง']); exit;
}

try {
    if ($petId > 0) {
        // Edit existing pet (must belong to this user)
        $stmt = $pdo->prepare("UPDATE pets SET pet_name=?,pet_type=?,pet_breed=?,pet_gender=?,pet_birthday=?,pet_old=?,pet_weight=?,pet_behaviors=?,flea_tick=?,microship_number=?,microship_date=? WHERE pet_id=? AND user_id=?");
        $stmt->execute([$petName,$petType,$petBreed,$petGender,$petBirthday,$petOld,$petWeight,$petBehav,$fleaTick,$microNum,$microDate,$petId,$userId]);
    } else {
        // Add new pet
        $stmt = $pdo->prepare("INSERT INTO pets (user_id,pet_name,pet_type,pet_breed,pet_gender,pet_birthday,pet_old,pet_weight,pet_behaviors,flea_tick,microship_number,microship_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$userId,$petName,$petType,$petBreed,$petGender,$petBirthday,$petOld,$petWeight,$petBehav,$fleaTick,$microNum,$microDate]);
        $petId = (int)$pdo->lastInsertId();
    }
    echo json_encode(['success' => true, 'pet_id' => $petId]);
} catch (PDOException $e) {
    error_log("savepet: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}