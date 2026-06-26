<?php
/**
 * Business Registration Handler
 * Processes multi-step business registration form
 */

session_start();
require_once 'connect.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
$required_fields = [
    'firstName', 'lastName', 'email', 'password', 
    'businessName', 'businessType', 'address', 'province'
];

foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "กรุณากรอก $field"]);
        exit;
    }
}

// Sanitize inputs
$firstName = $conn->real_escape_string(trim($input['firstName']));
$lastName = $conn->real_escape_string(trim($input['lastName']));
$email = $conn->real_escape_string(trim($input['email']));
$password = trim($input['password']);
$businessName = $conn->real_escape_string(trim($input['businessName']));
$businessType = $conn->real_escape_string(trim($input['businessType']));
$businessDetails = $conn->real_escape_string(trim($input['businessDetails'] ?? ''));
$address = $conn->real_escape_string(trim($input['address']));
$province = $conn->real_escape_string(trim($input['province']));
$petAllowed = $input['petAllowed'] ?? 'yes';
$petSizeAllowed = $conn->real_escape_string($input['petSizeAllowed'] ?? '');
$petWeightAllowed = isset($input['petWeightAllowed']) ? (int)$input['petWeightAllowed'] : null;

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'รูปแบบอีเมลไม่ถูกต้อง']);
    exit;
}

// Check if email already exists
$check_email = $conn->prepare("SELECT entre_id FROM account_entre WHERE entre_email = ?");
$check_email->bind_param("s", $email);
$check_email->execute();
$result = $check_email->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'อีเมลนี้ถูกใช้งานแล้ว']);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Handle image upload
$businessImage = '';
if (isset($input['businessImages']) && !empty($input['businessImages'])) {
    // Join multiple image paths with comma
    $businessImage = implode(',', array_map(function($img) use ($conn) {
        return $conn->real_escape_string($img);
    }, $input['businessImages']));
}

// Insert into database
$stmt = $conn->prepare("
    INSERT INTO account_entre (
        entre_firstname, 
        entre_lastname, 
        entre_email, 
        entre_password, 
        business_name, 
        business_type, 
        business_details, 
        business_image,
        business_address, 
        bussiness_province,
        pet_allowed, 
        pet_size_allowed, 
        pet_weight_allowed,
        created_at,
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");

$stmt->bind_param(
    "ssssssssssssi",
    $firstName,
    $lastName,
    $email,
    $hashedPassword,
    $businessName,
    $businessType,
    $businessDetails,
    $businessImage,
    $address,
    $province,
    $petAllowed,
    $petSizeAllowed,
    $petWeightAllowed
);

if ($stmt->execute()) {
    $entre_id = $conn->insert_id;
    
    // Create session for the business user
    $_SESSION['entre_id'] = $entre_id;
    $_SESSION['entre_email'] = $email;
    $_SESSION['business_name'] = $businessName;
    $_SESSION['user_type'] = 'business';
    
    echo json_encode([
        'success' => true, 
        'message' => 'ลงทะเบียนสำเร็จ!',
        'entre_id' => $entre_id
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาด: ' . $conn->error
    ]);
}


$stmt->close();
$conn->close();
?>
