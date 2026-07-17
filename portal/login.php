<?php
/**
 * WebFalx Client Portal Authentication
 * Secure login portal utilizing password hashes, secure session controls, and CSRF protection
 */

require_once __DIR__ . '/../includes/functions.php';

$error_message = '';
$success_message = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
    header('Location: ' . BASE_URL . 'portal/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            throw new Exception("Please enter your email and password.");
        }

        if ($db === null) {
            throw new Exception("Database is offline.");
        }

        $stmt = $db->prepare("SELECT * FROM clients WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $client = $stmt->fetch();

        if (!$client || !password_verify($password, $client['password_hash'])) {
            throw new Exception("Invalid email or password credentials.");
        }

        if ($client['status'] !== 'active') {
            throw new Exception("Your portal access has been suspended. Please contact WebFalx desk.");
        }

        // Establish secure client sessions
        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['name'];
        $_SESSION['client_company'] = $client['company'];

        header('Location: ' . BASE_URL . 'portal/dashboard.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$page_seo = [
    'title' => 'Client Portal Login | WebFalx'
];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section login-section" style="padding: var(--spacing-xl) 0; min-height: 80vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at top, rgba(124,58,237,0.05) 0%, var(--color-bg-dark) 70%);">
    <div class="container reveal" style="max-width: 450px;">
        
        <div class="glass-card" style="padding: 2.25rem; border-radius: var(--radius-md); border-color: rgba(255,255,255,0.06); text-align: center;">
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">CLIENT PORTAL</span>
            <h2 style="color: #ffffff; font-size: 1.85rem; margin-top: 0.25rem; margin-bottom: var(--spacing-xs);">Welcome Back</h2>
            <p style="font-size: 0.88rem; color: var(--color-text-secondary-dark); margin-bottom: var(--spacing-sm);">Sign in to track progress, download invoices, and approve milestones.</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" style="margin-bottom: 12px; font-size: 0.82rem; text-align: left;"><?php echo esc($error_message); ?></div>
            <?php endif; ?>

            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px; text-align: left;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">

                <div class="form-group">
                    <label for="cl_email">Account Email</label>
                    <input type="email" name="email" id="cl_email" class="form-control" required placeholder="name@company.com">
                </div>

                <div class="form-group">
                    <label for="cl_pass">Password</label>
                    <input type="password" name="password" id="cl_pass" class="form-control" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">Sign In to Portal</button>
            </form>

            <div style="margin-top: 15px; font-size: 0.8rem; color: var(--color-text-muted-dark);">
                Need access? Contact your WebFalx account manager to register your workspace.
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
