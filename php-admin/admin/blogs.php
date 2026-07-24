<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Blogs';
$active    = 'blogs';

$stmt = db()->query('SELECT id, title, category, featured_image, status, published_at, updated_at FROM blogs ORDER BY created_at DESC');
$blogs = $stmt->fetchAll();

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Blog post deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Blog post saved successfully.</div>
    <?php endif; ?>

    <div class="card">
        <div class="list-toolbar">
            <h2 style="margin:0;">All Blog Posts</h2>
            <a href="blog-form.php" class="btn btn-primary">+ Add New Blog</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th>Last Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blogs)): ?>
                    <tr><td colspan="7">No blog posts yet. Click "Add New Blog" to create your first one.</td></tr>
                <?php endif; ?>
                <?php foreach ($blogs as $b): ?>
                    <tr>
                        <td>
                            <?php if (!empty($b['featured_image'])): ?>
                                <img src="<?= e(UPLOAD_URL . '/' . $b['featured_image']) ?>" alt="Featured image" class="blog-list-thumb">
                            <?php else: ?>
                                <span class="blog-list-thumb-placeholder">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($b['title']) ?></td>
                        <td><?= e($b['category']) ?></td>
                        <td>
                            <span class="badge <?= $b['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>">
                                <?= e(ucfirst($b['status'])) ?>
                            </span>
                        </td>
                        <td><?= e($b['published_at'] ?? '—') ?></td>
                        <td><?= e($b['updated_at']) ?></td>
                        <td class="table-actions">
                            <a href="blog-form.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm">Edit</a>
                            <form method="post" action="blog-delete.php" onsubmit="return confirm('Delete this blog post? This cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>