<?php
/**
 * WebFalx Admin Navigation Menu Builder
 * Save, edit, and reorder header menu links dynamically
 */

$page_seo = [
    'title' => 'Menu Manager | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle POST Menu Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'save_menu') {
    try {
        require_csrf_token();
        
        $menu_id = intval($_POST['menu_id'] ?? 0);
        $title = sanitize_input($_POST['title'] ?? '');
        $url = sanitize_input($_POST['url'] ?? '');
        $order = intval($_POST['display_order'] ?? 10);
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($url)) {
            throw new Exception("Menu Title and Link URL are required.");
        }

        if ($menu_id > 0) {
            // UPDATE
            $stmt = $db->prepare("UPDATE menus SET title = :title, url = :url, display_order = :order, is_active = :active WHERE id = :id");
            $stmt->execute(['title' => $title, 'url' => $url, 'order' => $order, 'active' => $active, 'id' => $menu_id]);
            log_activity('Update Menu Link', 'Menu link modified: ' . $title);
            flash_message('menu_flash', 'Menu link updated.', 'success');
        } else {
            // INSERT
            $stmt = $db->prepare("INSERT INTO menus (title, url, display_order, is_active) VALUES (:title, :url, :order, :active)");
            $stmt->execute(['title' => $title, 'url' => $url, 'order' => $order, 'active' => $active]);
            log_activity('Add Menu Link', 'Menu link created: ' . $title);
            flash_message('menu_flash', 'New menu link added.', 'success');
        }

        header('Location: ' . BASE_URL . 'admin/menu-manager.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 2. Handle GET Actions
if (isset($_GET['action'])) {
    try {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            if ($_GET['action'] === 'delete') {
                $db->prepare("DELETE FROM menus WHERE id = :id")->execute(['id' => $id]);
                log_activity('Delete Menu Link', 'Cleared menu ID ' . $id);
                flash_message('menu_flash', 'Menu link deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/menu-manager.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 3. Fetch menu lists
$menus = [];
try {
    $menus = $db->query("SELECT * FROM menus ORDER BY display_order ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Failed listing menus: " . $e->getMessage());
}

$edit_menu = null;
if (isset($_GET['edit_id'])) {
    $eid = intval($_GET['edit_id']);
    $edit_menu = $db->query("SELECT * FROM menus WHERE id = $eid")->fetch();
}

$flash = flash_message('menu_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Navigation Menu Manager</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Build, reorder, and activate header links dynamically across WebFalx.</p>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.3fr;">
    <!-- Save Menu Form -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;"><?php echo $edit_menu ? 'Edit Menu Link' : 'Add Menu Link'; ?></h4>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_menu">
            <input type="hidden" name="menu_id" value="<?php echo $edit_menu ? $edit_menu['id'] : '0'; ?>">

            <div class="form-group">
                <label for="mn_title">Link Display Name</label>
                <input type="text" name="title" id="mn_title" class="form-control" value="<?php echo esc_attr($edit_menu['title'] ?? ''); ?>" required placeholder="e.g. Services">
            </div>

            <div class="form-group">
                <label for="mn_url">Destination Link Target (relative to root)</label>
                <input type="text" name="url" id="mn_url" class="form-control" value="<?php echo esc_attr($edit_menu['url'] ?? ''); ?>" required placeholder="e.g. blog.php or contact.php">
            </div>

            <div class="grid grid-2" style="gap: 10px; align-items: center;">
                <div class="form-group">
                    <label for="mn_order">Sort Display Order</label>
                    <input type="number" name="display_order" id="mn_order" class="form-control" value="<?php echo esc_attr($edit_menu['display_order'] ?? '10'); ?>">
                </div>
                <div class="form-group" style="padding-top: 25px;">
                    <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; text-transform: none;">
                        <input type="checkbox" name="is_active" value="1" <?php echo (!isset($edit_menu) || $edit_menu['is_active'] == 1) ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                        Link Visible
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Menu Link</button>
            <?php if ($edit_menu): ?>
                <a href="menu-manager.php" class="btn btn-secondary" style="text-align: center; font-size: 0.88rem; min-height: auto; padding: 10px;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Active Menu Items table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Active Links</h4>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Display Name</th>
                    <th>Link Target</th>
                    <th>Sort Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menus as $m): ?>
                    <tr style="opacity: <?php echo $m['is_active'] ? '1' : '0.5'; ?>;">
                        <td><strong><?php echo esc($m['title']); ?></strong></td>
                        <td><code><?php echo esc($m['url']); ?></code></td>
                        <td><?php echo $m['display_order']; ?></td>
                        <td style="font-size: 0.8rem;">
                            <a href="?edit_id=<?php echo $m['id']; ?>" class="action-link">Edit</a> |
                            <a href="?action=delete&id=<?php echo $m['id']; ?>" class="action-link action-delete" onclick="return confirm('Remove this menu link target?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
