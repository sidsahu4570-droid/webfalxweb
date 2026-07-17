<?php
/**
 * WebFalx Portfolio Index Page
 * Masonry Case Studies Grid, Category Filter Tabs, Real-Time Searches, and Dynamic Counters
 */

require_once __DIR__ . '/includes/functions.php';

// Fetch Portfolio Categories
$categories = [];
if ($db !== null) {
    try {
        $categories = $db->query("SELECT * FROM portfolio_categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch portfolio categories: " . $e->getMessage());
    }
}

// Fetch Active Portfolio Projects
$projects = [];
if ($db !== null) {
    try {
        $stmt = $db->prepare("SELECT p.*, c.slug as cat_slug, c.name as cat_name 
                              FROM portfolio_projects p 
                              LEFT JOIN portfolio_categories c ON p.category_id = c.id 
                              WHERE p.is_active = 1 
                              ORDER BY p.display_order ASC");
        $stmt->execute();
        $projects = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch portfolio projects: " . $e->getMessage());
    }
}

// Read Stats Settings
$total_proj = get_setting('stat_total_projects', '150');
$web_deliv = get_setting('stat_websites_delivered', '95');
$shop_store = get_setting('stat_shopify_stores', '35');
$crm_sys = get_setting('stat_crm_systems', '20');

$page_seo = [
    'title' => 'Portfolio of Completed Case Studies | WebFalx Agency',
    'description' => 'Explore our completed e-commerce Shopify sites, WordPress designs, and custom CRM software implementations designed for high conversions.'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Portfolio Hero Banner with counters -->
<section class="section portfolio-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%); overflow: hidden;">
    <div class="container reveal">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">PROVEN RESULTS</span>
            <h1 class="gradient-text" style="font-size: 3rem; margin-top: 0.5rem; line-height: 1.2;">Case Studies & Digital Success Metrics</h1>
            <p style="font-size: 1.1rem; margin-top: var(--spacing-sm); margin-bottom: var(--spacing-md);">Browse our database of bespoke websites, custom integrations, and online storefronts engineered to maximize company revenue.</p>
        </div>

        <!-- Inline Stats counters grid -->
        <div class="grid grid-4" style="margin-top: var(--spacing-md);">
            <div class="glass-card" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.25rem; font-family: var(--font-heading); font-weight: 800; color: #ffffff;">
                    <span class="counter-value" data-target="<?php echo esc_attr($total_proj); ?>">0</span>+
                </div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted-dark); letter-spacing: 0.5px;">Total Projects</div>
            </div>
            <div class="glass-card" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.25rem; font-family: var(--font-heading); font-weight: 800; color: var(--color-accent);">
                    <span class="counter-value" data-target="<?php echo esc_attr($web_deliv); ?>">0</span>+
                </div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted-dark); letter-spacing: 0.5px;">Websites Delivered</div>
            </div>
            <div class="glass-card" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.25rem; font-family: var(--font-heading); font-weight: 800; color: var(--color-secondary);">
                    <span class="counter-value" data-target="<?php echo esc_attr($shop_store); ?>">0</span>+
                </div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted-dark); letter-spacing: 0.5px;">Shopify Stores</div>
            </div>
            <div class="glass-card" style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.25rem; font-family: var(--font-heading); font-weight: 800; color: #ffffff;">
                    <span class="counter-value" data-target="<?php echo esc_attr($crm_sys); ?>">0</span>+
                </div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted-dark); letter-spacing: 0.5px;">CRM & Automations</div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Interactive Search & Filters Panel -->
<section class="section filter-panel-section" style="padding: var(--spacing-xs) 0; position: relative; z-index: 10;">
    <div class="container reveal">
        <div class="glass-card search-filter-wrapper" style="padding: 1rem; border-radius: var(--radius-sm);">
            
            <!-- Category Tabs Slider -->
            <div class="filter-btn-group">
                <button class="portfolio-filter-btn filter-btn active" data-filter="all">All Projects</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="portfolio-filter-btn filter-btn" data-filter="<?php echo esc_attr($cat['slug']); ?>"><?php echo esc($cat['name']); ?></button>
                <?php endforeach; ?>
            </div>
            
            <!-- Search Controls -->
            <div style="display: flex; gap: var(--spacing-xs); width: 100%; max-width: 520px;">
                <div class="search-input-box">
                    <input type="text" id="project-search" class="form-control" placeholder="Search by tech or title..." style="padding-left: 2rem;">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted-dark);">&#9906;</span>
                </div>
                <select id="project-sort" class="form-control" style="width: 160px; background: var(--color-bg-dark); cursor: pointer;">
                    <option value="latest">Latest Projects</option>
                    <option value="oldest">Oldest Projects</option>
                    <option value="asc">A - Z Title</option>
                </select>
            </div>
            
        </div>
    </div>
</section>

<!-- 3. Dynamic Projects Masonry Grid -->
<section class="section portfolio-list-section" style="padding: var(--spacing-md) 0;">
    <div class="container reveal">
        <div class="grid grid-3 portfolio-list-grid">
            <?php if (empty($projects)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: var(--spacing-lg) 0;">
                    <h3 style="color: var(--color-text-muted-dark);">No portfolio case studies found. Initialize default database seed.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $proj): ?>
                    <div class="glass-card glow-card project-grid-item" 
                         data-category="<?php echo esc_attr($proj['cat_slug'] ?? 'uncategorized'); ?>"
                         data-date="<?php echo esc_attr(strtotime($proj['completion_date'] ?? '2026-01-01')); ?>"
                         style="display: flex; flex-direction: column; justify-content: space-between; padding: 0; border-radius: var(--radius-md);">
                        
                        <div>
                            <!-- Cover thumbnail with zoom hover -->
                            <div class="project-thumb-container">
                                <img src="<?php echo esc_url($proj['thumbnail_url']); ?>" alt="<?php echo esc_attr($proj['title']); ?>" class="project-thumb" loading="lazy">
                            </div>
                            
                            <div style="padding: 1.5rem;">
                                <!-- Category & Client Info -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span class="project-category"><?php echo esc($proj['cat_name'] ?? 'General'); ?></span>
                                    <?php if (!empty($proj['client'])): ?>
                                        <span style="font-size: 0.75rem; color: var(--color-text-muted-dark); font-weight: 500;">For: <?php echo esc($proj['client']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="project-title" style="font-size: 1.35rem; margin-bottom: 0.5rem; color: #ffffff;"><?php echo esc($proj['title']); ?></h3>
                                <p class="project-desc" style="font-size: 0.9rem; line-height: 1.5; color: var(--color-text-secondary-dark); margin-bottom: 1rem;"><?php echo esc($proj['description']); ?></p>
                                
                                <!-- Comma Tech Tags display -->
                                <div class="project-tech-tags" style="display: flex; flex-wrap: wrap; gap: 6px; font-size: 0.75rem; color: var(--color-text-muted-dark); border-top: 1px solid rgba(255,255,255,0.03); padding-top: 0.75rem;">
                                    <?php
                                    $tags = explode(',', $proj['technology']);
                                    foreach ($tags as $tag):
                                    ?>
                                        <span style="background: rgba(255,255,255,0.02); padding: 2px 8px; border-radius: var(--radius-sm); border: var(--border-glass);"><?php echo esc(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Learn More action bar -->
                        <div style="border-top: 1px solid rgba(255, 255, 255, 0.04); padding: 1rem 1.5rem; background: rgba(255,255,255,0.01); border-bottom-left-radius: var(--radius-md); border-bottom-right-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: var(--color-text-muted-dark); font-weight: 600;">
                                <?php echo date('M Y', strtotime($proj['completion_date'] ?? '2026-01-01')); ?>
                            </span>
                            <a href="project.php?slug=<?php echo esc_attr($proj['slug']); ?>" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem; border-radius: var(--radius-sm); min-height: auto;">
                                View Case Study
                            </a>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 4. Dynamic general testimonials mapping -->
<?php
$general_testimonials = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT client_name, client_business, client_image_url, rating, review 
                            FROM testimonials 
                            WHERE is_active = 1 AND project_id IS NOT NULL 
                            ORDER BY display_order ASC LIMIT 3");
        $general_testimonials = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch general testimonials: " . $e->getMessage());
    }
}
if (!empty($general_testimonials)):
?>
    <section class="section testimonials-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">PARTNER OUTCOMES</span>
                <h2 style="margin-top: 0.5rem; line-height: 1.2;">Commercial Impact Validated By Clients</h2>
            </div>
            
            <div class="testimonial-track">
                <?php foreach ($general_testimonials as $item): ?>
                    <div class="glass-card glow-card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div class="testimonial-rating">
                                <?php for($i = 0; $i < $item['rating']; $i++): ?>&#9733;<?php endfor; ?>
                            </div>
                            <p style="font-style: italic; font-size: 0.9rem; line-height: 1.6; color: #ffffff;">
                                "<?php echo esc($item['review']); ?>"
                            </p>
                        </div>
                        <div class="testimonial-client">
                            <img src="<?php echo esc_url($item['client_image_url']); ?>" alt="<?php echo esc_attr($item['client_name']); ?>" class="client-img" loading="lazy">
                            <div>
                                <h4 style="font-size: 0.95rem; color: #ffffff;"><?php echo esc($item['client_name']); ?></h4>
                                <span style="font-size: 0.75rem; color: var(--color-text-muted-dark);"><?php echo esc($item['client_business']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 5. Conversion CTA -->
<section id="contact" class="section cta-section" style="padding: var(--spacing-lg) 0;">
    <div class="container reveal">
        <div class="glass-card glow-card" style="text-align: center; padding: var(--spacing-lg) var(--spacing-md); position: relative; overflow: hidden; border-radius: var(--radius-lg);">
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: var(--gradient-dark-glow); z-index: 1; pointer-events: none;"></div>
            
            <div style="position: relative; z-index: 2; max-width: 700px; margin: 0 auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">READY TO GET RESULTS?</span>
                <h2 class="gradient-text" style="font-size: 2.5rem; margin-bottom: var(--spacing-sm); line-height: 1.2;">Let's Code Your Commercial Solution</h2>
                <p style="margin-bottom: var(--spacing-md); font-size: 1.1rem; color: var(--color-text-secondary-dark);">Connect with our engineering team for a technical overview and detailed pricing structure matching your project expectations.</p>
                <div style="display: flex; gap: var(--spacing-sm); justify-content: center; flex-wrap: wrap;">
                    <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-primary">Call: <?php echo esc(APP_PHONE); ?></a>
                    <a href="mailto:<?php echo esc_attr(get_setting('contact_email', 'hello@webfalx.com')); ?>" class="btn btn-secondary">Email Team</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
