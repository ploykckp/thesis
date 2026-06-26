<?php
// ================================================
//  connect.php — Database Connection (Fixed)
//  Usage: require_once 'connect.php';  → $pdo ready
// ================================================

$hostname = 'sql302.infinityfree.com';
$username = 'if0_42221064';
$password = 'OcW4q1oezXn7DJ';
$database = 'if0_42221064_pawland';

try {
    $pdo = new PDO(
        "mysql:host=$hostname;dbname=$database;charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pdo = null;
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
