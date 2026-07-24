<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

$director = [
    'name' => '', 'designation' => '', 'badge' => '', 'phone' => '',
    'photo' => '', 'status' => 'active', 'sort_order' => 0,
];
$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM directors WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header('Location: directors.php');
        exit;
    }
    $director = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $director['name']        = trim($_POST['name'] ?? '');
    $director['designation'] = trim($_POST['designation'] ?? '');
    $director['badge']       = trim($_POST['badge'] ?? '');
    $director['phone']       = trim($_POST['phone'] ?? '');
    $director['status']      = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $director['sort_order']  = (int)($_POST['sort_order'] ?? 0);

    if ($director['name'] === '') {
        $errors[] = 'Name is required.';
    }
    if ($director['designation'] === '') {
        $errors[] = 'Designation is required.';
    }

    // ---- Photo upload (required on new, optional on edit) ----
    $photo = $director['photo'] ?? '';
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $file    = $_FILES['photo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Photo upload failed. Please try again.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Photo must be under 2MB.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = 'Photo must be a PNG, JPG or WEBP file.';
            } else {
                $destDir = UPLOAD_DIR . '/director';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $filename = 'director-' . time() . '-' . rand(100, 999) . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
                    $photo = 'director/' . $filename;
                } else {
                    $errors[] = 'Could not save the uploaded photo.';
                }
            }
        }
    } elseif (!$isEdit) {
        $errors[] = 'Please upload a photo.';
    }

    if (empty($errors)) {
        $director['photo'] = $photo;

        if ($isEdit) {
            $sql = 'UPDATE directors SET
                        name = :name, designation = :designation, badge = :badge, phone = :phone,
                        photo = :photo, status = :status, sort_order = :sort_order
                    WHERE id = :id';
            $params = $director;
            $params['id'] = $id;
            unset($params['created_at'], $params['updated_at']);
            db()->prepare($sql)->execute($params);
        } else {
            $sql = 'INSERT INTO directors (name, designation, badge, phone, photo, status, sort_order)
                    VALUES (:name, :designation, :badge, :phone, :photo, :status, :sort_order)';
            db()->prepare($sql)->execute($director);
        }

        header('Location: directors.php?saved=1');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Director' : 'Add New Director';
$active    = 'directors';
require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Full Name</label>
            <input type="text" name="name" value="<?= e($director['name']) ?>" placeholder="e.g. Mr. Kamesh Mandal" required>
        </div>

        <div class="form-row">
            <div>
                <label>Designation (shown under the name)</label>
                <input type="text" name="designation" value="<?= e($director['designation']) ?>" placeholder="e.g. Proprietor & Founder" required>
            </div>
            <div>
                <label>Badge (short label shown on the photo)</label>
                <input type="text" name="badge" value="<?= e($director['badge']) ?>" placeholder="e.g. Proprietor">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Phone (optional)</label>
                <input type="text" name="phone" value="<?= e($director['phone']) ?>" placeholder="e.g. +919871523344">
            </div>
            <div>
                <label>Sort Order (lower numbers show first)</label>
                <input type="number" name="sort_order" value="<?= (int)$director['sort_order'] ?>">
            </div>
        </div>

        <div>
            <label>Status</label>
            <select name="status">
                <option value="active" <?= $director['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $director['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div>
            <label>Photo <?= $isEdit ? '(leave empty to keep current)' : '' ?></label>
            <?php if (!empty($director['photo'])): ?>
                <div class="logo-preview">
                    <img src="<?= e(UPLOAD_URL . '/' . $director['photo']) ?>" alt="Current photo" height="80">
                </div>
            <?php endif; ?>
            <input type="file" name="photo" accept=".png,.jpg,.jpeg,.webp">
            <small>PNG/JPG/WEBP, max 2MB. Square photos work best.</small>
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Director' : 'Add Director' ?></button>
    </form>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>