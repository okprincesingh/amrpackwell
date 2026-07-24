<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    // Delete the featured image file
    $stmt = db()->prepare('SELECT featured_image FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if ($product = $stmt->fetch()) {
        if (!empty($product['featured_image'])) {
            $path = UPLOAD_DIR . '/' . $product['featured_image'];
            if (is_file($path)) unlink($path);
        }
    }

    // Delete gallery image files
    $gStmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = ?');
    $gStmt->execute([$id]);
    foreach ($gStmt->fetchAll() as $img) {
        $path = UPLOAD_DIR . '/' . $img['image_path'];
        if (is_file($path)) unlink($path);
    }

    // Deleting the product row also removes product_images rows automatically (ON DELETE CASCADE)
    db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
}

header('Location: products.php?deleted=1');
exit;