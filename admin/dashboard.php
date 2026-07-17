<?php
/**
 * WebFalx Admin Console Overview Dashboard
 * Dynamic database counts widgets, notifications lists, and premium inline SVG analytics charts
 */

$page_seo = [
    'title' => 'Console Overview | WebFalx Admin'
];

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$leads_count = 0;
$services_count = 0;
$projects_count = 0;
$blogs_count = 0;
$testimonials_count = 0;
$subscribers_count = 0;
$team_count = 0;
$cats_count = 0;
$inq_count = 0;
$visitors_count = get_setting('stat_sim_visitors', '3840');

// Dynamic recent logs
$recent_leads = [];
$recent_logs = [];

if ($db !== null) {
    try {
        $leads_count = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
        $services_count = $db->query("SELECT COUNT(*) FROM services")->fetchColumn();
        $projects_count = $db->query("SELECT COUNT(*) FROM portfolio_projects")->fetchColumn();
        $blogs_count = $db->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
        $testimonials_count = $db->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
        $subscribers_count = $db->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
        $team_count = $db->query("SELECT COUNT(*) FROM team_members")->fetchColumn();
        $cats_count = $db->query("SELECT COUNT(*) FROM service_categories")->fetchColumn();
        $inq_count = $db->query("SELECT COUNT(*) FROM leads WHERE lead_type = 'inquiry'")->fetchColumn();

        // Recent items
        $recent_leads = $db->query("SELECT * FROM leads ORDER BY id DESC LIMIT 5")->fetchAll();
        $recent_logs = $db->query("SELECT l.*, a.username FROM activity_logs l LEFT JOIN admins a ON l.user_id = a.id ORDER BY l.id DESC LIMIT 5")->fetchAll();
    } catch (PDOException $ex) {
        error_log("Dashboard query fails: " . $ex->getMessage());
    }
}

require_once __DIR__ . '/admin_header.php';
?>

<h3 style="margin-bottom: var(--spacing-sm);">SaaS Overview Console</h3>
<p style="margin-bottom: var(--spacing-md); color: var(--color-text-secondary-dark);">Real-time tracking of leads generation channels, technical publication traffic, and system operations audit logs.</p>

<!-- 1. Stats Counter Widgets Grid -->
<div class="grid grid-4" style="margin-bottom: var(--spacing-md); gap: 12px;">
    <div class="glass-card" style="padding: 1rem; text-align: center;">
        <span style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase; letter-spacing: 0.5px;">Leads Total</span>
        <div style="font-size: 2rem; font-weight: 800; color: #ffffff;"><?php echo $leads_count; ?></div>
    </div>
    <div class="glass-card" style="padding: 1rem; text-align: center;">
        <span style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase; letter-spacing: 0.5px;">Subscribers</span>
        <div style="font-size: 2rem; font-weight: 800; color: var(--color-accent);"><?php echo $subscribers_count; ?></div>
    </div>
    <div class="glass-card" style="padding: 1rem; text-align: center;">
        <span style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase; letter-spacing: 0.5px;">Publications</span>
        <div style="font-size: 2rem; font-weight: 800; color: var(--color-secondary);"><?php echo $blogs_count; ?></div>
    </div>
    <div class="glass-card" style="padding: 1rem; text-align: center;">
        <span style="font-size: 0.72rem; color: var(--color-text-muted-dark); text-transform: uppercase; letter-spacing: 0.5px;">Traffic Visitors</span>
        <div style="font-size: 2rem; font-weight: 800; color: #ffffff;"><?php echo $visitors_count; ?></div>
    </div>
</div>

<!-- 2. Responsive SVG Charts Grid (Prompt 8 Dashboard Charts) -->
<div class="grid grid-2" style="margin-bottom: var(--spacing-md); gap: var(--spacing-sm);">
    <!-- Chart 1: Leads Generated (Line Chart mockup using pure SVG) -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: #ffffff; margin-bottom: 12px; font-size: 1.1rem;">Leads Acquisition Trend (Last 7 Days)</h4>
        
        <svg viewBox="0 0 500 200" style="width: 100%; height: auto; display: block; overflow: visible;">
            <!-- Grid Lines -->
            <line x1="40" y1="20" x2="480" y2="20" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
            <line x1="40" y1="70" x2="480" y2="70" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
            <line x1="40" y1="120" x2="480" y2="120" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
            <line x1="40" y1="170" x2="480" y2="170" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
            
            <!-- Axis text labels -->
            <text x="35" y="174" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="end">0</text>
            <text x="35" y="124" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="end">50</text>
            <text x="35" y="74" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="end">100</text>
            <text x="35" y="24" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="end">150</text>
            
            <!-- Trend Line Curve -->
            <path d="M 50 160 Q 120 100 190 120 T 330 60 T 470 30" fill="none" stroke="url(#leadsGrad)" stroke-width="3" stroke-linecap="round"/>
            
            <!-- Point highlights -->
            <circle cx="470" cy="30" r="5" fill="var(--color-accent)"/>
            
            <!-- Gradient definition -->
            <defs>
                <linearGradient id="leadsGrad" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="var(--color-secondary)" />
                    <stop offset="100%" stop-color="var(--color-accent)" />
                </linearGradient>
            </defs>

            <!-- Bottom week markers -->
            <text x="50" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Mon</text>
            <text x="120" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Tue</text>
            <text x="190" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Wed</text>
            <text x="260" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Thu</text>
            <text x="330" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Fri</text>
            <text x="400" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Sat</text>
            <text x="470" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Sun</text>
        </svg>
    </div>

    <!-- Chart 2: Popular Services (Bar Chart mockup using pure SVG) -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: #ffffff; margin-bottom: 12px; font-size: 1.1rem;">Popular Service Channels</h4>
        
        <svg viewBox="0 0 500 200" style="width: 100%; height: auto; display: block; overflow: visible;">
            <!-- Grid Lines -->
            <line x1="40" y1="170" x2="480" y2="170" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
            
            <!-- Bars -->
            <!-- Bar 1: Shopify (height 120px) -->
            <rect x="70" y="50" width="40" height="120" rx="3" fill="var(--color-primary)" opacity="0.8"/>
            <text x="90" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">Shopify</text>
            
            <!-- Bar 2: WordPress (height 90px) -->
            <rect x="170" y="80" width="40" height="90" rx="3" fill="var(--color-secondary)" opacity="0.8"/>
            <text x="190" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">WordPress</text>

            <!-- Bar 3: SEO Growth (height 140px) -->
            <rect x="270" y="30" width="40" height="140" rx="3" fill="var(--color-accent)" opacity="0.8"/>
            <text x="290" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">SEO</text>

            <!-- Bar 4: Custom Apps (height 100px) -->
            <rect x="370" y="70" width="40" height="100" rx="3" fill="#10b981" opacity="0.8"/>
            <text x="390" y="190" fill="rgba(255,255,255,0.3)" font-size="9" text-anchor="middle">CRM</text>
        </svg>
    </div>
</div>

<!-- 3. Logs & Recent Activities grid -->
<div class="grid grid-2" style="gap: var(--spacing-sm); align-items: start;">
    <!-- Recent incoming leads list -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: var(--spacing-xs);">Recent Pipeline Leads</h4>
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 0.88rem;">
            <?php foreach ($recent_leads as $lead): ?>
                <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                    <div>
                        <strong style="color: #ffffff;"><?php echo esc($lead['full_name']); ?></strong> &bull; 
                        <span style="color: var(--color-text-muted-dark); font-size: 0.72rem;"><?php echo esc($lead['lead_id']); ?></span>
                        <div style="font-size: 0.75rem; color: var(--color-text-secondary-dark);"><?php echo esc($lead['service_interested']); ?></div>
                    </div>
                    <span class="status-badge <?php echo 'status-' . strtolower(str_replace(' ', '', $lead['status'])); ?>" style="font-size: 0.65rem; height: fit-content; align-self: center;">
                        <?php echo esc($lead['status']); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="manage-leads.php" class="btn btn-secondary" style="font-size: 0.75rem; min-height: auto; padding: 4px 10px; margin-top: 10px; width: 100%; text-align: center;">Open Leads Pipeline</a>
    </div>

    <!-- Security Action activity logs -->
    <div class="glass-card" style="padding: 1.25rem;">
        <h4 style="color: var(--color-accent); margin-bottom: var(--spacing-xs);">Security Activity Log</h4>
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 0.88rem;">
            <?php foreach ($recent_logs as $log): ?>
                <li style="border-bottom: 1px solid rgba(255,255,255,0.02); padding-bottom: 4px;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong style="color: #ffffff;"><?php echo esc($log['action']); ?></strong>
                        <span style="font-size: 0.72rem; color: var(--color-text-muted-dark);"><?php echo date('H:i M d', strtotime($log['created_at'])); ?></span>
                    </div>
                    <p style="font-size: 0.75rem; color: var(--color-text-secondary-dark);"><?php echo esc($log['details']); ?> (IP: <?php echo esc($log['ip_address']); ?>)</p>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="activity-logs.php" class="btn btn-secondary" style="font-size: 0.75rem; min-height: auto; padding: 4px 10px; margin-top: 10px; width: 100%; text-align: center;">Open Full Audit Logs</a>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
