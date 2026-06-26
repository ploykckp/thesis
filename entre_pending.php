<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['entre_id'])) {
    header('Location: business-register.php');
    exit;
}

$entre_id = (int)$_SESSION['entre_id'];
$status   = 'pending';
$reason   = '';
$name     = $_SESSION['entre_name'] ?? '';

if ($pdo) {
    $stmt = $pdo->prepare("SELECT approval_status, rejection_reason, entre_firstname, entre_lastname FROM account_entre WHERE entre_id = :id");
    $stmt->execute([':id' => $entre_id]);
    $row = $stmt->fetch();
    if ($row) {
        $status = $row['approval_status'] ?? 'pending';
        $reason = $row['rejection_reason'] ?? '';
        $name   = $row['entre_firstname'] . ' ' . $row['entre_lastname'];
        $_SESSION['entre_status'] = $status;
    }
}

if ($status === 'approved') {
    header('Location: entre_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะการสมัคร - Pawlands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Kanit', sans-serif; background: #f0f4f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 20px; padding: 48px 40px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .icon { font-size: 64px; margin-bottom: 16px; }
        .title { font-size: 24px; font-weight: 600; margin-bottom: 8px; }
        .subtitle { color: #666; font-size: 15px; margin-bottom: 24px; line-height: 1.6; }
        .badge { display: inline-block; padding: 6px 20px; border-radius: 99px; font-size: 14px; font-weight: 500; margin-bottom: 28px; }
        .badge-pending  { background: #fff3cd; color: #856404; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .reason-box { background: #fff8e1; border: 1px solid #ffc107; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; text-align: left; font-size: 14px; color: #5a4a00; }
        .reason-box strong { display: block; margin-bottom: 6px; }
        .steps { background: #f8f9fa; border-radius: 12px; padding: 20px; text-align: left; margin-bottom: 28px; }
        .steps h4 { font-size: 14px; color: #555; margin-bottom: 12px; }
        .step { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; font-size: 14px; color: #444; }
        .step-num { width: 22px; height: 22px; border-radius: 50%; background: #2c3e6b; color: white; font-size: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }
        .btn { display: block; width: 100%; padding: 13px 32px; border-radius: 12px; font-family: 'Kanit', sans-serif; font-size: 15px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: all 0.2s; margin-bottom: 10px; }
        .btn-primary { background: #2c3e6b; color: white; }
        .btn-primary:hover { background: #1a2a4a; }
        .btn-secondary { background: transparent; color: #666; border: 1px solid #ddd; text-align: center; }
    </style>
</head>
<body>
<div class="card">
<?php if ($status === 'pending'): ?>
    <div class="icon">⏳</div>
    <h1 class="title">รอการอนุมัติ</h1>
    <p class="subtitle">สวัสดีคุณ <?= htmlspecialchars($name) ?><br>คำขอลงทะเบียนของคุณอยู่ระหว่างการตรวจสอบ</p>
    <span class="badge badge-pending">🔄 รอดำเนินการ</span>
    <div class="steps">
        <h4>ขั้นตอนการอนุมัติ</h4>
        <div class="step"><div class="step-num">1</div><span>Admin ตรวจสอบข้อมูลธุรกิจของคุณ</span></div>
        <div class="step"><div class="step-num">2</div><span>Admin อนุมัติหรือแจ้งเหตุผลที่ไม่อนุมัติ</span></div>
        <div class="step"><div class="step-num">3</div><span>คุณสามารถเข้าใช้ Dashboard ได้ทันที</span></div>
    </div>
    <button class="btn btn-primary" onclick="checkStatus()">🔄 ตรวจสอบสถานะ</button>
    <a href="form-login.php" class="btn btn-secondary">กลับหน้าหลัก</a>

<?php elseif ($status === 'rejected'): ?>
    <div class="icon">❌</div>
    <h1 class="title">ไม่ได้รับการอนุมัติ</h1>
    <p class="subtitle">ขออภัย คำขอของคุณไม่ผ่านการอนุมัติ</p>
    <span class="badge badge-rejected">❌ ไม่อนุมัติ</span>
    <?php if ($reason): ?>
    <div class="reason-box"><strong>📋 เหตุผล:</strong><?= htmlspecialchars($reason) ?></div>
    <?php endif; ?>
    <button class="btn btn-primary" onclick="window.location.href='business-register.php'">📝 สมัครใหม่</button>
    <a href="form-login.php" class="btn btn-secondary">กลับหน้าหลัก</a>
<?php endif; ?>
</div>

<script>
async function checkStatus() {
    const btn = document.querySelector('.btn-primary');
    btn.textContent = 'กำลังตรวจสอบ...';
    btn.disabled = true;
    try {
        const res = await fetch('check_entre_status.php');
        const data = await res.json();
        if (data.status === 'approved') {
            window.location.href = 'entre_dashboard.php';
        } else if (data.status === 'rejected') {
            window.location.reload();
        } else {
            btn.textContent = '⏳ ยังรออยู่ — ลองใหม่ภายหลัง';
            setTimeout(() => { btn.textContent = '🔄 ตรวจสอบสถานะ'; btn.disabled = false; }, 3000);
        }
    } catch(e) {
        btn.textContent = '🔄 ตรวจสอบสถานะ';
        btn.disabled = false;
    }
}
</script>
</body>
</html>