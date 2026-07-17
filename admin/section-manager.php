<?php
/**
 * WebFalx Admin Section Layout Manager
 * Displays homepage sections, toggles active visibility, and manages display ordering
 */

$page_seo = [
    'title' => 'Manage Homepage Sections Layout | WebFalx Admin'
];

require_once __DIR__ . '/admin_header.php';

$error_message = '';

// Handle Layout Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        // Loop through inputs and apply values
        $orders = $_POST['display_order'] ?? [];
        $actives = $_POST['is_active'] ?? []; // only contains checked inputs
        
        if ($db === null) {
            throw new Exception("Database is offline.");
        }
        
        // Fetch all IDs to ensure we disable unchecked items
        $all_sections_stmt = $db->query("SELECT id, section_key FROM homepage_sections");
        $all_sections = $all_sections_stmt->fetchAll();
        
        $db->beginTransaction();
        
        $update_stmt = $db->prepare("UPDATE homepage_sections SET display_order = :order, is_active = :active WHERE id = :id");
        
        foreach ($all_sections as $sec) {
            $id = $sec['id'];
            $key = $sec['section_key'];
            
            $order = intval($orders[$id] ?? 0);
            $active = isset($actives[$key]) ? 1 : 0;
            
            $update_stmt->execute([
                'order' => $order,
                'active' => $active,
                'id' => $id
            ]);
        }
        
        $db->commit();
        flash_message('sections_flash', 'Homepage layout sections updated successfully.', 'success');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Failed updating sections layout: " . $e->getMessage());
        $error_message = 'Failed to update section parameters: ' . $e->getMessage();
    }
}

// Fetch sections from database
$sections = [];
if ($db !== null) {
    try {
        $stmt = $db->prepare("SELECT * FROM homepage_sections ORDER BY display_order ASC");
        $stmt->execute();
        $sections = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed fetching sections list: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Homepage Layout Section Manager</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Enable or disable sections, or change their display sequence on the homepage.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<form action="" method="POST">
    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Active</th>
                    <th>Section Name</th>
                    <th>System Key</th>
                    <th style="width: 140px;">Display Sequence</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sections)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark);">No layout sections available. Initialize the seeder.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sections as $sec): ?>
                        <tr>
                            <td>
                                <!-- Active Toggle -->
                                <input type="checkbox" name="is_active[<?php echo esc_attr($sec['section_key']); ?>]" value="1" <?php echo $sec['is_active'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                            </td>
                            <td>
                                <strong style="color: #ffffff;"><?php echo esc($sec['section_name']); ?></strong>
                            </td>
                            <td style="color: var(--color-text-secondary-dark); font-family: monospace;">
                                <?php echo esc($sec['section_key']); ?>
                            </td>
                            <td>
                                <!-- Order input field -->
                                <input type="number" name="display_order[<?php echo esc_attr($sec['id']); ?>]" class="form-control" value="<?php echo esc_attr($sec['display_order']); ?>" style="padding: 0.25rem 0.5rem; text-align: center;" required>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Save Layout Parameters</button>
    </div>
</form>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
