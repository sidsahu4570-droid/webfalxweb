<?php
/**
 * WebFalx About Us Page
 * Premium, fully dynamic, mobile-first showcasing company timeline, team profiles, core values, skills, certifications, and awards
 */

require_once __DIR__ . '/includes/functions.php';

// Fetch Dynamic Content Blocks
$story_block = get_content_block('about_story', [
    'title' => 'A Vision Coded Into Reality',
    'subtitle' => 'THE WEBFALX ORIGINS',
    'content' => 'WebFalx was founded with one clear mission: to build custom-coded, high-performance web products that run quickly and command instant authority.'
]);

$mission_block = get_content_block('about_mission', [
    'title' => 'Code Compliant Operations',
    'subtitle' => 'OUR MISSION',
    'content' => 'To build secure, fast, and high-conversion web architectures that maximize company revenues.'
]);

$vision_block = get_content_block('about_vision', [
    'title' => 'Bespoke Global Solutions',
    'subtitle' => 'OUR VISION',
    'content' => 'To establish WebFalx as the global benchmark for web engineering and performance marketing.'
]);

// Fetch database records for dynamic widgets
$core_values = [];
$milestones = [];
$achievements = [];
$technologies = [];
$industries = [];
$team = [];
$skills = [];
$certifications = [];
$awards = [];
$logos = [];

if ($db !== null) {
    try {
        $core_values = $db->query("SELECT * FROM core_values WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $milestones = $db->query("SELECT * FROM company_milestones WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $achievements = $db->query("SELECT * FROM achievements WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $technologies = $db->query("SELECT * FROM technologies WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $industries = $db->query("SELECT * FROM industries_served WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $team = $db->query("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $skills = $db->query("SELECT * FROM skills_expertise WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $certifications = $db->query("SELECT * FROM certifications WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $awards = $db->query("SELECT * FROM awards WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
        $logos = $db->query("SELECT * FROM client_logos WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch about resource logs: " . $e->getMessage());
    }
}

// Stats variables
$happy_clients = get_setting('stat_happy_clients', '120');
$years_exp = get_setting('stat_years_experience', '8');
$completed_proj = get_setting('stat_total_projects', '150');
$countries_serv = get_setting('stat_countries_served', '5');

$page_seo = [
    'title' => 'About WebFalx Agency | Custom Web Engineering & CRO Experts',
    'description' => 'Learn how our Pasadena team combines low-latency code engineering with digital marketing psychology to accelerate business revenue.'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Premium Hero Section with background shapes -->
<section class="section about-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; overflow: hidden; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <!-- Floating abstract shapes -->
    <div class="abstract-glow-shape" style="top: 10%; left: 5%; width: 250px; height: 250px; background: var(--color-accent);"></div>
    <div class="abstract-glow-shape" style="bottom: 10%; right: 5%; width: 300px; height: 300px; background: var(--color-secondary);"></div>

    <div class="container reveal" style="position: relative; z-index: 2;">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;"><?php echo esc(APP_NAME); ?> agency</span>
            <h1 class="gradient-text" style="font-size: 3.5rem; line-height: 1.1; margin-top: 0.5rem; margin-bottom: var(--spacing-sm);">We Code Fast Systems For Elite Brands</h1>
            <p style="font-size: 1.15rem; color: var(--color-text-secondary-dark); line-height: 1.6;">WebFalx is an international Digital Engineering and Cognitive CRO agency. We replace bloated drag-and-drop templates with clean database scripts to boost rankings and capture hot sales leads.</p>
            
            <div style="display: flex; gap: var(--spacing-sm); justify-content: center; margin-top: var(--spacing-md); flex-wrap: wrap;">
                <a href="#story" class="btn btn-primary">Read Our Story</a>
                <a href="portfolio.php" class="btn btn-secondary">View Case Studies</a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Story block -->
<section id="story" class="section story-section" style="border-top: var(--border-glass);">
    <div class="container reveal">
        <div class="grid grid-2" style="align-items: center; gap: var(--spacing-md);">
            <div class="reveal-left">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;"><?php echo esc($story_block['subtitle']); ?></span>
                <h2 style="font-size: 2.25rem; margin-top: 0.5rem; margin-bottom: var(--spacing-sm); color: #ffffff;"><?php echo esc($story_block['title']); ?></h2>
                <p style="font-size: 1.05rem; line-height: 1.6; color: var(--color-text-secondary-dark); margin-bottom: 1rem;">
                    <?php echo esc($story_block['content']); ?>
                </p>
            </div>
            
            <!-- Mission & Vision Overlapping cards -->
            <div class="reveal-right" style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                <div class="glass-card glow-card" style="border-color: rgba(6,182,212,0.2);">
                    <h3 style="color: var(--color-accent); font-size: 1.25rem; margin-bottom: 6px;"><?php echo esc($mission_block['title']); ?></h3>
                    <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark);"><?php echo esc($mission_block['content']); ?></p>
                </div>
                <div class="glass-card glow-card" style="border-color: rgba(124,58,237,0.2);">
                    <h3 style="color: var(--color-secondary); font-size: 1.25rem; margin-bottom: 6px;"><?php echo esc($vision_block['title']); ?></h3>
                    <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark);"><?php echo esc($vision_block['content']); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Core Values Grid -->
<?php if (!empty($core_values)): ?>
    <section class="section values-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">VALUES SYSTEM</span>
                <h2>Core Operating Values We Honor</h2>
            </div>
            
            <div class="grid grid-4">
                <?php foreach ($core_values as $val): ?>
                    <div class="glass-card glow-card" style="border-radius: var(--radius-md);">
                        <div style="color: var(--color-accent); margin-bottom: var(--spacing-xs); font-weight: 800; font-size: 1.25rem;">
                            <!-- Simple icon badges based on name -->
                            &#9670;
                        </div>
                        <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 6px;"><?php echo esc($val['name']); ?></h3>
                        <p style="font-size: 0.85rem; line-height: 1.5; color: var(--color-text-secondary-dark);"><?php echo esc($val['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 4. Milestone timelines -->
<?php if (!empty($milestones)): ?>
    <section class="section timeline-section">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">GROWTH MILESTONES</span>
                <h2>The WebFalx Evolution Timeline</h2>
            </div>
            
            <div class="vertical-timeline">
                <?php foreach ($milestones as $mile): ?>
                    <div class="timeline-milestone">
                        <div class="milestone-year"><?php echo esc($mile['year']); ?></div>
                        <div class="glass-card" style="display: flex; gap: var(--spacing-sm); align-items: center; flex-wrap: wrap; padding: 1rem;">
                            <?php if ($mile['image_url']): ?>
                                <img src="<?php echo esc_url($mile['image_url']); ?>" alt="<?php echo esc_attr($mile['title']); ?>" style="width: 100px; height: 75px; object-fit: cover; border-radius: var(--radius-sm);">
                            <?php endif; ?>
                            <div style="flex: 1; min-width: 200px;">
                                <h4 style="color: #ffffff; font-size: 1.15rem; margin-bottom: 4px;"><?php echo esc($mile['title']); ?></h4>
                                <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark);"><?php echo esc($mile['description']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 5. Team Members & Technical Skillprogress meters -->
<section class="section team-skills-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
    <div class="container reveal">
        <div class="grid grid-2" style="grid-template-columns: 1.3fr 1fr; align-items: start; gap: var(--spacing-md);">
            
            <!-- Left: Team list -->
            <div class="reveal-left">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">ENGINEERING STAFF</span>
                <h2 style="margin-bottom: var(--spacing-md); color: #ffffff;">Meet Our Technical Architects</h2>
                
                <div class="grid grid-2" style="gap: var(--spacing-sm);">
                    <?php foreach ($team as $member): ?>
                        <div class="glass-card glow-card" style="padding: 1rem; text-align: center; border-radius: var(--radius-md);">
                            <img src="<?php echo esc_url($member['image_url']); ?>" alt="<?php echo esc_attr($member['name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.05); margin-bottom: 10px;">
                            <h4 style="color: #ffffff; font-size: 1.05rem;"><?php echo esc($member['name']); ?></h4>
                            <span style="font-size: 0.75rem; color: var(--color-accent); font-weight: 600; display: block; margin-bottom: 6px;"><?php echo esc($member['designation']); ?></span>
                            <p style="font-size: 0.8rem; color: var(--color-text-secondary-dark); line-height: 1.4; margin-bottom: 8px;"><?php echo esc($member['bio']); ?></p>
                            
                            <!-- Display comma skills list -->
                            <div style="font-size: 0.7rem; color: var(--color-text-muted-dark); margin-bottom: 8px;">
                                <?php echo esc($member['skills']); ?>
                            </div>
                            
                            <!-- Social links icon mappings -->
                            <div style="display: flex; gap: 8px; justify-content: center; font-size: 0.75rem; color: var(--color-text-muted-dark);">
                                <?php 
                                $links = json_decode($member['social_links_json'] ?: '{}', true);
                                foreach ($links as $social => $url):
                                ?>
                                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" style="text-transform: capitalize; color: var(--color-accent);"><?php echo esc($social); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Right: Skills meters -->
            <div class="reveal-right" style="position: sticky; top: 90px;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">INDEX CAPABILITIES</span>
                <h2 style="margin-bottom: var(--spacing-md); color: #ffffff;">Agency Performance Ratings</h2>
                
                <div class="glass-card" style="padding: var(--spacing-md);">
                    <?php foreach ($skills as $skill): ?>
                        <div class="skill-bar-wrapper">
                            <div class="skill-bar-header">
                                <span><?php echo esc($skill['name']); ?></span>
                                <span><?php echo esc($skill['percentage']); ?>%</span>
                            </div>
                            <div class="skill-bar-track">
                                <div class="skill-bar-fill" data-percentage="<?php echo esc_attr($skill['percentage']); ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 6. Certifications & Awards -->
<?php if (!empty($certifications) || !empty($awards)): ?>
    <section class="section cert-awards-section">
        <div class="container reveal">
            <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
                <!-- Certifications -->
                <?php if (!empty($certifications)): ?>
                    <div class="reveal-left">
                        <h3 style="font-size: 1.5rem; color: #ffffff; margin-bottom: 1rem;">Industry Certifications</h3>
                        <div style="display: flex; flex-direction: column; gap: var(--spacing-xs);">
                            <?php foreach ($certifications as $cert): ?>
                                <div class="glass-card" style="display: flex; gap: var(--spacing-xs); align-items: center; padding: 10px;">
                                    <?php if ($cert['logo_url']): ?>
                                        <img src="<?php echo esc_url($cert['logo_url']); ?>" alt="<?php echo esc_attr($cert['title']); ?>" style="width: 50px; height: 50px; object-fit: contain;">
                                    <?php endif; ?>
                                    <div>
                                        <h4 style="font-size: 1rem; color: #ffffff;"><?php echo esc($cert['title']); ?></h4>
                                        <span style="font-size: 0.75rem; color: var(--color-text-secondary-dark);"><?php echo esc($cert['issuer']); ?> &bull; <?php echo esc($cert['year']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Awards -->
                <?php if (!empty($awards)): ?>
                    <div class="reveal-right">
                        <h3 style="font-size: 1.5rem; color: #ffffff; margin-bottom: 1rem;">Awards & Recognitions</h3>
                        <div style="display: flex; flex-direction: column; gap: var(--spacing-xs);">
                            <?php foreach ($awards as $award): ?>
                                <div class="glass-card" style="display: flex; gap: var(--spacing-xs); align-items: center; padding: 10px;">
                                    <?php if ($award['logo_url']): ?>
                                        <img src="<?php echo esc_url($award['logo_url']); ?>" alt="<?php echo esc_attr($award['title']); ?>" style="width: 50px; height: 50px; object-fit: contain;">
                                    <?php endif; ?>
                                    <div>
                                        <h4 style="font-size: 1rem; color: #ffffff;"><?php echo esc($award['title']); ?></h4>
                                        <span style="font-size: 0.75rem; color: var(--color-text-secondary-dark);"><?php echo esc($award['issuer']); ?> &bull; <?php echo esc($award['year']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 7. Tech stacks & Industries -->
<?php if (!empty($technologies) || !empty($industries)): ?>
    <section class="section tech-industries-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container reveal">
            <div class="grid grid-2" style="gap: var(--spacing-md); align-items: start;">
                <!-- Tech stacks -->
                <?php if (!empty($technologies)): ?>
                    <div class="reveal-left">
                        <h3 style="font-size: 1.5rem; color: #ffffff; margin-bottom: 1rem;">Engineering Stack</h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <?php foreach ($technologies as $tech): ?>
                                <span style="font-size: 0.8rem; padding: 6px 12px; background: rgba(255,255,255,0.02); border: var(--border-glass); border-radius: var(--radius-sm); color: #ffffff;"><?php echo esc($tech['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Industries Served -->
                <?php if (!empty($industries)): ?>
                    <div class="reveal-right">
                        <h3 style="font-size: 1.5rem; color: #ffffff; margin-bottom: 1rem;">Industries We Serve</h3>
                        <div style="display: flex; flex-direction: column; gap: var(--spacing-xs);">
                            <?php foreach ($industries as $ind): ?>
                                <div class="glass-card" style="padding: 10px;">
                                    <h4 style="font-size: 1rem; color: #ffffff; margin-bottom: 2px;"><?php echo esc($ind['name']); ?></h4>
                                    <p style="font-size: 0.8rem; color: var(--color-text-secondary-dark);"><?php echo esc($ind['description']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 8. Conversion CTA -->
<section id="contact" class="section cta-section" style="padding: var(--spacing-lg) 0;">
    <div class="container reveal">
        <div class="glass-card glow-card" style="text-align: center; padding: var(--spacing-lg) var(--spacing-md); position: relative; overflow: hidden; border-radius: var(--radius-lg);">
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: var(--gradient-dark-glow); z-index: 1; pointer-events: none;"></div>
            
            <div style="position: relative; z-index: 2; max-width: 700px; margin: 0 auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">READY TO CODE?</span>
                <h2 class="gradient-text" style="font-size: 2.5rem; margin-bottom: var(--spacing-sm); line-height: 1.2;">Let's Collaborate On Your Product</h2>
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
