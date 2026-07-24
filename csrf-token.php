<?php
// csrf-token.php (site root)
require_once __DIR__ . '/php-admin/includes/auth.php'; // session_start() + csrf_token()

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['csrf_token' => csrf_token()]);