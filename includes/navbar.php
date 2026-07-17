<?php
/**
 * WebFalx Navigation Bar Component
 * Responsive Header navigation with session awareness and dynamic database navigation links
 */

require_once __DIR__ . '/functions.php';

// Fetch dynamic menu links
$nav_items = [];
if ($db !== null) {
    try {
        $nav_items = $db->query("SELECT * FROM menus WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
    } catch (PDOException $ex) {
        error_log("Failed to fetch menu list: " . $ex->getMessage());
    }
}
?>
<header class="navbar-header">
    <div class="container navbar-container">
        <!-- Logo -->
        <a href="<?php echo BASE_URL; ?>" class="logo-link">
            <span class="gradient-text"><?php echo esc(APP_NAME); ?></span>
            <span class="logo-dot">.</span>
        </a>
        
        <!-- Navigation Menu -->
        <nav class="primary-navigation" aria-label="Primary navigation">
            <ul id="primary-menu" class="nav-menu">
                <?php if (empty($nav_items)): ?>
                    <li><a href="<?php echo BASE_URL; ?>index.php" class="nav-link">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>services.php" class="nav-link">Services</a></li>
                    <li><a href="<?php echo BASE_URL; ?>portfolio.php" class="nav-link">Portfolio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>about.php" class="nav-link">About</a></li>
                    <li><a href="<?php echo BASE_URL; ?>blog.php" class="nav-link">Blog</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php" class="nav-link">Contact</a></li>
                <?php else: ?>
                    <?php foreach ($nav_items as $nav): ?>
                        <li><a href="<?php echo BASE_URL . esc($nav['url']); ?>" class="nav-link"><?php echo esc($nav['title']); ?></a></li>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (is_admin_logged_in()): ?>
                    <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link" style="color: var(--color-accent);">Admin Panel</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- CTA Call Button -->
        <div class="nav-actions" style="display: flex; align-items: center; gap: 0.5rem;">
            <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" class="btn btn-secondary" style="padding: 0.5rem 1.2rem; font-size: 0.8rem; border-radius: var(--radius-full);">
                Call
            </a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-primary" style="padding: 0.5rem 1.2rem; font-size: 0.8rem; border-radius: var(--radius-full);">
                Get Quote
            </a>
            
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="primary-menu" style="margin-left: 0.5rem;">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>
