<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียน - Registration</title>
    <link rel="stylesheet" href="register.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="registration-card">
            <!-- Logo Section -->
            <div class="logo-container">
                <img src="logo.png" alt="Logo" class="logo" width="476" height="476">
            </div>

            <!-- Title -->
            <h1 class="title">สมัครสมาชิก</h1>

            <!-- Registration Form -->
            <form action="process-register.php" method="POST" class="registration-form" id="registrationForm">
                <!-- Name Field -->
                <div class="form-group">
                    <input name="firstname_account" type="text" id="firstName" class="form-input" placeholder="ชื่อ" required>
                </div>

                <!-- Surname Field -->
                <div class="form-group">
                    <input name="lastname_account" type="text" id="lastName" class="form-input" placeholder="นามสกุล" required>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <input name="email_account" type="email" id="email" class="form-input" placeholder="อีเมล" required>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label class="input-label">ตั้งรหัสผ่าน</label>
                    <div class="password-wrapper1">
                        <input name="password_account1" type="password" id="password" class="form-input" placeholder="รหัสผ่าน" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group">
                    <label class="input-label">ยืนยันรหัสผ่าน</label>
                    <div class="password-wrapper2">
                        <input name="password_account2" type="password" id="confirmPassword" class="form-input" placeholder="รหัสผ่าน" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">ลงทะเบียน</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelRegistration()">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>