<?php
// admin/enquiry-delete.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: enquiries.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    db()->prepare('DELETE FROM enquiries WHERE id = ?')->execute([$id]);
}

header('Location: enquiries.php?deleted=1');
exit;