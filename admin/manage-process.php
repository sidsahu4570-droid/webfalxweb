<?php
/**
 * WebFalx Admin Process Manager
 * Workflow Steps CRUD operations
 */

$page_seo = [
    'title' => 'Manage Process Workflow Steps | WebFalx Admin'
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
            $stmt = $db->prepare("DELETE FROM process_steps WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('process_flash', 'Process step deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-process.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete process step: ' . $e->getMessage();
    }
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $step_number = intval($_POST['step_number'] ?? 0);
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if ($step_number <= 0 || empty($title) || empty($description)) {
            $error_message = 'Please provide valid process step details.';
        } else {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $stmt = $db->prepare("INSERT INTO process_steps (step_number, title, description, display_order, is_active) VALUES (:step, :title, :desc, :order, 1)");
            $stmt->execute([
                'step' => $step_number,
                'title' => $title,
                'desc' => $description,
                'order' => $display_order
            ]);
            
            flash_message('process_flash', 'Process step added successfully.', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to save process step: ' . $e->getMessage();
    }
}

// Fetch all process steps
$steps = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT * FROM process_steps ORDER BY display_order ASC");
        $steps = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch process steps: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage Process Workflow Steps</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Rearrange or rewrite steps displayed in the chronological workflow timeline.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
    <!-- Add New Step Card -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add New Workflow Step</h4>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="step_number">Step Number</label>
                <input type="number" name="step_number" id="step_number" class="form-control" required placeholder="e.g. 1" min="1">
            </div>
            
            <div class="form-group">
                <label for="title">Step Title</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Research & Discovery">
            </div>
            
            <div class="form-group">
                <label for="description">Step Description</label>
                <textarea name="description" id="description" rows="4" class="form-control" required placeholder="Detail the outcomes of this step..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="10" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Step</button>
        </form>
    </div>
    
    <!-- Steps List Table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Workflow Steps</h4>
        
        <div class="table-responsive" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Step #</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th style="width: 60px;">Sequence</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($steps)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--color-text-muted-dark);">No process steps defined.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($steps as $st): ?>
                            <tr>
                                <td><strong style="color: var(--color-accent);">#<?php echo esc($st['step_number']); ?></strong></td>
                                <td>
                                    <strong style="color: #ffffff;"><?php echo esc($st['title']); ?></strong>
                                </td>
                                <td style="font-size: 0.8rem; line-height: 1.4; color: var(--color-text-secondary-dark);">
                                    <?php echo esc(substr($st['description'], 0, 75)) . (strlen($st['description']) > 75 ? '...' : ''); ?>
                                </td>
                                <td><?php echo esc($st['display_order']); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $st['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this process step?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
