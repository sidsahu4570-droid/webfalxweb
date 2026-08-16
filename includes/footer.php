<?php
/**
 * WebFalx Footer Component
 * Page wrap closures, dynamic links, CDN asset loading, and scripts integration
 */

require_once __DIR__ . '/functions.php';
?>
    </main> <!-- Close page-content from header -->

    <!-- Global Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Profile -->
                <div class="footer-brand">
                    <a href="<?php echo BASE_URL; ?>" class="logo-link" style="margin-bottom: var(--spacing-sm);">
                        <span class="gradient-text"><?php echo esc(APP_NAME); ?></span>
                        <span class="logo-dot">.</span>
                    </a>
                    <p>We craft premium high-conversion digital experiences using cutting-edge engineering and data-driven marketing psychology.</p>
                    <div class="footer-socials">
                        <a href="<?php echo esc_attr(get_setting('social_linkedin', '#')); ?>" target="_blank" rel="noopener" class="social-icon-btn" aria-label="LinkedIn">
                            <!-- LinkedIn Clean Icon SVG -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                        </a>
                        <a href="<?php echo esc_attr(get_setting('social_twitter', '#')); ?>" target="_blank" rel="noopener" class="social-icon-btn" aria-label="Twitter">
                            <!-- Twitter/X Clean Icon SVG -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path></svg>
                        </a>
                        <a href="<?php echo esc_attr(get_setting('social_instagram', '#')); ?>" target="_blank" rel="noopener" class="social-icon-btn" aria-label="Instagram">
                            <!-- Instagram Clean Icon SVG -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                    </div>
                </div>

                <!-- Sitemap Navigation Links -->
                <div class="footer-links">
                    <h4>Agency</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#services">Services</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#work">Case Studies</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#about">About Us</a></li>
                    </ul>
                </div>

                <!-- Utilities/Legal Links -->
                <div class="footer-links">
                    <h4>Portal</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Console</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#privacy">Privacy Policy</a></li>
                        <li><a href="<?php echo BASE_URL; ?>#terms">Terms of Service</a></li>
                        <li><a href="tel:<?php echo esc_attr(APP_PHONE); ?>">Contact Phone</a></li>
                    </ul>
                </div>

                <!-- Contact details column -->
                <div class="footer-links">
                    <h4>Get In Touch</h4>
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--color-text-secondary-dark);">
                        <strong>Phone:</strong> <a href="tel:<?php echo esc_attr(APP_PHONE); ?>" style="color: var(--color-accent);"><?php echo esc(APP_PHONE); ?></a>
                    </p>
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--color-text-secondary-dark);">
                        <strong>Email:</strong> <a href="mailto:<?php echo esc_attr(get_setting('contact_email', 'hello@webfalx.com')); ?>"><?php echo esc(get_setting('contact_email', 'hello@webfalx.com')); ?></a>
                    </p>
                    <p style="font-size: 0.9rem; color: var(--color-text-secondary-dark);">
                        <strong>Location:</strong> <?php echo esc(get_setting('contact_address', 'California, USA')); ?>
                    </p>
                </div>
            </div>

            <!-- Footer Bottom copyrights -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo esc(APP_NAME); ?>. All rights reserved. Crafting Digital Excellence.</p>
                <p>Designed and Built by <?php echo esc(APP_NAME); ?></p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" aria-label="Back to Top">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
    </button>

    <!-- External Animation CDN Libraries (GSAP, ScrollTrigger) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <!-- Custom Client Application Engines -->
    <script src="<?php echo BASE_URL; ?>assets/js/main.js?v=1.0.2"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/animations.js?v=1.0.2"></script>
</body>
</html>
