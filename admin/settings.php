<?php
/**
 * WebFalx Admin General Website Settings
 * Manages brand coordinates, support timings, logo links, and custom footer texts
 */

$page_seo = [
    'title' => 'Website Settings | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $name = sanitize_input($_POST['site_name'] ?? '');
        $phone = sanitize_input($_POST['contact_phone'] ?? '');
        $email = sanitize_input($_POST['contact_email'] ?? '');
        $address = sanitize_input($_POST['contact_address'] ?? '');
        
        $logo = sanitize_input($_POST['site_logo_url'] ?? '');
        $copyright = sanitize_input($_POST['copyright_text'] ?? '');
        $footer_info = sanitize_input($_POST['footer_info_text'] ?? '');

        // Update database site settings
        update_setting('site_name', $name);
        update_setting('contact_phone', $phone);
        update_setting('contact_email', $email);
        update_setting('contact_address', $address);
        update_setting('site_logo_url', $logo);
        update_setting('copyright_text', $copyright);
        update_setting('footer_info_text', $footer_info);
        
        // Log action trail
        log_activity('Update settings', 'General brand credentials modified.');
        
        flash_message('settings_flash', 'General settings saved successfully.', 'success');
        header('Location: ' . BASE_URL . 'admin/settings.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$flash = flash_message('settings_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">General Website Settings</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Modify company coordinates, office addresses, and brand logos globally.</p>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<div class="glass-card" style="max-width: 800px; padding: 1.25rem;">
    <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Edit Website Coordinates</h4>
    
    <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
        
        <div class="grid grid-2" style="gap: 10px;">
            <div class="form-group">
                <label for="set_name">Company Brand Name</label>
                <input type="text" name="site_name" id="set_name" class="form-control" value="<?php echo esc_attr(get_setting('site_name', 'WebFalx')); ?>" required>
            </div>
            <div class="form-group">
                <label for="set_logo">Branding Logo URL</label>
                <input type="text" name="site_logo_url" id="set_logo" class="form-control" value="<?php echo esc_attr(get_setting('site_logo_url', 'assets/images/logo.png')); ?>">
            </div>
        </div>

        <div class="grid grid-2" style="gap: 10px;">
            <div class="form-group">
                <label for="set_phone">Contact Phone Number</label>
                <input type="text" name="contact_phone" id="set_phone" class="form-control" value="<?php echo esc_attr(get_setting('contact_phone', APP_PHONE)); ?>" required>
            </div>
            <div class="form-group">
                <label for="set_email">Branding Email Address</label>
                <input type="email" name="contact_email" id="set_email" class="form-control" value="<?php echo esc_attr(get_setting('contact_email', 'hello@webfalx.com')); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="set_addr">Office Postal Coordinates</label>
            <input type="text" name="contact_address" id="set_addr" class="form-control" value="<?php echo esc_attr(get_setting('contact_address', 'Pasadena, California, USA')); ?>" required>
        </div>

        <div class="form-group">
            <label for="set_copy">Footer Copyright Text</label>
            <input type="text" name="copyright_text" id="set_copy" class="form-control" value="<?php echo esc_attr(get_setting('copyright_text', '&copy; 2026 WebFalx. All rights reserved.')); ?>">
        </div>

        <div class="form-group">
            <label for="set_foot">Footer Brand Brief Description</label>
            <textarea name="footer_info_text" id="set_foot" rows="3" class="form-control"><?php echo esc(get_setting('footer_info_text', 'WebFalx builds templates-free backend dashboards and speeds up pages load times.')); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Coordinates Settings</button>
    </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
