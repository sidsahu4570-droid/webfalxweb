<?php
/**
 * WebFalx - Dedicated Meta Ads Campaign Landing Page (/offer)
 * Offer: "Get a Complete 10-Page Website + 1 Month of FREE Social Media Growth"
 * Psychologically Persuasive, Mobile-First CRO Design for Cold Traffic
 */

require_once __DIR__ . '/includes/functions.php';

$success_message = '';
$error_message = '';
$lead_id_generated = '';

// -------------------------------------------------------------
// FORM SUBMISSION PROCESSING
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Honeypot check - Bot protection
        if (!empty($_POST['website_hp'])) {
            // Silently redirect bots without saving or firing conversion pixel
            flash_message('offer_success', 'Thank you! Your inquiry has been submitted.', 'success');
            header('Location: ' . BASE_URL . 'offer');
            exit;
        }

        // 2. CSRF Token Verification
        require_csrf_token();

        // 3. Sanitize and Extract Form Fields
        $full_name       = sanitize_input($_POST['full_name'] ?? '');
        $business_name   = sanitize_input($_POST['business_name'] ?? '');
        $industry        = sanitize_input($_POST['industry'] ?? '');
        $phone           = sanitize_input($_POST['phone'] ?? '');
        $email           = sanitize_input($_POST['email'] ?? '');
        $online_presence = sanitize_input($_POST['online_presence'] ?? 'No Website');

        // 4. Strict Validation
        if (empty($full_name) || empty($business_name) || empty($industry) || empty($phone) || empty($email)) {
            throw new Exception("Please fill in all required fields marked with an asterisk (*).");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please provide a valid email address so we can send your confirmation.");
        }

        if ($db === null) {
            throw new Exception("Our lead intake system is temporarily undergoing maintenance. Please call or WhatsApp us directly.");
        }

        // 5. Generate Unique Lead Reference ID
        $lead_id_generated = 'WF-' . date('Ymd') . '-' . rand(1000, 9999);

        // 6. Save Lead into database
        $msg_details = "Business: {$business_name}\nIndustry: {$industry}\nCurrent Presence: {$online_presence}";

        $stmt = $db->prepare("INSERT INTO `leads` (
            `lead_id`, `lead_type`, `full_name`, `company_name`, `business_type`, 
            `phone`, `email`, `service_interested`, `subject`, `message`, 
            `ip_address`, `user_agent`, `status`, `priority`
        ) VALUES (
            :lid, 'meta_offer', :name, :company, :btype, 
            :phone, :email, '10-Page Website + 1 Month FREE Social Media', 
            'Meta Ads Campaign: 10-Page Website + 1 Month FREE Social Media', :msg, 
            :ip, :ua, 'New', 'High'
        )");

        $stmt->execute([
            ':lid'     => $lead_id_generated,
            ':name'    => $full_name,
            ':company' => $business_name,
            ':btype'   => $industry,
            ':phone'   => $phone,
            ':email'   => $email,
            ':msg'     => $msg_details,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':ua'      => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);

        // 7. Auto-reply simulation & server log
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        error_log("[META ADS OFFER LEAD] ID: {$lead_id_generated} | Name: {$full_name} | Business: {$business_name} | Phone: {$phone}\n", 3, $log_dir . '/error.log');

        // 8. Flash success message with Lead ID & redirect to clear POST data
        flash_message('offer_success', "Congratulations! Your 10-Page Website + 1 Month Free Social Media offer slot has been reserved. Your Reference ID is {$lead_id_generated}. Our team will contact you within 2 business hours to coordinate your 15-minute strategy call.", 'success');
        header('Location: ' . BASE_URL . 'offer?claimed=1#lead-form');
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Check for success flash message
$flash_success = flash_message('offer_success');
$is_new_lead_submitted = false;
if ($flash_success) {
    $success_message = $flash_success['message'];
    $is_new_lead_submitted = true;
}

// Configurable Scarcity Slots (From site_settings or fallback)
$monthly_slots_total = intval(get_setting('offer_slots_total', '5'));
$monthly_slots_left = intval(get_setting('offer_slots_left', '5'));

// SEO Configurations for Offer Page
$page_seo = [
    'title' => 'Complete 10-Page Website + 1 Month FREE Social Media Growth | WebFalx Special Offer',
    'description' => 'Take your business online in 7 days with a complete 10-page professional website plus 1 month of free social media management. Claim your offer today.',
    'canonical' => BASE_URL . 'offer'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Custom Offer Page Styles -->
<style>
    html {
        scroll-behavior: smooth;
    }
    
    .offer-page-wrapper {
        color: var(--color-text-primary-dark);
        overflow-x: hidden;
    }

    .highlight-gradient {
        background: linear-gradient(135deg, #38bdf8 0%, #a855f7 50%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: inline;
        font-weight: 800;
    }

    .urgency-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.35);
        color: #fca5a5;
        padding: 6px 16px;
        border-radius: var(--radius-full);
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: var(--spacing-sm);
    }

    .slots-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.35);
        color: #fcd34d;
        padding: 6px 14px;
        border-radius: var(--radius-sm);
        font-size: 0.85rem;
        font-weight: 700;
    }

    .offer-hero-section {
        padding: 40px 0 60px 0;
        position: relative;
    }

    .hero-headline {
        font-size: clamp(2.3rem, 5.5vw, 3.6rem);
        line-height: 1.12;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-bottom: 1.25rem;
    }

    .hero-subheadline {
        font-size: clamp(1.05rem, 2vw, 1.25rem);
        line-height: 1.6;
        color: var(--color-text-secondary-dark);
        margin-bottom: 1.5rem;
        max-width: 620px;
    }

    .hero-trust-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        font-size: 0.9rem;
        color: #cbd5e1;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .hero-trust-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }
    .hero-trust-item svg {
        color: #10b981;
        flex-shrink: 0;
    }

    .mockup-container {
        position: relative;
        background: radial-gradient(circle at center, rgba(37, 99, 235, 0.15) 0%, rgba(15, 23, 42, 0) 70%);
        border-radius: var(--radius-lg);
        padding: 20px;
    }

    .desktop-frame {
        background: #1e293b;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 30px rgba(6, 182, 212, 0.15);
        overflow: hidden;
        position: relative;
    }
    .browser-bar {
        background: #0f172a;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    .dot-red { background: #ef4444; }
    .dot-yellow { background: #f59e0b; }
    .dot-green { background: #10b981; }
    .browser-url {
        background: rgba(255, 255, 255, 0.06);
        border-radius: 4px;
        padding: 3px 12px;
        font-size: 0.75rem;
        color: #94a3b8;
        flex-grow: 1;
        max-width: 260px;
    }

    .mobile-float-frame {
        position: absolute;
        bottom: -20px;
        right: -10px;
        width: 180px;
        background: #0f172a;
        border: 2px solid rgba(6, 182, 212, 0.4);
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7);
        padding: 8px;
        z-index: 5;
    }

    .offer-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.85) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-lg);
        padding: 2.25rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        transition: transform var(--transition-fast), border-color var(--transition-fast), box-shadow var(--transition-fast);
    }
    .offer-card:hover {
        transform: translateY(-6px);
        border-color: rgba(6, 182, 212, 0.4);
        box-shadow: 0 20px 40px rgba(6, 182, 212, 0.12);
    }
    .offer-card-glow-free {
        border-color: rgba(168, 85, 247, 0.5);
        box-shadow: 0 10px 30px rgba(168, 85, 247, 0.15);
    }
    .offer-card-glow-free:hover {
        border-color: rgba(168, 85, 247, 0.7);
        box-shadow: 0 20px 40px rgba(168, 85, 247, 0.25);
    }
    .card-header-badge {
        font-family: var(--font-heading);
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--color-accent);
        margin-bottom: 0.5rem;
    }
    .feature-grid-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        margin: 1.5rem 0;
    }
    .feature-pill-item {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: var(--radius-sm);
        padding: 8px 12px;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #f1f5f9;
    }
    .feature-pill-item svg {
        color: #06b6d4;
        flex-shrink: 0;
    }

    .stack-card {
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(6, 182, 212, 0.3);
        border-radius: var(--radius-lg);
        padding: 2.5rem;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    }
    .stack-item {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 14px 20px;
        border-radius: var(--radius-md);
        font-size: 1.05rem;
        font-weight: 600;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .stack-plus {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--color-accent);
        margin: 8px 0;
    }

    .trust-card {
        background: rgba(30, 41, 59, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: var(--radius-md);
        padding: 1.75rem;
        transition: transform var(--transition-fast);
    }
    .trust-card:hover {
        transform: translateY(-4px);
        border-color: rgba(6, 182, 212, 0.3);
    }
    .trust-card-icon {
        font-size: 1.8rem;
        margin-bottom: 0.75rem;
    }

    .step-connector {
        position: relative;
    }
    @media (min-width: 769px) {
        .step-connector::after {
            content: '';
            position: absolute;
            top: 40px;
            left: 15%;
            right: 15%;
            height: 2px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #06b6d4);
            z-index: 0;
        }
    }
    .step-box {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-md);
        padding: 2rem 1.5rem;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .step-number {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--gradient-hero);
        color: #ffffff;
        font-family: var(--font-heading);
        font-size: 1.2rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem auto;
        box-shadow: 0 0 20px rgba(37, 99, 235, 0.5);
    }

    .lead-form-card {
        background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(6, 182, 212, 0.4);
        border-radius: var(--radius-lg);
        padding: 2.75rem;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 0 0 35px rgba(6, 182, 212, 0.15);
        position: relative;
    }
    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 6px;
    }
    .form-input-control {
        width: 100%;
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: var(--radius-sm);
        padding: 0.85rem 1rem;
        color: #ffffff;
        font-size: 0.95rem;
        outline: none;
        transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
        min-height: 48px;
    }
    .form-input-control:focus {
        border-color: var(--color-accent);
        box-shadow: 0 0 15px rgba(6, 182, 212, 0.25);
    }

    .btn-cta-pulse {
        background: linear-gradient(135deg, #2563eb 0%, #7c3aed 50%, #06b6d4 100%);
        color: #ffffff;
        font-family: var(--font-heading);
        font-weight: 700;
        border-radius: var(--radius-full);
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        animation: pulseCTA 2.5s infinite;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .btn-cta-pulse:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 30px rgba(6, 182, 212, 0.6);
    }
    @keyframes pulseCTA {
        0%, 100% { box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4); }
        50% { box-shadow: 0 8px 32px rgba(6, 182, 212, 0.6); }
    }

    .success-celebration-box {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(6, 182, 212, 0.15) 100%);
        border: 1px solid #10b981;
        border-radius: var(--radius-md);
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .mobile-sticky-bar {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(15, 23, 42, 0.95);
        border-top: 1px solid rgba(6, 182, 212, 0.3);
        padding: 12px 16px;
        z-index: 9998;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 -8px 25px rgba(0, 0, 0, 0.5);
    }
    .mobile-sticky-bar .btn-sticky {
        width: 100%;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 700;
        text-align: center;
        border-radius: var(--radius-full);
    }

    @media (max-width: 768px) {
        .mobile-sticky-bar {
            display: block;
        }
        .offer-hero-section {
            padding: 20px 0 40px 0;
        }
        .lead-form-card {
            padding: 1.5rem;
        }
        .offer-card {
            padding: 1.5rem;
        }
        .stack-card {
            padding: 1.5rem;
        }
        .mobile-float-frame {
            display: none;
        }
        body {
            padding-bottom: 70px;
        }
    }
</style>

<div class="offer-page-wrapper">

    <!-- ========================================================================= -->
    <!-- SECTION 1 — HERO / ABOVE THE FOLD -->
    <!-- ========================================================================= -->
    <section class="offer-hero-section">
        <div class="container">
            <div class="grid grid-2" style="align-items: center; gap: 3rem;">
                
                <!-- Hero Left -->
                <div>
                    <div class="urgency-badge">
                        🔥 LIMITED-TIME BUSINESS GROWTH OFFER
                    </div>

                    <h1 class="hero-headline">
                        Get a Complete <span class="highlight-gradient">10-Page Website</span> + <span class="highlight-gradient">1 Month FREE</span> Social Media Growth
                    </h1>

                    <p class="hero-subheadline">
                        Take your business online in 7 days with a professional website built to attract customers — plus 1 month of social media support at no extra cost.
                    </p>

                    <div style="margin-bottom: 1.75rem;">
                        <div class="slots-pill">
                            <span>⚡ LIMITED SLOTS THIS MONTH</span>
                            <span style="color: #ffffff; font-weight: 800;">• Only <?php echo $monthly_slots_left; ?> businesses accepted</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                        <a href="#lead-form" class="btn-cta-pulse" style="padding: 1rem 2rem; font-size: 1.05rem;">
                            CLAIM MY FREE MONTH →
                        </a>
                        <a href="#offer-breakdown" class="btn btn-secondary" style="padding: 0.95rem 1.6rem; font-size: 0.95rem;">
                            See What's Included ↓
                        </a>
                    </div>

                    <div class="hero-trust-bar">
                        <div class="hero-trust-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>No technical knowledge required</span>
                        </div>
                        <div class="hero-trust-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Mobile responsive</span>
                        </div>
                        <div class="hero-trust-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>30-day post-launch support</span>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2 — VISUAL ANCHOR -->
                <div>
                    <div class="mockup-container">
                        <div class="desktop-frame">
                            <div class="browser-bar">
                                <div class="dot dot-red"></div>
                                <div class="dot dot-yellow"></div>
                                <div class="dot dot-green"></div>
                                <div class="browser-url">yourbusiness.com</div>
                            </div>
                            
                            <div style="padding: 1.5rem; background: #0f172a;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.75rem;">
                                    <div style="font-weight: 800; font-size: 0.9rem; color: #ffffff;">
                                        YOUR BRAND<span style="color: var(--color-accent);">.</span>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <span style="width: 32px; height: 6px; background: rgba(255,255,255,0.2); border-radius: 3px;"></span>
                                        <span style="width: 32px; height: 6px; background: rgba(255,255,255,0.2); border-radius: 3px;"></span>
                                        <span style="width: 44px; height: 16px; background: var(--color-primary); border-radius: 4px;"></span>
                                    </div>
                                </div>

                                <div style="background: linear-gradient(135deg, rgba(37,99,235,0.15) 0%, rgba(124,58,237,0.15) 100%); border: 1px solid rgba(6,182,212,0.2); border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
                                    <span style="font-size: 0.65rem; color: var(--color-accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">MODERN &amp; HIGH-CONVERTING</span>
                                    <h4 style="font-size: 1.1rem; color: #ffffff; margin: 4px 0 8px 0;">Transform Visitors Into Paying Customers</h4>
                                    <p style="font-size: 0.75rem; color: var(--color-text-secondary-dark); margin: 0 0 10px 0;">Clean layouts, fast loading speeds, and inquiry forms engineered for customer growth.</p>
                                    <span style="display: inline-block; background: var(--color-accent); color: #0f172a; font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 4px;">Book Appointment</span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; padding: 8px; text-align: center;">
                                        <div style="font-size: 0.65rem; color: #94a3b8;">10 Complete Pages</div>
                                        <div style="font-size: 0.8rem; font-weight: 700; color: #38bdf8;">Custom Built</div>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; padding: 8px; text-align: center;">
                                        <div style="font-size: 0.65rem; color: #94a3b8;">Social Media</div>
                                        <div style="font-size: 0.8rem; font-weight: 700; color: #a855f7;">1 Month FREE</div>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; padding: 8px; text-align: center;">
                                        <div style="font-size: 0.65rem; color: #94a3b8;">Turnaround</div>
                                        <div style="font-size: 0.8rem; font-weight: 700; color: #10b981;">7 Days</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mobile-float-frame">
                            <div style="width: 36px; height: 3px; background: rgba(255,255,255,0.3); border-radius: 2px; margin: 0 auto 8px auto;"></div>
                            <div style="background: #1e293b; border-radius: 12px; padding: 10px; text-align: center;">
                                <div style="width: 100%; height: 60px; background: linear-gradient(135deg, #2563eb, #06b6d4); border-radius: 6px; margin-bottom: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #fff; font-weight: 700;">
                                    Mobile Optimized
                                </div>
                                <div style="font-size: 0.65rem; font-weight: 700; color: #ffffff;">100% Responsive</div>
                                <div style="font-size: 0.6rem; color: #10b981;">Fast &amp; Touch Friendly</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 3 — OFFER BREAKDOWN (Two Large Premium Cards) -->
    <!-- ========================================================================= -->
    <section id="offer-breakdown" class="section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass); scroll-margin-top: 80px;">
        <div class="container">
            <div style="text-align: center; max-width: 760px; margin: 0 auto 3rem auto;">
                <span class="card-header-badge">THE COMPLETE PACKAGE</span>
                <h2 style="font-size: clamp(1.8rem, 4vw, 2.6rem); line-height: 1.2; margin-top: 0.5rem;">
                    Everything You Need to Take Your Business Online
                </h2>
                <p style="color: var(--color-text-secondary-dark); font-size: 1rem; margin-top: 0.75rem;">
                    No hidden extras. No monthly website builder subscriptions. You get a complete digital asset ready to generate customer inquiries from day one.
                </p>
            </div>

            <div class="grid grid-2" style="gap: 2rem; align-items: stretch;">
                
                <!-- CARD 1: COMPLETE 10-PAGE WEBSITE -->
                <div class="offer-card">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <span class="card-header-badge">DELIVERABLE 01</span>
                            <span style="background: rgba(37, 99, 235, 0.15); border: 1px solid var(--color-primary); color: #93c5fd; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 700;">COMPLETE BUILD</span>
                        </div>

                        <h3 style="font-size: 1.6rem; color: #ffffff; margin-bottom: 0.75rem;">
                            01 — COMPLETE 10-PAGE WEBSITE
                        </h3>

                        <p style="font-size: 0.95rem; color: var(--color-text-secondary-dark); line-height: 1.6; margin-bottom: 1.25rem;">
                            A comprehensive, fully customized website structured to present your services with maximum credibility:
                        </p>

                        <div class="feature-grid-list">
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 1. Home Page</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 2. About Us</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 3. Services / Menu</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 4. Gallery / Portfolio</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 5. Pricing / Packages</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 6. Contact &amp; Location</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 7. Customer FAQs</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 8. Terms &amp; Policies</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 9. Blog / Updates</div>
                            <div class="feature-pill-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 10. Inquiry / Booking</div>
                        </div>

                        <div style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1rem; display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #cbd5e1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <strong>Fully mobile responsive</strong> (looks flawless on iPhone, Android &amp; tablets)
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #cbd5e1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <strong>SEO-ready structure</strong> (Google meta tags, sitemap, and search optimization)
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #cbd5e1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <strong>Fast loading speed</strong> (optimized assets for instant browsing)
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #cbd5e1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <strong>Modern premium design</strong> tailored directly to your brand
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #cbd5e1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <strong>Lead-generation focused</strong> with click-to-call &amp; WhatsApp actions
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: 1 MONTH FREE SOCIAL MEDIA GROWTH -->
                <div class="offer-card offer-card-glow-free">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <span class="card-header-badge" style="color: #c084fc;">DELIVERABLE 02</span>
                            <span style="background: rgba(168, 85, 247, 0.2); border: 1px solid #a855f7; color: #f3e8ff; padding: 4px 12px; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 800;">100% FREE BONUS</span>
                        </div>

                        <h3 style="font-size: 1.6rem; color: #ffffff; margin-bottom: 0.75rem;">
                            02 — 1 MONTH FREE SOCIAL MEDIA GROWTH
                        </h3>

                        <p style="font-size: 0.95rem; color: var(--color-text-secondary-dark); line-height: 1.6; margin-bottom: 1.25rem;">
                            We don’t just build your website and leave you stranded. We actively kickstart your social presence to build audience momentum:
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 1.5rem;">
                            <div class="feature-pill-item" style="padding: 12px 14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <div>
                                    <strong style="color: #ffffff;">Social profile setup &amp; optimization</strong>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">High-converting bio, branded banner graphics, and direct website links.</div>
                                </div>
                            </div>

                            <div class="feature-pill-item" style="padding: 12px 14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <div>
                                    <strong style="color: #ffffff;">8–12 Custom branded creative posts</strong>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">Professionally designed graphic creatives matching your brand colors.</div>
                                </div>
                            </div>

                            <div class="feature-pill-item" style="padding: 12px 14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <div>
                                    <strong style="color: #ffffff;">Persuasive caption writing &amp; hashtags</strong>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">Copy written to spark engagement and position you as the industry go-to.</div>
                                </div>
                            </div>

                            <div class="feature-pill-item" style="padding: 12px 14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <div>
                                    <strong style="color: #ffffff;">Content scheduling &amp; distribution</strong>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">Automated calendar publishing at peak engagement times.</div>
                                </div>
                            </div>

                            <div class="feature-pill-item" style="padding: 12px 14px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <div>
                                    <strong style="color: #ffffff;">Initial audience engagement &amp; brand consistency</strong>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">Unified aesthetic and voice across Instagram, Facebook, and LinkedIn.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="background: rgba(168, 85, 247, 0.1); border: 1px dashed rgba(168, 85, 247, 0.4); border-radius: var(--radius-sm); padding: 12px; text-align: center;">
                        <span style="font-size: 0.85rem; color: #f3e8ff;">🎁 Included at <strong style="color: #38bdf8;">$0 Extra Cost</strong> with your 10-Page Website Build.</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 4 — VALUE / OFFER STACK -->
    <!-- ========================================================================= -->
    <section class="section">
        <div class="container" style="max-width: 820px;">
            <div class="stack-card">
                <span class="card-header-badge">TOTAL OFFER STACK</span>
                <h2 style="font-size: clamp(1.8rem, 4vw, 2.4rem); margin: 0.5rem 0 1.5rem 0;">
                    What You Get In This Starter Package
                </h2>

                <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 2rem;">
                    <div class="stack-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        10-Page Custom Professional Website
                    </div>
                    <div class="stack-plus">+</div>
                    <div class="stack-item" style="border-color: rgba(168, 85, 247, 0.4); background: rgba(168, 85, 247, 0.08);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        1 Month Social Media Management (FREE)
                    </div>
                    <div class="stack-plus">+</div>
                    <div class="stack-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Mobile Responsive &amp; Fast-Loading Architecture
                    </div>
                    <div class="stack-plus">+</div>
                    <div class="stack-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Google Search SEO Setup &amp; Indexing
                    </div>
                    <div class="stack-plus">+</div>
                    <div class="stack-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Direct Lead Generation &amp; WhatsApp Integration
                    </div>
                    <div class="stack-plus">+</div>
                    <div class="stack-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        30-Day Post-Launch Technical Support
                    </div>
                </div>

                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
                    <div style="font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; letter-spacing: 2px; color: var(--color-accent); text-transform: uppercase; margin-bottom: 1.25rem;">
                        ONE COMPLETE DIGITAL STARTER PACKAGE
                    </div>
                    <a href="#lead-form" class="btn-cta-pulse" style="padding: 1.1rem 2.5rem; font-size: 1.1rem;">
                        CLAIM THIS BUNDLE NOW →
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 5 — URGENCY / WHY NOW -->
    <!-- ========================================================================= -->
    <section class="section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container" style="max-width: 900px; text-align: center;">
            <span class="card-header-badge" style="color: #ef4444;">ACT BEFORE YOUR COMPETITORS DO</span>
            <h2 style="font-size: clamp(1.9rem, 4.5vw, 2.8rem); line-height: 1.2; margin: 0.5rem 0 1rem 0;">
                Your Customers Are Already Searching Online.
            </h2>
            <p style="font-size: 1.15rem; color: var(--color-text-secondary-dark); line-height: 1.6; max-width: 720px; margin: 0 auto 2.5rem auto;">
                Every day your business stays without a professional online presence is another day potential customers are finding competitors instead.
            </p>

            <div class="grid grid-3" style="gap: 1.5rem; margin-bottom: 2.5rem;">
                <div class="trust-card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">✨</div>
                    <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.5rem;">Look Professional</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0;">Make a memorable first impression that positions you as the premium market choice.</p>
                </div>

                <div class="trust-card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🤝</div>
                    <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.5rem;">Build Instant Trust</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0;">Over 84% of consumers consider a business with a dedicated website more credible.</p>
                </div>

                <div class="trust-card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📈</div>
                    <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.5rem;">Generate Inquiries</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0;">Turn passive browsers into inbound phone calls, WhatsApp messages, and quote requests.</p>
                </div>
            </div>

            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: var(--radius-md); padding: 1.25rem; max-width: 650px; margin: 0 auto 2rem auto;">
                <div style="font-weight: 700; color: #fca5a5; font-size: 0.95rem; margin-bottom: 4px;">
                    🔥 LIMITED CAMPAIGN AVAILABILITY
                </div>
                <p style="font-size: 0.85rem; color: #cbd5e1; margin: 0;">
                    To maintain dedicated delivery quality, we are accepting only <strong><?php echo $monthly_slots_total; ?> businesses</strong> for this offer each month.
                </p>
            </div>

            <a href="#lead-form" class="btn-cta-pulse" style="padding: 1rem 2.2rem; font-size: 1.05rem;">
                CLAIM THE OFFER →
            </a>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 6 — WHY WEBFALX (4 Benefit-Focused Trust Cards) -->
    <!-- ========================================================================= -->
    <section class="section">
        <div class="container">
            <div style="text-align: center; max-width: 700px; margin: 0 auto 3rem auto;">
                <span class="card-header-badge">THE WEBFALX STANDARD</span>
                <h2 style="font-size: clamp(1.8rem, 4vw, 2.5rem); margin-top: 0.5rem;">
                    Why Businesses Trust WebFalx
                </h2>
                <p style="color: var(--color-text-secondary-dark); font-size: 0.95rem; margin-top: 0.5rem;">
                    We make going online seamless, fast, and stress-free.
                </p>
            </div>

            <div class="grid grid-2" style="gap: 1.5rem;">
                <div class="trust-card" style="display: flex; gap: 1.25rem; align-items: flex-start;">
                    <div class="trust-card-icon">⚡</div>
                    <div>
                        <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.35rem;">Fast 7-Day Turnaround</h3>
                        <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Get your business website launched quickly without months of delays or endless revisions.</p>
                    </div>
                </div>

                <div class="trust-card" style="display: flex; gap: 1.25rem; align-items: flex-start;">
                    <div class="trust-card-icon">🎯</div>
                    <div>
                        <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.35rem;">Conversion-Focused Design</h3>
                        <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Built to turn visitors into inquiries with clear calls to action and frictionless forms.</p>
                    </div>
                </div>

                <div class="trust-card" style="display: flex; gap: 1.25rem; align-items: flex-start;">
                    <div class="trust-card-icon">🛠</div>
                    <div>
                        <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.35rem;">Zero Technical Hassle</h3>
                        <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">We handle the hosting, DNS, coding, and technical setup for you from start to finish.</p>
                    </div>
                </div>

                <div class="trust-card" style="display: flex; gap: 1.25rem; align-items: flex-start;">
                    <div class="trust-card-icon">🛡</div>
                    <div>
                        <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 0.35rem;">30-Day Post-Launch Support</h3>
                        <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Get support and adjustments after your website goes live so you are never left alone.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 8 — SIMPLE 3-STEP PROCESS -->
    <!-- ========================================================================= -->
    <section class="section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container">
            <div style="text-align: center; max-width: 700px; margin: 0 auto 3rem auto;">
                <span class="card-header-badge">SIMPLE ONBOARDING</span>
                <h2 style="font-size: clamp(1.8rem, 4vw, 2.5rem); margin-top: 0.5rem;">
                    From Idea to Online in 3 Simple Steps
                </h2>
                <p style="color: var(--color-text-secondary-dark); font-size: 0.95rem;">
                    A streamlined, guided experience with zero confusion.
                </p>
            </div>

            <div class="grid grid-3 step-connector" style="gap: 2rem;">
                <div class="step-box">
                    <div class="step-number">1</div>
                    <h3 style="font-size: 1.25rem; color: #ffffff; margin-bottom: 0.5rem;">Submit Your Details</h3>
                    <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark); line-height: 1.5; margin: 0;">
                        Tell us about your business and what you need using our quick form below.
                    </p>
                </div>

                <div class="step-box">
                    <div class="step-number">2</div>
                    <h3 style="font-size: 1.25rem; color: #ffffff; margin-bottom: 0.5rem;">15-Minute Strategy Call</h3>
                    <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark); line-height: 1.5; margin: 0;">
                        We align on your branding, pages, goals, and requirements in one quick conversation.
                    </p>
                </div>

                <div class="step-box">
                    <div class="step-number">3</div>
                    <h3 style="font-size: 1.25rem; color: #ffffff; margin-bottom: 0.5rem;">Launch &amp; Grow</h3>
                    <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark); line-height: 1.5; margin: 0;">
                        Your website goes live and your 1-month social media support begins immediately.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 9 — LEAD CAPTURE FORM (Primary Conversion Section) -->
    <!-- ========================================================================= -->
    <section id="lead-form" class="section" style="position: relative; scroll-margin-top: 80px;">
        <div class="container" style="max-width: 820px;">

            <!-- Successful Submission Notification Box -->
            <?php if ($success_message): ?>
                <div class="success-celebration-box">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎉</div>
                    <h3 style="color: #10b981; font-size: 1.6rem; margin-bottom: 0.5rem;">Offer Claimed Successfully!</h3>
                    <p style="font-size: 1.05rem; color: #ffffff; max-width: 650px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                        <?php echo esc($success_message); ?>
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-primary" style="padding: 0.75rem 1.8rem;">
                            Direct Call (<?php echo esc(APP_PHONE); ?>)
                        </a>
                        <a href="https://wa.me/<?php echo esc_attr(get_setting('whatsapp_number', '16266273414')); ?>?text=<?php echo urlencode("Hello WebFalx! I just claimed the 10-Page Website + 1 Month FREE Social Media Offer."); ?>" target="_blank" rel="noopener" class="btn btn-secondary" style="padding: 0.75rem 1.8rem;">
                            Message on WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Meta Pixel Lead Event Trigger: Fires ONLY on Genuine Form Submission -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof fbq === 'function') {
                            fbq('track', 'Lead');
                            console.log('Meta Pixel: Lead event fired successfully after genuine submission.');
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger fade-in" style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--color-danger); color: #fca5a5; padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
                    <strong>Notice:</strong> <?php echo esc($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Lead Capture Form Card -->
            <div class="lead-form-card">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div class="urgency-badge" style="margin-bottom: 0.5rem;">SECURE YOUR FREE MONTH</div>
                    <h2 style="font-size: clamp(1.8rem, 4.5vw, 2.5rem); color: #ffffff; line-height: 1.2; margin-bottom: 0.5rem;">
                        Ready to Take Your Business Online?
                    </h2>
                    <p style="color: var(--color-text-secondary-dark); font-size: 1rem; max-width: 580px; margin: 0 auto;">
                        Claim your 10-page website + 1 month FREE social media offer.
                    </p>
                </div>

                <form action="<?php echo BASE_URL; ?>offer#lead-form" method="POST" id="offerCampaignForm" style="display: flex; flex-direction: column; gap: 16px;">
                    <?php echo csrf_field(); ?>

                    <!-- Honeypot field for bot spam elimination -->
                    <div class="contact-hp-field" style="display: none !important; visibility: hidden !important;">
                        <label for="website_hp">Leave empty</label>
                        <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Row 1: Full Name & Business Name -->
                    <div class="grid grid-2" style="gap: 16px;">
                        <div>
                            <label for="f_name" class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="f_name" class="form-input-control" required placeholder="e.g. John Smith" value="<?php echo esc_attr($_POST['full_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="b_name" class="form-label">Business Name *</label>
                            <input type="text" name="business_name" id="b_name" class="form-input-control" required placeholder="e.g. Apex Dental Clinic" value="<?php echo esc_attr($_POST['business_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Row 2: Industry & Phone/WhatsApp -->
                    <div class="grid grid-2" style="gap: 16px;">
                        <div>
                            <label for="ind_name" class="form-label">Industry *</label>
                            <input type="text" name="industry" id="ind_name" class="form-input-control" required placeholder="e.g. Healthcare, Legal, Retail, Consulting" value="<?php echo esc_attr($_POST['industry'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="phone_num" class="form-label">Phone / WhatsApp *</label>
                            <input type="tel" name="phone" id="phone_num" class="form-input-control" required placeholder="e.g. +1 626 627 3414" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Row 3: Email & Current Online Presence -->
                    <div class="grid grid-2" style="gap: 16px;">
                        <div>
                            <label for="email_addr" class="form-label">Email Address *</label>
                            <input type="email" name="email" id="email_addr" class="form-input-control" required placeholder="john@company.com" value="<?php echo esc_attr($_POST['email'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="pres_opt" class="form-label">Current Online Presence</label>
                            <select name="online_presence" id="pres_opt" class="form-input-control" style="background: #0f172a; color: #ffffff;">
                                <option value="No Website" <?php echo (($_POST['online_presence'] ?? '') === 'No Website') ? 'selected' : ''; ?>>No Website</option>
                                <option value="Outdated Website" <?php echo (($_POST['online_presence'] ?? '') === 'Outdated Website') ? 'selected' : ''; ?>>Outdated Website</option>
                                <option value="Social Media Only" <?php echo (($_POST['online_presence'] ?? '') === 'Social Media Only') ? 'selected' : ''; ?>>Social Media Only</option>
                                <option value="Existing Website — Looking for an Upgrade" <?php echo (($_POST['online_presence'] ?? '') === 'Existing Website — Looking for an Upgrade') ? 'selected' : ''; ?>>Existing Website — Looking for an Upgrade</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-cta-pulse" style="width: 100%; padding: 1.25rem; font-size: 1.15rem; font-weight: 700; border: none; margin-top: 10px;">
                        CLAIM MY 1-MONTH FREE OFFER →
                    </button>

                    <!-- Trust & Confidentiality Line -->
                    <div style="text-align: center; font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">
                        🔒 Your information is confidential. No spam.
                    </div>
                </form>
            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 10 — FINAL URGENCY CTA -->
    <!-- ========================================================================= -->
    <section class="section" style="padding: 4rem 0 5rem 0; text-align: center; background: radial-gradient(circle at center, rgba(37, 99, 235, 0.12) 0%, rgba(15, 23, 42, 0) 70%);">
        <div class="container" style="max-width: 800px;">
            <div style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-lg); padding: 3rem 2rem; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                
                <span class="card-header-badge" style="color: var(--color-accent); font-size: 0.8rem;">
                    YOUR BUSINESS DESERVES A BETTER DIGITAL PRESENCE
                </span>

                <h2 style="font-size: clamp(1.8rem, 4.5vw, 2.6rem); line-height: 1.2; margin: 0.75rem 0 1rem 0; color: #ffffff;">
                    Don't Let Another Customer Find Your Competitor First.
                </h2>

                <p style="font-size: 1.05rem; color: var(--color-text-secondary-dark); line-height: 1.6; max-width: 620px; margin: 0 auto 2rem auto;">
                    Claim the campaign offer and get your business online with a professional website + 1 month of social media growth support.
                </p>

                <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                    <a href="#lead-form" class="btn-cta-pulse" style="padding: 1.15rem 2.75rem; font-size: 1.1rem;">
                        CLAIM MY OFFER →
                    </a>
                    <span style="font-size: 0.85rem; color: #fca5a5; font-weight: 600;">
                        ⚡ Limited campaign availability.
                    </span>
                </div>

            </div>
        </div>
    </section>

</div>

<!-- ========================================================================= -->
<!-- MOBILE STICKY BOTTOM CTA BAR -->
<!-- ========================================================================= -->
<div class="mobile-sticky-bar">
    <a href="#lead-form" class="btn-cta-pulse btn-sticky">
        🔥 CLAIM 1-MONTH FREE OFFER →
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
