<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: directors.php');
    exit;
}

verify_csrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('SELECT photo FROM directors WHERE id = ?');
    $stmt->execute([$id]);
    $director = $stmt->fetch();

    if ($director) {
        if (!empty($director['photo'])) {
            $path = UPLOAD_DIR . '/' . $director['photo'];
            if (is_file($path)) unlink($path);
        }
        db()->prepare('DELETE FROM directors WHERE id = ?')->execute([$id]);
    }
}

header('Location: directors.php?deleted=1');
exit;