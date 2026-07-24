<?php
// contact-submit.php  (site root)
// Handles AJAX POST from the #mainContactForm on contact-us.php
// Returns JSON: { success: bool, message: string, errors?: {} }

require_once __DIR__ . '/php-admin/includes/auth.php';   // session_start() + csrf_token()/verify_csrf()
require_once __DIR__ . '/php-admin/config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// ---- CSRF ----
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    json_out(['success' => false, 'message' => 'Your session has expired. Please refresh the page and try again.'], 403);
}

// ---- Honeypot (hidden input name="website"; humans leave it blank) ----
if (!empty($_POST['website'])) {
    json_out(['success' => true, 'message' => 'Thank you! Your enquiry has been received.']);
}

// ---- Collect + sanitize ----
$data = [
    'inquiry_type'     => trim($_POST['inquiry_type'] ?? 'General Inquiry'),
    'requirement'      => trim($_POST['requirement'] ?? ''),
    'full_name'        => trim($_POST['full_name'] ?? ''),
    'company_name'     => trim($_POST['company_name'] ?? ''),
    'email'            => trim($_POST['email'] ?? ''),
    'mobile_number'    => trim($_POST['mobile_number'] ?? ''),
    'product_category' => trim($_POST['product_category'] ?? ''),
    'quantity'         => ($_POST['quantity'] ?? '') !== '' ? (int)$_POST['quantity'] : null,
    'unit'             => trim($_POST['unit'] ?? ''),
];

// ---- Validate ----
$errors = [];

if ($data['requirement'] === '') {
    $errors['requirement'] = 'Please describe your requirement.';
}
if ($data['full_name'] === '') {
    $errors['full_name'] = 'Full name is required.';
} elseif (mb_strlen($data['full_name']) > 150) {
    $errors['full_name'] = 'Full name is too long.';
}
if ($data['mobile_number'] === '') {
    $errors['mobile_number'] = 'Mobile number is required.';
} else {
    $digits = preg_replace('/\D+/', '', $data['mobile_number']);
    if (strlen($digits) < 7 || strlen($digits) > 15) {
        $errors['mobile_number'] = 'Please enter a valid mobile number.';
    }
}
if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
$allowedTypes = ['Get Quotation', 'Get Price List', 'Discuss Requirement', 'General Inquiry'];
if (!in_array($data['inquiry_type'], $allowedTypes, true)) {
    $data['inquiry_type'] = 'General Inquiry';
}

if (!empty($errors)) {
    json_out(['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors], 422);
}

// ---- Insert ----
try {
    $stmt = db()->prepare(
        'INSERT INTO enquiries
            (inquiry_type, requirement, full_name, company_name, email, mobile_number,
             product_category, quantity, unit, ip_address)
         VALUES
            (:inquiry_type, :requirement, :full_name, :company_name, :email, :mobile_number,
             :product_category, :quantity, :unit, :ip_address)'
    );
    $stmt->execute([
        'inquiry_type'     => $data['inquiry_type'],
        'requirement'      => $data['requirement'],
        'full_name'        => $data['full_name'],
        'company_name'     => $data['company_name'] ?: null,
        'email'            => $data['email'] ?: null,
        'mobile_number'    => $data['mobile_number'],
        'product_category' => $data['product_category'] ?: null,
        'quantity'         => $data['quantity'],
        'unit'             => $data['unit'] ?: null,
        'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    $enquiryId = (int)db()->lastInsertId();
} catch (Throwable $e) {
    error_log('Enquiry insert failed: ' . $e->getMessage());
    json_out(['success' => false, 'message' => 'Something went wrong. Please try again in a moment.'], 500);
}

// ---- Notify admin by email (best-effort; never blocks the success response) ----
try {
    $settings = db()->query('SELECT emails FROM contact_page_settings WHERE id = 1')->fetch();
$emailList = json_decode($settings['emails'] ?? '[]', true);
$to = (is_array($emailList) && !empty($emailList[0])) ? trim($emailList[0]) : 'amrpackwell@gmail.com';

    $subject = 'New Website Enquiry #' . $enquiryId . ' - ' . $data['inquiry_type'];
    $body  = "A new enquiry was submitted on the website:\r\n\r\n";
    $body .= "Type: {$data['inquiry_type']}\r\n";
    $body .= "Name: {$data['full_name']}\r\n";
    $body .= "Company: " . ($data['company_name'] ?: '-') . "\r\n";
    $body .= "Mobile: {$data['mobile_number']}\r\n";
    $body .= "Email: " . ($data['email'] ?: '-') . "\r\n";
    $body .= "Product Category: " . ($data['product_category'] ?: '-') . "\r\n";
    $body .= "Quantity: " . ($data['quantity'] ?: '-') . " " . ($data['unit'] ?: '') . "\r\n";
    $body .= "Requirement:\r\n{$data['requirement']}\r\n";

    $headers = "From: AMR Packwell Website <no-reply@amrpackwell.com>\r\n";
    if (!empty($data['email'])) {
        $headers .= "Reply-To: {$data['email']}\r\n";
    }

    @mail($to, $subject, $body, $headers);
} catch (Throwable $e) {
    error_log('Enquiry notify email failed: ' . $e->getMessage());
}

json_out(['success' => true, 'message' => "Your enquiry has been sent successfully! We'll get back to you within 24 hours."]);