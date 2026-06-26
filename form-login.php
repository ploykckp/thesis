<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พอวีแลนด์ - เข้าสู่ระบบ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <!-- Logo -->
            <div class="logo-container">
                <img src="logo.png" alt="Logo" class="logo" width="476" height="476">
            </div>
            
            <!-- Welcome Text -->
            <h1 class="welcome-text">พอว์แลนด์...ยินดีต้อนรับ</h1>
            
            <!-- Login Form -->
            <form class="login-form" action="process-login.php" method="POST">
                <!-- Email Input -->
                <div class="input-group">
                    <input name="email_account" id="email" class="input-field" placeholder="อีเมล" required>
                </div>
                
                <!-- Password Input -->
                <div class="input-group">
                    <input name="password_account" id="password" class="input-field" placeholder="รหัสผ่าน" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                
                
                <!-- Login Button -->
                <button type="submit" class="login-button">เข้าสู่ระบบ</button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span class="divider-text">หรือ</span>
            </div>
            
            <!-- Register Link -->
            <div class="register-section">
                <p class="register-text">
                    ไม่มีสมาชิกใช่ไหม? <a href="form-register.php" class="register-link">ลงทะเบียน</a>
                </p>
            </div>
            
            <!-- Divider -->
            <div class="divider">
                <span class="divider-text">หรือ</span>
            </div>
            
            <!-- Business Register -->
            <div class="business-register">
                <a href="business-register.php" class="business-link">ลงทะเบียนผู้ประกอบการ</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }
    </script>
</body>
</html>