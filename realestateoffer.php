<?php
/**
 * WebFalx - Dedicated Meta Ads Campaign Landing Page (/realestateoffer)
 * Ultra-Compact, High-Converting 6-Section Landing Page
 * Core Offer: Complete 10-Page Website + 1 Month FREE Social Media Growth
 */

require_once __DIR__ . '/includes/functions.php';

$success_message = '';
$error_message = '';
$lead_id_generated = '';

// -------------------------------------------------------------
// 1. FORM SUBMISSION PROCESSING
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Honeypot check - Bot protection
        if (!empty($_POST['website_hp'])) {
            flash_message('offer_success', 'Thank you! Your inquiry has been submitted.', 'success');
            header('Location: ' . BASE_URL . 'realestateoffer');
            exit;
        }

        // CSRF Verification
        require_csrf_token();

        // Sanitize inputs
        $full_name       = sanitize_input($_POST['full_name'] ?? '');
        $business_name   = sanitize_input($_POST['business_name'] ?? '');
        $industry        = sanitize_input($_POST['industry'] ?? '');
        $phone           = sanitize_input($_POST['phone'] ?? '');
        $email           = sanitize_input($_POST['email'] ?? '');
        $online_presence = sanitize_input($_POST['online_presence'] ?? 'No Website');

        if (empty($full_name) || empty($business_name) || empty($industry) || empty($phone) || empty($email)) {
            throw new Exception("Please fill in all required fields marked with an asterisk (*).");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        if ($db === null) {
            throw new Exception("Database unavailable. Please contact us directly via WhatsApp or phone.");
        }

        // Generate Lead Reference ID
        $lead_id_generated = 'WF-' . date('Ymd') . '-' . rand(1000, 9999);

        // Store into existing database leads table
        $lead_message = "Business: {$business_name} | Industry: {$industry} | Presence: {$online_presence}";

        $stmt = $db->prepare("INSERT INTO `leads` (
            `lead_id`, `lead_type`, `full_name`, `company_name`, `business_type`, 
            `phone`, `email`, `service_interested`, `subject`, `message`, 
            `ip_address`, `user_agent`, `status`, `priority`
        ) VALUES (
            :lid, 'meta_offer', :name, :company, :btype, 
            :phone, :email, '10-Page Website + 1 Month FREE Social Media', 
            'Meta Ads: 10-Page Website + 1 Month FREE Social Media', :msg, 
            :ip, :ua, 'New', 'High'
        )");

        $stmt->execute([
            ':lid'     => $lead_id_generated,
            ':name'    => $full_name,
            ':company' => $business_name,
            ':btype'   => $industry,
            ':phone'   => $phone,
            ':email'   => $email,
            ':msg'     => $lead_message,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':ua'      => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);

        // Server log entry
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        error_log("[META ADS LEAD] ID: {$lead_id_generated} | Name: {$full_name} | Phone: {$phone}\n", 3, $log_dir . '/error.log');

        flash_message('offer_success', "Congratulations! Your offer slot has been reserved. Reference ID: {$lead_id_generated}. We will reach out within 2 hours for your 15-minute strategy call.", 'success');
        header('Location: ' . BASE_URL . 'realestateoffer?claimed=1#lead-form');
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$flash_success = flash_message('offer_success');
if ($flash_success) {
    $success_message = $flash_success['message'];
}

// Page SEO Metadata
$page_seo = [
    'title' => 'Complete 10-Page Website + 1 Month FREE Social Media | WebFalx Special Offer',
    'description' => 'Get a complete 10-page website plus 1 month of free social media management. Take your business online in 7 days.',
    'canonical' => BASE_URL . 'realestateoffer'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Minimal Header Override for Campaign Focus -->
<style>
    /* Streamline header navigation on /realestateoffer */
    .primary-navigation, .nav-actions .btn-secondary, .mobile-nav-toggle {
        display: none !important;
    }
    
    html {
        scroll-behavior: smooth;
    }

    /* Core Visual Tokens & Gradient Typography */
    .offer-gradient-text {
        background: linear-gradient(135deg, #38bdf8 0%, #a855f7 50%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
    }

    .offer-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.35);
        color: #fca5a5;
        padding: 5px 14px;
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.75rem;
    }

    .offer-slots-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.3);
        color: #fcd34d;
        padding: 4px 12px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 700;
    }

    /* Section Spacing */
    .compact-section {
        padding: 3rem 0;
    }

    /* CTA Button Pulse */
    .btn-offer-cta {
        background: linear-gradient(135deg, #2563eb 0%, #7c3aed 50%, #06b6d4 100%);
        color: #ffffff;
        font-family: var(--font-heading);
        font-weight: 700;
        padding: 0.95rem 2rem;
        font-size: 1.05rem;
        border-radius: var(--radius-full);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        border: none;
        cursor: pointer;
    }
    .btn-offer-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(6, 182, 212, 0.5);
    }

    /* Mockup Frame */
    .hero-mockup-wrap {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.6);
        overflow: hidden;
    }
    .mockup-top-bar {
        background: #0f172a;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .mockup-dot { width: 8px; height: 8px; border-radius: 50%; }

    /* Compact 2-Card Offer Grid */
    .compact-offer-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-md);
        padding: 1.75rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .compact-offer-card.card-free {
        border-color: rgba(168, 85, 247, 0.5);
        box-shadow: 0 10px 30px rgba(168, 85, 247, 0.15);
    }

    /* Checklist Columns */
    .checklist-2col {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px 14px;
        margin: 1rem 0;
    }
    .checklist-item {
        font-size: 0.88rem;
        color: #f1f5f9;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .checklist-item svg { color: #06b6d4; flex-shrink: 0; }

    /* Trust & Process Blocks */
    .trust-mini-card {
        background: rgba(30, 41, 59, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: var(--radius-sm);
        padding: 1.25rem;
        text-align: center;
    }
    .step-mini-card {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-sm);
        padding: 1.25rem;
        text-align: center;
    }

    /* Lead Form Card */
    .lead-form-box {
        background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(6, 182, 212, 0.4);
        border-radius: var(--radius-md);
        padding: 2rem;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
    }
    .form-control-compact {
        width: 100%;
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: var(--radius-sm);
        padding: 0.75rem 1rem;
        color: #ffffff;
        font-size: 0.9rem;
        outline: none;
    }
    .form-control-compact:focus {
        border-color: var(--color-accent);
    }

    /* Mobile Sticky Bar */
    .mobile-sticky-cta {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(15, 23, 42, 0.96);
        border-top: 1px solid rgba(6, 182, 212, 0.3);
        padding: 10px 16px;
        z-index: 9999;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    @media (max-width: 768px) {
        .mobile-sticky-cta { display: block; }
        .compact-section { padding: 2rem 0; }
        .lead-form-box { padding: 1.25rem; }
        body { padding-bottom: 60px; }
        .checklist-2col { grid-template-columns: 1fr; }
    }
</style>

<div class="offer-page-wrapper">

    <!-- ========================================================================= -->
    <!-- 1. HERO — ABOVE THE FOLD -->
    <!-- ========================================================================= -->
    <section class="compact-section" style="padding-top: 2rem; padding-bottom: 2.5rem;">
        <div class="container">
            <div class="grid grid-2" style="align-items: center; gap: 2rem;">
                
                <!-- Left Hero Content -->
                <div>
                    <div class="offer-badge">
                        🔥 LIMITED-TIME BUSINESS GROWTH OFFER
                    </div>

                    <h1 style="font-size: clamp(2.1rem, 4.5vw, 3.2rem); line-height: 1.15; font-weight: 800; margin-bottom: 1rem;">
                        Get a Complete <span class="offer-gradient-text">10-Page Website</span> + <span class="offer-gradient-text">1 Month FREE</span> Social Media
                    </h1>

                    <p style="font-size: 1.05rem; line-height: 1.5; color: var(--color-text-secondary-dark); margin-bottom: 1.25rem;">
                        Take your business online in 7 days with a professional website designed to build trust and generate inquiries.
                    </p>

                    <div style="margin-bottom: 1.5rem;">
                        <div class="offer-slots-tag">
                            <span>⚡ LIMITED CAMPAIGN SLOTS</span>
                            <span style="color: #ffffff;">• Only a limited number accepted each month</span>
                        </div>
                    </div>

                    <div>
                        <a href="#lead-form" class="btn-offer-cta">
                            CLAIM MY FREE MONTH →
                        </a>
                    </div>

                    <!-- 3 Trust Points -->
                    <div style="display: flex; flex-wrap: wrap; gap: 14px; font-size: 0.85rem; color: #cbd5e1; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">
                        <span>✓ No technical knowledge required</span>
                        <span>✓ Mobile responsive</span>
                        <span>✓ 30-day post-launch support</span>
                    </div>
                </div>

                <!-- Right Compact Mockup Preview -->
                <div>
                    <div class="hero-mockup-wrap">
                        <div class="mockup-top-bar">
                            <span class="mockup-dot" style="background:#ef4444;"></span>
                            <span class="mockup-dot" style="background:#f59e0b;"></span>
                            <span class="mockup-dot" style="background:#10b981;"></span>
                            <span style="background: rgba(255,255,255,0.08); border-radius: 4px; padding: 2px 10px; font-size: 0.7rem; color: #94a3b8;">yourbusiness.com</span>
                        </div>
                        
                        <div style="padding: 1.25rem; background: #0f172a;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <strong style="font-size: 0.85rem; color: #fff;">YOUR BRAND<span style="color:var(--color-accent)">.</span></strong>
                                <span style="background: var(--color-primary); color:#fff; font-size:0.65rem; padding:3px 8px; border-radius:4px;">Book Now</span>
                            </div>

                            <div style="background: linear-gradient(135deg, rgba(37,99,235,0.15), rgba(124,58,237,0.15)); border: 1px solid rgba(6,182,212,0.2); border-radius: 6px; padding: 1rem; margin-bottom: 0.75rem;">
                                <div style="font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 4px;">Attract &amp; Convert More Local Customers</div>
                                <div style="font-size: 0.75rem; color: var(--color-text-secondary-dark);">Fast loading, clean mobile UX, and high-converting lead forms.</div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; text-align: center;">
                                <div style="background: rgba(255,255,255,0.03); padding: 6px; border-radius: 4px; font-size: 0.7rem; color: #38bdf8; font-weight: 700;">10 Pages</div>
                                <div style="background: rgba(255,255,255,0.03); padding: 6px; border-radius: 4px; font-size: 0.7rem; color: #a855f7; font-weight: 700;">1 Mo Free Social</div>
                                <div style="background: rgba(255,255,255,0.03); padding: 6px; border-radius: 4px; font-size: 0.7rem; color: #10b981; font-weight: 700;">7-Day Launch</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 2. WHAT YOU GET (ONLY TWO LARGE CARDS) -->
    <!-- ========================================================================= -->
    <section class="compact-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container">
            <div style="text-align: center; max-width: 650px; margin: 0 auto 2rem auto;">
                <span style="font-size: 0.8rem; color: var(--color-accent); font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">COMPLETE BUNDLE</span>
                <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem); margin: 0.35rem 0 0.5rem 0;">
                    Everything You Need to Get Online
                </h2>
            </div>

            <div class="grid grid-2" style="gap: 1.5rem; align-items: stretch;">
                
                <!-- CARD 1: 10-PAGE CUSTOM WEBSITE -->
                <div class="compact-offer-card">
                    <div>
                        <span style="font-size: 0.75rem; color: var(--color-accent); font-weight: 700; text-transform: uppercase;">CORE DELIVERABLE</span>
                        <h3 style="font-size: 1.35rem; color: #ffffff; margin: 0.35rem 0 0.75rem 0;">
                            10-PAGE CUSTOM WEBSITE
                        </h3>

                        <div class="checklist-2col">
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Home</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> About Us</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Services</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Gallery / Portfolio</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Pricing</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Contact</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> FAQs</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Policies</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Blog / Updates</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Inquiry / Booking</div>
                        </div>
                    </div>

                    <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 0.85rem; display: flex; flex-wrap: wrap; gap: 8px; font-size: 0.8rem; color: #10b981; font-weight: 600;">
                        <span>✓ Mobile Responsive</span> • <span>✓ SEO Ready</span> • <span>✓ Fast Loading</span>
                    </div>
                </div>

                <!-- CARD 2: 1 MONTH FREE SOCIAL MEDIA -->
                <div class="compact-offer-card card-free">
                    <div>
                        <span style="font-size: 0.75rem; color: #c084fc; font-weight: 700; text-transform: uppercase;">BONUS INCLUDED</span>
                        <h3 style="font-size: 1.35rem; color: #ffffff; margin: 0.35rem 0 0.75rem 0;">
                            1 MONTH <span style="color:#a855f7; font-weight: 800;">FREE</span> SOCIAL MEDIA
                        </h3>

                        <div style="display: flex; flex-direction: column; gap: 8px; margin: 1rem 0;">
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Profile Setup &amp; Optimization</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 8–12 Branded Creative Posts</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Engaging Caption Writing</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Content Scheduling &amp; Publishing</div>
                            <div class="checklist-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Initial Audience Engagement</div>
                        </div>
                    </div>

                    <div style="background: rgba(168,85,247,0.12); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 0.8rem; color: #f3e8ff; text-align: center; font-weight: 600;">
                        🎁 100% Free with your website package
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 3. WHY TRUST WEBFALX (4 Compact Benefit Cards) -->
    <!-- ========================================================================= -->
    <section class="compact-section">
        <div class="container">
            <div style="text-align: center; max-width: 650px; margin: 0 auto 2rem auto;">
                <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem); margin-bottom: 0.35rem;">
                    Why Businesses Trust WebFalx
                </h2>
            </div>

            <!-- 4 Compact Benefit Badges -->
            <div class="grid grid-4" style="gap: 1rem;">
                <div class="trust-mini-card">
                    <div style="font-size: 1.4rem; margin-bottom: 4px;">⚡</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">7-Day Launch</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Get online fast without delays.</span>
                </div>
                <div class="trust-mini-card">
                    <div style="font-size: 1.4rem; margin-bottom: 4px;">🎯</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">Conversion Design</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Built to attract customer inquiries.</span>
                </div>
                <div class="trust-mini-card">
                    <div style="font-size: 1.4rem; margin-bottom: 4px;">🛠</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">Zero Tech Hassle</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">We handle the coding &amp; setup.</span>
                </div>
                <div class="trust-mini-card">
                    <div style="font-size: 1.4rem; margin-bottom: 4px;">🛡</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">30-Day Support</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Full assistance after launch.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 4. SIMPLE 3-STEP PROCESS -->
    <!-- ========================================================================= -->
    <section class="compact-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container" style="max-width: 860px;">
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <h2 style="font-size: clamp(1.5rem, 3.5vw, 2rem);">
                    From Idea to Online in 3 Steps
                </h2>
            </div>

            <div class="grid grid-3" style="gap: 1rem;">
                <div class="step-mini-card">
                    <div style="color: var(--color-accent); font-weight: 800; font-size: 1.1rem; margin-bottom: 4px;">01</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Submit Details</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Tell us about your business goals.</span>
                </div>
                <div class="step-mini-card">
                    <div style="color: var(--color-primary); font-weight: 800; font-size: 1.1rem; margin-bottom: 4px;">02</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">15-Min Strategy Call</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">We align on branding &amp; pages.</span>
                </div>
                <div class="step-mini-card">
                    <div style="color: #10b981; font-weight: 800; font-size: 1.1rem; margin-bottom: 4px;">03</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Launch &amp; Grow</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Website goes live + free social starts.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 5. LEAD FORM (MAIN CONVERSION SECTION) -->
    <!-- ========================================================================= -->
    <section id="lead-form" class="compact-section" style="scroll-margin-top: 60px;">
        <div class="container" style="max-width: 740px;">
            
            <?php if ($success_message): ?>
                <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; border-radius: var(--radius-sm); padding: 1.5rem; text-align: center; margin-bottom: 1.5rem;">
                    <h3 style="color: #10b981; font-size: 1.3rem; margin-bottom: 0.35rem;">🎉 Offer Slot Reserved!</h3>
                    <p style="color: #ffffff; font-size: 0.95rem; margin: 0;"><?php echo esc($success_message); ?></p>
                </div>
                <!-- Meta Pixel Lead Event Trigger ONLY after genuine submission -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof fbq === 'function') {
                            fbq('track', 'Lead');
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; border-radius: var(--radius-sm); padding: 1rem; color: #fca5a5; font-size: 0.9rem; margin-bottom: 1rem;">
                    <?php echo esc($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="lead-form-box">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <h2 style="font-size: clamp(1.6rem, 4vw, 2.2rem); color: #fff; margin-bottom: 0.35rem;">
                        Ready to Take Your Business Online?
                    </h2>
                    <p style="color: var(--color-text-secondary-dark); font-size: 0.95rem; margin: 0;">
                        Claim your 10-page website + 1 month FREE social media offer.
                    </p>
                </div>

                <form action="<?php echo BASE_URL; ?>realestateoffer#lead-form" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
                    <?php echo csrf_field(); ?>

                    <!-- Honeypot -->
                    <div style="display: none !important; visibility: hidden !important;">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid grid-2" style="gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Full Name *</label>
                            <input type="text" name="full_name" class="form-control-compact" required placeholder="John Smith" value="<?php echo esc_attr($_POST['full_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Business Name *</label>
                            <input type="text" name="business_name" class="form-control-compact" required placeholder="Your Business" value="<?php echo esc_attr($_POST['business_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid grid-2" style="gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Industry *</label>
                            <input type="text" name="industry" class="form-control-compact" required placeholder="e.g. Dental, Legal, Retail, Services" value="<?php echo esc_attr($_POST['industry'] ?? ''); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Phone / WhatsApp *</label>
                            <input type="tel" name="phone" class="form-control-compact" required placeholder="+1 626 627 3414" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid grid-2" style="gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Email *</label>
                            <input type="email" name="email" class="form-control-compact" required placeholder="john@company.com" value="<?php echo esc_attr($_POST['email'] ?? ''); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Current Online Presence</label>
                            <select name="online_presence" class="form-control-compact" style="background: #0f172a; color: #fff;">
                                <option value="No Website" <?php echo (($_POST['online_presence'] ?? '') === 'No Website') ? 'selected' : ''; ?>>No Website</option>
                                <option value="Outdated Website" <?php echo (($_POST['online_presence'] ?? '') === 'Outdated Website') ? 'selected' : ''; ?>>Outdated Website</option>
                                <option value="Social Media Only" <?php echo (($_POST['online_presence'] ?? '') === 'Social Media Only') ? 'selected' : ''; ?>>Social Media Only</option>
                                <option value="Existing Website — Looking for an Upgrade" <?php echo (($_POST['online_presence'] ?? '') === 'Existing Website — Looking for an Upgrade') ? 'selected' : ''; ?>>Existing Website — Looking for an Upgrade</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-offer-cta" style="width: 100%; padding: 1.1rem; font-size: 1.1rem; margin-top: 6px;">
                        CLAIM MY 1-MONTH FREE OFFER →
                    </button>

                    <div style="text-align: center; font-size: 0.8rem; color: #94a3b8;">
                        🔒 Your information is confidential. No spam.
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 6. FINAL CTA -->
    <!-- ========================================================================= -->
    <section class="compact-section" style="text-align: center; padding-top: 1rem; padding-bottom: 3.5rem;">
        <div class="container" style="max-width: 680px;">
            <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem); margin-bottom: 0.5rem;">
                Don't Let Another Customer Find Your Competitor First.
            </h2>
            <p style="font-size: 1rem; color: var(--color-text-secondary-dark); margin-bottom: 1.5rem;">
                Get your business online with a professional website + 1 month of social media support.
            </p>
            <a href="#lead-form" class="btn-offer-cta">
                CLAIM MY OFFER →
            </a>
        </div>
    </section>

</div>

<!-- Mobile Sticky Bottom CTA Bar -->
<div class="mobile-sticky-cta">
    <a href="#lead-form" class="btn-offer-cta" style="width: 100%; padding: 10px; font-size: 0.95rem;">
        🔥 CLAIM OFFER
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
