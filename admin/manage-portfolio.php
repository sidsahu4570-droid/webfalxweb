<?php
/**
 * WebFalx Admin Portfolio Manager
 * Dynamic CRUD console for Case Studies with mockups, SEO, and results mapping
 */

$page_seo = [
    'title' => 'Manage Portfolio Projects | WebFalx Admin'
];

require_once __DIR__ . '/admin_header.php';

$error_message = '';
$success_message = '';

// Fetch categories for reference in selects
$categories = [];
if ($db !== null) {
    try {
        $categories = $db->query("SELECT id, name FROM portfolio_categories ORDER BY display_order ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch dropdown categories: " . $e->getMessage());
    }
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if ($id > 0) {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            $stmt = $db->prepare("DELETE FROM portfolio_projects WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('portfolio_flash', 'Portfolio project deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-portfolio.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete project: ' . $e->getMessage();
    }
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $title = sanitize_input($_POST['title'] ?? '');
        $slug = sanitize_input($_POST['slug'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $client = sanitize_input($_POST['client'] ?? '');
        $industry = sanitize_input($_POST['industry'] ?? '');
        $project_type = sanitize_input($_POST['project_type'] ?? '');
        $technology = sanitize_input($_POST['technology'] ?? '');
        $completion_date = $_POST['completion_date'] ?: date('Y-m-d');
        $description = sanitize_input($_POST['description'] ?? '');
        
        // Case Study fields
        $full_overview = sanitize_input($_POST['full_overview'] ?? '');
        $challenge = sanitize_input($_POST['challenge'] ?? '');
        $solution = sanitize_input($_POST['solution'] ?? '');
        $features = sanitize_input($_POST['features'] ?? '');
        $results = sanitize_input($_POST['results'] ?? '');
        
        // Screenshot URLs
        $thumbnail_url = sanitize_input($_POST['thumbnail_url'] ?? '');
        $desktop_screenshot = sanitize_input($_POST['desktop_screenshot'] ?? '');
        $tablet_screenshot = sanitize_input($_POST['tablet_screenshot'] ?? '');
        $mobile_screenshot = sanitize_input($_POST['mobile_screenshot'] ?? '');
        $gallery_json = $_POST['gallery_json'] ?? '[]';
        $website_url = sanitize_input($_POST['website_url'] ?? '');
        
        // SEO metadata
        $meta_title = sanitize_input($_POST['meta_title'] ?? '');
        $meta_description = sanitize_input($_POST['meta_description'] ?? '');
        
        // Ordering / Status flags
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $is_latest = isset($_POST['is_latest']) ? 1 : 0;
        $is_recommended = isset($_POST['is_recommended']) ? 1 : 0;
        $display_order = intval($_POST['display_order'] ?? 0);

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }

        if (empty($title) || empty($description) || empty($thumbnail_url)) {
            $error_message = 'Please provide project title, short description, and card thumbnail URL.';
        } else {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }

            // Insert / Update on Duplicate key
            $stmt = $db->prepare("INSERT INTO portfolio_projects (category_id, title, slug, client, industry, project_type, technology, completion_date, description, full_overview, challenge, solution, features, results, thumbnail_url, desktop_screenshot, tablet_screenshot, mobile_screenshot, gallery_json, website_url, meta_title, meta_description, is_featured, is_popular, is_latest, is_recommended, is_active, display_order) 
                                  VALUES (:cat, :title, :slug, :client, :ind, :type, :tech, :date, :desc, :overview, :chal, :sol, :feat, :res, :thumb, :desk, :tab, :mob, :gal, :web, :meta_t, :meta_d, :feat_f, :pop_f, :lat_f, :rec_f, 1, :order)
                                  ON DUPLICATE KEY UPDATE category_id = :cat, title = :title, client = :client, industry = :ind, project_type = :type, technology = :tech, completion_date = :date, description = :desc, full_overview = :overview, challenge = :chal, solution = :sol, features = :feat, results = :res, thumbnail_url = :thumb, desktop_screenshot = :desk, tablet_screenshot = :tab, mobile_screenshot = :mob, gallery_json = :gal, website_url = :web, meta_title = :meta_t, meta_description = :meta_d, is_featured = :feat_f, is_popular = :pop_f, is_latest = :lat_f, is_recommended = :rec_f, display_order = :order");
            
            $stmt->execute([
                'cat' => $category_id ?: null,
                'title' => $title,
                'slug' => $slug,
                'client' => $client ?: null,
                'ind' => $industry ?: null,
                'type' => $project_type ?: null,
                'tech' => $technology,
                'date' => $completion_date,
                'desc' => $description,
                'overview' => $full_overview ?: null,
                'chal' => $challenge ?: null,
                'sol' => $solution ?: null,
                'feat' => $features ?: null,
                'res' => $results ?: null,
                'thumb' => $thumbnail_url,
                'desk' => $desktop_screenshot ?: null,
                'tab' => $tablet_screenshot ?: null,
                'mob' => $mobile_screenshot ?: null,
                'gal' => $gallery_json ?: '[]',
                'web' => $website_url ?: null,
                'meta_t' => $meta_title ?: null,
                'meta_d' => $meta_description ?: null,
                'feat_f' => $is_featured,
                'pop_f' => $is_popular,
                'lat_f' => $is_latest,
                'rec_f' => $is_recommended,
                'order' => $display_order
            ]);

            flash_message('portfolio_flash', 'Portfolio project saved successfully.', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to save project: ' . $e->getMessage();
    }
}

// Fetch all projects
$projects = [];
if ($db !== null) {
    try {
        $projects = $db->query("SELECT p.*, c.name as cat_name FROM portfolio_projects p LEFT JOIN portfolio_categories c ON p.category_id = c.id ORDER BY p.display_order ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch projects list: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage Portfolio Projects</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Build and control case studies, mockup image galleries, and outcomes statistics.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.1fr;">
    <!-- Form Card -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add or Update Case Study</h4>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="title">Project Name</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Vanguard Apparel Store">
            </div>

            <div class="form-group">
                <label for="slug">SEO Slug (Unique Key)</label>
                <input type="text" name="slug" id="slug" class="form-control" placeholder="e.g. vanguard-apparel-store">
                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Leave empty to auto-generate. Inputting an existing slug updates its database columns.</small>
            </div>

            <div class="form-group">
                <label for="category_id">Portfolio Category</label>
                <select name="category_id" id="category_id" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;">
                    <option value="">No Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo esc($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="client">Client Name</label>
                    <input type="text" name="client" id="client" class="form-control" placeholder="Vanguard Couture">
                </div>
                <div class="form-group">
                    <label for="industry">Industry</label>
                    <input type="text" name="industry" id="industry" class="form-control" placeholder="Retail / Apparel">
                </div>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="project_type">Project Type</label>
                    <input type="text" name="project_type" id="project_type" class="form-control" placeholder="E-commerce store design">
                </div>
                <div class="form-group">
                    <label for="completion_date">Completion Date</label>
                    <input type="date" name="completion_date" id="completion_date" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="technology">Technologies Used (Comma Separated)</label>
                <input type="text" name="technology" id="technology" class="form-control" placeholder="Shopify, Liquid, JavaScript, HTML5">
            </div>

            <div class="form-group">
                <label for="website_url">Live Website URL</label>
                <input type="text" name="website_url" id="website_url" class="form-control" placeholder="https://vanguardcouture.com">
            </div>

            <div class="form-group">
                <label for="description">Short Hook / Cards Description</label>
                <textarea name="description" id="description" rows="3" class="form-control" required placeholder="Luxury e-commerce storefront with AJAX checkout systems..."></textarea>
            </div>

            <h5 style="color: var(--color-accent); border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 4px; margin-top: 10px;">Case Study Content Details</h5>

            <div class="form-group">
                <label for="full_overview">Project Overview</label>
                <textarea name="full_overview" id="full_overview" rows="4" class="form-control" placeholder="Vanguard Couture wanted a custom, lightning fast Shopify design..."></textarea>
            </div>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="challenge">Business Challenge</label>
                    <textarea name="challenge" id="challenge" rows="4" class="form-control" placeholder="Heavy page builders increased latency..."></textarea>
                </div>
                <div class="form-group">
                    <label for="solution">Solution Provided</label>
                    <textarea name="solution" id="solution" rows="4" class="form-control" placeholder="Developed clean liquid themes from scratch..."></textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="features">Key Implemented Features (One per line)</label>
                <textarea name="features" id="features" rows="4" class="form-control" placeholder="Bespoke Liquid product sheets&#10;AJAX Slide-out shopping cart&#10;SEO structured JSON breadcrumbs"></textarea>
            </div>

            <div class="form-group">
                <label for="results">Performance Metrics Achieved (Format: label:value, one per line)</label>
                <textarea name="results" id="results" rows="4" class="form-control" placeholder="Mobile Loading speed:1.1s&#10;Bounce Rate:-34%&#10;Sales Conversions:+4.2%"></textarea>
            </div>

            <h5 style="color: var(--color-accent); border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 4px; margin-top: 10px;">Screenshots Mockups (URLs)</h5>

            <div class="form-group">
                <label for="thumbnail_url">Card Thumbnail Cover Image URL *</label>
                <input type="text" name="thumbnail_url" id="thumbnail_url" class="form-control" required placeholder="https://images.unsplash.com/...">
            </div>

            <div class="grid grid-3" style="gap: 10px;">
                <div class="form-group">
                    <label for="desktop_screenshot">Desktop mockup</label>
                    <input type="text" name="desktop_screenshot" id="desktop_screenshot" class="form-control" placeholder="Image URL">
                </div>
                <div class="form-group">
                    <label for="tablet_screenshot">Tablet mockup</label>
                    <input type="text" name="tablet_screenshot" id="tablet_screenshot" class="form-control" placeholder="Image URL">
                </div>
                <div class="form-group">
                    <label for="mobile_screenshot">Mobile mockup</label>
                    <input type="text" name="mobile_screenshot" id="mobile_screenshot" class="form-control" placeholder="Image URL">
                </div>
            </div>

            <div class="form-group">
                <label for="gallery_json">Additional Screenshot Gallery (JSON Array)</label>
                <textarea name="gallery_json" id="gallery_json" rows="3" class="form-control" style="font-family: monospace; font-size: 0.8rem;">[]</textarea>
            </div>

            <h5 style="color: var(--color-accent); border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 4px; margin-top: 10px;">SEO & Display Parameters</h5>

            <div class="grid grid-2" style="gap: 10px;">
                <div class="form-group">
                    <label for="meta_title">Meta Title</label>
                    <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="Custom Shopify Store Design | Case Study">
                </div>
                <div class="form-group">
                    <label for="meta_description">Meta Description</label>
                    <input type="text" name="meta_description" id="meta_description" class="form-control" placeholder="WebFalx optimized Liquid theme execution...">
                </div>
            </div>

            <div class="grid grid-4" style="gap: 6px; font-size: 0.72rem; color: #ffffff; padding-top: 10px;">
                <label style="display: flex; align-items: center; gap: 4px;">
                    <input type="checkbox" name="is_featured" value="1"> Featured
                </label>
                <label style="display: flex; align-items: center; gap: 4px;">
                    <input type="checkbox" name="is_popular" value="1"> Popular
                </label>
                <label style="display: flex; align-items: center; gap: 4px;">
                    <input type="checkbox" name="is_latest" value="1"> Latest
                </label>
                <label style="display: flex; align-items: center; gap: 4px;">
                    <input type="checkbox" name="is_recommended" value="1"> Recom.
                </label>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <label for="display_order">Display Order Sequence</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="10" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Project details</button>
        </form>
    </div>

    <!-- Active Table List -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Case Studies Database</h4>
        
        <div class="table-responsive" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Category</th>
                        <th>Ordering</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark);">No case studies created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $proj): ?>
                            <tr>
                                <td>
                                    <strong style="color: #ffffff;"><?php echo esc($proj['title']); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted-dark);">Client: <?php echo esc($proj['client']); ?></div>
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);"><?php echo esc($proj['cat_name'] ?: 'General'); ?></span>
                                </td>
                                <td><?php echo esc($proj['display_order']); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $proj['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this case study?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
