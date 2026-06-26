<?php
/**
 * Check Session
 * Returns current session status
 */

session_start();
header('Content-Type: application/json');

if (isset($_SESSION['entre_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'business') {
    // Check if session is still valid (30 minutes timeout)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expired
        session_unset();
        session_destroy();
        echo json_encode(['logged_in' => false, 'message' => 'Session expired']);
        exit;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['entre_id'],
            'email' => $_SESSION['entre_email'],
            'name' => $_SESSION['entre_firstname'] . ' ' . $_SESSION['entre_lastname'],
            'business_name' => $_SESSION['business_name'],
            'business_type' => $_SESSION['business_type']
        ]
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>
