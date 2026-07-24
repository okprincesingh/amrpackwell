<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

$category = [
    'name' => '', 'slug' => '', 'canonical' => '', 'description' => '', 'image' => '',
    'status' => 'active', 'sort_order' => 0,
];
$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header('Location: categories.php');
        exit;
    }
    $category = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $category['name']        = trim($_POST['name'] ?? '');
    $category['canonical']   = trim($_POST['canonical'] ?? '');
    $category['description'] = trim($_POST['description'] ?? '');
    $category['status']      = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $category['sort_order']  = (int)($_POST['sort_order'] ?? 0);

    if ($category['name'] === '') {
        $errors[] = 'Category name is required.';
    } else {
        // Make sure the name isn't already used by another category
        $dupSql = 'SELECT id FROM categories WHERE name = ?' . ($isEdit ? ' AND id != ?' : '');
        $dupParams = $isEdit ? [$category['name'], $id] : [$category['name']];
        $dupStmt = db()->prepare($dupSql);
        $dupStmt->execute($dupParams);
        if ($dupStmt->fetch()) {
            $errors[] = 'A category with this name already exists.';
        }
    }

    // ---- Image upload (optional) ----
    $image = $category['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $file    = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed. Please try again.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Category image must be under 2MB.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = 'Image must be a PNG, JPG or WEBP file.';
            } else {
                $destDir = UPLOAD_DIR . '/category';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $filename = 'category-' . time() . '-' . rand(100, 999) . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
                    $image = 'category/' . $filename;
                } else {
                    $errors[] = 'Could not save the uploaded image.';
                }
            }
        }
    }

    if (empty($errors)) {
        $category['image'] = $image;

        if ($isEdit) {
            $oldNameStmt = db()->prepare('SELECT name FROM categories WHERE id = ?');
            $oldNameStmt->execute([$id]);
            $oldName = $oldNameStmt->fetch()['name'];

            $sql = 'UPDATE categories SET
                        name = :name, canonical = :canonical, description = :description, image = :image,
                        status = :status, sort_order = :sort_order
                    WHERE id = :id';
            $params = [
                'name'        => $category['name'],
                'canonical'   => $category['canonical'],
                'description' => $category['description'],
                'image'       => $category['image'],
                'status'      => $category['status'],
                'sort_order'  => $category['sort_order'],
                'id'          => $id,
            ];
            db()->prepare($sql)->execute($params);

            // Keep existing products in sync if the category name changed
            if ($oldName !== $category['name']) {
                db()->prepare('UPDATE products SET category = ? WHERE category = ?')
                    ->execute([$category['name'], $oldName]);
            }
        } else {
            $baseSlug = slugify($category['name']);
            $category['slug'] = unique_category_slug($baseSlug);

            $sql = 'INSERT INTO categories (name, slug, canonical, description, image, status, sort_order)
                    VALUES (:name, :slug, :canonical, :description, :image, :status, :sort_order)';
            $params = [
                'name'        => $category['name'],
                'slug'        => $category['slug'],
                'canonical'   => $category['canonical'],
                'description' => $category['description'],
                'image'       => $category['image'],
                'status'      => $category['status'],
                'sort_order'  => $category['sort_order'],
            ];
            db()->prepare($sql)->execute($params);
        }

        header('Location: categories.php?saved=1');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Category' : 'Add New Category';
$active    = 'categories';
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
            <label>Category Name</label>
            <input type="text" name="name" value="<?= e($category['name']) ?>" required>
            <?php if ($isEdit): ?>
                <small>Renaming this will automatically update all products currently using it.</small>
            <?php endif; ?>
        </div>
        <div>
            <label>Canonical URL (optional)</label>
            <input type="text" name="canonical" value="<?= e($category['canonical']) ?>" placeholder="https://www.amrpackwell.com/your-preferred-category-url">
            <small>Leave blank to auto-use <code>product.php?category=<?= e($category['slug'] ?: 'your-slug') ?></code>. Only set this if you want a different URL treated as canonical for this category.</small>
        </div>

        <div class="form-row">
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="active" <?= $category['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $category['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label>Sort Order (lower numbers show first)</label>
                <input type="number" name="sort_order" value="<?= (int)$category['sort_order'] ?>">
            </div>
        </div>

        <div>
            <label>Image (optional)</label>
            <?php if (!empty($category['image'])): ?>
                <div class="logo-preview" id="featured-image-current">
                    <img src="<?= e(UPLOAD_URL . '/' . $category['image']) ?>" alt="Current image" height="120" style="max-width: 100%; object-fit: cover; border-radius: 8px;">
                </div>
            <?php endif; ?>
            <div class="logo-preview" id="featured-image-preview-wrapper" style="display:none;">
                <img id="featured-image-preview" alt="Selected image preview" height="120" style="max-width: 100%; object-fit: cover; border-radius: 8px;">
            </div>
            <input type="file" name="image" id="featured-image-input" accept=".png,.jpg,.jpeg,.webp">
            <small>PNG/JPG/WEBP, max 2MB.</small>
        </div>

        <div>
            <label>Description (optional)</label>
            <textarea name="description" rows="3" maxlength="500"><?= e($category['description']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Category' : 'Add Category' ?></button>
    </form>
    <script>
        const featuredInput = document.getElementById('featured-image-input');
        const featuredPreviewWrapper = document.getElementById('featured-image-preview-wrapper');
        const featuredPreview = document.getElementById('featured-image-preview');
        const featuredCurrent = document.getElementById('featured-image-current');

        if (featuredInput && featuredPreviewWrapper && featuredPreview) {
            featuredInput.addEventListener('change', () => {
                const file = featuredInput.files && featuredInput.files[0];
                if (!file) {
                    featuredPreviewWrapper.style.display = 'none';
                    featuredPreview.removeAttribute('src');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    featuredPreview.src = event.target.result;
                    featuredPreviewWrapper.style.display = 'block';
                    if (featuredCurrent) {
                        featuredCurrent.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>