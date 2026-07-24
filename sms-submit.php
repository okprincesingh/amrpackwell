<?php
// sms-submit.php  (site root)
// Handles AJAX POST from the "Send SMS" box on contact-us.php

require_once __DIR__ . '/php-admin/includes/auth.php';
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

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    json_out(['success' => false, 'message' => 'Your session has expired. Please refresh the page and try again.'], 403);
}

if (!empty($_POST['website'])) { // honeypot
    json_out(['success' => true, 'message' => 'Your message has been sent!']);
}

$name    = trim($_POST['sms_name'] ?? '');
$mobile  = trim($_POST['sms_mobile'] ?? '');
$message = trim($_POST['sms_message'] ?? '');

$errors = [];
if ($name === '') {
    $errors['sms_name'] = 'Please enter your name.';
}
if ($mobile === '') {
    $errors['sms_mobile'] = 'Please enter your mobile number.';
} else {
    $digits = preg_replace('/\D+/', '', $mobile);
    if (strlen($digits) < 7 || strlen($digits) > 15) {
        $errors['sms_mobile'] = 'Please enter a valid mobile number.';
    }
}
if ($message === '') {
    $errors['sms_message'] = 'Please enter a message.';
}

if (!empty($errors)) {
    json_out(['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors], 422);
}

try {
    $stmt = db()->prepare(
        'INSERT INTO sms_requests (sms_name, sms_mobile, sms_message, ip_address) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, $mobile, $message, $_SERVER['REMOTE_ADDR'] ?? null]);
} catch (Throwable $e) {
    error_log('SMS request insert failed: ' . $e->getMessage());
    json_out(['success' => false, 'message' => 'Something went wrong. Please try again in a moment.'], 500);
}

try {
    $settings = db()->query('SELECT emails FROM contact_page_settings WHERE id = 1')->fetch();
$emailList = json_decode($settings['emails'] ?? '[]', true);
$to = (is_array($emailList) && !empty($emailList[0])) ? trim($emailList[0]) : 'amrpackwell@gmail.com';
    $subject = 'New SMS Request from Website';
    $body = "Name: {$name}\r\nMobile: {$mobile}\r\nMessage: {$message}\r\n";
    @mail($to, $subject, $body, "From: AMR Packwell Website <no-reply@amrpackwell.com>\r\n");
} catch (Throwable $e) {
    error_log('SMS notify email failed: ' . $e->getMessage());
}

json_out(['success' => true, 'message' => 'Your message has been sent! Our team will contact you shortly.']);