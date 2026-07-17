<?php
/**
 * WebFalx Header Component
 * Global document wrapper with dynamic SEO configurations, loading overlays, and navigation
 */

require_once __DIR__ . '/functions.php';

// Generate SEO data using whatever override the parent page sets
$seo = get_seo_data($page_seo ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- Core SEO Meta Tags -->
    <title><?php echo esc($seo['title']); ?></title>
    <meta name="description" content="<?php echo esc($seo['description']); ?>">
    <?php if (!empty($seo['keywords'])): ?>
    <meta name="keywords" content="<?php echo esc($seo['keywords']); ?>">
    <?php endif; ?>
    
    <!-- Canonical Reference -->
    <link rel="canonical" href="<?php echo esc_attr($seo['canonical']); ?>">
    
    <!-- Open Graph (Facebook / LinkedIn) Meta tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo esc($seo['title']); ?>">
    <meta property="og:description" content="<?php echo esc($seo['description']); ?>">
    <meta property="og:image" content="<?php echo esc_attr($seo['og_image']); ?>">
    <meta property="og:url" content="<?php echo esc_attr($seo['canonical']); ?>">
    <meta property="og:site_name" content="<?php echo esc(APP_NAME); ?>">
    
    <!-- Twitter Card Meta tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc($seo['title']); ?>">
    <meta name="twitter:description" content="<?php echo esc($seo['description']); ?>">
    <meta name="twitter:image" content="<?php echo esc_attr($seo['og_image']); ?>">
    
    <!-- Fonts - Luxury Typography Integration -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/animations.css">
    
    <!-- Dynamic Theme Styling variables injection (Prompt 8) -->
    <style>
        :root {
            --color-primary: <?php echo esc(get_setting('theme_color_primary', '#2563eb')); ?>;
            --color-secondary: <?php echo esc(get_setting('theme_color_secondary', '#7c3aed')); ?>;
            --color-accent: <?php echo esc(get_setting('theme_color_accent', '#06b6d4')); ?>;
            --color-bg-dark: <?php echo esc(get_setting('theme_color_bg_dark', '#0f172a')); ?>;
            --font-body: <?php echo esc(get_setting('theme_font_family', 'Inter, sans-serif')); ?>;
            --radius-md: <?php echo esc(get_setting('theme_border_radius', '0.5rem')); ?>;
            --transition-medium: <?php echo esc(get_setting('theme_animation_speed', '0.3s')); ?>;
        }
    </style>
    
    <!-- Schema.org Structured Data Hook (JSON-LD) -->
    <?php
    $schemaJson = get_setting('schema_organization_json');
    if (!empty($schemaJson)):
    ?>
    <script type="application/ld+json">
        <?php echo $schemaJson; // Output raw schema json since it is controlled database admin content ?>
    </script>
    <?php endif; ?>
</head>
<body>

    <!-- 1. Custom Interactive Cursors -->
    <div class="custom-cursor-dot"></div>
    <div class="custom-cursor-outline"></div>

    <!-- 2. Premium Page Loader -->
    <div class="page-loader">
        <div class="loader-inner">
            <div class="loader-logo gradient-text"><?php echo esc(APP_NAME); ?></div>
            <div class="loader-bar">
                <div class="loader-progress"></div>
            </div>
        </div>
    </div>

    <!-- 3. Ambient Background Gradient Blobs Wrapper to prevent horizontal overflow -->
    <div class="gradient-blobs-wrapper" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="gradient-blob blob-primary"></div>
        <div class="gradient-blob blob-secondary"></div>
        <div class="gradient-blob blob-accent"></div>
    </div>

    <!-- 4. Global Navigation Header -->
    <?php require_once __DIR__ . '/navbar.php'; ?>
    
    <!-- Page Body Wrapper (closed in footer) -->
    <main class="page-content" style="padding-top: 80px; min-height: calc(100vh - 80px);">
