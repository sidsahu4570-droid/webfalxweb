<?php
/**
 * WebFalx Admin SEO Manager Settings & Redirects Controller
 * Includes global meta tagging forms, dynamic SEO audits scanner, and 301/302 redirect loops prevention logs
 */

$page_seo = [
    'title' => 'Advanced SEO & Redirects | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$active_tab = sanitize_input($_GET['tab'] ?? 'meta');
$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        $action_type = sanitize_input($_POST['action_type'] ?? '');

        // A. Save Meta parameters
        if ($action_type === 'save_meta') {
            $title = sanitize_input($_POST['site_title'] ?? '');
            $desc = sanitize_input($_POST['site_description'] ?? '');
            $keywords = sanitize_input($_POST['site_keywords'] ?? '');
            $google_id = sanitize_input($_POST['google_analytics_id'] ?? '');
            $console_id = sanitize_input($_POST['google_verification_id'] ?? '');
            $schema = $_POST['schema_organization_json'] ?? '';

            update_setting('site_title', $title);
            update_setting('site_description', $desc);
            update_setting('site_keywords', $keywords);
            update_setting('google_analytics_id', $google_id);
            update_setting('google_verification_id', $console_id);
            update_setting('schema_organization_json', $schema);

            log_activity('Update SEO Meta Settings', 'Updated page titles and meta keywords.');
            flash_message('seo_flash_msg', 'Global SEO parameters saved.', 'success');
        }

        // B. Add Redirect log
        elseif ($action_type === 'add_redirect') {
            $source = sanitize_input($_POST['source_url'] ?? '');
            $target = sanitize_input($_POST['target_url'] ?? '');
            $type = intval($_POST['redirect_type'] ?? 301);

            if (empty($source) || empty($target)) {
                throw new Exception("Please input both source and target URL parameters.");
            }

            // Prevent redirect loops
            if ($source === $target) {
                throw new Exception("Source and target URLs cannot be identical (prevent redirect loop).");
            }

            $stmt = $db->prepare("INSERT INTO redirects (source_url, target_url, redirect_type) VALUES (:src, :tgt, :type) ON DUPLICATE KEY UPDATE target_url = :tgt, redirect_type = :type");
            $stmt->execute(['src' => $source, 'tgt' => $target, 'type' => $type]);

            log_activity('Add URL Redirect', 'Mapped source ' . $source . ' to target ' . $target);
            flash_message('seo_flash_msg', 'URL Redirect rule added successfully.', 'success');
        }

        header('Location: ' . BASE_URL . 'admin/seo-manager.php?tab=' . $active_tab);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 2. Handle GET actions (delete redirect rule)
if (isset($_GET['action']) && $_GET['action'] === 'delete_redirect') {
    try {
        $rid = intval($_GET['id'] ?? 0);
        if ($rid > 0) {
            $db->prepare("DELETE FROM redirects WHERE id = :id")->execute(['id' => $rid]);
            log_activity('Delete Redirect Rule', 'Removed redirect ID ' . $rid);
            flash_message('seo_flash_msg', 'Redirect rule cleared.', 'success');
        }
        header('Location: ' . BASE_URL . 'admin/seo-manager.php?tab=redirects');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 3. Load lists
$redirects = [];
$audit_issues = [];

try {
    $redirects = $db->query("SELECT * FROM redirects ORDER BY id DESC")->fetchAll();

    // Run dynamic SEO Audit scanner on tab load
    if ($active_tab === 'audit') {
        // A. Check services missing metas
        $services = $db->query("SELECT id, title, slug, meta_title, meta_description FROM services")->fetchAll();
        foreach ($services as $serv) {
            if (empty($serv['meta_title']) || $serv['meta_title'] === 'NULL') {
                $audit_issues[] = ['type' => 'Service', 'name' => $serv['title'], 'issue' => 'Missing Meta Title tag', 'slug' => $serv['slug']];
            }
            if (empty($serv['meta_description']) || $serv['meta_description'] === 'NULL') {
                $audit_issues[] = ['type' => 'Service', 'name' => $serv['title'], 'issue' => 'Missing Meta Description Tag', 'slug' => $serv['slug']];
            }
        }

        // B. Check blogs missing metas
        $blogs = $db->query("SELECT id, title, slug, meta_title, meta_description FROM blog_posts")->fetchAll();
        foreach ($blogs as $post) {
            if (empty($post['meta_title']) || $post['meta_title'] === 'NULL') {
                $audit_issues[] = ['type' => 'Blog Post', 'name' => $post['title'], 'issue' => 'Missing SEO Meta Title tag', 'slug' => $post['slug']];
            }
            if (empty($post['meta_description']) || $post['meta_description'] === 'NULL') {
                $audit_issues[] = ['type' => 'Blog Post', 'name' => $post['title'], 'issue' => 'Missing SEO Description Tag', 'slug' => $post['slug']];
            }
        }
    }
} catch (PDOException $e) {
    error_log("Failed listing SEO properties: " . $e->getMessage());
}

$flash = flash_message('seo_flash_msg');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Advanced SEO & Redirect Coordinator</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Scan search engine index statuses, configure tags, and establish redirection paths.</p>

<div class="admin-tab-group" style="margin-bottom: var(--spacing-sm);">
    <a href="?tab=meta" class="admin-tab-btn <?php echo $active_tab === 'meta' ? 'active' : ''; ?>">Meta Tags Settings</a>
    <a href="?tab=audit" class="admin-tab-btn <?php echo $active_tab === 'audit' ? 'active' : ''; ?>">SEO Audit Scanner</a>
    <a href="?tab=redirects" class="admin-tab-btn <?php echo $active_tab === 'redirects' ? 'active' : ''; ?>">URL Redirects</a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 1: META SETTINGS
     ============================================== -->
<?php if ($active_tab === 'meta'): ?>
    <div class="glass-card" style="max-width: 800px; padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;">SEO Headers & Schemas</h4>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_meta">
            
            <div class="form-group">
                <label for="seo_title">Global Page SEO Title Tag</label>
                <input type="text" name="site_title" id="seo_title" class="form-control" value="<?php echo esc_attr(get_setting('site_title', 'WebFalx | Premium Digital Marketing')); ?>" required>
            </div>

            <div class="form-group">
                <label for="seo_desc">Global Page SEO Description Tag</label>
                <textarea name="site_description" id="seo_desc" rows="3" class="form-control" required><?php echo esc(get_setting('site_description')); ?></textarea>
            </div>

            <div class="form-group">
                <label for="seo_keys">Search Meta Keywords (Comma Separated)</label>
                <input type="text" name="site_keywords" id="seo_keys" class="form-control" value="<?php echo esc_attr(get_setting('site_keywords')); ?>">
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="seo_g_analytics">Google Analytics Tracking ID (G-XXXXX)</label>
                    <input type="text" name="google_analytics_id" id="seo_g_analytics" class="form-control" value="<?php echo esc_attr(get_setting('google_analytics_id')); ?>" placeholder="G-XXXXXXXXXX">
                </div>
                <div class="form-group">
                    <label for="seo_g_console">Google Search Console Verification Key</label>
                    <input type="text" name="google_verification_id" id="seo_g_console" class="form-control" value="<?php echo esc_attr(get_setting('google_verification_id')); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="seo_schema">Organization JSON-LD Schema Markup</label>
                <textarea name="schema_organization_json" id="seo_schema" rows="6" class="form-control" style="font-family: monospace;"><?php echo esc(get_setting('schema_organization_json')); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save SEO Specifications</button>
        </form>
    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 2: SEO AUDIT SCANNER
     ============================================== -->
<?php if ($active_tab === 'audit'): ?>
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;">SEO Audit Scanner Results</h4>
        
        <?php if (empty($audit_issues)): ?>
            <div style="background: rgba(16,185,129,0.08); border: 1px solid var(--color-success); border-radius: var(--radius-sm); padding: 12px; text-align: center; color: var(--color-success);">
                ✓ Excellent! No critical SEO tag errors identified. All database pages comply with search standards.
            </div>
        <?php else: ?>
            <p style="color: var(--color-warning); margin-bottom: 12px; font-weight: 600;">Found <?php echo count($audit_issues); ?> issues needing correction:</p>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Page Type</th>
                        <th>Page Title</th>
                        <th>Critical SEO Warning</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_issues as $iss): ?>
                        <tr>
                            <td><strong style="color: var(--color-accent);"><?php echo $iss['type']; ?></strong></td>
                            <td><?php echo esc($iss['name']); ?></td>
                            <td>
                                <span style="color: var(--color-warning); font-weight: 600;">⚠️ <?php echo $iss['issue']; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 3: URL REDIRECTS MANAGER
     ============================================== -->
<?php if ($active_tab === 'redirects'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
        <!-- Add Redirect form -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Create Redirection Rule</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_redirect">

                <div class="form-group">
                    <label for="rd_src">Source URL (Relative request path)</label>
                    <input type="text" name="source_url" id="rd_src" class="form-control" required placeholder="e.g. /old-services-page" style="padding: 6px 12px; min-height: auto; font-size: 0.85rem;">
                </div>
                <div class="form-group">
                    <label for="rd_tgt">Target URL (Absolute or relative redirect target)</label>
                    <input type="text" name="target_url" id="rd_tgt" class="form-control" required placeholder="e.g. /services" style="padding: 6px 12px; min-height: auto; font-size: 0.85rem;">
                </div>
                <div class="form-group">
                    <label for="rd_type">Redirect HTTP Status Code</label>
                    <select name="redirect_type" id="rd_type" class="form-control" style="background: var(--color-bg-dark); font-size: 0.85rem; padding: 6px; min-height: auto;">
                        <option value="301" selected>301 Moved Permanently (SEO Friendly)</option>
                        <option value="302">302 Found Temporarily</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Redirect Rule</button>
            </form>
        </div>

        <!-- Redirects list table -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Redirection Ledger</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Source URL</th>
                        <th>Target Destination</th>
                        <th>Clicks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($redirects)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark);">No URL redirects mapped.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($redirects as $r): ?>
                            <tr>
                                <td><span style="font-family: monospace; font-size: 0.8rem;"><?php echo esc($r['source_url']); ?></span></td>
                                <td>
                                    <span style="font-family: monospace; font-size: 0.8rem; color: #fff;"><?php echo esc($r['target_url']); ?></span><br>
                                    <small style="color: var(--color-text-muted-dark); font-size: 0.65rem;">HTTP: <?php echo $r['redirect_type']; ?></small>
                                </td>
                                <td><?php echo $r['clicks']; ?></td>
                                <td style="font-size: 0.8rem;">
                                    <a href="?action=delete_redirect&id=<?php echo $r['id']; ?>" class="action-link action-delete" onclick="return confirm('Delete redirect path rule?');">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
