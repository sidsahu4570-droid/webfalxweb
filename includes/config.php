<?php
/**
 * WebFalx Configuration File
 * Core Settings, Sessions, and DB Connection Setup
 */

// 1. Error Reporting Configuration (Secure)
define('DEV_MODE', true); // Toggle to false in production

if (DEV_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Logging setup
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// 2. Hardened Session Management
if (session_status() === PHP_SESSION_NONE) {
    // Robust HTTPS detection (handles Cloudflare / reverse proxies)
    $session_is_https = (
        (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
    );

    // Session Cookie Settings
    $cookieParams = [
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => '', 
        'secure' => $session_is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

// Prevent Session Hijacking (Regenerate ID periodically)
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // Every 30 mins
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// 3. Database Credentials (Fallback-supported configurations)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'webfalxdata');
define('DB_USER', getenv('DB_USER') ?: 'webfalxuser');
define('DB_PASS', getenv('DB_PASS') ?: '');  // Set DB_PASS env var on your live server, or put password here

// 4. Global Constants
define('APP_NAME', 'WebFalx');
define('APP_PHONE', '6266273414');

// Robust protocol detection to handle SSL-terminating reverse proxies (like Cloudflare, AWS, Nginx)
$is_https = (
    (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
);
$protocol = $is_https ? 'https' : 'http';
define('BASE_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/');

// 5. Establish PDO Database Connection
$db = null;
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if (DEV_MODE) {
        $db_error = $e->getMessage();
    }
    // Note: pages calling db will need to handle $db === null cases
}
