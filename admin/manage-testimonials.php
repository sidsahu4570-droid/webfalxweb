<?php
/**
 * WebFalx Admin Testimonials Manager
 * Client Testimonials CRUD operations with profile uploads
 */

$page_seo = [
    'title' => 'Manage Testimonials | WebFalx Admin'
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
            $stmt = $db->prepare("DELETE FROM testimonials WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('testimonials_flash', 'Testimonial deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-testimonials.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete testimonial: ' . $e->getMessage();
    }
}

// Handle Add Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $client_name = sanitize_input($_POST['client_name'] ?? '');
        $client_business = sanitize_input($_POST['client_business'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $review = sanitize_input($_POST['review'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $client_image_url = '';
        
        // Handle Photo Upload
        if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExtension = pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'client_' . time() . '_' . rand(100,999) . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                throw new Exception("Invalid image type. Only JPG, PNG, and WebP are allowed.");
            }
            
            if (move_uploaded_file($_FILES['photo_file']['tmp_name'], $targetPath)) {
                $client_image_url = 'assets/uploads/' . $fileName;
            } else {
                throw new Exception("Failed to move uploaded photo.");
            }
        } else {
            $client_image_url = sanitize_input($_POST['client_image_url'] ?? '');
        }
        
        if (empty($client_name) || empty($review)) {
            $error_message = 'Please provide client name and review text.';
        } elseif (empty($client_image_url)) {
            $error_message = 'Please upload a client photo or provide a fallback URL.';
        } else {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $stmt = $db->prepare("INSERT INTO testimonials (client_name, client_business, client_image_url, rating, review, is_active, display_order) 
                                  VALUES (:name, :business, :url, :rating, :review, 1, :order)");
            $stmt->execute([
                'name' => $client_name,
                'business' => $client_business,
                'url' => $client_image_url,
                'rating' => $rating,
                'review' => $review,
                'order' => $display_order
            ]);
            
            flash_message('testimonials_flash', 'Testimonial added successfully.', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to save testimonial: ' . $e->getMessage();
    }
}

// Fetch all testimonials
$testimonials = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT * FROM testimonials ORDER BY display_order ASC");
        $testimonials = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch testimonials: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage Testimonials</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Create or delete reviews displayed in the user feedback segment.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
    <!-- Add New Testimonial Card -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add New Review</h4>
        
        <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="client_name">Client Name</label>
                <input type="text" name="client_name" id="client_name" class="form-control" required placeholder="e.g. Sarah Jenkins">
            </div>
            
            <div class="form-group">
                <label for="client_business">Business Title</label>
                <input type="text" name="client_business" id="client_business" class="form-control" required placeholder="e.g. CEO, Vanguard Apparel">
            </div>
            
            <div class="form-group">
                <label for="rating">Rating (Stars)</label>
                <select name="rating" id="rating" class="form-control" style="background: var(--color-bg-dark);">
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="photo_file">Upload Client Photo</label>
                <input type="file" name="photo_file" id="photo_file" class="form-control" style="padding: 6px;">
                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Supports JPG, PNG, WebP</small>
            </div>
            
            <div class="form-group">
                <label for="client_image_url">Or Photo URL Fallback</label>
                <input type="text" name="client_image_url" id="client_image_url" class="form-control" placeholder="https://images.unsplash.com/...">
            </div>
            
            <div class="form-group">
                <label for="review">Testimonial Content</label>
                <textarea name="review" id="review" rows="4" class="form-control" required placeholder="Client feedback..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="10" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Review</button>
        </form>
    </div>
    
    <!-- Testimonials List Table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Testimonial Submissions</h4>
        
        <div class="table-responsive" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Stars</th>
                        <th>Content</th>
                        <th style="width: 60px;">Sequence</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($testimonials)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--color-text-muted-dark);">No client testimonials added yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($testimonials as $t): ?>
                            <tr>
                                <td>
                                    <strong style="color: #ffffff;"><?php echo esc($t['client_name']); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted-dark);"><?php echo esc($t['client_business']); ?></div>
                                </td>
                                <td><span style="color: var(--color-warning);">&#9733; <?php echo esc($t['rating']); ?></span></td>
                                <td style="font-size: 0.8rem; line-height: 1.4; color: var(--color-text-secondary-dark);">
                                    <?php echo esc(substr($t['review'], 0, 75)) . (strlen($t['review']) > 75 ? '...' : ''); ?>
                                </td>
                                <td><?php echo esc($t['display_order']); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $t['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this testimonial?');">Delete</a>
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
