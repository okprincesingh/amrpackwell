<?php
// php-admin/navbar-data.php
// Public read-only endpoint that the site header uses to build the
// "Our Products" dropdown. No auth required — this is site content.
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

$categories = db()->query("SELECT id, name, slug, nav_sort_order
                            FROM categories
                            WHERE status = 'active' AND show_in_navbar = 1")->fetchAll();

$standaloneProducts = db()->query("SELECT id, name, slug, nav_sort_order FROM products
                                    WHERE status = 'active' AND show_in_navbar = 1
                                      AND (category = '' OR category IS NULL)")->fetchAll();

$boxPackagingConfig = ['show' => 0, 'order' => 999];
$columnCheck = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA = DATABASE()
                                   AND TABLE_NAME = 'footer_settings'
                                   AND COLUMN_NAME IN ('show_box_packaging_link','box_packaging_nav_order')");
$columnCheck->execute();
if ((int)$columnCheck->fetchColumn() === 2) {
    $footerSettings = db()->query('SELECT show_box_packaging_link, box_packaging_nav_order FROM footer_settings WHERE id = 1')->fetch();
    if ($footerSettings) {
        $boxPackagingConfig['show'] = (int)$footerSettings['show_box_packaging_link'];
        $boxPackagingConfig['order'] = (int)$footerSettings['box_packaging_nav_order'];
    }
}

$menu = [];
foreach ($categories as $c) {
    $menu[] = [
        'type' => 'category',
        'name' => $c['name'],
        'url'  => 'product.php?category=' . rawurlencode($c['slug']),
        'sort_order' => (int)$c['nav_sort_order'],
    ];
}

foreach ($standaloneProducts as $p) {
    $menu[] = [
        'type' => 'product',
        'name' => $p['name'],
        'url'  => 'products/' . rawurlencode($p['slug']),
        'sort_order' => (int)$p['nav_sort_order'],
    ];
}

if (!empty($boxPackagingConfig['show'])) {
    $menu[] = [
        'type' => 'page',
        'name' => 'All - All type of Box Packaging Services',
        'url'  => 'box-packaging-services.html',
        'sort_order' => $boxPackagingConfig['order'],
    ];
}

usort($menu, function ($a, $b) {
    if ($a['sort_order'] === $b['sort_order']) {
        return strcasecmp($a['name'], $b['name']);
    }
    return $a['sort_order'] <=> $b['sort_order'];
});

echo json_encode($menu, JSON_UNESCAPED_SLASHES);