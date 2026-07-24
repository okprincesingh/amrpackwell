<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

verify_csrf($_POST['csrf_token'] ?? null);

if (empty($_FILES['image']['name'])) {
    echo json_encode(['error' => 'No image received.']);
    exit;
}

$file    = $_FILES['image'];
$allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload failed.']);
    exit;
}
if ($file['size'] > 4 * 1024 * 1024) {
    echo json_encode(['error' => 'Image must be under 4MB.']);
    exit;
}

$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    echo json_encode(['error' => 'Only PNG, JPG, WEBP or GIF images are allowed.']);
    exit;
}

$destDir = UPLOAD_DIR . '/product/content';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

$filename = 'img-' . time() . '-' . rand(1000, 9999) . '.' . $allowed[$mime];

if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
    echo json_encode(['error' => 'Could not save the image.']);
    exit;
}

echo json_encode(['url' => UPLOAD_URL . '/product/content/' . $filename]);