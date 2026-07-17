<?php
/**
 * WebFalx Appointment Booking System
 * Allows visitors to request Zoom/Google Meet video syncs or office consultations.
 */

require_once __DIR__ . '/includes/functions.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf_token();
        
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $company = sanitize_input($_POST['company'] ?? '');
        $service = sanitize_input($_POST['service'] ?? 'General Consultation');
        
        $date = sanitize_input($_POST['booking_date'] ?? '');
        $time = sanitize_input($_POST['booking_time'] ?? '');
        $type = sanitize_input($_POST['meeting_type'] ?? 'Google Meet');
        $notes = sanitize_input($_POST['notes'] ?? '');

        if (empty($name) || empty($email) || empty($date) || empty($time)) {
            throw new Exception("Please fill out all required fields marked with *.");
        }

        if ($db === null) {
            throw new Exception("Database is offline.");
        }

        // Insert into Appointments table
        $stmt = $db->prepare("INSERT INTO appointments (name, email, phone, service, booking_date, booking_time, meeting_type, status, notes) VALUES (:name, :email, :phone, :serv, :date, :time, :type, 'Pending', :notes)");
        $stmt->execute([
            'name' => $name, 'email' => $email, 'phone' => $phone, 'serv' => $service,
            'date' => $date, 'time' => $time, 'type' => $type, 'notes' => $notes
        ]);

        // Log also as Lead
        $lead_id = 'WF-' . date('Ymd') . '-' . rand(1000, 9999);
        $lead_msg = "Requested appointment slot: " . $date . " at " . $time . " via " . $type . ". Note: " . $notes;
        $stmt = $db->prepare("INSERT INTO leads (lead_id, lead_type, full_name, phone, email, company_name, service_interested, message, status) VALUES (:lid, 'inquiry', :name, :phone, :email, :comp, :serv, :msg, 'New')");
        $stmt->execute([
            'lid' => $lead_id, 'name' => $name, 'phone' => $phone, 'email' => $email, 'comp' => $company, 'serv' => $service, 'msg' => $lead_msg
        ]);

        flash_message('booking_flash', 'Your appointment request has been submitted. We will email confirmation within 1 hour.', 'success');
        header('Location: ' . BASE_URL . 'book-appointment.php');
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$flash = flash_message('booking_flash');
if ($flash) {
    $success_message = $flash['message'];
}

$page_seo = [
    'title' => 'Schedule a Consultation | WebFalx',
    'description' => 'Book a live phone, Zoom, or Google Meet video sync with a senior technical developer.'
];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Appointment banner -->
<section class="section booking-hero" style="padding: var(--spacing-lg) 0 var(--spacing-xs) 0; background: linear-gradient(180deg, #090e1a 0%, var(--color-bg-dark) 100%);">
    <div class="container reveal" style="text-align: center;">
        <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-accent); letter-spacing: 2px; text-transform: uppercase;">COLLABORATION</span>
        <h1 style="color: #ffffff; font-size: 2.5rem; margin-top: 0.5rem;">Schedule A Consultation</h1>
        <p style="max-width: 600px; margin: 10px auto 0 auto;">Select date coordinates to coordinate a project scoping call with our engineering team.</p>
    </div>
</section>

<section class="section booking-body" style="padding-top: var(--spacing-xs);">
    <div class="container reveal">
        
        <div class="glass-card" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success fade-in"><?php echo esc($success_message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger fade-in"><?php echo esc($error_message); ?></div>
            <?php endif; ?>

            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr(get_csrf_token()); ?>">

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="bk_name">Full Name *</label>
                        <input type="text" name="name" id="bk_name" class="form-control" required placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label for="bk_email">Email address *</label>
                        <input type="email" name="email" id="bk_email" class="form-control" required placeholder="john@company.com">
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="bk_phone">Phone Number</label>
                        <input type="text" name="phone" id="bk_phone" class="form-control" placeholder="6265551212">
                    </div>
                    <div class="form-group">
                        <label for="bk_comp">Company Name</label>
                        <input type="text" name="company" id="bk_comp" class="form-control" placeholder="Acme Inc">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bk_service">Select Service Interest</label>
                    <select name="service" id="bk_service" class="form-control" style="background: var(--color-bg-dark);">
                        <option value="Shopify Development">Shopify Development</option>
                        <option value="WordPress Theme">WordPress Theme Development</option>
                        <option value="Custom Database">Custom CRM / Automation</option>
                        <option value="Technical SEO">Technical SEO Auditing</option>
                    </select>
                </div>

                <div class="grid grid-3" style="gap: 10px;">
                    <div class="form-group">
                        <label for="bk_date">Preferred Date *</label>
                        <input type="date" name="booking_date" id="bk_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="bk_time">Preferred Time *</label>
                        <input type="time" name="booking_time" id="bk_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="bk_type">Meeting Platform</label>
                        <select name="meeting_type" id="bk_type" class="form-control" style="background: var(--color-bg-dark);">
                            <option value="Google Meet">Google Meet</option>
                            <option value="Zoom">Zoom</option>
                            <option value="Phone Call">Phone Call</option>
                            <option value="Office Visit">Office Visit</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="bk_notes">Brief Project scope notes</label>
                    <textarea name="notes" id="bk_notes" rows="3" class="form-control" placeholder="Tell us about your project requirements..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Book Scoping Call</button>
            </form>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
