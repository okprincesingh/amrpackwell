<?php
// admin/sms-requests.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'SMS Requests';
$active    = 'sms-requests';

$allowedStatus = ['new', 'read', 'closed'];

// ---- Handle inline status update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'new';

    if ($id > 0 && in_array($status, $allowedStatus, true)) {
        db()->prepare('UPDATE sms_requests SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    header('Location: sms-requests.php?updated=1');
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$where  = [];
$params = [];
if ($statusFilter !== '' && in_array($statusFilter, $allowedStatus, true)) {
    $where[] = 'status = :status';
    $params['status'] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = db()->prepare("SELECT * FROM sms_requests $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$requests = $stmt->fetchAll();

$statusBadge = [
    'new'    => 'badge-blue',
    'read'   => 'badge-orange',
    'closed' => 'badge-gray',
];

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">SMS request deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Status updated.</div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">SMS Requests (<?= count($requests) ?>)</h2>
        </div>

        <form method="get" class="filter-bar">
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ($allowedStatus as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm">Filter</button>
            <?php if ($statusFilter !== ''): ?>
                <a href="sms-requests.php" class="btn btn-sm">Reset</a>
            <?php endif; ?>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6">No SMS requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= e($r['sms_name']) ?></td>
                        <td><?= e($r['sms_mobile']) ?></td>
                        <td style="max-width:300px;"><?= e($r['sms_message']) ?></td>
                        <td>
                            <span class="badge <?= $statusBadge[$r['status']] ?? 'badge-gray' ?>">
                                <?= e(ucfirst($r['status'])) ?>
                            </span>
                        </td>
                        <td><?= e($r['created_at']) ?></td>
                        <td class="table-actions">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <?php foreach ($allowedStatus as $s): ?>
                                        <option value="<?= e($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <form method="post" action="sms-delete.php" onsubmit="return confirm('Delete this SMS request?');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>