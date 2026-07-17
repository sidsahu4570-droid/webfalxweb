<?php
/**
 * WebFalx Tag Articles Viewer
 * Displays dynamic blog list filtered by Tag Slug
 */

require_once __DIR__ . '/includes/functions.php';

$slug = sanitize_input($_GET['slug'] ?? '');
$tag = null;
$posts = [];

if (!empty($slug) && $db !== null) {
    try {
        // Fetch tag details
        $stmt = $db->prepare("SELECT * FROM blog_tags WHERE slug = :slug AND is_active = 1 LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $tag = $stmt->fetch();

        if ($tag) {
            // Fetch articles
            $stmt = $db->prepare("SELECT p.*, a.name as author_name, c.name as category_name 
                                  FROM blog_posts p 
                                  LEFT JOIN blog_authors a ON p.author_id = a.id 
                                  LEFT JOIN blog_categories c ON p.category_id = c.id 
                                  JOIN blog_post_tags pt ON p.id = pt.post_id 
                                  WHERE p.status = 'published' AND pt.tag_id = :tag_id 
                                  ORDER BY p.id DESC");
            $stmt->execute(['tag_id' => $tag['id']]);
            $posts = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Failed to load tag post lists: " . $e->getMessage());
    }
}

if (!$tag) {
    header('Location: ' . BASE_URL . 'blog.php');
    exit;
}

$page_seo = [
    'title' => 'Articles tagged with ' . esc($tag['name']) . ' | WebFalx Journal',
    'description' => 'Browse articles focusing on ' . esc($tag['name']) . ' written by WebFalx developers.'
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="section tag-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal" style="text-align: center;">
        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">TAG DIRECTORY</span>
        <h1 style="color: #ffffff; font-size: 2.5rem; margin-top: 0.5rem;">Tag: #<?php echo esc($tag['name']); ?></h1>
    </div>
</section>

<section class="section tag-body" style="padding-top: var(--spacing-xs);">
    <div class="container reveal">
        <?php if (empty($posts)): ?>
            <div style="text-align: center; padding: var(--spacing-lg) 0;">
                <p style="color: var(--color-text-muted-dark);">No posts tagged with #<?php echo esc($tag['name']); ?> yet.</p>
                <a href="blog.php" class="btn btn-secondary" style="margin-top: 15px;">Back to Blog Home</a>
            </div>
        <?php else: ?>
            <div class="grid grid-3" style="gap: var(--spacing-sm);">
                <?php foreach ($posts as $post): ?>
                    <div class="glass-card blog-card">
                        <div class="blog-thumb-box">
                            <img src="<?php echo esc_url($post['featured_image']); ?>" alt="<?php echo esc_attr($post['title']); ?>">
                        </div>
                        <div style="padding: var(--spacing-sm); display: flex; flex-direction: column; flex: 1;">
                            <span class="blog-meta-tag"><?php echo esc($post['category_name']); ?></span>
                            <h4 style="font-size: 1.15rem; color: #ffffff; margin-top: 4px; margin-bottom: 8px;">
                                <a href="blog-details.php?slug=<?php echo esc($post['slug']); ?>"><?php echo esc($post['title']); ?></a>
                            </h4>
                            <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); line-height: 1.4; margin-bottom: 12px;">
                                <?php echo esc(substr($post['excerpt'], 0, 120)) . '...'; ?>
                            </p>
                            <div class="blog-meta-footer">
                                <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                <span><?php echo esc($post['reading_time']); ?> min read</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
