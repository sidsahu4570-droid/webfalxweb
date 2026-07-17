<?php
/**
 * WebFalx Admin Accounts User Management
 * Manage administrative credentials, system access roles, and suspension states
 */

$page_seo = [
    'title' => 'Admin Accounts | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle GET Actions (Actions, Status changes)
if (isset($_GET['action'])) {
    try {
        $id = intval($_GET['id'] ?? 0);
        $action = sanitize_input($_GET['action']);
        
        if ($id > 0) {
            // Cannot delete current logged-in user or original Super Admin ID 1
            if ($id == $_SESSION['admin_id'] && ($action === 'delete' || $action === 'suspend')) {
                throw new Exception("You cannot alter your own active logged-in status.");
            }
            if ($id == 1 && ($action === 'delete' || $action === 'suspend')) {
                throw new Exception("Original Super Admin root account cannot be altered.");
            }

            if ($action === 'delete') {
                $db->prepare("DELETE FROM admins WHERE id = :id")->execute(['id' => $id]);
                log_activity('Delete User Account', 'Cleared admin account ID ' . $id);
                flash_message('users_flash', 'Admin account deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-users.php');
                exit;
            } elseif ($action === 'suspend') {
                $db->prepare("UPDATE admins SET status = 'suspended' WHERE id = :id")->execute(['id' => $id]);
                log_activity('Suspend User Account', 'Suspended admin account ID ' . $id);
                flash_message('users_flash', 'Admin account suspended.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-users.php');
                exit;
            } elseif ($action === 'activate') {
                $db->prepare("UPDATE admins SET status = 'active' WHERE id = :id")->execute(['id' => $id]);
                log_activity('Activate User Account', 'Activated admin account ID ' . $id);
                flash_message('users_flash', 'Admin account activated.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-users.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 2. Handle POST Add/Edit User Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'save_user') {
    try {
        require_csrf_token();
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $role = sanitize_input($_POST['role'] ?? 'Admin');
        $status = sanitize_input($_POST['status'] ?? 'active');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email)) {
            throw new Exception("Username and Email address are required fields.");
        }

        if ($user_id > 0) {
            // Cannot change own role/status
            if ($user_id == $_SESSION['admin_id'] && ($role !== 'Super Admin' || $status !== 'active')) {
                // Check if current user is trying to lower own privileges
                $curr = $db->query("SELECT * FROM admins WHERE id = $user_id")->fetch();
                if ($curr && ($curr['role'] !== $role || $curr['status'] !== $status)) {
                    throw new Exception("You cannot alter your own role or active status.");
                }
            }

            // UPDATE
            if (!empty($password)) {
                $pwd_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE admins SET username = :user, email = :email, role = :role, status = :status, password_hash = :hash WHERE id = :id");
                $stmt->execute(['user' => $username, 'email' => $email, 'role' => $role, 'status' => $status, 'hash' => $pwd_hash, 'id' => $user_id]);
            } else {
                $stmt = $db->prepare("UPDATE admins SET username = :user, email = :email, role = :role, status = :status WHERE id = :id");
                $stmt->execute(['user' => $username, 'email' => $email, 'role' => $role, 'status' => $status, 'id' => $user_id]);
            }
            
            log_activity('Update User Account', 'Updated details for: ' . $username);
            flash_message('users_flash', 'Admin account details updated.', 'success');
        } else {
            // INSERT
            if (empty($password)) {
                throw new Exception("A secure password is required for new accounts.");
            }
            $pwd_hash = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $db->prepare("INSERT INTO admins (username, email, role, status, password_hash) VALUES (:user, :email, :role, :status, :hash)");
            $stmt->execute(['user' => $username, 'email' => $email, 'role' => $role, 'status' => $status, 'hash' => $pwd_hash]);
            
            log_activity('Create User Account', 'Created new admin user: ' . $username);
            flash_message('users_flash', 'New admin account generated.', 'success');
        }

        header('Location: ' . BASE_URL . 'admin/manage-users.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 3. Fetch admin lists
$admins = [];
try {
    $admins = $db->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Failed listing admins: " . $e->getMessage());
}

$edit_user = null;
if (isset($_GET['edit_id'])) {
    $eid = intval($_GET['edit_id']);
    $edit_user = $db->query("SELECT * FROM admins WHERE id = $eid")->fetch();
}

$flash = flash_message('users_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">System Administrator Accounts</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Create additional admin seats, configure permission roles, or suspend access keys.</p>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.3fr;">
    <!-- Save Account Form -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;"><?php echo $edit_user ? 'Edit Account' : 'Add Admin User'; ?></h4>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_user">
            <input type="hidden" name="user_id" value="<?php echo $edit_user ? $edit_user['id'] : '0'; ?>">

            <div class="form-group">
                <label for="us_name">Account Username</label>
                <input type="text" name="username" id="us_name" class="form-control" value="<?php echo esc_attr($edit_user['username'] ?? ''); ?>" required placeholder="alex_rivera">
            </div>

            <div class="form-group">
                <label for="us_email">Email Address</label>
                <input type="email" name="email" id="us_email" class="form-control" value="<?php echo esc_attr($edit_user['email'] ?? ''); ?>" required placeholder="alex@webfalx.com">
            </div>

            <div class="form-group">
                <label for="us_pass">Password <?php echo $edit_user ? '(Leave empty to keep current)' : ''; ?></label>
                <input type="password" name="password" id="us_pass" class="form-control" <?php echo $edit_user ? '' : 'required'; ?> placeholder="Minimum 8 characters">
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="us_role">System Access Role</label>
                    <select name="role" id="us_role" class="form-control" style="background: var(--color-bg-dark);">
                        <option value="Super Admin" <?php echo (isset($edit_user) && $edit_user['role'] === 'Super Admin') ? 'selected' : ''; ?>>Super Admin</option>
                        <option value="Admin" <?php echo (!isset($edit_user) || $edit_user['role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="Editor" <?php echo (isset($edit_user) && $edit_user['role'] === 'Editor') ? 'selected' : ''; ?>>Editor</option>
                        <option value="Content Manager" <?php echo (isset($edit_user) && $edit_user['role'] === 'Content Manager') ? 'selected' : ''; ?>>Content Manager</option>
                        <option value="SEO Manager" <?php echo (isset($edit_user) && $edit_user['role'] === 'SEO Manager') ? 'selected' : ''; ?>>SEO Manager</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="us_status">Account Access Status</label>
                    <select name="status" id="us_status" class="form-control" style="background: var(--color-bg-dark);">
                        <option value="active" <?php echo (!isset($edit_user) || $edit_user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo (isset($edit_user) && $edit_user['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save User Account</button>
            <?php if ($edit_user): ?>
                <a href="manage-users.php" class="btn btn-secondary" style="text-align: center; font-size: 0.88rem; min-height: auto; padding: 10px;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Active Administrator accounts table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Administrator Seats</h4>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username / Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $u): ?>
                    <tr style="opacity: <?php echo $u['status'] === 'active' ? '1' : '0.5'; ?>;">
                        <td>
                            <strong><?php echo esc($u['username']); ?></strong><br>
                            <span style="font-size: 0.75rem; color: var(--color-text-secondary-dark);"><?php echo esc($u['email']); ?></span>
                        </td>
                        <td><span style="font-size: 0.75rem; color: var(--color-accent); font-weight: 600;"><?php echo esc($u['role']); ?></span></td>
                        <td>
                            <span class="status-badge <?php echo $u['status'] === 'active' ? 'status-won' : 'status-lost'; ?>" style="font-size: 0.65rem;">
                                <?php echo esc($u['status']); ?>
                            </span>
                        </td>
                        <td style="font-size: 0.8rem;">
                            <a href="?edit_id=<?php echo $u['id']; ?>" class="action-link">Edit</a> |
                            <?php if ($u['status'] === 'active'): ?>
                                <a href="?action=suspend&id=<?php echo $u['id']; ?>" class="action-link" style="color: var(--color-warning);">Suspend</a>
                            <?php else: ?>
                                <a href="?action=activate&id=<?php echo $u['id']; ?>" class="action-link" style="color: var(--color-success);">Activate</a>
                            <?php endif; ?>
                            <?php if ($u['id'] != 1 && $u['id'] != $_SESSION['admin_id']): ?>
                                | <a href="?action=delete&id=<?php echo $u['id']; ?>" class="action-link action-delete" onclick="return confirm('Delete this admin seat? Action is irreversible.');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
