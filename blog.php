<?php
/**
 * WebFalx Blog Listing & Main Landing Page
 * Dynamic Article grids, Search filters, Category lists, Tag clouds, and Newsletter subscription handles
 */

require_once __DIR__ . '/includes/functions.php';

// Handle Newsletter Subscription
$subscriber_success = '';
$subscriber_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    try {
        require_csrf_token();
        $sub_email = sanitize_input($_POST['newsletter_email'] ?? '');
        
        if (!filter_var($sub_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        if ($db === null) {
            throw new Exception("Database is offline.");
        }

        // Check duplicate
        $chk = $db->prepare("SELECT COUNT(*) FROM newsletter_subscribers WHERE email = :email");
        $chk->execute(['email' => $sub_email]);
        if ($chk->fetchColumn() > 0) {
            throw new Exception("You are already subscribed to our newsletter.");
        }

        // Insert
        $ins = $db->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:email)");
        $ins->execute(['email' => $sub_email]);
        
        $subscriber_success = "Thank you! You have successfully subscribed to our newsletter.";
    } catch (Exception $ex) {
        $subscriber_error = $ex->getMessage();
    }
}

// 1. Core database queries
$featured_post = null;
$latest_posts = [];
$categories = [];
$popular_posts = [];
$all_tags = [];

if ($db !== null) {
    try {
        // Fetch featured post
        $stmt = $db->query("SELECT p.*, a.name as author_name, c.name as category_name, c.slug as category_slug 
                            FROM blog_posts p 
                            LEFT JOIN blog_authors a ON p.author_id = a.id 
                            LEFT JOIN blog_categories c ON p.category_id = c.id 
                            WHERE p.status = 'published' AND p.is_featured = 1 
                            ORDER BY p.id DESC LIMIT 1");
        $featured_post = $stmt->fetch();

        // Fetch latest articles
        $stmt = $db->query("SELECT p.*, a.name as author_name, c.name as category_name, c.slug as category_slug 
                            FROM blog_posts p 
                            LEFT JOIN blog_authors a ON p.author_id = a.id 
                            LEFT JOIN blog_categories c ON p.category_id = c.id 
                            WHERE p.status = 'published' 
                            ORDER BY p.id DESC LIMIT 6");
        $latest_posts = $stmt->fetchAll();

        // Fetch popular articles
        $stmt = $db->query("SELECT p.*, a.name as author_name 
                            FROM blog_posts p 
                            LEFT JOIN blog_authors a ON p.author_id = a.id 
                            WHERE p.status = 'published' AND p.is_popular = 1 
                            ORDER BY p.id DESC LIMIT 3");
        $popular_posts = $stmt->fetchAll();

        // Fetch categories with post count
        $categories = $db->query("SELECT c.*, COUNT(p.id) as post_count 
                                  FROM blog_categories c 
                                  LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = 'published'
                                  WHERE c.is_active = 1 
                                  GROUP BY c.id 
                                  ORDER BY c.display_order ASC")->fetchAll();

        // Fetch tags
        $all_tags = $db->query("SELECT * FROM blog_tags WHERE is_active = 1 LIMIT 15")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch blog homepage resources: " . $e->getMessage());
    }
}

$page_seo = [
    'title' => 'WebFalx Agency Blog | High Performance Web Insights & SEO Tips',
    'description' => 'Read our latest insights on technical SEO schema formats, e-commerce conversion triggers, and custom database engineering guides.'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Banner Section -->
<section class="section blog-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">WEBFALX CMS JOURNAL</span>
            <h1 class="gradient-text" style="font-size: 3rem; margin-top: 0.5rem; line-height: 1.2;">Code Engineering & Cognitive CRO Insights</h1>
            <p style="font-size: 1.1rem; color: var(--color-text-secondary-dark); margin-top: 10px;">Articles on how lightweight applications and visual psychology drive organic business rankings.</p>
        </div>
    </div>
</section>

<!-- 2. Main Page Grid Section -->
<section class="section blog-body-grid" style="padding: var(--spacing-md) 0;">
    <div class="container">
        <div class="grid grid-2" style="grid-template-columns: 1.8fr 1fr; align-items: start; gap: var(--spacing-md);">
            
            <!-- Left Listing Side -->
            <div>
                <!-- A. Featured Post Layout -->
                <?php if ($featured_post): ?>
                    <h3 style="color: #ffffff; margin-bottom: 1rem; font-size: 1.5rem;">Featured Story</h3>
                    <div class="glass-card glow-card" style="padding: 0; overflow: hidden; margin-bottom: var(--spacing-lg);">
                        <div style="height: 320px; overflow: hidden;">
                            <img src="<?php echo esc_url($featured_post['featured_image']); ?>" alt="<?php echo esc_attr($featured_post['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="padding: var(--spacing-md);">
                            <span class="blog-meta-tag"><?php echo esc($featured_post['category_name']); ?></span>
                            <h2 style="font-size: 1.8rem; color: #ffffff; margin-top: 5px; margin-bottom: 10px;">
                                <a href="blog-details.php?slug=<?php echo esc($featured_post['slug']); ?>"><?php echo esc($featured_post['title']); ?></a>
                            </h2>
                            <p style="font-size: 0.95rem; line-height: 1.5; color: var(--color-text-secondary-dark); margin-bottom: var(--spacing-sm);"><?php echo esc($featured_post['excerpt']); ?></p>
                            
                            <div class="blog-meta-footer">
                                <span>By <?php echo esc($featured_post['author_name']); ?> &bull; <?php echo date('M d, Y', strtotime($featured_post['created_at'])); ?></span>
                                <span><?php echo esc($featured_post['reading_time']); ?> Mins Read</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- B. Latest Articles Grid -->
                <h3 style="color: #ffffff; margin-bottom: 1rem; font-size: 1.5rem;">Latest Publications</h3>
                <div class="grid grid-2" style="gap: var(--spacing-sm);">
                    <?php foreach ($latest_posts as $post): ?>
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
                                    <?php echo esc(substr($post['excerpt'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <div class="blog-meta-footer">
                                    <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                    <span><?php echo esc($post['reading_time']); ?> min read</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Sidebar Widgets -->
            <aside style="display: flex; flex-direction: column; gap: var(--spacing-md); position: sticky; top: 90px;">
                
                <!-- Search Widget -->
                <div class="glass-card blog-sidebar-box" style="padding: 1.25rem;">
                    <h3>Search Articles</h3>
                    <form action="search.php" method="GET" style="display: flex; gap: 8px;">
                        <input type="text" name="q" class="form-control" placeholder="Search keywords..." required style="min-height: auto; padding: 6px 12px; font-size: 0.88rem;">
                        <button type="submit" class="btn btn-primary" style="min-height: auto; padding: 6px 12px; font-size: 0.85rem; border-radius: var(--radius-sm);">Search</button>
                    </form>
                </div>

                <!-- Categories Widget -->
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

                <!-- Recent / Popular Widget -->
                <div class="glass-card blog-sidebar-box" style="padding: 1.25rem;">
                    <h3>Popular Publications</h3>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 10px; font-size: 0.88rem;">
                        <?php foreach ($popular_posts as $pop): ?>
                            <li>
                                <a href="blog-details.php?slug=<?php echo esc($pop['slug']); ?>" style="color: #ffffff; font-weight: 600; display: block; line-height: 1.3;"><?php echo esc($pop['title']); ?></a>
                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">By <?php echo esc($pop['author_name']); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Tags cloud -->
                <div class="glass-card blog-sidebar-box" style="padding: 1.25rem;">
                    <h3>Popular Tag Cloud</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <?php foreach ($all_tags as $tg): ?>
                            <a href="tag.php?slug=<?php echo esc($tg['slug']); ?>" style="font-size: 0.75rem; padding: 4px 8px; background: rgba(255,255,255,0.03); border: var(--border-glass); border-radius: var(--radius-sm); color: var(--color-text-secondary-dark);"><?php echo esc($tg['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Newsletter signup -->
                <div class="glass-card blog-sidebar-box newsletter-card" style="padding: 1.25rem; border-color: rgba(6,182,212,0.3); background: rgba(6,182,212,0.01);">
                    <h3>Subscribe Newsletter</h3>
                    <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark); margin-bottom: 12px; line-height: 1.4;">Get technical coding and conversions insights direct to your inbox weekly.</p>
                    
                    <?php if (!empty($subscriber_success)): ?>
                        <div class="alert alert-success" style="font-size: 0.75rem; padding: 6px 10px;"><?php echo esc($subscriber_success); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($subscriber_error)): ?>
                        <div class="alert alert-danger" style="font-size: 0.75rem; padding: 6px 10px;"><?php echo esc($subscriber_error); ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                        <input type="email" name="newsletter_email" class="form-control" placeholder="Enter email address" required style="min-height: auto; padding: 6px 10px; font-size: 0.82rem;">
                        <button type="submit" class="btn btn-primary" style="min-height: auto; padding: 6px 10px; font-size: 0.85rem; width: 100%;">Subscribe</button>
                    </form>
                </div>

            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
