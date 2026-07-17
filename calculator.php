<?php
/**
 * WebFalx AI Smart Project Cost Calculator
 * Interactive multi-step pricing estimator driven by admin configurations.
 * Saves quotes as active leads and compiles printable HTML quotation invoices.
 */

require_once __DIR__ . '/includes/functions.php';

// Fetch admin controlled pricing rules
$shopify_base = floatval(get_setting('calc_base_shopify', '3000'));
$wordpress_base = floatval(get_setting('calc_base_wordpress', '2000'));
$php_base = floatval(get_setting('calc_base_custom_php', '4000'));
$per_page_rate = floatval(get_setting('calc_per_page_rate', '150'));
$seo_rate = floatval(get_setting('calc_seo_rate', '800'));
$hosting_rate = floatval(get_setting('calc_hosting_rate', '250'));
$maintenance_rate = floatval(get_setting('calc_maintenance_rate', '350'));

$quote_invoice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'generate_quote') {
    try {
        require_csrf_token();
        
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $company = sanitize_input($_POST['company'] ?? '');
        
        $project_type = sanitize_input($_POST['project_type'] ?? 'Shopify Store');
        $pages = intval($_POST['pages'] ?? 1);
        $seo_required = isset($_POST['seo_required']) ? 1 : 0;
        $hosting_required = isset($_POST['hosting_required']) ? 1 : 0;
        $maintenance_required = isset($_POST['maintenance_required']) ? 1 : 0;

        if (empty($name) || empty($email)) {
            throw new Exception("Please enter your name and email coordinates.");
        }

        // Calculate Cost Engine
        $base = 0;
        if ($project_type === 'Shopify Store') {
            $base = $shopify_base;
        } elseif ($project_type === 'WordPress Website') {
            $base = $wordpress_base;
        } else {
            $base = $php_base;
        }

        $pages_cost = $pages * $per_page_rate;
        $addons_cost = 0;
        $features = [];

        if ($seo_required) {
            $addons_cost += $seo_rate;
            $features[] = 'SEO Setup';
        }
        if ($hosting_required) {
            $addons_cost += $hosting_rate;
            $features[] = 'Hosting Setup';
        }
        if ($maintenance_required) {
            $addons_cost += $maintenance_rate;
            $features[] = 'Maintenance Plan';
        }

        $subtotal = $base + $pages_cost + $addons_cost;
        $tax = $subtotal * 0.08; // 8% Sales Tax
        $total = $subtotal + $tax;

        $quote_num = 'Q-' . date('Ymd') . '-' . rand(1000, 9999);

        if ($db !== null) {
            // Save as Lead in Leads table
            $lead_id = 'WF-' . date('Ymd') . '-' . rand(1000, 9999);
            $msg = "Generated cost estimation total: $" . number_format($total, 2) . " for project: " . $project_type . " with " . $pages . " pages. Addons: " . implode(', ', $features);
            
            $stmt = $db->prepare("INSERT INTO leads (lead_id, lead_type, full_name, phone, email, company_name, service_interested, message, status) VALUES (:lid, 'quote', :name, :phone, :email, :comp, :serv, :msg, 'Quotation Sent')");
            $stmt->execute([
                'lid' => $lead_id,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'comp' => $company,
                'serv' => $project_type,
                'msg' => $msg
            ]);

            // Save in Quotations table
            $stmt = $db->prepare("INSERT INTO quotations (quote_number, project_type, pages, features_json, calculated_total, status) VALUES (:num, :type, :pages, :feats, :total, 'Sent')");
            $stmt->execute([
                'num' => $quote_num,
                'type' => $project_type,
                'pages' => $pages,
                'feats' => json_encode($features),
                'total' => $total
            ]);
        }

        // Output Printable invoice dataset
        $quote_invoice = [
            'number' => $quote_num,
            'client_name' => $name,
            'client_email' => $email,
            'client_company' => $company,
            'project_type' => $project_type,
            'pages' => $pages,
            'base' => $base,
            'pages_cost' => $pages_cost,
            'seo' => $seo_required ? $seo_rate : 0,
            'hosting' => $hosting_required ? $hosting_rate : 0,
            'maintenance' => $maintenance_required ? $maintenance_rate : 0,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total
        ];

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$page_seo = [
    'title' => 'AI Smart Quotation Calculator | WebFalx',
    'description' => 'Calculate project estimates instantly using our database-driven quotation engine.'
];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Cost Calculator banner -->
<section class="section calc-hero" style="padding: var(--spacing-lg) 0 var(--spacing-xs) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal" style="text-align: center;">
        <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">SMART AUTOMATION</span>
        <h1 style="color: #ffffff; font-size: 2.5rem; margin-top: 0.5rem;">Interactive Cost Calculator</h1>
        <p style="max-width: 600px; margin: 10px auto 0 auto;">Select project specifications to compile dynamic estimates for development and branding.</p>
    </div>
</section>

<section class="section calc-body" style="padding-top: var(--spacing-xs);">
    <div class="container">
        
        <?php if ($quote_invoice): ?>
            <!-- Render Printable Quotation PDF Structure Ready -->
            <div class="glass-card" id="printable-quote-invoice" style="max-width: 700px; margin: 0 auto; padding: 2rem; border-color: var(--color-accent);">
                <div style="display: flex; justify-content: space-between; align-items: start; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 12px; margin-bottom: 20px;">
                    <div>
                        <h3 class="gradient-text" style="font-size: 1.5rem; margin: 0;"><?php echo esc(APP_NAME); ?>.</h3>
                        <small style="color: var(--color-text-muted-dark);">Pasadena, CA, USA &bull; 6266273414</small>
                    </div>
                    <div style="text-align: right;">
                        <h4 style="color: #fff; margin: 0;">ESTIMATE</h4>
                        <span style="font-size: 0.85rem; color: var(--color-text-secondary-dark);"><?php echo $quote_invoice['number']; ?></span>
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 15px; margin-bottom: 20px; font-size: 0.88rem; color: var(--color-text-secondary-dark);">
                    <div>
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted-dark);">ESTIMATE FOR:</span>
                        <strong style="color: #fff;"><?php echo esc($quote_invoice['client_name']); ?></strong><br>
                        <?php echo esc($quote_invoice['client_company']); ?><br>
                        <?php echo esc($quote_invoice['client_email']); ?>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted-dark);">DATE ISSUED:</span>
                        <span style="color: #fff;"><?php echo date('F d, Y'); ?></span>
                    </div>
                </div>

                <!-- Price line items -->
                <table class="admin-table" style="margin-bottom: 20px; font-size: 0.88rem;">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php echo esc($quote_invoice['project_type']); ?> Base Cost</strong></td>
                            <td>$<?php echo number_format($quote_invoice['base'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Content Pages Multiplier (<?php echo $quote_invoice['pages']; ?> Pages x $<?php echo $per_page_rate; ?>)</td>
                            <td>$<?php echo number_format($quote_invoice['pages_cost'], 2); ?></td>
                        </tr>
                        <?php if ($quote_invoice['seo'] > 0): ?>
                            <tr>
                                <td>Technical SEO Schema Setups Addon</td>
                                <td>$<?php echo number_format($quote_invoice['seo'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($quote_invoice['hosting'] > 0): ?>
                            <tr>
                                <td>Optimized Cloud Server Hosting Setup</td>
                                <td>$<?php echo number_format($quote_invoice['hosting'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($quote_invoice['maintenance'] > 0): ?>
                            <tr>
                                <td>Monthly Maintenance & Code Updates Support</td>
                                <td>$<?php echo number_format($quote_invoice['maintenance'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div style="text-align: right; font-size: 0.88rem; color: var(--color-text-secondary-dark); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px;">
                    <div>Subtotal: $<?php echo number_format($quote_invoice['subtotal'], 2); ?></div>
                    <div>Est. Tax (8%): $<?php echo number_format($quote_invoice['tax'], 2); ?></div>
                    <div style="font-size: 1.25rem; color: #fff; font-weight: 700; margin-top: 4px;">Total Cost: $<?php echo number_format($quote_invoice['total'], 2); ?></div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button onclick="window.print()" class="btn btn-primary" style="flex: 1;">Print / Download PDF</button>
                    <a href="calculator.php" class="btn btn-secondary" style="flex: 1; text-align: center; line-height: 2;">Reset Calculator</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Cost Estimation multi-step form -->
            <div class="glass-card" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
                <form action="" method="POST" id="calculator-step-form" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">
                    <input type="hidden" name="action_type" value="generate_quote">

                    <!-- Step 1: Project details -->
                    <div>
                        <h4 style="color: var(--color-accent); margin-bottom: 8px;">1. Project Type & Scale</h4>
                        
                        <div class="form-group">
                            <label for="calc_type">Core Project Technology</label>
                            <select name="project_type" id="calc_type" class="form-control" style="background: var(--color-bg-dark); cursor: pointer;" onchange="calculateRealtimeCost()">
                                <option value="Shopify Store">Shopify Store Base ($<?php echo $shopify_base; ?>)</option>
                                <option value="WordPress Website">WordPress Website Base ($<?php echo $wordpress_base; ?>)</option>
                                <option value="Custom PHP Website">Custom PHP Portal Base ($<?php echo $php_base; ?>)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="calc_pages">Number of Content Pages ($<?php echo $per_page_rate; ?> / Page)</label>
                            <input type="number" name="pages" id="calc_pages" class="form-control" value="5" min="1" oninput="calculateRealtimeCost()">
                        </div>
                    </div>

                    <!-- Step 2: Addons -->
                    <div>
                        <h4 style="color: var(--color-accent); margin-bottom: 8px;">2. Add-on Integrations</h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.88rem;">
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-transform: none;">
                                <input type="checkbox" name="seo_required" id="calc_seo" value="1" onchange="calculateRealtimeCost()" style="width: 16px; height: 16px;">
                                Technical SEO Optimization Setup (+$<?php echo $seo_rate; ?>)
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-transform: none;">
                                <input type="checkbox" name="hosting_required" id="calc_host" value="1" onchange="calculateRealtimeCost()" style="width: 16px; height: 16px;">
                                Optimized Cloud Hosting Server Setup (+$<?php echo $hosting_rate; ?>)
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-transform: none;">
                                <input type="checkbox" name="maintenance_required" id="calc_maint" value="1" onchange="calculateRealtimeCost()" style="width: 16px; height: 16px;">
                                Monthly Code Maintenance Support (+$<?php echo $maintenance_rate; ?>)
                            </label>
                        </div>
                    </div>

                    <!-- Step 3: Contact Info -->
                    <div>
                        <h4 style="color: var(--color-accent); margin-bottom: 8px;">3. Contact Coordinates</h4>
                        <div class="grid grid-2" style="gap: 8px;">
                            <div class="form-group">
                                <label for="c_name">Full Name *</label>
                                <input type="text" name="name" id="c_name" class="form-control" required placeholder="John Doe" style="padding: 6px 12px; min-height: auto; font-size: 0.85rem;">
                            </div>
                            <div class="form-group">
                                <label for="c_email">Email address *</label>
                                <input type="email" name="email" id="c_email" class="form-control" required placeholder="john@company.com" style="padding: 6px 12px; min-height: auto; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="grid grid-2" style="gap: 8px;">
                            <div class="form-group">
                                <label for="c_phone">Phone Number</label>
                                <input type="text" name="phone" id="c_phone" class="form-control" placeholder="6265551212" style="padding: 6px 12px; min-height: auto; font-size: 0.85rem;">
                            </div>
                            <div class="form-group">
                                <label for="c_comp">Company</label>
                                <input type="text" name="company" id="c_comp" class="form-control" placeholder="Acme Logistics" style="padding: 6px 12px; min-height: auto; font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Realtime counter -->
                    <div style="background: rgba(255,255,255,0.02); border: var(--border-glass); padding: 12px; border-radius: var(--radius-sm); text-align: center; margin-top: 10px;">
                        <span style="font-size: 0.85rem; color: var(--color-text-secondary-dark);">Estimated Cost Value:</span>
                        <div style="font-size: 1.85rem; font-weight: 800; color: var(--color-accent);" id="realtime-estimate-price">$3,750.00</div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Submit Estimate & Get Quotation</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- Cost Calculator Realtime JS Calculator -->
<script>
function calculateRealtimeCost() {
    const shopify_base = <?php echo $shopify_base; ?>;
    const wordpress_base = <?php echo $wordpress_base; ?>;
    const php_base = <?php echo $php_base; ?>;
    const per_page_rate = <?php echo $per_page_rate; ?>;
    const seo_rate = <?php echo $seo_rate; ?>;
    const hosting_rate = <?php echo $hosting_rate; ?>;
    const maintenance_rate = <?php echo $maintenance_rate; ?>;

    const calcType = document.getElementById('calc_type');
    const calcPages = document.getElementById('calc_pages');
    const calcSeo = document.getElementById('calc_seo');
    const calcHost = document.getElementById('calc_host');
    const calcMaint = document.getElementById('calc_maint');
    const displayPrice = document.getElementById('realtime-estimate-price');

    if (!calcType || !calcPages || !displayPrice) return;

    let base = 0;
    if (calcType.value === 'Shopify Store') {
        base = shopify_base;
    } else if (calcType.value === 'WordPress Website') {
        base = wordpress_base;
    } else {
        base = php_base;
    }

    const pages = parseInt(calcPages.value) || 0;
    const pagesCost = pages * per_page_rate;

    let addonsCost = 0;
    if (calcSeo && calcSeo.checked) addonsCost += seo_rate;
    if (calcHost && calcHost.checked) addonsCost += hosting_rate;
    if (calcMaint && calcMaint.checked) addonsCost += maintenance_rate;

    const subtotal = base + pagesCost + addonsCost;
    const tax = subtotal * 0.08;
    const total = subtotal + tax;

    displayPrice.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Initial trigger
document.addEventListener('DOMContentLoaded', calculateRealtimeCost);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
