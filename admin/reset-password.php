<?php
/**
 * WebFalx Admin Reset Password
 * Secure password reset screen validating token expiry
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error_message = '';
$success_message = '';
$valid_token = false;
$admin_id = null;

if (empty($token)) {
    header('Location: ' . BASE_URL . 'admin/login.php');
    exit;
}

try {
    if ($db === null) {
        throw new Exception("Database connection is offline.");
    }
    
    // Check if token exists and is not expired
    $stmt = $db->prepare("SELECT id, username FROM admins WHERE reset_token = :token AND reset_token_expires > NOW() LIMIT 1");
    $stmt->execute(['token' => $token]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $valid_token = true;
        $admin_id = $admin['id'];
    } else {
        $error_message = 'Reset token is invalid or has expired.';
    }
} catch (Exception $e) {
    error_log("Password reset validation error: " . $e->getMessage());
    $error_message = 'Unable to validate recovery token. Please try again later.';
}

// Handle Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    try {
        // 1. Verify CSRF
        require_csrf_token();
        
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
        } else {
            // Update admin record: hash password, clear reset tokens
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_stmt = $db->prepare("UPDATE admins SET password_hash = :hash, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
            $update_stmt->execute([
                'hash' => $new_hash,
                'id' => $admin_id
            ]);
            
            flash_message('login_flash', 'Password updated successfully. You can now login with your new key.', 'success');
            header('Location: ' . BASE_URL . 'admin/login.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Password reset processing error: " . $e->getMessage());
        $error_message = 'Failed to update credentials. Please try again.';
    }
}

$page_seo = [
    'title' => 'Reset Administrative Password | WebFalx',
    'description' => 'Renew administrative portal access credentials.'
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .navbar-header nav, .navbar-header .nav-actions .btn {
        display: none !important;
    }
    .reset-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 160px);
        padding: var(--spacing-md) 0;
    }
    .reset-card {
        width: 100%;
        max-width: 440px;
        text-align: center;
    }
</style>

<section class="section reset-container">
    <div class="container reset-card reveal">
        <div class="glass-card">
            <h2 class="gradient-text" style="font-size: 2rem; margin-bottom: var(--spacing-sm);">Reset Password</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger fade-in">
                    <?php echo esc($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token): ?>
                <p style="margin-bottom: var(--spacing-md); font-size: 0.9rem;">Configure a new security password for: <strong><?php echo esc($admin['username']); ?></strong></p>
                
                <form action="" method="POST">
                    <!-- CSRF Protection Field -->
                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                    <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password (Min. 8 characters)</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required autocomplete="new-password">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required autocomplete="new-password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Save New Password
                    </button>
                </form>
            <?php else: ?>
                <div style="margin-top: 1.5rem;">
                    <a href="forgot-password.php" class="btn btn-secondary">Request New Token</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
