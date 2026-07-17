<?php
/**
 * WebFalx Admin Client Portal Moderator Console
 * Manage client credentials, projects, milestones tracking, invoices uploading, and private messaging replies
 */

$page_seo = [
    'title' => 'Client Portal Moderator | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$active_tab = sanitize_input($_GET['tab'] ?? 'clients');
$error_message = '';
$success_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle POST Actions (Save Client, Save Project, Save Milestone, Save Invoice, Send Chat, Moderate Revision)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        $action_type = sanitize_input($_POST['action_type'] ?? '');

        // A. Add / Edit Client
        if ($action_type === 'save_client') {
            $client_id = intval($_POST['client_id'] ?? 0);
            $name = sanitize_input($_POST['name'] ?? '');
            $company = sanitize_input($_POST['company'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $pwd = $_POST['password'] ?? '';
            $status = sanitize_input($_POST['status'] ?? 'active');

            if (empty($name) || empty($email)) {
                throw new Exception("Name and Email address are required fields.");
            }

            if ($client_id > 0) {
                // UPDATE
                if (!empty($pwd)) {
                    $hash = password_hash($pwd, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE clients SET name = :name, company = :comp, email = :email, phone = :phone, status = :status, password_hash = :hash WHERE id = :id");
                    $stmt->execute(['name' => $name, 'comp' => $company, 'email' => $email, 'phone' => $phone, 'status' => $status, 'hash' => $hash, 'id' => $client_id]);
                } else {
                    $stmt = $db->prepare("UPDATE clients SET name = :name, company = :comp, email = :email, phone = :phone, status = :status WHERE id = :id");
                    $stmt->execute(['name' => $name, 'comp' => $company, 'email' => $email, 'phone' => $phone, 'status' => $status, 'id' => $client_id]);
                }
                log_activity('Update Client Profile', 'Modified credentials for client: ' . $name);
                flash_message('portal_admin_flash', 'Client account details updated.', 'success');
            } else {
                // INSERT
                if (empty($pwd)) throw new Exception("Password is required for new accounts.");
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                
                $stmt = $db->prepare("INSERT INTO clients (name, company, email, phone, status, password_hash) VALUES (:name, :comp, :email, :phone, :status, :hash)");
                $stmt->execute(['name' => $name, 'comp' => $company, 'email' => $email, 'phone' => $phone, 'status' => $status, 'hash' => $hash]);
                
                log_activity('Create Client Profile', 'Created client workspace: ' . $name);
                flash_message('portal_admin_flash', 'New client workspace generated.', 'success');
            }
        }

        // B. Save Project
        elseif ($action_type === 'save_project') {
            $project_id = intval($_POST['project_id'] ?? 0);
            $client_target = intval($_POST['client_id'] ?? 0);
            $name = sanitize_input($_POST['name'] ?? '');
            $type = sanitize_input($_POST['project_type'] ?? '');
            $status = sanitize_input($_POST['status'] ?? 'Planning');
            $progress = intval($_POST['progress_percent'] ?? 0);
            $start = sanitize_input($_POST['start_date'] ?? '');
            $completion = sanitize_input($_POST['estimated_completion'] ?? '');
            $priority = sanitize_input($_POST['priority'] ?? 'Medium');
            $desc = sanitize_input($_POST['description'] ?? '');
            $team = sanitize_input($_POST['assigned_team'] ?? '');

            if ($client_target <= 0 || empty($name)) {
                throw new Exception("Please select a client and input a project name.");
            }

            if ($project_id > 0) {
                // UPDATE
                $stmt = $db->prepare("UPDATE projects SET client_id = :cid, name = :name, project_type = :type, status = :status, progress_percent = :progress, start_date = :start, estimated_completion = :comp, priority = :pri, description = :desc, assigned_team = :team WHERE id = :id");
                $stmt->execute([
                    'cid' => $client_target, 'name' => $name, 'type' => $type, 'status' => $status, 'progress' => $progress,
                    'start' => $start ?: null, 'comp' => $completion ?: null, 'pri' => $priority, 'desc' => $desc, 'team' => $team, 'id' => $project_id
                ]);
                log_activity('Update Project', 'Updated project parameters: ' . $name);
                flash_message('portal_admin_flash', 'Project updated.', 'success');
            } else {
                // INSERT
                $stmt = $db->prepare("INSERT INTO projects (client_id, name, project_type, status, progress_percent, start_date, estimated_completion, priority, description, assigned_team) 
                                      VALUES (:cid, :name, :type, :status, :progress, :start, :comp, :pri, :desc, :team)");
                $stmt->execute([
                    'cid' => $client_target, 'name' => $name, 'type' => $type, 'status' => $status, 'progress' => $progress,
                    'start' => $start ?: null, 'comp' => $completion ?: null, 'pri' => $priority, 'desc' => $desc, 'team' => $team
                ]);
                log_activity('Create Project', 'Launched project: ' . $name);
                flash_message('portal_admin_flash', 'Project launched successfully.', 'success');
            }
        }

        // C. Save Milestone
        elseif ($action_type === 'save_milestone') {
            $pid = intval($_POST['project_id'] ?? 0);
            $name = sanitize_input($_POST['name'] ?? '');
            $due = sanitize_input($_POST['due_date'] ?? '');
            $status = sanitize_input($_POST['status'] ?? 'Pending');

            if ($pid <= 0 || empty($name)) throw new Exception("Project target and Milestone name are required.");

            $stmt = $db->prepare("INSERT INTO project_milestones (project_id, name, due_date, status) VALUES (:pid, :name, :due, :status)");
            $stmt->execute(['pid' => $pid, 'name' => $name, 'due' => $due ?: null, 'status' => $status]);
            flash_message('portal_admin_flash', 'Milestone added.', 'success');
        }

        // D. Save Invoice
        elseif ($action_type === 'save_invoice') {
            $pid = intval($_POST['project_id'] ?? 0);
            $number = sanitize_input($_POST['invoice_number'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $due = sanitize_input($_POST['due_date'] ?? '');
            $status = sanitize_input($_POST['status'] ?? 'Pending');

            if ($pid <= 0 || empty($number) || $amount <= 0) throw new Exception("Invoice parameters are required.");

            $stmt = $db->prepare("INSERT INTO project_invoices (project_id, invoice_number, amount, due_date, status) VALUES (:pid, :num, :amt, :due, :status)");
            $stmt->execute(['pid' => $pid, 'num' => $number, 'amt' => $amount, 'due' => $due ?: null, 'status' => $status]);
            flash_message('portal_admin_flash', 'Invoice uploaded.', 'success');
        }

        // E. Send Message Chat (Admin to Client)
        elseif ($action_type === 'send_chat') {
            $pid = intval($_POST['project_id'] ?? 0);
            $msg = sanitize_input($_POST['message'] ?? '');

            if ($pid > 0 && !empty($msg)) {
                $stmt = $db->prepare("INSERT INTO project_messages (project_id, sender_type, sender_id, message, is_read) VALUES (:pid, 'admin', :aid, :msg, 0)");
                $stmt->execute(['pid' => $pid, 'aid' => $_SESSION['admin_id'], 'msg' => $msg]);
                flash_message('portal_admin_flash', 'Message dispatched to client portal.', 'success');
            }
        }

        // F. Moderate Revisions
        elseif ($action_type === 'moderate_revision') {
            $rev_id = intval($_POST['revision_id'] ?? 0);
            $status = sanitize_input($_POST['status'] ?? '');

            if ($rev_id > 0 && !empty($status)) {
                $db->prepare("UPDATE project_revisions SET status = :status WHERE id = :id")->execute(['status' => $status, 'id' => $rev_id]);
                flash_message('portal_admin_flash', 'Revision status moderated.', 'success');
            }
        }

        header('Location: ' . BASE_URL . 'admin/manage-clients.php?tab=' . $active_tab);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 2. Handle GET Deletes & Approvals Actions
if (isset($_GET['action'])) {
    try {
        $id = intval($_GET['id'] ?? 0);
        $action = sanitize_input($_GET['action']);

        if ($id > 0) {
            if ($action === 'delete_client' && $active_tab === 'clients') {
                $db->prepare("DELETE FROM clients WHERE id = :id")->execute(['id' => $id]);
                log_activity('Delete Client Account', 'Cleared ID ' . $id);
                flash_message('portal_admin_flash', 'Client account cleared.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-clients.php?tab=clients');
                exit;
            } elseif ($action === 'delete_proj' && $active_tab === 'clients') {
                $db->prepare("DELETE FROM projects WHERE id = :id")->execute(['id' => $id]);
                log_activity('Delete Project', 'Cleared Project ID ' . $id);
                flash_message('portal_admin_flash', 'Project cleared.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-clients.php?tab=clients');
                exit;
            } elseif ($action === 'complete_milestone' && $active_tab === 'milestones') {
                $db->prepare("UPDATE project_milestones SET status = 'Completed', completion_date = CURRENT_DATE WHERE id = :id")->execute(['id' => $id]);
                flash_message('portal_admin_flash', 'Milestone completed.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-clients.php?tab=milestones');
                exit;
            } elseif ($action === 'pay_invoice' && $active_tab === 'billing') {
                $db->prepare("UPDATE project_invoices SET status = 'Paid' WHERE id = :id")->execute(['id' => $id]);
                flash_message('portal_admin_flash', 'Invoice paid.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-clients.php?tab=billing');
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 3. Load lists based on active tab
$clients = [];
$projects = [];
$milestones = [];
$invoices = [];
$revisions = [];
$messages = [];

try {
    $clients = $db->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll();
    $projects = $db->query("SELECT p.*, c.name as client_name, c.company as client_company FROM projects p JOIN clients c ON p.client_id = c.id ORDER BY p.id DESC")->fetchAll();

    if ($active_tab === 'milestones') {
        $milestones = $db->query("SELECT m.*, p.name as project_name FROM project_milestones m JOIN projects p ON m.project_id = p.id ORDER BY m.id DESC")->fetchAll();
    } elseif ($active_tab === 'billing') {
        $invoices = $db->query("SELECT i.*, p.name as project_name FROM project_invoices i JOIN projects p ON i.project_id = p.id ORDER BY i.id DESC")->fetchAll();
    } elseif ($active_tab === 'chats') {
        $revisions = $db->query("SELECT r.*, p.name as project_name FROM project_revisions r JOIN projects p ON r.project_id = p.id ORDER BY r.id DESC")->fetchAll();
        $messages = $db->query("SELECT m.*, p.name as project_name FROM project_messages m JOIN projects p ON m.project_id = p.id ORDER BY m.id DESC LIMIT 30")->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed listing client portal catalogs: " . $e->getMessage());
}

$edit_client = null;
if (isset($_GET['edit_client_id']) && $active_tab === 'clients') {
    $ecid = intval($_GET['edit_client_id']);
    $edit_client = $db->query("SELECT * FROM clients WHERE id = $ecid")->fetch();
}

$edit_project = null;
if (isset($_GET['edit_proj_id']) && $active_tab === 'clients') {
    $epid = intval($_GET['edit_proj_id']);
    $edit_project = $db->query("SELECT * FROM projects WHERE id = $epid")->fetch();
}

$flash = flash_message('portal_admin_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Client Portal & Tracking Console</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Manage client details, project timeline progress percentages, invoice statuses, and communications chats.</p>

<div class="admin-tab-group" style="margin-bottom: var(--spacing-sm);">
    <a href="?tab=clients" class="admin-tab-btn <?php echo $active_tab === 'clients' ? 'active' : ''; ?>">Clients & Projects</a>
    <a href="?tab=milestones" class="admin-tab-btn <?php echo $active_tab === 'milestones' ? 'active' : ''; ?>">Milestones Planner</a>
    <a href="?tab=billing" class="admin-tab-btn <?php echo $active_tab === 'billing' ? 'active' : ''; ?>">Billing & Invoices</a>
    <a href="?tab=chats" class="admin-tab-btn <?php echo $active_tab === 'chats' ? 'active' : ''; ?>">Chats & Revisions Board</a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 1: CLIENTS & PROJECTS CRUD
     ============================================== -->
<?php if ($active_tab === 'clients'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
        
        <!-- Clients Profiles CRUD -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;"><?php echo $edit_client ? 'Edit Client' : 'Add Client Account'; ?></h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="save_client">
                <input type="hidden" name="client_id" value="<?php echo $edit_client ? $edit_client['id'] : '0'; ?>">

                <div class="form-group">
                    <label for="cl_name">Client Full Name</label>
                    <input type="text" name="name" id="cl_name" class="form-control" value="<?php echo esc_attr($edit_client['name'] ?? ''); ?>" required placeholder="e.g. Sarah Jenkins">
                </div>
                <div class="form-group">
                    <label for="cl_comp">Company Name</label>
                    <input type="text" name="company" id="cl_comp" class="form-control" value="<?php echo esc_attr($edit_client['company'] ?? ''); ?>" placeholder="Vanguard Wear">
                </div>
                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="cl_email">Email address</label>
                        <input type="email" name="email" id="cl_email" class="form-control" value="<?php echo esc_attr($edit_client['email'] ?? ''); ?>" required placeholder="client@company.com">
                    </div>
                    <div class="form-group">
                        <label for="cl_phone">Contact Phone</label>
                        <input type="text" name="phone" id="cl_phone" class="form-control" value="<?php echo esc_attr($edit_client['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="cl_pwd">Password <?php echo $edit_client ? '(Leave blank to keep)' : ''; ?></label>
                        <input type="password" name="password" id="cl_pwd" class="form-control" <?php echo $edit_client ? '' : 'required'; ?>>
                    </div>
                    <div class="form-group">
                        <label for="cl_status">Status</label>
                        <select name="status" id="cl_status" class="form-control" style="background: var(--color-bg-dark);">
                            <option value="active" <?php echo (isset($edit_client) && $edit_client['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo (isset($edit_client) && $edit_client['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Client</button>
            </form>

            <h5 style="color: #ffffff; margin-bottom: 6px;">Clients Registry</h5>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($c['name']); ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--color-text-secondary-dark);"><?php echo esc($c['company']); ?> (<?php echo esc($c['email']); ?>)</span>
                            </td>
                            <td style="font-size: 0.8rem;">
                                <a href="?edit_client_id=<?php echo $c['id']; ?>&tab=clients" class="action-link">Edit</a> |
                                <a href="?action=delete_client&id=<?php echo $c['id']; ?>&tab=clients" class="action-link action-delete" onclick="return confirm('Clear client account and projects?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Projects CRUD -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;"><?php echo $edit_project ? 'Edit Project Details' : 'Launch New Project'; ?></h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="save_project">
                <input type="hidden" name="project_id" value="<?php echo $edit_project ? $edit_project['id'] : '0'; ?>">

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="pr_client">Client Target</label>
                        <select name="client_id" id="pr_client" class="form-control" style="background: var(--color-bg-dark);" required>
                            <option value="">Select Client</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo (isset($edit_project) && $edit_project['client_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo esc($c['name']); ?> (<?php echo esc($c['company']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pr_name">Project Title</label>
                        <input type="text" name="name" id="pr_name" class="form-control" value="<?php echo esc_attr($edit_project['name'] ?? ''); ?>" required placeholder="Shopify Theme build">
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="pr_type">Project Type</label>
                        <input type="text" name="project_type" id="pr_type" class="form-control" value="<?php echo esc_attr($edit_project['project_type'] ?? ''); ?>" placeholder="Shopify, PHP, CRM">
                    </div>
                    <div class="form-group">
                        <label for="pr_progress">Progress Percentage (%)</label>
                        <input type="number" name="progress_percent" id="pr_progress" class="form-control" value="<?php echo esc_attr($edit_project['progress_percent'] ?? '0'); ?>" min="0" max="100">
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="pr_status">Progress Stage</label>
                        <select name="status" id="pr_status" class="form-control" style="background: var(--color-bg-dark);">
                            <option value="Planning" <?php echo (isset($edit_project) && $edit_project['status'] === 'Planning') ? 'selected' : ''; ?>>Planning</option>
                            <option value="UI/UX Design" <?php echo (isset($edit_project) && $edit_project['status'] === 'UI/UX Design') ? 'selected' : ''; ?>>UI/UX Design</option>
                            <option value="Development" <?php echo (isset($edit_project) && $edit_project['status'] === 'Development') ? 'selected' : ''; ?>>Development</option>
                            <option value="Testing" <?php echo (isset($edit_project) && $edit_project['status'] === 'Testing') ? 'selected' : ''; ?>>Testing</option>
                            <option value="Review" <?php echo (isset($edit_project) && $edit_project['status'] === 'Review') ? 'selected' : ''; ?>>Review</option>
                            <option value="Client Approval" <?php echo (isset($edit_project) && $edit_project['status'] === 'Client Approval') ? 'selected' : ''; ?>>Client Approval</option>
                            <option value="Deployment" <?php echo (isset($edit_project) && $edit_project['status'] === 'Deployment') ? 'selected' : ''; ?>>Deployment</option>
                            <option value="Completed" <?php echo (isset($edit_project) && $edit_project['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pr_pri">Priority</label>
                        <select name="priority" id="pr_pri" class="form-control" style="background: var(--color-bg-dark);">
                            <option value="Low" <?php echo (isset($edit_project) && $edit_project['priority'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo (!isset($edit_project) || $edit_project['priority'] === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo (isset($edit_project) && $edit_project['priority'] === 'High') ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="pr_start">Start Date</label>
                        <input type="date" name="start_date" id="pr_start" class="form-control" value="<?php echo esc_attr($edit_project['start_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="pr_comp">Estimated Completion</label>
                        <input type="date" name="estimated_completion" id="pr_comp" class="form-control" value="<?php echo esc_attr($edit_project['estimated_completion'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="pr_team">Assigned Staff team</label>
                    <input type="text" name="assigned_team" id="pr_team" class="form-control" value="<?php echo esc_attr($edit_project['assigned_team'] ?? ''); ?>" placeholder="Alex Rivera, Marcus Vance">
                </div>

                <div class="form-group">
                    <label for="pr_desc">Scope Description</label>
                    <textarea name="description" id="pr_desc" rows="3" class="form-control"><?php echo esc($edit_project['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Project</button>
            </form>

            <h5 style="color: #ffffff; margin-bottom: 6px;">Active Projects Pipeline</h5>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($p['name']); ?></strong><br>
                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Client: <?php echo esc($p['client_name']); ?> (<?php echo esc($p['status']); ?>)</small>
                            </td>
                            <td><?php echo $p['progress_percent']; ?>%</td>
                            <td style="font-size: 0.8rem;">
                                <a href="?edit_proj_id=<?php echo $p['id']; ?>&tab=clients" class="action-link">Edit</a> |
                                <a href="?action=delete_proj&id=<?php echo $p['id']; ?>&tab=clients" class="action-link action-delete" onclick="return confirm('Delete project?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 2: PROJECT MILESTONES PLANNER
     ============================================== -->
<?php if ($active_tab === 'milestones'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
        <!-- Add milestone -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Add Milestone</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="save_milestone">

                <div class="form-group">
                    <label for="ms_proj">Project Target</label>
                    <select name="project_id" id="ms_proj" class="form-control" style="background: var(--color-bg-dark);" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo esc($p['name']); ?> (<?php echo esc($p['client_name']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ms_name">Milestone Title</label>
                    <input type="text" name="name" id="ms_name" class="form-control" required placeholder="e.g. Deliver homepage beta">
                </div>
                <div class="form-group">
                    <label for="ms_due">Due Date</label>
                    <input type="date" name="due_date" id="ms_due" class="form-control">
                </div>
                <div class="form-group">
                    <label for="ms_status">Status</label>
                    <select name="status" id="ms_status" class="form-control" style="background: var(--color-bg-dark);">
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Milestone</button>
            </form>
        </div>

        <!-- Milestones list -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Milestones Schedule</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Milestone</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($milestones as $ms): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($ms['name']); ?></strong><br>
                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Due: <?php echo date('M d, Y', strtotime($ms['due_date'])); ?></small>
                            </td>
                            <td><?php echo esc($ms['project_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $ms['status'] === 'Completed' ? 'status-won' : 'status-new'; ?>" style="font-size: 0.65rem;">
                                    <?php echo esc($ms['status']); ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem;">
                                <?php if ($ms['status'] !== 'Completed'): ?>
                                    <a href="?action=complete_milestone&id=<?php echo $ms['id']; ?>&tab=milestones" style="color: var(--color-success);" class="action-link">Complete</a>
                                <?php else: ?>
                                    <span style="color: var(--color-text-muted-dark);">✓ Logged</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 3: BILLING & INVOICES
     ============================================== -->
<?php if ($active_tab === 'billing'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
        <!-- Upload Invoice -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Record Invoice</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="save_invoice">

                <div class="form-group">
                    <label for="inv_proj">Project Target</label>
                    <select name="project_id" id="inv_proj" class="form-control" style="background: var(--color-bg-dark);" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo esc($p['name']); ?> (<?php echo esc($p['client_name']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="inv_num">Invoice Number</label>
                    <input type="text" name="invoice_number" id="inv_num" class="form-control" required placeholder="INV-2026-XXXX">
                </div>
                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="inv_amt">Billing Amount ($)</label>
                        <input type="number" step="0.01" name="amount" id="inv_amt" class="form-control" required value="0.00">
                    </div>
                    <div class="form-group">
                        <label for="inv_due">Due Date</label>
                        <input type="date" name="due_date" id="inv_due" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inv_status">Payment Status</label>
                    <select name="status" id="inv_status" class="form-control" style="background: var(--color-bg-dark);">
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Record Invoice</button>
            </form>
        </div>

        <!-- Invoices List table -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Billing Ledger</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Project</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><strong><?php echo esc($inv['invoice_number']); ?></strong></td>
                            <td><?php echo esc($inv['project_name']); ?></td>
                            <td>$<?php echo number_format($inv['amount'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo $inv['status'] === 'Paid' ? 'status-won' : 'status-new'; ?>" style="font-size: 0.65rem;">
                                    <?php echo esc($inv['status']); ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem;">
                                <?php if ($inv['status'] !== 'Paid'): ?>
                                    <a href="?action=pay_invoice&id=<?php echo $inv['id']; ?>&tab=billing" style="color: var(--color-success);" class="action-link">Mark Paid</a>
                                <?php else: ?>
                                    <span style="color: var(--color-text-muted-dark);">✓ Paid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==============================================
     SUB-TAB 4: CHATS & REVISIONS BOARD
     ============================================== -->
<?php if ($active_tab === 'chats'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
        
        <!-- Private Chats reply panel -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 12px;">Admin-to-Client Chat Canvas</h4>
            
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="send_chat">

                <div class="form-group">
                    <label for="ch_proj">Select Client Project Thread</label>
                    <select name="project_id" id="ch_proj" class="form-control" style="background: var(--color-bg-dark);" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo esc($p['name']); ?> (<?php echo esc($p['client_name']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ch_msg">Message body</label>
                    <textarea name="message" id="ch_msg" rows="3" class="form-control" placeholder="Type reply updates..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="min-height: auto; padding: 8px;">Send Chat Message</button>
            </form>

            <h5 style="color: #ffffff; margin-bottom: 6px;">Recent chat exchanges</h5>
            <div style="max-height: 250px; overflow-y: auto; background: rgba(0,0,0,0.1); border-radius: var(--radius-sm); padding: 8px; display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($messages as $msg): ?>
                    <div style="font-size: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                        <span style="font-weight: 700; color: <?php echo $msg['sender_type'] === 'admin' ? 'var(--color-accent)' : 'var(--color-secondary)'; ?>;">
                            <?php echo $msg['sender_type'] === 'admin' ? 'You' : 'Client'; ?> (<?php echo esc($msg['project_name']); ?>):
                        </span>
                        <p style="color: var(--color-text-secondary-dark); margin: 2px 0;"><?php echo esc($msg['message']); ?></p>
                        <small style="color: var(--color-text-muted-dark); font-size: 0.65rem;"><?php echo date('H:i M d', strtotime($msg['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Client Revisions Moderator -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 1rem;">Revisions Moderator Pipeline</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Revision Title / Project</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($revisions)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--color-text-muted-dark);">No revision requests filed.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($revisions as $rev): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc($rev['title']); ?></strong><br>
                                    <span style="font-size: 0.72rem; color: var(--color-text-secondary-dark);"><?php echo esc($rev['project_name']); ?> (<?php echo esc($rev['status']); ?>)</span>
                                </td>
                                <td><?php echo esc($rev['priority']); ?></td>
                                <td>
                                    <form action="" method="POST" style="display: inline-flex; gap: 4px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                                        <input type="hidden" name="action_type" value="moderate_revision">
                                        <input type="hidden" name="revision_id" value="<?php echo $rev['id']; ?>">
                                        
                                        <select name="status" onchange="this.form.submit()" class="form-control" style="padding: 2px 4px; font-size: 0.75rem; background: var(--color-bg-dark); min-height: auto; cursor: pointer;">
                                            <option value="">Update Status</option>
                                            <option value="Open">Open</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Complete">Complete</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                    </form>
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
