<?php
/**
 * WebFalx Admin Blog CMS Manager
 * Integrated administration for articles cloning, tag merging, categories SEO, comments moderation, and subscriber listings
 */

$page_seo = [
    'title' => 'Blog CMS Console | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$active_tab = sanitize_input($_GET['tab'] ?? 'articles');
$error_message = '';
$success_message = '';

if ($db === null) {
    die("Database is offline.");
}

// 1. Handle Deletes, Duplicates, and Approvals (GET Actions)
if (isset($_GET['action'])) {
    try {
        $action = sanitize_input($_GET['action']);
        $id = intval($_GET['id'] ?? 0);
        
        if ($id > 0) {
            // DUPLICATE ARTICLE
            if ($action === 'duplicate' && $active_tab === 'articles') {
                $post = $db->query("SELECT * FROM blog_posts WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
                if ($post) {
                    $new_title = $post['title'] . ' (Copy)';
                    $new_slug = $post['slug'] . '-copy-' . rand(100, 999);
                    
                    $stmt = $db->prepare("INSERT INTO blog_posts (author_id, category_id, title, slug, excerpt, content, featured_image, gallery_json, video_url, download_file, reading_time, meta_title, meta_description, focus_keyword, canonical_url, robots_meta, status, is_featured, is_popular, is_trending) 
                                          VALUES (:aid, :cid, :title, :slug, :excerpt, :content, :img, :gal, :vid, :dl, :read, :mtitle, :mdesc, :fkey, :canon, :robots, 'draft', 0, 0, 0)");
                    $stmt->execute([
                        'aid' => $post['author_id'],
                        'cid' => $post['category_id'],
                        'title' => $new_title,
                        'slug' => $new_slug,
                        'excerpt' => $post['excerpt'],
                        'content' => $post['content'],
                        'img' => $post['featured_image'],
                        'gal' => $post['gallery_json'],
                        'vid' => $post['video_url'],
                        'dl' => $post['download_file'],
                        'read' => $post['reading_time'],
                        'mtitle' => $post['meta_title'],
                        'mdesc' => $post['meta_description'],
                        'fkey' => $post['focus_keyword'],
                        'canon' => $post['canonical_url'],
                        'robots' => $post['robots_meta']
                    ]);
                    
                    flash_message('cms_flash', 'Article cloned successfully as draft.', 'success');
                    header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=articles');
                    exit;
                }
            }
            
            // DELETE ARTICLE
            elseif ($action === 'delete_post' && $active_tab === 'articles') {
                $db->prepare("DELETE FROM blog_posts WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Article deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=articles');
                exit;
            }
            
            // DELETE CATEGORY
            elseif ($action === 'delete_cat' && $active_tab === 'categories') {
                $db->prepare("DELETE FROM blog_categories WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Category deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=categories');
                exit;
            }
            
            // DELETE TAG
            elseif ($action === 'delete_tag' && $active_tab === 'tags') {
                $db->prepare("DELETE FROM blog_tags WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Tag deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=tags');
                exit;
            }

            // DELETE AUTHOR
            elseif ($action === 'delete_author' && $active_tab === 'authors') {
                $db->prepare("DELETE FROM blog_authors WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Author deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=authors');
                exit;
            }
            
            // COMMENT MODERATIONS
            elseif ($action === 'approve_comment' && $active_tab === 'comments') {
                $db->prepare("UPDATE blog_comments SET status = 'approved' WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Comment approved.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=comments');
                exit;
            }
            elseif ($action === 'reject_comment' && $active_tab === 'comments') {
                $db->prepare("UPDATE blog_comments SET status = 'rejected' WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Comment rejected.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=comments');
                exit;
            }
            elseif ($action === 'delete_comment' && $active_tab === 'comments') {
                $db->prepare("DELETE FROM blog_comments WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Comment deleted.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=comments');
                exit;
            }

            // DELETE SUBSCRIBER
            elseif ($action === 'delete_sub' && $active_tab === 'newsletter') {
                $db->prepare("DELETE FROM newsletter_subscribers WHERE id = :id")->execute(['id' => $id]);
                flash_message('cms_flash', 'Subscriber removed.', 'success');
                header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=newsletter');
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = 'Failed action: ' . $e->getMessage();
    }
}

// 2. Handle Export Subscribers CSV
if (isset($_GET['export']) && $_GET['export'] === 'subs') {
    try {
        $stmt = $db->query("SELECT email, created_at FROM newsletter_subscribers ORDER BY id DESC");
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Ymd') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email Address', 'Subscription Date']);
        foreach ($subs as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    } catch (Exception $e) {
        echo 'Export failed: ' . $e->getMessage();
        exit;
    }
}

// 3. Handle POST Actions (Forms Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        $action_type = sanitize_input($_POST['action_type'] ?? '');
        
        // Add / Edit Blog Post
        if ($action_type === 'save_post') {
            $post_id = intval($_POST['post_id'] ?? 0);
            $title = sanitize_input($_POST['title'] ?? '');
            $slug_input = sanitize_input($_POST['slug'] ?? '');
            $excerpt = sanitize_input($_POST['excerpt'] ?? '');
            $content = $_POST['content'] ?? ''; // Rich content spacing
            $image = sanitize_input($_POST['featured_image'] ?? '');
            $author = intval($_POST['author_id'] ?? 0);
            $category = intval($_POST['category_id'] ?? 0);
            $read_time = intval($_POST['reading_time'] ?? 5);
            
            $mtitle = sanitize_input($_POST['meta_title'] ?? '');
            $mdesc = sanitize_input($_POST['meta_description'] ?? '');
            $fkey = sanitize_input($_POST['focus_keyword'] ?? '');
            $canon = sanitize_input($_POST['canonical_url'] ?? '');
            $robots = sanitize_input($_POST['robots_meta'] ?? 'index, follow');
            $status = sanitize_input($_POST['status'] ?? 'published');
            
            $featured = isset($_POST['is_featured']) ? 1 : 0;
            $popular = isset($_POST['is_popular']) ? 1 : 0;
            
            if (empty($title) || empty($content)) {
                throw new Exception("Title and content content are required.");
            }
            
            if (empty($slug_input)) {
                $slug_input = slugify($title);
            }
            
            if ($post_id > 0) {
                // UPDATE
                $stmt = $db->prepare("UPDATE blog_posts SET author_id = :aid, category_id = :cid, title = :title, slug = :slug, excerpt = :excerpt, content = :content, featured_image = :img, reading_time = :read, meta_title = :mtitle, meta_description = :mdesc, focus_keyword = :fkey, canonical_url = :canon, robots_meta = :robots, status = :status, is_featured = :feat, is_popular = :pop WHERE id = :id");
                $stmt->execute([
                    'aid' => $author ?: null, 'cid' => $category ?: null, 'title' => $title, 'slug' => $slug_input,
                    'excerpt' => $excerpt, 'content' => $content, 'img' => $image, 'read' => $read_time,
                    'mtitle' => $mtitle, 'mdesc' => $mdesc, 'fkey' => $fkey, 'canon' => $canon, 'robots' => $robots,
                    'status' => $status, 'feat' => $featured, 'pop' => $popular, 'id' => $post_id
                ]);
                flash_message('cms_flash', 'Article updated.', 'success');
            } else {
                // INSERT
                $stmt = $db->prepare("INSERT INTO blog_posts (author_id, category_id, title, slug, excerpt, content, featured_image, reading_time, meta_title, meta_description, focus_keyword, canonical_url, robots_meta, status, is_featured, is_popular) 
                                      VALUES (:aid, :cid, :title, :slug, :excerpt, :content, :img, :read, :mtitle, :mdesc, :fkey, :canon, :robots, :status, :feat, :pop)");
                $stmt->execute([
                    'aid' => $author ?: null, 'cid' => $category ?: null, 'title' => $title, 'slug' => $slug_input,
                    'excerpt' => $excerpt, 'content' => $content, 'img' => $image, 'read' => $read_time,
                    'mtitle' => $mtitle, 'mdesc' => $mdesc, 'fkey' => $fkey, 'canon' => $canon, 'robots' => $robots,
                    'status' => $status, 'feat' => $featured, 'pop' => $popular
                ]);
                flash_message('cms_flash', 'New article published.', 'success');
            }
        }
        
        // Add Category
        elseif ($action_type === 'add_category') {
            $name = sanitize_input($_POST['name'] ?? '');
            $slug_input = sanitize_input($_POST['slug'] ?? '');
            $desc = sanitize_input($_POST['description'] ?? '');
            $img = sanitize_input($_POST['image_url'] ?? '');
            $order = intval($_POST['display_order'] ?? 10);
            
            if (empty($name)) throw new Exception("Category name is required.");
            if (empty($slug_input)) $slug_input = slugify($name);
            
            $stmt = $db->prepare("INSERT INTO blog_categories (name, slug, description, image_url, display_order) VALUES (:name, :slug, :desc, :img, :order)");
            $stmt->execute(['name' => $name, 'slug' => $slug_input, 'desc' => $desc, 'img' => $img, 'order' => $order]);
            flash_message('cms_flash', 'Category added.', 'success');
        }

        // Add Tag
        elseif ($action_type === 'add_tag') {
            $name = sanitize_input($_POST['name'] ?? '');
            $slug_input = sanitize_input($_POST['slug'] ?? '');
            if (empty($name)) throw new Exception("Tag name is required.");
            if (empty($slug_input)) $slug_input = slugify($name);

            $stmt = $db->prepare("INSERT INTO blog_tags (name, slug) VALUES (:name, :slug)");
            $stmt->execute(['name' => $name, 'slug' => $slug_input]);
            flash_message('cms_flash', 'Tag saved.', 'success');
        }

        // MERGE TAGS (Prompt 7 Tag Merging)
        elseif ($action_type === 'merge_tags') {
            $source = intval($_POST['tag_id_source'] ?? 0);
            $target = intval($_POST['tag_id_target'] ?? 0);

            if ($source <= 0 || $target <= 0 || $source === $target) {
                throw new Exception("Please select two distinct tags to merge.");
            }

            // Remap post associations
            $remap = $db->prepare("UPDATE IGNORE blog_post_tags SET tag_id = :target WHERE tag_id = :source");
            $remap->execute(['target' => $target, 'source' => $source]);

            // Clear duplicates that failed IGNORE remapping due to PRIMARY KEY
            $clear = $db->prepare("DELETE FROM blog_post_tags WHERE tag_id = :source");
            $clear->execute(['source' => $source]);

            // Delete source tag
            $del = $db->prepare("DELETE FROM blog_tags WHERE id = :source");
            $del->execute(['source' => $source]);

            flash_message('cms_flash', 'Tags merged successfully.', 'success');
        }

        // Add Author
        elseif ($action_type === 'add_author') {
            $name = sanitize_input($_POST['name'] ?? '');
            $desig = sanitize_input($_POST['designation'] ?? '');
            $bio = sanitize_input($_POST['bio'] ?? '');
            $img = sanitize_input($_POST['image_url'] ?? '');
            
            if (empty($name)) throw new Exception("Author name is required.");

            $stmt = $db->prepare("INSERT INTO blog_authors (name, designation, bio, image_url) VALUES (:name, :desig, :bio, :img)");
            $stmt->execute(['name' => $name, 'desig' => $desig, 'bio' => $bio, 'img' => $img]);
            flash_message('cms_flash', 'Author profile saved.', 'success');
        }

        // Post Admin reply
        elseif ($action_type === 'reply_comment') {
            $pid = intval($_POST['post_id'] ?? 0);
            $parent = intval($_POST['parent_id'] ?? 0);
            $comment = sanitize_input($_POST['comment'] ?? '');

            if ($pid > 0 && $parent > 0 && !empty($comment)) {
                $stmt = $db->prepare("INSERT INTO blog_comments (post_id, parent_id, name, email, comment, status) VALUES (:pid, :parent, 'Admin Desk', 'admin@webfalx.com', :comment, 'approved')");
                $stmt->execute(['pid' => $pid, 'parent' => $parent, 'comment' => $comment]);
                flash_message('cms_flash', 'Admin reply posted.', 'success');
            }
        }
        
        header('Location: ' . BASE_URL . 'admin/manage-blogs.php?tab=' . $active_tab);
        exit;
    } catch (Exception $ex) {
        $error_message = $ex->getMessage();
    }
}

// 4. Fetch Lists based on active tab
$posts = [];
$categories = [];
$tags = [];
$authors = [];
$comments = [];
$subscribers = [];

try {
    $categories = $db->query("SELECT * FROM blog_categories ORDER BY display_order ASC")->fetchAll();
    $authors = $db->query("SELECT * FROM blog_authors ORDER BY name ASC")->fetchAll();
    $tags = $db->query("SELECT * FROM blog_tags ORDER BY name ASC")->fetchAll();

    if ($active_tab === 'articles') {
        $posts = $db->query("SELECT p.*, a.name as author_name, c.name as category_name FROM blog_posts p LEFT JOIN blog_authors a ON p.author_id = a.id LEFT JOIN blog_categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
    } elseif ($active_tab === 'comments') {
        $comments = $db->query("SELECT c.*, p.title as post_title FROM blog_comments c JOIN blog_posts p ON c.post_id = p.id ORDER BY c.id DESC")->fetchAll();
    } elseif ($active_tab === 'newsletter') {
        $subscribers = $db->query("SELECT * FROM newsletter_subscribers ORDER BY id DESC")->fetchAll();
    }
} catch (PDOException $e) {
    error_log("CMS loading lists error: " . $e->getMessage());
}

$flash_msg = flash_message('cms_flash');
if ($flash_msg) {
    $success_message = $flash_msg['message'];
}

// Handle Single Article Edit Query
$edit_post = null;
if (isset($_GET['edit_id']) && $active_tab === 'articles') {
    $eid = intval($_GET['edit_id']);
    $edit_post = $db->query("SELECT * FROM blog_posts WHERE id = $eid")->fetch();
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">Blog CMS & Content Manager</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Create SEO articles, reorder categories, moderate comment queues, and manage email subscribers.</p>

<!-- CMS Tabs Navigation -->
<div class="admin-tab-group">
    <a href="?tab=articles" class="admin-tab-btn <?php echo $active_tab === 'articles' ? 'active' : ''; ?>">Articles Manager</a>
    <a href="?tab=categories" class="admin-tab-btn <?php echo $active_tab === 'categories' ? 'active' : ''; ?>">Categories</a>
    <a href="?tab=tags" class="admin-tab-btn <?php echo $active_tab === 'tags' ? 'active' : ''; ?>">Tags & Merging</a>
    <a href="?tab=authors" class="admin-tab-btn <?php echo $active_tab === 'authors' ? 'active' : ''; ?>">Authors</a>
    <a href="?tab=comments" class="admin-tab-btn <?php echo $active_tab === 'comments' ? 'active' : ''; ?>">Comments Moderation</a>
    <a href="?tab=newsletter" class="admin-tab-btn <?php echo $active_tab === 'newsletter' ? 'active' : ''; ?>">Subscribers List</a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
<?php endif; ?>

<!-- ==========================================
     TAB 1: ARTICLES MANAGER
     ========================================== -->
<?php if ($active_tab === 'articles'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1.3fr 1fr;">
        <!-- Add / Edit Article Form -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);"><?php echo $edit_post ? 'Edit Article' : 'Create Article'; ?></h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="save_post">
                <input type="hidden" name="post_id" value="<?php echo $edit_post ? $edit_post['id'] : '0'; ?>">

                <div class="form-group">
                    <label for="post_title">Article Title</label>
                    <input type="text" name="title" id="post_title" class="form-control" value="<?php echo esc_attr($edit_post['title'] ?? ''); ?>" required placeholder="e.g. How Page Load Speed Drives Conversions">
                </div>

                <div class="form-group">
                    <label for="post_slug">URL Slug (Leave blank to auto-generate)</label>
                    <input type="text" name="slug" id="post_slug" class="form-control" value="<?php echo esc_attr($edit_post['slug'] ?? ''); ?>" placeholder="how-page-speed-drives-conversions">
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="post_cat">Category</label>
                        <select name="category_id" id="post_cat" class="form-control" style="background: var(--color-bg-dark);">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($edit_post['category_id']) && $edit_post['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo esc($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="post_author">Author</label>
                        <select name="author_id" id="post_author" class="form-control" style="background: var(--color-bg-dark);">
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $auth): ?>
                                <option value="<?php echo $auth['id']; ?>" <?php echo (isset($edit_post['author_id']) && $edit_post['author_id'] == $auth['id']) ? 'selected' : ''; ?>><?php echo esc($auth['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="post_image">Featured Cover Image URL</label>
                        <input type="text" name="featured_image" id="post_image" class="form-control" value="<?php echo esc_attr($edit_post['featured_image'] ?? 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=600'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="post_read">Reading Time (Minutes)</label>
                        <input type="number" name="reading_time" id="post_read" class="form-control" value="<?php echo esc_attr($edit_post['reading_time'] ?? '5'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="post_excerpt">Excerpt Summary</label>
                    <textarea name="excerpt" id="post_excerpt" rows="2" class="form-control" placeholder="Brief metadata description for lists..."><?php echo esc($edit_post['excerpt'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="post_content">Article Body (Supports HTML formats)</label>
                    <textarea name="content" id="post_content" rows="12" class="form-control" style="font-family: monospace;" required placeholder="<h2>Heading 2</h2><p>Article body content text...</p>"><?php echo esc($edit_post['content'] ?? ''); ?></textarea>
                </div>

                <h5 style="color: var(--color-accent); margin-top: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 4px;">Search Optimization (SEO Tags)</h5>
                
                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="post_mtitle">Meta Title tag</label>
                        <input type="text" name="meta_title" id="post_mtitle" class="form-control" value="<?php echo esc_attr($edit_post['meta_title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="post_fkey">Focus Keyword</label>
                        <input type="text" name="focus_keyword" id="post_fkey" class="form-control" value="<?php echo esc_attr($edit_post['focus_keyword'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="post_mdesc">Meta Description tag</label>
                    <textarea name="meta_description" id="post_mdesc" rows="2" class="form-control"><?php echo esc($edit_post['meta_description'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="post_canon">Canonical URL link</label>
                        <input type="text" name="canonical_url" id="post_canon" class="form-control" value="<?php echo esc_attr($edit_post['canonical_url'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="post_robots">Robots Meta tag</label>
                        <input type="text" name="robots_meta" id="post_robots" class="form-control" value="<?php echo esc_attr($edit_post['robots_meta'] ?? 'index, follow'); ?>">
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="post_status">Publishing Status</label>
                        <select name="status" id="post_status" class="form-control" style="background: var(--color-bg-dark);">
                            <option value="published" <?php echo (isset($edit_post['status']) && $edit_post['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo (isset($edit_post['status']) && $edit_post['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="scheduled" <?php echo (isset($edit_post['status']) && $edit_post['status'] == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="archived" <?php echo (isset($edit_post['status']) && $edit_post['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; gap: 15px; align-items: center; padding-top: 25px;">
                        <label style="display: inline-flex; align-items: center; gap: 4px; text-transform: none; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="is_featured" value="1" <?php echo (isset($edit_post['is_featured']) && $edit_post['is_featured'] == 1) ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                            Featured Story
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 4px; text-transform: none; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="is_popular" value="1" <?php echo (isset($edit_post['is_popular']) && $edit_post['is_popular'] == 1) ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                            Popular List
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Article Details</button>
                <?php if ($edit_post): ?>
                    <a href="manage-blogs.php?tab=articles" class="btn btn-secondary" style="text-align: center;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Articles list -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Published Articles</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Article Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($p['title']); ?></strong><br>
                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Cat: <?php echo esc($p['category_name']); ?> | Author: <?php echo esc($p['author_name']); ?></small>
                            </td>
                            <td>
                                <span class="status-badge" style="background: rgba(255,255,255,0.05); font-size: 0.7rem; color: #fff;"><?php echo esc($p['status']); ?></span>
                            </td>
                            <td style="font-size: 0.8rem; display: flex; flex-direction: column; gap: 4px;">
                                <a href="?edit_id=<?php echo $p['id']; ?>&tab=articles" class="action-link">Edit Details</a>
                                <a href="?action=duplicate&id=<?php echo $p['id']; ?>&tab=articles" class="action-link" style="color: var(--color-secondary);">Clone Post</a>
                                <a href="?action=delete_post&id=<?php echo $p['id']; ?>&tab=articles" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this article?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 2: CATEGORIES
     ========================================== -->
<?php if ($active_tab === 'categories'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.3fr;">
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Category Cover</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_category">

                <div class="form-group">
                    <label for="cat_name">Category Name</label>
                    <input type="text" name="name" id="cat_name" class="form-control" required placeholder="e.g. Design Insights">
                </div>
                <div class="form-group">
                    <label for="cat_slug">Slug (Optional)</label>
                    <input type="text" name="slug" id="cat_slug" class="form-control" placeholder="design-insights">
                </div>
                <div class="form-group">
                    <label for="cat_image">Cover Image URL</label>
                    <input type="text" name="image_url" id="cat_image" class="form-control" placeholder="https://images.unsplash.com/...">
                </div>
                <div class="form-group">
                    <label for="cat_desc">Brief Description</label>
                    <textarea name="description" id="cat_desc" rows="3" class="form-control" placeholder="Insights on minimalist branding..."></textarea>
                </div>
                <div class="form-group">
                    <label for="cat_order">Display Order</label>
                    <input type="number" name="display_order" id="cat_order" class="form-control" value="10">
                </div>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </form>
        </div>

        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Categories Directory</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><strong><?php echo esc($c['name']); ?></strong></td>
                            <td><code><?php echo esc($c['slug']); ?></code></td>
                            <td>
                                <a href="?action=delete_cat&id=<?php echo $c['id']; ?>&tab=categories" class="action-link action-delete" onclick="return confirm('Delete this category?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 3: TAGS & MERGING
     ========================================== -->
<?php if ($active_tab === 'tags'): ?>
    <div class="grid grid-3" style="gap: var(--spacing-md); align-items: start;">
        <!-- Add Tag Form -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Article Tag</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_tag">

                <div class="form-group">
                    <label for="tg_name">Tag Name</label>
                    <input type="text" name="name" id="tg_name" class="form-control" required placeholder="e.g. Core Web Vitals">
                </div>
                <div class="form-group">
                    <label for="tg_slug">Slug (Optional)</label>
                    <input type="text" name="slug" id="tg_slug" class="form-control" placeholder="core-web-vitals">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Tag</button>
            </form>
        </div>

        <!-- Merge Tags Form (Prompt 7 Tag Merge) -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Merge Duplicate Tags</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="merge_tags">

                <div class="form-group">
                    <label for="tag_source">Source Tag (Will be DELETED)</label>
                    <select name="tag_id_source" id="tag_source" class="form-control" style="background: var(--color-bg-dark);">
                        <option value="">Select Tag to remove</option>
                        <?php foreach ($tags as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo esc($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tag_target">Target Tag (Will KEEP posts)</label>
                    <select name="tag_id_target" id="tag_target" class="form-control" style="background: var(--color-bg-dark);">
                        <option value="">Select Tag to keep</option>
                        <?php foreach ($tags as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo esc($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%; border-color: var(--color-secondary); color: #fff;" onclick="return confirm('Are you sure you want to merge and delete the source tag? Post mappings will transfer.');">Merge Tag records</button>
            </form>
        </div>

        <!-- Tags List table -->
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Tag Cloud List</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tags as $t): ?>
                        <tr>
                            <td><strong>#<?php echo esc($t['name']); ?></strong></td>
                            <td>
                                <a href="?action=delete_tag&id=<?php echo $t['id']; ?>&tab=tags" class="action-link action-delete" onclick="return confirm('Delete this tag?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 4: AUTHORS
     ========================================== -->
<?php if ($active_tab === 'authors'): ?>
    <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start; grid-template-columns: 1fr 1.3fr;">
        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Add Author profile</h4>
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                <input type="hidden" name="action_type" value="add_author">

                <div class="form-group">
                    <label for="au_name">Full Name</label>
                    <input type="text" name="name" id="au_name" class="form-control" required placeholder="Alex Rivera">
                </div>
                <div class="form-group">
                    <label for="au_desig">Designation</label>
                    <input type="text" name="designation" id="au_desig" class="form-control" placeholder="CTO & Senior Engineer">
                </div>
                <div class="form-group">
                    <label for="au_image">Profile Picture URL</label>
                    <input type="text" name="image_url" id="au_image" class="form-control" placeholder="https://images.unsplash.com/...">
                </div>
                <div class="form-group">
                    <label for="au_bio">Short Bio</label>
                    <textarea name="bio" id="au_bio" rows="3" class="form-control" placeholder="Alex writes technical code blueprints..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Author</button>
            </form>
        </div>

        <div class="glass-card" style="padding: 1.25rem;">
            <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Authors Profiles</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Author</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authors as $a): ?>
                        <tr>
                            <td><strong><?php echo esc($a['name']); ?></strong></td>
                            <td><?php echo esc($a['designation']); ?></td>
                            <td>
                                <a href="?action=delete_author&id=<?php echo $a['id']; ?>&tab=authors" class="action-link action-delete" onclick="return confirm('Delete this author?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 5: COMMENTS MODERATION
     ========================================== -->
<?php if ($active_tab === 'comments'): ?>
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="margin-bottom: 1rem; color: var(--color-accent);">Reader Feedback Moderation</h4>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Article / Reader</th>
                    <th>Comment Message</th>
                    <th>Status</th>
                    <th>Actions / Admin Reply</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--color-text-muted-dark); padding: var(--spacing-sm) 0;">No comments logged yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($comments as $comm): ?>
                        <tr>
                            <td>
                                <strong style="color: #ffffff; font-size: 0.82rem;"><?php echo esc($comm['post_title']); ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);"><?php echo esc($comm['name']); ?> (<?php echo esc($comm['email']); ?>)</span>
                            </td>
                            <td style="font-size: 0.82rem; max-width: 250px;">
                                <p style="color: var(--color-text-secondary-dark);"><?php echo esc($comm['comment']); ?></p>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $comm['status'] === 'approved' ? 'status-won' : ($comm['status'] === 'pending' ? 'status-new' : 'status-lost'); ?>" style="font-size: 0.65rem;">
                                    <?php echo esc($comm['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem; display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px;">
                                    <?php if ($comm['status'] !== 'approved'): ?>
                                        <a href="?action=approve_comment&id=<?php echo $comm['id']; ?>&tab=comments" style="color: var(--color-success);" class="action-link">Approve</a>
                                    <?php endif; ?>
                                    <?php if ($comm['status'] !== 'rejected'): ?>
                                        <a href="?action=reject_comment&id=<?php echo $comm['id']; ?>&tab=comments" style="color: var(--color-warning);" class="action-link">Reject</a>
                                    <?php endif; ?>
                                    <a href="?action=delete_comment&id=<?php echo $comm['id']; ?>&tab=comments" class="action-link action-delete" onclick="return confirm('Delete comment?');">Delete</a>
                                </div>

                                <!-- Inline Admin reply form -->
                                <?php if ($comm['parent_id'] === null && $comm['status'] === 'approved'): ?>
                                    <form action="" method="POST" style="display: flex; gap: 4px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                                        <input type="hidden" name="action_type" value="reply_comment">
                                        <input type="hidden" name="post_id" value="<?php echo $comm['post_id']; ?>">
                                        <input type="hidden" name="parent_id" value="<?php echo $comm['id']; ?>">
                                        
                                        <input type="text" name="comment" class="form-control" required placeholder="Type reply..." style="padding: 2px 6px; font-size: 0.75rem; min-height: auto;">
                                        <button type="submit" class="btn btn-secondary" style="padding: 2px 6px; font-size: 0.72rem; min-height: auto;">Reply</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- ==========================================
     TAB 6: NEWSLETTER SUBSCRIBERS
     ========================================== -->
<?php if ($active_tab === 'newsletter'): ?>
    <div class="glass-card" style="padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="color: var(--color-accent); margin-bottom: 0;">Subscribed Readers List</h4>
            <a href="?export=subs" class="btn btn-secondary" style="min-height: auto; padding: 6px 12px; font-size: 0.85rem;">Export CSV List</a>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Email Address</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--color-text-muted-dark); padding: var(--spacing-sm) 0;">No subscribers in database yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td><strong><?php echo esc($sub['email']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($sub['created_at'])); ?></td>
                            <td>
                                <a href="?action=delete_sub&id=<?php echo $sub['id']; ?>&tab=newsletter" class="action-link action-delete" onclick="return confirm('Remove subscriber email?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
