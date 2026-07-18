<?php
session_start();

require_once 'connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: form-login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch latest user data from DB
$stmt = $pdo->prepare("SELECT * FROM account_user WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: form-login.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $firstname = trim(htmlspecialchars($_POST['firstname'] ?? ''));
    $lastname  = trim(htmlspecialchars($_POST['lastname']  ?? ''));

    $profile_img = $user['profile_images']; // keep existing

    // Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime    = mime_content_type($_FILES['profile_image']['tmp_name']);
        if (in_array($mime, $allowed)) {
            $ext      = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $dest     = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                $profile_img = $dest;
            } else {
                $error_msg = 'ไม่สามารถอัปโหลดรูปได้ กรุณาลองใหม่';
            }
        } else {
            $error_msg = 'รูปภาพต้องเป็น JPG, PNG, GIF หรือ WebP เท่านั้น';
        }
    }

    if (empty($error_msg)) {
        $upd = $pdo->prepare("UPDATE account_user SET firstname_account=:fn, lastname_account=:ln, profile_images=:img WHERE user_id=:uid");
        if ($upd->execute([':fn'=>$firstname, ':ln'=>$lastname, ':img'=>$profile_img, ':uid'=>$user_id])) {
            // Update session
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname']  = $lastname;
            $user['firstname_account'] = $firstname;
            $user['lastname_account']  = $lastname;
            $user['profile_images']    = $profile_img;
            $success_msg = 'อัปเดตข้อมูลสำเร็จ!';
        } else {
            $error_msg = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
        }
    }
}

// Profile image src
$profile_src = (!empty($user['profile_images']) && $user['profile_images'] !== 'default_images_account.jpg')
    ? htmlspecialchars($user['profile_images'])
    : null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawlands — โปรไฟล์ของฉัน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="home.css">
    <style>
        /* ── Page wrapper ── */
        .profile-page {
            min-height: calc(100vh - 108px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 60px 20px 80px;
            background: linear-gradient(160deg, #e8eef4 0%, #f5f9fd 40%, #ffffff 100%);
        }

        /* ── Card ── */
        .profile-card {
            width: 100%;
            max-width: 560px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 8px 40px rgba(100, 181, 246, 0.18), 0 2px 8px rgba(0,0,0,0.06);
            padding: 48px 44px 40px;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #123451, #0d2a42, #1a4a73);
            border-radius: 24px 24px 0 0;
        }

        /* ── Avatar area ── */
        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 36px;
        }

        .avatar-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 16px;
        }

        .avatar-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #123451;
            box-shadow: 0 4px 20px rgba(100,181,246,0.3);
            background: #e8eef4;
        }

        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e8eef4, #1a4068);
            border: 4px solid #123451;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(100,181,246,0.3);
        }

        .avatar-placeholder .iconify {
            color: #123451;
        }

        .avatar-upload-btn {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 34px;
            height: 34px;
            background: #123451;
            border: 3px solid #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 2px 8px rgba(100,181,246,0.4);
        }

        .avatar-upload-btn:hover { background: #0d2a42; }
        .avatar-upload-btn .iconify { color: #fff; }
        #avatarInput { display: none; }

        .avatar-name {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            letter-spacing: 0.3px;
        }

        .avatar-role {
            font-size: 13px;
            font-weight: 400;
            color: #94a3b8;
            margin-top: 2px;
            text-transform: capitalize;
        }

        /* ── Section title ── */
        .section-label {
            font-size: 13px;
            font-weight: 600;
            color: #123451;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        /* ── Form fields ── */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Kanit', sans-serif;
            font-size: 15px;
            color: #1e293b;
            background: #f8fafc;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-input:focus {
            border-color: #123451;
            box-shadow: 0 0 0 3px rgba(100,181,246,0.15);
            background: #fff;
        }

        .form-input:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .email-note {
            font-size: 11px;
            color: #b0bec5;
            margin-top: 4px;
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 28px 0;
        }

        /* ── Buttons ── */
        .btn-save {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #123451, #0d2a42);
            color: #fff;
            font-family: 'Kanit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(100,181,246,0.4);
            letter-spacing: 0.3px;
            margin-bottom: 14px;
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(100,181,246,0.5);
        }

        .btn-save:active { transform: translateY(0); }

        .btn-logout {
            width: 100%;
            padding: 13px;
            background: transparent;
            color: #ef5350;
            font-family: 'Kanit', sans-serif;
            font-size: 15px;
            font-weight: 500;
            border: 1.5px solid #ef5350;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s, color 0.2s;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #ef5350;
            color: #fff;
        }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        .alert-error {
            background: #fce4ec;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* ── Preview ── */
        #avatarPreview { display: none; }
    </style>
</head>
<body>

<!-- ══════════ HEADER ══════════ -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="home.php"><img src="logo.png" alt="Logo" width="136" height="136"></a>
            </div>
            <nav class="nav">
                <a href="home.php" class="nav-link">หน้าแรก</a>
                <a href="plantrip.php" class="nav-link">แผนเที่ยว</a>
                <a href="petinfo.php" class="nav-link">ข้อมูลสัตว์เลี้ยง</a>
                <a href="nearme.php" class="nav-link">ใกล้ฉัน</a>
            </nav>
            <div class="header-right">
                <div class="language-switch">
                    <span class="lang-active">TH</span>
                </div>
                <?php include 'header_user_icon.php'; ?>
            </div>
        </div>
    </div>
</header>

<!-- ══════════ PROFILE PAGE ══════════ -->
<div class="profile-page">
    <div class="profile-card">

        <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <span class="iconify" data-icon="mdi:check-circle" data-width="18"></span>
            <?= $success_msg ?>
        </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
        <div class="alert alert-error">
            <span class="iconify" data-icon="mdi:alert-circle" data-width="18"></span>
            <?= $error_msg ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <input type="hidden" name="action" value="update_profile">
            <input type="file" id="avatarInput" name="profile_image" accept="image/*">

            <!-- Avatar -->
            <div class="avatar-section">
                <div class="avatar-wrapper">
                    <?php if ($profile_src): ?>
                        <img id="avatarPreview" class="avatar-img" src="" alt="preview" style="display:none;">
                        <img id="avatarCurrent" class="avatar-img" src="<?= $profile_src ?>" alt="Profile">
                    <?php else: ?>
                        <img id="avatarPreview" class="avatar-img" src="" alt="preview" style="display:none;">
                        <div id="avatarCurrent" class="avatar-placeholder">
                            <span class="iconify" data-icon="mdi:account" data-width="60" data-height="60"></span>
                        </div>
                    <?php endif; ?>
                    <label for="avatarInput" class="avatar-upload-btn" title="เปลี่ยนรูปโปรไฟล์">
                        <span class="iconify" data-icon="mdi:camera" data-width="16"></span>
                    </label>
                </div>
                <div class="avatar-name"><?= htmlspecialchars($user['firstname_account'] . ' ' . $user['lastname_account']) ?></div>
                <div class="avatar-role"><?= htmlspecialchars($user['role_account'] ?? 'member') ?></div>
            </div>

            <!-- Fields -->
            <div class="section-label">ข้อมูลส่วนตัว</div>

            <div class="form-group">
                <label class="form-label" for="firstname">ชื่อ</label>
                <input class="form-input" type="text" id="firstname" name="firstname"
                    value="<?= htmlspecialchars($user['firstname_account']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="lastname">นามสกุล</label>
                <input class="form-input" type="text" id="lastname" name="lastname"
                    value="<?= htmlspecialchars($user['lastname_account']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">อีเมล</label>
                <input class="form-input" type="email" id="email"
                    value="<?= htmlspecialchars($user['email_account']) ?>" disabled>
                <div class="email-note">* อีเมลไม่สามารถแก้ไขได้</div>
            </div>

            <hr class="divider">

            <button type="submit" class="btn-save">
                <span class="iconify" data-icon="mdi:content-save" data-width="18" style="vertical-align:middle; margin-right:6px;"></span>
                บันทึกข้อมูล
            </button>
        </form>

        <!-- Logout -->
        <a href="logout.php" class="btn-logout">
            <span class="iconify" data-icon="mdi:logout" data-width="18"></span>
            ออกจากระบบ
        </a>

    </div>
</div>

<script>
    // Avatar live preview
    document.getElementById('avatarInput').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById('avatarPreview');
            const current = document.getElementById('avatarCurrent');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (current) current.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
</script>
</body>
</html>