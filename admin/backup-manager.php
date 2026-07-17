<?php
/**
 * WebFalx Admin Backup Manager Utility
 * Pure PHP Database Backup engine. Exports tables schema structures and inserts data seeds securely
 */

$page_seo = [
    'title' => 'Backup Manager | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Trigger SQL Backup Download (Pure PHP MySQL Exporter)
if (isset($_GET['action']) && $_GET['action'] === 'download_sql') {
    try {
        log_activity('Download Backup', 'Database SQL schema and data exported.');

        $sql_dump = "-- WebFalx Database Backup Dump\n";
        $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        // Query all tables
        $tables = [];
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $sql_dump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            
            // Get Create statement
            $createStmt = $db->query("SHOW CREATE TABLE `" . $table . "`")->fetch(PDO::FETCH_NUM);
            $sql_dump .= $createStmt[1] . ";\n\n";

            // Get Rows insert statements
            $rows = $db->query("SELECT * FROM `" . $table . "`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sql_dump .= "INSERT INTO `" . $table . "` (";
                $cols = array_keys($rows[0]);
                $sql_dump .= implode(", ", array_map(function($c) { return "`" . $c . "`"; }, $cols));
                $sql_dump .= ") VALUES\n";

                $valLines = [];
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = "NULL";
                        } else {
                            $vals[] = $db->quote($val);
                        }
                    }
                    $valLines[] = "(" . implode(", ", $vals) . ")";
                }
                $sql_dump .= implode(",\n", $valLines) . ";\n\n";
            }
        }

        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        // Output file download headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=webfalx_backup_' . date('Ymd_His') . '.sql');
        header('Content-Length: ' . strlen($sql_dump));
        echo $sql_dump;
        exit;
    } catch (Exception $e) {
        $error_message = 'Failed to generate backup: ' . $e->getMessage();
    }
}

$flash = flash_message('backup_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Database Backup Manager</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Download database tables snapshots containing all settings coordinates, service logs, process timelines, and case study profiles.</p>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<div class="glass-card" style="max-width: 600px; padding: 1.5rem; text-align: center;">
    <h4 style="color: var(--color-accent); margin-bottom: var(--spacing-xs);">Generate Schema & Data SQL Backup</h4>
    <p style="font-size: 0.88rem; color: var(--color-text-secondary-dark); margin-bottom: var(--spacing-md); line-height: 1.5;">
        This utility reads table configurations and content rows directly from MySQL, compiling a standalone SQL script file containing drop/create tables, constraints, and data insert definitions.
    </p>

    <a href="?action=download_sql" class="btn btn-primary" style="display: inline-block; padding: 10px 20px;">Download SQL File Backup</a>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
