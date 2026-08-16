-- WebFalx Complete Seed Data

-- 1. Default Admin
INSERT INTO `admins` (`id`, `username`, `password_hash`, `email`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$zN8n0cOEIHP50Q9jVC6ybO3txoMnlCGpeXGMxDAvyUHh1wyVMXwm6', 'admin@webfalx.com', 'Super Admin', 'active')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- 2. Global Configurations
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('site_name', 'WebFalx', 'general', 'Company brand name'),
('site_title', 'WebFalx | Premium Digital Marketing & Web Development Agency', 'seo', 'Page SEO Title tag'),
('site_description', 'WebFalx is a premium international Digital Marketing & Web Development Agency. We build high-conversion websites and custom digital solutions that scale your business.', 'seo', 'Page SEO Description tag'),
('site_keywords', 'digital marketing, web development, custom software, SEO, conversion rate optimization, premium agency', 'seo', 'Page SEO Keywords'),
('contact_phone', '6266273414', 'contact', 'Agency contact phone'),
('contact_email', 'hello@webfalx.com', 'contact', 'Agency contact email'),
('contact_address', 'Pasadena, California, USA', 'contact', 'Agency physical address'),
('social_linkedin', 'https://linkedin.com/company/webfalx', 'social', 'LinkedIn profile URL'),
('social_twitter', 'https://twitter.com/webfalx', 'social', 'Twitter/X profile URL'),
('social_instagram', 'https://instagram.com/webfalx', 'social', 'Instagram profile URL'),
('google_analytics_id', 'G-XXXXXXXXXX', 'integrations', 'Google Analytics ID'),
('schema_organization_json', '{"@context":"https://schema.org","@type":"Organization","name":"WebFalx","url":"https://webfalx.com","logo":"https://webfalx.com/assets/images/logo.png","contactPoint":{"@type":"ContactPoint","telephone":"+1-626-627-3414","contactType":"customer service"}}', 'seo', 'Organization schema markup in JSON-LD'),
('hero_typing_terms', 'Local Businesses,Startups,Clinics & Doctors,E-commerce Brands,Corporate Brands', 'homepage', 'Comma-separated terms for the hero typing animation'),

-- Static numbers for dynamic statistics counters
('stat_total_projects', '150', 'portfolio_stats', 'Total completed projects counter'),
('stat_websites_delivered', '95', 'portfolio_stats', 'Total websites delivered counter'),
('stat_shopify_stores', '35', 'portfolio_stats', 'Total Shopify stores counter'),
('stat_crm_systems', '20', 'portfolio_stats', 'Total CRM systems counter'),
('stat_happy_clients', '120', 'stats', 'Number of happy clients'),
('stat_years_experience', '8', 'stats', 'Years of agency experience'),
('stat_countries_served', '5', 'stats', 'Countries served globally'),
('stat_support_hours', '24', 'stats', 'Support system duration (e.g. 24/7)'),

-- SMTP and Auto-Reply Variables (Prompt 6)
('smtp_host', 'sandbox.smtp.mailtrap.io', 'email', 'SMTP Server hostname'),
('smtp_port', '2525', 'email', 'SMTP Server Port'),
('smtp_user', 'smtp_username_placeholder', 'email', 'SMTP Server Username'),
('smtp_pass', 'smtp_password_placeholder', 'email', 'SMTP Server Password'),
('sender_name', 'WebFalx Engineering', 'email', 'Sender Display Name'),
('sender_email', 'noreply@webfalx.com', 'email', 'Sender Display Email'),
('notification_email', 'admin@webfalx.com', 'email', 'Lead Notification recipient email'),
('auto_reply_subject', 'WebFalx Lead Received - WF-{id}', 'email', 'Confirmation auto response subject'),
('auto_reply_template', 'Hello {name},\n\nThank you for reaching out to WebFalx. A senior technical engineer has received your project details (Lead ID: {id}) and will review your specifications.\n\nWe will follow up with you within 2 hours to coordinate a free estimate.\n\nBest regards,\nWebFalx Team', 'email', 'Confirmation auto response template'),

-- WhatsApp and Call Integration configuration
('whatsapp_number', '16266273414', 'contact', 'WhatsApp integration phone number'),
('whatsapp_prefilled_msg', 'Hello WebFalx! I would like to review a custom web project scope.', 'contact', 'Default pre-filled WhatsApp click message'),
('google_map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3301.2407221448834!2d-118.14669868478191!3d34.14778438058204!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80c2c36dec46002f%3A0xc3f8e5f27c3bcbe0!2sPasadena%2C%20CA!5e0!3m2!1sen!2sus!4v1627000000000!5m2!1sen!2sus', 'contact', 'Google maps iframe embed URL'),
('google_map_enabled', '1', 'contact', 'Enable or disable public Google Maps displays'),

-- Theme Customization settings coordinates
('theme_color_primary', '#2563eb', 'theme', 'Primary color (HEX format)'),
('theme_color_secondary', '#7c3aed', 'theme', 'Secondary color (HEX format)'),
('theme_color_accent', '#06b6d4', 'theme', 'Accent color (HEX format)'),
('theme_color_bg_dark', '#0f172a', 'theme', 'Background color (HEX format)'),
('theme_font_family', 'Inter, sans-serif', 'theme', 'Body text Font Family name'),
('theme_border_radius', '0.5rem', 'theme', 'Border radius layout variables'),
('theme_animation_speed', '0.3s', 'theme', 'Hover transitions interval value'),
('theme_dark_mode_enabled', '1', 'theme', 'Enable dark-mode styling controls'),

-- Calculator Pricing Rules (Prompt 10)
('calc_base_shopify', '3000', 'pricing', 'Shopify Store base price'),
('calc_base_wordpress', '2000', 'pricing', 'WordPress website base price'),
('calc_base_custom_php', '4000', 'pricing', 'Custom PHP website base price'),
('calc_per_page_rate', '150', 'pricing', 'Rate per content page'),
('calc_seo_rate', '800', 'pricing', 'Add-on SEO setup price'),
('calc_hosting_rate', '250', 'pricing', 'Add-on hosting setup price'),
('calc_maintenance_rate', '350', 'pricing', 'Add-on monthly maintenance price')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);

-- 3. Core Text Blocks
INSERT INTO `content_blocks` (`block_key`, `title`, `subtitle`, `content`) VALUES
('hero_section', 'We Engineer High-Conversion Digital Experiences', 'INNOVATION MEET AESTHETICS', 'WebFalx is a premium agency crafting state-of-the-art web architectures, digital marketing funnels, and luxury branding for elite brands globally.'),
('cta_section', 'Ready to Scale Your Brand to the Next Level?', 'LET\'S COLLABORATE', 'Partner with WebFalx today and gain access to premium engineering, high-ROI marketing strategies, and design experiences that inspire trust and drive results.'),
('about_summary', 'We Don\'t Just Build Websites. We Build Digital Empires.', 'THE WEBFALX WAY', 'Our interdisciplinary team merges cognitive marketing psychology, high-performance web engineering, and award-winning design to construct high-velocity growth channels for companies around the world.'),

-- About Us Story blocks
('about_story', 'A Vision Coded Into Reality', 'THE WEBFALX ORIGINS', 'Established in Pasadena, California, WebFalx arose from a core observation: standard templates and page builder platforms were failing modern businesses.'),
('about_mission', 'To build secure, fast, and high-conversion web architectures that maximize company revenues.', 'OUR MISSION', 'We balance clean coding standards with CRO psychology.'),
('about_vision', 'To establish ourselves as the global benchmark for bespoke web engineering and performance digital marketing.', 'OUR VISION', 'We strive to eliminate slow loading latency times and generic templates.')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`), `subtitle`=VALUES(`subtitle`), `content`=VALUES(`content`);

-- 4. Homepage Section Layout Control
INSERT INTO `homepage_sections` (`section_key`, `section_name`, `display_order`, `is_active`) VALUES
('hero', 'Hero Section', 10, 1),
('trust_bar', 'Client Logos Trust Bar', 20, 1),
('why_choose', 'Why Choose WebFalx', 30, 1),
('services', 'Services Preview', 40, 1),
('process', 'Our Process Timeline', 50, 1),
('portfolio', 'Featured Portfolio', 60, 1),
('stats', 'Animated Statistics', 70, 1),
('testimonials', 'Client Testimonials', 80, 1),
('faq', 'FAQs Accordion', 90, 1),
('blog', 'Latest Blogs Showcase', 100, 1),
('cta', 'Final Call-to-Action', 110, 1)
ON DUPLICATE KEY UPDATE `display_order`=VALUES(`display_order`), `is_active`=VALUES(`is_active`);

-- 5. Client Trust Logos
INSERT INTO `client_logos` (`company_name`, `logo_url`, `display_order`, `is_active`) VALUES
('TechCorp', 'TechCorp', 10, 1),
('ApexGroup', 'ApexGroup', 20, 1),
('Clinique', 'Clinique', 30, 1),
('RealEstateInc', 'RealEstateInc', 40, 1),
('EcomBuilders', 'EcomBuilders', 50, 1),
('Vanguard', 'Vanguard', 60, 1)
ON DUPLICATE KEY UPDATE `logo_url`=VALUES(`logo_url`), `is_active`=VALUES(`is_active`);

-- 6. Service Categories
INSERT INTO `service_categories` (`id`, `name`, `slug`, `icon_svg`, `image_url`, `display_order`, `is_active`) VALUES
(1, 'Web Development', 'web-development', 'code', 'https://images.unsplash.com/photo-1547082299-de196ea013d6?q=80&w=600', 10, 1),
(2, 'E-commerce Solutions', 'ecommerce', 'shopping-cart', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=600', 20, 1),
(3, 'Digital Marketing', 'digital-marketing', 'globe', 'https://images.unsplash.com/photo-1432821596592-e2c18b78144f?q=80&w=600', 30, 1),
(4, 'UI/UX & Branding', 'branding-design', 'brush', 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?q=80&w=600', 40, 1),
(5, 'Business Automation', 'business-automation', 'cpu', 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=600', 50, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `slug`=VALUES(`slug`), `is_active`=VALUES(`is_active`);

-- 7. Agency Services
INSERT INTO `services` (`id`, `category_id`, `title`, `slug`, `description`, `full_description`, `icon_svg`, `meta_title`, `meta_description`, `hero_image`, `features`, `benefits`, `technologies`, `packages_json`, `is_featured`, `is_active`, `display_order`) VALUES
(1, 2, 'Shopify Development', 'shopify-development', 'High-conversion Shopify stores built with speed, custom themes, and conversion triggers.', 'We build bespoke Shopify experiences designed from the ground up to convert visits into sales.', '<svg width=\"24\" height=\"24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" viewBox=\"0 0 24 24\"><circle cx=\"9\" cy=\"21\" r=\"1\"/><circle cx=\"20\" cy=\"21\" r=\"1\"/><path d=\"M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6\"/></svg>', 'Shopify Development Agency | WebFalx E-commerce Experts', 'Bespoke Shopify development agency building ultra-fast Liquid themes, API connections, and high-conversion e-commerce funnels.', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=1200', 'Bespoke Liquid Theme Design\nShopify API & ERP Integration\nOptimized Core Web Vitals Speed\nInteractive Checkout Upgrades', 'Increase average order values (AOV) by 24%\nHalve mobile loading latency times\nGain complete content controls without code lock-in', 'Shopify, JavaScript, Liquid, HTML5, CSS3, GraphQL', '[{\"name\":\"Launch Tier\",\"price\":\"$3,500\",\"features\":[\"Custom Homepage\",\"Up to 5 Collections\",\"Responsive layouts\",\"Essential Analytics\"]},{\"name\":\"Pro Growth\",\"price\":\"$6,000\",\"features\":[\"Bespoke Theme Development\",\"ERP / CRM Integrations\",\"Custom Cart Upsells\",\"Advanced Speed Tuning\"]}]', 1, 1, 10),
(2, 1, 'WordPress Development', 'wordpress-development', 'Custom head-less or theme-based WordPress systems built without code bloat.', 'WordPress powers over 40% of the web, but standard themes are bloated and slow.', '<svg width=\"24\" height=\"24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" viewBox=\"0 0 24 24\"><rect width=\"20\" height=\"14\" x=\"2\" y=\"3\" rx=\"2\" ry=\"2\"/><line x1=\"8\" x2=\"16\" y1=\"21\" y2=\"21\"/><line x1=\"12\" x2=\"12\" y1=\"17\" y2=\"21\"/></svg>', 'Custom WordPress Development Services | WebFalx', 'High-performance WordPress developer agency. Custom themes, Gutenberg blocks, headless architectures, and security configurations.', 'https://images.unsplash.com/photo-1547082299-de196ea013d6?q=80&w=1200', 'Custom Gutenberg Block Development\nSecure Host & Cache Setup\nZero Page-Builder Bloat\nAPI Headless Integrations', 'Top-tier SEO structures right out of the box\nEasily manage layouts without breaking templates\nPrevent server-side security vulnerabilities', 'WordPress, PHP 8, MySQL, Gutenberg, React, Vanilla JS', '[{\"name\":\"Starter Site\",\"price\":\"$2,800\",\"features\":[\"Bespoke WordPress theme\",\"Up to 5 layouts\",\"Schema setup\",\"Contact forms integrations\"]},{\"name\":\"Enterprise Blocks\",\"price\":\"$5,200\",\"features\":[\"Headless configuration option\",\"Custom block layouts library\",\"CRM integrations\",\"Advanced SEO setups\"]}]', 1, 1, 20),
(3, 3, 'Search Engine Optimization', 'seo-marketing', 'Rank #1 on Google. We combine technical code modifications and keyword targeting.', 'Our technical SEO services focus on code optimizations, JSON-LD schema layouts, structure audits, and content audits to earn authority.', '<svg width=\"24\" height=\"24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" viewBox=\"0 0 24 24\"><circle cx=\"11\" cy=\"11\" r=\"8\"/><line x1=\"21\" x2=\"16.65\" y1=\"21\" y2=\"16.65\"/></svg>', 'Technical SEO Agency & Search Engine Ranking | WebFalx', 'Search Engine Optimization agency. Increase rankings, organic website clicks, and sales leads with clean code compliance.', 'https://images.unsplash.com/photo-1432821596592-e2c18b78144f?q=80&w=1200', 'JSON-LD Schema Implementations\nMobile Usability Auditing\nKeyword & Competitor Mapping\nOptimized Canonical & Breadcrumbs', 'Dominate local and national search channels\nReduce reliance on expensive paid ads campaigns\nGain long-term organic authority and trust', 'Google Search Console, Schema JSON, Ahrefs, Semrush', '[{\"name\":\"SEO Growth\",\"price\":\"$1,500/mo\",\"features\":[\"Keyword Strategy Map\",\"Technical code audits\",\"3 Optimized blog insights/mo\",\"Local SEO setup\"]},{\"name\":\"Market Dominator\",\"price\":\"$3,000/mo\",\"features\":[\"Complete market takeover strategy\",\"Advanced landing creations\",\"10 Optimized pages/mo\",\"Interactive link-building plans\"]}]', 1, 1, 30),
(4, 5, 'CRM & Automation Systems', 'crm-automation', 'Connect internal databases, emails, and forms to eliminate repetitive labor.', 'We connect and automate operations across platforms (Salesforce, HubSpot, custom databases, and emails) to reduce operational friction.', '<svg width=\"24\" height=\"24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" viewBox=\"0 0 24 24\"><path d=\"M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2\"/><circle cx=\"9\" cy=\"7\" r=\"4\"/><path d=\"M23 21v-2a4 4 0 0 0-3-3.87\"/><path d=\"M16 3.13a4 4 0 0 1 0 7.75\"/></svg>', 'Custom CRM Integrations & Business Automations | WebFalx', 'Streamline agency operations. We build custom business dashboards, Salesforce connections, and automated client funnels.', 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=1200', 'Bespoke CRM Dashboard Panels\nThird-Party API Connections\nLead Auto-Responder Workflows\nDatabase Integrations', 'Save up to 15 hours of manual admin labor weekly\nInstantly follow up with digital marketing leads\nConsolidate dashboard statistics in one layout', 'PHP 8, MySQL, Python, Node, Salesforce API, Zapier', '[{\"name\":\"Automation Setup\",\"price\":\"$3,200\",\"features\":[\"App connections (Zapier/Make)\",\"Form-to-CRM integrations\",\"Default lead email sequences\"]},{\"name\":\"Bespoke CRM Portal\",\"price\":\"$8,500\",\"features\":[\"Custom coded server portal\",\"Private database setup\",\"Advanced visual dashboards\",\"Employee roles configurations\"]}]', 1, 1, 40)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `is_active`=VALUES(`is_active`);

-- 8. Agency Process Steps
INSERT INTO `process_steps` (`step_number`, `title`, `description`, `display_order`, `is_active`) VALUES
(1, 'Research', 'Analyzing target audience behaviors, competitor gaps, and marketing opportunities.', 10, 1),
(2, 'Planning', 'Developing user journeys, software architecture plans, and conversion-funnel pathways.', 20, 1),
(3, 'Design', 'Drafting high-fidelity UI layout frames with premium, minimalist aesthetic styles.', 30, 1),
(4, 'Development', 'Writing clean, optimized markup and backend integrations without code bloat.', 40, 1),
(5, 'Testing', 'Conducting thorough validation against load performance, mobile formats, and forms security.', 50, 1),
(6, 'Launch', 'Deploying optimizations, setting up server caches, and submitting canonical links to crawlers.', 60, 1),
(7, 'Support', 'Providing ongoing code maintenance, tracking conversion analytics, and maintaining integrations.', 70, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `is_active`=VALUES(`is_active`);

-- 9. Portfolio Categories
INSERT INTO `portfolio_categories` (`id`, `name`, `slug`, `display_order`, `is_active`) VALUES
(1, 'Shopify Store', 'shopify-store', 10, 1),
(2, 'WordPress Website', 'wordpress-website', 20, 1),
(3, 'CRM System', 'crm-system', 30, 1),
(4, 'UI/UX Design', 'uiux-design', 40, 1),
(5, 'Custom PHP Website', 'custom-php-website', 50, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `slug`=VALUES(`slug`), `is_active`=VALUES(`is_active`);

-- 10. Portfolio Projects
INSERT INTO `portfolio_projects` (`id`, `category_id`, `title`, `slug`, `client`, `industry`, `project_type`, `technology`, `completion_date`, `description`, `full_overview`, `challenge`, `solution`, `features`, `results`, `thumbnail_url`, `desktop_screenshot`, `tablet_screenshot`, `mobile_screenshot`, `gallery_json`, `website_url`, `is_featured`, `is_active`, `display_order`) VALUES
(1, 3, 'Apex Medical Portal', 'apex-medical-portal', 'Apex Medical Group', 'Healthcare', 'CRM System Integration', 'PHP, MySQL, Tailwind, Vanilla JS, Twilio API', '2026-03-15', 'A clean, high-conversion patient intake portal designed to simplify bookings and clinical onboarding.', 
'Apex Medical Group required a secure patient intake portal.',
'Patients had to print, manually write, and scan intake documentation prior to clinical visits.',
'We developed a responsive custom database portal.',
'HIPAA Compliant Security Logs\nSMS Automated Appointment Text Alerts\nInteractive Health Assessments Form\nDoctor Schedule Management Calendars',
'Reduced check-in delays by 68%',
'https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=600',
'https://images.unsplash.com/photo-1506784983877-45594efa4cbe?q=80&w=1200',
'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=600',
'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=400',
'[\"https://images.unsplash.com/photo-1506784983877-45594efa4cbe?q=80&w=1200\"]',
'https://apexmedical.com', 1, 1, 10),
(2, 1, 'E-com Luxury Wear', 'ecom-luxury-wear', 'Vanguard Couture', 'Apparel & E-commerce', 'Shopify Store Theme', 'Shopify, Liquid, JavaScript, HTML5, WebP', '2026-04-10', 'An elegant storefront presenting high-end apparel with fast page loading speeds and micro-interactions.',
'Vanguard Couture wanted a luxury digital store layout.',
'Existing templates loaded slowly due to excessive JS scripts.',
'We designed a bespoke Shopify Liquid layout.',
'Bespoke Minimalist Product Slides\nDynamic AJAX Slide-out Cart\nOptimized Core Web Vitals Scripting\nFilterable Collections Sorting',
'Average mobile page load time dropped to 1.1s',
'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=600',
'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1200',
'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=600',
'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=400',
'[\"https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1200\"]',
'https://vanguardcouture.com', 1, 1, 20),
(3, 5, 'Real Estate Horizon', 'real-estate-horizon', 'Horizon Real Estate', 'Real Estate', 'Custom PHP Web Portal', 'PHP 8, MySQL, Leaflet Maps API, CSS Grid, AJAX', '2026-05-18', 'An interactive listing platform with responsive interactive maps and real-time support channels.',
'Horizon Real Estate needed an integrated dashboard.',
'Proprietary plugins caused slow maps renders.',
'We wrote custom vanilla JS scripts connecting the OpenStreetMap system.',
'Custom Leaflet Map Pins\nInteractive Filterable Property Search\nReal-time Agent SMS Notifications\nMulti-Image Drag Upload Portal',
'Lead submission count grew by 48%',
'https://images.unsplash.com/photo-1560518883-ce09059eeffa?q=80&w=600',
'https://images.unsplash.com/photo-1560518883-ce09059eeffa?q=80&w=1200',
'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=600',
'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=400',
'[\"https://images.unsplash.com/photo-1560518883-ce09059eeffa?q=80&w=1200\"]',
'https://horizonproperties.com', 1, 1, 30)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `is_active`=VALUES(`is_active`);

-- 11. Testimonials
INSERT INTO `testimonials` (`service_id`, `project_id`, `client_name`, `client_business`, `client_image_url`, `rating`, `review`, `is_active`, `display_order`) VALUES
(1, 2, 'Sarah Jenkins', 'Vanguard Apparel', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=200', 5, 'Absolute professionals.', 1, 10),
(2, 1, 'Dr. Raymond Mercer', 'Apex Health Group', 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=200', 5, 'WebFalx modernized our clinical intake process.', 1, 20),
(NULL, 3, 'Marcus Vance', 'Horizon Properties', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=200', 5, 'The custom database integration is seamless.', 1, 30)
ON DUPLICATE KEY UPDATE `review`=VALUES(`review`), `is_active`=VALUES(`is_active`);

-- 12. Service Specific FAQs
INSERT INTO `service_faqs` (`service_id`, `question`, `answer`, `display_order`) VALUES
(1, 'Can you import existing products and customers?', 'Yes.', 10),
(2, 'How do you secure custom WordPress sites?', 'We implement database prefix protection.', 10)
ON DUPLICATE KEY UPDATE `answer`=VALUES(`answer`);

-- 13. General FAQs
INSERT INTO `faqs` (`question`, `answer`, `is_active`, `display_order`) VALUES
('Why vanilla technologies?', 'Vanilla technologies ensure maximum loading performance.', 1, 10),
('Do we own the source code?', 'Yes, 100%.', 1, 20)
ON DUPLICATE KEY UPDATE `answer`=VALUES(`answer`);

-- 14. Core Values
INSERT INTO `core_values` (`name`, `description`, `icon_svg`, `display_order`, `is_active`) VALUES
('Pure Innovation', 'We write custom code tailored to your exact product requirements.', 'cpu', 10, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- 15. Milestones
INSERT INTO `company_milestones` (`year`, `title`, `description`, `image_url`, `display_order`, `is_active`) VALUES
('2018', 'Started WebFalx', 'Launched our agency in Pasadena.', 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=600', 10, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- 16. Achievements
INSERT INTO `achievements` (`year`, `title`, `description`, `icon_svg`, `display_order`, `is_active`) VALUES
('2024', 'Elite Shopify Developer Seal', 'Recognized for Liquid themes.', 'award', 10, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- 17. Technologies We Use
INSERT INTO `technologies` (`name`, `icon_svg`, `display_order`, `is_active`) VALUES
('PHP 8', 'https://images.unsplash.com/photo-1599507593499-a3f7f7d9a224?q=80&w=150', 10, 1)
ON DUPLICATE KEY UPDATE `is_active`=VALUES(`is_active`);

-- 18. Industries We Serve
INSERT INTO `industries_served` (`name`, `description`, `icon_svg`, `display_order`, `is_active`) VALUES
('Healthcare & Clinics', 'Syncing patients onboarding portals.', 'plus-square', 10, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- 19. Team Members
INSERT INTO `team_members` (`name`, `designation`, `bio`, `experience`, `skills`, `social_links_json`, `image_url`, `display_order`, `is_active`) VALUES
('Alex Rivera', 'Chief Technology Officer', 'Specializes in low-latency custom PHP databases.', '10 Years', 'PHP, MySQL', '{\"linkedin\":\"https://linkedin.com\"}', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200', 10, 1)
ON DUPLICATE KEY UPDATE `bio`=VALUES(`bio`);

-- 20. Skills and Expertise
INSERT INTO `skills_expertise` (`name`, `percentage`, `display_order`, `is_active`) VALUES
('Custom Database Engineering', 95, 10, 1)
ON DUPLICATE KEY UPDATE `percentage`=VALUES(`percentage`);

-- 21. Certifications
INSERT INTO `certifications` (`title`, `issuer`, `year`, `description`, `logo_url`, `display_order`, `is_active`) VALUES
('Shopify Plus Developer Certificate', 'Shopify Partners', '2024', 'Validation of theme code.', 'https://images.unsplash.com/photo-1627163430004-67ad3a09e370?q=80&w=150', 10, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- 22. Awards
INSERT INTO `awards` (`title`, `issuer`, `year`, `description`, `logo_url`, `display_order`, `is_active`) VALUES
('Awwwards Clean Code Badge', 'International Web Design Committee', '2025', 'Honorable recognition.', 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?q=80&w=150', 10, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- 23. Unified Leads Table
INSERT INTO `leads` (`lead_id`, `lead_type`, `full_name`, `phone`, `whatsapp`, `email`, `company_name`, `city`, `state`, `country`, `business_type`, `service_interested`, `budget`, `subject`, `message`, `status`, `priority`) VALUES
('WF-202607-0001', 'inquiry', 'John Doe', '6265551234', '6265551234', 'johndoe@gmail.com', 'Doe Logistics', 'Pasadena', 'California', 'USA', 'Logistics', 'Custom PHP Web Portal', '$5k - $10k', 'New Portal scope', 'Looking to construct a custom portal.', 'New', 'High')
ON DUPLICATE KEY UPDATE `message`=VALUES(`message`);

-- 24. CMS Blog Tables
INSERT INTO `blog_authors` (`id`, `name`, `designation`, `bio`, `image_url`, `social_links_json`, `display_order`) VALUES
(1, 'Alex Rivera', 'Chief Technology Officer', 'alex is the technical architect.', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200', '{\"linkedin\":\"https://linkedin.com/in/alexrivera\"}', 10)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `description`, `image_url`, `meta_title`, `meta_description`, `display_order`) VALUES
(1, 'SEO Marketing', 'seo-marketing', 'Technical ranking advice.', 'https://images.unsplash.com/photo-1432821596592-e2c18b78144f?q=80&w=600', 'Technical SEO Guides', 'Rank #1.', 10)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `blog_tags` (`id`, `name`, `slug`) VALUES
(1, 'PHP 8', 'php8')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `blog_posts` (`id`, `author_id`, `category_id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `reading_time`, `meta_title`, `meta_description`, `focus_keyword`, `is_featured`, `is_popular`, `is_trending`, `status`) VALUES
(1, 1, 1, 'How Page Loading Speed Directly Affects Your Conversion Rates', 'how-page-speed-affects-conversion-rates', 'Discover the correlation.', '<h2>Introduction</h2><p>Page loading speeds affect conversions.</p>', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=600', 5, 'Speed Drives Conversions', 'Page speed details.', 'page speed', 1, 1, 0, 'published')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

INSERT INTO `blog_post_tags` (`post_id`, `tag_id`) VALUES
(1, 1)
ON DUPLICATE KEY UPDATE `post_id`=VALUES(`post_id`);

INSERT INTO `blog_comments` (`id`, `post_id`, `parent_id`, `name`, `email`, `comment`, `status`) VALUES
(1, 1, NULL, 'Jane Miller', 'jane@miller.com', 'This is true.', 'approved')
ON DUPLICATE KEY UPDATE `comment`=VALUES(`comment`);

INSERT INTO `newsletter_subscribers` (`id`, `email`) VALUES
(1, 'subscriber1@gmail.com')
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`);

-- 25. Enterprise settings
INSERT INTO `menus` (`id`, `title`, `url`, `display_order`, `is_active`) VALUES
(1, 'Home', 'index.php', 10, 1),
(2, 'Services', 'services.php', 20, 1),
(3, 'Portfolio', 'portfolio.php', 30, 1),
(4, 'About Us', 'about.php', 40, 1),
(5, 'Blog Journal', 'blog.php', 50, 1),
(6, 'Contact Desk', 'contact.php', 60, 1)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`), `url`=VALUES(`url`);

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`) VALUES
(1, 1, 'Admin Login', 'Super Admin logged in successfully.', '127.0.0.1')
ON DUPLICATE KEY UPDATE `action`=VALUES(`action`);

-- 26. Client Portal & Project Seeds
INSERT INTO `clients` (`id`, `name`, `company`, `email`, `phone`, `password_hash`, `status`) VALUES
(1, 'Sarah Jenkins', 'Vanguard Couture', 'client@company.com', '6265551212', '$2y$12$Lt69ArDmfaiPcYzZFlCy4uePQ0ZjlOjM0oUfO.fTqotICw/Tu2Ufa', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `projects` (`id`, `client_id`, `name`, `project_type`, `status`, `progress_percent`, `start_date`, `estimated_completion`, `priority`, `description`, `assigned_team`) VALUES
(1, 1, 'Bespoke Shopify Store', 'Shopify Store', 'Development', 65, '2026-06-01', '2026-08-15', 'High', 'Developing Liquid themes.', 'Alex Rivera, Marcus Vance')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `project_milestones` (`id`, `project_id`, `name`, `description`, `due_date`, `completion_date`, `status`) VALUES
(1, 1, 'Requirement Gathering', 'Analyzing layout specs.', '2026-06-05', '2026-06-04', 'Completed')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `project_approvals` (`id`, `project_id`, `approval_item`, `description`, `status`, `comments`) VALUES
(1, 1, 'Homepage UI Layout Mockup', 'Review grids.', 'Approved', 'Approved.')
ON DUPLICATE KEY UPDATE `approval_item`=VALUES(`approval_item`);

INSERT INTO `project_revisions` (`id`, `project_id`, `title`, `description`, `priority`, `status`) VALUES
(1, 1, 'Adjust cart slide timing', 'Slide timing adjustment.', 'Medium', 'Complete')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

INSERT INTO `project_messages` (`id`, `project_id`, `sender_type`, `sender_id`, `message`, `is_read`) VALUES
(1, 1, 'admin', 1, 'Hi Sarah, Liquid transitions done.', 1)
ON DUPLICATE KEY UPDATE `message`=VALUES(`message`);

INSERT INTO `project_meetings` (`id`, `project_id`, `meeting_date`, `meeting_time`, `platform`, `meeting_link`, `notes`) VALUES
(1, 1, '2026-07-20', '10:00:00', 'Google Meet', 'https://meet.google.com/abc-defg-hij', 'Weekly update.')
ON DUPLICATE KEY UPDATE `platform`=VALUES(`platform`);

INSERT INTO `project_invoices` (`id`, `project_id`, `invoice_number`, `amount`, `status`, `due_date`) VALUES
(1, 1, 'INV-2026-001', 3500.00, 'Paid', '2026-06-10')
ON DUPLICATE KEY UPDATE `invoice_number`=VALUES(`invoice_number`);


-- ==========================================================
-- 27. BUSINESS AUTOMATION SEEDS (PROMPT 10)
-- ==========================================================

-- Seed Appointments calendar bookings
INSERT INTO `appointments` (`id`, `client_id`, `name`, `email`, `phone`, `service`, `booking_date`, `booking_time`, `meeting_type`, `status`, `notes`) VALUES
(1, 1, 'Sarah Jenkins', 'client@company.com', '6265551212', 'Shopify Development', '2026-07-20', '10:00:00', 'Google Meet', 'Approved', 'Weekly update sync.')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Quotations
INSERT INTO `quotations` (`id`, `client_id`, `quote_number`, `project_type`, `pages`, `features_json`, `calculated_total`, `status`) VALUES
(1, 1, 'Q-2026-1001', 'Shopify Store', 10, '[\"SEO Required\",\"Hosting setup\",\"Maintenance plan\"]', 4500.00, 'Sent')
ON DUPLICATE KEY UPDATE `quote_number`=VALUES(`quote_number`);

-- Seed Proposals
INSERT INTO `proposals` (`id`, `project_id`, `client_id`, `title`, `scope_of_work`, `timeline`, `investment`, `status`) VALUES
(1, 1, 1, 'Shopify Store Theme Proposal', 'Develop dynamic Liquid store theme, setup catalog collections and optimize loading velocity speeds.', '6 Weeks', 4500.00, 'Pending')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Seed Redirect rules
INSERT INTO `redirects` (`id`, `source_url`, `target_url`, `redirect_type`) VALUES
(1, '/old-about', 'about.php', 301)
ON DUPLICATE KEY UPDATE `source_url`=VALUES(`source_url`);
