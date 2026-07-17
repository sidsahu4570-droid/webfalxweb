<?php
/**
 * WebFalx Case Study / Project Detail Page
 * Dynamic Case Studies layout with mockup device overlays, results lists, and responsive Lightbox galleries
 */

require_once __DIR__ . '/includes/functions.php';

// 1. Resolve slug routing
$slug = sanitize_input($_GET['slug'] ?? '');
$project = null;
$category_name = 'General';

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'portfolio.php');
    exit;
}

if ($db !== null) {
    try {
        $stmt = $db->prepare("SELECT p.*, c.name as cat_name 
                              FROM portfolio_projects p 
                              LEFT JOIN portfolio_categories c ON p.category_id = c.id 
                              WHERE p.slug = :slug AND p.is_active = 1 
                              LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $project = $stmt->fetch();
        
        if ($project) {
            $category_name = $project['cat_name'] ?: 'General';
        }
    } catch (PDOException $e) {
        error_log("Failed to query project details: " . $e->getMessage());
    }
}

// Redirect or show 404 if no project found
if (!$project) {
    $page_seo = ['title' => 'Project Not Found | WebFalx'];
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container" style="text-align:center; padding: var(--spacing-lg) 0;">';
    echo '<h2>Case Study Not Found</h2><p style="margin: 1rem 0;">The requested case study is currently unavailable.</p>';
    echo '<a href="' . BASE_URL . 'portfolio.php" class="btn btn-primary">Return to Portfolio</a>';
    echo '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$project_id = $project['id'];

// 2. Fetch Associated Testimonials & Related projects
$project_testimonials = [];
$related_projects = [];

if ($db !== null) {
    try {
        $t_stmt = $db->prepare("SELECT client_name, client_business, client_image_url, rating, review 
                                FROM testimonials 
                                WHERE project_id = :pid AND is_active = 1 
                                ORDER BY display_order ASC");
        $t_stmt->execute(['pid' => $project_id]);
        $project_testimonials = $t_stmt->fetchAll();

        if ($project['category_id']) {
            $r_stmt = $db->prepare("SELECT title, slug, thumbnail_url, description 
                                    FROM portfolio_projects 
                                    WHERE category_id = :cat_id AND id != :id AND is_active = 1 
                                    LIMIT 3");
            $r_stmt->execute(['cat_id' => $project['category_id'], 'id' => $project_id]);
            $related_projects = $r_stmt->fetchAll();
        }
    } catch (PDOException $ex) {
        error_log("Failed loading related portfolio details: " . $ex->getMessage());
    }
}

// Global SEO Setup
$meta_t = !empty($project['meta_title']) ? $project['meta_title'] : $project['title'] . " Case Study | WebFalx";
$meta_d = !empty($project['meta_description']) ? $project['meta_description'] : $project['description'];

$page_seo = [
    'title' => $meta_t,
    'description' => $meta_d,
    'canonical' => BASE_URL . 'project.php?slug=' . $project['slug']
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Case Study Header -->
<section class="section project-hero" style="background: linear-gradient(180deg, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.98) 100%), url('<?php echo esc_url($project['thumbnail_url']); ?>') no-repeat center center / cover; padding: var(--spacing-xl) 0 var(--spacing-md) 0; position: relative;">
    <div class="container reveal" style="position: relative; z-index: 2;">
        <div style="max-width: 800px;">
            <div style="display: flex; gap: 8px; align-items: center; font-size: 0.8rem; font-weight: 600; color: var(--color-accent); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                <span>Portfolio</span> &rsaquo; <span><?php echo esc($category_name); ?></span>
            </div>
            <h1 class="gradient-text" style="font-size: 3.5rem; line-height: 1.1; margin-bottom: var(--spacing-xs);"><?php echo esc($project['title']); ?></h1>
            <p style="font-size: 1.15rem; color: var(--color-text-secondary-dark); max-width: 650px;"><?php echo esc($project['description']); ?></p>
            
            <!-- Quick metrics bar -->
            <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-sm); margin-top: var(--spacing-sm); font-size: 0.85rem; color: #ffffff;">
                <?php if ($project['client']): ?>
                    <div><strong>Client:</strong> <?php echo esc($project['client']); ?></div>
                <?php endif; ?>
                <?php if ($project['industry']): ?>
                    <div><strong>Industry:</strong> <?php echo esc($project['industry']); ?></div>
                <?php endif; ?>
                <?php if ($project['completion_date']): ?>
                    <div><strong>Delivered:</strong> <?php echo date('F Y', strtotime($project['completion_date'])); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- 2. Case Study Content Columns split -->
<section class="section case-study-content" style="padding: var(--spacing-md) 0;">
    <div class="container">
        <div class="grid grid-2" style="grid-template-columns: 1.5fr 1fr; align-items: start; gap: var(--spacing-md);">
            
            <!-- Left Info column -->
            <div class="reveal-left" style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                
                <!-- Overview -->
                <div class="glass-card" style="padding: var(--spacing-md);">
                    <h2 style="font-size: 1.75rem; margin-bottom: var(--spacing-sm); color: #ffffff; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem;">Project Overview</h2>
                    <p style="font-size: 1.05rem; line-height: 1.6; color: var(--color-text-secondary-dark);">
                        <?php echo esc($project['full_overview'] ?: $project['description']); ?>
                    </p>
                </div>

                <!-- Challenge and Solution -->
                <?php if (!empty($project['challenge']) || !empty($project['solution'])): ?>
                    <div class="grid grid-2" style="gap: 15px;">
                        <?php if ($project['challenge']): ?>
                            <div class="glass-card" style="padding: var(--spacing-sm);">
                                <h3 style="font-size: 1.2rem; color: var(--color-danger); margin-bottom: 8px;">The Challenge</h3>
                                <p style="font-size: 0.9rem; line-height: 1.5; color: var(--color-text-secondary-dark);"><?php echo esc($project['challenge']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($project['solution']): ?>
                            <div class="glass-card" style="padding: var(--spacing-sm);">
                                <h3 style="font-size: 1.2rem; color: var(--color-success); margin-bottom: 8px;">WebFalx Solution</h3>
                                <p style="font-size: 0.9rem; line-height: 1.5; color: var(--color-text-secondary-dark);"><?php echo esc($project['solution']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Features checklist -->
                <?php if (!empty($project['features'])): ?>
                    <div class="glass-card" style="padding: var(--spacing-sm);">
                        <h4 style="font-size: 1.15rem; color: #ffffff; margin-bottom: var(--spacing-xs);">Engineering Implementations</h4>
                        <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
                            <?php
                            $features_list = explode("\n", $project['features']);
                            foreach ($features_list as $feat):
                                if (trim($feat) !== ''):
                            ?>
                                <li style="display: flex; align-items: start; gap: 8px;">
                                    <span style="color: var(--color-accent); font-weight: bold; margin-top: 2px;">&bull;</span>
                                    <span><?php echo esc($feat); ?></span>
                                </li>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Results Achieved Stats Cards -->
                <?php if (!empty($project['results'])): ?>
                    <div>
                        <h2 style="font-size: 1.75rem; margin-bottom: var(--spacing-xs); color: #ffffff;">Performance Improvements</h2>
                        <div class="grid grid-3" style="gap: 12px;">
                            <?php
                            $results_list = explode("\n", $project['results']);
                            foreach ($results_list as $res):
                                if (trim($res) !== ''):
                                    // Parse label:value (e.g. "Load Time: 0.8s")
                                    $parts = explode(':', $res, 2);
                                    $label = trim($parts[0]);
                                    $val = isset($parts[1]) ? trim($parts[1]) : '';
                            ?>
                                <div class="glass-card" style="text-align: center; padding: 12px;">
                                    <?php if ($val !== ''): ?>
                                        <div style="font-size: 1.6rem; font-family: var(--font-heading); font-weight: 800; color: var(--color-success);"><?php echo esc($val); ?></div>
                                        <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--color-text-muted-dark); font-weight: 600; margin-top: 4px;"><?php echo esc($label); ?></div>
                                    <?php else: ?>
                                        <div style="font-size: 0.85rem; color: #ffffff; font-weight: 600;"><?php echo esc($label); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right Visual Mockups column -->
            <div class="reveal-right">
                
                <!-- Overlapping device screens -->
                <div class="mockup-showcase-container">
                    <?php if ($project['desktop_screenshot']): ?>
                        <div class="mockup-item mockup-desktop">
                            <img src="<?php echo esc_url($project['desktop_screenshot']); ?>" alt="Desktop view" class="lightbox-trigger" data-fullsrc="<?php echo esc_url($project['desktop_screenshot']); ?>">
                        </div>
                    <?php endif; ?>

                    <?php if ($project['tablet_screenshot']): ?>
                        <div class="mockup-item mockup-tablet">
                            <img src="<?php echo esc_url($project['tablet_screenshot']); ?>" alt="Tablet view" class="lightbox-trigger" data-fullsrc="<?php echo esc_url($project['tablet_screenshot']); ?>">
                        </div>
                    <?php endif; ?>

                    <?php if ($project['mobile_screenshot']): ?>
                        <div class="mockup-item mockup-mobile">
                            <img src="<?php echo esc_url($project['mobile_screenshot']); ?>" alt="Mobile view" class="lightbox-trigger" data-fullsrc="<?php echo esc_url($project['mobile_screenshot']); ?>">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Live website Link button -->
                <?php if ($project['website_url']): ?>
                    <a href="<?php echo esc_url($project['website_url']); ?>" target="_blank" rel="noopener" class="btn btn-primary" style="width: 100%; margin-top: var(--spacing-sm);">
                        Visit Live Website
                    </a>
                <?php endif; ?>

                <!-- Comma tech stack list -->
                <div class="glass-card" style="margin-top: var(--spacing-sm); padding: var(--spacing-sm);">
                    <h5 style="color: var(--color-accent); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 8px;">Development Stack</h5>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <?php
                        $tech_stack = explode(',', $project['technology']);
                        foreach ($tech_stack as $tech):
                        ?>
                            <span style="font-size: 0.75rem; background: rgba(255,255,255,0.02); padding: 4px 10px; border-radius: var(--radius-sm); border: var(--border-glass); color: #ffffff;"><?php echo esc(trim($tech)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Mini Images Gallery list -->
                <?php
                if (!empty($project['gallery_json'])):
                    $gallery = json_decode($project['gallery_json'], true);
                    if (is_array($gallery) && count($gallery) > 0):
                ?>
                    <div style="margin-top: var(--spacing-sm);">
                        <h5 style="color: #ffffff; font-size: 0.85rem; margin-bottom: 6px;">Screenshots Archive</h5>
                        <div class="gallery-grid">
                            <?php foreach ($gallery as $img_url): ?>
                                <div class="gallery-item">
                                    <img src="<?php echo esc_url($img_url); ?>" alt="Gallery item" class="lightbox-trigger" data-fullsrc="<?php echo esc_url($img_url); ?>" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                    endif;
                endif;
                ?>

            </div>

        </div>
    </div>
</section>

<!-- 3. Dynamic Case Study Client Testimonial -->
<?php if (!empty($project_testimonials)): ?>
    <section class="section testimonials-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">CLIENT REVIEW</span>
                <h2>What the client said about this execution</h2>
            </div>
            
            <div class="testimonial-track" style="grid-template-columns: 1fr !important; max-width: 800px; margin: 0 auto;">
                <?php foreach ($project_testimonials as $item): ?>
                    <div class="glass-card glow-card" style="padding: var(--spacing-md); text-align: center;">
                        <div class="testimonial-rating" style="font-size: 1.5rem;">
                            <?php for($i = 0; $i < $item['rating']; $i++): ?>&#9733;<?php endfor; ?>
                        </div>
                        <p style="font-style: italic; font-size: 1.1rem; line-height: 1.8; color: #ffffff; margin: var(--spacing-sm) 0;">
                            "<?php echo esc($item['review']); ?>"
                        </p>
                        <div class="testimonial-client" style="justify-content: center;">
                            <img src="<?php echo esc_url($item['client_image_url']); ?>" alt="<?php echo esc_attr($item['client_name']); ?>" class="client-img" loading="lazy">
                            <div style="text-align: left;">
                                <h4 style="font-size: 1rem; color: #ffffff;"><?php echo esc($item['client_name']); ?></h4>
                                <span style="font-size: 0.8rem; color: var(--color-text-muted-dark);"><?php echo esc($item['client_business']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 4. Related Projects -->
<?php if (!empty($related_projects)): ?>
    <section class="section related-projects-section" style="background: #090e1a; border-top: var(--border-glass);">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">SIMILAR EXECUTIONS</span>
                <h2>Related Case Studies</h2>
            </div>
            
            <div class="grid grid-3">
                <?php foreach ($related_projects as $rel): ?>
                    <div class="glass-card glow-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 0; border-radius: var(--radius-md);">
                        <div>
                            <div style="height: 180px; overflow: hidden;">
                                <img src="<?php echo esc_url($rel['thumbnail_url']); ?>" alt="<?php echo esc_attr($rel['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="padding: 1rem;">
                                <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.5rem;"><?php echo esc($rel['title']); ?></h3>
                                <p style="font-size: 0.85rem; line-height: 1.5; color: var(--color-text-secondary-dark);"><?php echo esc($rel['description']); ?></p>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.03); padding: 0.75rem 1rem;">
                            <a href="project.php?slug=<?php echo esc_attr($rel['slug']); ?>" style="font-size: 0.75rem; font-weight: 700; color: var(--color-accent); text-transform: uppercase;">
                                Read Case Study &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 5. Fullscreen Lightbox Modal (Triggered by JS click events) -->
<div class="lightbox-modal">
    <span class="lightbox-close">&times;</span>
    <span class="lightbox-nav lightbox-prev">&lsaquo;</span>
    <img src="" alt="Enlarged screenshot" class="lightbox-content">
    <span class="lightbox-nav lightbox-next">&rsaquo;</span>
</div>

<!-- 6. Sticky Mobile CTA -->
<div class="mobile-sticky-cta">
    <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-secondary" style="background: var(--color-bg-dark); border-color: rgba(255,255,255,0.1);">
        Call Support
    </a>
    <a href="https://wa.me/<?php echo esc_attr(APP_PHONE); ?>?text=<?php echo urlencode('Hello WebFalx! I saw your portfolio case study: ' . $project['title']); ?>" target="_blank" rel="noopener" class="btn btn-primary" style="background: #10b981; box-shadow: none;">
        WhatsApp Scope
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
