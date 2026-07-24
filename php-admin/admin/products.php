<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Products';
$active    = 'products';

$stmt = db()->query('SELECT id, name, category, featured_image, price, status, sort_order FROM products ORDER BY sort_order ASC, name ASC');
$products = $stmt->fetchAll();

function product_thumb_url($path) {
    if (!$path) return null;
    return UPLOAD_URL . '/' . $path;
}

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Product deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Product saved successfully.</div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">All Products</h2>
            <a href="product-form.php" class="btn btn-primary">+ Add New Product</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Sort Order</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="6">No products yet. Click "Add New Product" to create your first one.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if ($thumb = product_thumb_url($p['featured_image'])): ?>
                                <img src="<?= e($thumb) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['name']) ?></td>
                        <td><?= e($p['category']) ?></td>
                        <td><?= $p['price'] !== '' ? '&#8377; ' . e($p['price']) : '—' ?></td>
                        <td>
                            <span class="badge <?= $p['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                                <?= e(ucfirst($p['status'])) ?>
                            </span>
                        </td>
                        <td><?= (int)$p['sort_order'] ?></td>
                        <td class="table-actions">
                            <a href="product-form.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm">Edit</a>
                            <form method="post" action="product-delete.php" onsubmit="return confirm('Delete this product? This cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>