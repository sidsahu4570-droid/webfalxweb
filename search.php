<?php
/**
 * WebFalx Search Articles Viewer
 * Displays dynamic blog list matching search keywords query
 */

require_once __DIR__ . '/includes/functions.php';

$query = sanitize_input($_GET['q'] ?? '');
$posts = [];

if (!empty($query) && $db !== null) {
    try {
        $stmt = $db->prepare("SELECT p.*, a.name as author_name, c.name as category_name 
                              FROM blog_posts p 
                              LEFT JOIN blog_authors a ON p.author_id = a.id 
                              LEFT JOIN blog_categories c ON p.category_id = c.id 
                              WHERE p.status = 'published' AND (p.title LIKE :q OR p.content LIKE :q OR p.excerpt LIKE :q OR a.name LIKE :q OR c.name LIKE :q)
                              ORDER BY p.id DESC");
        $stmt->execute(['q' => '%' . $query . '%']);
        $posts = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to query blog searches: " . $e->getMessage());
    }
}

$page_seo = [
    'title' => 'Search Results for "' . esc($query) . '" | WebFalx Journal',
    'description' => 'Browse articles matching keywords search: ' . esc($query)
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="section search-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal" style="text-align: center;">
        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">SEARCH PORTAL</span>
        <h1 style="color: #ffffff; font-size: 2.5rem; margin-top: 0.5rem;">Results: "<?php echo esc($query); ?>"</h1>
        <p style="margin-top: 10px;"><?php echo count($posts); ?> articles match your query parameters.</p>
    </div>
</section>

<section class="section search-body" style="padding-top: var(--spacing-xs);">
    <div class="container reveal">
        <?php if (empty($posts)): ?>
            <div style="text-align: center; padding: var(--spacing-lg) 0;">
                <p style="color: var(--color-text-muted-dark);">No posts found matching "<?php echo esc($query); ?>". Try searching other web keywords.</p>
                <form action="search.php" method="GET" style="display: flex; gap: 8px; max-width: 400px; margin: 20px auto 0 auto;">
                    <input type="text" name="q" class="form-control" placeholder="Search keywords..." required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
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
