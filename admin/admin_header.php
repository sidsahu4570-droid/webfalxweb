<?php
/**
 * WebFalx Admin Layout Header
 * Shared authentication enforcement, stylesheet linkage, and dashboard layouts
 */

require_once __DIR__ . '/../includes/functions.php';

// Secure access checks
require_admin();

// Dynamic SEO titles for admin sections
$admin_title = $page_seo['title'] ?? 'WebFalx Administration Console';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($admin_title); ?></title>
    
    <!-- Load design system styles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/animations.css">
    
    <style>
        .admin-dashboard-container {
            width: 100%;
            max-width: 100% !important;
            margin: 0;
            padding: 0 var(--spacing-md) var(--spacing-lg) var(--spacing-md);
        }
        
        .admin-sidebar-toggle-btn {
            display: none;
        }

        .admin-layout-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: var(--spacing-md);
            align-items: start;
        }
        
        .admin-sidebar {
            position: sticky;
            top: 90px;
            height: calc(100vh - 110px);
            overflow-y: auto;
            z-index: 90;
        }

        /* Custom scrollbar for sidebar menu */
        .admin-sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .admin-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .admin-sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .admin-sidebar-menu a {
            display: block;
            padding: 0.7rem 1rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        .admin-sidebar-menu a:hover,
        .admin-sidebar-menu a.active {
            background: var(--gradient-hero);
            color: #ffffff;
            border-color: transparent;
            transform: translateX(4px);
        }
        .admin-content-box {
            padding: var(--spacing-md);
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-top: 1.5rem;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .admin-table th, .admin-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }
        .admin-table th {
            font-family: var(--font-heading);
            font-weight: 600;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.02);
        }
        .admin-table tr:hover {
            background: rgba(255, 255, 255, 0.01);
        }
        .action-link {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-right: 0.75rem;
        }
        .action-edit { color: var(--color-accent); }
        .action-delete { color: var(--color-danger); }
        
        .admin-sidebar-overlay {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 98;
            backdrop-filter: blur(3px);
        }
        
        @media (max-width: 1024px) {
            .admin-layout-grid {
                grid-template-columns: 1fr;
            }
            .admin-sidebar-toggle-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid rgba(255, 255, 255, 0.08);
                color: #ffffff;
                padding: 0.45rem 0.9rem;
                font-size: 0.8rem;
                font-weight: 600;
                border-radius: var(--radius-sm);
                cursor: pointer;
                transition: all var(--transition-fast);
                outline: none;
            }
            .admin-sidebar-toggle-btn:hover {
                background: rgba(255, 255, 255, 0.08);
            }
            .admin-sidebar {
                position: fixed;
                top: 80px;
                left: -300px;
                bottom: 0;
                width: 280px;
                height: calc(100vh - 80px);
                z-index: 999;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                background: var(--color-bg-dark);
                border-right: 1px solid rgba(255, 255, 255, 0.05);
            }
            .admin-sidebar.active {
                left: 0;
            }
            .admin-sidebar-overlay.active {
                display: block;
            }
            .admin-sidebar .glass-card {
                height: 100%;
                border: none;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }
        }
    </style>
</head>
<body style="cursor: default;">

    <!-- Simplified Header for Admin Area -->
    <header class="navbar-header scrolled">
        <div class="container navbar-container">
            <div style="display: flex; align-items: center;">
                <button class="admin-sidebar-toggle-btn" id="adminSidebarToggle" type="button" aria-label="Toggle Sidebar" style="margin-right: 12px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block;"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <a href="<?php echo BASE_URL; ?>" class="logo-link">
                    <span class="gradient-text"><?php echo esc(APP_NAME); ?> Admin</span>
                    <span class="logo-dot">.</span>
                </a>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>" target="_blank" class="btn btn-secondary" style="padding: 0.4rem 1rem; font-size: 0.8rem;">View Website</a>
                <a href="<?php echo BASE_URL; ?>admin/logout.php" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem; background: var(--color-danger); box-shadow: none; margin-left: 0.5rem;">Sign Out</a>
            </div>
        </div>
    </header>

    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggleBtn = document.getElementById('adminSidebarToggle');
            var sidebar = document.querySelector('.admin-sidebar');
            var overlay = document.getElementById('adminSidebarOverlay');

            if (toggleBtn && sidebar && overlay) {
                function toggleSidebar() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                }
                
                toggleBtn.addEventListener('click', toggleSidebar);
                overlay.addEventListener('click', toggleSidebar);
                
                var links = sidebar.querySelectorAll('a');
                links.forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 1024) {
                            toggleSidebar();
                        }
                    });
                });
            }
        });
    </script>

    <main class="page-content" style="padding-top: 80px; min-height: calc(100vh - 80px);">
        <div class="container admin-dashboard-container">
            <div class="admin-layout-grid">
                
                <!-- Administration Sidebar Navigation -->
                <aside class="admin-sidebar reveal">
                    <div class="glass-card">
                        <ul class="admin-sidebar-menu">
                            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Console Overview</a></li>
                            <li><a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">Website Settings</a></li>
                            <li><a href="manage-media.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-media.php' ? 'active' : ''; ?>">Media Manager</a></li>
                            <li><a href="menu-manager.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'menu-manager.php' ? 'active' : ''; ?>">Menu Manager</a></li>
                            <li><a href="seo-manager.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'seo-manager.php' ? 'active' : ''; ?>">SEO Manager</a></li>
                            <li><a href="theme-settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'theme-settings.php' ? 'active' : ''; ?>">Theme Settings</a></li>
                            <li><a href="section-manager.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'section-manager.php' ? 'active' : ''; ?>">Layout Sections</a></li>
                            <li><a href="hero-settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'hero-settings.php' ? 'active' : ''; ?>">Hero & Stats</a></li>
                            <li><a href="manage-logos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-logos.php' ? 'active' : ''; ?>">Client Logos</a></li>
                            <li><a href="manage-about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-about.php' ? 'active' : ''; ?>">About Us CRUD</a></li>
                            <li><a href="manage-categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-categories.php' ? 'active' : ''; ?>">Service Categories</a></li>
                            <li><a href="manage-services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-services.php' ? 'active' : ''; ?>">Services List</a></li>
                            <li><a href="manage-process.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-process.php' ? 'active' : ''; ?>">Process Workflow</a></li>
                            <li><a href="manage-portfolio-categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-portfolio-categories.php' ? 'active' : ''; ?>">Portfolio Categories</a></li>
                            <li><a href="manage-portfolio.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-portfolio.php' ? 'active' : ''; ?>">Portfolio CRUD</a></li>
                            <li><a href="manage-testimonials.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-testimonials.php' ? 'active' : ''; ?>">Testimonials</a></li>
                            <li><a href="manage-faqs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-faqs.php' ? 'active' : ''; ?>">FAQs Accordion</a></li>
                            <li><a href="manage-blogs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-blogs.php' ? 'active' : ''; ?>">Blogs CMS</a></li>
                            <li><a href="manage-leads.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-leads.php' ? 'active' : ''; ?>">Leads Pipeline</a></li>
                            <li><a href="manage-clients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-clients.php' ? 'active' : ''; ?>">Client Portal Admin</a></li>
                            <li><a href="manage-automation.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-automation.php' ? 'active' : ''; ?>">Business Automation</a></li>
                            <li><a href="performance-settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'performance-settings.php' ? 'active' : ''; ?>">System Health & Perf</a></li>
                            <li><a href="manage-contact-settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-contact-settings.php' ? 'active' : ''; ?>">Contact & SMTP</a></li>
                            <li><a href="manage-users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>">User Accounts</a></li>
                            <li><a href="backup-manager.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'backup-manager.php' ? 'active' : ''; ?>">Backup Manager</a></li>
                            <li><a href="activity-logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'activity-logs.php' ? 'active' : ''; ?>">Activity Logs</a></li>
                            <li><a href="change-password.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'change-password.php' ? 'active' : ''; ?>">Update Password</a></li>
                        </ul>
                    </div>
                </aside>
                
                <!-- Right Side Admin workspace -->
                <section class="admin-main reveal">
                    <div class="glass-card admin-content-box">
                        <?php display_flash_messages(); ?>
