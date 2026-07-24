<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('SELECT name, image FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if ($category) {
        $countStmt = db()->prepare('SELECT COUNT(*) AS c FROM products WHERE category = ?');
        $countStmt->execute([$category['name']]);
        $inUse = (int)$countStmt->fetch()['c'];

        if ($inUse > 0) {
            header('Location: categories.php?error=in_use');
            exit;
        }

        if (!empty($category['image'])) {
            $path = UPLOAD_DIR . '/' . $category['image'];
            if (is_file($path)) unlink($path);
        }

        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }
}

header('Location: categories.php?deleted=1');
exit;