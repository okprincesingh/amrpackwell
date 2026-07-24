<?php
// admin/quote-request-view.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'View Quote Request';
$active    = 'quote-requests';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM quote_requests WHERE id = ?');
$stmt->execute([$id]);
$q = $stmt->fetch();

if (!$q) {
    header('Location: quote-requests.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $status = $_POST['status'] ?? 'new';
    $notes  = trim($_POST['admin_notes'] ?? '');
    $allowedStatus = ['new', 'contacted', 'closed'];

    if (!in_array($status, $allowedStatus, true)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        db()->prepare('UPDATE quote_requests SET status = ?, admin_notes = ? WHERE id = ?')
            ->execute([$status, $notes, $id]);

        header('Location: quote-requests.php?updated=1');
        exit;
    }
}

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">Quote Request #<?= (int)$q['id'] ?></h2>
            <a href="quote-requests.php" class="btn btn-sm">&larr; Back to list</a>
        </div>

        <table class="data-table" style="margin-bottom:20px;">
            <tr><th style="width:180px;">Full Name</th><td><?= e($q['full_name']) ?></td></tr>
            <tr><th>Phone</th><td><?= e($q['phone']) ?></td></tr>
            <tr><th>Product</th><td><?= e($q['product']) ?></td></tr>
            <tr><th>Message</th><td style="white-space:pre-wrap;"><?= e($q['message'] ?? '—') ?></td></tr>
            <tr><th>Submitted On</th><td><?= e($q['created_at']) ?></td></tr>
            <tr><th>IP Address</th><td><?= e($q['ip_address'] ?? '—') ?></td></tr>
        </table>
    </div>

    <form method="post" class="card form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <h3>Manage Quote Request</h3>
        <div>
            <label>Status</label>
            <select name="status">
                <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $q['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Admin Notes</label>
            <textarea name="admin_notes" rows="4" placeholder="Internal notes about follow-up, quote sent, etc."><?= e($q['admin_notes']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>