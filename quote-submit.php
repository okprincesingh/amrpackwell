<?php
// quote-submit.php (site root)
// Handles the "Get a Quote" modal (in header.html) via fetch().
// Returns JSON: { success: bool, message: string, errors?: {} }

require_once __DIR__ . '/php-admin/includes/auth.php';
require_once __DIR__ . '/php-admin/includes/helpers.php'; 
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
// ---- Honeypot (hidden input name="hp_field"; humans leave it blank) ----
if (!empty($_POST['hp_field'])) {
    json_out(['success' => true, 'message' => 'Thank you! Your quote request has been received.']);
}

if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {
    json_out(['success' => false, 'message' => 'Please complete the "I\'m not a robot" check and try again.'], 422);
}

// ---- Collect + sanitize ----
$data = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'phone'     => trim($_POST['phone'] ?? ''),
    'product'   => trim($_POST['product'] ?? ''),
    'message'   => trim($_POST['message'] ?? ''),
];

// ---- Validate ----
$errors = [];

if ($data['full_name'] === '') {
    $errors['full_name'] = 'Full name is required.';
} elseif (mb_strlen($data['full_name']) > 150) {
    $errors['full_name'] = 'Full name is too long.';
}
if ($data['phone'] === '') {
    $errors['phone'] = 'Phone number is required.';
} else {
    $digits = preg_replace('/\D+/', '', $data['phone']);
    if (strlen($digits) < 7 || strlen($digits) > 15) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
}
if ($data['product'] === '') {
    $errors['product'] = 'Please select a product.';
}

if (!empty($errors)) {
    json_out(['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors], 422);
}

// ---- Insert ----
try {
    $stmt = db()->prepare(
        'INSERT INTO quote_requests (full_name, phone, product, message, ip_address, status, created_at)
         VALUES (:full_name, :phone, :product, :message, :ip_address, :status, NOW())'
    );
    $stmt->execute([
        'full_name'  => $data['full_name'],
        'phone'      => $data['phone'],
        'product'    => $data['product'],
        'message'    => $data['message'] ?: null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'status'     => 'new',
    ]);
    $quoteId = (int)db()->lastInsertId();
} catch (Throwable $e) {
    error_log('Quote request insert failed: ' . $e->getMessage());
    json_out(['success' => false, 'message' => 'Something went wrong. Please try again in a moment.'], 500);
}

// ---- Notify admin by email (best-effort; never blocks the success response) ----
try {
    $settings = db()->query('SELECT emails FROM contact_page_settings WHERE id = 1')->fetch();
$emailList = json_decode($settings['emails'] ?? '[]', true);
$to = (is_array($emailList) && !empty($emailList[0])) ? trim($emailList[0]) : 'amrpackwell@gmail.com';

    $subject = 'New Quote Request #' . $quoteId . ' - ' . $data['product'];
    $body  = "A new quote request was submitted from the website's Get a Quote form:\r\n\r\n";
    $body .= "Name: {$data['full_name']}\r\n";
    $body .= "Phone: {$data['phone']}\r\n";
    $body .= "Product: {$data['product']}\r\n";
    $body .= "Message:\r\n" . ($data['message'] ?: '-') . "\r\n";

    $headers = "From: AMR Packwell Website <no-reply@amrpackwell.com>\r\n";
    @mail($to, $subject, $body, $headers);
} catch (Throwable $e) {
    error_log('Quote request notify email failed: ' . $e->getMessage());
}

json_out(['success' => true, 'message' => "Thanks! We've received your request and will call you back shortly."]);