<?php
/**
 * WebFalx Admin Media manager library
 * Secure file upload portal, copy targets, and media grids filter directories
 */

$page_seo = [
    'title' => 'Media Manager | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success_message = '';
$error_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle Upload Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    try {
        require_csrf_token();
        
        if ($_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . $_FILES['media_file']['error']);
        }
        
        $uploadDir = __DIR__ . '/../assets/uploads/media/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
        $originalName = pathinfo($_FILES['media_file']['name'], PATHINFO_FILENAME);
        $cleanName = slugify($originalName) . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $cleanName;

        // Secure type constraints
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif', 'mp4', 'pdf', 'docx', 'zip'];
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            throw new Exception("File format extension not allowed.");
        }

        if ($_FILES['media_file']['size'] > 25 * 1024 * 1024) { // Max 25MB
            throw new Exception("File size exceeds max 25MB limit.");
        }

        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $targetPath)) {
            $relative_path = 'assets/uploads/media/' . $cleanName;
            
            // Insert into media catalog table
            $stmt = $db->prepare("INSERT INTO media_items (filename, file_path, file_type, file_size) VALUES (:name, :path, :type, :size)");
            $stmt->execute([
                'name' => $_FILES['media_file']['name'],
                'path' => $relative_path,
                'type' => $fileExtension,
                'size' => $_FILES['media_file']['size']
            ]);

            log_activity('Upload Media', 'File uploaded: ' . $_FILES['media_file']['name']);
            flash_message('media_flash', 'File uploaded successfully.', 'success');
        } else {
            throw new Exception("Failed to save uploaded file on server.");
        }

        header('Location: ' . BASE_URL . 'admin/manage-media.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 2. Handle Delete actions
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            $item = $db->query("SELECT * FROM media_items WHERE id = $id")->fetch();
            if ($item) {
                // Delete physical file
                $full_path = __DIR__ . '/../' . $item['file_path'];
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
                
                // Clear DB record
                $db->prepare("DELETE FROM media_items WHERE id = :id")->execute(['id' => $id]);
                
                log_activity('Delete Media', 'File deleted: ' . $item['filename']);
                flash_message('media_flash', 'Media item deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-media.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 3. Query Uploaded files lists
$media_items = [];
$filter_type = sanitize_input($_GET['type'] ?? '');

try {
    $query = "SELECT * FROM media_items";
    $params = [];
    
    if (!empty($filter_type)) {
        if ($filter_type === 'images') {
            $query .= " WHERE file_type IN ('png', 'jpg', 'jpeg', 'svg', 'webp', 'gif')";
        } elseif ($filter_type === 'documents') {
            $query .= " WHERE file_type IN ('pdf', 'docx', 'zip')";
        } elseif ($filter_type === 'videos') {
            $query .= " WHERE file_type = 'mp4'";
        }
    }
    
    $query .= " ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $media_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed listing media catalogs: " . $e->getMessage());
}

$flash = flash_message('media_flash');
if ($flash) {
    $success_message = $flash['message'];
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Media Library Manager</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Upload graphic assets, vector maps, PDF design guides, and copy references links for content layout edits.</p>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<!-- 1. Drag upload and list grids -->
<div class="grid grid-2" style="grid-template-columns: 1fr 2.5fr; gap: var(--spacing-md); align-items: start;">
    <!-- Upload Box -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Upload File</h4>
        <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 8px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group" style="border: 2px dashed rgba(255,255,255,0.08); padding: 1.5rem; text-align: center; border-radius: var(--radius-sm);">
                <label for="media_file" style="cursor: pointer; display: block; font-weight: 500; font-size: 0.88rem;">Select Media File</label>
                <input type="file" name="media_file" id="media_file" required style="margin-top: 10px; font-size: 0.8rem; width: 100%;">
                <small style="color: var(--color-text-muted-dark); display: block; margin-top: 8px; font-size: 0.72rem;">Max 25MB (Images, PDF, DOCX, ZIP, MP4)</small>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; min-height: auto; padding: 10px;">Upload File</button>
        </form>
    </div>

    <!-- Media Directory list -->
    <div class="glass-card" style="padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 1rem;">
            <h4 style="color: var(--color-accent); margin: 0;">Uploaded Assets</h4>
            
            <!-- Type Filters -->
            <div style="display: flex; gap: 4px;">
                <a href="?" class="btn btn-secondary" style="min-height: auto; padding: 4px 8px; font-size: 0.75rem;">All</a>
                <a href="?type=images" class="btn btn-secondary" style="min-height: auto; padding: 4px 8px; font-size: 0.75rem;">Images</a>
                <a href="?type=documents" class="btn btn-secondary" style="min-height: auto; padding: 4px 8px; font-size: 0.75rem;">Docs/ZIP</a>
                <a href="?type=videos" class="btn btn-secondary" style="min-height: auto; padding: 4px 8px; font-size: 0.75rem;">Videos</a>
            </div>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>File Details</th>
                    <th>Reference Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($media_items)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--color-text-muted-dark); padding: var(--spacing-sm) 0;">No media uploads found in database.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($media_items as $item): ?>
                        <tr>
                            <!-- Preview cell -->
                            <td style="width: 80px;">
                                <?php 
                                $isImg = in_array(strtolower($item['file_type']), ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif']);
                                if ($isImg):
                                ?>
                                    <img src="<?php echo BASE_URL . $item['file_path']; ?>" alt="" style="width: 60px; height: 45px; object-fit: cover; border-radius: var(--radius-sm); border: var(--border-glass);">
                                <?php else: ?>
                                    <div style="width: 60px; height: 45px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.02); border: var(--border-glass); border-radius: var(--radius-sm); font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--color-accent);">
                                        <?php echo esc($item['file_type']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- File specs details -->
                            <td>
                                <strong style="font-size: 0.88rem; color: #fff; word-break: break-all;"><?php echo esc($item['filename']); ?></strong>
                                <div style="font-size: 0.75rem; color: var(--color-text-muted-dark);"><?php echo round($item['file_size'] / 1024, 1); ?> KB</div>
                            </td>

                            <!-- Clipboard action -->
                            <td style="font-size: 0.8rem;">
                                <input type="text" value="<?php echo BASE_URL . $item['file_path']; ?>" readonly style="background: var(--color-bg-dark); border: var(--border-glass); color: #fff; font-size: 0.72rem; padding: 2px 6px; width: 100%; border-radius: var(--radius-sm); margin-bottom: 4px;" onclick="this.select(); document.execCommand('copy'); alert('URL Copied to clipboard!');">
                                <a href="?action=delete&id=<?php echo $item['id']; ?>" class="action-link action-delete" onclick="return confirm('Delete this media file permanently?');">Delete File</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
