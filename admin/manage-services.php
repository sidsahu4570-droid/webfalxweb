<?php
/**
 * WebFalx Admin Services Manager
 * Extended Services CRUD with categories, SEO, packages, and technical lists
 */

$page_seo = [
    'title' => 'Manage Services Stacks | WebFalx Admin'
];

require_once __DIR__ . '/admin_header.php';

$error_message = '';
$success_message = '';

// Fetch all categories for reference in dropdowns
$categories = [];
if ($db !== null) {
    try {
        $categories = $db->query("SELECT id, name FROM service_categories ORDER BY display_order ASC")->fetchAll();
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
            $stmt = $db->prepare("DELETE FROM services WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('services_flash', 'Service deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-services.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete service: ' . $e->getMessage();
    }
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $title = sanitize_input($_POST['title'] ?? '');
        $slug = sanitize_input($_POST['slug'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $description = sanitize_input($_POST['description'] ?? '');
        $full_description = sanitize_input($_POST['full_description'] ?? '');
        $icon_svg = $_POST['icon_svg'] ?? ''; // Keep SVG markup intact (safe under admin auth)
        $meta_title = sanitize_input($_POST['meta_title'] ?? '');
        $meta_description = sanitize_input($_POST['meta_description'] ?? '');
        $hero_image = sanitize_input($_POST['hero_image'] ?? '');
        $features = sanitize_input($_POST['features'] ?? '');
        $benefits = sanitize_input($_POST['benefits'] ?? '');
        $technologies = sanitize_input($_POST['technologies'] ?? '');
        $packages_json = $_POST['packages_json'] ?? '[]'; // Safe default
        $display_order = intval($_POST['display_order'] ?? 0);
        
        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }

        if (empty($title) || empty($description) || empty($icon_svg)) {
            $error_message = 'Please provide title, short description, and icon SVG markup.';
        } else {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            // Insert or Update logic
            $stmt = $db->prepare("INSERT INTO services (category_id, title, slug, description, full_description, icon_svg, meta_title, meta_description, hero_image, features, benefits, technologies, packages_json, display_order, is_active) 
                                  VALUES (:cat, :title, :slug, :desc, :full, :icon, :meta_t, :meta_d, :image, :features, :benefits, :tech, :pkg, :order, 1)
                                  ON DUPLICATE KEY UPDATE category_id = :cat, title = :title, description = :desc, full_description = :full, icon_svg = :icon, meta_title = :meta_t, meta_description = :meta_d, hero_image = :image, features = :features, benefits = :benefits, technologies = :tech, packages_json = :pkg, display_order = :order");
            
            $stmt->execute([
                'cat' => $category_id ?: null,
                'title' => $title,
                'slug' => $slug,
                'desc' => $description,
                'full' => $full_description,
                'icon' => $icon_svg,
                'meta_t' => $meta_title ?: null,
                'meta_d' => $meta_description ?: null,
                'image' => $hero_image ?: null,
                'features' => $features ?: null,
                'benefits' => $benefits ?: null,
                'tech' => $technologies ?: null,
                'pkg' => $packages_json ?: '[]',
                'order' => $display_order
            ]);
            
            flash_message('services_flash', 'Service saved successfully.', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to save service: ' . $e->getMessage();
    }
}

// Fetch all services
$services = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT s.*, c.name as cat_name FROM services s LEFT JOIN service_categories c ON s.category_id = c.id ORDER BY s.display_order ASC");
        $services = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch services: " . $e->getMessage());
    }
}

// Pre-fill json placeholder
$default_packages_json = '[
  {
    "name": "Standard Tier",
    "price": "$2,500",
    "features": [
      "Custom Homepage",
      "Up to 5 pages",
      "Essential analytics"
    ]
  },
  {
    "name": "Enterprise Core",
    "price": "$5,000",
    "features": [
      "Bespoke designs library",
      "API ERP integrations",
      "Advanced caching setups"
    ]
  }
]';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage Services Stacks</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Build, edit, or remove technical services and package tiers.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.2fr;">
    <!-- Add/Edit Service Card -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add or Update Service</h4>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="title">Service Title</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. WordPress Development">
            </div>

            <div class="form-group">
                <label for="slug">SEO Slug (Unique Key)</label>
                <input type="text" name="slug" id="slug" class="form-control" placeholder="e.g. wordpress-development">
                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Leave empty to auto-generate from title. Submitting an existing slug will update its record.</small>
            </div>
            
            <div class="form-group">
                <label for="category_id">Service Category</label>
                <select name="category_id" id="category_id" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;">
                    <option value="">No Category (Uncategorized)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo esc($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="icon_svg">Icon SVG Markup</label>
                <textarea name="icon_svg" id="icon_svg" rows="2" class="form-control" placeholder="<svg ...>...</svg>" required></textarea>
            </div>

            <div class="form-group">
                <label for="hero_image">Hero Cover Image URL</label>
                <input type="text" name="hero_image" id="hero_image" class="form-control" placeholder="https://images.unsplash.com/...">
            </div>

            <div class="form-group">
                <label for="description">Short Description (Cards)</label>
                <textarea name="description" id="description" rows="3" class="form-control" required placeholder="A brief hook used in index sliders..."></textarea>
            </div>

            <div class="form-group">
                <label for="full_description">Full Content Overview (Detail Page)</label>
                <textarea name="full_description" id="full_description" rows="6" class="form-control" placeholder="Complete detail overview of the service scope..."></textarea>
            </div>

            <div class="form-group">
                <label for="features">Key Features (One per line)</label>
                <textarea name="features" id="features" rows="4" class="form-control" placeholder="Custom Theme Engineering&#10;Core Web Vitals Optimisations&#10;API Connections"></textarea>
            </div>

            <div class="form-group">
                <label for="benefits">Business Benefits (One per line)</label>
                <textarea name="benefits" id="benefits" rows="4" class="form-control" placeholder="Increase sales conversions by 20%&#10;Gain complete content controls&#10;Reduce maintenance costs"></textarea>
            </div>

            <div class="form-group">
                <label for="technologies">Technologies Used (Comma Separated)</label>
                <input type="text" name="technologies" id="technologies" class="form-control" placeholder="WordPress, PHP 8, React, MySQL">
            </div>

            <div class="form-group">
                <label for="packages_json">Pricing Packages (JSON Format)</label>
                <textarea name="packages_json" id="packages_json" rows="6" class="form-control" style="font-family: monospace; font-size: 0.8rem;"><?php echo esc($default_packages_json); ?></textarea>
                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Follow standard JSON format array mapping name, price, and features.</small>
            </div>

            <h5 style="color: var(--color-accent); margin-top: 10px; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 4px;">SEO Metadata Override</h5>

            <div class="form-group">
                <label for="meta_title">Meta Title</label>
                <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="Shopify Development Agency | WebFalx">
            </div>

            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea name="meta_description" id="meta_description" rows="2" class="form-control" placeholder="Custom Liquid templates design..."></textarea>
            </div>

            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="10" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Service Details</button>
        </form>
    </div>
    
    <!-- Services List Table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Current Services</h4>
        
        <div class="table-responsive" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Slug</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark);">No services created.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $srv): ?>
                            <tr>
                                <td>
                                    <strong style="color: #ffffff;"><?php echo esc($srv['title']); ?></strong>
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);"><?php echo esc($srv['cat_name'] ?: 'General'); ?></span>
                                </td>
                                <td style="font-size: 0.8rem; font-family: monospace; color: var(--color-text-muted-dark);">
                                    <?php echo esc($srv['slug']); ?>
                                </td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $srv['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
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
