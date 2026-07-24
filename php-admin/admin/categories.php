<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Categories';
$active    = 'categories';

$stmt = db()->query('SELECT id, name, image, status, sort_order,
                      (SELECT COUNT(*) FROM products WHERE products.category = categories.name) AS product_count
                      FROM categories ORDER BY sort_order ASC, name ASC');
$categories = $stmt->fetchAll();

function category_thumb_url($path) {
    if (!$path) return null;
    return UPLOAD_URL . '/' . $path;
}

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Category deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Category saved successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'in_use'): ?>
        <div class="alert alert-error">This category can't be deleted because one or more products are still using it. Move those products to a different category first.</div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">All Categories</h2>
            <a href="category-form.php" class="btn btn-primary">+ Add New Category</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th>Sort Order</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6">No categories yet. Click "Add New Category" to create your first one.</td></tr>
                <?php endif; ?>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <?php if ($thumb = category_thumb_url($c['image'])): ?>
                                <img src="<?= e($thumb) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px;">
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= e($c['name']) ?></td>
                        <td><?= (int)$c['product_count'] ?></td>
                        <td>
                            <span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                                <?= e(ucfirst($c['status'])) ?>
                            </span>
                        </td>
                        <td><?= (int)$c['sort_order'] ?></td>
                        <td class="table-actions">
                            <a href="category-form.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm">Edit</a>
                            <form method="post" action="category-delete.php" onsubmit="return confirm('Delete this category? This cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>