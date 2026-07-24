<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: blogs.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    // Optional: remove the uploaded image file too
    $stmt = db()->prepare('SELECT featured_image FROM blogs WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['featured_image'])) {
        $path = UPLOAD_DIR . '/' . $row['featured_image'];
        if (is_file($path)) {
            @unlink($path);
        }
    }

    db()->prepare('DELETE FROM blogs WHERE id = ?')->execute([$id]);
}

header('Location: blogs.php?deleted=1');
exit;