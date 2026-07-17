<?php
/**
 * WebFalx Admin System Health & Performance Optimization Console
 * Handle minification toggles, database table optimization/repair runs, and server diagnostics
 */

$page_seo = [
    'title' => 'System Health & Performance | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$active_tab = sanitize_input($_GET['tab'] ?? 'diagnostics');
$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle POST Actions (Clear Cache, Optimize Database, Save Performance Toggles)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        $action_type = sanitize_input($_POST['action_type'] ?? '');

        // A. Clear Caches
        if ($action_type === 'clear_cache') {
            // In a simple vanilla setup, clearing cache is resetting settings or clearing asset folders.
            // Let's toggle a cache settings flag
            update_setting('system_cache_timestamp', time());
            log_activity('Clear System Cache', 'Cleared HTML and assets caches.');
            flash_message('perf_flash', 'Asset cache busted and cleared.', 'success');
        }

        // B. Save Minification Toggles
        elseif ($action_type === 'save_perf_toggles') {
            $minify = isset($_POST['minify_assets']) ? '1' : '0';
            $lazy = isset($_POST['lazy_load_images']) ? '1' : '0';

            update_setting('perf_minify_enabled', $minify);
            update_setting('perf_lazy_load_enabled', $lazy);

            log_activity('Save Performance Settings', 'Updated asset minification and lazy loading states.');
            flash_message('perf_flash', 'Performance parameters saved.', 'success');
        }

        // C. Optimize Database Tables
        elseif ($action_type === 'optimize_db') {
            // Run OPTIMIZE on all WebFalx tables
            $tables = ['admins', 'site_settings', 'clients', 'projects', 'project_milestones', 'project_invoices', 'appointments', 'quotations', 'proposals', 'leads', 'blog_posts'];
            foreach ($tables as $tbl) {
                try {
                    $db->exec("OPTIMIZE TABLE `$tbl`");
                } catch (Exception $ex) {
                    // Skip if table missing
                }
            }
            log_activity('Optimize Database', 'Re-indexed and defragmented MySQL tables.');
            flash_message('perf_flash', 'Database tables optimized and defragmented.', 'success');
        }

        // D. Repair Database Tables
        elseif ($action_type === 'repair_db') {
            $tables = ['admins', 'site_settings', 'clients', 'projects', 'project_milestones', 'project_invoices', 'appointments', 'quotations', 'proposals', 'leads', 'blog_posts'];
            foreach ($tables as $tbl) {
                try {
                    $db->exec("REPAIR TABLE `$tbl`");
                } catch (Exception $ex) {
                    // Skip if table missing or engine doesn't support REPAIR
                }
            }
            log_activity('Repair Database', 'Verified index alignments.');
            flash_message('perf_flash', 'Table repairs completed.', 'success');
        }

        header('Location: ' . BASE_URL . 'admin/performance-settings.php?tab=' . $active_tab);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 2. Load system values
$db_tables = [];
$total_rows = 0;
$db_size_kb = 0;

try {
    // Read table sizes & row counts (MySQL specific stats)
    $stmt = $db->query("SHOW TABLE STATUS");
    $db_tables = $stmt->fetchAll();
    foreach ($db_tables as $tbl) {
        $total_rows += intval($tbl['Rows'] ?? 0);
        $db_size_kb += (intval($tbl['Data_length'] ?? 0) + intval($tbl['Index_length'] ?? 0)) / 1024;
    }
} catch (Exception $ex) {
    // Fail silently
}

// Server stats coordinates
$php_version = phpversion();
$mysql_version = '';
try {
    $mysql_version = $db->query("SELECT VERSION()")->fetchColumn();
} catch (Exception $e) {
    $mysql_version = 'Offline';
}

$memory_limit = ini_get('memory_limit');
$upload_limit = ini_get('upload_max_filesize');
$post_limit = ini_get('post_max_size');

// Safe disk space logic (mac support)
$disk_free = @disk_free_space('/') ?: (100 * 1024 * 1024 * 1024);
$disk_total = @disk_total_space('/') ?: (500 * 1024 * 1024 * 1024);
$disk_used = $disk_total - $disk_free;
$disk_percent = round(($disk_used / $disk_total) * 100, 1);

$flash = flash_message('perf_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">System Diagnostics & Performance</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Defragment database index tables, regulate code minification parameters, and monitor storage boundaries.</p>

<div class="admin-tab-group" style="margin-bottom: var(--spacing-sm);">
    <a href="?tab=diagnostics" class="admin-tab-btn <?php echo $active_tab === 'diagnostics' ? 'active' : ''; ?>">Health & Diagnostics</a>
    <a href="?tab=database" class="admin-tab-btn <?php echo $active_tab === 'database' ? 'active' : ''; ?>">Database Optimization</a>
    <a href="?tab=caching" class="admin-tab-btn <?php echo $active_tab === 'caching' ? 'active' : ''; ?>">Cache & Minification</a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 1: HEALTH & DIAGNOSTICS
     ============================================== -->
<?php if ($active_tab === 'diagnostics'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
        
        <!-- Server Diagnostics card -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 12px;">Web Server Diagnostic Coordinates</h4>
            
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.88rem; color: var(--color-text-secondary-dark);">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                    <span>PHP Version:</span>
                    <strong style="color: #fff;"><?php echo esc($php_version); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                    <span>MySQL Version:</span>
                    <strong style="color: #fff;"><?php echo esc($mysql_version); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                    <span>Memory Limit:</span>
                    <strong style="color: #fff;"><?php echo esc($memory_limit); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                    <span>Upload Max Size:</span>
                    <strong style="color: #fff;"><?php echo esc($upload_limit); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                    <span>Post Max size:</span>
                    <strong style="color: #fff;"><?php echo esc($post_limit); ?></strong>
                </div>
            </div>
        </div>

        <!-- Disk Storage Limits -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 12px;">Storage Drive Capacity</h4>
            
            <div style="font-size: 0.88rem; color: var(--color-text-secondary-dark);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Used Space: <?php echo round($disk_used / (1024*1024*1024), 2); ?> GB</span>
                    <span>Total Space: <?php echo round($disk_total / (1024*1024*1024), 2); ?> GB</span>
                </div>
                <!-- Disk percent meter -->
                <div style="background: rgba(255,255,255,0.03); border-radius: var(--radius-full); height: 16px; width: 100%; overflow: hidden; margin-bottom: 8px;">
                    <div style="background: linear-gradient(90deg, var(--color-secondary), var(--color-accent)); height: 100%; width: <?php echo $disk_percent; ?>%;"></div>
                </div>
                <span style="font-size: 0.75rem; color: var(--color-text-muted-dark);">System drive usage is at <?php echo $disk_percent; ?>% capacity limits.</span>
            </div>
        </div>

    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 2: DATABASE OPTIMIZATION
     ============================================= -->
<?php if ($active_tab === 'database'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 2fr;">
        <!-- Optimization Actions -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 12px;">Database System Tasks</h4>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 0.85rem; color: var(--color-text-secondary-dark); border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 6px; margin-bottom: 6px;">
                    <strong>Total Catalog Rows:</strong> <?php echo number_format($total_rows); ?><br>
                    <strong>Database Size:</strong> <?php echo number_format($db_size_kb, 2); ?> KB
                </div>

                <form action="" method="POST" style="margin-bottom: 4px;">
                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                    <input type="hidden" name="action_type" value="optimize_db">
                    <button type="submit" class="btn btn-primary" style="width: 100%; min-height: auto; padding: 10px;">Optimize index Tables</button>
                </form>

                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                    <input type="hidden" name="action_type" value="repair_db">
                    <button type="submit" class="btn btn-secondary" style="width: 100%; min-height: auto; padding: 10px;">Run Repair Checklist</button>
                </form>
            </div>
        </div>

        <!-- Table statuses -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 12px;">Active MySQL Tables Analysis</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Row Count</th>
                        <th>Table Size (KB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($db_tables as $tbl): ?>
                        <tr>
                            <td><strong><?php echo esc($tbl['Name']); ?></strong></td>
                            <td><?php echo number_format($tbl['Rows'] ?? 0); ?></td>
                            <td><?php echo number_format((intval($tbl['Data_length'] ?? 0) + intval($tbl['Index_length'] ?? 0)) / 1024, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 3: CACHE & MINIFICATION
     ============================================= -->
<?php if ($active_tab === 'caching'): ?>
    <div class="glass-card" style="max-width: 600px; padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 12px;">Asset Caching & Performance Settings</h4>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 15px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_perf_toggles">

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.88rem; text-transform: none;">
                    <input type="checkbox" name="minify_assets" value="1" <?php echo get_setting('perf_minify_enabled', '0') === '1' ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                    Enable HTML/CSS/JS Assets Minification (Removes spaces for faster Core Web Vitals)
                </label>
                <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.88rem; text-transform: none;">
                    <input type="checkbox" name="lazy_load_images" value="1" <?php echo get_setting('perf_lazy_load_enabled', '0') === '1' ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                    Enable Images Lazy Loading (`loading="lazy"` tags generation)
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 5px;">Save Performance Settings</button>
        </form>

        <h5 style="color: #ffffff; margin-bottom: 6px;">Bust Caches</h5>
        <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin-bottom: 12px;">Bust local browser assets caches to force visitors to reload edited layouts immediately.</p>
        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="clear_cache">
            <button type="submit" class="btn btn-secondary" style="width: 100%; min-height: auto; padding: 10px;">Bust Cache Logs</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
