<?php
// admin/navbar.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Navbar';
$active    = 'navbar';

// ---- Load current categories & products (used for both GET display and POST id validation) ----
$categories = db()->query('SELECT id, name, status, show_in_navbar, nav_sort_order
                            FROM categories ORDER BY nav_sort_order ASC, name ASC')->fetchAll();

$productsFlat = db()->query('SELECT id, name, category, status, show_in_navbar, nav_sort_order
                              FROM products ORDER BY category ASC, nav_sort_order ASC, name ASC')->fetchAll();

$boxPackagingConfig = ['show' => 0, 'order' => 999];
$columnCheck = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'footer_settings' AND COLUMN_NAME IN ('show_box_packaging_link','box_packaging_nav_order')");
$columnCheck->execute();
if ((int)$columnCheck->fetchColumn() === 2) {
    $footerSettings = db()->query('SELECT show_box_packaging_link, box_packaging_nav_order FROM footer_settings WHERE id = 1')->fetch();
    if ($footerSettings) {
        $boxPackagingConfig['show'] = (int)$footerSettings['show_box_packaging_link'];
        $boxPackagingConfig['order'] = (int)$footerSettings['box_packaging_nav_order'];
    }
}

// Group products by category name for display
$productsByCategory = [];
foreach ($productsFlat as $p) {
    $productsByCategory[$p['category']][] = $p;
}

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $catShow  = $_POST['cat_show']  ?? [];   // [id => '1']
    $catOrder = $_POST['cat_order'] ?? [];   // [id => '3']
    $prodShow  = $_POST['prod_show']  ?? [];
    $prodOrder = $_POST['prod_order'] ?? [];

    $pdo = db();
    $pdo->beginTransaction();

    $catStmt = $pdo->prepare('UPDATE categories SET show_in_navbar = :show, nav_sort_order = :order WHERE id = :id');
    foreach ($categories as $c) {
        $id = $c['id'];
        $catStmt->execute([
            'show'  => isset($catShow[$id]) ? 1 : 0,
            'order' => (int)($catOrder[$id] ?? 0),
            'id'    => $id,
        ]);
    }

    $prodStmt = $pdo->prepare('UPDATE products SET show_in_navbar = :show, nav_sort_order = :order WHERE id = :id');
    foreach ($productsFlat as $p) {
        $id = $p['id'];
        $prodStmt->execute([
            'show'  => isset($prodShow[$id]) ? 1 : 0,
            'order' => (int)($prodOrder[$id] ?? 0),
            'id'    => $id,
        ]);
    }

    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'footer_settings' AND COLUMN_NAME IN ('show_box_packaging_link','box_packaging_nav_order')");
    $columnCheck->execute();
    if ((int)$columnCheck->fetchColumn() === 2) {
        $settingsStmt = $pdo->prepare('UPDATE footer_settings SET show_box_packaging_link = :show, box_packaging_nav_order = :order WHERE id = 1');
        $settingsStmt->execute([
            'show'  => isset($_POST['box_packaging_show']) ? 1 : 0,
            'order' => (int)($_POST['box_packaging_order'] ?? 999),
        ]);
    }

    $pdo->commit();

    header('Location: navbar.php?saved=1');
    exit;
}

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Navbar settings saved. Changes go live immediately on the site.</div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="card">
            <div class="list-toolbar">
                <h2 style="margin:0;">Categories in "Our Products" Menu</h2>
            </div>
            <p style="color:var(--muted); font-size:13px; margin-top:-8px;">
                Controls which categories appear in the navbar dropdown, and in what order (lower number = shown first).
            </p>

            <table class="data-table nav-manage-table">
                <thead>
                    <tr>
                        <th style="width:70px;">Show</th>
                        <th style="width:90px;">Order</th>
                        <th>Category</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="4">No categories yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="cat_show[<?= (int)$c['id'] ?>]" value="1"
                                       <?= $c['show_in_navbar'] ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <input type="number" name="cat_order[<?= (int)$c['id'] ?>]"
                                       value="<?= (int)$c['nav_sort_order'] ?>" class="nav-order-input">
                            </td>
                            <td><?= e($c['name']) ?></td>
                            <td>
                                <span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                                    <?= e(ucfirst($c['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="list-toolbar">
                <h2 style="margin:0;">Special Navbar Link</h2>
            </div>
            <p style="color:var(--muted); font-size:13px; margin-top:-8px;">
                Enable and order the special Box Packaging Services page link in the navbar dropdown.
            </p>
            <table class="data-table nav-manage-table">
                <thead>
                    <tr>
                        <th style="width:70px;">Show</th>
                        <th style="width:90px;">Order</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="checkbox" name="box_packaging_show" value="1"
                                   <?= $boxPackagingConfig['show'] ? 'checked' : '' ?>>
                        </td>
                        <td>
                            <input type="number" name="box_packaging_order"
                                   value="<?= (int)$boxPackagingConfig['order'] ?>" class="nav-order-input">
                        </td>
                        <td>All type of Box Packaging Services</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="list-toolbar">
                <h2 style="margin:0;">Products in "Our Products" Menu</h2>
            </div>
            <p style="color:var(--muted); font-size:13px; margin-top:-8px;">
                Pick which individual products show as sub-links under each category in the dropdown, and their order. Standalone products with no category appear separately.
            </p>

            <?php
                $uncategorizedProducts = $productsByCategory[''] ?? [];
                if (isset($productsByCategory[''])) {
                    unset($productsByCategory['']);
                }
            ?>

            <?php if (empty($productsByCategory) && empty($uncategorizedProducts)): ?>
                <p>No products yet.</p>
            <?php endif; ?>

            <?php if (!empty($uncategorizedProducts)): ?>
                <h3 class="nav-group-heading">Standalone Products</h3>
                <table class="data-table nav-manage-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">Show</th>
                            <th style="width:90px;">Order</th>
                            <th>Product</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uncategorizedProducts as $p): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="prod_show[<?= (int)$p['id'] ?>]" value="1"
                                           <?= $p['show_in_navbar'] ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="prod_order[<?= (int)$p['id'] ?>]"
                                           value="<?= (int)$p['nav_sort_order'] ?>" class="nav-order-input">
                                </td>
                                <td><?= e($p['name']) ?></td>
                                <td>
                                    <span class="badge <?= $p['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                                        <?= e(ucfirst($p['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php foreach ($productsByCategory as $catName => $prods): ?>
                <h3 class="nav-group-heading"><?= e($catName) ?></h3>
                <table class="data-table nav-manage-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">Show</th>
                            <th style="width:90px;">Order</th>
                            <th>Product</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prods as $p): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="prod_show[<?= (int)$p['id'] ?>]" value="1"
                                           <?= $p['show_in_navbar'] ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="prod_order[<?= (int)$p['id'] ?>]"
                                           value="<?= (int)$p['nav_sort_order'] ?>" class="nav-order-input">
                                </td>
                                <td><?= e($p['name']) ?></td>
                                <td>
                                    <span class="badge <?= $p['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                                        <?= e(ucfirst($p['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">Save Navbar Settings</button>
    </form>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>