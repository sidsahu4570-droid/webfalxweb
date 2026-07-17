<?php
/**
 * WebFalx Service Detail Page
 * Dynamic, SEO Optimized, Mobile-First Service Presentation with Secure Lead Capture
 */

require_once __DIR__ . '/includes/functions.php';

// 1. Resolve slug routing
$slug = sanitize_input($_GET['slug'] ?? '');
$service = null;
$category_name = 'General';

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'services.php');
    exit;
}

if ($db !== null) {
    try {
        // Query service record
        $stmt = $db->prepare("SELECT * FROM services WHERE slug = :slug AND is_active = 1 LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $service = $stmt->fetch();
        
        if ($service) {
            // Query Category Name
            if ($service['category_id']) {
                $cat_stmt = $db->prepare("SELECT name FROM service_categories WHERE id = :id LIMIT 1");
                $cat_stmt->execute(['id' => $service['category_id']]);
                $category_name = $cat_stmt->fetchColumn() ?: 'General';
            }
        }
    } catch (PDOException $e) {
        error_log("Failed to query service detail: " . $e->getMessage());
    }
}

// Redirect or show 404 if no service found
if (!$service) {
    $page_seo = ['title' => 'Service Not Found | WebFalx'];
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container" style="text-align:center; padding: var(--spacing-lg) 0;">';
    echo '<h2>Service Not Found</h2><p style="margin: 1rem 0;">The requested digital service is currently unavailable.</p>';
    echo '<a href="' . BASE_URL . 'services.php" class="btn btn-primary">Return to Catalog</a>';
    echo '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$service_id = $service['id'];
$success_message = '';
$error_message = '';

// 2. Handle Lead Intake Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF
        require_csrf_token();

        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        $budget = sanitize_input($_POST['budget'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');

        // Validation checks
        if (empty($full_name) || empty($phone) || empty($email) || empty($message)) {
            $error_message = 'Please fill out all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please provide a valid email address.';
        } else {
            // Save lead to database
            if ($db === null) {
                throw new Exception("Inquiry system database offline.");
            }
            
            $stmt = $db->prepare("INSERT INTO service_inquiries (service_id, full_name, phone, email, company_name, budget, message) 
                                  VALUES (:sid, :name, :phone, :email, :company, :budget, :msg)");
            $stmt->execute([
                'sid' => $service_id,
                'name' => $full_name,
                'phone' => $phone,
                'email' => $email,
                'company' => $company_name,
                'budget' => $budget,
                'msg' => $message
            ]);

            // Save success flag and redirect to prevent double POST on reload
            flash_message('inquiry_success', 'Thank you for your project details. A senior engineer will review your inquiry and contact you within 2 hours.', 'success');
            header('Location: ' . BASE_URL . 'service.php?slug=' . $service['slug']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Failed to process service inquiry: " . $e->getMessage());
        $error_message = 'Failed to submit inquiry: ' . $e->getMessage();
    }
}

// Check flash messages for successful redirects
$flash_success = flash_message('inquiry_success');
if ($flash_success) {
    $success_message = $flash_success['message'];
}

// 3. Fetch Related Data
$service_faqs = [];
$service_testimonials = [];
$related_services = [];

if ($db !== null) {
    try {
        // Service specific FAQs
        $faq_stmt = $db->prepare("SELECT question, answer FROM service_faqs WHERE service_id = :sid ORDER BY display_order ASC");
        $faq_stmt->execute(['sid' => $service_id]);
        $service_faqs = $faq_stmt->fetchAll();

        // Mapped Testimonials
        $test_stmt = $db->prepare("SELECT client_name, client_business, client_image_url, rating, review 
                                    FROM testimonials 
                                    WHERE service_id = :sid AND is_active = 1 
                                    ORDER BY display_order ASC");
        $test_stmt->execute(['sid' => $service_id]);
        $service_testimonials = $test_stmt->fetchAll();

        // Related Services in same category
        if ($service['category_id']) {
            $rel_stmt = $db->prepare("SELECT title, slug, description, icon_svg 
                                      FROM services 
                                      WHERE category_id = :cat_id AND id != :id AND is_active = 1 
                                      LIMIT 3");
            $rel_stmt->execute(['cat_id' => $service['category_id'], 'id' => $service_id]);
            $related_services = $rel_stmt->fetchAll();
        }
    } catch (PDOException $ex) {
        error_log("Failed fetching dynamic service properties: " . $ex->getMessage());
    }
}

// Fallback SEO meta
$meta_t = !empty($service['meta_title']) ? $service['meta_title'] : $service['title'] . " Services | WebFalx Agency";
$meta_d = !empty($service['meta_description']) ? $service['meta_description'] : $service['description'];

$page_seo = [
    'title' => $meta_t,
    'description' => $meta_d,
    'canonical' => BASE_URL . 'service.php?slug=' . $service['slug']
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero banner section with background image -->
<section class="section service-hero" style="background: linear-gradient(180deg, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.98) 100%), url('<?php echo esc_url($service['hero_image'] ?: BASE_URL . 'assets/images/hero-default.jpg'); ?>') no-repeat center center / cover; padding: var(--spacing-xl) 0 var(--spacing-md) 0; position: relative;">
    <div class="container reveal" style="position: relative; z-index: 2;">
        <div style="max-width: 800px;">
            <div style="display: flex; gap: 8px; align-items: center; font-size: 0.8rem; font-weight: 600; color: var(--color-accent); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                <span>Services</span> &rsaquo; <span><?php echo esc($category_name); ?></span>
            </div>
            <h1 class="gradient-text" style="font-size: 3.5rem; line-height: 1.1; margin-bottom: var(--spacing-xs);"><?php echo esc($service['title']); ?></h1>
            <p style="font-size: 1.15rem; color: var(--color-text-secondary-dark); max-width: 650px;"><?php echo esc($service['description']); ?></p>
        </div>
    </div>
</section>

<!-- 2. Main Page content area (Split column grid) -->
<section class="section service-content-section" style="padding: var(--spacing-md) 0;">
    <div class="container">
        <!-- Split structure: Left details, Right sticky inquiry card -->
        <div class="grid grid-2" style="grid-template-columns: 1.6fr 1fr; align-items: start; gap: var(--spacing-md);">
            
            <!-- Left Info Panel -->
            <div class="reveal-left" style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                
                <!-- Service Overview -->
                <div class="glass-card" style="padding: var(--spacing-md);">
                    <h2 style="font-size: 1.75rem; margin-bottom: var(--spacing-sm); border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.5rem; color: #ffffff;">Service Overview</h2>
                    <p style="font-size: 1rem; line-height: 1.6; color: var(--color-text-secondary-dark);">
                        <?php echo esc($service['full_description'] ?: $service['description']); ?>
                    </p>
                </div>

                <!-- Benefits and Features Lists -->
                <?php if (!empty($service['features']) || !empty($service['benefits'])): ?>
                    <div class="grid grid-2" style="gap: 15px;">
                        <!-- Features -->
                        <?php if (!empty($service['features'])): ?>
                            <div class="glass-card" style="padding: var(--spacing-sm);">
                                <h3 style="font-size: 1.25rem; margin-bottom: var(--spacing-xs); color: var(--color-accent);">Key Features</h3>
                                <ul style="list-style: none; display: flex; flex-direction: column; gap: 6px; font-size: 0.9rem;">
                                    <?php
                                    $features = explode("\n", $service['features']);
                                    foreach ($features as $feat):
                                        if (trim($feat) !== ''):
                                    ?>
                                        <li style="display: flex; align-items: center; gap: 8px;">
                                            <span style="color: var(--color-accent); font-weight: bold;">&bull;</span> <?php echo esc($feat); ?>
                                        </li>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Benefits -->
                        <?php if (!empty($service['benefits'])): ?>
                            <div class="glass-card" style="padding: var(--spacing-sm);">
                                <h3 style="font-size: 1.25rem; margin-bottom: var(--spacing-xs); color: var(--color-secondary);">Business Benefits</h3>
                                <ul style="list-style: none; display: flex; flex-direction: column; gap: 6px; font-size: 0.9rem;">
                                    <?php
                                    $benefits = explode("\n", $service['benefits']);
                                    foreach ($benefits as $ben):
                                        if (trim($ben) !== ''):
                                    ?>
                                        <li style="display: flex; align-items: center; gap: 8px;">
                                            <span style="color: var(--color-success); font-weight: bold;">&#10003;</span> <?php echo esc($ben); ?>
                                        </li>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Technologies Used -->
                <?php if (!empty($service['technologies'])): ?>
                    <div class="glass-card" style="padding: var(--spacing-sm);">
                        <h4 style="font-size: 1.1rem; color: #ffffff; margin-bottom: var(--spacing-xs);">Engineering Stack Used</h4>
                        <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-xs);">
                            <?php
                            $techs = explode(',', $service['technologies']);
                            foreach ($techs as $tech):
                            ?>
                                <span style="font-size: 0.8rem; padding: 4px 10px; background: rgba(255,255,255,0.03); border: var(--border-glass); border-radius: var(--radius-sm); color: #ffffff;"><?php echo esc(trim($tech)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Pricing Packages (Dynamic JSON load) -->
                <?php
                if (!empty($service['packages_json'])):
                    $packages = json_decode($service['packages_json'], true);
                    if (is_array($packages) && count($packages) > 0):
                ?>
                    <div style="margin-top: var(--spacing-sm);">
                        <h2 style="font-size: 1.75rem; margin-bottom: var(--spacing-xs); color: #ffffff;">Pricing & Packages</h2>
                        <div class="packages-grid">
                            <?php foreach ($packages as $pkg): ?>
                                <div class="glass-card package-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <h3 style="font-size: 1.35rem; color: #ffffff;"><?php echo esc($pkg['name']); ?></h3>
                                        <div class="package-price"><?php echo esc($pkg['price']); ?></div>
                                        <ul class="package-features-list">
                                            <?php foreach ($pkg['features'] as $feat): ?>
                                                <li><?php echo esc($feat); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div style="margin-top: var(--spacing-sm);">
                                        <a href="#inquire-form" class="btn btn-secondary" style="width: 100%;">Select Tier</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                    endif;
                endif;
                ?>

            </div>

            <!-- Right Info Panel (Sticky Lead Capture Form) -->
            <div class="reveal-right" style="position: sticky; top: 90px; z-index: 10;">
                <div class="glass-card glow-card" id="inquire-form" style="padding: var(--spacing-md); border-color: rgba(6,182,212,0.2);">
                    <h3 style="font-size: 1.5rem; margin-bottom: 4px; color: #ffffff;">Connect With An Engineer</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin-bottom: var(--spacing-sm);">Fill out your scope details below to get a free estimate.</p>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success fade-in" style="font-size: 0.85rem; padding: 8px 12px; margin-bottom: var(--spacing-sm);">
                            <?php echo esc($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger fade-in" style="font-size: 0.85rem; padding: 8px 12px; margin-bottom: var(--spacing-sm);">
                            <?php echo esc($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="inquiry-form" style="display: flex; flex-direction: column; gap: 8px;">
                        <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="full_name" style="font-size: 0.75rem; margin-bottom: 2px;">Full Name *</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Acme client" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="phone" style="font-size: 0.75rem; margin-bottom: 2px;">Phone Number *</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g. 6266273414" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="email" style="font-size: 0.75rem; margin-bottom: 2px;">Email Address *</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="client@acme.com" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="company_name" style="font-size: 0.75rem; margin-bottom: 2px;">Company Name</label>
                            <input type="text" name="company_name" id="company_name" class="form-control" placeholder="Acme Corp">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="budget" style="font-size: 0.75rem; margin-bottom: 2px;">Estimated Budget</label>
                            <select name="budget" id="budget" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;">
                                <option value="Under $2.5k">Under $2.5k</option>
                                <option value="$2.5k - $5k">$2.5k - $5k</option>
                                <option value="$5k - $10k">$5k - $10k</option>
                                <option value="$10k+">$10k+</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="message" style="font-size: 0.75rem; margin-bottom: 2px;">Project Scope *</label>
                            <textarea name="message" id="message" rows="3" class="form-control" required placeholder="Describe your expectations..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--spacing-xs);">Submit Scope</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 3. Dynamic Service Testimonials -->
<?php if (!empty($service_testimonials)): ?>
    <section class="section testimonials-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">CLIENT WORK REVIEWS</span>
                <h2 style="margin-top: 0.5rem; line-height: 1.2;">What Clients Say About Our <?php echo esc($service['title']); ?> work</h2>
            </div>
            
            <div class="testimonial-track">
                <?php foreach ($service_testimonials as $item): ?>
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

<!-- 4. Dynamic Service FAQs Accordion -->
<?php if (!empty($service_faqs)): ?>
    <section class="section faq-section">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">FAQ MATRIX</span>
                <h2 style="margin-top: 0.5rem; line-height: 1.2;">Service-Specific FAQs</h2>
            </div>
            
            <div class="faq-accordion">
                <?php foreach ($service_faqs as $faq): ?>
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
<?php endif; ?>

<!-- 5. Related Services list -->
<?php if (!empty($related_services)): ?>
    <section class="section related-services-section" style="background: #090e1a; border-top: var(--border-glass);">
        <div class="container reveal">
            <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
                <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">SIMILAR SERVICES</span>
                <h2 style="margin-top: 0.5rem; line-height: 1.2;">You May Also Be Interested In</h2>
            </div>
            
            <div class="grid grid-3">
                <?php foreach ($related_services as $rel): ?>
                    <div class="glass-card glow-card" style="display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-md);">
                        <div>
                            <div style="color: var(--color-accent); margin-bottom: 0.75rem;">
                                <?php echo $rel['icon_svg']; ?>
                            </div>
                            <h3 style="font-size: 1.25rem; color: #ffffff; margin-bottom: 0.5rem;"><?php echo esc($rel['title']); ?></h3>
                            <p style="font-size: 0.85rem; line-height: 1.5; color: var(--color-text-secondary-dark);"><?php echo esc($rel['description']); ?></p>
                        </div>
                        <div style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.03); padding-top: 0.75rem;">
                            <a href="service.php?slug=<?php echo esc_attr($rel['slug']); ?>" style="font-size: 0.75rem; font-weight: 700; color: var(--color-accent); text-transform: uppercase;">
                                View Details &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- 6. Sticky Mobile CTA (Only triggers on small screens) -->
<div class="mobile-sticky-cta">
    <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-secondary" style="background: var(--color-bg-dark); border-color: rgba(255,255,255,0.1);">
        Call Now
    </a>
    <a href="https://wa.me/<?php echo esc_attr(APP_PHONE); ?>?text=<?php echo urlencode('Hello WebFalx! I would like to query info about: ' . $service['title']); ?>" target="_blank" rel="noopener" class="btn btn-primary" style="background: #10b981; box-shadow: none;">
        WhatsApp Chat
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
