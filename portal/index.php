<?php
/**
 * Client Portal Root Redirector
 */
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
    header('Location: ' . BASE_URL . 'portal/dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'portal/login.php');
}
exit;
