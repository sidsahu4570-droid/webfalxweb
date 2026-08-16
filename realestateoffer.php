<?php
/**
 * WebFalx - Dedicated Real Estate Meta Ads Campaign Landing Page (/realestateoffer)
 * Target Audience: Real Estate Agents, Brokers, Builders, Developers, and Property Dealers
 * Core Offer: Professional Real Estate Website Built to Showcase Properties & Generate Enquiries
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
            flash_message('re_offer_success', 'Thank you! Your property website plan request has been received.', 'success');
            header('Location: ' . BASE_URL . 'realestateoffer');
            exit;
        }

        // CSRF Verification
        require_csrf_token();

        // Sanitize inputs
        $full_name        = sanitize_input($_POST['full_name'] ?? '');
        $business_name    = sanitize_input($_POST['business_name'] ?? '');
        $phone            = sanitize_input($_POST['phone'] ?? '');
        $email            = sanitize_input($_POST['email'] ?? '');
        $city             = sanitize_input($_POST['city'] ?? '');
        $business_type    = sanitize_input($_POST['business_type'] ?? 'Real Estate Agent');
        $current_website  = sanitize_input($_POST['current_website'] ?? 'No Website');
        $service_needed   = sanitize_input($_POST['service_needed'] ?? 'Property Listing Website');

        // Required validation
        if (empty($full_name) || empty($business_name) || empty($phone) || empty($city)) {
            throw new Exception("Please fill in all required fields (Full Name, Business Name, Phone/WhatsApp, and City).");
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        if ($db === null) {
            throw new Exception("Our lead intake system is temporarily offline. Please contact us directly via WhatsApp or Phone.");
        }

        // Generate Lead Reference ID
        $lead_id_generated = 'RE-' . date('Ymd') . '-' . rand(1000, 9999);

        // Build lead details summary
        $lead_notes = "Real Estate Website Plan Request\n" .
                      "Business Type: {$business_type}\n" .
                      "City: {$city}\n" .
                      "Current Website: {$current_website}\n" .
                      "Requirement: {$service_needed}";

        // Save into existing leads table
        $stmt = $db->prepare("INSERT INTO `leads` (
            `lead_id`, `lead_type`, `full_name`, `company_name`, `business_type`, 
            `phone`, `email`, `city`, `service_interested`, `subject`, `message`, 
            `ip_address`, `user_agent`, `status`, `priority`
        ) VALUES (
            :lid, 'real_estate_offer', :name, :company, :btype, 
            :phone, :email, :city, :service, :subject, :msg, 
            :ip, :ua, 'New', 'High'
        )");

        $stmt->execute([
            ':lid'     => $lead_id_generated,
            ':name'    => $full_name,
            ':company' => $business_name,
            ':btype'   => $business_type,
            ':phone'   => $phone,
            ':email'   => $email ?: 'noreply@webfalx.com',
            ':city'    => $city,
            ':service' => $service_needed,
            ':subject' => "Real Estate Website Plan: {$business_name} ({$city})",
            ':msg'     => $lead_notes,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':ua'      => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);

        // Server error log for SMTP fallback
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        error_log("[REAL ESTATE LEAD] ID: {$lead_id_generated} | Name: {$full_name} | Company: {$business_name} | City: {$city} | Phone: {$phone}\n", 3, $log_dir . '/error.log');

        flash_message('re_offer_success', "Thank you, {$full_name}! Your Real Estate Website Plan has been received (Ref: {$lead_id_generated}). Our real estate technical specialist will contact you within 2 business hours for your 15-minute strategy call.", 'success');
        header('Location: ' . BASE_URL . 'realestateoffer?submitted=1#lead-form');
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$flash_success = flash_message('re_offer_success');
if ($flash_success) {
    $success_message = $flash_success['message'];
}

// Configurable Scarcity Slots
$monthly_slots_left = intval(get_setting('real_estate_slots_left', '5'));

// Page Specific SEO Configurations
$page_seo = [
    'title' => 'Professional Real Estate Website for Agents, Brokers & Builders | WebFalx',
    'description' => 'Turn your property listings into a lead-generating real estate website. Integrated with WhatsApp, lead forms, property cards and Google maps.',
    'canonical' => BASE_URL . 'realestateoffer'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Minimal Header Override for Dedicated Campaign Focus -->
<style>
    /* Hide distraction navigation links on campaign landing page */
    .primary-navigation, .nav-actions .btn-secondary, .mobile-nav-toggle {
        display: none !important;
    }

    html {
        scroll-behavior: smooth;
    }

    /* Typography & Visual Identity */
    .re-gradient-text {
        background: linear-gradient(135deg, #38bdf8 0%, #a855f7 50%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
    }

    .re-badge {
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

    .re-slots-tag {
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

    .re-section {
        padding: 3.5rem 0;
    }

    /* Primary CTA Button */
    .btn-re-cta {
        background: linear-gradient(135deg, #2563eb 0%, #7c3aed 50%, #06b6d4 100%);
        color: #ffffff;
        font-family: var(--font-heading);
        font-weight: 700;
        padding: 1rem 2.2rem;
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
    .btn-re-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(6, 182, 212, 0.5);
    }

    /* Glass Cards */
    .re-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.75) 0%, rgba(15, 23, 42, 0.9) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: var(--radius-md);
        padding: 1.5rem;
        transition: transform var(--transition-fast), border-color var(--transition-fast);
    }
    .re-card:hover {
        transform: translateY(-4px);
        border-color: rgba(6, 182, 212, 0.35);
    }

    /* Simulated Property Listing Card */
    .prop-mockup-card {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
    }
    .prop-img-header {
        height: 140px;
        background: linear-gradient(135deg, #1e3a8a, #0f172a);
        position: relative;
        display: flex;
        align-items: flex-end;
        padding: 10px;
    }
    .prop-tag-featured {
        position: absolute;
        top: 10px;
        left: 10px;
        background: var(--color-accent);
        color: #0f172a;
        font-size: 0.65rem;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    .prop-price-tag {
        background: rgba(15, 23, 42, 0.85);
        color: #38bdf8;
        font-size: 0.85rem;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 4px;
    }

    /* Lead Form Card */
    .re-form-card {
        background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(6, 182, 212, 0.4);
        border-radius: var(--radius-lg);
        padding: 2.25rem;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(6, 182, 212, 0.12);
    }
    .re-input {
        width: 100%;
        background: rgba(15, 23, 42, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: var(--radius-sm);
        padding: 0.75rem 1rem;
        color: #ffffff;
        font-size: 0.9rem;
        outline: none;
        transition: border-color var(--transition-fast);
    }
    .re-input:focus {
        border-color: var(--color-accent);
    }

    /* Timeline step box */
    .re-step-box {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-md);
        padding: 1.5rem 1.25rem;
        text-align: center;
    }
    .re-step-num {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--gradient-hero);
        color: #fff;
        font-family: var(--font-heading);
        font-size: 1.1rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem auto;
    }

    /* Mobile Sticky Bar */
    .re-mobile-sticky {
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
        .re-mobile-sticky { display: block; }
        .re-section { padding: 2.25rem 0; }
        .re-form-card { padding: 1.25rem; }
        body { padding-bottom: 60px; }
    }
</style>

<div class="offer-page-wrapper">

    <!-- ========================================================================= -->
    <!-- 1. HERO — ABOVE THE FOLD -->
    <!-- ========================================================================= -->
    <section class="re-section" style="padding-top: 2rem; padding-bottom: 2.5rem;">
        <div class="container">
            <div class="grid grid-2" style="align-items: center; gap: 2.5rem;">
                
                <!-- Left Hero Column -->
                <div>
                    <div class="re-badge">
                        🔥 SPECIAL OFFER FOR REAL ESTATE BUSINESSES
                    </div>

                    <h1 style="font-size: clamp(2.2rem, 4.5vw, 3.3rem); line-height: 1.15; font-weight: 800; margin-bottom: 1rem;">
                        Turn Your <span class="re-gradient-text">Property Listings</span> Into a Lead-Generating Website
                    </h1>

                    <p style="font-size: 1.05rem; line-height: 1.6; color: var(--color-text-secondary-dark); margin-bottom: 1.25rem;">
                        Get a professional real estate website that showcases your properties, builds buyer trust and makes it easy for prospects to enquire on <strong style="color:#ffffff;">WhatsApp</strong> or through your website.
                    </p>

                    <div style="margin-bottom: 1.5rem;">
                        <div class="re-slots-tag">
                            <span>⚡ LIMITED CAMPAIGN SLOTS</span>
                            <span style="color: #ffffff;">• Only <?php echo $monthly_slots_left; ?> real estate businesses accepted this month</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                        <a href="#lead-form" class="btn-re-cta">
                            GET MY REAL ESTATE WEBSITE →
                        </a>
                        <span style="font-size: 0.85rem; color: #94a3b8;">
                            Free 15-minute website strategy call
                        </span>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 14px; font-size: 0.85rem; color: #cbd5e1; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">
                        <span>✓ Showcase unlimited properties</span>
                        <span>✓ Direct WhatsApp enquiries</span>
                        <span>✓ 7-day launch turnaround</span>
                    </div>
                </div>

                <!-- Right Real Estate Mockup Preview -->
                <div>
                    <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; overflow: hidden; box-shadow: 0 20px 45px rgba(0,0,0,0.6);">
                        <div style="background: #0f172a; padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08);">
                            <div style="display: flex; gap: 6px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span>
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span>
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                            </div>
                            <span style="background: rgba(255,255,255,0.06); border-radius: 4px; padding: 2px 10px; font-size: 0.7rem; color: #94a3b8;">yourrealestate.com/properties</span>
                            <span style="font-size: 0.65rem; color: #10b981; font-weight: 700;">● Live Preview</span>
                        </div>

                        <div style="padding: 1.25rem; background: #0f172a;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <strong style="font-size: 0.85rem; color: #fff;">ROYAL REALTY<span style="color:var(--color-accent)">.</span></strong>
                                <span style="background: rgba(6,182,212,0.15); color:var(--color-accent); font-size:0.65rem; padding:3px 8px; border-radius:4px; font-weight:700;">Verified Listings</span>
                            </div>

                            <!-- Mini Property Cards -->
                            <div class="grid grid-2" style="gap: 10px;">
                                <div class="prop-mockup-card">
                                    <div class="prop-img-header" style="background: linear-gradient(135deg, #1e3a8a, #065f46);">
                                        <span class="prop-tag-featured">Villa</span>
                                        <span class="prop-price-tag">$480,000</span>
                                    </div>
                                    <div style="padding: 8px 10px;">
                                        <div style="font-size: 0.8rem; font-weight: 700; color: #fff;">Palm Grove Residency</div>
                                        <div style="font-size: 0.68rem; color: #94a3b8;">📍 Prime Sector 45 • 4 BHK</div>
                                        <div style="margin-top: 6px; display: flex; gap: 4px;">
                                            <span style="background: #10b981; color:#fff; font-size:0.6rem; padding:2px 6px; border-radius:3px; font-weight:700;">WhatsApp Enquiry</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="prop-mockup-card">
                                    <div class="prop-img-header" style="background: linear-gradient(135deg, #581c87, #1e293b);">
                                        <span class="prop-tag-featured" style="background:#a855f7; color:#fff;">Apartment</span>
                                        <span class="prop-price-tag">$220,000</span>
                                    </div>
                                    <div style="padding: 8px 10px;">
                                        <div style="font-size: 0.8rem; font-weight: 700; color: #fff;">Horizon Heights 3BHK</div>
                                        <div style="font-size: 0.68rem; color: #94a3b8;">📍 Downtown View • 1,650 sq.ft</div>
                                        <div style="margin-top: 6px; display: flex; gap: 4px;">
                                            <span style="background: #10b981; color:#fff; font-size:0.6rem; padding:2px 6px; border-radius:3px; font-weight:700;">WhatsApp Enquiry</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 2. PAIN POINT SECTION -->
    <!-- ========================================================================= -->
    <section class="re-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container" style="max-width: 860px; text-align: center;">
            <span class="re-badge" style="background: rgba(239,68,68,0.1); color:#fca5a5;">COMMON FRUSTRATION</span>
            <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem); margin: 0.5rem 0 1rem 0;">
                Still Depending Only on WhatsApp &amp; Property Portals?
            </h2>

            <div class="grid grid-3" style="gap: 1rem; margin: 1.5rem 0 2rem 0; text-align: left;">
                <div class="re-card" style="border-left: 3px solid #ef4444;">
                    <div style="font-size: 1.2rem; margin-bottom: 4px;">❌</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Scattered Listings</strong>
                    <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Property photos get lost in endless WhatsApp chats and story updates after 24 hours.</p>
                </div>

                <div class="re-card" style="border-left: 3px solid #ef4444;">
                    <div style="font-size: 1.2rem; margin-bottom: 4px;">❌</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Difficult Browsing</strong>
                    <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">High-budget buyers can't easily filter by budget, location, and property type.</p>
                </div>

                <div class="re-card" style="border-left: 3px solid #ef4444;">
                    <div style="font-size: 1.2rem; margin-bottom: 4px;">❌</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">No Digital Home</strong>
                    <p style="font-size: 0.82rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Without your own website, you lose brand credibility when clients search your name on Google.</p>
                </div>
            </div>

            <div style="font-size: 1.15rem; font-weight: 700; color: var(--color-accent);">
                ✨ Give Your Properties a Professional Digital Home.
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 3. WHAT YOU GET (6 Real Estate Features + Badges) -->
    <!-- ========================================================================= -->
    <section class="re-section">
        <div class="container">
            <div style="text-align: center; max-width: 700px; margin: 0 auto 2.5rem auto;">
                <span style="font-size: 0.8rem; color: var(--color-accent); font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">COMPLETE CAPABILITY</span>
                <h2 style="font-size: clamp(1.7rem, 3.5vw, 2.3rem); margin: 0.35rem 0 0.5rem 0;">
                    Everything Your Real Estate Business Needs Online
                </h2>
            </div>

            <!-- 6 Feature Cards -->
            <div class="grid grid-3" style="gap: 1.25rem; margin-bottom: 1.75rem;">
                <div class="re-card">
                    <div style="font-size: 1.6rem; margin-bottom: 6px;">🏠</div>
                    <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 4px;">Property Listings</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Show available apartments, villas, plots, and commercial spaces with photos, pricing, and key details.</p>
                </div>

                <div class="re-card">
                    <div style="font-size: 1.6rem; margin-bottom: 6px;">📍</div>
                    <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 4px;">Location &amp; Maps</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Interactive Google Maps integration to help buyers understand nearby landmarks and connectivity.</p>
                </div>

                <div class="re-card">
                    <div style="font-size: 1.6rem; margin-bottom: 6px;">📱</div>
                    <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 4px;">WhatsApp Integration</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Pre-filled WhatsApp enquiry buttons on every property card so prospects reach out with one tap.</p>
                </div>

                <div class="re-card">
                    <div style="font-size: 1.6rem; margin-bottom: 6px;">📩</div>
                    <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 4px;">Lead Enquiry Forms</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Capture verified buyer and seller contact details directly into your database for fast followup.</p>
                </div>

                <div class="re-card">
                    <div style="font-size: 1.6rem; margin-bottom: 6px;">🖼</div>
                    <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 4px;">Property Gallery</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">High-resolution image sliders and floor-plan showcases engineered for mobile viewing.</p>
                </div>

                <div class="re-card">
                    <div style="font-size: 1.6rem; margin-bottom: 6px;">📞</div>
                    <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 4px;">Click-to-Call</h3>
                    <p style="font-size: 0.85rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Direct instant phone dialing buttons placed strategically across all listing headers.</p>
                </div>
            </div>

            <!-- Compact Benefits Bar -->
            <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-sm); padding: 12px 18px; display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; font-size: 0.85rem; color: #38bdf8; font-weight: 600;">
                <span>✓ Mobile Responsive</span> • 
                <span>✓ SEO-Ready</span> • 
                <span>✓ Fast Loading</span> • 
                <span>✓ Easy Content Management</span> • 
                <span>✓ Professional Design</span> • 
                <span>✓ Lead Generation Focused</span>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 4. PROPERTY WEBSITE VISUAL DEMONSTRATION -->
    <!-- ========================================================================= -->
    <section class="re-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container">
            <div style="text-align: center; max-width: 700px; margin: 0 auto 2.5rem auto;">
                <span style="font-size: 0.8rem; color: var(--color-accent); font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">INTERACTIVE ARCHITECTURE</span>
                <h2 style="font-size: clamp(1.7rem, 3.5vw, 2.3rem); margin: 0.35rem 0 0.5rem 0;">
                    What Your Real Estate Website Looks Like
                </h2>
                <p style="color: var(--color-text-secondary-dark); font-size: 0.95rem;">
                    Every property gets its own dedicated presentation page optimized for buyer conversions.
                </p>
            </div>

            <div class="grid grid-2" style="gap: 1.5rem; align-items: stretch;">
                
                <!-- Component 1: Property Listing View -->
                <div class="re-card" style="padding: 1.25rem;">
                    <div style="font-size: 0.75rem; color: var(--color-accent); font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">01. MULTI-LISTING CATALOG VIEW</div>
                    <div style="background: #0f172a; border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.06);">
                        <div style="display: flex; gap: 6px; margin-bottom: 10px;">
                            <span style="background: var(--color-primary); color: #fff; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px;">All Properties</span>
                            <span style="background: rgba(255,255,255,0.05); color: #94a3b8; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px;">Residential</span>
                            <span style="background: rgba(255,255,255,0.05); color: #94a3b8; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px;">Commercial</span>
                            <span style="background: rgba(255,255,255,0.05); color: #94a3b8; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px;">Plots</span>
                        </div>
                        <div class="prop-mockup-card">
                            <div class="prop-img-header" style="height: 110px; background: linear-gradient(135deg, #1e3a8a, #0369a1);">
                                <span class="prop-price-tag">$350,000</span>
                            </div>
                            <div style="padding: 10px;">
                                <strong style="font-size: 0.85rem; color: #fff; display: block;">Emerald Grand Luxury 3BHK</strong>
                                <span style="font-size: 0.75rem; color: #94a3b8;">📍 Prime Ring Road • 1,920 sq.ft</span>
                                <div style="display: flex; gap: 8px; margin-top: 8px;">
                                    <span style="background: rgba(6,182,212,0.15); color: var(--color-accent); font-size: 0.65rem; padding: 3px 8px; border-radius: 3px; font-weight: 700;">View Details</span>
                                    <span style="background: #10b981; color: #fff; font-size: 0.65rem; padding: 3px 8px; border-radius: 3px; font-weight: 700;">WhatsApp</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Component 2: Dedicated Property Detail View -->
                <div class="re-card" style="padding: 1.25rem;">
                    <div style="font-size: 0.75rem; color: var(--color-accent); font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">02. DEDICATED PROPERTY DETAIL PAGE</div>
                    <div style="background: #0f172a; border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.06);">
                        <div style="height: 90px; background: linear-gradient(135deg, #312e81, #1e3a8a); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.75rem; font-weight: 700; margin-bottom: 8px;">
                            HD Photo Gallery &amp; Virtual Tour
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; margin-bottom: 8px; text-align: center;">
                            <div style="background: rgba(255,255,255,0.03); padding: 4px; border-radius: 4px; font-size: 0.65rem; color: #cbd5e1;">🛏 3 Bedrooms</div>
                            <div style="background: rgba(255,255,255,0.03); padding: 4px; border-radius: 4px; font-size: 0.65rem; color: #cbd5e1;">🚿 3 Bathrooms</div>
                            <div style="background: rgba(255,255,255,0.03); padding: 4px; border-radius: 4px; font-size: 0.65rem; color: #cbd5e1;">🚗 2 Parking</div>
                        </div>
                        <div style="background: rgba(16,185,129,0.12); border: 1px solid #10b981; border-radius: 4px; padding: 6px 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: #fff; font-weight: 700;">Direct Agent Callback</span>
                            <span style="background: #10b981; color:#fff; font-size: 0.65rem; padding: 3px 8px; border-radius: 3px; font-weight: 700;">Enquire Now</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 5. WHY A REAL ESTATE WEBSITE? (4 Benefits) -->
    <!-- ========================================================================= -->
    <section class="re-section">
        <div class="container" style="max-width: 880px;">
            <div style="text-align: center; margin-bottom: 2.25rem;">
                <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem); margin-bottom: 0.5rem;">
                    Your Properties Deserve More Than a WhatsApp Status
                </h2>
            </div>

            <div class="grid grid-2" style="gap: 1.25rem;">
                <div class="re-card">
                    <div style="color: var(--color-accent); font-weight: 800; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 4px;">BUILD TRUST</div>
                    <p style="font-size: 0.88rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Look established and credible when high-net-worth buyers search your agency name on Google.</p>
                </div>

                <div class="re-card">
                    <div style="color: var(--color-primary); font-weight: 800; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 4px;">SHOWCASE PROPERTIES</div>
                    <p style="font-size: 0.88rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Give every property its own clean, sharable link with photos, pricing, and exact specifications.</p>
                </div>

                <div class="re-card">
                    <div style="color: #10b981; font-weight: 800; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 4px;">GENERATE ENQUIRIES</div>
                    <p style="font-size: 0.88rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Capture leads round-the-clock through automated web enquiry forms, WhatsApp, and phone calls.</p>
                </div>

                <div class="re-card">
                    <div style="color: #c084fc; font-weight: 800; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 4px;">OWN YOUR DIGITAL PRESENCE</div>
                    <p style="font-size: 0.88rem; color: var(--color-text-secondary-dark); margin: 0; line-height: 1.5;">Stop depending completely on third-party property portals where competitors advertise right next to you.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 6. PORTFOLIO / PROOF -->
    <!-- ========================================================================= -->
    <section class="re-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container">
            <div style="text-align: center; max-width: 700px; margin: 0 auto 2rem auto;">
                <span style="font-size: 0.8rem; color: var(--color-accent); font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">PROVEN ARCHITECTURES</span>
                <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem); margin-top: 0.35rem;">
                    See What a Professional Property Website Can Look Like
                </h2>
            </div>

            <div class="grid grid-3" style="gap: 1.25rem;">
                <div class="re-card" style="padding: 0; overflow: hidden;">
                    <div style="height: 140px; background: linear-gradient(135deg, #1e3a8a, #0f172a); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700; color: #fff;">
                        🏢 Commercial &amp; Retail Hub
                    </div>
                    <div style="padding: 1rem;">
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Apex Commercial Hub</strong>
                        <p style="font-size: 0.8rem; color: var(--color-text-secondary-dark); margin: 0;">Multi-unit commercial property catalog with interactive floorplans and booking enquiry.</p>
                    </div>
                </div>

                <div class="re-card" style="padding: 0; overflow: hidden;">
                    <div style="height: 140px; background: linear-gradient(135deg, #065f46, #1e293b); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700; color: #fff;">
                        🏡 Luxury Residential Portal
                    </div>
                    <div style="padding: 1rem;">
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Horizon Heights Estate</strong>
                        <p style="font-size: 0.8rem; color: var(--color-text-secondary-dark); margin: 0;">Residential property showcase featuring instant WhatsApp integration and Google Maps.</p>
                    </div>
                </div>

                <div class="re-card" style="padding: 0; overflow: hidden;">
                    <div style="height: 140px; background: linear-gradient(135deg, #581c87, #0f172a); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700; color: #fff;">
                        🏗 Developer &amp; Plots Showcase
                    </div>
                    <div style="padding: 1rem;">
                        <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Prime City Developers</strong>
                        <p style="font-size: 0.8rem; color: var(--color-text-secondary-dark); margin: 0;">Turnkey builder portfolio presenting ongoing construction phases and buyer brochures.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 7. TRUST / WHY WEBFALX (4 Compact Cards) -->
    <!-- ========================================================================= -->
    <section class="re-section">
        <div class="container">
            <div style="text-align: center; max-width: 650px; margin: 0 auto 2rem auto;">
                <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem);">
                    Why Real Estate Businesses Choose WebFalx
                </h2>
            </div>

            <div class="grid grid-4" style="gap: 1rem;">
                <div class="re-card" style="text-align: center; padding: 1.25rem;">
                    <div style="font-size: 1.5rem; margin-bottom: 4px;">⚡</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">Fast Launch</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Get your website online quickly in 7 days.</span>
                </div>
                <div class="re-card" style="text-align: center; padding: 1.25rem;">
                    <div style="font-size: 1.5rem; margin-bottom: 4px;">🎯</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">Lead-Focused</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Designed around enquiries, calls and WhatsApp.</span>
                </div>
                <div class="re-card" style="text-align: center; padding: 1.25rem;">
                    <div style="font-size: 1.5rem; margin-bottom: 4px;">🛠</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">We Handle All</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">You don't need any technical coding knowledge.</span>
                </div>
                <div class="re-card" style="text-align: center; padding: 1.25rem;">
                    <div style="font-size: 1.5rem; margin-bottom: 4px;">🛡</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block;">Post-Launch Support</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Full assistance and updates after launch.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 8. SIMPLE 3-STEP PROCESS -->
    <!-- ========================================================================= -->
    <section class="re-section" style="background: #090e1a; border-top: var(--border-glass); border-bottom: var(--border-glass);">
        <div class="container" style="max-width: 860px;">
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <h2 style="font-size: clamp(1.5rem, 3.5vw, 2rem);">
                    From Property Listings to Your Own Website in 3 Steps
                </h2>
            </div>

            <div class="grid grid-3" style="gap: 1rem;">
                <div class="re-step-box">
                    <div class="re-step-num">01</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Submit Your Details</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Tell us about your real estate business.</span>
                </div>
                <div class="re-step-box">
                    <div class="re-step-num">02</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">15-Min Strategy Call</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">We discuss your properties, branding &amp; pages.</span>
                </div>
                <div class="re-step-box">
                    <div class="re-step-num">03</div>
                    <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 4px;">Launch &amp; Start Enquiries</strong>
                    <span style="font-size: 0.8rem; color: var(--color-text-secondary-dark);">Your website goes live with enquiry &amp; WhatsApp.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 9. REAL ESTATE OFFER (VALUE STACK) -->
    <!-- ========================================================================= -->
    <section class="re-section">
        <div class="container" style="max-width: 820px;">
            <div style="background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%); border: 1px solid rgba(6,182,212,0.35); border-radius: var(--radius-lg); padding: 2.25rem; text-align: center;">
                <span style="font-size: 0.8rem; color: var(--color-accent); font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">LIMITED ONBOARDING</span>
                <h2 style="font-size: clamp(1.7rem, 3.5vw, 2.3rem); margin: 0.35rem 0 1.25rem 0;">
                    Launch Your Real Estate Website
                </h2>

                <div class="grid grid-2" style="text-align: left; gap: 8px 16px; margin-bottom: 1.75rem;">
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ Professional Real Estate Website</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ Click-to-Call Functionality</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ Property Listing Pages</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ Mobile Responsive Design</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ Dedicated Property Detail Pages</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ SEO-Ready Google Structure</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ WhatsApp Instant Chat Integration</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ Gallery &amp; Location Map Sections</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ Lead Enquiry Capture Forms</div>
                    <div style="font-size: 0.9rem; color: #cbd5e1;">✓ 30-Day Post-Launch Support</div>
                </div>

                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-sm); padding: 8px 14px; display: inline-block; font-size: 0.85rem; color: #fca5a5; font-weight: 700; margin-bottom: 1.25rem;">
                    🔥 LIMITED REAL ESTATE CAMPAIGN • Limited onboarding slots available this month.
                </div>

                <div>
                    <a href="#lead-form" class="btn-re-cta">
                        CLAIM MY REAL ESTATE WEBSITE →
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 10. LEAD FORM — MAIN CONVERSION -->
    <!-- ========================================================================= -->
    <section id="lead-form" class="re-section" style="scroll-margin-top: 60px; padding-bottom: 2rem;">
        <div class="container" style="max-width: 780px;">

            <?php if ($success_message): ?>
                <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; border-radius: var(--radius-sm); padding: 1.5rem; text-align: center; margin-bottom: 1.5rem;">
                    <h3 style="color: #10b981; font-size: 1.3rem; margin-bottom: 0.35rem;">🎉 Website Plan Request Received!</h3>
                    <p style="color: #ffffff; font-size: 0.95rem; margin: 0;"><?php echo esc($success_message); ?></p>
                </div>
                <!-- Meta Pixel Lead Event Trigger ONLY on genuine submission -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof fbq === 'function') {
                            fbq('track', 'Lead');
                            console.log('Meta Pixel: Lead event fired successfully for Real Estate offer.');
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; border-radius: var(--radius-sm); padding: 1rem; color: #fca5a5; font-size: 0.9rem; margin-bottom: 1rem;">
                    <?php echo esc($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="re-form-card">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div class="re-badge" style="margin-bottom: 0.5rem;">FREE CONSULTATION &amp; BLUEPRINT</div>
                    <h2 style="font-size: clamp(1.6rem, 4vw, 2.2rem); color: #fff; margin-bottom: 0.35rem;">
                        Get Your Real Estate Website Plan
                    </h2>
                    <p style="color: var(--color-text-secondary-dark); font-size: 0.95rem; margin: 0;">
                        Tell us about your business and we'll show you how your property website can be structured.
                    </p>
                </div>

                <form action="<?php echo BASE_URL; ?>realestateoffer#lead-form" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
                    <?php echo csrf_field(); ?>

                    <!-- Honeypot -->
                    <div style="display: none !important; visibility: hidden !important;">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Row 1: Full Name & Business Name -->
                    <div class="grid grid-2" style="gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Full Name *</label>
                            <input type="text" name="full_name" class="re-input" required placeholder="e.g. Rajesh Sharma" value="<?php echo esc_attr($_POST['full_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Business / Agency Name *</label>
                            <input type="text" name="business_name" class="re-input" required placeholder="e.g. Royal City Realty" value="<?php echo esc_attr($_POST['business_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Row 2: Phone/WhatsApp & Email -->
                    <div class="grid grid-2" style="gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Phone / WhatsApp *</label>
                            <input type="tel" name="phone" class="re-input" required placeholder="e.g. +91 98765 43210" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Email (Optional)</label>
                            <input type="email" name="email" class="re-input" placeholder="e.g. rajesh@agency.com" value="<?php echo esc_attr($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Row 3: City & Business Type -->
                    <div class="grid grid-2" style="gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">City / Operating Area *</label>
                            <input type="text" name="city" class="re-input" required placeholder="e.g. Mumbai, Indore, Delhi NCR, Dubai" value="<?php echo esc_attr($_POST['city'] ?? ''); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Business Type *</label>
                            <select name="business_type" class="re-input" style="background: #0f172a; color: #fff;">
                                <option value="Real Estate Agent" <?php echo (($_POST['business_type'] ?? '') === 'Real Estate Agent') ? 'selected' : ''; ?>>Real Estate Agent</option>
                                <option value="Property Dealer" <?php echo (($_POST['business_type'] ?? '') === 'Property Dealer') ? 'selected' : ''; ?>>Property Dealer</option>
                                <option value="Real Estate Broker" <?php echo (($_POST['business_type'] ?? '') === 'Real Estate Broker') ? 'selected' : ''; ?>>Real Estate Broker</option>
                                <option value="Builder" <?php echo (($_POST['business_type'] ?? '') === 'Builder') ? 'selected' : ''; ?>>Builder</option>
                                <option value="Developer" <?php echo (($_POST['business_type'] ?? '') === 'Developer') ? 'selected' : ''; ?>>Developer</option>
                                <option value="Property Consultant" <?php echo (($_POST['business_type'] ?? '') === 'Property Consultant') ? 'selected' : ''; ?>>Property Consultant</option>
                                <option value="Other" <?php echo (($_POST['business_type'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 4: Current Website Status & What You Need -->
                    <div class="grid grid-2" style="gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">Do You Currently Have a Website?</label>
                            <select name="current_website" class="re-input" style="background: #0f172a; color: #fff;">
                                <option value="No Website" <?php echo (($_POST['current_website'] ?? '') === 'No Website') ? 'selected' : ''; ?>>No Website</option>
                                <option value="Old / Outdated Website" <?php echo (($_POST['current_website'] ?? '') === 'Old / Outdated Website') ? 'selected' : ''; ?>>Old / Outdated Website</option>
                                <option value="Only Social Media" <?php echo (($_POST['current_website'] ?? '') === 'Only Social Media') ? 'selected' : ''; ?>>Only Social Media</option>
                                <option value="Existing Website — Need Upgrade" <?php echo (($_POST['current_website'] ?? '') === 'Existing Website — Need Upgrade') ? 'selected' : ''; ?>>Existing Website — Need Upgrade</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #f1f5f9; margin-bottom: 4px;">What Do You Need?</label>
                            <select name="service_needed" class="re-input" style="background: #0f172a; color: #fff;">
                                <option value="Property Listing Website" <?php echo (($_POST['service_needed'] ?? '') === 'Property Listing Website') ? 'selected' : ''; ?>>Property Listing Website</option>
                                <option value="New Real Estate Website" <?php echo (($_POST['service_needed'] ?? '') === 'New Real Estate Website') ? 'selected' : ''; ?>>New Real Estate Website</option>
                                <option value="Website Redesign" <?php echo (($_POST['service_needed'] ?? '') === 'Website Redesign') ? 'selected' : ''; ?>>Website Redesign</option>
                                <option value="Property Lead Generation" <?php echo (($_POST['service_needed'] ?? '') === 'Property Lead Generation') ? 'selected' : ''; ?>>Property Lead Generation</option>
                                <option value="Other" <?php echo (($_POST['service_needed'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-re-cta" style="width: 100%; padding: 1.15rem; font-size: 1.1rem; margin-top: 6px;">
                        GET MY REAL ESTATE WEBSITE PLAN →
                    </button>

                    <div style="text-align: center; font-size: 0.8rem; color: #94a3b8;">
                        🔒 Your details are kept private. No spam.
                    </div>
                </form>
            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 11. FINAL CTA -->
    <!-- ========================================================================= -->
    <section class="re-section" style="text-align: center; padding-top: 1rem; padding-bottom: 3.5rem;">
        <div class="container" style="max-width: 680px;">
            <h2 style="font-size: clamp(1.6rem, 3.5vw, 2.2rem); margin-bottom: 0.5rem;">
                Ready to Put Your Properties Online?
            </h2>
            <p style="font-size: 1rem; color: var(--color-text-secondary-dark); margin-bottom: 1.5rem;">
                Give buyers a professional place to discover your properties and contact you.
            </p>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                <a href="#lead-form" class="btn-re-cta">
                    GET MY REAL ESTATE WEBSITE →
                </a>
                <span style="font-size: 0.8rem; color: #fca5a5;">
                    🔥 Limited campaign availability
                </span>
            </div>
        </div>
    </section>

</div>

<!-- Mobile Sticky Bottom CTA Bar -->
<div class="re-mobile-sticky">
    <a href="#lead-form" class="btn-re-cta" style="width: 100%; padding: 11px; font-size: 0.95rem;">
        🔥 GET MY REAL ESTATE WEBSITE
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
