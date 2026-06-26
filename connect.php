<?php
$hostname = 'db.ipgypgrozugtimoyyoua.supabase.co';
$username = 'postgres';
$password = 'fe-+s,#KRhSE5?d';
$database = 'postgres';

try {
    $pdo = new PDO(
        "pgsql:host=$hostname;port=5432;dbname=$database;sslmode=require",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pdo = null;
    error_log("PDO failed: " . $e->getMessage());
}
?>