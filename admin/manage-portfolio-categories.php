<?php
/**
 * WebFalx Admin Portfolio Categories Manager
 * CRUD operations for Case Study Categories
 */

$page_seo = [
    'title' => 'Manage Portfolio Categories | WebFalx Admin'
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
            $stmt = $db->prepare("DELETE FROM portfolio_categories WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('port_cat_flash', 'Portfolio category deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-portfolio-categories.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete category: ' . $e->getMessage();
    }
}

// Handle Add Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $name = sanitize_input($_POST['name'] ?? '');
        $slug = sanitize_input($_POST['slug'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        if (empty($name)) {
            $error_message = 'Please provide a category name.';
        } else {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $stmt = $db->prepare("INSERT INTO portfolio_categories (name, slug, display_order, is_active) VALUES (:name, :slug, :order, 1)");
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'order' => $display_order
            ]);
            
            flash_message('port_cat_flash', 'Portfolio category added successfully.', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to save category: ' . $e->getMessage();
    }
}

// Fetch categories
$categories = [];
if ($db !== null) {
    try {
        $categories = $db->query("SELECT * FROM portfolio_categories ORDER BY display_order ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch categories: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage Portfolio Categories</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Create, arrange, or delete categories for portfolio items.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
    <!-- Add New Category Card -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Portfolio Category</h4>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Shopify Store">
            </div>
            
            <div class="form-group">
                <label for="slug">URL Slug (Optional)</label>
                <input type="text" name="slug" id="slug" class="form-control" placeholder="e.g. shopify-store">
                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Leave empty to auto-generate from name.</small>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="10" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Category</button>
        </form>
    </div>
    
    <!-- Active Categories List Table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Current Portfolio Categories</h4>
        
        <div class="table-responsive" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Slug</th>
                        <th>Sequence</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark);">No categories created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong style="color: #ffffff;"><?php echo esc($cat['name']); ?></strong>
                                </td>
                                <td style="font-size: 0.8rem; font-family: monospace; color: var(--color-text-secondary-dark);">
                                    <?php echo esc($cat['slug']); ?>
                                </td>
                                <td><?php echo esc($cat['display_order']); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
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
