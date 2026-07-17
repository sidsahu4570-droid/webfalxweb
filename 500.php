<?php
/**
 * WebFalx 500 - Internal Server Error Page
 */

http_response_code(500);

$page_seo = [
    'title' => 'Server Error | WebFalx',
    'description' => 'A technical malfunction occurred on our servers.'
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="section error-page" style="padding: 100px 0; text-align: center; min-height: 70vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal">
        <div class="glass-card" style="max-width: 500px; margin: 0 auto; padding: 3rem; border-color: rgba(239, 68, 68, 0.2);">
            <div style="font-size: 5rem; font-weight: 900; line-height: 1; color: #ef4444; margin-bottom: 10px;">500</div>
            <h2 style="color: #fff; margin-bottom: var(--spacing-xs);">System Malfunction</h2>
            <p style="color: var(--color-text-secondary-dark); margin-bottom: 25px;">Our servers encountered an unexpected failure processing this route. The engineering team has been notified.</p>
            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary" style="display: inline-block;">Return to Homepage</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
