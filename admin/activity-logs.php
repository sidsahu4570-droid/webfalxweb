<?php
/**
 * WebFalx Admin Security Activity Logs Viewer
 * Display chronological audit trails, admin modification entries, and ip addresses
 */

$page_seo = [
    'title' => 'System Logs | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($db === null) {
    die("Database is offline.");
}

$logs = [];
$search = sanitize_input($_GET['q'] ?? '');

try {
    $query = "SELECT l.*, a.username FROM activity_logs l LEFT JOIN admins a ON l.user_id = a.id";
    $params = [];

    if (!empty($search)) {
        $query .= " WHERE l.action LIKE :q OR l.details LIKE :q OR a.username LIKE :q OR l.ip_address LIKE :q";
        $params['q'] = '%' . $search . '%';
    }

    $query .= " ORDER BY l.id DESC LIMIT 100";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed listing activity logs: " . $e->getMessage());
}
require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">System Security Activity Logs</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Audit operations logged by administrators including settings updates, content publishing, and media changes.</p>

<div class="glass-card" style="padding: 1.25rem; margin-bottom: var(--spacing-md);">
    <form action="" method="GET" style="display: flex; gap: 10px; max-width: 500px;">
        <input type="text" name="q" class="form-control" value="<?php echo esc_attr($search); ?>" placeholder="Search logs by action, details, user, or IP...">
        <button type="submit" class="btn btn-primary" style="min-height: auto; padding: 10px 16px;">Filter</button>
        <?php if (!empty($search)): ?>
            <a href="activity-logs.php" class="btn btn-secondary" style="min-height: auto; padding: 10px 16px; text-align: center;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="glass-card" style="padding: 1.25rem;">
    <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Chronological Audit Trail (Last 100 actions)</h4>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Administrator</th>
                <th>Action Type</th>
                <th>Details</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--color-text-muted-dark); padding: var(--spacing-sm) 0;">No system logs matching search coordinates.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-size: 0.78rem; white-space: nowrap;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><strong><?php echo esc($log['username'] ?: 'System / Guest'); ?></strong></td>
                        <td><span class="status-badge" style="background: rgba(255,255,255,0.04); color: #fff; font-size: 0.72rem;"><?php echo esc($log['action']); ?></span></td>
                        <td style="font-size: 0.8rem; max-width: 300px; word-break: break-all;"><?php echo esc($log['details']); ?></td>
                        <td><code><?php echo esc($log['ip_address']); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
