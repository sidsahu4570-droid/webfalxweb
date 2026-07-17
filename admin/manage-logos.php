<?php
/**
 * WebFalx Admin Logos Manager
 * Client Logos CRUD operations with image file uploads
 */

$page_seo = [
    'title' => 'Manage Client Logos | WebFalx Admin'
];

require_once __DIR__ . '/admin_header.php';

$error_message = '';
$success_message = '';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if ($id > 0) {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            $stmt = $db->prepare("DELETE FROM client_logos WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('logo_flash', 'Client logo deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-logos.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete logo: ' . $e->getMessage();
    }
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $logo_url = '';
        
        // Handle File Upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            // Generate clean target name
            $fileExtension = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'logo_' . time() . '_' . rand(100,999) . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            // Check file type (images only)
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                throw new Exception("Invalid file extension. Only JPG, PNG, GIF, WebP, and SVG are allowed.");
            }
            
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $targetPath)) {
                $logo_url = 'assets/uploads/' . $fileName;
            } else {
                throw new Exception("Failed to move uploaded file.");
            }
        } else {
            // Fallback to text input URL
            $logo_url = sanitize_input($_POST['logo_url'] ?? '');
        }
        
        if (empty($company_name)) {
            $error_message = 'Please provide a company name.';
        } elseif (empty($logo_url)) {
            $error_message = 'Please upload a logo image or enter a text logo fallback.';
        } else {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $stmt = $db->prepare("INSERT INTO client_logos (company_name, logo_url, display_order, is_active) VALUES (:name, :url, :order, 1)");
            $stmt->execute([
                'name' => $company_name,
                'url' => $logo_url,
                'order' => $display_order
            ]);
            
            flash_message('logo_flash', 'Client logo added successfully.', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to save logo: ' . $e->getMessage();
    }
}

// Fetch all logos
$logos = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT * FROM client_logos ORDER BY display_order ASC");
        $logos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch logos: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage Client Logos</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Manage brand logos displayed in the scrolling slider marquee.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
    <!-- Add New Logo Card -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add New Logo Partner</h4>
        
        <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" name="company_name" id="company_name" class="form-control" required placeholder="e.g. Acme Corp">
            </div>
            
            <div class="form-group">
                <label for="logo_file">Upload Image File (Optional)</label>
                <input type="file" name="logo_file" id="logo_file" class="form-control" style="padding: 6px;">
                <small style="color: var(--color-text-muted-dark); font-size: 0.75rem;">Supports JPG, PNG, WebP, SVG</small>
            </div>
            
            <div class="form-group">
                <label for="logo_url">Text Logo Fallback</label>
                <input type="text" name="logo_url" id="logo_url" class="form-control" placeholder="e.g. ACMECORP">
                <small style="color: var(--color-text-muted-dark); font-size: 0.75rem;">Used if no file is uploaded.</small>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="10" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Logo</button>
        </form>
    </div>
    
    <!-- Logos List Table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Current Logo Partners</h4>
        
        <div class="table-responsive" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Path/Value</th>
                        <th>Sequence</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logos)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark);">No client logos uploaded.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logos as $logo): ?>
                            <tr>
                                <td>
                                    <strong style="color: #ffffff;"><?php echo esc($logo['company_name']); ?></strong>
                                </td>
                                <td style="font-size: 0.8rem; font-family: monospace;">
                                    <?php echo esc($logo['logo_url']); ?>
                                </td>
                                <td><?php echo esc($logo['display_order']); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $logo['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this logo partner?');">Delete</a>
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
