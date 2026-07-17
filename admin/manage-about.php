<?php
/**
 * WebFalx Admin About Us Manager
 * Tabbed dashboard console providing complete CRUD management over timelines, values, teams, and awards
 */

$page_seo = [
    'title' => 'Manage About Us Page | WebFalx Admin'
];

require_once __DIR__ . '/admin_header.php';

$error_message = '';
$success_message = '';

// Resolve current active tab
$active_tab = sanitize_input($_GET['tab'] ?? 'story');

// 1. Handle Delete Actions for all sub-entities
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $id = intval($_GET['id'] ?? 0);
        $type = sanitize_input($_GET['type'] ?? '');
        
        if ($id > 0 && !empty($type)) {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $valid_tables = [
                'value' => 'core_values',
                'milestone' => 'company_milestones',
                'achievement' => 'achievements',
                'tech' => 'technologies',
                'industry' => 'industries_served',
                'team' => 'team_members',
                'skill' => 'skills_expertise',
                'cert' => 'certifications',
                'award' => 'awards'
            ];
            
            if (array_key_exists($type, $valid_tables)) {
                $table = $valid_tables[$type];
                $stmt = $db->prepare("DELETE FROM $table WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                flash_message('about_flash', 'Record deleted successfully.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-about.php?tab=' . $active_tab);
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete record: ' . $e->getMessage();
    }
}

// 2. Handle POST Add / Edit Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $action_type = sanitize_input($_POST['action_type'] ?? '');
        
        if ($db === null) {
            throw new Exception("Database is offline.");
        }
        
        // Save Story & Mission Content Blocks
        if ($action_type === 'save_story') {
            $story_title = sanitize_input($_POST['story_title'] ?? '');
            $story_subtitle = sanitize_input($_POST['story_subtitle'] ?? '');
            $story_content = sanitize_input($_POST['story_content'] ?? '');

            $mission_title = sanitize_input($_POST['mission_title'] ?? '');
            $mission_content = sanitize_input($_POST['mission_content'] ?? '');

            $vision_title = sanitize_input($_POST['vision_title'] ?? '');
            $vision_content = sanitize_input($_POST['vision_content'] ?? '');

            // Update blocks
            update_content_block('about_story', $story_title, $story_subtitle, $story_content);
            update_content_block('about_mission', $mission_title, 'OUR MISSION', $mission_content);
            update_content_block('about_vision', $vision_title, 'OUR VISION', $vision_content);
            
            flash_message('about_flash', 'Story and Mission blocks updated.', 'success');
        }
        
        // Add Core Value
        elseif ($action_type === 'add_value') {
            $name = sanitize_input($_POST['name'] ?? '');
            $desc = sanitize_input($_POST['description'] ?? '');
            $order = intval($_POST['display_order'] ?? 10);
            
            if (empty($name) || empty($desc)) {
                throw new Exception("Name and description are required.");
            }
            
            $stmt = $db->prepare("INSERT INTO core_values (name, description, icon_svg, display_order, is_active) VALUES (:name, :desc, 'shield', :order, 1)");
            $stmt->execute(['name' => $name, 'desc' => $desc, 'order' => $order]);
            flash_message('about_flash', 'Core Value added.', 'success');
        }
        
        // Add Milestone
        elseif ($action_type === 'add_milestone') {
            $year = sanitize_input($_POST['year'] ?? '');
            $title = sanitize_input($_POST['title'] ?? '');
            $desc = sanitize_input($_POST['description'] ?? '');
            $image = sanitize_input($_POST['image_url'] ?? '');
            $order = intval($_POST['display_order'] ?? 10);
            
            if (empty($year) || empty($title) || empty($desc)) {
                throw new Exception("Year, title, and description are required.");
            }
            
            $stmt = $db->prepare("INSERT INTO company_milestones (year, title, description, image_url, display_order, is_active) VALUES (:year, :title, :desc, :image, :order, 1)");
            $stmt->execute(['year' => $year, 'title' => $title, 'desc' => $desc, 'image' => $image, 'order' => $order]);
            flash_message('about_flash', 'Milestone milestone added.', 'success');
        }
        
        // Add Team Member
        elseif ($action_type === 'add_team') {
            $name = sanitize_input($_POST['name'] ?? '');
            $desig = sanitize_input($_POST['designation'] ?? '');
            $bio = sanitize_input($_POST['bio'] ?? '');
            $exp = sanitize_input($_POST['experience'] ?? '');
            $skills = sanitize_input($_POST['skills'] ?? '');
            $image = sanitize_input($_POST['image_url'] ?? '');
            $linkedin = sanitize_input($_POST['linkedin'] ?? '');
            $github = sanitize_input($_POST['github'] ?? '');
            $order = intval($_POST['display_order'] ?? 10);

            if (empty($name) || empty($desig) || empty($image)) {
                throw new Exception("Name, designation, and member photo URL are required.");
            }
            
            $socials = json_encode(['linkedin' => $linkedin, 'github' => $github]);
            
            $stmt = $db->prepare("INSERT INTO team_members (name, designation, bio, experience, skills, social_links_json, image_url, display_order, is_active) VALUES (:name, :desig, :bio, :exp, :skills, :socials, :image, :order, 1)");
            $stmt->execute(['name' => $name, 'desig' => $desig, 'bio' => $bio, 'exp' => $exp, 'skills' => $skills, 'socials' => $socials, 'image' => $image, 'order' => $order]);
            flash_message('about_flash', 'Team Member profile saved.', 'success');
        }
        
        // Add Skill
        elseif ($action_type === 'add_skill') {
            $name = sanitize_input($_POST['name'] ?? '');
            $pct = intval($_POST['percentage'] ?? 85);
            $order = intval($_POST['display_order'] ?? 10);
            
            if (empty($name)) {
                throw new Exception("Skill title is required.");
            }
            
            $stmt = $db->prepare("INSERT INTO skills_expertise (name, percentage, display_order, is_active) VALUES (:name, :pct, :order, 1)");
            $stmt->execute(['name' => $name, 'pct' => $pct, 'order' => $order]);
            flash_message('about_flash', 'Skill rating added.', 'success');
        }
        
        // Add Tech
        elseif ($action_type === 'add_tech') {
            $name = sanitize_input($_POST['name'] ?? '');
            $order = intval($_POST['display_order'] ?? 10);
            
            if (empty($name)) {
                throw new Exception("Tech tag title is required.");
            }
            
            $stmt = $db->prepare("INSERT INTO technologies (name, icon_svg, display_order, is_active) VALUES (:name, 'tech', :order, 1)");
            $stmt->execute(['name' => $name, 'order' => $order]);
            flash_message('about_flash', 'Technology tag saved.', 'success');
        }
        
        // Add Industry
        elseif ($action_type === 'add_industry') {
            $name = sanitize_input($_POST['name'] ?? '');
            $desc = sanitize_input($_POST['description'] ?? '');
            $order = intval($_POST['display_order'] ?? 10);

            if (empty($name)) {
                throw new Exception("Industry name is required.");
            }
            
            $stmt = $db->prepare("INSERT INTO industries_served (name, description, icon_svg, display_order, is_active) VALUES (:name, :desc, 'home', :order, 1)");
            $stmt->execute(['name' => $name, 'desc' => $desc, 'order' => $order]);
            flash_message('about_flash', 'Industry target added.', 'success');
        }
        
        // Redirect back to same tab to clear POST data
        header('Location: ' . BASE_URL . 'admin/manage-about.php?tab=' . $active_tab);
        exit;
    } catch (Exception $ex) {
        $error_message = $ex->getMessage();
    }
}

// Fetch tab lists
$values = [];
$milestones = [];
$team = [];
$skills = [];
$techs = [];
$industries = [];

if ($db !== null) {
    try {
        if ($active_tab === 'story') {
            $values = $db->query("SELECT * FROM core_values ORDER BY display_order ASC")->fetchAll();
        } elseif ($active_tab === 'timeline') {
            $milestones = $db->query("SELECT * FROM company_milestones ORDER BY display_order ASC")->fetchAll();
        } elseif ($active_tab === 'team') {
            $team = $db->query("SELECT * FROM team_members ORDER BY display_order ASC")->fetchAll();
            $skills = $db->query("SELECT * FROM skills_expertise ORDER BY display_order ASC")->fetchAll();
        } elseif ($active_tab === 'tech') {
            $techs = $db->query("SELECT * FROM technologies ORDER BY display_order ASC")->fetchAll();
            $industries = $db->query("SELECT * FROM industries_served ORDER BY display_order ASC")->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Failed loading admin values: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage About Us Page</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Manage growth timelines, core values, technical staff, and certified credentials.</p>

<!-- URL Tabs navigation bar -->
<div class="admin-tab-group">
    <a href="?tab=story" class="admin-tab-btn <?php echo $active_tab === 'story' ? 'active' : ''; ?>">Story & Core Values</a>
    <a href="?tab=timeline" class="admin-tab-btn <?php echo $active_tab === 'timeline' ? 'active' : ''; ?>">Timeline Milestones</a>
    <a href="?tab=team" class="admin-tab-btn <?php echo $active_tab === 'team' ? 'active' : ''; ?>">Team & Capabilities</a>
    <a href="?tab=tech" class="admin-tab-btn <?php echo $active_tab === 'tech' ? 'active' : ''; ?>">Tech & Industries</a>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 1: STORY & CORE VALUES
     ========================================== -->
<?php if ($active_tab === 'story'): ?>
    <?php
    $story = get_content_block('about_story', ['title' => '', 'subtitle' => '', 'content' => '']);
    $miss = get_content_block('about_mission', ['title' => '', 'subtitle' => '', 'content' => '']);
    $vis = get_content_block('about_vision', ['title' => '', 'subtitle' => '', 'content' => '']);
    ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.2fr;">
        <!-- Edit text blocks -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Edit Main Story Paragraphs</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="save_story">
                
                <div class="form-group">
                    <label for="story_title">Story Heading</label>
                    <input type="text" name="story_title" id="story_title" class="form-control" value="<?php echo esc_attr($story['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="story_subtitle">Story Subtitle</label>
                    <input type="text" name="story_subtitle" id="story_subtitle" class="form-control" value="<?php echo esc_attr($story['subtitle']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="story_content">Story Paragraphs</label>
                    <textarea name="story_content" id="story_content" rows="6" class="form-control" required><?php echo esc($story['content']); ?></textarea>
                </div>

                <h5 style="color: var(--color-accent); margin-top: 10px; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 4px;">Mission & Vision</h5>
                
                <div class="form-group">
                    <label for="mission_title">Mission Title</label>
                    <input type="text" name="mission_title" id="mission_title" class="form-control" value="<?php echo esc_attr($miss['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mission_content">Mission Statement</label>
                    <textarea name="mission_content" id="mission_content" rows="3" class="form-control" required><?php echo esc($miss['content']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="vision_title">Vision Title</label>
                    <input type="text" name="vision_title" id="vision_title" class="form-control" value="<?php echo esc_attr($vis['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="vision_content">Vision Statement</label>
                    <textarea name="vision_content" id="vision_content" rows="3" class="form-control" required><?php echo esc($vis['content']); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Story blocks</button>
            </form>
        </div>
        
        <!-- Core values CRUD -->
        <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
            <div class="glass-card" style="padding: 1.25rem;">
                <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Core Value</h4>
                <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                    <input type="hidden" name="action_type" value="add_value">
                    
                    <div class="form-group">
                        <label for="val_name">Value Name</label>
                        <input type="text" name="name" id="val_name" class="form-control" required placeholder="e.g. Radical Transparency">
                    </div>
                    <div class="form-group">
                        <label for="val_desc">Description</label>
                        <textarea name="description" id="val_desc" rows="3" class="form-control" required placeholder="Complete visibility on staging servers..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="val_order">Display Order</label>
                        <input type="number" name="display_order" id="val_order" class="form-control" value="10">
                    </div>
                    <button type="submit" class="btn btn-secondary">Save Core Value</button>
                </form>
            </div>
            
            <div class="glass-card" style="padding: 1.25rem;">
                <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Active Values</h4>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Value</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($values as $v): ?>
                            <tr>
                                <td><strong><?php echo esc($v['name']); ?></strong></td>
                                <td style="font-size: 0.8rem;"><?php echo esc($v['description']); ?></td>
                                <td>
                                    <a href="?action=delete&type=value&id=<?php echo $v['id']; ?>&tab=story" class="action-link action-delete" onclick="return confirm('Delete this core value?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 2: TIMELINE MILESTONES
     ========================================== -->
<?php if ($active_tab === 'timeline'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Milestone Milestone</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_milestone">
                
                <div class="form-group">
                    <label for="mil_year">Year</label>
                    <input type="text" name="year" id="mil_year" class="form-control" required placeholder="e.g. 2025">
                </div>
                <div class="form-group">
                    <label for="mil_title">Milestone Title</label>
                    <input type="text" name="title" id="mil_title" class="form-control" required placeholder="e.g. 100 Projects Completed">
                </div>
                <div class="form-group">
                    <label for="mil_image">Feature Image URL</label>
                    <input type="text" name="image_url" id="mil_image" class="form-control" placeholder="https://images.unsplash.com/...">
                </div>
                <div class="form-group">
                    <label for="mil_desc">Brief Description</label>
                    <textarea name="description" id="mil_desc" rows="3" class="form-control" required placeholder="Delivered low latency stores..."></textarea>
                </div>
                <div class="form-group">
                    <label for="mil_order">Display Order</label>
                    <input type="number" name="display_order" id="mil_order" class="form-control" value="10">
                </div>
                <button type="submit" class="btn btn-primary">Save Milestone</button>
            </form>
        </div>

        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Milestone History</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Milestone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($milestones as $m): ?>
                        <tr>
                            <td><strong style="color: var(--color-accent);"><?php echo esc($m['year']); ?></strong></td>
                            <td>
                                <strong><?php echo esc($m['title']); ?></strong>
                                <p style="font-size: 0.8rem; color: var(--color-text-muted-dark);"><?php echo esc($m['description']); ?></p>
                            </td>
                            <td>
                                <a href="?action=delete&type=milestone&id=<?php echo $m['id']; ?>&tab=timeline" class="action-link action-delete" onclick="return confirm('Delete this timeline milestone?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 3: TEAM & CAPABILITIES
     ========================================== -->
<?php if ($active_tab === 'team'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.3fr;">
        <!-- Add Team Member Profile -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Team Architect</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_team">
                
                <div class="form-group">
                    <label for="team_name">Full Name</label>
                    <input type="text" name="name" id="team_name" class="form-control" required placeholder="Marcus Vance">
                </div>
                <div class="form-group">
                    <label for="team_desig">Designation</label>
                    <input type="text" name="designation" id="team_desig" class="form-control" required placeholder="Lead PHP Engineer">
                </div>
                <div class="form-group">
                    <label for="team_image">Photo URL</label>
                    <input type="text" name="image_url" id="team_image" class="form-control" required placeholder="https://images.unsplash.com/...">
                </div>
                <div class="form-group">
                    <label for="team_exp">Experience Duration</label>
                    <input type="text" name="experience" id="team_exp" class="form-control" placeholder="e.g. 8 Years">
                </div>
                <div class="form-group">
                    <label for="team_skills">Coding Skills (Comma Separated)</label>
                    <input type="text" name="skills" id="team_skills" class="form-control" placeholder="PHP, MySQL, React">
                </div>
                <div class="form-group">
                    <label for="team_linkedin">LinkedIn profile URL</label>
                    <input type="text" name="linkedin" id="team_linkedin" class="form-control" placeholder="https://linkedin.com/in/...">
                </div>
                <div class="form-group">
                    <label for="team_github">GitHub URL</label>
                    <input type="text" name="github" id="team_github" class="form-control" placeholder="https://github.com/...">
                </div>
                <div class="form-group">
                    <label for="team_bio">Short Professional Bio</label>
                    <textarea name="bio" id="team_bio" rows="3" class="form-control" placeholder="Specializes in low-latency custom PHP databases..."></textarea>
                </div>
                <div class="form-group">
                    <label for="team_order">Display Order</label>
                    <input type="number" name="display_order" id="team_order" class="form-control" value="10">
                </div>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>

        <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
            <!-- Active Team Members list -->
            <div class="glass-card" style="padding: 1.25rem;">
                <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Staff Listing</h4>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team as $t): ?>
                            <tr>
                                <td><strong><?php echo esc($t['name']); ?></strong></td>
                                <td><?php echo esc($t['designation']); ?></td>
                                <td>
                                    <a href="?action=delete&type=team&id=<?php echo $t['id']; ?>&tab=team" class="action-link action-delete" onclick="return confirm('Delete team member?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Skills CRUD -->
            <div class="glass-card" style="padding: 1.25rem;">
                <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Performance Capability</h4>
                <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                    <input type="hidden" name="action_type" value="add_skill">
                    
                    <div class="form-group">
                        <label for="sk_name">Capability / Skill name</label>
                        <input type="text" name="name" id="sk_name" class="form-control" required placeholder="e.g. Low Latency database sync">
                    </div>
                    <div class="form-group">
                        <label for="sk_pct">Percentage Rating (0 - 100)</label>
                        <input type="number" name="percentage" id="sk_pct" class="form-control" min="1" max="100" value="90">
                    </div>
                    <div class="form-group">
                        <label for="sk_order">Display Order</label>
                        <input type="number" name="display_order" id="sk_order" class="form-control" value="10">
                    </div>
                    <button type="submit" class="btn btn-secondary">Add Skill</button>
                </form>

                <table class="admin-table" style="margin-top: 1rem;">
                    <thead>
                        <tr>
                            <th>Capability</th>
                            <th>Ratio</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($skills as $s): ?>
                            <tr>
                                <td><strong><?php echo esc($s['name']); ?></strong></td>
                                <td><?php echo esc($s['percentage']); ?>%</td>
                                <td>
                                    <a href="?action=delete&type=skill&id=<?php echo $s['id']; ?>&tab=team" class="action-link action-delete" onclick="return confirm('Delete this skill ratio?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 4: TECH & INDUSTRIES
     ========================================== -->
<?php if ($active_tab === 'tech'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
        <!-- Tech stack tags CRUD -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Technology stack tag</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_tech">
                
                <div class="form-group">
                    <label for="tc_name">Technology Name</label>
                    <input type="text" name="name" id="tc_name" class="form-control" required placeholder="e.g. Shopify Liquid">
                </div>
                <div class="form-group">
                    <label for="tc_order">Display Order</label>
                    <input type="number" name="display_order" id="tc_order" class="form-control" value="10">
                </div>
                <button type="submit" class="btn btn-primary">Save Tag</button>
            </form>

            <table class="admin-table" style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>Tech Tag</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($techs as $t): ?>
                        <tr>
                            <td><strong><?php echo esc($t['name']); ?></strong></td>
                            <td>
                                <a href="?action=delete&type=tech&id=<?php echo $t['id']; ?>&tab=tech" class="action-link action-delete" onclick="return confirm('Delete technology tag?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Industries CRUD -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Target Industry Served</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_industry">
                
                <div class="form-group">
                    <label for="ind_name">Industry Name</label>
                    <input type="text" name="name" id="ind_name" class="form-control" required placeholder="e.g. Clinic Groups & Doctors">
                </div>
                <div class="form-group">
                    <label for="ind_desc">Brief Description</label>
                    <textarea name="description" id="ind_desc" rows="3" class="form-control" placeholder="Syncing appointments portals with offline patient CRM databases..."></textarea>
                </div>
                <div class="form-group">
                    <label for="ind_order">Display Order</label>
                    <input type="number" name="display_order" id="ind_order" class="form-control" value="10">
                </div>
                <button type="submit" class="btn btn-secondary">Save Industry</button>
            </form>

            <table class="admin-table" style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>Industry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($industries as $i): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($i['name']); ?></strong>
                                <p style="font-size: 0.8rem; color: var(--color-text-muted-dark);"><?php echo esc($i['description']); ?></p>
                            </td>
                            <td>
                                <a href="?action=delete&type=industry&id=<?php echo $i['id']; ?>&tab=tech" class="action-link action-delete" onclick="return confirm('Delete industry served?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
