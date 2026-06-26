<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$firstName        = trim($_POST['firstName']        ?? '');
$lastName         = trim($_POST['lastName']         ?? '');
$email            = trim($_POST['email']            ?? '');
$password         = $_POST['password']              ?? '';
$businessName     = trim($_POST['businessName']     ?? '');
$businessType     = trim($_POST['businessType']     ?? '');
$animalType       = trim($_POST['animalType']       ?? '');
$businessDetails  = trim($_POST['businessDetails']  ?? '');
$address          = trim($_POST['address']          ?? '');
$province         = trim($_POST['province']         ?? '');
$petAllowed       = $_POST['petAllowed']            ?? 'no';
$petSizeAllowed   = $_POST['petSizeAllowed']        ?? '';
$petWeightAllowed = $_POST['petWeightAllowed']      ?? null;
if ($petWeightAllowed === '') $petWeightAllowed = null;

if (!$firstName || !$lastName || !$email || !$password || !$businessName || !$businessType || !$address || !$province) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'รูปแบบอีเมลไม่ถูกต้อง']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร']);
    exit;
}
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล']);
    exit;
}

try {
    // Auto-add columns ถ้ายังไม่มี
    foreach ([
        "ALTER TABLE account_entre ADD COLUMN animal_type VARCHAR(255) DEFAULT '' AFTER business_type",
        "ALTER TABLE account_entre ADD COLUMN reg_docs TEXT DEFAULT NULL COMMENT 'JSON array of registration document paths'",
    ] as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) {}
    }

    // Upload directory
    $uploadDir = __DIR__ . '/uploads/entre_docs/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // รับไฟล์เอกสาร docs[]
    $docPaths = [];
    if (!empty($_FILES['docs']['name'][0])) {
        $files = $_FILES['docs'];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','pdf'])) continue;
            $fname = 'doc_' . time() . '_' . $i . '.' . $ext;
            $dest  = $uploadDir . $fname;
            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                $docPaths[] = 'uploads/entre_docs/' . $fname;
            }
        }
    }
    $regDocsJson = !empty($docPaths) ? json_encode($docPaths) : null;

    // เช็ค email ซ้ำ
    $check = $pdo->prepare("SELECT entre_id FROM account_entre WHERE entre_email = :email LIMIT 1");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'อีเมลนี้ถูกใช้งานแล้ว']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO account_entre
            (entre_firstname, entre_lastname, entre_email, entre_password,
             business_name, business_type, animal_type, business_details,
             business_address, bussiness_province,
             pet_allowed, pet_size_allowed, pet_weight_allowed,
             reg_docs, approval_status, created_at)
            VALUES
            (:fname, :lname, :email, :password,
             :bname, :btype, :animaltype, :bdetails,
             :address, :province,
             :petallowed, :petsize, :petweight,
             :regdocs, 'pending', NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fname'      => $firstName,
        ':lname'      => $lastName,
        ':email'      => $email,
        ':password'   => $hashedPassword,
        ':bname'      => $businessName,
        ':btype'      => $businessType,
        ':animaltype' => $animalType,
        ':bdetails'   => $businessDetails,
        ':address'    => $address,
        ':province'   => $province,
        ':petallowed' => $petAllowed,
        ':petsize'    => $petSizeAllowed,
        ':petweight'  => $petWeightAllowed,
        ':regdocs'    => $regDocsJson,
    ]);

    $newId = $pdo->lastInsertId();
    $_SESSION['entre_id']     = $newId;
    $_SESSION['entre_email']  = $email;
    $_SESSION['entre_name']   = $firstName . ' ' . $lastName;
    $_SESSION['entre_status'] = 'pending';

    echo json_encode(['success' => true, 'message' => 'ลงทะเบียนสำเร็จ! กรุณารอการอนุมัติจาก Admin']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}