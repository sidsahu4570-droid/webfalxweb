<?php
/**
 * WebFalx Client Portal Session Clear
 */

require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['client_logged_in']);
unset($_SESSION['client_id']);
unset($_SESSION['client_name']);
unset($_SESSION['client_company']);

header('Location: ' . BASE_URL . 'portal/login.php');
exit;
