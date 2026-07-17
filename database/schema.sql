-- WebFalx Database Schema
-- Production Ready, Optimized, Secure

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `proposals`;
DROP TABLE IF EXISTS `quotations`;
DROP TABLE IF EXISTS `appointments`;

DROP TABLE IF EXISTS `project_invoices`;
DROP TABLE IF EXISTS `project_meetings`;
DROP TABLE IF EXISTS `project_messages`;
DROP TABLE IF EXISTS `project_revisions`;
DROP TABLE IF EXISTS `project_approvals`;
DROP TABLE IF EXISTS `project_files`;
DROP TABLE IF EXISTS `project_milestones`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `clients`;

DROP TABLE IF EXISTS `media_items`;
DROP TABLE IF EXISTS `menus`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `newsletter_subscribers`;
DROP TABLE IF EXISTS `blog_comments`;
DROP TABLE IF EXISTS `blog_post_tags`;
DROP TABLE IF EXISTS `blog_posts`;
DROP TABLE IF EXISTS `blog_tags`;
DROP TABLE IF EXISTS `blog_categories`;
DROP TABLE IF EXISTS `blog_authors`;
DROP TABLE IF EXISTS `leads`;
DROP TABLE IF EXISTS `awards`;
DROP TABLE IF EXISTS `certifications`;
DROP TABLE IF EXISTS `skills_expertise`;
DROP TABLE IF EXISTS `team_members`;
DROP TABLE IF EXISTS `industries_served`;
DROP TABLE IF EXISTS `technologies`;
DROP TABLE IF EXISTS `achievements`;
DROP TABLE IF EXISTS `company_milestones`;
DROP TABLE IF EXISTS `core_values`;
DROP TABLE IF EXISTS `service_inquiries`;
DROP TABLE IF EXISTS `service_faqs`;
DROP TABLE IF EXISTS `blogs`;
DROP TABLE IF EXISTS `faqs`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `portfolio_projects`;
DROP TABLE IF EXISTS `process_steps`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `client_logos`;
DROP TABLE IF EXISTS `portfolio_categories`;
DROP TABLE IF EXISTS `service_categories`;
DROP TABLE IF EXISTS `homepage_sections`;
DROP TABLE IF EXISTS `content_blocks`;
DROP TABLE IF EXISTS `site_settings`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Admins Table
CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` VARCHAR(50) NOT NULL DEFAULT 'Admin',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `reset_token` VARCHAR(64) DEFAULT NULL,
    `reset_token_expires` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Site Settings Table
CREATE TABLE `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Content Blocks Table
CREATE TABLE `content_blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `block_key` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(255) DEFAULT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `content` TEXT DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `link_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Homepage Sections Manager
CREATE TABLE `homepage_sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_key` VARCHAR(50) NOT NULL UNIQUE,
    `section_name` VARCHAR(100) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Client Logos
CREATE TABLE `client_logos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(100) NOT NULL,
    `logo_url` VARCHAR(255) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Service Categories
CREATE TABLE `service_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon_svg` TEXT DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Agency Services
CREATE TABLE `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `title` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NOT NULL,
    `full_description` TEXT DEFAULT NULL,
    `icon_svg` TEXT NOT NULL,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `schema_markup` TEXT DEFAULT NULL,
    `hero_image` VARCHAR(255) DEFAULT NULL,
    `features` TEXT DEFAULT NULL,
    `benefits` TEXT DEFAULT NULL,
    `technologies` VARCHAR(255) DEFAULT NULL,
    `packages_json` TEXT DEFAULT NULL,
    `gallery_json` TEXT DEFAULT NULL,
    `cta_primary_text` VARCHAR(100) DEFAULT 'Get a Free Quote',
    `cta_primary_url` VARCHAR(255) DEFAULT '#contact',
    `cta_secondary_text` VARCHAR(100) DEFAULT 'Call Us',
    `cta_secondary_url` VARCHAR(255) DEFAULT 'tel:6266273414',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Agency Process Steps
CREATE TABLE `process_steps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `step_number` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Portfolio Categories
CREATE TABLE `portfolio_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Portfolio Projects
CREATE TABLE `portfolio_projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `title` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `client` VARCHAR(100) DEFAULT NULL,
    `industry` VARCHAR(100) DEFAULT NULL,
    `project_type` VARCHAR(100) DEFAULT NULL,
    `technology` TEXT NOT NULL,
    `completion_date` DATE DEFAULT NULL,
    `description` TEXT NOT NULL,
    `full_overview` TEXT DEFAULT NULL,
    `challenge` TEXT DEFAULT NULL,
    `solution` TEXT DEFAULT NULL,
    `features` TEXT DEFAULT NULL,
    `results` TEXT DEFAULT NULL,
    `thumbnail_url` VARCHAR(255) NOT NULL,
    `desktop_screenshot` VARCHAR(255) DEFAULT NULL,
    `tablet_screenshot` VARCHAR(255) DEFAULT NULL,
    `mobile_screenshot` VARCHAR(255) DEFAULT NULL,
    `gallery_json` TEXT DEFAULT NULL,
    `website_url` VARCHAR(255) DEFAULT NULL,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `schema_markup` TEXT DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
    `is_latest` TINYINT(1) NOT NULL DEFAULT 0,
    `is_recommended` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`category_id`) REFERENCES `portfolio_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Testimonials
CREATE TABLE `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT DEFAULT NULL,
    `project_id` INT DEFAULT NULL,
    `client_name` VARCHAR(100) NOT NULL,
    `client_business` VARCHAR(100) NOT NULL,
    `client_image_url` VARCHAR(255) NOT NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `review` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`project_id`) REFERENCES `portfolio_projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. FAQs Accordion
CREATE TABLE `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question` VARCHAR(255) NOT NULL,
    `answer` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Service Specific FAQs
CREATE TABLE `service_faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT NOT NULL,
    `question` VARCHAR(255) NOT NULL,
    `answer` TEXT NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Core Values
CREATE TABLE `core_values` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `icon_svg` TEXT NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Company Milestones
CREATE TABLE `company_milestones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `year` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Company Achievements
CREATE TABLE `achievements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `year` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `icon_svg` TEXT DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Technologies We Use
CREATE TABLE `technologies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `icon_svg` TEXT NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Industries We Serve
CREATE TABLE `industries_served` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon_svg` TEXT DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Team Members
CREATE TABLE `team_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(100) NOT NULL,
    `bio` TEXT DEFAULT NULL,
    `experience` VARCHAR(50) DEFAULT NULL,
    `skills` VARCHAR(255) DEFAULT NULL,
    `social_links_json` TEXT DEFAULT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Skills and Expertise progress indicators
CREATE TABLE `skills_expertise` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `percentage` INT NOT NULL DEFAULT 85,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Company Certifications
CREATE TABLE `certifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `issuer` VARCHAR(150) NOT NULL,
    `year` VARCHAR(50) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `logo_url` VARCHAR(255) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Company Awards
CREATE TABLE `awards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `issuer` VARCHAR(150) NOT NULL,
    `year` VARCHAR(50) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `logo_url` VARCHAR(255) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Unified Leads Table
CREATE TABLE `leads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lead_id` VARCHAR(50) NOT NULL UNIQUE,
    `lead_type` VARCHAR(50) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `whatsapp` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(100) NOT NULL,
    `company_name` VARCHAR(100) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `business_type` VARCHAR(100) DEFAULT NULL,
    `service_interested` VARCHAR(100) DEFAULT NULL,
    `budget` VARCHAR(50) DEFAULT NULL,
    `deadline` VARCHAR(50) DEFAULT NULL,
    `contact_method` VARCHAR(50) DEFAULT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `num_pages` INT DEFAULT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(50) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'New',
    `assigned_staff` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `followup_date` DATE DEFAULT NULL,
    `priority` VARCHAR(20) NOT NULL DEFAULT 'Medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. CMS Blog Tables
CREATE TABLE `blog_authors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(100) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `social_links_json` TEXT DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `author_id` INT DEFAULT NULL,
    `category_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `excerpt` TEXT DEFAULT NULL,
    `content` LONGTEXT NOT NULL,
    `featured_image` VARCHAR(255) DEFAULT NULL,
    `gallery_json` TEXT DEFAULT NULL,
    `video_url` VARCHAR(255) DEFAULT NULL,
    `download_file` VARCHAR(255) DEFAULT NULL,
    `reading_time` INT NOT NULL DEFAULT 5,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `focus_keyword` VARCHAR(150) DEFAULT NULL,
    `canonical_url` VARCHAR(255) DEFAULT NULL,
    `robots_meta` VARCHAR(100) DEFAULT 'index, follow',
    `status` VARCHAR(20) NOT NULL DEFAULT 'published',
    `scheduled_at` DATETIME DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
    `is_trending` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `blog_authors` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_post_tags` (
    `post_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`),
    FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `parent_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `comment` TEXT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `blog_comments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `newsletter_subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Enterprise System Modules
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(150) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menus` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `parent_id` INT DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL,
    `file_size` INT NOT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Client Portal & Project Tracking Tables
CREATE TABLE `clients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `company` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(50) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `project_type` VARCHAR(100) DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Planning',
    `progress_percent` INT DEFAULT 0,
    `start_date` DATE DEFAULT NULL,
    `estimated_completion` DATE DEFAULT NULL,
    `priority` VARCHAR(20) DEFAULT 'Medium',
    `description` TEXT DEFAULT NULL,
    `assigned_team` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_milestones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `due_date` DATE DEFAULT NULL,
    `completion_date` DATE DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'Pending',
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` INT DEFAULT NULL,
    `is_favorite` TINYINT(1) DEFAULT 0,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `approval_item` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'Pending',
    `comments` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_revisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` VARCHAR(20) DEFAULT 'Medium',
    `screenshot_path` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'Open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `sender_type` VARCHAR(20) NOT NULL,
    `sender_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_meetings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `meeting_date` DATE NOT NULL,
    `meeting_time` TIME NOT NULL,
    `platform` VARCHAR(100) DEFAULT 'Google Meet',
    `meeting_link` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `amount` DECIMAL(10,2) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'Pending',
    `due_date` DATE DEFAULT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- 27. BUSINESS AUTOMATION & CALCULATION TABLES (PROMPT 10)
-- ==========================================================

-- A. Appointments calendar bookings
CREATE TABLE `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `service` VARCHAR(100) DEFAULT NULL,
    `booking_date` DATE NOT NULL,
    `booking_time` TIME NOT NULL,
    `meeting_type` VARCHAR(50) DEFAULT 'Google Meet', -- Google Meet, Zoom, Phone, Office
    `status` VARCHAR(20) DEFAULT 'Pending', -- Pending, Approved, Cancelled
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- B. Quotations registry
CREATE TABLE `quotations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT DEFAULT NULL,
    `quote_number` VARCHAR(50) NOT NULL UNIQUE,
    `project_type` VARCHAR(100) NOT NULL,
    `pages` INT DEFAULT 1,
    `features_json` TEXT DEFAULT NULL,
    `calculated_total` DECIMAL(10,2) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'Draft', -- Draft, Sent, Accepted
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- C. Proposals templates registry
CREATE TABLE `proposals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT DEFAULT NULL,
    `client_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `scope_of_work` TEXT DEFAULT NULL,
    `timeline` VARCHAR(100) DEFAULT NULL,
    `investment` DECIMAL(10,2) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'Pending', -- Pending, Accepted, Changes Requested
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- D. URL Redirect rules table
CREATE TABLE `redirects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `source_url` VARCHAR(255) NOT NULL UNIQUE,
    `target_url` VARCHAR(255) NOT NULL,
    `redirect_type` INT DEFAULT 301,
    `clicks` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Indexes for performance queries
CREATE INDEX `idx_settings_key` ON `site_settings` (`setting_key`);
CREATE INDEX `idx_blocks_key` ON `content_blocks` (`block_key`);
CREATE INDEX `idx_sections_order` ON `homepage_sections` (`display_order`);
CREATE INDEX `idx_logos_order` ON `client_logos` (`display_order`);
CREATE INDEX `idx_categories_order` ON `service_categories` (`display_order`);
CREATE INDEX `idx_services_order` ON `services` (`display_order`);
CREATE INDEX `idx_services_slug` ON `services` (`slug`);
CREATE INDEX `idx_portfolio_cat_order` ON `portfolio_categories` (`display_order`);
CREATE INDEX `idx_portfolio_projects_order` ON `portfolio_projects` (`display_order`);
CREATE INDEX `idx_portfolio_projects_slug` ON `portfolio_projects` (`slug`);
CREATE INDEX `idx_process_order` ON `process_steps` (`display_order`);
CREATE INDEX `idx_testimonials_order` ON `testimonials` (`display_order`);
CREATE INDEX `idx_faqs_order` ON `faqs` (`display_order`);
CREATE INDEX `idx_service_faqs_order` ON `service_faqs` (`display_order`);

CREATE INDEX `idx_core_values_order` ON `core_values` (`display_order`);
CREATE INDEX `idx_milestones_order` ON `company_milestones` (`display_order`);
CREATE INDEX `idx_achievements_order` ON `achievements` (`display_order`);
CREATE INDEX `idx_tech_order` ON `technologies` (`display_order`);
CREATE INDEX `idx_industries_order` ON `industries_served` (`display_order`);
CREATE INDEX `idx_team_order` ON `team_members` (`display_order`);
CREATE INDEX `idx_skills_order` ON `skills_expertise` (`display_order`);
CREATE INDEX `idx_certifications_order` ON `certifications` (`display_order`);
CREATE INDEX `idx_awards_order` ON `awards` (`display_order`);

CREATE INDEX `idx_leads_id` ON `leads` (`lead_id`);
CREATE INDEX `idx_leads_status` ON `leads` (`status`);
CREATE INDEX `idx_leads_priority` ON `leads` (`priority`);
CREATE INDEX `idx_leads_created` ON `leads` (`created_at`);

-- Blog Specific indexes
CREATE INDEX `idx_blog_authors_order` ON `blog_authors` (`display_order`);
CREATE INDEX `idx_blog_categories_slug` ON `blog_categories` (`slug`);
CREATE INDEX `idx_blog_tags_slug` ON `blog_tags` (`slug`);
CREATE INDEX `idx_blog_posts_slug` ON `blog_posts` (`slug`);
CREATE INDEX `idx_blog_posts_status` ON `blog_posts` (`status`);
CREATE INDEX `idx_blog_posts_date` ON `blog_posts` (`created_at`);
CREATE INDEX `idx_blog_comments_status` ON `blog_comments` (`status`);

-- Enterprise specific indexes
CREATE INDEX `idx_activity_logs_created` ON `activity_logs` (`created_at`);
CREATE INDEX `idx_menus_order` ON `menus` (`display_order`);
CREATE INDEX `idx_media_items_uploaded` ON `media_items` (`uploaded_at`);

-- Client Portal indexes
CREATE INDEX `idx_clients_email` ON `clients` (`email`);
CREATE INDEX `idx_projects_client` ON `projects` (`client_id`);
CREATE INDEX `idx_milestones_project` ON `project_milestones` (`project_id`);
CREATE INDEX `idx_invoices_project` ON `project_invoices` (`project_id`);
CREATE INDEX `idx_messages_project` ON `project_messages` (`project_id`);

-- Automation indexes
CREATE INDEX `idx_appointments_date` ON `appointments` (`booking_date`);
CREATE INDEX `idx_quotations_num` ON `quotations` (`quote_number`);
CREATE INDEX `idx_proposals_client` ON `proposals` (`client_id`);
CREATE INDEX `idx_redirects_source` ON `redirects` (`source_url`);
