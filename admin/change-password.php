<?php
/**
 * WebFalx Admin Change Password
 * Secure interface to change administrative password
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login check
require_admin();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Verify CSRF
        require_csrf_token();

        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please enter all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error_message = 'New password must be at least 8 characters long.';
        } else {
            // 2. Fetch current hashed password from database
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $_SESSION['admin_id']]);
            $hash = $stmt->fetchColumn();

            // 3. Verify current password matches
            if ($hash && password_verify($current_password, $hash)) {
                // Hashing new password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // 4. Update the DB record
                $update_stmt = $db->prepare("UPDATE admins SET password_hash = :hash WHERE id = :id");
                $update_stmt->execute([
                    'hash' => $new_hash,
                    'id' => $_SESSION['admin_id']
                ]);

                flash_message('db_dashboard', 'Password changed successfully.', 'success');
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
                exit;
            } else {
                $error_message = 'Current password is incorrect.';
            }
        }
    } catch (Exception $e) {
        error_log("Password change failure: " . $e->getMessage());
        $error_message = 'An error occurred during password update. Please try again.';
    }
}

$page_seo = [
    'title' => 'Change Administrative Key | WebFalx',
    'description' => 'Security portal to renew administrator login credentials.'
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .dashboard-container {
        padding: var(--spacing-md) 0 var(--spacing-lg) 0;
    }
    .admin-grid {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: var(--spacing-md);
    }
    .admin-sidebar {
        height: max-content;
    }
    .admin-sidebar ul {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .admin-sidebar li a {
        display: block;
        padding: 0.75rem 1rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: var(--radius-sm);
        font-weight: 500;
        font-size: 0.95rem;
        transition: all var(--transition-fast);
    }
    .admin-sidebar li a:hover,
    .admin-sidebar li a.active {
        background: var(--gradient-hero);
        color: #ffffff;
        border-color: transparent;
        transform: translateX(4px);
    }
    
    @media (max-width: 768px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container dashboard-container">
    <div class="reveal">
        <h2 style="margin-bottom: var(--spacing-xs);">Security Settings</h2>
        <p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Update your dashboard credentials.</p>
    </div>
    
    <div class="admin-grid">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar reveal">
            <div class="glass-card">
                <ul>
                    <li><a href="dashboard.php">Overview</a></li>
                    <li><a href="change-password.php" class="active">Change Password</a></li>
                    <li><a href="logout.php" style="color: var(--color-danger);">Sign Out</a></li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Form Panel -->
        <section class="admin-main reveal" style="width: 100%; max-width: 600px;">
            <div class="glass-card">
                <h3 style="margin-bottom: var(--spacing-sm);">Modify Credentials</h3>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger fade-in">
                        <?php echo esc($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password (Min. 8 chars)</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required autocomplete="new-password">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required autocomplete="new-password">
                    </div>
                    
                    <div style="display: flex; gap: var(--spacing-sm);">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
