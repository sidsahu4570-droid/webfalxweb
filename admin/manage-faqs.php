<?php
/**
 * WebFalx Admin FAQs Manager
 * FAQs Accordion CRUD operations
 */

$page_seo = [
    'title' => 'Manage FAQs | WebFalx Admin'
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
            $stmt = $db->prepare("DELETE FROM faqs WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            flash_message('faq_flash', 'FAQ item deleted successfully.', 'success');
            header('Location: ' . BASE_URL . 'admin/manage-faqs.php');
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to delete FAQ: ' . $e->getMessage();
    }
}

// Handle Add Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $question = sanitize_input($_POST['question'] ?? '');
        $answer = sanitize_input($_POST['answer'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if (empty($question) || empty($answer)) {
            $error_message = 'Please provide both the question and answer text.';
        } else {
            if ($db === null) {
                throw new Exception("Database is offline.");
            }
            
            $stmt = $db->prepare("INSERT INTO faqs (question, answer, is_active, display_order) VALUES (:quest, :answ, 1, :order)");
            $stmt->execute([
                'quest' => $question,
                'answ' => $answer,
                'order' => $display_order
            ]);
            
            flash_message('faq_flash', 'FAQ item added successfully.', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = 'Failed to save FAQ: ' . $e->getMessage();
    }
}

// Fetch all FAQs
$faqs = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT * FROM faqs ORDER BY display_order ASC");
        $faqs = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch FAQs: " . $e->getMessage());
    }
}
?>

<h3 style="margin-bottom: var(--spacing-sm);">Manage FAQs Accordion</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Modify public-facing queries displayed in the collapsible accordions list.</p>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in">
        <?php echo esc($error_message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.5fr;">
    <!-- Add New FAQ Card -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add New Accordion Question</h4>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
            
            <div class="form-group">
                <label for="question">Question</label>
                <input type="text" name="question" id="question" class="form-control" required placeholder="e.g. Do we own the final source code?">
            </div>
            
            <div class="form-group">
                <label for="answer">Detailed Answer</label>
                <textarea name="answer" id="answer" rows="5" class="form-control" required placeholder="Provide the conversion-optimized answer..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" name="display_order" id="display_order" class="form-control" value="10" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Accordion</button>
        </form>
    </div>
    
    <!-- FAQs List Table -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Active FAQ List</h4>
        
        <div class="table-responsive" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Answer Summary</th>
                        <th style="width: 60px;">Sequence</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faqs)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark);">No FAQ items entered yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($faqs as $f): ?>
                            <tr>
                                <td>
                                    <strong style="color: #ffffff;"><?php echo esc($f['question']); ?></strong>
                                </td>
                                <td style="font-size: 0.8rem; line-height: 1.4; color: var(--color-text-secondary-dark);">
                                    <?php echo esc(substr($f['answer'], 0, 75)) . (strlen($f['answer']) > 75 ? '...' : ''); ?>
                                </td>
                                <td><?php echo esc($f['display_order']); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $f['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this FAQ item?');">Delete</a>
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
