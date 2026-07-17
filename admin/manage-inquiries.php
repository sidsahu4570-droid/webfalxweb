<?php
/**
 * WebFalx Admin Inquiries Viewer
 * Reviews submitted client scopes and contact details
 */

$page_seo = [
    'title' => 'Project Inquiries Console | WebFalx Admin'
];

require_once __DIR__ . '/admin_header.php';

$error_message = '';
$success_message = '';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if ($id > 0) {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            $stmt = $db->prepare("DELETE FROM service_inquiries WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('inquiry_flash', 'Project inquiry record deleted.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-inquiries.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete record: ' . $e->getMessage();
    }
}

// Fetch all inquiries
$inquiries = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT i.*, s.title as service_title 
                            FROM service_inquiries i 
                            LEFT JOIN services s ON i.service_id = s.id 
                            ORDER BY i.id DESC");
        $inquiries = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch inquiries: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Project Inquiries Viewer</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Review inbound leads and specifications submitted by potential clients.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="glass-card" style="padding: 1.25rem;">
    <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Inbound Inquiries</h4>
    
    <div class="table-responsive" style="margin-top: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Submission Date</th>
                    <th>Client Details</th>
                    <th>Service / Budget</th>
                    <th>Project Description</th>
                    <th style="width: 80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inquiries)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--color-text-muted-dark); padding: var(--spacing-md) 0;">No inquiries received yet. Keep optimising marketing funnels.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td style="font-size: 0.8rem; color: var(--color-text-muted-dark);">
                                <?php echo date('M d, Y', strtotime($inq['created_at'])); ?><br>
                                <?php echo date('h:i A', strtotime($inq['created_at'])); ?>
                            </td>
                            <td>
                                <strong style="color: #ffffff;"><?php echo esc($inq['full_name']); ?></strong>
                                <div style="font-size: 0.8rem; color: var(--color-text-secondary-dark);"><?php echo esc($inq['email']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Tel: <?php echo esc($inq['phone']); ?></div>
                                <?php if (!empty($inq['company_name'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--color-accent); font-weight: 600;">Co: <?php echo esc($inq['company_name']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: #ffffff;"><?php echo esc($inq['service_title'] ?: 'General'); ?></strong>
                                <div style="font-size: 0.8rem; color: var(--color-text-muted-dark);">Budget: <?php echo esc($inq['budget'] ?: 'Not Specified'); ?></div>
                            </td>
                            <td style="font-size: 0.85rem; line-height: 1.4; max-width: 320px; word-wrap: break-word; white-space: normal;">
                                <?php echo esc($inq['message']); ?>
                            </td>
                            <td>
                                <a href="?action=delete&id=<?php echo $inq['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this inquiry record?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
