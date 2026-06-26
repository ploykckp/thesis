<?php
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'pawland';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pdo = null;
    error_log("PDO failed: " . $e->getMessage());
}

$connect = mysqli_connect($hostname, $username, $password, $database);
if ($connect) mysqli_set_charset($connect, 'utf8');
?>