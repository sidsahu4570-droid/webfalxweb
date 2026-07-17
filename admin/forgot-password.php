<?php
/**
 * WebFalx Admin Forgot Password
 * Secure password recovery token request
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$message = '';
$message_type = 'success'; // default to success message for security timing parity

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Verify CSRF
        require_csrf_token();
        
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'danger';
        } else {
            // 2. Query DB
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $stmt = $db->prepare("SELECT id, username FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                // 3. Generate token & expiry
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save to db
                $update_stmt = $db->prepare("UPDATE admins SET reset_token = :token, reset_token_expires = :expiry WHERE id = :id");
                $update_stmt->execute([
                    'token' => $token,
                    'expiry' => $expiry,
                    'id' => $admin['id']
                ]);
                
                // 4. Simulate mailing by writing reset URL to error log for developers
                $reset_url = BASE_URL . "admin/reset-password.php?token=" . $token;
                error_log("ADMIN PASSWORD RESET LINK REQUESTED: username: " . $admin['username'] . " | Reset Link: " . $reset_url);
            }
            
            // Standard generic message to prevent timing and email discovery attacks
            $message = 'If that email address matches an administrator account, a recovery link has been generated and dispatched.';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        error_log("Forgot password failure: " . $e->getMessage());
        $message = 'An unexpected error occurred. Please try again later.';
        $message_type = 'danger';
    }
}

$page_seo = [
    'title' => 'Password Recovery Portal | WebFalx',
    'description' => 'Secure administration password recovery dispatch.'
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .navbar-header nav, .navbar-header .nav-actions .btn {
        display: none !important;
    }
    .recovery-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 160px);
        padding: var(--spacing-md) 0;
    }
    .recovery-card {
        width: 100%;
        max-width: 440px;
        text-align: center;
    }
</style>

<section class="section recovery-container">
    <div class="container recovery-card reveal">
        <div class="glass-card">
            <h2 class="gradient-text" style="font-size: 2rem; margin-bottom: var(--spacing-sm);">Password Recovery</h2>
            <p style="margin-bottom: var(--spacing-md); font-size: 0.9rem;">Enter your email to receive a recovery token</p>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo esc_attr($message_type); ?> fade-in">
                    <?php echo esc($message); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <!-- CSRF Protection Field -->
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="email">Administrative Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="admin@webfalx.com" required autocomplete="email">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Generate Recovery Link
                </button>
                
                <div style="margin-top: 1.5rem;">
                    <a href="login.php" style="font-size: 0.85rem; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.5px;">Return to Authentication</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
