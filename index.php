<?php
/**
 * WebFalx Homepage
 * Fully Dynamic, CRO-Optimized, Integrated with Admin & Database
 */

require_once __DIR__ . '/includes/functions.php';

// 1. Fetch active sections in display order
$active_sections = [];
if ($db !== null) {
    try {
        $stmt = $db->prepare("SELECT section_key FROM homepage_sections WHERE is_active = 1 ORDER BY display_order ASC");
        $stmt->execute();
        $active_sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Failed to fetch homepage sections layout: " . $e->getMessage());
    }
}

// Fallback order in case database connection fails
if (empty($active_sections)) {
    $active_sections = ['hero', 'trust_bar', 'why_choose', 'services', 'process', 'portfolio', 'stats', 'testimonials', 'faq', 'blog', 'cta'];
}

// Page Specific SEO Configurations
$page_seo = [
    'title' => get_setting('site_title', 'WebFalx | Premium Digital Marketing & Web Development Agency'),
    'description' => get_setting('site_description', 'High-conversion websites and custom digital solutions that scale your business.')
];

require_once __DIR__ . '/includes/header.php';
?>

<?php
// Loop through and render each active section dynamically
foreach ($active_sections as $section_key) {
    switch ($section_key) {
        
        // ==========================================
        // HERO SECTION
        // ==========================================
        case 'hero':
            $hero = get_content_block('hero_section');
            $hero_title = $hero['title'] ?? 'We Engineer High-Conversion Digital Experiences';
            $hero_subtitle = $hero['subtitle'] ?? 'INNOVATION MEET AESTHETICS';
            $hero_content = $hero['content'] ?? 'WebFalx is a premium agency crafting state-of-the-art web architectures, digital marketing funnels, and luxury branding for elite brands globally.';
            
            // Fetch typing animation terms
            $typing_terms_raw = get_setting('hero_typing_terms', 'Local Businesses,Startups,Clinics & Doctors,E-commerce Brands,Corporate Brands');
            $typing_terms_array = array_map('trim', explode(',', $typing_terms_raw));
            $typing_terms_json = json_encode($typing_terms_array);
            ?>
            <section id="hero" class="section hero-section" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; overflow: hidden; display: flex; align-items: center;">
                <div class="container" style="position: relative; z-index: 2;">
                    <div class="grid grid-2" style="align-items: center; gap: var(--spacing-md);">
                        <!-- Left Hero Column -->
                        <div class="hero-left reveal-left">
                            <span style="display: block; font-family: var(--font-heading); font-size: 0.85rem; font-weight: 700; letter-spacing: 2px; color: var(--color-accent); margin-bottom: var(--spacing-sm); text-transform: uppercase;">
                                <?php echo esc($hero_subtitle); ?>
                            </span>
                            
                            <h1 style="line-height: 1.1; margin-bottom: var(--spacing-sm); font-size: 3.5rem;">
                                <?php echo esc($hero_title); ?> <br>
                                <span style="font-size: 0.75em; color: var(--color-text-secondary-dark);">For </span>
                                <span id="typing-text" class="gradient-text" data-words="<?php echo esc_attr($typing_terms_json); ?>"></span><span class="typed-cursor">|</span>
                            </h1>
                            
                            <p style="font-size: 1.15rem; margin-bottom: var(--spacing-md); max-width: 580px;">
                                <?php echo esc($hero_content); ?>
                            </p>
                            
                            <!-- Trust Badges & Ratings -->
                            <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-md); align-items: center; margin-bottom: var(--spacing-md);">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="color: var(--color-warning); font-size: 1.2rem; display: flex; gap: 2px;">
                                        &#9733;&#9733;&#9733;&#9733;&#9733;
                                    </div>
                                    <span style="font-size: 0.85rem; font-weight: 600; color: #ffffff;">Google Rating 5.0</span>
                                </div>
                                <div style="width: 1px; height: 20px; background: rgba(255,255,255,0.1);"></div>
                                <div style="font-size: 0.85rem; color: var(--color-text-secondary-dark);">
                                    <strong style="color: #ffffff; font-size: 1rem;">150+</strong> Projects Completed
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                                <a href="#contact" class="btn btn-primary">Get Free Quote</a>
                                <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-secondary">Call: <?php echo esc(APP_PHONE); ?></a>
                            </div>
                        </div>

                        <!-- Right Column (3D Interactive UI Mockup Elements) -->
                        <div class="hero-right reveal-right" style="position: relative; height: 420px; display: flex; justify-content: center; align-items: center;">
                            <!-- Premium Animated Dashboard Frame using inline SVG -->
                            <div class="glass-card card-3d" style="width: 90%; max-width: 440px; padding: var(--spacing-sm); position: relative; z-index: 5; box-shadow: var(--shadow-glass); border-color: rgba(6,182,212,0.2);">
                                <!-- Dashboard title/header bar -->
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                                    <div style="display: flex; gap: 6px;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-danger);"></span>
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-warning);"></span>
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-success);"></span>
                                    </div>
                                    <span style="font-size: 0.7rem; color: var(--color-text-muted-dark); text-transform: uppercase; letter-spacing: 0.5px;">WebFalx Analytics v2.0</span>
                                </div>
                                <!-- Graph representation -->
                                <svg viewBox="0 0 300 120" style="width: 100%; height: auto; display: block; overflow: visible;">
                                    <defs>
                                        <linearGradient id="gradient-chart" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="var(--color-accent)" stop-opacity="0.4"/>
                                            <stop offset="100%" stop-color="var(--color-accent)" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>
                                    <!-- Grid Lines -->
                                    <line x1="0" y1="20" x2="300" y2="20" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
                                    <line x1="0" y1="60" x2="300" y2="60" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
                                    <line x1="0" y1="100" x2="300" y2="100" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
                                    
                                    <!-- Area Path -->
                                    <path d="M 0,110 L 20,80 L 60,95 L 100,50 L 140,75 L 180,30 L 220,55 L 260,20 L 300,5 L 300,120 L 0,120 Z" fill="url(#gradient-chart)"/>
                                    <!-- Line Path -->
                                    <path d="M 0,110 L 20,80 L 60,95 L 100,50 L 140,75 L 180,30 L 220,55 L 260,20 L 300,5" fill="none" stroke="var(--color-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    <!-- Points -->
                                    <circle cx="100" cy="50" r="4" fill="var(--color-primary)"/>
                                    <circle cx="180" cy="30" r="4" fill="var(--color-secondary)"/>
                                    <circle cx="300" cy="5" r="5" fill="var(--color-accent)" class="pulse-element"/>
                                </svg>
                                <!-- Mini analytics cards -->
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 0.75rem;">
                                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius: var(--radius-sm); padding: 8px;">
                                        <div style="font-size: 0.7rem; color: var(--color-text-muted-dark);">CONVERSIONS</div>
                                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--color-success);">+42.8%</div>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius: var(--radius-sm); padding: 8px;">
                                        <div style="font-size: 0.7rem; color: var(--color-text-muted-dark);">BOUNCE RATE</div>
                                        <div style="font-size: 1.1rem; font-weight: 700; color: #ffffff;">18.4%</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Background glow blobs and floating mini visual elements -->
                            <div style="position: absolute; width: 140px; height: 140px; background: radial-gradient(circle, rgba(124,58,237,0.3) 0%, rgba(0,0,0,0) 70%); top: -20px; right: -10px; z-index: 1;"></div>
                            <div style="position: absolute; width: 160px; height: 160px; background: radial-gradient(circle, rgba(6,182,212,0.2) 0%, rgba(0,0,0,0) 70%); bottom: -40px; left: -20px; z-index: 1;"></div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // CLIENT TRUST BAR (LOGO SLIDER)
        // ==========================================
        case 'trust_bar':
            $logos = [];
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT company_name, logo_url FROM client_logos WHERE is_active = 1 ORDER BY display_order ASC");
                    $stmt->execute();
                    $logos = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Failed to fetch client logos: " . $e->getMessage());
                }
            }
            if (empty($logos)) {
                $logos = [
                    ['company_name' => 'TechCorp', 'logo_url' => 'TechCorp'],
                    ['company_name' => 'ApexGroup', 'logo_url' => 'ApexGroup'],
                    ['company_name' => 'Clinique', 'logo_url' => 'Clinique'],
                    ['company_name' => 'RealEstateInc', 'logo_url' => 'RealEstateInc'],
                    ['company_name' => 'EcomBuilders', 'logo_url' => 'EcomBuilders'],
                    ['company_name' => 'Vanguard', 'logo_url' => 'Vanguard']
                ];
            }
            ?>
            <section class="section trust-bar-section" style="padding: var(--spacing-sm) 0; border-top: var(--border-glass); border-bottom: var(--border-glass); background: rgba(255,255,255,0.01);">
                <div class="container">
                    <div class="logos-slider-container">
                        <!-- Repeated twice to enable seamless infinite scroll wraps -->
                        <div class="logos-slider-track">
                            <?php foreach ($logos as $logo): ?>
                                <span class="logo-item"><?php echo esc($logo['company_name']); ?></span>
                            <?php endforeach; ?>
                            <?php foreach ($logos as $logo): ?>
                                <span class="logo-item"><?php echo esc($logo['company_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // WHY CHOOSE WEBFALX
        // ==========================================
        case 'why_choose':
            $wc1 = get_content_block('why_choose_1') ?? ['title' => 'Cognitive Psychology CRO', 'subtitle' => 'PSYCHOLOGY', 'content' => 'Every layout, visual contrast, and text flow is tailored to match cognitive user patterns, creating instant brand authority.'];
            $wc2 = get_content_block('why_choose_2') ?? ['title' => 'Speed & Clean Engineering', 'subtitle' => 'ENGINEERING', 'content' => 'We code from scratch without template builders, ensuring ultra-fast load speed and high Google Core Web Vitals rankings.'];
            $wc3 = get_content_block('why_choose_3') ?? ['title' => 'Lead Generation Systems', 'subtitle' => 'CONVERSIONS', 'content' => 'We build custom interactive forms and CRM channels to automate funnel operations, tracking ROI on every asset.'];
            ?>
            <section id="about" class="section why-choose-section">
                <div class="container reveal">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">WHY WE DIFFER</span>
                        <h2 style="margin-top: 0.5rem; line-height: 1.2;">Built on Conversion Rate Optimization & Premium Code Performance</h2>
                    </div>
                    
                    <div class="grid grid-3">
                        <div class="glass-card glow-card" style="padding: var(--spacing-md); border-radius: var(--radius-md);">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(37,99,235,0.1); border: 1px solid var(--color-primary); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-sm);">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2"><circle cx=\"12\" cy=\"12\" r=\"10\"/><path d=\"M8 14s1.5 2 4 2 4-2 4-2\"/><line x1=\"9\" x2=\"9.01\" y1=\"9\" y2=\"9\"/><line x1=\"15\" x2=\"15.01\" y1=\"9\" y2=\"9\"/></svg>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--color-primary); letter-spacing: 1px; text-transform: uppercase;"><?php echo esc($wc1['subtitle']); ?></span>
                            <h3 style="font-size: 1.35rem; margin: 0.25rem 0 0.5rem 0;"><?php echo esc($wc1['title']); ?></h3>
                            <p style="font-size: 0.95rem;"><?php echo esc($wc1['content']); ?></p>
                        </div>
                        
                        <div class="glass-card glow-card" style="padding: var(--spacing-md); border-radius: var(--radius-md);">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(124,58,237,0.1); border: 1px solid var(--color-secondary); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-sm);">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--color-secondary)" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 1px; text-transform: uppercase;"><?php echo esc($wc2['subtitle']); ?></span>
                            <h3 style="font-size: 1.35rem; margin: 0.25rem 0 0.5rem 0;"><?php echo esc($wc2['title']); ?></h3>
                            <p style="font-size: 0.95rem;"><?php echo esc($wc2['content']); ?></p>
                        </div>
                        
                        <div class="glass-card glow-card" style="padding: var(--spacing-md); border-radius: var(--radius-md);">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(6,182,212,0.1); border: 1px solid var(--color-accent); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-sm);">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--color-accent)" stroke-width="2"><path d=\"M22 11.08V12a10 10 0 1 1-5.93-9.14\"/><polyline points=\"22 4 12 14.01 9 11.01\"/></svg>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--color-accent); letter-spacing: 1px; text-transform: uppercase;"><?php echo esc($wc3['subtitle']); ?></span>
                            <h3 style="font-size: 1.35rem; margin: 0.25rem 0 0.5rem 0;"><?php echo esc($wc3['title']); ?></h3>
                            <p style="font-size: 0.95rem;"><?php echo esc($wc3['content']); ?></p>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // SERVICES PREVIEW
        // ==========================================
        case 'services':
            $services = [];
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT title, description, icon_svg FROM services WHERE is_active = 1 ORDER BY display_order ASC");
                    $stmt->execute();
                    $services = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Failed to fetch services list: " . $e->getMessage());
                }
            }
            ?>
            <section id="services" class="section services-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
                <div class="container reveal">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">WHAT WE DELIVER</span>
                        <h2 style="margin-top: 0.5rem; line-height: 1.2;">High-Performance Technical & Digital Growth Services</h2>
                    </div>
                    
                    <div class="grid grid-4">
                        <?php foreach ($services as $service): ?>
                            <div class="glass-card glow-card" style="padding: 1.5rem; border-radius: var(--radius-md); display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="color: var(--color-accent); margin-bottom: 0.75rem;">
                                        <?php echo $service['icon_svg']; // Raw SVG code allowed from DB as controlled settings output ?>
                                    </div>
                                    <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: #ffffff;"><?php echo esc($service['title']); ?></h3>
                                    <p style="font-size: 0.85rem; line-height: 1.5;"><?php echo esc($service['description']); ?></p>
                                </div>
                                <div style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.03); padding-top: 0.75rem;">
                                    <a href="#contact" style="font-size: 0.75rem; font-weight: 700; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px;">
                                        Inquire Service &rarr;
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // OUR PROCESS TIMELINE
        // ==========================================
        case 'process':
            $steps = [];
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT step_number, title, description FROM process_steps WHERE is_active = 1 ORDER BY display_order ASC");
                    $stmt->execute();
                    $steps = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Failed to fetch process steps: " . $e->getMessage());
                }
            }
            ?>
            <section id="process" class="section process-section">
                <div class="container reveal">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">ENGINEERING WORKFLOW</span>
                        <h2 style="margin-top: 0.5rem; line-height: 1.2;">Our Seamless 7-Step Implementation Pipeline</h2>
                    </div>
                    
                    <div class="timeline-track">
                        <?php foreach ($steps as $step): ?>
                            <div class="timeline-step">
                                <div class="step-badge"><?php echo esc($step['step_number']); ?></div>
                                <h4><?php echo esc($step['title']); ?></h4>
                                <p><?php echo esc($step['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // FEATURED PORTFOLIO
        // ==========================================
        case 'portfolio':
            $projects = [];
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT p.title, p.technology, p.thumbnail_url, p.website_url, p.description, c.name AS category 
                                          FROM portfolio_projects p 
                                          LEFT JOIN portfolio_categories c ON p.category_id = c.id 
                                          WHERE p.is_active = 1 AND p.is_featured = 1 
                                          ORDER BY p.display_order ASC");
                    $stmt->execute();
                    $projects = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Failed to fetch featured projects: " . $e->getMessage());
                }
            }
            ?>
            <section id="work" class="section portfolio-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
                <div class="container reveal">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">SELECTED CASE STUDIES</span>
                        <h2 style="margin-top: 0.5rem; line-height: 1.2;">Premium Architectures & High-ROI Campaigns</h2>
                    </div>
                    
                    <div class="portfolio-grid">
                        <?php foreach ($projects as $project): ?>
                            <div class="glass-card project-card" style="padding: 0;">
                                <div class="project-thumb-container">
                                    <img src="<?php echo esc_attr($project['thumbnail_url']); ?>" alt="<?php echo esc_attr($project['title']); ?>" class="project-thumb" loading="lazy">
                                </div>
                                <div style="padding: 1.5rem;">
                                    <div class="project-category"><?php echo esc($project['category']); ?></div>
                                    <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: #ffffff;"><?php echo esc($project['title']); ?></h3>
                                    <p style="font-size: 0.85rem; margin-bottom: 1rem; line-height: 1.5; color: var(--color-text-secondary-dark);"><?php echo esc($project['description']); ?></p>
                                    <span class="project-tech">Tech Stack: <?php echo esc($project['technology']); ?></span>
                                    
                                    <div style="margin-top: 1.25rem; display: flex; gap: var(--spacing-sm); border-top: 1px solid rgba(255,255,255,0.03); padding-top: 1rem;">
                                        <a href="<?php echo esc_attr($project['website_url']); ?>" target="_blank" rel="noopener" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem; border-radius: var(--radius-sm);">
                                            Visit Link
                                        </a>
                                        <a href="#contact" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.75rem; border-radius: var(--radius-sm);">
                                            Inquire
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // STATISTICS COUNTERS
        // ==========================================
        case 'stats':
            $stat_projects = get_setting('stat_projects_completed', '150');
            $stat_clients = get_setting('stat_happy_clients', '120');
            $stat_years = get_setting('stat_years_experience', '8');
            $stat_countries = get_setting('stat_countries_served', '5');
            $stat_support = get_setting('stat_support_hours', '24');
            ?>
            <section class="section stats-counters-section" style="padding: var(--spacing-lg) 0;">
                <div class="container reveal">
                    <div class="grid grid-4">
                        <div class="glass-card" style="text-align: center; padding: var(--spacing-sm);">
                            <div style="font-size: 3rem; font-family: var(--font-heading); font-weight: 800; color: #ffffff;">
                                <span class="counter-value" data-target="<?php echo esc_attr($stat_projects); ?>">0</span>+
                            </div>
                            <p style="font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; color: var(--color-text-secondary-dark);">Projects Completed</p>
                        </div>
                        <div class="glass-card" style="text-align: center; padding: var(--spacing-sm);">
                            <div style="font-size: 3rem; font-family: var(--font-heading); font-weight: 800; color: #ffffff;">
                                <span class="counter-value" data-target="<?php echo esc_attr($stat_clients); ?>">0</span>+
                            </div>
                            <p style="font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; color: var(--color-text-secondary-dark);">Happy Clients</p>
                        </div>
                        <div class="glass-card" style="text-align: center; padding: var(--spacing-sm);">
                            <div style="font-size: 3rem; font-family: var(--font-heading); font-weight: 800; color: #ffffff;">
                                <span class="counter-value" data-target="<?php echo esc_attr($stat_years); ?>">0</span>+
                            </div>
                            <p style="font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; color: var(--color-text-secondary-dark);">Years Experience</p>
                        </div>
                        <div class="glass-card" style="text-align: center; padding: var(--spacing-sm);">
                            <div style="font-size: 3rem; font-family: var(--font-heading); font-weight: 800; color: #ffffff;">
                                <span class="counter-value" data-target="<?php echo esc_attr($stat_support); ?>">0</span>/7
                            </div>
                            <p style="font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; color: var(--color-text-secondary-dark);">Technical Support</p>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // CLIENT TESTIMONIALS
        // ==========================================
        case 'testimonials':
            $testimonials = [];
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT client_name, client_business, client_image_url, rating, review FROM testimonials WHERE is_active = 1 ORDER BY display_order ASC");
                    $stmt->execute();
                    $testimonials = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Failed to fetch testimonials: " . $e->getMessage());
                }
            }
            ?>
            <section id="testimonials" class="section testimonials-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
                <div class="container reveal">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">CLIENT SUCCESS</span>
                        <h2 style="margin-top: 0.5rem; line-height: 1.2;">What Business Leaders Say About WebFalx</h2>
                    </div>
                    
                    <div class="testimonial-track">
                        <?php foreach ($testimonials as $item): ?>
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
            <?php
            break;

        // ==========================================
        // FAQs ACCORDION
        // ==========================================
        case 'faq':
            $faqs = [];
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT question, answer FROM faqs WHERE is_active = 1 ORDER BY display_order ASC");
                    $stmt->execute();
                    $faqs = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Failed to fetch FAQs: " . $e->getMessage());
                }
            }
            ?>
            <section class="section faq-section">
                <div class="container reveal">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">COMMON INQUIRIES</span>
                        <h2 style="margin-top: 0.5rem; line-height: 1.2;">Resolving Your Core Development Questions</h2>
                    </div>
                    
                    <div class="faq-accordion">
                        <?php foreach ($faqs as $faq): ?>
                            <div class="glass-card faq-card" style="border-radius: var(--radius-sm);">
                                <div class="faq-header">
                                    <h4><?php echo esc($faq['question']); ?></h4>
                                    <div class="faq-icon" style="font-size: 1.5rem; font-weight: 300;">+</div>
                                </div>
                                <div class="faq-body">
                                    <div class="faq-content">
                                        <p><?php echo esc($faq['answer']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // LATEST BLOGS
        // ==========================================
        case 'blog':
            $blogs = [];
            if ($db !== null) {
                try {
                    $stmt = $db->prepare("SELECT p.title, c.name AS category, p.created_at AS date_published, p.excerpt AS short_description, p.featured_image AS featured_image_url 
                                          FROM blog_posts p 
                                          LEFT JOIN blog_categories c ON p.category_id = c.id 
                                          WHERE p.status = 'published' 
                                          ORDER BY p.id DESC LIMIT 3");
                    $stmt->execute();
                    $blogs = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Failed to fetch blogs: " . $e->getMessage());
                }
            }
            ?>
            <section id="blog" class="section blogs-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
                <div class="container reveal">
                    <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                        <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">RESOURCE CENTER</span>
                        <h2 style="margin-top: 0.5rem; line-height: 1.2;">Latest Design, Code & SEO Industry Insights</h2>
                    </div>
                    
                    <div class="grid grid-3">
                        <?php foreach ($blogs as $post): ?>
                            <div class="glass-card" style="padding: 0; display: flex; flex-direction: column; justify-content: space-between;">
                                <div class="project-thumb-container" style="height: 180px;">
                                    <img src="<?php echo esc_attr($post['featured_image_url']); ?>" alt="<?php echo esc_attr($post['title']); ?>" class="project-thumb" loading="lazy">
                                </div>
                                <div style="padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--color-accent); font-weight: 600; margin-bottom: 0.5rem;">
                                            <span><?php echo esc($post['category']); ?></span>
                                            <span style="color: var(--color-text-muted-dark);"><?php echo date('M d, Y', strtotime($post['date_published'])); ?></span>
                                        </div>
                                        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: #ffffff; line-height: 1.3;"><?php echo esc($post['title']); ?></h3>
                                        <p style="font-size: 0.85rem; line-height: 1.5; color: var(--color-text-secondary-dark); margin-bottom: 1rem;"><?php echo esc($post['short_description']); ?></p>
                                    </div>
                                    <div style="border-top: 1px solid rgba(255,255,255,0.03); padding-top: 0.75rem;">
                                        <a href="#contact" style="font-size: 0.75rem; font-weight: 700; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.5px;">Read Full Insight &rarr;</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        // ==========================================
        // FINAL CTA SECTION
        // ==========================================
        case 'cta':
            $cta = get_content_block('cta_section');
            $cta_title = $cta['title'] ?? 'Ready to Scale Your Brand to the Next Level?';
            $cta_subtitle = $cta['subtitle'] ?? 'LET\'S COLLABORATE';
            $cta_content = $cta['content'] ?? 'Partner with WebFalx today and gain access to premium engineering, high-ROI marketing strategies, and design experiences that inspire trust and drive results.';
            
            // Build custom WhatsApp link
            $wa_number = APP_PHONE;
            $wa_text = urlencode("Hello WebFalx team! I would like to get a quote and explore digital marketing services.");
            $whatsapp_url = "https://wa.me/{$wa_number}?text={$wa_text}";
            ?>
            <section id="contact" class="section cta-section" style="padding: var(--spacing-lg) 0; overflow: hidden; position: relative;">
                <!-- Moving animated background shapes specific to the conversion CTA -->
                <div class="gradient-blob blob-secondary" style="bottom: auto; top: -10%; left: -10%; opacity: 0.1;"></div>
                
                <div class="container reveal" style="position: relative; z-index: 2;">
                    <div class="glass-card glow-card" style="text-align: center; padding: var(--spacing-lg) var(--spacing-md); position: relative; overflow: hidden; border-radius: var(--radius-lg);">
                        <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: var(--gradient-dark-glow); z-index: 1; pointer-events: none;"></div>
                        
                        <div style="position: relative; z-index: 2; max-width: 720px; margin: 0 auto;">
                            <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 0.5rem; display: block;">
                                <?php echo esc($cta_subtitle); ?>
                            </span>
                            <h2 class="gradient-text" style="font-size: 2.75rem; margin-bottom: var(--spacing-sm); line-height: 1.2;">
                                <?php echo esc($cta_title); ?>
                            </h2>
                            <p style="margin-bottom: var(--spacing-md); font-size: 1.1rem; color: var(--color-text-secondary-dark);">
                                <?php echo esc($cta_content); ?>
                            </p>
                            
                            <div style="display: flex; gap: var(--spacing-sm); justify-content: center; flex-wrap: wrap;">
                                <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-primary">Call Now: <?php echo esc(APP_PHONE); ?></a>
                                <a href="<?php echo esc_attr($whatsapp_url); ?>" target="_blank" rel="noopener" class="btn btn-secondary" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: var(--color-success);">
                                    WhatsApp Chat
                                </a>
                                <a href="mailto:<?php echo esc_attr(get_setting('contact_email', 'hello@webfalx.com')); ?>" class="btn btn-secondary">Email Team</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
