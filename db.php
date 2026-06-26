<?php
// ================================================
//  db.php — Database Connection
//  Usage: require_once 'db.php';  → $pdo is ready
// ================================================

$host     = 'localhost';
$dbname   = 'pawland';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pdo = null;
    error_log("DB Connection failed: " . $e->getMessage());
}