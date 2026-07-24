<?php
// admin/quote-requests.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Quote Requests';
$active    = 'quote-requests';

$statusFilter  = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$allowedStatus = ['new', 'contacted', 'closed'];

$where  = [];
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatus, true)) {
    $where[] = 'status = :status';
    $params['status'] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(full_name LIKE :q OR phone LIKE :q OR product LIKE :q)';
    $params['q'] = '%' . $search . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$countStmt = db()->prepare("SELECT COUNT(*) FROM quote_requests $whereSql");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT id, full_name, phone, product, status, created_at
        FROM quote_requests $whereSql
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$quotes = $stmt->fetchAll();

function qs(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return http_build_query($params);
}

$statusBadge = [
    'new'       => 'badge-blue',
    'contacted' => 'badge-orange',
    'closed'    => 'badge-gray',
];

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Quote request deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Quote request updated.</div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">All Quote Requests (<?= (int)$total ?>)</h2>
        </div>

        <form method="get" class="filter-bar">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name, phone, product...">
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ($allowedStatus as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm">Filter</button>
            <?php if ($statusFilter !== '' || $search !== ''): ?>
                <a href="quote-requests.php" class="btn btn-sm">Reset</a>
            <?php endif; ?>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quotes)): ?>
                    <tr><td colspan="6">No quote requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td><?= e($q['full_name']) ?></td>
                        <td><?= e($q['phone']) ?></td>
                        <td><?= e($q['product']) ?></td>
                        <td>
                            <span class="badge <?= $statusBadge[$q['status']] ?? 'badge-gray' ?>">
                                <?= e(ucfirst($q['status'])) ?>
                            </span>
                        </td>
                        <td><?= e($q['created_at']) ?></td>
                        <td class="table-actions">
                            <a href="quote-request-view.php?id=<?= (int)$q['id'] ?>" class="btn btn-sm">View</a>
                            <form method="post" action="quote-request-delete.php" onsubmit="return confirm('Delete this quote request? This cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?<?= e(qs(['page' => $p])) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>