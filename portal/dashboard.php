<?php
/**
 * WebFalx Client Portal Workspace Dashboard
 * Dynamic project tracking, progress timelines, client approvals, file manager, message board, proposals, and profile settings
 */

require_once __DIR__ . '/../includes/functions.php';

// Portal Auth check
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    header('Location: ' . BASE_URL . 'portal/login.php');
    exit;
}

$client_id = $_SESSION['client_id'];
$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Fetch Client Profile details
try {
    $client = $db->query("SELECT * FROM clients WHERE id = $client_id")->fetch();
    if (!$client) {
        header('Location: ' . BASE_URL . 'portal/logout.php');
        exit;
    }
} catch (PDOException $e) {
    die("Failed loading client profile.");
}

// 2. Fetch Projects associated with this client
$projects = [];
try {
    $stmt = $db->prepare("SELECT * FROM projects WHERE client_id = :cid ORDER BY id DESC");
    $stmt->execute(['cid' => $client_id]);
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed listing projects: " . $e->getMessage());
}

// Select active project (defaults to first project if exists)
$selected_project_id = intval($_GET['project_id'] ?? ($projects[0]['id'] ?? 0));
$project = null;
foreach ($projects as $p) {
    if ($p['id'] == $selected_project_id) {
        $project = $p;
        break;
    }
}

// 3. Handle POST Actions (Approvals, Revisions, Messages, Uploads, Proposals)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    try {
        require_csrf_token();
        $action_type = sanitize_input($_POST['action_type']);

        // A. Handle Approvals Action
        if ($action_type === 'respond_approval' && $project) {
            $approval_id = intval($_POST['approval_id'] ?? 0);
            $response = sanitize_input($_POST['response'] ?? '');
            $comments = sanitize_input($_POST['comments'] ?? '');

            if ($approval_id > 0 && in_array($response, ['Approved', 'Changes Requested'])) {
                $stmt = $db->prepare("UPDATE project_approvals SET status = :status, comments = :comments WHERE id = :id AND project_id = :pid");
                $stmt->execute(['status' => $response, 'comments' => $comments, 'id' => $approval_id, 'pid' => $project['id']]);
                
                log_activity('Client Approval Response', 'Client responded to Approval ID ' . $approval_id . ' as ' . $response);
                flash_message('portal_flash', 'Approval response submitted successfully.', 'success');
            }
        }

        // B. Handle Revision Request Submit
        elseif ($action_type === 'submit_revision' && $project) {
            $title = sanitize_input($_POST['title'] ?? '');
            $desc = sanitize_input($_POST['description'] ?? '');
            $priority = sanitize_input($_POST['priority'] ?? 'Medium');
            $screenshot = '';

            if (empty($title) || empty($desc)) {
                throw new Exception("Revision Title and Description details are required.");
            }

            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
                $uploadDir = __DIR__ . '/../assets/uploads/revisions/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $cleanName = 'rev_' . time() . '_' . rand(100,999) . '.' . $ext;
                if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $uploadDir . $cleanName)) {
                    $screenshot = 'assets/uploads/revisions/' . $cleanName;
                }
            }

            $stmt = $db->prepare("INSERT INTO project_revisions (project_id, title, description, priority, screenshot_path, status) VALUES (:pid, :title, :desc, :pri, :scr, 'Open')");
            $stmt->execute([
                'pid' => $project['id'],
                'title' => $title,
                'desc' => $desc,
                'pri' => $priority,
                'scr' => $screenshot ?: null
            ]);

            flash_message('portal_flash', 'Revision request submitted to engineering desk.', 'success');
        }

        // C. Send Private Message Chat
        elseif ($action_type === 'send_chat' && $project) {
            $msg = sanitize_input($_POST['message'] ?? '');
            $attachment = '';

            if (empty($msg) && !isset($_FILES['chat_file'])) {
                throw new Exception("Cannot post blank messages.");
            }

            if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['chat_file']['name'], PATHINFO_EXTENSION);
                $uploadDir = __DIR__ . '/../assets/uploads/chat/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $cleanName = 'chat_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $uploadDir . $cleanName)) {
                    $attachment = 'assets/uploads/chat/' . $cleanName;
                }
            }

            $stmt = $db->prepare("INSERT INTO project_messages (project_id, sender_type, sender_id, message, file_path, is_read) VALUES (:pid, 'client', :cid, :msg, :file, 0)");
            $stmt->execute([
                'pid' => $project['id'],
                'cid' => $client_id,
                'msg' => $msg,
                'file' => $attachment ?: null
            ]);

            flash_message('portal_flash', 'Message dispatched.', 'success');
        }

        // D. File Manager Upload
        elseif ($action_type === 'upload_file' && $project) {
            if (isset($_FILES['shared_file']) && $_FILES['shared_file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['shared_file']['name'], PATHINFO_EXTENSION);
                $uploadDir = __DIR__ . '/../assets/uploads/shared/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $cleanName = 'shared_' . time() . '_' . rand(100,999) . '.' . $ext;
                
                if (move_uploaded_file($_FILES['shared_file']['tmp_name'], $uploadDir . $cleanName)) {
                    $relative = 'assets/uploads/shared/' . $cleanName;
                    
                    $stmt = $db->prepare("INSERT INTO project_files (project_id, filename, file_path, file_size) VALUES (:pid, :name, :path, :size)");
                    $stmt->execute([
                        'pid' => $project['id'],
                        'name' => $_FILES['shared_file']['name'],
                        'path' => $relative,
                        'size' => $_FILES['shared_file']['size']
                    ]);
                    flash_message('portal_flash', 'File uploaded to project library.', 'success');
                } else {
                    throw new Exception("Save failure.");
                }
            }
        }

        // E. Profile updates
        elseif ($action_type === 'update_profile') {
            $name = sanitize_input($_POST['name'] ?? '');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $pwd = $_POST['password'] ?? '';

            if (empty($name)) throw new Exception("Name cannot be empty.");

            if (!empty($pwd)) {
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE clients SET name = :name, phone = :phone, password_hash = :hash WHERE id = :id");
                $stmt->execute(['name' => $name, 'phone' => $phone, 'hash' => $hash, 'id' => $client_id]);
            } else {
                $stmt = $db->prepare("UPDATE clients SET name = :name, phone = :phone WHERE id = :id");
                $stmt->execute(['name' => $name, 'phone' => $phone, 'id' => $client_id]);
            }
            
            $_SESSION['client_name'] = $name;
            flash_message('portal_flash', 'Profile modifications saved.', 'success');
        }

        // F. Proposals Acceptance
        elseif ($action_type === 'respond_proposal') {
            $proposal_id = intval($_POST['proposal_id'] ?? 0);
            $response = sanitize_input($_POST['response'] ?? '');
            if ($proposal_id > 0 && in_array($response, ['Accepted', 'Changes Requested'])) {
                $db->prepare("UPDATE proposals SET status = :status WHERE id = :id")->execute(['status' => $response, 'id' => $proposal_id]);
                log_activity('Client Proposal Response', 'Client set Proposal ID ' . $proposal_id . ' to ' . $response);
                flash_message('portal_flash', 'Proposal status updated to ' . $response, 'success');
            }
        }

        header('Location: ' . BASE_URL . 'portal/dashboard.php?project_id=' . $selected_project_id);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 4. Load Supporting lists for selected active project
$milestones = [];
$approvals = [];
$revisions = [];
$messages = [];
$meetings = [];
$invoices = [];
$files = [];
$proposals = [];
$quotations = [];

if ($project) {
    try {
        $pid = $project['id'];
        $milestones = $db->query("SELECT * FROM project_milestones WHERE project_id = $pid ORDER BY id ASC")->fetchAll();
        $approvals = $db->query("SELECT * FROM project_approvals WHERE project_id = $pid ORDER BY id DESC")->fetchAll();
        $revisions = $db->query("SELECT * FROM project_revisions WHERE project_id = $pid ORDER BY id DESC")->fetchAll();
        $messages = $db->query("SELECT * FROM project_messages WHERE project_id = $pid ORDER BY id ASC")->fetchAll();
        $meetings = $db->query("SELECT * FROM project_meetings WHERE project_id = $pid ORDER BY id DESC")->fetchAll();
        $invoices = $db->query("SELECT * FROM project_invoices WHERE project_id = $pid ORDER BY id DESC")->fetchAll();
        $files = $db->query("SELECT * FROM project_files WHERE project_id = $pid ORDER BY id DESC")->fetchAll();
        
        // Proposals and quotations associated with client
        $proposals = $db->query("SELECT * FROM proposals WHERE client_id = $client_id ORDER BY id DESC")->fetchAll();
        $quotations = $db->query("SELECT * FROM quotations WHERE client_id = $client_id ORDER BY id DESC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed listing project logs: " . $e->getMessage());
    }
}

$flash = flash_message('portal_flash');
if ($flash) {
    $success_message = $flash['message'];
}

$page_seo = [
    'title' => 'Client Workspace | WebFalx'
];
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Client Workspace Container -->
<section class="section client-workspace" style="padding: 40px 0 var(--spacing-lg) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal">
        
        <!-- Header Profile Coordinates -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: var(--spacing-sm); border-bottom: var(--border-glass); padding-bottom: 12px;">
            <div>
                <span style="font-size: 0.75rem; color: var(--color-accent); font-weight: 700; text-transform: uppercase;">Portal Workspace</span>
                <h2 style="color: #ffffff; font-size: 1.85rem; margin-top: 2px;">Welcome, <?php echo esc($client['name']); ?></h2>
                <small style="color: var(--color-text-muted-dark);"><?php echo esc($client['company']); ?></small>
            </div>
            
            <div style="display: flex; gap: 8px;">
                <!-- Project Selector Dropdown -->
                <?php if (count($projects) > 1): ?>
                    <form action="" method="GET" style="display: inline-flex; align-items: center;">
                        <select name="project_id" onchange="this.form.submit()" class="form-control" style="background: var(--color-bg-dark); cursor: pointer; padding: 6px 12px; font-size: 0.85rem;">
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $selected_project_id ? 'selected' : ''; ?>><?php echo esc($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary" style="min-height: auto; padding: 6px 12px; font-size: 0.85rem; border-color: rgba(239,68,68,0.2); color: #ef4444;">Log Out</a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success fade-in" style="margin-bottom: var(--spacing-xs);"><?php echo esc($success_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in" style="margin-bottom: var(--spacing-xs);"><?php echo esc($error_message); ?></div>
        <?php endif; ?>

        <?php if (!$project): ?>
            <div class="glass-card" style="text-align: center; padding: var(--spacing-md) 0;">
                <p style="color: var(--color-text-muted-dark);">No active projects assigned to your account yet. We will notify you when setup finishes.</p>
            </div>
        <?php else: ?>

            <!-- SaaS Interactive Tabs System -->
            <div class="admin-tab-group" style="margin-bottom: var(--spacing-sm);">
                <button onclick="switchPortalTab('timeline')" class="portal-tab-btn active" id="tab-btn-timeline">Progress & Timeline</button>
                <button onclick="switchPortalTab('approvals')" class="portal-tab-btn" id="tab-btn-approvals">Approvals & Revisions</button>
                <button onclick="switchPortalTab('proposals')" class="portal-tab-btn" id="tab-btn-proposals">Proposals & Quotes</button>
                <button onclick="switchPortalTab('files')" class="portal-tab-btn" id="tab-btn-files">File Library</button>
                <button onclick="switchPortalTab('messages')" class="portal-tab-btn" id="tab-btn-messages">Private Messaging</button>
                <button onclick="switchPortalTab('billing')" class="portal-tab-btn" id="tab-btn-billing">Invoices & Billings</button>
                <button onclick="switchPortalTab('settings')" class="portal-tab-btn" id="tab-btn-settings">Profile Settings</button>
            </div>

            <!-- =============================================
                 TAB 1: PROGRESS & TIMELINE
                 ============================================= -->
            <div class="portal-tab-content active" id="portal-tab-timeline">
                <div class="grid grid-2" style="grid-template-columns: 1.5fr 1fr; gap: var(--spacing-md); align-items: start;">
                    
                    <!-- Progress Card -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: 8px;">Project Progress (<?php echo $project['progress_percent']; ?>%)</h4>
                        
                        <!-- Progress Bar layout -->
                        <div style="background: rgba(255,255,255,0.03); border-radius: var(--radius-full); height: 16px; width: 100%; overflow: hidden; margin-bottom: var(--spacing-sm);">
                            <div style="background: linear-gradient(90deg, var(--color-secondary), var(--color-accent)); height: 100%; width: <?php echo $project['progress_percent']; ?>%; transition: width 0.6s ease;"></div>
                        </div>

                        <div class="grid grid-3" style="gap: 10px; font-size: 0.82rem; color: var(--color-text-secondary-dark); margin-bottom: var(--spacing-sm);">
                            <div><strong>Type:</strong> <?php echo esc($project['project_type']); ?></div>
                            <div><strong>Priority:</strong> <?php echo esc($project['priority']); ?></div>
                            <div><strong>Completion:</strong> <?php echo date('M d, Y', strtotime($project['estimated_completion'])); ?></div>
                        </div>

                        <!-- Progress Timeline stages -->
                        <h5 style="color: #ffffff; margin-bottom: 8px;">Development Timeline</h5>
                        <?php 
                        $stages = ['Planning', 'UI/UX Design', 'Development', 'Testing', 'Review', 'Client Approval', 'Deployment', 'Completed'];
                        $current_idx = array_search($project['status'], $stages);
                        if ($current_idx === false) $current_idx = 0;
                        ?>
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                            <?php foreach ($stages as $idx => $stage): 
                                $completed = $idx < $current_idx;
                                $active = $idx == $current_idx;
                            ?>
                                <div style="display: flex; align-items: center; gap: 10px; opacity: <?php echo ($completed || $active) ? '1' : '0.4'; ?>;">
                                    <div style="width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700;
                                        background: <?php echo $completed ? 'var(--color-success)' : ($active ? 'var(--color-accent)' : 'rgba(255,255,255,0.05)'); ?>; color: #fff;">
                                        <?php echo $completed ? '✓' : ($idx + 1); ?>
                                    </div>
                                    <span style="font-size: 0.85rem; color: #fff; font-weight: <?php echo $active ? '700' : '400'; ?>;">
                                        <?php echo $stage; ?> <?php echo $active ? '<small style="color: var(--color-accent); font-weight: 600;">(Current Stage)</small>' : ''; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Milestones Checklist -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: var(--spacing-xs);">Project Milestones</h4>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Milestone</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($milestones)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--color-text-muted-dark);">No milestones cataloged.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($milestones as $ms): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc($ms['name']); ?></strong><br>
                                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;"><?php echo esc($ms['description']); ?></small>
                                            </td>
                                            <td style="font-size: 0.8rem;"><?php echo date('M d, Y', strtotime($ms['due_date'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $ms['status'] === 'Completed' ? 'status-won' : 'status-new'; ?>" style="font-size: 0.65rem;">
                                                    <?php echo esc($ms['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Upcoming meetings widgets -->
                        <?php if (!empty($meetings)): ?>
                            <h4 style="color: var(--color-accent); margin-top: 15px; margin-bottom: 8px;">Upcoming Sync Syncs</h4>
                            <?php foreach ($meetings as $meet): ?>
                                <div style="background: rgba(255,255,255,0.02); border: var(--border-glass); padding: 8px; border-radius: var(--radius-sm); margin-bottom: 6px;">
                                    <div style="font-size: 0.85rem; color: #fff; font-weight: 600;">Meeting on: <?php echo date('M d, Y', strtotime($meet['meeting_date'])); ?> @ <?php echo date('h:i A', strtotime($meet['meeting_time'])); ?></div>
                                    <span style="font-size: 0.75rem; color: var(--color-accent); font-weight: 700;"><?php echo esc($meet['platform']); ?></span>
                                    <p style="font-size: 0.78rem; color: var(--color-text-secondary-dark); margin: 4px 0;"><?php echo esc($meet['notes']); ?></p>
                                    <a href="<?php echo esc_url($meet['meeting_link']); ?>" target="_blank" rel="noopener" class="btn btn-secondary" style="font-size: 0.72rem; min-height: auto; padding: 4px 8px; width: 100%; text-align: center;">Join Meeting</a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- =============================================
                 TAB 2: APPROVALS & REVISIONS
                 ============================================= -->
            <div class="portal-tab-content" id="portal-tab-approvals">
                <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
                    
                    <!-- Approvals List Queue -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: 12px;">Work Approvals Requests</h4>
                        
                        <?php if (empty($approvals)): ?>
                            <p style="color: var(--color-text-muted-dark); font-size: 0.88rem;">No approval requests issued for this project.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($approvals as $app): ?>
                                    <div style="background: rgba(255,255,255,0.01); border: var(--border-glass); padding: 10px; border-radius: var(--radius-sm);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 4px; margin-bottom: 6px;">
                                            <strong style="color: #fff;"><?php echo esc($app['approval_item']); ?></strong>
                                            <span class="status-badge <?php echo $app['status'] === 'Approved' ? 'status-won' : ($app['status'] === 'Pending' ? 'status-new' : 'status-lost'); ?>" style="font-size: 0.65rem;">
                                                <?php echo esc($app['status']); ?>
                                            </span>
                                        </div>
                                        <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark); margin-bottom: 8px;"><?php echo esc($app['description']); ?></p>

                                        <?php if ($app['comments']): ?>
                                            <div style="background: rgba(255,255,255,0.02); padding: 6px; border-radius: var(--radius-sm); font-size: 0.78rem; font-style: italic; color: var(--color-text-muted-dark); margin-bottom: 8px;">
                                                Comments: "<?php echo esc($app['comments']); ?>"
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($app['status'] === 'Pending'): ?>
                                            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 6px; margin-top: 10px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                                                <input type="hidden" name="action_type" value="respond_approval">
                                                <input type="hidden" name="approval_id" value="<?php echo $app['id']; ?>">
                                                
                                                <input type="text" name="comments" class="form-control" placeholder="Comments or revision details..." style="padding: 4px 8px; font-size: 0.8rem; min-height: auto;">
                                                <div style="display: flex; gap: 6px;">
                                                    <button type="submit" name="response" value="Approved" class="btn btn-primary" style="flex: 1; min-height: auto; padding: 6px; font-size: 0.78rem;">Approve Item</button>
                                                    <button type="submit" name="response" value="Changes Requested" class="btn btn-secondary" style="flex: 1; min-height: auto; padding: 6px; font-size: 0.78rem; color: var(--color-warning); border-color: rgba(245,158,11,0.2);">Request Adjustments</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Revision form and table -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: 8px;">Submit Revision Request</h4>
                        <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                            <input type="hidden" name="action_type" value="submit_revision">

                            <div class="form-group">
                                <label for="rev_title" style="font-size: 0.72rem;">Revision Title</label>
                                <input type="text" name="title" id="rev_title" class="form-control" required placeholder="e.g. Change hero heading text font" style="padding: 6px 12px; min-height: auto; font-size: 0.85rem;">
                            </div>
                            <div class="form-group">
                                <label for="rev_desc" style="font-size: 0.72rem;">Description Details</label>
                                <textarea name="description" id="rev_desc" rows="3" class="form-control" required placeholder="Outline exactly what adjustments are required..." style="padding: 6px; font-size: 0.85rem;"></textarea>
                            </div>
                            <div class="grid grid-2" style="gap: 10px;">
                                <div class="form-group">
                                    <label for="rev_pri" style="font-size: 0.72rem;">Priority</label>
                                    <select name="priority" id="rev_pri" class="form-control" style="background: var(--color-bg-dark); font-size: 0.85rem; padding: 6px; min-height: auto;">
                                        <option value="Low">Low</option>
                                        <option value="Medium" selected>Medium</option>
                                        <option value="High">High</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="rev_file" style="font-size: 0.72rem;">Screenshot Upload</label>
                                    <input type="file" name="screenshot" id="rev_file" style="font-size: 0.78rem;">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; min-height: auto; padding: 8px;">Post Revision Request</button>
                        </form>

                        <h5 style="color: #ffffff; margin-bottom: 6px;">Requested Revisions</h5>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Revision Details</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($revisions)): ?>
                                    <tr>
                                        <td colspan="2" style="text-align: center; color: var(--color-text-muted-dark); font-size: 0.78rem;">No revisions submitted.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($revisions as $rev): ?>
                                        <tr>
                                            <td>
                                                <strong style="font-size: 0.82rem; color: #fff;"><?php echo esc($rev['title']); ?></strong><br>
                                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;"><?php echo esc($rev['description']); ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $rev['status'] === 'Complete' ? 'status-won' : 'status-new'; ?>" style="font-size: 0.65rem;">
                                                    <?php echo esc($rev['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 TAB 3: PROPOSALS & ESTIMATES
                 ============================================= -->
            <div class="portal-tab-content" id="portal-tab-proposals">
                <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
                    
                    <!-- Proposals List -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: 12px;">Active Project Proposals</h4>
                        <?php if (empty($proposals)): ?>
                            <p style="color: var(--color-text-muted-dark); font-size: 0.85rem;">No active proposals loaded for your account.</p>
                        <?php else: ?>
                            <?php foreach ($proposals as $prop): ?>
                                <div style="background: rgba(255,255,255,0.01); border: var(--border-glass); padding: 10px; border-radius: var(--radius-sm); margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 4px; margin-bottom: 6px;">
                                        <strong style="color: #fff;"><?php echo esc($prop['title']); ?></strong>
                                        <span class="status-badge <?php echo $prop['status'] === 'Accepted' ? 'status-won' : 'status-new'; ?>" style="font-size: 0.65rem;">
                                            <?php echo esc($prop['status']); ?>
                                        </span>
                                    </div>
                                    <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark); margin-bottom: 8px;"><?php echo esc($prop['scope_of_work']); ?></p>
                                    <div style="font-size: 0.8rem; color: var(--color-text-muted-dark); margin-bottom: 10px;">
                                        <strong>Investment Total:</strong> $<?php echo number_format($prop['investment'], 2); ?> | <strong>Timeline:</strong> <?php echo esc($prop['timeline']); ?>
                                    </div>
                                    
                                    <?php if ($prop['status'] === 'Pending'): ?>
                                        <form action="" method="POST" style="display: flex; gap: 8px;">
                                            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                                            <input type="hidden" name="action_type" value="respond_proposal">
                                            <input type="hidden" name="proposal_id" value="<?php echo $prop['id']; ?>">
                                            
                                            <button type="submit" name="response" value="Accepted" class="btn btn-primary" style="flex: 1; min-height: auto; padding: 6px; font-size: 0.78rem;">Accept Proposal</button>
                                            <button type="submit" name="response" value="Changes Requested" class="btn btn-secondary" style="flex: 1; min-height: auto; padding: 6px; font-size: 0.78rem; color: var(--color-warning); border-color: rgba(245,158,11,0.2);">Request Changes</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Calculator Quotations History -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: 12px;">Quotations Cost Estimates</h4>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Quote Number</th>
                                    <th>Project Details</th>
                                    <th>Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($quotations)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--color-text-muted-dark); font-size: 0.85rem;">No quotations generated yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($quotations as $q): ?>
                                        <tr>
                                            <td><strong><?php echo esc($q['quote_number']); ?></strong></td>
                                            <td>
                                                <span style="font-size: 0.82rem; color: #fff;"><?php echo esc($q['project_type']); ?></span><br>
                                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;"><?php echo $q['pages']; ?> Pages</small>
                                            </td>
                                            <td><strong>$<?php echo number_format($q['calculated_total'], 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 TAB 4: FILE LIBRARY
                 ============================================= -->
            <div class="portal-tab-content" id="portal-tab-files">
                <div class="grid grid-2" style="grid-template-columns: 1fr 2fr; gap: var(--spacing-md); align-items: start;">
                    
                    <!-- File Upload Center -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: 8px;">Upload Assets</h4>
                        <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                            <input type="hidden" name="action_type" value="upload_file">
                            
                            <div class="form-group" style="border: 2px dashed rgba(255,255,255,0.08); padding: 1.25rem; text-align: center; border-radius: var(--radius-sm);">
                                <label for="shr_file" style="cursor: pointer; display: block; font-size: 0.82rem;">Select File</label>
                                <input type="file" name="shared_file" id="shr_file" required style="margin-top: 10px; font-size: 0.78rem; width: 100%;">
                                <small style="color: var(--color-text-muted-dark); display: block; margin-top: 6px; font-size: 0.7rem;">Share logo files, copy grids, or credentials documents.</small>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; min-height: auto; padding: 10px;">Upload to Project</button>
                        </form>
                    </div>

                    <!-- Shared Files list -->
                    <div class="glass-card" style="padding: 1.25rem;">
                        <h4 style="color: var(--color-accent); margin-bottom: 12px;">Project Download Library</h4>
                        
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>File Details</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($files)): ?>
                                    <tr>
                                        <td colspan="2" style="text-align: center; color: var(--color-text-muted-dark);">No shared files available in library database.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($files as $fl): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: #fff; font-size: 0.88rem;"><?php echo esc($fl['filename']); ?></strong><br>
                                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Uploaded: <?php echo date('M d, Y H:i', strtotime($fl['uploaded_at'])); ?></small>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL . $fl['file_path']; ?>" download class="btn btn-secondary" style="font-size: 0.75rem; min-height: auto; padding: 4px 8px; text-align: center; width: 100%;">Download File</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 TAB 5: PRIVATE MESSAGING
                 ============================================= -->
            <div class="portal-tab-content" id="portal-tab-messages">
                <div class="glass-card" style="padding: 1.25rem; max-width: 800px; margin: 0 auto;">
                    <h4 style="color: var(--color-accent); margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 4px;">Private Project Communications</h4>
                    
                    <!-- Chat messages scroll canvas -->
                    <div class="chat-message-canvas" style="height: 350px; overflow-y: auto; background: rgba(0,0,0,0.1); border: var(--border-glass); border-radius: var(--radius-sm); padding: 12px; display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                        <?php if (empty($messages)): ?>
                            <p style="color: var(--color-text-muted-dark); font-size: 0.88rem; text-align: center; margin-top: 150px;">No messages exchanged yet. Send a message to start communicating with WebFalx engineering.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): 
                                $isClient = $msg['sender_type'] === 'client';
                            ?>
                                <div style="display: flex; flex-direction: column; align-items: <?php echo $isClient ? 'flex-end' : 'flex-start'; ?>;">
                                    <div style="max-width: 75%; background: <?php echo $isClient ? 'var(--color-primary)' : 'rgba(255,255,255,0.04)'; ?>; color: #fff; padding: 8px 12px; border-radius: var(--radius-sm); font-size: 0.85rem;">
                                        <div style="font-size: 0.72rem; color: rgba(255,255,255,0.4); margin-bottom: 2px; font-weight: 600;">
                                            <?php echo $isClient ? 'You' : 'Alex (WebFalx Eng)'; ?>
                                        </div>
                                        <p style="line-height: 1.4;"><?php echo esc($msg['message']); ?></p>
                                        
                                        <?php if ($msg['file_path']): ?>
                                            <div style="margin-top: 4px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 4px;">
                                                <a href="<?php echo BASE_URL . $msg['file_path']; ?>" download style="color: var(--color-accent); font-size: 0.75rem; font-weight: 700;">Download Attachment</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <small style="color: var(--color-text-muted-dark); font-size: 0.65rem; margin-top: 2px;"><?php echo date('H:i M d', strtotime($msg['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Dispatch Input -->
                    <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 8px;">
                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                        <input type="hidden" name="action_type" value="send_chat">
                        
                        <textarea name="message" rows="3" class="form-control" placeholder="Type your update message for engineering desk..." required style="padding: 10px; font-size: 0.88rem;"></textarea>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <div style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">
                                <label for="chat_att">Attach File:</label>
                                <input type="file" name="chat_file" id="chat_att" style="font-size: 0.75rem;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="min-height: auto; padding: 8px 16px;">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- =============================================
                 TAB 6: INVOICES & BILLINGS
                 ============================================= -->
            <div class="portal-tab-content" id="portal-tab-billing">
                <div class="glass-card" style="padding: 1.25rem;">
                    <h4 style="color: var(--color-accent); margin-bottom: 12px;">Financial Invoices Registry</h4>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Billing Amount</th>
                                <th>Due Date</th>
                                <th>Payment Status</th>
                                <th>Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--color-text-muted-dark);">No invoices recorded for this project workspace.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td><strong><?php echo esc($inv['invoice_number']); ?></strong></td>
                                        <td style="font-weight: 600; color: #fff;">$<?php echo number_format($inv['amount'], 2); ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $inv['status'] === 'Paid' ? 'status-won' : 'status-new'; ?>" style="font-size: 0.65rem;">
                                                <?php echo esc($inv['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="#" onclick="alert('PDF invoice format compilation ready.'); return false;" class="action-link">PDF Invoice</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =============================================
                 TAB 7: PROFILE SETTINGS
                 ============================================= -->
            <div class="portal-tab-content" id="portal-tab-settings">
                <div class="glass-card" style="padding: 1.25rem; max-width: 600px; margin: 0 auto;">
                    <h4 style="color: var(--color-accent); margin-bottom: 12px;">Update Profile Coordinates</h4>
                    <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                        <input type="hidden" name="action_type" value="update_profile">

                        <div class="form-group">
                            <label for="pr_name">Full Name</label>
                            <input type="text" name="name" id="pr_name" class="form-control" value="<?php echo esc_attr($client['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="pr_phone">Contact Phone</label>
                            <input type="text" name="phone" id="pr_phone" class="form-control" value="<?php echo esc_attr($client['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="pr_pass">Change Password (Leave blank to keep current)</label>
                            <input type="password" name="password" id="pr_pass" class="form-control" placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Save Profile Settings</button>
                    </form>
                </div>
            </div>

        <?php endif; ?>

    </div>
</section>

<!-- Tab switcher Vanilla JS script -->
<script>
function switchPortalTab(tabName) {
    // Hide all contents
    const contents = document.querySelectorAll('.portal-tab-content');
    contents.forEach(content => content.classList.remove('active'));

    // Deactivate all buttons
    const buttons = document.querySelectorAll('.portal-tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Show selected content and activate button
    const targetContent = document.getElementById('portal-tab-' + tabName);
    const targetBtn = document.getElementById('tab-btn-' + tabName);
    
    if (targetContent && targetBtn) {
        targetContent.classList.add('active');
        targetBtn.classList.add('active');
    }
}
</script>

<style>
/* Dashboard Tab stylings */
.portal-tab-btn {
    background: rgba(255,255,255,0.02);
    border: var(--border-glass);
    color: var(--color-text-secondary-dark);
    padding: 8px 16px;
    font-size: 0.85rem;
    font-family: var(--font-heading);
    font-weight: 500;
    cursor: pointer;
    border-radius: var(--radius-sm);
    transition: all var(--transition-medium) ease;
}
.portal-tab-btn.active, .portal-tab-btn:hover {
    background: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary);
}
.portal-tab-content {
    display: none;
    animation: fadeIn 0.4s ease forwards;
}
.portal-tab-content.active {
    display: block;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
