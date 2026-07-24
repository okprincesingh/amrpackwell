<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'About Us / Directors';
$active    = 'directors';

$stmt = db()->query('SELECT id, name, designation, badge, photo, status, sort_order FROM directors ORDER BY sort_order ASC, name ASC');
$directors = $stmt->fetchAll();

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Director removed.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Director saved successfully.</div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">Directors / Team</h2>
            <a href="director-form.php" class="btn btn-primary">+ Add Director</a>
        </div>
        <p style="color:var(--muted); font-size:13px; margin-top:-8px;">
            Shown in the "Meet Our Directors" section on the About Us page, in Sort Order.
        </p>

        <table class="data-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Badge</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($directors)): ?>
                    <tr><td colspan="7">No directors added yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($directors as $d): ?>
                    <tr>
                        <td>
                            <?php if (!empty($d['photo'])): ?>
                                <img src="<?= e(UPLOAD_URL . '/' . $d['photo']) ?>" alt="" height="40" style="border-radius:6px; object-fit:cover; width:40px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($d['name']) ?></td>
                        <td><?= e($d['designation']) ?></td>
                        <td><?= e($d['badge']) ?></td>
                        <td><?= (int)$d['sort_order'] ?></td>
                        <td>
                            <span class="badge <?= $d['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                                <?= e(ucfirst($d['status'])) ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="director-form.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm">Edit</a>
                            <form method="post" action="director-delete.php" onsubmit="return confirm('Remove this director?');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>