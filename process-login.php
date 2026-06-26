<?php
session_start();
require('connect.php');

if (!isset($_POST['email_account']) || !isset($_POST['password_account'])) {
    header('Location: form-login.php?error=missing_fields'); exit;
}

$email_input    = trim($_POST['email_account']);
$password_input = $_POST['password_account'];

// 1. ตรวจสอบ account_user
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM account_user WHERE email_account = :email LIMIT 1");
    $stmt->execute([':email' => $email_input]);
    $user = $stmt->fetch();
    if ($user) {
        if (password_verify($password_input . ($user['salt_account'] ?? ''), $user['password_user'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['email']     = $user['email_account'];
            $_SESSION['firstname'] = $user['firstname_account'];
            $_SESSION['lastname']  = $user['lastname_account'];
            $_SESSION['role']      = $user['role_account'];
            $role = $user['role_account'] ?? '';
            if ($role === 'Admin') { header('Location: admin_dashboard.php'); exit; }
            else { header('Location: home.php'); exit; }
        } else {
            header('Location: form-login.php?error=wrong_password'); exit;
        }
    }
}

// 2. ตรวจสอบ account_entre
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM account_entre WHERE entre_email = :email LIMIT 1");
    $stmt->execute([':email' => $email_input]);
    $entre = $stmt->fetch();
    if ($entre) {
        if (password_verify($password_input, $entre['entre_password'])) {
            $_SESSION['entre_id']    = $entre['entre_id'];
            $_SESSION['entre_email'] = $entre['entre_email'];
            $_SESSION['entre_name']  = $entre['entre_firstname'] . ' ' . $entre['entre_lastname'];
            $status = $entre['approval_status'] ?? 'pending';
            if ($status === 'approved') { header('Location: entre_dashboard.php'); exit; }
            else { header('Location: entre_pending.php'); exit; }
        } else {
            header('Location: form-login.php?error=wrong_password'); exit;
        }
    }
}

header('Location: form-login.php?error=email_not_found'); exit;
?>