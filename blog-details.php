<?php
/**
 * WebFalx Blog Details Template
 * JSON-LD Schema markup, dynamic TOC sidebars, related posts recommendations, moderated nested comments, and social sharing anchors
 */

require_once __DIR__ . '/includes/functions.php';

// Resolve current post slug
$slug = sanitize_input($_GET['slug'] ?? '');
$post = null;

if (!empty($slug) && $db !== null) {
    try {
        $stmt = $db->prepare("SELECT p.*, a.name as author_name, a.designation as author_desig, a.bio as author_bio, a.image_url as author_img, c.name as category_name, c.slug as category_slug 
                              FROM blog_posts p 
                              LEFT JOIN blog_authors a ON p.author_id = a.id 
                              LEFT JOIN blog_categories c ON p.category_id = c.id 
                              WHERE p.slug = :slug AND p.status = 'published' LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $post = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to load blog details: " . $e->getMessage());
    }
}

if (!$post) {
    header('Location: ' . BASE_URL . 'blog.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle Reader Comments POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'add_comment') {
    try {
        // Honeypot spam check
        if (!empty($_POST['website_hp'])) {
            flash_message('comment_flash', 'Thank you for your comment! It is pending moderation.', 'success');
            header('Location: ' . BASE_URL . 'blog-details.php?slug=' . $post['slug']);
            exit;
        }

        require_csrf_token();
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $comment = sanitize_input($_POST['comment'] ?? '');
        $parent_id = intval($_POST['parent_id'] ?? 0);

        if (empty($name) || empty($email) || empty($comment)) {
            throw new Exception("Please fill out all required fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        if ($db === null) {
            throw new Exception("Database is offline.");
        }

        $stmt = $db->prepare("INSERT INTO blog_comments (post_id, parent_id, name, email, comment, status) VALUES (:pid, :parent, :name, :email, :comment, 'pending')");
        $stmt->execute([
            'pid' => $post['id'],
            'parent' => $parent_id > 0 ? $parent_id : null,
            'name' => $name,
            'email' => $email,
            'comment' => $comment
        ]);

        flash_message('comment_flash', 'Your comment has been submitted and is pending moderation.', 'success');
        header('Location: ' . BASE_URL . 'blog-details.php?slug=' . $post['slug']);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$flash_msg = flash_message('comment_flash');
if ($flash_msg) {
    $success_message = $flash_msg['message'];
}

// Fetch approved comments
$comments = [];
$related_posts = [];
$categories = [];

if ($db !== null) {
    try {
        // Fetch comments
        $stmt = $db->prepare("SELECT * FROM blog_comments WHERE post_id = :pid AND status = 'approved' ORDER BY id ASC");
        $stmt->execute(['pid' => $post['id']]);
        $comments = $stmt->fetchAll();

        // Fetch related posts (same category, exclude current)
        $stmt = $db->prepare("SELECT * FROM blog_posts WHERE category_id = :cat AND id != :id AND status = 'published' ORDER BY id DESC LIMIT 3");
        $stmt->execute(['cat' => $post['category_id'], 'id' => $post['id']]);
        $related_posts = $stmt->fetchAll();

        // Categories Directory
        $categories = $db->query("SELECT c.*, COUNT(p.id) as post_count FROM blog_categories c LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = 'published' WHERE c.is_active = 1 GROUP BY c.id ORDER BY c.display_order ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to load details support resources: " . $e->getMessage());
    }
}

// Dynamic JSON-LD Article Schema
$schema_markup = [
    "@context" => "https://schema.org",
    "@type" => "BlogPosting",
    "headline" => $post['title'],
    "image" => $post['featured_image'],
    "author" => [
        "@type" => "Person",
        "name" => $post['author_name']
    ],
    "publisher" => [
        "@type" => "Organization",
        "name" => APP_NAME,
        "logo" => [
            "@type" => "ImageObject",
            "url" => BASE_URL . "assets/images/logo.png"
        ]
    ],
    "datePublished" => date('c', strtotime($post['created_at'])),
    "description" => $post['excerpt']
];

$page_seo = [
    'title' => $post['meta_title'] ?: $post['title'] . ' | WebFalx Journal',
    'description' => $post['meta_description'] ?: $post['excerpt'],
    'schema_markup' => json_encode($schema_markup, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Article Hero Banner -->
<section class="section post-hero" style="padding: var(--spacing-xl) 0 var(--spacing-sm) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal">
        <div style="max-width: 900px; margin: 0 auto; text-align: center;">
            <a href="category.php?slug=<?php echo esc($post['category_slug']); ?>" style="color: var(--color-accent); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px;"><?php echo esc($post['category_name']); ?></a>
            
            <h1 style="font-size: 2.75rem; margin-top: 0.5rem; margin-bottom: var(--spacing-sm); color: #ffffff; line-height: 1.2;"><?php echo esc($post['title']); ?></h1>
            
            <div style="display: flex; gap: var(--spacing-sm); justify-content: center; font-size: 0.88rem; color: var(--color-text-secondary-dark); flex-wrap: wrap;">
                <span>By <?php echo esc($post['author_name']); ?></span>
                <span>&bull;</span>
                <span>Published: <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                <span>&bull;</span>
                <span><?php echo esc($post['reading_time']); ?> Min read</span>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Article Body columns -->
<section class="section post-body" style="padding: var(--spacing-sm) 0 var(--spacing-lg) 0;">
    <div class="container">
        <div class="grid grid-2" style="grid-template-columns: 1.8fr 1fr; align-items: start; gap: var(--spacing-md);">
            
            <!-- Left Content Column -->
            <div class="reveal-left">
                <!-- Cover Photo -->
                <div class="glass-card" style="padding: 0; overflow: hidden; margin-bottom: var(--spacing-md); border-radius: var(--radius-md);">
                    <img src="<?php echo esc_url($post['featured_image']); ?>" alt="<?php echo esc_attr($post['title']); ?>" style="width: 100%; height: auto; display: block;">
                </div>

                <!-- Rich text content -->
                <div class="post-content-rich" style="margin-bottom: var(--spacing-md);">
                    <?php echo $post['content']; // Dynamic formatted CMS rich text content ?>
                </div>

                <!-- Author Bio Section -->
                <?php if ($post['author_name']): ?>
                    <div class="glass-card" style="display: flex; gap: var(--spacing-sm); align-items: center; margin-bottom: var(--spacing-md); border-color: rgba(255,255,255,0.05); padding: 1.25rem;">
                        <img src="<?php echo esc_url($post['author_img'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150'); ?>" alt="<?php echo esc_attr($post['author_name']); ?>" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <h4 style="color: #ffffff; font-size: 1.1rem; margin-bottom: 2px;">Written By: <?php echo esc($post['author_name']); ?></h4>
                            <span style="font-size: 0.75rem; color: var(--color-accent); font-weight: 600; display: block; margin-bottom: 6px;"><?php echo esc($post['author_desig']); ?></span>
                            <p style="font-size: 0.85rem; line-height: 1.4; color: var(--color-text-secondary-dark);"><?php echo esc($post['author_bio']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Moderated Comments System -->
                <div class="glass-card" style="padding: 1.25rem; margin-bottom: var(--spacing-md);">
                    <h3 style="color: #ffffff; font-size: 1.35rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 6px;">Comments (<?php echo count($comments); ?>)</h3>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo esc($success_message); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo esc($error_message); ?></div>
                    <?php endif; ?>

                    <!-- Comments Listing (Parent-Child Hierarchy) -->
                    <div class="comments-list-wrapper">
                        <?php 
                        // Isolate parents and replies
                        $parents = array_filter($comments, function($c) { return $c['parent_id'] === null; });
                        if (empty($parents)):
                        ?>
                            <p style="color: var(--color-text-muted-dark); font-size: 0.88rem;">No comments approved yet. Be the first to share your thoughts!</p>
                        <?php else: ?>
                            <?php foreach ($parents as $p): ?>
                                <div class="comment-node">
                                    <div class="comment-avatar"><?php echo esc(substr($p['name'], 0, 1)); ?></div>
                                    <div class="comment-bubble">
                                        <div class="comment-meta">
                                            <strong style="color: #ffffff;"><?php echo esc($p['name']); ?></strong>
                                            <span><?php echo date('M d, Y', strtotime($p['created_at'])); ?></span>
                                        </div>
                                        <p style="font-size: 0.88rem; color: var(--color-text-secondary-dark);"><?php echo esc($p['comment']); ?></p>
                                        
                                        <!-- Inline reply button hook -->
                                        <div style="text-align: right; margin-top: 4px;">
                                            <a href="#comment-form" onclick="document.getElementById('comment_parent').value = <?php echo $p['id']; ?>;" style="font-size: 0.72rem; color: var(--color-accent); font-weight: 700;">Reply to this comment</a>
                                        </div>

                                        <!-- Render Sub replies child list -->
                                        <?php 
                                        $replies = array_filter($comments, function($c) use ($p) { return $c['parent_id'] == $p['id']; });
                                        if (!empty($replies)):
                                        ?>
                                            <div class="comment-replies">
                                                <?php foreach ($replies as $r): ?>
                                                    <div class="comment-node" style="margin-bottom: 10px;">
                                                        <div class="comment-avatar" style="width: 30px; height: 30px; font-size: 0.8rem;"><?php echo esc(substr($r['name'], 0, 1)); ?></div>
                                                        <div class="comment-bubble" style="background: rgba(255,255,255,0.01);">
                                                            <div class="comment-meta">
                                                                <strong style="color: #ffffff;"><?php echo esc($r['name']); ?></strong>
                                                                <span><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                                                            </div>
                                                            <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark);"><?php echo esc($r['comment']); ?></p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Add Comment Form -->
                    <h4 id="comment-form" style="color: #ffffff; margin-top: var(--spacing-md); margin-bottom: 8px;">Leave A Comment</h4>
                    <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                        <input type="hidden" name="action_type" value="add_comment">
                        <input type="hidden" name="parent_id" id="comment_parent" value="0">
                        
                        <!-- Honeypot Bot protection -->
                        <div class="contact-hp-field">
                            <label for="comment_hp">Leave empty</label>
                            <input type="text" name="website_hp" id="comment_hp">
                        </div>

                        <div class="grid grid-2" style="gap: 10px;">
                            <div class="form-group">
                                <label for="c_name">Full Name *</label>
                                <input type="text" name="name" id="c_name" class="form-control" required placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label for="c_email">Email Address *</label>
                                <input type="email" name="email" id="c_email" class="form-control" required placeholder="johndoe@email.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="c_body">Comment Message *</label>
                            <textarea name="comment" id="c_body" rows="4" class="form-control" required placeholder="Type your thoughts here..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-secondary" style="width: 100%; min-height: auto; padding: 10px;">Post Comment</button>
                    </form>
                </div>

            </div>

            <!-- Right Column: Sidebar Table of Contents & Recommendations -->
            <aside class="reveal-right" style="display: flex; flex-direction: column; gap: var(--spacing-md); position: sticky; top: 90px;">
                
                <!-- Dynamic Client-generated Table of Contents -->
                <div class="glass-card toc-box">
                    <h4>Table of Contents</h4>
                    <ul class="toc-box-list" style="margin-top: 6px;">
                        <!-- Automatically populated via initTableOfContents() in main.js -->
                    </ul>
                </div>

                <!-- Sidebar Categories Directory -->
                <div class="glass-card blog-sidebar-box" style="padding: 1.25rem;">
                    <h3>Categories Directory</h3>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
                        <?php foreach ($categories as $cat): ?>
                            <li style="display: flex; justify-content: space-between;">
                                <a href="category.php?slug=<?php echo esc($cat['slug']); ?>" style="color: var(--color-text-secondary-dark);"><?php echo esc($cat['name']); ?></a>
                                <span style="color: var(--color-text-muted-dark); font-size: 0.8rem;">(<?php echo $cat['post_count']; ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Conversion CTA banner -->
                <div class="glass-card glow-card" style="padding: 1.25rem; border-color: rgba(6,182,212,0.3); background: rgba(6,182,212,0.01); text-align: center;">
                    <h4 style="color: var(--color-accent); margin-bottom: 4px;">Need Code Assistance?</h4>
                    <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark); margin-bottom: 12px; line-height: 1.4;">WebFalx builds templates-free backend dashboards and speeds up pages load times.</p>
                    <a href="contact.php" class="btn btn-primary" style="min-height: auto; padding: 8px 12px; font-size: 0.82rem; width: 100%;">Get a Free Quote</a>
                </div>

            </aside>
        </div>
    </div>
</section>

<!-- 3. Related Posts Recommendation Grid -->
<?php if (!empty($related_posts)): ?>
    <section class="section related-posts-section" style="border-top: var(--border-glass); background: #090e1a; padding-bottom: var(--spacing-lg);">
        <div class="container reveal">
            <h3 style="color: #ffffff; margin-bottom: var(--spacing-md); font-size: 1.5rem;">Recommended Articles</h3>
            <div class="grid grid-3">
                <?php foreach ($related_posts as $rel): ?>
                    <div class="glass-card blog-card">
                        <div class="blog-thumb-box" style="height: 150px;">
                            <img src="<?php echo esc_url($rel['featured_image']); ?>" alt="<?php echo esc_attr($rel['title']); ?>">
                        </div>
                        <div style="padding: 12px; display: flex; flex-direction: column; flex: 1;">
                            <h4 style="font-size: 1rem; color: #ffffff; margin-bottom: 6px;">
                                <a href="blog-details.php?slug=<?php echo esc($rel['slug']); ?>"><?php echo esc($rel['title']); ?></a>
                            </h4>
                            <div class="blog-meta-footer">
                                <span><?php echo date('M d, Y', strtotime($rel['created_at'])); ?></span>
                                <span><?php echo esc($rel['reading_time']); ?> min read</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 4. Mobile Sticky Share Bar (Prompt 7) -->
<div class="sticky-share-bar">
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(BASE_URL . 'blog-details.php?slug=' . $post['slug']); ?>" target="_blank" rel="noopener" style="color: #fff; font-size: 0.85rem;">FB</a>
    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(BASE_URL . 'blog-details.php?slug=' . $post['slug']); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" rel="noopener" style="color: #fff; font-size: 0.85rem;">X</a>
    <a href="https://www.linkedin.com/shareArticle?url=<?php echo urlencode(BASE_URL . 'blog-details.php?slug=' . $post['slug']); ?>" target="_blank" rel="noopener" style="color: #fff; font-size: 0.85rem;">LN</a>
    <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' ' . BASE_URL . 'blog-details.php?slug=' . $post['slug']); ?>" target="_blank" rel="noopener" style="color: #fff; font-size: 0.85rem;">WA</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
