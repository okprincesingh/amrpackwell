<?php
// admin/enquiry-view.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'View Enquiry';
$active    = 'enquiries';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM enquiries WHERE id = ?');
$stmt->execute([$id]);
$en = $stmt->fetch();

if (!$en) {
    header('Location: enquiries.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $status = $_POST['status'] ?? 'new';
    $notes  = trim($_POST['admin_notes'] ?? '');
    $allowedStatus = ['new', 'in_progress', 'responded', 'closed'];

    if (!in_array($status, $allowedStatus, true)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        db()->prepare('UPDATE enquiries SET status = ?, admin_notes = ? WHERE id = ?')
            ->execute([$status, $notes, $id]);

        header('Location: enquiries.php?updated=1');
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
            <h2 style="margin:0;">Enquiry #<?= (int)$en['id'] ?></h2>
            <a href="enquiries.php" class="btn btn-sm">&larr; Back to list</a>
        </div>

        <table class="data-table" style="margin-bottom:20px;">
            <tr><th style="width:180px;">Enquiry Type</th><td><?= e($en['inquiry_type']) ?></td></tr>
            <tr><th>Full Name</th><td><?= e($en['full_name']) ?></td></tr>
            <tr><th>Company Name</th><td><?= e($en['company_name'] ?? '—') ?></td></tr>
            <tr><th>Mobile Number</th><td><?= e($en['mobile_number']) ?></td></tr>
            <tr><th>Email</th><td><?= e($en['email'] ?? '—') ?></td></tr>
            <tr><th>Product Category</th><td><?= e($en['product_category'] ?? '—') ?></td></tr>
            <tr><th>Quantity</th><td><?= e($en['quantity'] !== null ? $en['quantity'] . ' ' . $en['unit'] : '—') ?></td></tr>
            <tr><th>Requirement</th><td style="white-space:pre-wrap;"><?= e($en['requirement']) ?></td></tr>
            <tr><th>Submitted On</th><td><?= e($en['created_at']) ?></td></tr>
            <tr><th>IP Address</th><td><?= e($en['ip_address'] ?? '—') ?></td></tr>
        </table>
    </div>

    <form method="post" class="card form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <h3>Manage Enquiry</h3>
        <div>
            <label>Status</label>
            <select name="status">
                <?php foreach (['new' => 'New', 'in_progress' => 'In Progress', 'responded' => 'Responded', 'closed' => 'Closed'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $en['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Admin Notes</label>
            <textarea name="admin_notes" rows="4" placeholder="Internal notes about follow-up, quote sent, etc."><?= e($en['admin_notes']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>