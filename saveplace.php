<?php
// ================================================
//  saveplace.php — Save new place from entrepreneur
// ================================================
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['entre_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$entre_id = (int)$_SESSION['entre_id'];

// Collect form fields
$place_name  = trim($_POST['place_name']  ?? '');
$category    = trim($_POST['category']    ?? '');
$description = trim($_POST['description'] ?? '');
$phone       = trim($_POST['phone']       ?? '');
$open_time   = trim($_POST['open_time']   ?? '');
$close_time  = trim($_POST['close_time']  ?? '');
$address     = trim($_POST['address']     ?? '');
$province    = trim($_POST['province']    ?? '');
$latitude    = (float)($_POST['latitude']  ?? 0);
$longitude   = (float)($_POST['longitude'] ?? 0);
$pet_allowed = trim($_POST['pet_allowed'] ?? 'no');
$pet_type    = trim($_POST['pet_type']    ?? '');
$pet_size    = trim($_POST['pet_size']    ?? '');
$extra_cost  = trim($_POST['extra_cost']  ?? '');
$pet_rules   = trim($_POST['pet_rules']   ?? '');
$amenities   = trim($_POST['amenities']   ?? '');
$pet_amenities = trim($_POST['pet_amenities'] ?? '');

if (!$place_name) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อสถานที่']);
    exit;
}

// ── Upload directory setup ──────────────────────
$uploadDir = __DIR__ . '/uploads/places/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── Handle license file ─────────────────────────
$license_file = '';
if (!empty($_FILES['license_file']['name'])) {
    $ext  = pathinfo($_FILES['license_file']['name'], PATHINFO_EXTENSION);
    $fname = 'license_' . $entre_id . '_' . time() . '.' . $ext;
    $dest  = $uploadDir . $fname;
    if (move_uploaded_file($_FILES['license_file']['tmp_name'], $dest)) {
        $license_file = 'uploads/places/' . $fname;
    }
}

// ── Handle place images ─────────────────────────
$place_image = '';
$all_images  = '';
$imageNames  = [];

if (!empty($_FILES['place_images']['name'][0])) {
    $files = $_FILES['place_images'];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext   = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $fname = 'place_' . $entre_id . '_' . time() . '_' . $i . '.' . $ext;
        $dest  = $uploadDir . $fname;
        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $imageNames[] = 'uploads/places/' . $fname;
        }
    }
    if (!empty($imageNames)) {
        $place_image = $imageNames[0];
        $all_images  = implode(',', $imageNames);
    }
}

// ── Handle category verification documents ──────
$category_docs_json = '';
if (!empty($_FILES['category_docs']['name'][0])) {
    $files     = $_FILES['category_docs'];
    $count     = count($files['name']);
    $docPaths  = [];
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext   = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','pdf'])) continue;
        $fname = 'catdoc_' . $entre_id . '_' . time() . '_' . $i . '.' . $ext;
        $dest  = $uploadDir . $fname;
        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $docPaths[] = 'uploads/places/' . $fname;
        }
    }
    if (!empty($docPaths)) {
        $category_docs_json = json_encode($docPaths);
    }
}

// ── Auto-add category_docs column if not exists ─
try {
    $pdo->exec("ALTER TABLE places ADD COLUMN category_docs TEXT DEFAULT NULL AFTER license_file");
} catch (PDOException $e) { /* column already exists */ }

// ── เปลี่ยน category จาก ENUM เป็น VARCHAR รองรับหลาย category ─
try {
    $pdo->exec("ALTER TABLE places MODIFY COLUMN category VARCHAR(200) DEFAULT NULL");
} catch (PDOException $e) { /* already varchar or no change needed */ }

// ── Insert into DB ──────────────────────────────
try {
    $stmt = $pdo->prepare("
        INSERT INTO places
          (place_name, category, description, phone, open_time, close_time,
           address, province, latitude, longitude,
           pet_allowed, pet_type_allowed, pet_size_allowed, extra_cost, pet_rules,
           amenities, pet_amenities, license_file, category_docs, place_image, all_images,
           entre_id, status, created_at, updated_at)
        VALUES
          (:name, :cat, :desc, :phone, :open, :close,
           :addr, :prov, :lat, :lng,
           :pet, :ptype, :psize, :extra, :prules,
           :amen, :pamen, :lic, :catdocs, :img, :allimg,
           :eid, 'pending', NOW(), NOW())
    ");
    $stmt->execute([
        ':name'    => $place_name,
        ':cat'     => $category,
        ':desc'    => $description,
        ':phone'   => $phone,
        ':open'    => $open_time,
        ':close'   => $close_time,
        ':addr'    => $address,
        ':prov'    => $province,
        ':lat'     => $latitude,
        ':lng'     => $longitude,
        ':pet'     => $pet_allowed,
        ':ptype'   => $pet_type,
        ':psize'   => $pet_size,
        ':extra'   => $extra_cost,
        ':prules'  => $pet_rules,
        ':amen'    => $amenities,
        ':pamen'   => $pet_amenities,
        ':lic'     => $license_file,
        ':catdocs' => $category_docs_json,
        ':img'     => $place_image,
        ':allimg'  => $all_images,
        ':eid'     => $entre_id,
    ]);

    $newId = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'place_id' => $newId]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'บันทึกข้อมูลไม่สำเร็จ: ' . $e->getMessage()]);
}