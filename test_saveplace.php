<?php
// test_saveplace.php - ทดสอบ saveplace โดยตรง ลบทิ้งหลังใช้
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:sans-serif;padding:20px}.ok{color:green}.err{color:red}pre{background:#f5f5f5;padding:10px;border-radius:5px}</style>
</head><body>
<h2>🔍 ทดสอบ saveplace</h2>

<?php
// เช็ค session
echo "<h3>1. Session</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['entre_id'])) {
    echo "<p class='err'>✗ ไม่มี entre_id ใน session — ยังไม่ได้ login เป็น entrepreneur</p>";
    echo "<p>→ กรุณา login ด้วย account entrepreneur ก่อน แล้วค่อยเปิดหน้านี้</p>";
} else {
    echo "<p class='ok'>✓ entre_id = " . $_SESSION['entre_id'] . "</p>";
}

// เช็ค DB และ places columns
echo "<h3>2. Places table columns</h3>";
try {
    $pdo = new PDO("mysql:host=pawland.infinityfree.com;dbname=if0_42221064_pawland;charset=utf8","if0_42221064","OcW4q1oezXn7DJ");
    $cols = $pdo->query("SHOW COLUMNS FROM places")->fetchAll(PDO::FETCH_COLUMN);
    $needed = ['entre_id','status','phone','open_time','close_time','amenities','pet_amenities','pet_rules','extra_cost','license_file','all_images','pet_allowed','created_at'];
    foreach ($needed as $c) {
        $has = in_array($c, $cols);
        echo "<p class='".($has?'ok':'err')."'>".($has?'✓':'✗')." $c</p>";
    }
    
    // ทดสอบ INSERT ตรงๆ
    echo "<h3>3. ทดสอบ INSERT โดยตรง</h3>";
    if (isset($_SESSION['entre_id'])) {
        try {
            $sql = "INSERT INTO places 
                    (place_name, category, description, address, province, latitude, longitude,
                     pet_type_allowed, pet_size_allowed, place_image, entre_id, status,
                     open_time, close_time, phone, amenities, pet_amenities, pet_rules,
                     extra_cost, license_file, all_images, pet_allowed, created_at)
                    VALUES 
                    ('TEST PLACE', 'คาเฟ่', 'test desc', '123 test road', 'กรุงเทพมหานคร', 13.7, 100.5,
                     'สุนัข', 'small', '', :entre_id, 'pending',
                     '09:00', '18:00', '0812345678', '', '', '',
                     '', '', '', 'yes', NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':entre_id' => $_SESSION['entre_id']]);
            $newId = $pdo->lastInsertId();
            echo "<p class='ok'>✓ INSERT สำเร็จ! place_id = $newId</p>";
            
            // ลบ test record ออก
            $pdo->exec("DELETE FROM places WHERE place_id = $newId");
            echo "<p class='ok'>✓ ลบ test record แล้ว</p>";
            echo "<p style='color:blue'><strong>→ DB ทำงานปกติ ปัญหาอยู่ที่ JS ส่งข้อมูลไม่ครบ</strong></p>";
        } catch (PDOException $e) {
            echo "<p class='err'>✗ INSERT ล้มเหลว: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // แสดง places ที่มีอยู่ทั้งหมด
    echo "<h3>4. Places ใน DB ตอนนี้</h3>";
    $places = $pdo->query("SELECT place_id, place_name, entre_id, status, created_at FROM places ORDER BY place_id DESC LIMIT 10")->fetchAll();
    echo "<pre>";
    foreach($places as $p) {
        echo "ID:{$p['place_id']} | {$p['place_name']} | entre:{$p['entre_id']} | status:{$p['status']} | {$p['created_at']}\n";
    }
    echo "</pre>";

} catch (Exception $e) {
    echo "<p class='err'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<h3>5. ทดสอบ saveplace.php ด้วย form จริง</h3>
<form method="POST" action="saveplace.php" enctype="multipart/form-data" target="result_frame">
    <input type="hidden" name="place_name" value="TEST จาก form">
    <input type="hidden" name="category" value="คาเฟ่">
    <input type="hidden" name="description" value="test">
    <input type="hidden" name="address" value="123 test">
    <input type="hidden" name="province" value="กรุงเทพมหานคร">
    <input type="hidden" name="latitude" value="13.7">
    <input type="hidden" name="longitude" value="100.5">
    <input type="hidden" name="pet_allowed" value="yes">
    <input type="hidden" name="pet_type" value="สุนัข">
    <input type="hidden" name="pet_size" value="small">
    <button type="submit" style="padding:10px 20px;background:#123451;color:white;border:none;border-radius:8px;cursor:pointer">
        ส่ง POST ไปยัง saveplace.php
    </button>
</form>
<iframe name="result_frame" style="width:100%;height:80px;margin-top:10px;border:1px solid #ddd;border-radius:5px"></iframe>
<p style="color:orange">หลังกดปุ่ม ดูผลใน iframe ด้านบน</p>

</body></html>