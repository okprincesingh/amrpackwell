<?php
// admin/sms-delete.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sms-requests.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    db()->prepare('DELETE FROM sms_requests WHERE id = ?')->execute([$id]);
}

header('Location: sms-requests.php?deleted=1');
exit;