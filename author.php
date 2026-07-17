<?php
/**
 * WebFalx Author Articles Viewer
 * Displays dynamic blog list written by a specific Author profile
 */

require_once __DIR__ . '/includes/functions.php';

$author_id = intval($_GET['id'] ?? 0);
$author = null;
$posts = [];

if ($author_id > 0 && $db !== null) {
    try {
        // Fetch author
        $stmt = $db->prepare("SELECT * FROM blog_authors WHERE id = :id AND is_active = 1 LIMIT 1");
        $stmt->execute(['id' => $author_id]);
        $author = $stmt->fetch();

        if ($author) {
            // Fetch posts
            $stmt = $db->prepare("SELECT p.*, a.name as author_name, c.name as category_name 
                                  FROM blog_posts p 
                                  LEFT JOIN blog_authors a ON p.author_id = a.id 
                                  LEFT JOIN blog_categories c ON p.category_id = c.id 
                                  WHERE p.status = 'published' AND p.author_id = :author_id 
                                  ORDER BY p.id DESC");
            $stmt->execute(['author_id' => $author_id]);
            $posts = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Failed to load author posts: " . $e->getMessage());
    }
}

if (!$author) {
    header('Location: ' . BASE_URL . 'blog.php');
    exit;
}

$page_seo = [
    'title' => 'Articles by ' . esc($author['name']) . ' | WebFalx Journal',
    'description' => esc($author['bio'])
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="section author-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal" style="max-width: 800px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: var(--spacing-xs);">
        <img src="<?php echo esc_url($author['image_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150'); ?>" alt="<?php echo esc_attr($author['name']); ?>" style="width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.05);">
        
        <h1 style="color: #ffffff; font-size: 2.25rem; margin-top: 0.25rem;"><?php echo esc($author['name']); ?></h1>
        <span style="font-size: 0.82rem; color: var(--color-accent); font-weight: 700; text-transform: uppercase;"><?php echo esc($author['designation']); ?></span>
        
        <p style="max-width: 600px; margin-top: 5px;"><?php echo esc($author['bio']); ?></p>
    </div>
</section>

<section class="section author-body" style="padding-top: var(--spacing-xs);">
    <div class="container reveal">
        <?php if (empty($posts)): ?>
            <div style="text-align: center; padding: var(--spacing-lg) 0;">
                <p style="color: var(--color-text-muted-dark);">No posts published by <?php echo esc($author['name']); ?> yet.</p>
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
