<?php
/**
 * WebFalx Services Catalog Page
 * Displays service categories, searches, dynamic sorting, and service previews
 */

require_once __DIR__ . '/includes/functions.php';

// Fetch Active Categories
$categories = [];
if ($db !== null) {
    try {
        $stmt = $db->prepare("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY display_order ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch service categories: " . $e->getMessage());
    }
}

// Fetch Active Services with Category relationships
$services = [];
if ($db !== null) {
    try {
        $stmt = $db->prepare("SELECT s.*, c.slug as cat_slug, c.name as cat_name 
                              FROM services s 
                              LEFT JOIN service_categories c ON s.category_id = c.id 
                              WHERE s.is_active = 1 
                              ORDER BY s.display_order ASC");
        $stmt->execute();
        $services = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch services layout: " . $e->getMessage());
    }
}

// Global configurations override for SEO Meta
$page_seo = [
    'title' => 'Our Core Services & Digital Expertise | WebFalx',
    'description' => 'Explore the custom digital services we offer: website development, e-commerce stores, SEO growth, and business automations.'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Services Hero Banner -->
<section class="section services-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; overflow: hidden; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal">
        <div style="max-width: 750px; margin: 0 auto; text-align: center;">
            <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">DYNAMIC DELIVERABLES</span>
            <h1 class="gradient-text" style="font-size: 3rem; margin-top: 0.5rem; line-height: 1.2;">Expert Solutions Tailored For Your Business Scale</h1>
            <p style="font-size: 1.1rem; margin-top: var(--spacing-sm);">Select a category or search our development stacks to discover how we build modern digital products that inspire user trust.</p>
        </div>
    </div>
</section>

<!-- 2. Interactive Search & Filters Panel (Responsive Mobile-First layout) -->
<section class="section filter-panel-section" style="padding: var(--spacing-xs) 0; position: relative; z-index: 10;">
    <div class="container reveal">
        <div class="glass-card search-filter-wrapper" style="padding: 1rem; border-radius: var(--radius-sm);">
            
            <!-- Category Horizontal tabs list (scrolls horizontally on small devices) -->
            <div class="filter-btn-group">
                <button class="filter-btn active" data-filter="all">All Services</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="filter-btn" data-filter="<?php echo esc_attr($cat['slug']); ?>"><?php echo esc($cat['name']); ?></button>
                <?php endforeach; ?>
            </div>
            
            <!-- Right Search controls -->
            <div style="display: flex; gap: var(--spacing-xs); width: 100%; max-width: 520px;">
                <div class="search-input-box">
                    <input type="text" id="service-search" class="form-control" placeholder="Search services..." style="padding-left: 2rem;">
                    <!-- Magnifier icon placeholder -->
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted-dark);">&#9906;</span>
                </div>
                <select id="service-sort" class="form-control" style="width: 160px; background: var(--color-bg-dark); cursor: pointer;">
                    <option value="default">Sort Order</option>
                    <option value="asc">A - Z List</option>
                    <option value="desc">Z - A List</option>
                </select>
            </div>
            
        </div>
    </div>
</section>

<!-- 3. Dynamic Services Directory Grid -->
<section class="section services-list-section" style="padding: var(--spacing-md) 0;">
    <div class="container reveal">
        <div class="grid grid-3 services-list-grid">
            <?php if (empty($services)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: var(--spacing-lg) 0;">
                    <h3 style="color: var(--color-text-muted-dark);">No services found. Ensure details are seeded.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($services as $srv): ?>
                    <div class="glass-card glow-card service-grid-item" data-category="<?php echo esc_attr($srv['cat_slug'] ?? 'uncategorized'); ?>" style="display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-md);">
                        <div>
                            <!-- Header Icon and Category tag -->
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-sm);">
                                <div style="color: var(--color-accent);">
                                    <?php echo $srv['icon_svg']; ?>
                                </div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: var(--color-text-muted-dark); background: rgba(255,255,255,0.02); padding: 4px 8px; border-radius: var(--radius-sm);">
                                    <?php echo esc($srv['cat_name'] ?? 'General'); ?>
                                </span>
                            </div>
                            
                            <h3 class="service-title" style="font-size: 1.35rem; margin-bottom: 0.5rem; color: #ffffff;"><?php echo esc($srv['title']); ?></h3>
                            <p class="service-desc" style="font-size: 0.9rem; line-height: 1.5; color: var(--color-text-secondary-dark);"><?php echo esc($srv['description']); ?></p>
                            
                            <!-- Key benefits bullet summaries -->
                            <?php if (!empty($srv['benefits'])): ?>
                                <ul style="margin: 1rem 0; padding-left: 1.25rem; font-size: 0.8rem; color: var(--color-text-muted-dark);">
                                    <?php
                                    $benefits_list = array_slice(explode("\n", $srv['benefits']), 0, 2);
                                    foreach ($benefits_list as $benefit):
                                        if (trim($benefit) !== ''):
                                    ?>
                                        <li style="margin-bottom: 4px;"><?php echo esc($benefit); ?></li>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        
                        <div style="border-top: 1px solid rgba(255, 255, 255, 0.04); padding-top: 1rem; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--color-accent); font-family: var(--font-heading);"><?php echo esc($srv['technologies'] ? explode(',', $srv['technologies'])[0] : 'Custom Coded'); ?></span>
                            <a href="service.php?slug=<?php echo esc_attr($srv['slug']); ?>" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem; border-radius: var(--radius-sm); min-height: auto;">
                                Learn More
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 4. Conversion CTA Segment -->
<section id="contact" class="section cta-section" style="padding: var(--spacing-lg) 0; border-top: var(--border-glass);">
    <div class="container reveal">
        <div class="glass-card glow-card" style="text-align: center; padding: var(--spacing-lg) var(--spacing-md); position: relative; overflow: hidden; border-radius: var(--radius-lg);">
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: var(--gradient-dark-glow); z-index: 1; pointer-events: none;"></div>
            
            <div style="position: relative; z-index: 2; max-width: 700px; margin: 0 auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">READY TO GET STARTED?</span>
                <h2 class="gradient-text" style="font-size: 2.5rem; margin-bottom: var(--spacing-sm); line-height: 1.2;">Get a Free Consultation & Code Review</h2>
                <p style="margin-bottom: var(--spacing-md); font-size: 1.1rem; color: var(--color-text-secondary-dark);">Partner with WebFalx today and gain access to premium engineering, high-ROI marketing strategies, and design experiences that inspire trust.</p>
                <div style="display: flex; gap: var(--spacing-sm); justify-content: center; flex-wrap: wrap;">
                    <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-primary">Call: <?php echo esc(APP_PHONE); ?></a>
                    <a href="mailto:<?php echo esc_attr(get_setting('contact_email', 'hello@webfalx.com')); ?>" class="btn btn-secondary">Email Team</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
