<?php
/**
 * WebFalx Admin Theme Customization Panel
 * Customize CSS styling colors (HEX values), font structures, and element border-radii dynamically
 */

$page_seo = [
    'title' => 'Theme Customizations | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $primary = sanitize_input($_POST['color_primary'] ?? '#2563eb');
        $secondary = sanitize_input($_POST['color_secondary'] ?? '#7c3aed');
        $accent = sanitize_input($_POST['color_accent'] ?? '#06b6d4');
        $bg_dark = sanitize_input($_POST['color_bg_dark'] ?? '#0f172a');
        
        $font = sanitize_input($_POST['font_family'] ?? 'Inter, sans-serif');
        $radius = sanitize_input($_POST['border_radius'] ?? '0.5rem');
        $speed = sanitize_input($_POST['animation_speed'] ?? '0.3s');

        update_setting('theme_color_primary', $primary);
        update_setting('theme_color_secondary', $secondary);
        update_setting('theme_color_accent', $accent);
        update_setting('theme_color_bg_dark', $bg_dark);
        update_setting('theme_font_family', $font);
        update_setting('theme_border_radius', $radius);
        update_setting('theme_animation_speed', $speed);

        log_activity('Update theme settings', 'Theme dynamic styling color configurations updated.');
        flash_message('theme_flash', 'Dynamic CSS styling parameters updated.', 'success');
        header('Location: ' . BASE_URL . 'admin/theme-settings.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$flash = flash_message('theme_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Theme Design Settings</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Modify HEX colors, margins, fonts, and hover timings dynamically without touching stylesheets.</p>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<div class="glass-card" style="max-width: 800px; padding: 1.25rem;">
    <h4 style="color: var(--color-accent); margin-bottom: 1rem;">HEX Layout Colors & Typography</h4>
    
    <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
        
        <div class="grid grid-2" style="gap: 10px;">
            <div class="form-group">
                <label for="th_primary">Primary Brand Color</label>
                <div style="display: flex; gap: 8px;">
                    <input type="color" name="color_primary" id="th_primary" value="<?php echo esc_attr(get_setting('theme_color_primary', '#2563eb')); ?>" style="width: 44px; height: 44px; border: none; border-radius: var(--radius-sm); cursor: pointer; background: none;">
                    <input type="text" class="form-control" value="<?php echo esc_attr(get_setting('theme_color_primary', '#2563eb')); ?>" readonly style="flex: 1;">
                </div>
            </div>
            <div class="form-group">
                <label for="th_secondary">Secondary Accent Color</label>
                <div style="display: flex; gap: 8px;">
                    <input type="color" name="color_secondary" id="th_secondary" value="<?php echo esc_attr(get_setting('theme_color_secondary', '#7c3aed')); ?>" style="width: 44px; height: 44px; border: none; border-radius: var(--radius-sm); cursor: pointer; background: none;">
                    <input type="text" class="form-control" value="<?php echo esc_attr(get_setting('theme_color_secondary', '#7c3aed')); ?>" readonly style="flex: 1;">
                </div>
            </div>
        </div>

        <div class="grid grid-2" style="gap: 10px;">
            <div class="form-group">
                <label for="th_accent">Glow Highlighting Accent Color</label>
                <div style="display: flex; gap: 8px;">
                    <input type="color" name="color_accent" id="th_accent" value="<?php echo esc_attr(get_setting('theme_color_accent', '#06b6d4')); ?>" style="width: 44px; height: 44px; border: none; border-radius: var(--radius-sm); cursor: pointer; background: none;">
                    <input type="text" class="form-control" value="<?php echo esc_attr(get_setting('theme_color_accent', '#06b6d4')); ?>" readonly style="flex: 1;">
                </div>
            </div>
            <div class="form-group">
                <label for="th_bg">Visual Backdrop Dark Canvas</label>
                <div style="display: flex; gap: 8px;">
                    <input type="color" name="color_bg_dark" id="th_bg" value="<?php echo esc_attr(get_setting('theme_color_bg_dark', '#0f172a')); ?>" style="width: 44px; height: 44px; border: none; border-radius: var(--radius-sm); cursor: pointer; background: none;">
                    <input type="text" class="form-control" value="<?php echo esc_attr(get_setting('theme_color_bg_dark', '#0f172a')); ?>" readonly style="flex: 1;">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="th_font">Body Text Typography Family</label>
            <select name="font_family" id="th_font" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;">
                <option value="Inter, sans-serif" <?php echo get_setting('theme_font_family') === 'Inter, sans-serif' ? 'selected' : ''; ?>>Inter Sans Serif (Modern & Clean)</option>
                <option value="'Outfit', sans-serif" <?php echo get_setting('theme_font_family') === "'Outfit', sans-serif" ? 'selected' : ''; ?>>Outfit (Luxury Display Styles)</option>
                <option value="system-ui, sans-serif" <?php echo get_setting('theme_font_family') === 'system-ui, sans-serif' ? 'selected' : ''; ?>>Standard OS Default Font</option>
            </select>
        </div>

        <div class="grid grid-2" style="gap: 10px;">
            <div class="form-group">
                <label for="th_radius">Border Radius layout parameters</label>
                <input type="text" name="border_radius" id="th_radius" class="form-control" value="<?php echo esc_attr(get_setting('theme_border_radius', '0.5rem')); ?>" placeholder="e.g. 0.5rem">
            </div>
            <div class="form-group">
                <label for="th_speed">Hover Animation Transition Speed</label>
                <input type="text" name="animation_speed" id="th_speed" class="form-control" value="<?php echo esc_attr(get_setting('theme_animation_speed', '0.3s')); ?>" placeholder="e.g. 0.3s">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Apply Custom Styling Rules</button>
    </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
