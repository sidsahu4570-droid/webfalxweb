<?php
/**
 * WebFalx Admin Logout
 * Destroys session variables and logs the administrator out securely
 */

require_once __DIR__ . '/../includes/config.php';

// Unset all session variables
$_SESSION = [];

// If session cookie exists, destroy it completely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy session context
session_destroy();

// Redirect back to login portal
header('Location: ' . BASE_URL . 'admin/login.php');
exit;
