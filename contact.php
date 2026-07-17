<?php
/**
 * WebFalx Contact & Quote Request Page
 * High-Conversion Tabs Form, Secure Honeypot Protection, File Uploads, Lead Generation, and Auto-Reply Simulation
 */

require_once __DIR__ . '/includes/functions.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Honeypot check - Bot protection
        if (!empty($_POST['website_hp'])) {
            // Silently fail to mimic success so bots stop retry loops
            flash_message('contact_success', 'Thank you! Your message has been sent successfully.', 'success');
            header('Location: ' . BASE_URL . 'contact.php');
            exit;
        }

        // 2. CSRF check
        require_csrf_token();

        $form_type = sanitize_input($_POST['form_type'] ?? 'inquiry');
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $whatsapp = sanitize_input($_POST['whatsapp'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        
        $budget = sanitize_input($_POST['budget'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');
        
        // Specific Inquiry fields
        $city = sanitize_input($_POST['city'] ?? '');
        $state = sanitize_input($_POST['state'] ?? '');
        $country = sanitize_input($_POST['country'] ?? '');
        $business_type = sanitize_input($_POST['business_type'] ?? '');
        $service_interested = sanitize_input($_POST['service_interested'] ?? '');
        $deadline = sanitize_input($_POST['deadline'] ?? '');
        $contact_method = sanitize_input($_POST['contact_method'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? 'General Inquiry');
        
        // Specific Quote fields
        $num_pages = intval($_POST['num_pages'] ?? 0);
        $file_path = '';

        if (empty($full_name) || empty($phone) || empty($email) || empty($message)) {
            throw new Exception("Please fill out all required fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please provide a valid email address.");
        }

        // 3. Secure File Upload Logic
        if ($form_type === 'quote' && isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/assets/uploads/quotes/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['attachment_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'quote_' . time() . '_' . rand(100,999) . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            // Allow specs uploads only
            $allowedTypes = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip'];
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                throw new Exception("Invalid file extension. Allowed: PDF, DOC, DOCX, ZIP, PNG, JPG.");
            }
            
            if ($_FILES['attachment_file']['size'] > 10 * 1024 * 1024) {
                throw new Exception("File size exceeds max 10MB limit.");
            }

            if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], $targetPath)) {
                $file_path = 'assets/uploads/quotes/' . $fileName;
            } else {
                throw new Exception("Failed to save project specifications file.");
            }
        }

        if ($db === null) {
            throw new Exception("Lead management system database offline.");
        }

        // 4. Generate unique Lead ID (e.g. WF-202607-1234)
        $lead_id = 'WF-' . date('Ymd') . '-' . rand(1000, 9999);

        // 5. Save lead record into database
        $stmt = $db->prepare("INSERT INTO leads (lead_id, lead_type, full_name, phone, whatsapp, email, company_name, city, state, country, business_type, service_interested, budget, deadline, contact_method, subject, message, num_pages, file_path, ip_address, user_agent, status, priority) 
                              VALUES (:lid, :ltype, :name, :phone, :wa, :email, :company, :city, :state, :country, :btype, :service, :budget, :deadline, :method, :sub, :msg, :pages, :file, :ip, :ua, 'New', 'Medium')");
        
        $stmt->execute([
            'lid' => $lead_id,
            'ltype' => $form_type,
            'name' => $full_name,
            'phone' => $phone,
            'wa' => $whatsapp ?: null,
            'email' => $email,
            'company' => $company_name ?: null,
            'city' => $city ?: null,
            'state' => $state ?: null,
            'country' => $country ?: null,
            'btype' => $business_type ?: null,
            'service' => $service_interested ?: null,
            'budget' => $budget ?: null,
            'deadline' => $deadline ?: null,
            'method' => $contact_method ?: null,
            'sub' => $subject,
            'msg' => $message,
            'pages' => $num_pages ?: null,
            'file' => $file_path ?: null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);

        // 6. Simulate Auto-Reply dispatch (Writes to logs/error.log)
        $auto_subj = get_setting('auto_reply_subject', 'WebFalx Lead Received - WF-{id}');
        $auto_subj = str_replace('{id}', $lead_id, $auto_subj);
        
        $auto_tmpl = get_setting('auto_reply_template', 'Hello {name},\n\nLead WF-{id} logged.');
        $auto_tmpl = str_replace(['{name}', '{id}'], [$full_name, $lead_id], $auto_tmpl);

        // Write log simulation
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        error_log("[SMTP EMAIL DISPATCH] To: $email | Subject: $auto_subj | Content: " . str_replace("\n", " ", $auto_tmpl) . "\n", 3, $log_dir . '/error.log');

        flash_message('contact_success', "Thank you! Your project request has been logged successfully. Lead ID: $lead_id.", 'success');
        header('Location: ' . BASE_URL . 'contact.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch flash success messages
$flash_success = flash_message('contact_success');
if ($flash_success) {
    $success_message = $flash_success['message'];
}

$page_seo = [
    'title' => 'Contact Our Agency & Get a Free Code Audit | WebFalx',
    'description' => 'Get in touch with WebFalx. Request a custom web development quote or dial our Pasadena coordinates directly.'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero banner section -->
<section class="section contact-hero" style="padding: var(--spacing-xl) 0 var(--spacing-md) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%); overflow: hidden;">
    <div class="container reveal">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">CONNECT WITH US</span>
            <h1 class="gradient-text" style="font-size: 3rem; margin-top: 0.5rem; line-height: 1.2;">Partner With A Premium Developer Team</h1>
            <p style="font-size: 1.1rem; margin-top: var(--spacing-sm);">Select a tab below to request an estimate scope or submit general inquiries. Our engineering desk is online 24/7.</p>
        </div>
    </div>
</section>

<!-- 2. Main Page content area split -->
<section class="section contact-main" style="padding: var(--spacing-md) 0;">
    <div class="container">
        <div class="grid grid-2" style="grid-template-columns: 1fr 1.3fr; align-items: start; gap: var(--spacing-md);">
            
            <!-- Left Info Panel coordinates -->
            <div class="reveal-left" style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                <div class="glass-card">
                    <h3 style="color: #ffffff; font-size: 1.35rem; margin-bottom: var(--spacing-xs);">Agency Coordinates</h3>
                    
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem;">
                        <li>
                            <strong style="color: var(--color-accent); display: block; font-size: 0.75rem; text-transform: uppercase;">Direct Line</strong>
                            <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" style="font-size: 1.1rem; font-weight: 600; color: #ffffff;"><?php echo esc(APP_PHONE); ?></a>
                        </li>
                        <li>
                            <strong style="color: var(--color-secondary); display: block; font-size: 0.75rem; text-transform: uppercase;">Emergency Dispatch</strong>
                            <a href="mailto:<?php echo esc_attr(get_setting('contact_email', 'hello@webfalx.com')); ?>" style="color: #ffffff;"><?php echo esc(get_setting('contact_email', 'hello@webfalx.com')); ?></a>
                        </li>
                        <li>
                            <strong style="color: var(--color-text-muted-dark); display: block; font-size: 0.75rem; text-transform: uppercase;">Physical Office</strong>
                            <p style="color: #ffffff;"><?php echo esc(get_setting('contact_address', 'Pasadena, California, USA')); ?></p>
                        </li>
                        <li>
                            <strong style="color: var(--color-text-muted-dark); display: block; font-size: 0.75rem; text-transform: uppercase;">Business Hours</strong>
                            <p style="color: #ffffff;">Mon - Fri: 9:00 AM - 6:00 PM (PST)</p>
                            <small style="color: var(--color-accent);">Support Desk: 24/7/365</small>
                        </li>
                    </ul>
                </div>

                <!-- Floating/Static WhatsApp Box -->
                <div class="glass-card glow-card" style="border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.02);">
                    <h4 style="color: #10b981; margin-bottom: 4px;">WhatsApp Secure Routing</h4>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin-bottom: 12px;">Chat directly with a senior technical architect for immediate project review answers.</p>
                    <a href="https://wa.me/<?php echo esc_attr(get_setting('whatsapp_number', '16266273414')); ?>?text=<?php echo urlencode(get_setting('whatsapp_prefilled_msg', 'Hello WebFalx!')); ?>" target="_blank" rel="noopener" class="btn btn-primary" style="background: #10b981; box-shadow: none; width: 100%;">
                        Open WhatsApp Chat
                    </a>
                </div>
            </div>

            <!-- Right Interactive Forms Panel -->
            <div class="reveal-right">
                <div class="glass-card" style="padding: var(--spacing-md);">
                    
                    <!-- Tabs buttons -->
                    <div class="form-tab-btn-wrapper">
                        <div class="form-tab-btn active" data-target="panel-inquiry">General Inquiry</div>
                        <div class="form-tab-btn" data-target="panel-quote">Request Estimate</div>
                    </div>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success fade-in">
                            <?php echo esc($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger fade-in">
                            <?php echo esc($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form 1: General Inquiry panel -->
                    <div class="form-content-panel active" id="panel-inquiry">
                        <form action="" method="POST" class="inquiry-form" style="display: flex; flex-direction: column; gap: 10px;">
                            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                            <input type="hidden" name="form_type" value="inquiry">
                            
                            <!-- Bot protection Honeypot input -->
                            <div class="contact-hp-field">
                                <label for="website_hp">Leave empty</label>
                                <input type="text" name="website_hp" id="website_hp">
                            </div>

                            <div class="form-group">
                                <label for="inq_name">Full Name *</label>
                                <input type="text" name="full_name" id="inq_name" class="form-control" required placeholder="John Doe">
                            </div>

                            <div class="grid grid-2" style="gap: 10px;">
                                <div class="form-group">
                                    <label for="inq_phone">Phone Number *</label>
                                    <input type="text" name="phone" id="inq_phone" class="form-control" required placeholder="e.g. 6266273414">
                                </div>
                                <div class="form-group">
                                    <label for="inq_wa">WhatsApp Number</label>
                                    <input type="text" name="whatsapp" id="inq_wa" class="form-control" placeholder="Optional">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="inq_email">Email Address *</label>
                                <input type="email" name="email" id="inq_email" class="form-control" required placeholder="johndoe@company.com">
                            </div>

                            <div class="grid grid-3" style="gap: 10px;">
                                <div class="form-group">
                                    <label for="inq_city">City</label>
                                    <input type="text" name="city" id="inq_city" class="form-control" placeholder="Pasadena">
                                </div>
                                <div class="form-group">
                                    <label for="inq_state">State</label>
                                    <input type="text" name="state" id="inq_state" class="form-control" placeholder="California">
                                </div>
                                <div class="form-group">
                                    <label for="inq_country">Country</label>
                                    <input type="text" name="country" id="inq_country" class="form-control" placeholder="USA">
                                </div>
                            </div>

                            <div class="grid grid-2" style="gap: 10px;">
                                <div class="form-group">
                                    <label for="inq_service">Service Interested</label>
                                    <select name="service_interested" id="inq_service" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;">
                                        <option value="Shopify Development">Shopify Store</option>
                                        <option value="WordPress Website">WordPress Design</option>
                                        <option value="Custom PHP Portal">Custom PHP Portal</option>
                                        <option value="CRM System">CRM Automation</option>
                                        <option value="SEO Growth">SEO / Digital marketing</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="inq_budget">Estimated Budget</label>
                                    <select name="budget" id="inq_budget" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;">
                                        <option value="Under $2.5k">Under $2.5k</option>
                                        <option value="$2.5k - $5k">$2.5k - $5k</option>
                                        <option value="$5k - $10k">$5k - $10k</option>
                                        <option value="$10k+">$10k+</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="inq_subject">Subject *</label>
                                <input type="text" name="subject" id="inq_subject" class="form-control" required placeholder="e.g. Request quote for landing page">
                            </div>

                            <div class="form-group">
                                <label for="inq_message">Message Details *</label>
                                <textarea name="message" id="inq_message" rows="4" class="form-control" required placeholder="Describe your expectations..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Submit Inquiry</button>
                        </form>
                    </div>

                    <!-- Form 2: Request Quote panel -->
                    <div class="form-content-panel" id="panel-quote">
                        <form action="" method="POST" enctype="multipart/form-data" class="inquiry-form" style="display: flex; flex-direction: column; gap: 10px;">
                            <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                            <input type="hidden" name="form_type" value="quote">
                            
                            <!-- Bot protection Honeypot input -->
                            <div class="contact-hp-field">
                                <label for="quote_hp">Leave empty</label>
                                <input type="text" name="website_hp" id="quote_hp">
                            </div>

                            <div class="form-group">
                                <label for="quo_name">Full Name *</label>
                                <input type="text" name="full_name" id="quo_name" class="form-control" required placeholder="John Doe">
                            </div>

                            <div class="grid grid-2" style="gap: 10px;">
                                <div class="form-group">
                                    <label for="quo_phone">Phone Number *</label>
                                    <input type="text" name="phone" id="quo_phone" class="form-control" required placeholder="e.g. 6266273414">
                                </div>
                                <div class="form-group">
                                    <label for="quo_email">Email Address *</label>
                                    <input type="email" name="email" id="quo_email" class="form-control" required placeholder="johndoe@company.com">
                                </div>
                            </div>

                            <div class="grid grid-3" style="gap: 10px;">
                                <div class="form-group">
                                    <label for="quo_company">Company</label>
                                    <input type="text" name="company_name" id="quo_company" class="form-control" placeholder="Company Ltd">
                                </div>
                                <div class="form-group">
                                    <label for="quo_service">Project Type</label>
                                    <input type="text" name="service_interested" id="quo_service" class="form-control" placeholder="e.g. Shopify Store">
                                </div>
                                <div class="form-group">
                                    <label for="quo_pages">Estimated Pages Count</label>
                                    <input type="number" name="num_pages" id="quo_pages" class="form-control" value="5">
                                </div>
                            </div>

                            <div class="grid grid-2" style="gap: 10px;">
                                <div class="form-group">
                                    <label for="quo_budget">Project Budget</label>
                                    <select name="budget" id="quo_budget" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;">
                                        <option value="Under $2.5k">Under $2.5k</option>
                                        <option value="$2.5k - $5k">$2.5k - $5k</option>
                                        <option value="$5k - $10k">$5k - $10k</option>
                                        <option value="$10k+">$10k+</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="quo_timeline">Timeline Expectation</label>
                                    <input type="text" name="deadline" id="quo_timeline" class="form-control" placeholder="e.g. 4 Weeks">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="quo_file">Upload Scope/Design Document (Max 10MB)</label>
                                <input type="file" name="attachment_file" id="quo_file" class="form-control" style="padding: 6px;">
                                <small style="color: var(--color-text-muted-dark); font-size: 0.72rem;">Supported formats: PDF, DOC, DOCX, ZIP, PNG, JPG</small>
                            </div>

                            <div class="form-group">
                                <label for="quo_desc">Project Overview *</label>
                                <textarea name="message" id="quo_desc" rows="4" class="form-control" required placeholder="Describe what you want to achieve..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Submit Project Details</button>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- 3. FAQs matrix -->
<section class="section faq-section" style="padding-bottom: var(--spacing-lg);">
    <div class="container reveal">
        <div style="text-align: center; max-width: 700px; margin: 0 auto var(--spacing-lg) auto;">
            <span style="font-family: var(--font-heading); font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">FAQ MATRIX</span>
            <h2>Common Inquiry Questions</h2>
        </div>
        
        <div class="faq-accordion">
            <div class="glass-card faq-card" style="border-radius: var(--radius-sm);">
                <div class="faq-header">
                    <h4>How long does it take to compile an estimate?</h4>
                    <div class="faq-icon">+</div>
                </div>
                <div class="faq-body">
                    <div class="faq-content">
                        <p>Our senior technical architects review incoming specs cards within 2 hours, sending detailed email logs estimation summaries shortly after.</p>
                    </div>
                </div>
            </div>
            <div class="glass-card faq-card" style="border-radius: var(--radius-sm);">
                <div class="faq-header">
                    <h4>Do you offer direct phone calls scheduling?</h4>
                    <div class="faq-icon">+</div>
                </div>
                <div class="faq-body">
                    <div class="faq-content">
                        <p>Yes, clicking our sticky Call button routes dialing straight to our Pasadena desk team instantly.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating WhatsApp Widget (Prompt 6) -->
<a href="https://wa.me/<?php echo esc_attr(get_setting('whatsapp_number', '16266273414')); ?>?text=<?php echo urlencode(get_setting('whatsapp_prefilled_msg', 'Hello WebFalx!')); ?>" target="_blank" rel="noopener" class="floating-whatsapp-widget">
    <!-- Inline SVG WhatsApp Icon -->
    <svg width="28" height="28" fill="#ffffff" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.003 5.372 5.378 0 12.013 0c3.217.001 6.241 1.253 8.516 3.53C22.804 5.808 24 8.835 24 12.052c-.003 6.68-5.378 12.052-12.013 12.052-2.001-.001-3.968-.497-5.738-1.44L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.97C16.273 1.968 13.824 1.012 11.2 1.011c-5.437 0-9.86 4.371-9.864 9.8.001 1.957.521 3.867 1.509 5.568L1.75 22.25l6.096-1.597.001-.001z"/></svg>
</a>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
