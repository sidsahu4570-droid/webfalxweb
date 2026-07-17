<?php
/**
 * WebFalx Dynamic XML Sitemap Compiler
 * Returns standard search sitemap protocols containing dynamic URLs
 */

require_once __DIR__ . '/includes/functions.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. Static Pages
$static_pages = [
    'index.php',
    'about.php',
    'services.php',
    'portfolio.php',
    'blog.php',
    'contact.php',
    'calculator.php',
    'book-appointment.php'
];

foreach ($static_pages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . BASE_URL . $page . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

if ($db !== null) {
    try {
        // 2. Dynamic Services
        $services = $db->query("SELECT slug FROM services WHERE is_active = 1")->fetchAll();
        foreach ($services as $serv) {
            echo "  <url>\n";
            echo "    <loc>" . BASE_URL . "service.php?slug=" . esc($serv['slug']) . "</loc>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }

        // 3. Dynamic Portfolios
        $portfolios = $db->query("SELECT slug FROM portfolio_projects WHERE is_active = 1")->fetchAll();
        foreach ($portfolios as $port) {
            echo "  <url>\n";
            echo "    <loc>" . BASE_URL . "project.php?slug=" . esc($port['slug']) . "</loc>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }

        // 4. Dynamic Blogs
        $blogs = $db->query("SELECT slug, created_at FROM blog_posts WHERE status = 'published'")->fetchAll();
        foreach ($blogs as $post) {
            echo "  <url>\n";
            echo "    <loc>" . BASE_URL . "blog-details.php?slug=" . esc($post['slug']) . "</loc>\n";
            echo "    <lastmod>" . date('Y-m-d', strtotime($post['created_at'])) . "</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }
    } catch (PDOException $e) {
        // Fail silently inside XML structure
    }
}

echo '</urlset>' . "\n";
