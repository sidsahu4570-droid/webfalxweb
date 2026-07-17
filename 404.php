<?php
/**
 * WebFalx 404 - Not Found Error Page
 */

http_response_code(404);

$page_seo = [
    'title' => 'Page Not Found | WebFalx',
    'description' => 'The page you are looking for does not exist.'
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="section error-page" style="padding: 100px 0; text-align: center; min-height: 70vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal">
        <div class="glass-card" style="max-width: 500px; margin: 0 auto; padding: 3rem; border-color: rgba(239, 68, 68, 0.2);">
            <div style="font-size: 5rem; font-weight: 900; line-height: 1; color: var(--color-accent); margin-bottom: 10px;">404</div>
            <h2 style="color: #fff; margin-bottom: var(--spacing-xs);">Destination Lost</h2>
            <p style="color: var(--color-text-secondary-dark); margin-bottom: 25px;">The route you requested could not be resolved. It may have been relocated or deleted.</p>
            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary" style="display: inline-block;">Return to Homepage</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
