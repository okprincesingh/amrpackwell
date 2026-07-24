<?php
// php-admin/director-data.php
// Public read-only endpoint the About Us page uses to render the directors grid.
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');
// Prevent the endpoint from being cached by browsers/proxies (avoids stale/redirected responses)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$stmt = db()->query("SELECT name, designation, badge, phone, photo
                      FROM directors
                      WHERE status = 'active'
                      ORDER BY sort_order ASC, name ASC");
$rows = $stmt->fetchAll();

$directors = array_map(function ($d) {
    return [
        'name'        => $d['name'],
        'designation' => $d['designation'],
        'badge'       => $d['badge'],
        'phone'       => $d['phone'],
        'photo_url'   => $d['photo'] ? UPLOAD_URL . '/' . $d['photo'] : '',
    ];
}, $rows);

echo json_encode($directors, JSON_UNESCAPED_SLASHES);