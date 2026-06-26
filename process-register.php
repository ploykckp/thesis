<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('connect.php');

if (isset($_POST['firstname_account']) && isset($_POST['lastname_account']) && isset($_POST['email_account']) && isset($_POST['password_account1']) && isset($_POST['password_account2'])) {

    $firstname_account = trim($_POST['firstname_account']);
    $lastname_account  = trim($_POST['lastname_account']);
    $email_account     = trim($_POST['email_account']);
    $password_account1 = $_POST['password_account1'];
    $password_account2 = $_POST['password_account2'];

    if (empty($firstname_account)) {
        die(header('Location: form-register.php'));
    } elseif (empty($lastname_account)) {
        die(header('Location: form-register.php'));
    } elseif (empty($email_account)) {
        die(header('Location: form-register.php'));
    } elseif (empty($password_account1)) {
        die(header('Location: form-register.php'));
    } elseif (empty($password_account2)) {
        die(header('Location: form-register.php'));
    } elseif ($password_account1 !== $password_account2) {
        die(header('Location: form-register.php'));
    } else {
        // เช็ค email ซ้ำ
        $stmt = $pdo->prepare("SELECT email_account FROM account_user WHERE email_account = :email");
        $stmt->execute([':email' => $email_account]);
        if ($stmt->rowCount() > 0) {
            die(header('Location: form-register.php'));
        }

        // สร้าง salt และ hash password
        $length       = random_int(97, 128);
        $salt_account = bin2hex(random_bytes($length));
        $password_to_hash = $password_account1 . $salt_account;
        $password_user = password_hash($password_to_hash, PASSWORD_DEFAULT);

        // INSERT
        $ins = $pdo->prepare("INSERT INTO account_user 
            (firstname_account, lastname_account, email_account, password_user, role_account, profile_images, salt_account) 
            VALUES (:firstname, :lastname, :email, :password, 'Member', 'default_images_account.jpg', :salt)");
        
        if ($ins->execute([
            ':firstname' => $firstname_account,
            ':lastname'  => $lastname_account,
            ':email'     => $email_account,
            ':password'  => $password_user,
            ':salt'      => $salt_account
        ])) {
            die(header('Location: form-login.php'));
        } else {
            die("Insert Error");
        }
    }
} else {
    die(header('Location: form-register.php'));
}
?>