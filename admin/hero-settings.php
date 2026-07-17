<?php
/**
 * WebFalx Admin Hero & Stats Settings
 * Handles dynamic content updates for Hero text, Typing animations, and Statistics counters
 */

$page_seo = [
    'title' => 'Hero & Stats Settings | WebFalx Admin'
];

require_once __DIR__ . '/admin_header.php';

$error_message = '';
$success_message = '';

// Retrieve current data
$hero = get_content_block('hero_section') ?? [
    'title' => '',
    'subtitle' => '',
    'content' => ''
];

$typing_terms = get_setting('hero_typing_terms', '');
$stat_projects = get_setting('stat_projects_completed', '');
$stat_clients = get_setting('stat_happy_clients', '');
$stat_years = get_setting('stat_years_experience', '');
$stat_support = get_setting('stat_support_hours', '');

// Process updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF Check
        require_csrf_token();

        // 1. Sanitize & Update Hero content blocks
        $hero_title = sanitize_input($_POST['hero_title'] ?? '');
        $hero_subtitle = sanitize_input($_POST['hero_subtitle'] ?? '');
        $hero_content = sanitize_input($_POST['hero_content'] ?? '');
        
        $stmt = $db->prepare("INSERT INTO content_blocks (block_key, title, subtitle, content) 
                              VALUES ('hero_section', :title, :subtitle, :content) 
                              ON DUPLICATE KEY UPDATE title = :title, subtitle = :subtitle, content = :content");
        $stmt->execute([
            'title' => $hero_title,
            'subtitle' => $hero_subtitle,
            'content' => $hero_content
        ]);

        // Helper function for quick key update
        function update_setting_value(string $key, string $val) {
            global $db;
            $stmt = $db->prepare("UPDATE site_settings SET setting_value = :val WHERE setting_key = :key");
            $stmt->execute(['val' => $val, 'key' => $key]);
        }

        // 2. Update specific variables
        update_setting_value('hero_typing_terms', sanitize_input($_POST['hero_typing_terms'] ?? ''));
        update_setting_value('stat_projects_completed', sanitize_input($_POST['stat_projects_completed'] ?? ''));
        update_setting_value('stat_happy_clients', sanitize_input($_POST['stat_happy_clients'] ?? ''));
        update_setting_value('stat_years_experience', sanitize_input($_POST['stat_years_experience'] ?? ''));
        update_setting_value('stat_support_hours', sanitize_input($_POST['stat_support_hours'] ?? ''));

        // Refresh variables
        $hero = get_content_block('hero_section');
        $typing_terms = get_setting('hero_typing_terms', '');
        $stat_projects = get_setting('stat_projects_completed', '');
        $stat_clients = get_setting('stat_happy_clients', '');
        $stat_years = get_setting('stat_years_experience', '');
        $stat_support = get_setting('stat_support_hours', '');

        flash_message('hero_flash', 'Hero settings and statistics counters updated successfully.', 'success');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        error_log("Failed updating hero configurations: " . $e->getMessage());
        $error_message = 'Failed to save settings: ' . $e->getMessage();
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Hero & Stats Settings</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Modify the principal presentation text and metrics displayed on the home page.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<form action="" method="POST" style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
    <!-- CSRF Field -->
    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
    
    <h4 style="border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.25rem; margin-top: 0.5rem; color: var(--color-accent);">Hero Brand Copy</h4>
    
    <div class="form-group">
        <label for="hero_subtitle">Kicker Subtitle</label>
        <input type="text" name="hero_subtitle" id="hero_subtitle" class="form-control" value="<?php echo esc_attr($hero['subtitle']); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="hero_title">Primary Headline</label>
        <input type="text" name="hero_title" id="hero_title" class="form-control" value="<?php echo esc_attr($hero['title']); ?>" required>
    </div>

    <div class="form-group">
        <label for="hero_typing_terms">Typing Animation Words (Comma Separated)</label>
        <input type="text" name="hero_typing_terms" id="hero_typing_terms" class="form-control" value="<?php echo esc_attr($typing_terms); ?>" required>
        <small style="color: var(--color-text-muted-dark); font-size: 0.75rem;">Example: Local Businesses, Startups, Clinics</small>
    </div>

    <div class="form-group">
        <label for="hero_content">Description Summary</label>
        <textarea name="hero_content" id="hero_content" rows="4" class="form-control" required><?php echo esc($hero['content']); ?></textarea>
    </div>

    <h4 style="border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.25rem; margin-top: 1rem; color: var(--color-accent);">Statistics Counter values</h4>
    
    <div class="grid grid-2" style="gap: 15px;">
        <div class="form-group">
            <label for="stat_projects_completed">Projects Completed</label>
            <input type="number" name="stat_projects_completed" id="stat_projects_completed" class="form-control" value="<?php echo esc_attr($stat_projects); ?>" required>
        </div>
        <div class="form-group">
            <label for="stat_happy_clients">Happy Clients</label>
            <input type="number" name="stat_happy_clients" id="stat_happy_clients" class="form-control" value="<?php echo esc_attr($stat_clients); ?>" required>
        </div>
    </div>
    
    <div class="grid grid-2" style="gap: 15px;">
        <div class="form-group">
            <label for="stat_years_experience">Years Experience</label>
            <input type="number" name="stat_years_experience" id="stat_years_experience" class="form-control" value="<?php echo esc_attr($stat_years); ?>" required>
        </div>
        <div class="form-group">
            <label for="stat_support_hours">Technical Support (e.g. 24 for 24/7)</label>
            <input type="text" name="stat_support_hours" id="stat_support_hours" class="form-control" value="<?php echo esc_attr($stat_support); ?>" required>
        </div>
    </div>

    <div style="margin-top: 1rem;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
