<?php
/**
 * WebFalx Helper Functions
 * Security, Sanitization, SEO, and Content Queries
 */

require_once __DIR__ . '/config.php';

/**
 * 1. Security & Output Sanitization (XSS Mitigation)
 */

/**
 * Escapes HTML output safely.
 */
function esc(?string $html): string {
    return htmlspecialchars($html ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escapes attribute value safely.
 */
function esc_attr(?string $attr): string {
    return htmlspecialchars($attr ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Escapes URL safely.
 */
function esc_url(?string $url): string {
    return filter_var($url ?? '', FILTER_SANITIZE_URL);
}

/**
 * Generates absolute asset URLs natively.
 */
function asset(string $path): string {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Generates absolute route links natively.
 */
function route(string $name, array $params = []): string {
    $url = BASE_URL . ltrim($name, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * Generates dynamic relative or absolute URLs natively.
 */
function url(string $path = ''): string {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Retrieves platform configuration parameter natively.
 */
function config(string $key, $default = null) {
    $upperKey = strtoupper($key);
    if (defined($upperKey)) {
        return constant($upperKey);
    }
    return get_setting($key, (string)($default ?? ''));
}

/**
 * Emits CSRF token hidden field natively.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . esc_attr(get_csrf_token()) . '">';
}

/**
 * Recovers previous request value natively.
 */
function old(string $key, $default = '') {
    return $_REQUEST[$key] ?? $default;
}

/**
 * Sanitizes generic input data.
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return trim(strip_tags($data));
}

/**
 * Generates and returns a secure CSRF token.
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies if the provided CSRF token matches the session token.
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforces a valid CSRF token. Terminates request on failure.
 */
function require_csrf_token() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        die('Forbidden: CSRF token validation failed.');
    }
}

/**
 * 2. Administrative Authentication Helpers
 */

/**
 * Checks if the administrator is logged in.
 */
function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Restricts access to administrators only, redirecting unauthorized traffic to the login page.
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

/**
 * 3. Database Content & Settings Fetches
 */

/**
 * Fetches a website setting from the database.
 */
function get_setting(string $key, string $default = ''): string {
    global $db;
    if (!$db) {
        return $default;
    }
    try {
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Failed to fetch setting '$key': " . $e->getMessage());
        return $default;
    }
}

/**
 * Fetches a content block from the database.
 */
function get_content_block(string $key): ?array {
    global $db;
    if (!$db) {
        return null;
    }
    try {
        $stmt = $db->prepare("SELECT title, subtitle, content, image_url, link_url FROM content_blocks WHERE block_key = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Failed to fetch content block '$key': " . $e->getMessage());
        return null;
    }
}

/**
 * 4. Alert & Flash Message Helpers
 */

/**
 * Stores or retrieves a session flash message.
 */
function flash_message(string $name, ?string $message = null, ?string $type = 'success'): ?array {
    if ($message !== null) {
        $_SESSION['flash'][$name] = [
            'message' => $message,
            'type' => $type
        ];
        return null;
    } elseif (isset($_SESSION['flash'][$name])) {
        $flash = $_SESSION['flash'][$name];
        unset($_SESSION['flash'][$name]);
        return $flash;
    }
    return null;
}

/**
 * Outputs standard HTML alert box based on flash session.
 */
function display_flash_messages() {
    if (!empty($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $key => $flash) {
            $typeClass = $flash['type'] === 'success' ? 'alert-success' : ($flash['type'] === 'danger' ? 'alert-danger' : 'alert-warning');
            echo '<div class="alert ' . esc_attr($typeClass) . ' fade-in">' . esc($flash['message']) . '</div>';
            unset($_SESSION['flash'][$key]);
        }
    }
}

/**
 * 5. SEO & Open Graph Header Helpers
 */

/**
 * Generates SEO array combining page specifics and global configurations.
 */
function get_seo_data(array $page_seo = []): array {
    return [
        'title' => $page_seo['title'] ?? get_setting('site_title', 'WebFalx | Premium Digital Agency'),
        'description' => $page_seo['description'] ?? get_setting('site_description', 'Premium Web Development & Marketing Agency'),
        'keywords' => $page_seo['keywords'] ?? get_setting('site_keywords', ''),
        'og_image' => $page_seo['og_image'] ?? BASE_URL . 'assets/images/og-default.jpg',
        'canonical' => $page_seo['canonical'] ?? (BASE_URL . ltrim($_SERVER['REQUEST_URI'], '/'))
    ];
}

/**
 * Helper to slugify strings.
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Updates or inserts a site setting in the database.
 */
function update_setting(string $key, string $value, string $group = 'general') {
    global $db;
    if (!$db) return;
    try {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) 
                              VALUES (:key, :val, :grp) 
                              ON DUPLICATE KEY UPDATE setting_value = :val");
        $stmt->execute(['key' => $key, 'val' => $value, 'grp' => $group]);
    } catch (PDOException $e) {
        error_log("Failed updating setting key '$key': " . $e->getMessage());
    }
}

/**
 * Updates a content block in the database.
 */
function update_content_block(string $key, string $title, string $subtitle, string $content) {
    global $db;
    if (!$db) return;
    try {
        $stmt = $db->prepare("INSERT INTO content_blocks (block_key, title, subtitle, content) 
                              VALUES (:key, :title, :sub, :content) 
                              ON DUPLICATE KEY UPDATE title = :title, subtitle = :sub, content = :content");
        $stmt->execute(['key' => $key, 'title' => $title, 'sub' => $subtitle, 'content' => $content]);
    } catch (PDOException $e) {
        error_log("Failed updating content block '$key': " . $e->getMessage());
    }
}

/**
 * Records administrative activity in audit logs.
 */
function log_activity(string $action, string $details = '') {
    global $db;
    if (!$db) return;
    try {
        $user_id = $_SESSION['admin_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (:uid, :act, :det, :ip)");
        $stmt->execute(['uid' => $user_id, 'act' => $action, 'det' => $details, 'ip' => $ip]);
    } catch (PDOException $e) {
        error_log("Failed storing activity logs: " . $e->getMessage());
    }
}

/**
 * Auto-create redirects table if not exists (fail-safe)
 */
function verify_redirects_table() {
    global $db;
    if (!$db) return;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `redirects` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `source_url` VARCHAR(255) NOT NULL UNIQUE,
            `target_url` VARCHAR(255) NOT NULL,
            `redirect_type` INT DEFAULT 301,
            `clicks` INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    } catch (Exception $e) {
        // Fail silently
    }
}

/**
 * Run automatic URL redirections if any match source URL
 */
function handle_url_redirections() {
    // Get request path
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    // Remove query parameters for matching
    $parsed_url = parse_url($request_uri);
    $path = $parsed_url['path'] ?? '/';
    
    // Direct checks for index.php virtual paths and root paths
    if (preg_match('#^/index\.php/admin(/.*)?$#i', $path) || preg_match('#^/admin/?$#i', $path)) {
        if (is_admin_logged_in()) {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'admin/login.php');
        }
        exit;
    }
    if (preg_match('#^/index\.php/portal(/.*)?$#i', $path) || preg_match('#^/portal/?$#i', $path)) {
        if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
            header('Location: ' . BASE_URL . 'portal/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'portal/login.php');
        }
        exit;
    }

    global $db;
    if (!$db) return;
    
    try {
        $stmt = $db->prepare("SELECT * FROM redirects WHERE source_url = :url LIMIT 1");
        $stmt->execute(['url' => $path]);
        $redir = $stmt->fetch();
        if ($redir) {
            // Prevent redirect loops
            if ($redir['source_url'] !== $redir['target_url']) {
                $code = intval($redir['redirect_type']) === 302 ? 302 : 301;
                
                // Increment clicks count
                $db->prepare("UPDATE redirects SET clicks = clicks + 1 WHERE id = :id")->execute(['id' => $redir['id']]);
                
                header('Location: ' . $redir['target_url'], true, $code);
                exit;
            }
        }
    } catch (Exception $e) {
        // Table may not exist yet or fails silently
    }
}

verify_redirects_table();
handle_url_redirections();

