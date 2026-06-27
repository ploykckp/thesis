<?php
// Session config
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.gc_maxlifetime", 86400);
    ini_set("session.cookie_lifetime", 86400);
    ini_set("session.cookie_samesite", "Lax");
}
// อ่านค่าจาก Environment Variables (Railway/hosting)
// ถ้าไม่มี ENV ให้ใช้ค่า fallback สำหรับ local dev
$hostname = getenv('DB_HOST')     ?: 'db.ipgypgrozugtimoyyoua.supabase.co';
$username = getenv('DB_USER')     ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'fe-+s,#KRhSE5?d'; // ← ใส่รหัสจริงสำหรับ local
$database = getenv('DB_NAME')     ?: 'postgres';
$port     = getenv('DB_PORT')     ?: '5432';

try {
    $pdo = new PDO(
        "pgsql:host=$hostname;port=$port;dbname=$database;sslmode=require",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pdo = null;
    error_log("PDO failed: " . $e->getMessage());
}

$connect = null;
?>