<?php
/**
 * WebFalx Admin Contact & Lead System Settings
 * Edit contact coordinates, SMTP keys, map embeds, and auto-reply templates
 */

$page_seo = [
    'title' => 'Contact & SMTP Settings | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$active_tab = sanitize_input($_GET['tab'] ?? 'contact');
$error_message = '';
$success_message = '';

// Handle Settings Update POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $action_type = sanitize_input($_POST['action_type'] ?? '');
        
        if ($db === null) {
            throw new Exception("Database offline.");
        }
        
        if ($action_type === 'save_contact_settings') {
            $phone = sanitize_input($_POST['contact_phone'] ?? '');
            $email = sanitize_input($_POST['contact_email'] ?? '');
            $address = sanitize_input($_POST['contact_address'] ?? '');
            $whatsapp_num = sanitize_input($_POST['whatsapp_number'] ?? '');
            $whatsapp_msg = sanitize_input($_POST['whatsapp_prefilled_msg'] ?? '');
            $map_url = $_POST['google_map_embed_url'] ?? '';
            $map_status = isset($_POST['google_map_enabled']) ? '1' : '0';

            update_setting('contact_phone', $phone);
            update_setting('contact_email', $email);
            update_setting('contact_address', $address);
            update_setting('whatsapp_number', $whatsapp_num);
            update_setting('whatsapp_prefilled_msg', $whatsapp_msg);
            update_setting('google_map_embed_url', $map_url);
            update_setting('google_map_enabled', $map_status);
            
            flash_message('settings_flash', 'Contact settings updated successfully.', 'success');
        } 
        
        elseif ($action_type === 'save_smtp_settings') {
            $host = sanitize_input($_POST['smtp_host'] ?? '');
            $port = sanitize_input($_POST['smtp_port'] ?? '');
            $user = sanitize_input($_POST['smtp_user'] ?? '');
            $pass = sanitize_input($_POST['smtp_pass'] ?? '');
            $name = sanitize_input($_POST['sender_name'] ?? '');
            $s_email = sanitize_input($_POST['sender_email'] ?? '');
            $n_email = sanitize_input($_POST['notification_email'] ?? '');

            update_setting('smtp_host', $host);
            update_setting('smtp_port', $port);
            update_setting('smtp_user', $user);
            update_setting('smtp_pass', $pass);
            update_setting('sender_name', $name);
            update_setting('sender_email', $s_email);
            update_setting('notification_email', $n_email);

            flash_message('settings_flash', 'SMTP and sender credentials saved.', 'success');
        }
        
        elseif ($action_type === 'save_reply_settings') {
            $subj = sanitize_input($_POST['auto_reply_subject'] ?? '');
            $tmpl = $_POST['auto_reply_template'] ?? ''; // Keep spacing intact

            update_setting('auto_reply_subject', $subj);
            update_setting('auto_reply_template', $tmpl);

            flash_message('settings_flash', 'Auto-Reply email templates updated.', 'success');
        }

        header('Location: ' . BASE_URL . 'admin/manage-contact-settings.php?tab=' . $active_tab);
        exit;
    } catch (Exception $ex) {
        $error_message = $ex->getMessage();
    }
}

// Fetch flash success messages
$flash_success = flash_message('settings_flash');
if ($flash_success) {
    $success_message = $flash_success['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Contact & Lead System Settings</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Configure WhatsApp triggers, active coordinates, SMTP servers, and auto-reply templates.</p>

<!-- URL Tabs navigation bar -->
<div class="admin-tab-group">
    <a href="?tab=contact" class="admin-tab-btn <?php echo $active_tab === 'contact' ? 'active' : ''; ?>">Contact Coordinates</a>
    <a href="?tab=smtp" class="admin-tab-btn <?php echo $active_tab === 'smtp' ? 'active' : ''; ?>">SMTP Credentials</a>
    <a href="?tab=reply" class="admin-tab-btn <?php echo $active_tab === 'reply' ? 'active' : ''; ?>">Auto-Reply Templates</a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in">
        <?php echo esc($success_message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 1: CONTACT COORDINATES
     ========================================== -->
<?php if ($active_tab === 'contact'): ?>
    <div class="glass-card" style="max-width: 750px; padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Edit Office Coordinates</h4>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_contact_settings">

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="c_phone">Main Phone Number</label>
                    <input type="text" name="contact_phone" id="c_phone" class="form-control" value="<?php echo esc_attr(get_setting('contact_phone', APP_PHONE)); ?>" required>
                </div>
                <div class="form-group">
                    <label for="c_email">Direct Email Address</label>
                    <input type="email" name="contact_email" id="c_email" class="form-control" value="<?php echo esc_attr(get_setting('contact_email', 'hello@webfalx.com')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="c_addr">Physical Office Address</label>
                <input type="text" name="contact_address" id="c_addr" class="form-control" value="<?php echo esc_attr(get_setting('contact_address', 'Pasadena, California, USA')); ?>" required>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="c_wa_num">WhatsApp Action Number</label>
                    <input type="text" name="whatsapp_number" id="c_wa_num" class="form-control" value="<?php echo esc_attr(get_setting('whatsapp_number', '16266273414')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="c_wa_msg">WhatsApp Pre-filled Message</label>
                    <input type="text" name="whatsapp_prefilled_msg" id="c_wa_msg" class="form-control" value="<?php echo esc_attr(get_setting('whatsapp_prefilled_msg', 'Hello WebFalx!')); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="c_map_url">Google Map Embed Link (iframe src)</label>
                <textarea name="google_map_embed_url" id="c_map_url" rows="3" class="form-control"><?php echo esc(get_setting('google_map_embed_url')); ?></textarea>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="google_map_enabled" id="c_map_status" value="1" <?php echo get_setting('google_map_enabled', '1') == '1' ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                <label for="c_map_status" style="margin-bottom: 0; cursor: pointer;">Render map location on Contact page</label>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Coordinates</button>
        </form>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 2: SMTP CREDENTIALS
     ========================================== -->
<?php if ($active_tab === 'smtp'): ?>
    <div class="glass-card" style="max-width: 750px; padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Edit SMTP Mail Server Config</h4>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_smtp_settings">

            <div class="grid grid-3" style="gap: 10px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label for="sm_host">SMTP Hostname</label>
                    <input type="text" name="smtp_host" id="sm_host" class="form-control" value="<?php echo esc_attr(get_setting('smtp_host')); ?>" placeholder="smtp.mailgun.org">
                </div>
                <div class="form-group">
                    <label for="sm_port">SMTP Port</label>
                    <input type="text" name="smtp_port" id="sm_port" class="form-control" value="<?php echo esc_attr(get_setting('smtp_port')); ?>" placeholder="587">
                </div>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="sm_user">SMTP Username</label>
                    <input type="text" name="smtp_user" id="sm_user" class="form-control" value="<?php echo esc_attr(get_setting('smtp_user')); ?>">
                </div>
                <div class="form-group">
                    <label for="sm_pass">SMTP Password</label>
                    <input type="password" name="smtp_pass" id="sm_pass" class="form-control" value="<?php echo esc_attr(get_setting('smtp_pass')); ?>">
                </div>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="sm_name">Sender Display Name</label>
                    <input type="text" name="sender_name" id="sm_name" class="form-control" value="<?php echo esc_attr(get_setting('sender_name')); ?>" placeholder="WebFalx Team">
                </div>
                <div class="form-group">
                    <label for="sm_email">Sender Outbound Email</label>
                    <input type="email" name="sender_email" id="sm_email" class="form-control" value="<?php echo esc_attr(get_setting('sender_email')); ?>" placeholder="noreply@webfalx.com">
                </div>
            </div>

            <div class="form-group">
                <label for="sm_notify">Admin Notification Recipient Email</label>
                <input type="email" name="notification_email" id="sm_notify" class="form-control" value="<?php echo esc_attr(get_setting('notification_email')); ?>" placeholder="admin@webfalx.com">
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Credentials</button>
        </form>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 3: AUTO-REPLY TEMPLATES
     ========================================== -->
<?php if ($active_tab === 'reply'): ?>
    <div class="glass-card" style="max-width: 750px; padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Edit Auto-Reply Confirmation Email</h4>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_reply_settings">

            <div class="form-group">
                <label for="rp_subj">Email Subject</label>
                <input type="text" name="auto_reply_subject" id="rp_subj" class="form-control" value="<?php echo esc_attr(get_setting('auto_reply_subject')); ?>" required>
                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Use token {id} to include the Lead ID dynamically.</small>
            </div>

            <div class="form-group">
                <label for="rp_tmpl">Email Body Template</label>
                <textarea name="auto_reply_template" id="rp_tmpl" rows="8" class="form-control" style="font-family: monospace;" required><?php echo esc(get_setting('auto_reply_template')); ?></textarea>
                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Use tokens {name} and {id} to output lead credentials inside the confirmation template.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Templates</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
