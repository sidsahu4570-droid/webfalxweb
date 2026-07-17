<?php
/**
 * WebFalx Database Seeder & Installer
 * Run this script to automatically initialize database tables and seed values
 */

require_once __DIR__ . '/../includes/config.php';

// Force dev mode display for this installer script
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== WEBFALX DATABASE SEEDER ===\n";

if ($db === null) {
    echo "ERROR: Unable to connect to database. Checking if database '" . DB_NAME . "' exists...\n";
    
    try {
        // Connect to MySQL server without selecting database
        $tempDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $tempDb = new PDO($tempDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        echo "Creating database '" . DB_NAME . "'...\n";
        $tempDb->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database created successfully.\n";
        
        // Retry connection
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $db = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        echo "Database connection established.\n";
    } catch (PDOException $ex) {
        die("FATAL DATABASE ERROR: Could not create or connect to database. Message: " . $ex->getMessage() . "\n");
    }
}

// 1. Load Schema
$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    die("FATAL: schema.sql file not found at '$schemaFile'\n");
}

echo "Executing schema.sql...\n";
try {
    $schemaSql = file_get_contents($schemaFile);
    // Execute raw SQL commands
    $db->exec($schemaSql);
    echo "Schema loaded successfully.\n";
} catch (PDOException $e) {
    die("FATAL SCHEMA ERROR: " . $e->getMessage() . "\n");
}

// 2. Load Seed Data
$seedFile = __DIR__ . '/seed.sql';
if (!file_exists($seedFile)) {
    die("FATAL: seed.sql file not found at '$seedFile'\n");
}

echo "Executing seed.sql...\n";
try {
    $seedSql = file_get_contents($seedFile);
    // Execute seed SQL
    $db->exec($seedSql);
    echo "Seed data inserted successfully.\n";
} catch (PDOException $e) {
    die("FATAL SEED ERROR: " . $e->getMessage() . "\n");
}

echo "\nDatabase migration completed successfully!\n";
echo "Default Credentials:\n";
echo "Username: admin\n";
echo "Password: AdminPassword123!\n";
echo "================================\n";
