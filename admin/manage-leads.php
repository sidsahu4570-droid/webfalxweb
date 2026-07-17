<?php
/**
 * WebFalx Admin Leads Console
 * Dynamic leads pipeline, priority edits, CSV export, notes logger, and performance counters
 */

$page_seo = [
    'title' => 'Project Leads Console | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

// 1. Handle CSV Export Action
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        if ($db === null) {
            throw new Exception("Database offline.");
        }
        
        $stmt = $db->query("SELECT lead_id, lead_type, full_name, phone, whatsapp, email, company_name, city, state, country, service_interested, budget, deadline, subject, message, status, priority, followup_date, created_at FROM leads ORDER BY id DESC");
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=webfalx_leads_' . date('Ymd') . '.csv');
        
        $output = fopen('php://output', 'w');
        // CSV Headers row
        fputcsv($output, ['Lead ID', 'Type', 'Full Name', 'Phone', 'WhatsApp', 'Email', 'Company', 'City', 'State', 'Country', 'Service', 'Budget', 'Timeline', 'Subject', 'Message', 'Status', 'Priority', 'Followup Date', 'Date Logged']);
        
        foreach ($leads as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    } catch (Exception $e) {
        error_log("Failed to export leads CSV: " . $e->getMessage());
        echo 'Export failed: ' . $e->getMessage();
        exit;
    }
}

$error_message = '';
$success_message = '';

// 2. Handle Lead Status / Notes POST Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_lead') {
    try {
        require_csrf_token();
        
        $lead_id_key = intval($_POST['id'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? 'New');
        $priority = sanitize_input($_POST['priority'] ?? 'Medium');
        $assigned_staff = sanitize_input($_POST['assigned_staff'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');
        $followup_date = $_POST['followup_date'] ?: null;

        if ($lead_id_key > 0) {
            if ($db === null) {
                throw new Exception("Database offline.");
            }
            
            $stmt = $db->prepare("UPDATE leads SET status = :status, priority = :priority, assigned_staff = :staff, notes = :notes, followup_date = :fdate WHERE id = :id");
            $stmt->execute([
                'status' => $status,
                'priority' => $priority,
                'staff' => $assigned_staff,
                'notes' => $notes,
                'fdate' => $followup_date,
                'id' => $lead_id_key
            ]);
            
            flash_message('leads_flash', 'Lead details updated successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-leads.php');
            exit;
        }
    } catch (Exception $ex) {
        $error_message = 'Failed to update lead: ' . $ex->getMessage();
    }
}

// 3. Handle Delete Lead Request
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            if ($db === null) {
                throw new Exception("Database offline.");
            }
            $stmt = $db->prepare("DELETE FROM leads WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('leads_flash', 'Lead deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-leads.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete lead: ' . $e->getMessage();
    }
}

// 4. Calculate Statistics Widgets
$total_leads = 0;
$new_leads = 0;
$won_leads = 0;
$lost_leads = 0;
$pending_leads = 0;
$conversion_rate = 0;

if ($db !== null) {
    try {
        $total_leads = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
        $new_leads = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'New'")->fetchColumn();
        $won_leads = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'Won'")->fetchColumn();
        $lost_leads = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'Lost'")->fetchColumn();
        $pending_leads = $total_leads - $won_leads - $lost_leads;
        
        if ($total_leads > 0) {
            $conversion_rate = round(($won_leads / $total_leads) * 100, 1);
        }
    } catch (PDOException $ex) {
        error_log("Failed calculating dashboard metrics: " . $ex->getMessage());
    }
}

// 5. Fetch Filtered Leads List
$leads = [];
$search = sanitize_input($_GET['search'] ?? '');
$status_filter = sanitize_input($_GET['status'] ?? '');
$priority_filter = sanitize_input($_GET['priority'] ?? '');

if ($db !== null) {
    try {
        $query_str = "SELECT * FROM leads WHERE 1 = 1";
        $params = [];
        
        if (!empty($search)) {
            $query_str .= " AND (full_name LIKE :search OR email LIKE :search OR phone LIKE :search OR company_name LIKE :search OR lead_id LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        if (!empty($status_filter)) {
            $query_str .= " AND status = :status";
            $params['status'] = $status_filter;
        }

        if (!empty($priority_filter)) {
            $query_str .= " AND priority = :priority";
            $params['priority'] = $priority_filter;
        }
        
        $query_str .= " ORDER BY id DESC";
        $stmt = $db->prepare($query_str);
        $stmt->execute($params);
        $leads = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed searching leads log: " . $e->getMessage());
    }
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Leads Pipeline Console</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Review inbound project specifications, edit follow-up tasks, and track performance ratios.</p>

<!-- 1. Stats Counter Row -->
<div class="grid grid-4" style="margin-bottom: var(--spacing-md); gap: 10px;">
    <div class="glass-card" style="padding: 12px; text-align: center;">
        <div style="font-size: 1.75rem; font-weight: 800; color: #ffffff;"><?php echo $total_leads; ?></div>
        <div style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase;">Total leads</div>
    </div>
    <div class="glass-card" style="padding: 12px; text-align: center;">
        <div style="font-size: 1.75rem; font-weight: 800; color: #3b82f6;"><?php echo $new_leads; ?></div>
        <div style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase;">New Leads</div>
    </div>
    <div class="glass-card" style="padding: 12px; text-align: center;">
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--color-success);"><?php echo $won_leads; ?></div>
        <div style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase;">Won Deals</div>
    </div>
    <div class="glass-card" style="padding: 12px; text-align: center;">
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--color-accent);"><?php echo $conversion_rate; ?>%</div>
        <div style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase;">Conversion Rate</div>
    </div>
</div>

<!-- 2. Search & Export Controls bar -->
<div class="glass-card" style="padding: 1rem; margin-bottom: var(--spacing-sm);">
    <form action="" method="GET" style="display: flex; gap: var(--spacing-xs); flex-wrap: wrap; align-items: center; justify-content: space-between;">
        
        <div style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1;">
            <input type="text" name="search" class="form-control" placeholder="Search by name, email, company..." value="<?php echo esc_attr($search); ?>" style="width: 260px; min-height: auto;">
            
            <select name="status" class="form-control" style="width: 140px; background: var(--color-bg-dark); cursor: pointer; min-height: auto;">
                <option value="">All Statuses</option>
                <option value="New" <?php echo $status_filter == 'New' ? 'selected' : ''; ?>>New</option>
                <option value="Contacted" <?php echo $status_filter == 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                <option value="In Discussion" <?php echo $status_filter == 'In Discussion' ? 'selected' : ''; ?>>Discussion</option>
                <option value="Quotation Sent" <?php echo $status_filter == 'Quotation Sent' ? 'selected' : ''; ?>>Quote Sent</option>
                <option value="Won" <?php echo $status_filter == 'Won' ? 'selected' : ''; ?>>Won</option>
                <option value="Lost" <?php echo $status_filter == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                <option value="Spam" <?php echo $status_filter == 'Spam' ? 'selected' : ''; ?>>Spam</option>
            </select>

            <select name="priority" class="form-control" style="width: 130px; background: var(--color-bg-dark); cursor: pointer; min-height: auto;">
                <option value="">All Priorities</option>
                <option value="Low" <?php echo $priority_filter == 'Low' ? 'selected' : ''; ?>>Low</option>
                <option value="Medium" <?php echo $priority_filter == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="High" <?php echo $priority_filter == 'High' ? 'selected' : ''; ?>>High</option>
            </select>

            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-size: 0.85rem; border-radius: var(--radius-sm); min-height: auto;">Filter</button>
        </div>

        <div>
            <a href="?export=csv" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.85rem; border-radius: var(--radius-sm); min-height: auto;">Export CSV</a>
        </div>
    </form>
</div>

<!-- 3. Leads Database Log Table -->
<div class="glass-card" style="padding: 1rem;">
    <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Inbound Lead Pipeline</h4>
    
    <div class="table-responsive" style="margin-top: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Lead ID / Date</th>
                    <th>Client Details</th>
                    <th>Request Details</th>
                    <th>Task Coordinates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--color-text-muted-dark); padding: var(--spacing-md) 0;">No leads found matching your search parameters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leads as $item): ?>
                        <tr>
                            <!-- Column 1 -->
                            <td style="font-size: 0.8rem;">
                                <strong style="color: #ffffff;"><?php echo esc($item['lead_id']); ?></strong><br>
                                <span style="color: var(--color-text-muted-dark);"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></span><br>
                                <span class="status-badge <?php echo 'status-' . strtolower(str_replace(' ', '', $item['status'])); ?>"><?php echo esc($item['status']); ?></span>
                            </td>

                            <!-- Column 2 -->
                            <td>
                                <strong style="color: #ffffff;"><?php echo esc($item['full_name']); ?></strong>
                                <div style="font-size: 0.8rem; color: var(--color-text-secondary-dark);"><?php echo esc($item['email']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Tel: <?php echo esc($item['phone']); ?></div>
                                <?php if (!empty($item['company_name'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--color-accent); font-weight: 600;">Co: <?php echo esc($item['company_name']); ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Column 3 -->
                            <td style="font-size: 0.82rem; max-width: 250px;">
                                <div style="font-weight: 600; color: #ffffff;">Service: <?php echo esc($item['service_interested'] ?: 'General Inquiry'); ?></div>
                                <div style="font-size: 0.75rem; color: var(--color-text-muted-dark);">Budget: <?php echo esc($item['budget'] ?: 'Not specified'); ?> | Deadline: <?php echo esc($item['deadline'] ?: 'N/A'); ?></div>
                                <p style="margin-top: 4px; line-height: 1.3; color: var(--color-text-secondary-dark);"><?php echo esc($item['message']); ?></p>
                                
                                <?php if (!empty($item['file_path'])): ?>
                                    <div style="margin-top: 8px;">
                                        <a href="<?php echo BASE_URL . $item['file_path']; ?>" target="_blank" style="color: var(--color-accent); font-weight: 700; font-size: 0.75rem;">Download Attachment</a>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Column 4 -->
                            <td>
                                <!-- Quick update form inline -->
                                <form action="" method="POST" style="display: flex; flex-direction: column; gap: 4px; font-size: 0.8rem;">
                                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                                    <input type="hidden" name="action_type" value="update_lead">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    
                                    <div style="display: flex; gap: 4px;">
                                        <select name="status" class="form-control" style="padding: 2px 4px; min-height: auto; font-size: 0.75rem; background: var(--color-bg-dark);">
                                            <option value="New" <?php echo $item['status'] == 'New' ? 'selected' : ''; ?>>New</option>
                                            <option value="Contacted" <?php echo $item['status'] == 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                                            <option value="In Discussion" <?php echo $item['status'] == 'In Discussion' ? 'selected' : ''; ?>>Discussion</option>
                                            <option value="Quotation Sent" <?php echo $item['status'] == 'Quotation Sent' ? 'selected' : ''; ?>>Quote Sent</option>
                                            <option value="Won" <?php echo $item['status'] == 'Won' ? 'selected' : ''; ?>>Won</option>
                                            <option value="Lost" <?php echo $item['status'] == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                                            <option value="Spam" <?php echo $item['status'] == 'Spam' ? 'selected' : ''; ?>>Spam</option>
                                        </select>

                                        <select name="priority" class="form-control" style="padding: 2px 4px; min-height: auto; font-size: 0.75rem; background: var(--color-bg-dark);">
                                            <option value="Low" <?php echo $item['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                                            <option value="Medium" <?php echo $item['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="High" <?php echo $item['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                                        </select>
                                    </div>
                                    
                                    <input type="text" name="assigned_staff" class="form-control" placeholder="Assign staff" value="<?php echo esc_attr($item['assigned_staff']); ?>" style="padding: 2px 4px; min-height: auto; font-size: 0.75rem;">
                                    <input type="date" name="followup_date" class="form-control" value="<?php echo esc_attr($item['followup_date']); ?>" style="padding: 2px 4px; min-height: auto; font-size: 0.75rem;">
                                    
                                    <textarea name="notes" placeholder="Internal notes" rows="1" style="padding: 2px 4px; font-size: 0.75rem; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-sm); resize: vertical;"><?php echo esc($item['notes']); ?></textarea>
                                    
                                    <button type="submit" class="btn btn-secondary" style="padding: 2px 6px; font-size: 0.7rem; border-radius: var(--radius-sm); min-height: auto; margin-top: 2px;">Update Lead</button>
                                </form>
                            </td>

                            <!-- Column 5 -->
                            <td>
                                <a href="?action=delete&id=<?php echo $item['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this lead?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
