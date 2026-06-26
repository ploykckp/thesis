<?php
// ================================================
//  admin_login.php — Pawlands Admin Login
// ================================================
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

require_once 'connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM account_user WHERE email_account = :e AND role_account = 'Admin' LIMIT 1");
            $stmt->execute([':e' => $email]);
            $admin = $stmt->fetch();

            $passwordWithSalt = $password . ($admin['salt_account'] ?? '');
            if ($admin && password_verify($passwordWithSalt, $admin['password_user'])) {
                $_SESSION['admin_id']   = $admin['user_id'];
                $_SESSION['admin_name'] = $admin['firstname_account'];
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง หรือบัญชีนี้ไม่มีสิทธิ์ Admin';
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Pawlands</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Kanit',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f0f6ff;color:#1a2a3a}
.card{background:#fff;border-radius:20px;padding:40px 44px;width:100%;max-width:420px;box-shadow:0 8px 40px rgba(18,52,81,.1)}
.logo{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:28px}
.logo-icon{width:44px;height:44px;background:#123451;border-radius:12px;display:flex;align-items:center;justify-content:center}
.logo-text{font-size:20px;font-weight:600;color:#123451}
.logo-sub{font-size:11px;color:#6b8099;letter-spacing:1px;text-transform:uppercase}
h2{font-size:18px;font-weight:500;color:#1a2a3a;margin-bottom:24px;text-align:center}
.field{margin-bottom:16px}
label{display:block;font-size:13px;font-weight:500;color:#1a2a3a;margin-bottom:6px}
input{width:100%;padding:11px 14px;border:1px solid rgba(18,52,81,.18);border-radius:10px;font-size:14px;font-family:'Kanit',sans-serif;color:#1a2a3a;background:#f8fafc;outline:none;transition:border-color .15s}
input:focus{border-color:#123451;background:#fff}
.btn{width:100%;padding:12px;background:#123451;color:#fff;border:none;border-radius:10px;font-size:15px;font-family:'Kanit',sans-serif;font-weight:500;cursor:pointer;margin-top:8px;transition:background .15s}
.btn:hover{background:#1a4a6e}
.error{background:#fdecea;color:#b02a2a;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;text-align:center}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">
      <svg width="22" height="22" viewBox="0 0 80 80" fill="none">
        <path d="M40 10C31 10 24 17 24 26C24 38 40 58 40 58S56 38 56 26C56 17 49 10 40 10Z" fill="white"/>
        <circle cx="40" cy="26" r="7" fill="#123451"/>
        <circle cx="28" cy="52" r="4" fill="white" opacity="0.7"/>
        <circle cx="52" cy="52" r="4" fill="white" opacity="0.7"/>
      </svg>
    </div>
    <div>
      <div class="logo-text">Pawlands</div>
      <div class="logo-sub">Admin Portal</div>
    </div>
  </div>

  <h2>เข้าสู่ระบบแอดมิน</h2>

  <?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="field">
      <label for="email">อีเมล</label>
      <input type="email" id="email" name="email" placeholder="admin@pawlands.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="password">รหัสผ่าน</label>
      <input type="password" id="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">เข้าสู่ระบบ</button>
  </form>
</div>
</body>
</html>