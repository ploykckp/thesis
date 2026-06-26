<?php
/**
 * Database Configuration File
 * Update these settings to match your XAMPP MySQL configuration
 */

// Database credentials
define('DB_HOST', 'pawland.infinityfree.com');
define('DB_USER', 'if0_42221064');           // Default XAMPP username
define('DB_PASS', 'OcW4q1oezXn7DJ');               // Default XAMPP password (empty)
define('DB_NAME', 'if0_42221064_pawland');        // Your database name

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Timezone setting
date_default_timezone_set('Asia/Bangkok');

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
