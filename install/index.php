<?php
/**
 * WebFalx Enterprise Installation Wizard
 * Regulates system requirements checks, configures database handles dynamically, runs schemas migrations, and locks execution
 */

define('INSTALLER_PATH', __DIR__);

// Check if already locked
if (file_exists(INSTALLER_PATH . '/install.lock')) {
    die("WebFalx is already installed. To re-run this installation wizard, please remove the file: install/install.lock");
}

$step = intval($_GET['step'] ?? 1);
$error = '';
$success = '';

// Step 2 Server requirement variables
$php_valid = version_compare(PHP_VERSION, '8.0.0', '>=');
$exts = ['pdo', 'pdo_mysql', 'session', 'gd', 'json'];
$exts_valid = true;
foreach ($exts as $ext) {
    if (!extension_loaded($ext)) {
        $exts_valid = false;
    }
}
$uploads_dir = __DIR__ . '/../assets/uploads';
$config_file = __DIR__ . '/../includes/config.php';
$logs_dir = __DIR__ . '/../logs';

$uploads_writable = is_writable($uploads_dir) || (!file_exists($uploads_dir) && is_writable(dirname($uploads_dir)));
$config_writable = is_writable($config_file) || is_writable(dirname($config_file));
$logs_writable = is_writable($logs_dir) || (!file_exists($logs_dir) && is_writable(dirname($logs_dir)));

$server_check_passed = $php_valid && $exts_valid && $uploads_writable && $config_writable && $logs_writable;

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 3: Save DB & Connect
    if ($action === 'setup_db') {
        $db_host = trim($_POST['db_host'] ?? '127.0.0.1');
        $db_port = trim($_POST['db_port'] ?? '3306');
        $db_name = trim($_POST['db_name'] ?? 'webfalx');
        $db_user = trim($_POST['db_user'] ?? 'root');
        $db_pass = $_POST['db_pass'] ?? '';

        try {
            // Test connection
            $dsn = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
            $temp_pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            // Create database if not exists
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Update config.php dynamically
            $config_tpl = file_get_contents($config_file);
            
            // Search replace constants
            $config_tpl = preg_replace("/define\(\s*(['\"])DB_HOST\\1\s*,\s*.*?\);/i", "define('DB_HOST', getenv('DB_HOST') ?: '$db_host');", $config_tpl);
            $config_tpl = preg_replace("/define\(\s*(['\"])DB_PORT\\1\s*,\s*.*?\);/i", "define('DB_PORT', getenv('DB_PORT') ?: '$db_port');", $config_tpl);
            $config_tpl = preg_replace("/define\(\s*(['\"])DB_NAME\\1\s*,\s*.*?\);/i", "define('DB_NAME', getenv('DB_NAME') ?: '$db_name');", $config_tpl);
            $config_tpl = preg_replace("/define\(\s*(['\"])DB_USER\\1\s*,\s*.*?\);/i", "define('DB_USER', getenv('DB_USER') ?: '$db_user');", $config_tpl);
            $config_tpl = preg_replace("/define\(\s*(['\"])DB_PASS\\1\s*,\s*.*?\);/i", "define('DB_PASS', getenv('DB_PASS') ?: '$db_pass');", $config_tpl);

            file_put_contents($config_file, $config_tpl);
            
            header('Location: ?step=4');
            exit;
        } catch (PDOException $e) {
            $error = "Database Connection Failed: " . $e->getMessage();
        }
    }

    // Step 4: Import SQL Schemas & Create Admin
    elseif ($action === 'finalize_setup') {
        $adm_user = trim($_POST['admin_username'] ?? 'admin');
        $adm_email = trim($_POST['admin_email'] ?? 'admin@webfalx.com');
        $adm_pass = $_POST['admin_password'] ?? 'AdminPassword123!';

        if (empty($adm_user) || empty($adm_pass)) {
            $error = "Please define administrative username credentials.";
        } else {
            try {
                // Re-include configurations to fetch new constants
                require_once $config_file;
                
                // Establish connection
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                // Run Schema SQL
                $schema_path = __DIR__ . '/../database/schema.sql';
                if (file_exists($schema_path)) {
                    $schema_sql = file_get_contents($schema_path);
                    $pdo->exec($schema_sql);
                } else {
                    throw new Exception("Migration schema.sql file missing.");
                }

                // Run Seed SQL
                $seed_path = __DIR__ . '/../database/seed.sql';
                if (file_exists($seed_path)) {
                    $seed_sql = file_get_contents($seed_path);
                    $pdo->exec($seed_sql);
                } else {
                    throw new Exception("Migration seed.sql file missing.");
                }

                // Insert / Update Custom Admin account
                $hash = password_hash($adm_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE admins SET username = :user, password_hash = :hash, email = :email WHERE id = 1");
                $stmt->execute(['user' => $adm_user, 'hash' => $hash, 'email' => $adm_email]);

                // Write install.lock
                file_put_contents(INSTALLER_PATH . '/install.lock', 'Installed on ' . date('Y-m-d H:i:s'));

                header('Location: ?step=5');
                exit;
            } catch (Exception $e) {
                $error = "SQL Import & finalized setup failure: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebFalx Platform Installer</title>
    <!-- Modern responsive styling directly embedded matching core WebFalx look -->
    <style>
        :root {
            --color-primary: #2563eb;
            --color-secondary: #7c3aed;
            --color-accent: #06b6d4;
            --color-bg-dark: #0f172a;
            --color-text-primary-dark: #f8fafc;
            --color-text-secondary-dark: #94a3b8;
            --border-glass: 1px solid rgba(255, 255, 255, 0.08);
            --radius-sm: 8px;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: var(--color-bg-dark);
            color: var(--color-text-primary-dark);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .installer-container {
            width: 100%;
            max-width: 600px;
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            border: var(--border-glass);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h2 {
            font-size: 1.75rem;
            color: #ffffff;
            margin-bottom: 4px;
        }
        .header p {
            color: var(--color-text-secondary-dark);
            font-size: 0.88rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            border-bottom: var(--border-glass);
            padding-bottom: 15px;
        }
        .step-dot {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--color-text-secondary-dark);
            opacity: 0.5;
        }
        .step-dot.active {
            color: var(--color-accent);
            opacity: 1;
        }
        .alert {
            padding: 10px;
            border-radius: var(--radius-sm);
            margin-bottom: 15px;
            font-size: 0.88rem;
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
        }
        .form-group {
            margin-bottom: 12px;
        }
        .form-group label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--color-text-secondary-dark);
            margin-bottom: 4px;
        }
        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: var(--border-glass);
            border-radius: var(--radius-sm);
            color: #ffffff;
            padding: 8px 12px;
            font-size: 0.88rem;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--color-accent);
        }
        .btn {
            display: block;
            width: 100%;
            background: var(--color-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-secondary {
            background: transparent;
            border: var(--border-glass);
            color: var(--color-text-secondary-dark);
        }
        .req-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }
        .req-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }
        .status-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .status-pass {
            background: rgba(16,185,129,0.1);
            color: #10b981;
        }
        .status-fail {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
    </style>
</head>
<body>

<div class="installer-container">
    <div class="header">
        <h2>WebFalx Platform</h2>
        <p>Enterprise Site Setup Wizard</p>
    </div>

    <div class="step-indicator">
        <span class="step-dot <?php echo $step === 1 ? 'active' : ''; ?>">1. Welcome</span>
        <span class="step-dot <?php echo $step === 2 ? 'active' : ''; ?>">2. Server Check</span>
        <span class="step-dot <?php echo $step === 3 ? 'active' : ''; ?>">3. Database</span>
        <span class="step-dot <?php echo $step === 4 ? 'active' : ''; ?>">4. Setup</span>
        <span class="step-dot <?php echo $step === 5 ? 'active' : ''; ?>">5. Complete</span>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- ==============================================
         STEP 1: WELCOME SCREEN
         ============================================== -->
    <?php if ($step === 1): ?>
        <div style="text-align: center; font-size: 0.88rem; color: var(--color-text-secondary-dark); line-height: 1.6;">
            <p style="margin-bottom: 15px;">Welcome to the WebFalx setup wizard. This utility will automatically run diagnostic tests on your hosting environment, write settings configuration files, and seed required SQL database tables.</p>
            <p style="margin-bottom: 25px;">Please confirm you have database details ready before initiating installation.</p>
            <a href="?step=2" class="btn">Begin Installation Setup</a>
        </div>
    <?php endif; ?>

    <!-- ==============================================
         STEP 2: SERVER CHECK
         ============================================== -->
    <?php if ($step === 2): ?>
        <ul class="req-list">
            <li class="req-item">
                <span>PHP Version (8.0.0+ required)</span>
                <span class="status-badge <?php echo $php_valid ? 'status-pass' : 'status-fail'; ?>">
                    <?php echo PHP_VERSION; ?>
                </span>
            </li>
            <?php foreach ($exts as $ext): 
                $loaded = extension_loaded($ext);
            ?>
                <li class="req-item">
                <span>PHP Extension: <?php echo htmlspecialchars($ext, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="status-badge <?php echo $loaded ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $loaded ? 'Available' : 'Missing'; ?>
                    </span>
                </li>
            <?php endforeach; ?>
            <li class="req-item">
                <span>Folders Permission: assets/uploads</span>
                <span class="status-badge <?php echo $uploads_writable ? 'status-pass' : 'status-fail'; ?>">
                    <?php echo $uploads_writable ? 'Writable' : 'Locked'; ?>
                </span>
            </li>
            <li class="req-item">
                <span>Configuration Permission: includes/config.php</span>
                <span class="status-badge <?php echo $config_writable ? 'status-pass' : 'status-fail'; ?>">
                    <?php echo $config_writable ? 'Writable' : 'Locked'; ?>
                </span>
            </li>
            <li class="req-item">
                <span>Logs Permission: logs/</span>
                <span class="status-badge <?php echo $logs_writable ? 'status-pass' : 'status-fail'; ?>">
                    <?php echo $logs_writable ? 'Writable' : 'Locked'; ?>
                </span>
            </li>
        </ul>

        <?php if ($server_check_passed): ?>
            <a href="?step=3" class="btn">Continue to Database Configuration</a>
        <?php else: ?>
            <div class="alert alert-danger" style="margin-top: 15px;">Please verify write permissions or missing extensions to continue.</div>
            <a href="?step=2" class="btn btn-secondary">Re-run Diagnostics Check</a>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ==============================================
         STEP 3: DATABASE CONFIGURATION
         ============================================== -->
    <?php if ($step === 3): ?>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="action" value="setup_db">

            <div class="form-group">
                <label for="db_host">Database Server Hostname</label>
                <input type="text" name="db_host" id="db_host" class="form-control" value="127.0.0.1" required>
            </div>
            <div class="form-group">
                <label for="db_port">Port</label>
                <input type="text" name="db_port" id="db_port" class="form-control" value="3306" required>
            </div>
            <div class="form-group">
                <label for="db_name">Database Name</label>
                <input type="text" name="db_name" id="db_name" class="form-control" value="webfalx" required>
            </div>
            <div class="form-group">
                <label for="db_user">Username</label>
                <input type="text" name="db_user" id="db_user" class="form-control" value="root" required>
            </div>
            <div class="form-group">
                <label for="db_pass">Password</label>
                <input type="password" name="db_pass" id="db_pass" class="form-control">
            </div>

            <button type="submit" class="btn">Test & Save Configurations</button>
        </form>
    <?php endif; ?>

    <!-- ==============================================
         STEP 4: ADMIN CREATION & SQL SEEDING
         ============================================== -->
    <?php if ($step === 4): ?>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="action" value="finalize_setup">

            <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin-bottom: 10px; text-align: center;">Define custom administrator login credentials to lock the console.</p>

            <div class="form-group">
                <label for="adm_user">Admin Username</label>
                <input type="text" name="admin_username" id="adm_user" class="form-control" value="admin" required>
            </div>
            <div class="form-group">
                <label for="adm_email">Email address</label>
                <input type="email" name="admin_email" id="adm_email" class="form-control" value="admin@webfalx.com" required>
            </div>
            <div class="form-group">
                <label for="adm_pass">Password</label>
                <input type="password" name="admin_password" id="adm_pass" class="form-control" value="AdminPassword123!" required>
            </div>

            <button type="submit" class="btn">Run Migrations & Launch Site</button>
        </form>
    <?php endif; ?>

    <!-- ==============================================
         STEP 5: SETUP COMPLETED
         ============================================== -->
    <?php if ($step === 5): ?>
        <div style="text-align: center; font-size: 0.88rem; color: var(--color-text-secondary-dark); line-height: 1.6;">
            <div style="font-size: 3rem; margin-bottom: 10px; color: #10b981;">✓</div>
            <h3 style="color: #ffffff; margin-bottom: 10px;">WebFalx Ready!</h3>
            <p style="margin-bottom: 25px;">Database migrations have run successfully, configurations have been written, and the installer is now locked.</p>
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="../admin/login.php" class="btn">Go to Admin Dashboard</a>
                <a href="../index.php" class="btn btn-secondary">Go to Homepage</a>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
