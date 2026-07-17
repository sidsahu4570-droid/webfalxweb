<?php
/**
 * WebFalx Admin Business Automation Console
 * Integrated manager for smart project calculators pricing rules, client proposals generation, appointments calendar reviews, and SVG conversion reports
 */

$page_seo = [
    'title' => 'Business Automation | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$active_tab = sanitize_input($_GET['tab'] ?? 'pricing');
$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle POST Actions (Save Pricing, Save Proposal, Approve Appointment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        $action_type = sanitize_input($_POST['action_type'] ?? '');

        // A. Save Pricing Rules
        if ($action_type === 'save_pricing') {
            $shopify = floatval($_POST['calc_base_shopify'] ?? 3000);
            $wordpress = floatval($_POST['calc_base_wordpress'] ?? 2000);
            $custom_php = floatval($_POST['calc_base_custom_php'] ?? 4000);
            
            $per_page = floatval($_POST['calc_per_page_rate'] ?? 150);
            $seo = floatval($_POST['calc_seo_rate'] ?? 800);
            $hosting = floatval($_POST['calc_hosting_rate'] ?? 250);
            $maint = floatval($_POST['calc_maintenance_rate'] ?? 350);

            update_setting('calc_base_shopify', $shopify);
            update_setting('calc_base_wordpress', $wordpress);
            update_setting('calc_base_custom_php', $custom_php);
            update_setting('calc_per_page_rate', $per_page);
            update_setting('calc_seo_rate', $seo);
            update_setting('calc_hosting_rate', $hosting);
            update_setting('calc_maintenance_rate', $maint);

            log_activity('Update Pricing Rules', 'Calculator pricing rules changed.');
            flash_message('automation_flash', 'Calculator pricing rules updated successfully.', 'success');
        }

        // B. Generate Proposal
        elseif ($action_type === 'create_proposal') {
            $client_id = intval($_POST['client_id'] ?? 0);
            $title = sanitize_input($_POST['title'] ?? '');
            $scope = sanitize_input($_POST['scope_of_work'] ?? '');
            $timeline = sanitize_input($_POST['timeline'] ?? '4 Weeks');
            $investment = floatval($_POST['investment'] ?? 0);

            if ($client_id <= 0 || empty($title) || $investment <= 0) {
                throw new Exception("Please select a client, write a title, and define the investment amount.");
            }

            $stmt = $db->prepare("INSERT INTO proposals (client_id, title, scope_of_work, timeline, investment, status) VALUES (:cid, :title, :scope, :time, :amt, 'Pending')");
            $stmt->execute([
                'cid' => $client_id,
                'title' => $title,
                'scope' => $scope,
                'time' => $timeline,
                'amt' => $investment
            ]);

            log_activity('Generate Proposal', 'Issued proposal for Client ID: ' . $client_id);
            flash_message('automation_flash', 'Proposal generated and saved to client portal.', 'success');
        }

        // C. Update Appointment Status
        elseif ($action_type === 'update_appt') {
            $appt_id = intval($_POST['appt_id'] ?? 0);
            $status = sanitize_input($_POST['status'] ?? '');

            if ($appt_id > 0 && in_array($status, ['Approved', 'Cancelled'])) {
                $db->prepare("UPDATE appointments SET status = :status WHERE id = :id")->execute(['status' => $status, 'id' => $appt_id]);
                log_activity('Approve Appointment', 'Appointment ID ' . $appt_id . ' status set to ' . $status);
                flash_message('automation_flash', 'Appointment status updated.', 'success');
            }
        }

        header('Location: ' . BASE_URL . 'admin/manage-automation.php?tab=' . $active_tab);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 2. Fetch Lists based on active tab
$appointments = [];
$proposals = [];
$quotations = [];
$clients = [];

try {
    $clients = $db->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll();
    
    if ($active_tab === 'pricing') {
        // Just reading values via get_setting
    } elseif ($active_tab === 'appointments') {
        $appointments = $db->query("SELECT * FROM appointments ORDER BY id DESC")->fetchAll();
    } elseif ($active_tab === 'proposals') {
        $proposals = $db->query("SELECT p.*, c.name as client_name, c.company as client_company FROM proposals p JOIN clients c ON p.client_id = c.id ORDER BY p.id DESC")->fetchAll();
        $quotations = $db->query("SELECT * FROM quotations ORDER BY id DESC")->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed listing automation logs: " . $e->getMessage());
}

$flash = flash_message('automation_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Business Automation Dashboard</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Manage project cost calculator pricing parameters, issue contract proposals, and moderate scheduling sync calendars.</p>

<div class="admin-tab-group" style="margin-bottom: var(--spacing-sm);">
    <a href="?tab=pricing" class="admin-tab-btn <?php echo $active_tab === 'pricing' ? 'active' : ''; ?>">Calculator Pricing Rules</a>
    <a href="?tab=appointments" class="admin-tab-btn <?php echo $active_tab === 'appointments' ? 'active' : ''; ?>">Scoping Appointments</a>
    <a href="?tab=proposals" class="admin-tab-btn <?php echo $active_tab === 'proposals' ? 'active' : ''; ?>">Proposals & Quotations</a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 1: CALCULATOR PRICING RULES
     ============================================== -->
<?php if ($active_tab === 'pricing'): ?>
    <div class="glass-card" style="max-width: 700px; padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Pricing Matrix Configurations</h4>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            <input type="hidden" name="action_type" value="save_pricing">

            <div class="grid grid-3" style="gap: 10px;">
                <div class="form-group">
                    <label for="pr_shopify">Shopify Base Price ($)</label>
                    <input type="number" name="calc_base_shopify" id="pr_shopify" class="form-control" value="<?php echo esc_attr(get_setting('calc_base_shopify', '3000')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="pr_wp">WordPress Base Price ($)</label>
                    <input type="number" name="calc_base_wordpress" id="pr_wp" class="form-control" value="<?php echo esc_attr(get_setting('calc_base_wordpress', '2000')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="pr_php">Custom PHP Base ($)</label>
                    <input type="number" name="calc_base_custom_php" id="pr_php" class="form-control" value="<?php echo esc_attr(get_setting('calc_base_custom_php', '4000')); ?>" required>
                </div>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="pr_page">Rate Per Content Page ($)</label>
                    <input type="number" name="calc_per_page_rate" id="pr_page" class="form-control" value="<?php echo esc_attr(get_setting('calc_per_page_rate', '150')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="pr_seo">SEO Audit Addon Price ($)</label>
                    <input type="number" name="calc_seo_rate" id="pr_seo" class="form-control" value="<?php echo esc_attr(get_setting('calc_seo_rate', '800')); ?>" required>
                </div>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="pr_host">Hosting Setup Addon Price ($)</label>
                    <input type="number" name="calc_hosting_rate" id="pr_host" class="form-control" value="<?php echo esc_attr(get_setting('calc_hosting_rate', '250')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="pr_maint">Monthly Maintenance Addon ($)</label>
                    <input type="number" name="calc_maintenance_rate" id="pr_maint" class="form-control" value="<?php echo esc_attr(get_setting('calc_maintenance_rate', '350')); ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Pricing Rules</button>
        </form>
    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 2: SCOPING APPOINTMENTS
     ============================================== -->
<?php if ($active_tab === 'appointments'): ?>
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Appointments Sync Calendar Queue</h4>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Prospect Details</th>
                    <th>Service & Platform</th>
                    <th>Booking Slot</th>
                    <th>Status</th>
                    <th>Moderator Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--color-text-muted-dark);">No appointments requested.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($appt['name']); ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--color-text-secondary-dark);"><?php echo esc($appt['email']); ?> &bull; <?php echo esc($appt['phone']); ?></span>
                            </td>
                            <td>
                                <strong style="font-size: 0.85rem; color: #fff;"><?php echo esc($appt['service']); ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--color-accent);"><?php echo esc($appt['meeting_type']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo date('M d, Y', strtotime($appt['booking_date'])); ?></strong><br>
                                <small style="color: var(--color-text-muted-dark);"><?php echo date('h:i A', strtotime($appt['booking_time'])); ?></small>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $appt['status'] === 'Approved' ? 'status-won' : ($appt['status'] === 'Pending' ? 'status-new' : 'status-lost'); ?>" style="font-size: 0.65rem;">
                                    <?php echo esc($appt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($appt['status'] === 'Pending'): ?>
                                    <form action="" method="POST" style="display: flex; gap: 4px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                                        <input type="hidden" name="action_type" value="update_appt">
                                        <input type="hidden" name="appt_id" value="<?php echo $appt['id']; ?>">
                                        
                                        <button type="submit" name="status" value="Approved" class="btn btn-primary" style="padding: 2px 6px; font-size: 0.72rem; min-height: auto;">Approve</button>
                                        <button type="submit" name="status" value="Cancelled" class="btn btn-secondary" style="padding: 2px 6px; font-size: 0.72rem; min-height: auto; color: var(--color-warning); border-color: rgba(245,158,11,0.2);">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--color-text-muted-dark); font-size: 0.8rem;">Reviewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 3: PROPOSALS & QUOTATIONS
     ============================================== -->
<?php if ($active_tab === 'proposals'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.3fr;">
        <!-- Generate Proposal -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Generate Proposal Draft</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="create_proposal">

                <div class="form-group">
                    <label for="pp_client">Select Client Portal</label>
                    <select name="client_id" id="pp_client" class="form-control" style="background: var(--color-bg-dark);" required>
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo esc($c['name']); ?> (<?php echo esc($c['company']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pp_title">Proposal Title</label>
                    <input type="text" name="title" id="pp_title" class="form-control" required placeholder="e.g. Luxury Shopify Store Re-design">
                </div>
                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="pp_amt">Investment total ($)</label>
                        <input type="number" name="investment" id="pp_amt" class="form-control" required value="0">
                    </div>
                    <div class="form-group">
                        <label for="pp_time">Timeline Duration</label>
                        <input type="text" name="timeline" id="pp_time" class="form-control" value="6 Weeks">
                    </div>
                </div>
                <div class="form-group">
                    <label for="pp_scope">Scope description & deliverables</label>
                    <textarea name="scope_of_work" id="pp_scope" rows="4" class="form-control" placeholder="Outline deliverables and milestones..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Generate Proposal</button>
            </form>
        </div>

        <!-- Proposals & Quotations history -->
        <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
            <!-- Proposals -->
            <div class="glass-card" style="padding: 1.25rem;">
                <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Active Project Proposals</h4>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Proposal</th>
                            <th>Investment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proposals as $prop): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc($prop['title']); ?></strong><br>
                                    <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Client: <?php echo esc($prop['client_name']); ?></small>
                                </td>
                                <td>$<?php echo number_format($prop['investment'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $prop['status'] === 'Accepted' ? 'status-won' : 'status-new'; ?>" style="font-size: 0.65rem;">
                                        <?php echo esc($prop['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Quotations -->
            <div class="glass-card" style="padding: 1.25rem;">
                <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Quotations Generated (Cost Calculator)</h4>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Quote ID</th>
                            <th>Project specs</th>
                            <th>Estimated Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotations as $q): ?>
                            <tr>
                                <td><strong><?php echo esc($q['quote_number']); ?></strong></td>
                                <td>
                                    <strong style="font-size: 0.85rem; color: #fff;"><?php echo esc($q['project_type']); ?></strong><br>
                                    <small style="color: var(--color-text-muted-dark);"><?php echo $q['pages']; ?> Pages</small>
                                </td>
                                <td>$<?php echo number_format($q['calculated_total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
