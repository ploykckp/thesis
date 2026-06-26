<?php
/**
 * Business Login Handler
 * Authenticates business users
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate inputs
if (empty($input['email']) || empty($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกอีเมลและรหัสผ่าน']);
    exit;
}

$email = $conn->real_escape_string(trim($input['email']));
$password = trim($input['password']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'รูปแบบอีเมลไม่ถูกต้อง']);
    exit;
}

// Query database
$stmt = $conn->prepare("
    SELECT 
        entre_id, 
        entre_firstname, 
        entre_lastname, 
        entre_email, 
        entre_password, 
        business_name,
        business_type
    FROM account_entre 
    WHERE entre_email = ?
");

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้นี้']);
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['entre_password'])) {
    echo json_encode(['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง']);
    exit;
}

// Create session
$_SESSION['entre_id'] = $user['entre_id'];
$_SESSION['entre_email'] = $user['entre_email'];
$_SESSION['entre_firstname'] = $user['entre_firstname'];
$_SESSION['entre_lastname'] = $user['entre_lastname'];
$_SESSION['business_name'] = $user['business_name'];
$_SESSION['business_type'] = $user['business_type'];
$_SESSION['user_type'] = 'business';
$_SESSION['last_activity'] = time();

// Update last login time (optional - add a last_login column if needed)
// $update = $conn->prepare("UPDATE account_entre SET updated_at = NOW() WHERE entre_id = ?");
// $update->bind_param("i", $user['entre_id']);
// $update->execute();

echo json_encode([
    'success' => true,
    'message' => 'เข้าสู่ระบบสำเร็จ',
    'user' => [
        'id' => $user['entre_id'],
        'name' => $user['entre_firstname'] . ' ' . $user['entre_lastname'],
        'email' => $user['entre_email'],
        'business_name' => $user['business_name'],
        'business_type' => $user['business_type']
    ]
]);

$stmt->close();
$conn->close();
?>
