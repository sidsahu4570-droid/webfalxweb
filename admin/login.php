<?php
/**
 * WebFalx Admin Login
 * Secure Session-based Login Portal with CSRF Protection
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$error_message = '';

// Handle Authentication POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usernameOrEmail) || empty($password)) {
        $error_message = 'Please fill in all credentials.';
    } elseif ($db === null) {
        $error_message = 'Database connection is offline. Please contact the administrator.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM admins WHERE (username = :user_name OR email = :email) AND status = 'active' LIMIT 1");
            $stmt->execute(['user_name' => $usernameOrEmail, 'email' => $usernameOrEmail]);
            $admin = $stmt->fetch();
            
            // Verify password hash
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Successful login - regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['last_activity'] = time();
                
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
                exit;
            } else {
                // Deliberate delay to mitigate brute force attacks
                usleep(400000); // 400ms delay
                $error_message = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = 'A database error occurred. Please try again later.';
        }
    }
}

// SEO configurations
$page_seo = [
    'title' => 'Secure Admin Portal | WebFalx',
    'description' => 'Administrative authentication gateway for the WebFalx systems.'
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom CSS to hide navigation menu on login screens to keep user focused -->
<style>
    .navbar-header nav, .navbar-header .nav-actions .btn {
        display: none !important;
    }
    .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 160px);
        padding: var(--spacing-md) 0;
    }
    .login-card {
        width: 100%;
        max-width: 420px;
        text-align: center;
    }
</style>

<section class="section login-container">
    <div class="container login-card reveal">
        <div class="glass-card">
            <h2 class="gradient-text" style="font-size: 2rem; margin-bottom: var(--spacing-sm);">Admin Login</h2>
            <p style="margin-bottom: var(--spacing-md); font-size: 0.9rem;">WebFalx Management Console Access</p>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger fade-in">
                    <?php echo esc($error_message); ?>
                </div>
            <?php endif; ?>

            <?php display_flash_messages(); ?>
            
            <form action="" method="POST">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Enter administrative username" required autocomplete="username">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label for="password">Password</label>
                        <a href="forgot-password.php" style="font-size: 0.75rem; color: var(--color-accent); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Forgot?</a>
                    </div>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter secure key" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--spacing-sm);">
                    Authenticate Access
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
