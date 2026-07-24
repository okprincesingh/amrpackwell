<?php
// admin/enquiries.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Enquiries';
$active    = 'enquiries';

// ---- Filters ----
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$allowedStatus = ['new', 'in_progress', 'responded', 'closed'];

$where  = [];
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatus, true)) {
    $where[] = 'status = :status';
    $params['status'] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(full_name LIKE :q OR mobile_number LIKE :q OR email LIKE :q OR company_name LIKE :q)';
    $params['q'] = '%' . $search . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Pagination ----
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$countStmt = db()->prepare("SELECT COUNT(*) FROM enquiries $whereSql");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT id, inquiry_type, full_name, mobile_number, email, product_category, status, created_at
        FROM enquiries $whereSql
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

// Helper to keep existing query params when building pagination/filter links
function qs(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return http_build_query($params);
}

$statusBadge = [
    'new'         => 'badge-blue',
    'in_progress' => 'badge-orange',
    'responded'   => 'badge-green',
    'closed'      => 'badge-gray',
];

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Enquiry deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Enquiry updated.</div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">All Enquiries (<?= (int)$total ?>)</h2>
        </div>

        <form method="get" class="filter-bar">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name, mobile, email, company...">
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ($allowedStatus as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm">Filter</button>
            <?php if ($statusFilter !== '' || $search !== ''): ?>
                <a href="enquiries.php" class="btn btn-sm">Reset</a>
            <?php endif; ?>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Enquiry Type</th>
                    <th>Product Category</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enquiries)): ?>
                    <tr><td colspan="7">No enquiries found.</td></tr>
                <?php endif; ?>
                <?php foreach ($enquiries as $en): ?>
                    <tr>
                        <td><?= e($en['full_name']) ?></td>
                        <td><?= e($en['mobile_number']) ?></td>
                        <td><?= e($en['inquiry_type']) ?></td>
                        <td><?= e($en['product_category'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $statusBadge[$en['status']] ?? 'badge-gray' ?>">
                                <?= e(ucfirst(str_replace('_', ' ', $en['status']))) ?>
                            </span>
                        </td>
                        <td><?= e($en['created_at']) ?></td>
                        <td class="table-actions">
                            <a href="enquiry-view.php?id=<?= (int)$en['id'] ?>" class="btn btn-sm">View</a>
                            <form method="post" action="enquiry-delete.php" onsubmit="return confirm('Delete this enquiry? This cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$en['id'] ?>">
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