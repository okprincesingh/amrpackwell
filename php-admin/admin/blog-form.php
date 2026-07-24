<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$id       = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit   = $id !== null;
$blog     = [
    'title' => '', 'slug' => '', 'canonical' => '', 'category' => '', 'featured_image' => '',
    'excerpt' => '', 'content' => '', 'posted_by' => 'Admin',
    'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '',
    'status' => 'published', 'published_at' => date('Y-m-d'),
];
$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM blogs WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header('Location: blogs.php');
        exit;
    }
    $blog = $existing;
}

$categoryOptions = db()->query("SELECT DISTINCT TRIM(name) AS product_name FROM products WHERE TRIM(name) <> '' ORDER BY product_name ASC")
                        ->fetchAll(PDO::FETCH_COLUMN);
if (!empty($blog['category']) && !in_array($blog['category'], $categoryOptions, true)) {
    $categoryOptions[] = $blog['category'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $blog['title']             = trim($_POST['title'] ?? '');
    $blog['slug']               = trim($_POST['slug'] ?? '');
    $blog['canonical']         = trim($_POST['canonical'] ?? '');
    $blog['category']          = trim($_POST['category'] ?? '');
    $blog['excerpt']           = trim($_POST['excerpt'] ?? '');
    $blog['content']           = $_POST['content'] ?? '';           // HTML from Tiptap
    $blog['posted_by']         = trim($_POST['posted_by'] ?? 'Admin');
    $blog['meta_title']        = trim($_POST['meta_title'] ?? '');
    $blog['meta_description']  = trim($_POST['meta_description'] ?? '');
    $blog['meta_keywords']     = trim($_POST['meta_keywords'] ?? '');
    $blog['status']            = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
    $blog['published_at']      = $_POST['published_at'] ?: date('Y-m-d');

    if ($blog['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if ($blog['content'] === '' || $blog['content'] === '<p></p>') {
        $errors[] = 'Blog content cannot be empty.';
    }

    // ---- Featured image upload (optional on edit, required on new) ----
    $featuredImage = $blog['featured_image'] ?? '';
    if (!empty($_FILES['featured_image']['name'])) {
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $file    = $_FILES['featured_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed. Please try again.';
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Featured image must be under 3MB.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = 'Featured image must be a PNG, JPG or WEBP file.';
            } else {
                $destDir = UPLOAD_DIR . '/blog';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $filename = 'blog-' . time() . '-' . rand(100, 999) . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
                    $featuredImage = 'blog/' . $filename;
                } else {
                    $errors[] = 'Could not save the uploaded image.';
                }
            }
        }
    } elseif (!$isEdit) {
        $errors[] = 'Please upload a featured image.';
    }

    if (empty($errors)) {
        $blog['featured_image'] = $featuredImage;

        // Work out the slug independently of the title:
        // - if the admin typed a slug, slugify + uniquify that
        // - if left blank, fall back to slugifying the title (only really hit on create)
        $requestedSlug = $blog['slug'] !== '' ? slugify($blog['slug']) : slugify($blog['title']);

        if ($isEdit) {
            $blog['slug'] = unique_blog_slug($requestedSlug, $id);

            $sql = 'UPDATE blogs SET
                        title = :title, slug = :slug, canonical = :canonical, category = :category, featured_image = :featured_image,
                        excerpt = :excerpt, content = :content, posted_by = :posted_by,
                        meta_title = :meta_title, meta_description = :meta_description,
                        meta_keywords = :meta_keywords, status = :status, published_at = :published_at
                    WHERE id = :id';
            $params = $blog;
            $params['id'] = $id;
            unset($params['created_at'], $params['updated_at']);
            db()->prepare($sql)->execute($params);
        } else {
            $blog['slug'] = unique_blog_slug($requestedSlug);

            $sql = 'INSERT INTO blogs
                        (title, slug, canonical, category, featured_image, excerpt, content, posted_by,
                         meta_title, meta_description, meta_keywords, status, published_at)
                    VALUES
                        (:title, :slug, :canonical, :category, :featured_image, :excerpt, :content, :posted_by,
                         :meta_title, :meta_description, :meta_keywords, :status, :published_at)';
            db()->prepare($sql)->execute($blog);
        }

        header('Location: blogs.php?saved=1');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Blog' : 'Add New Blog';
$active    = 'blogs';
require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Title</label>
            <input type="text" name="title" value="<?= e($blog['title']) ?>" required>
        </div>

        <div>
            <label>Slug URL</label>
            <input type="text" name="slug" value="<?= e($blog['slug']) ?>" placeholder="auto-generated-from-title-if-left-blank">
            <small>Changing this changes the blog's live URL (<?= e(rtrim('https://www.amrpackwell.com/blog/', '/')) ?>/<em>slug</em>). Leave blank on a new post to auto-generate from the title.</small>
        </div>

        <div>
            <label>Canonical URL</label>
            <input type="text" name="canonical" value="<?= e($blog['canonical']) ?>" placeholder="https://www.amrpackwell.com/blog/your-post-slug">
        </div>

        <div class="form-row">
            <div>
                <label>Category</label>
                <select name="category" required>
                    <option value="" <?= $blog['category'] === '' ? 'selected' : '' ?>>Select category</option>
                    <?php foreach ($categoryOptions as $catName): ?>
                        <option value="<?= e($catName) ?>" <?= $blog['category'] === $catName ? 'selected' : '' ?>><?= e($catName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Posted By</label>
                <input type="text" name="posted_by" value="<?= e($blog['posted_by']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="published" <?= $blog['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $blog['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <div>
                <label>Published Date</label>
                <input type="date" name="published_at" value="<?= e($blog['published_at']) ?>">
            </div>
        </div>

        <div>
            <label>Featured Image <?= $isEdit ? '(leave empty to keep current)' : '' ?></label>
            <?php if (!empty($blog['featured_image'])): ?>
                <div class="logo-preview" id="featured-image-current">
                    <img src="<?= e(UPLOAD_URL . '/' . $blog['featured_image']) ?>" alt="Current image" height="120" style="max-width: 100%; object-fit: cover; border-radius: 8px;">
                </div>
            <?php endif; ?>
            <div class="logo-preview" id="featured-image-preview-wrapper" style="display:none;">
                <img id="featured-image-preview" alt="Selected image preview" height="120" style="max-width: 100%; object-fit: cover; border-radius: 8px;">
            </div>
            <input type="file" name="featured_image" id="featured-image-input" accept=".png,.jpg,.jpeg,.webp">
            <small>PNG/JPG/WEBP, max 3MB. Recommended size: 800x500px.</small>
        </div>

        <div>
            <label>Excerpt (short summary shown on the blog listing card)</label>
            <textarea name="excerpt" rows="2" maxlength="500"><?= e($blog['excerpt']) ?></textarea>
        </div>

        <div>
            <label>Blog Content</label>
            <div class="tiptap-wrapper">
                <div class="tiptap-toolbar" id="tiptap-toolbar">
                    <button type="button" data-action="undo" title="Undo">↺</button>
                    <button type="button" data-action="redo" title="Redo">↻</button>
                    <span class="tt-sep"></span>
                    <button type="button" data-action="h2" title="Heading 2"><b>H2</b></button>
                    <button type="button" data-action="h3" title="Heading 3"><b>H3</b></button>
                    <span class="tt-sep"></span>
                    <button type="button" data-action="bold" title="Bold"><b>B</b></button>
                    <button type="button" data-action="italic" title="Italic"><i>I</i></button>
                    <button type="button" data-action="underline" title="Underline"><u>U</u></button>
                    <button type="button" data-action="strike" title="Strikethrough"><s>S</s></button>
                    <span class="tt-sep"></span>
                    <button type="button" data-action="bulletList" title="Bullet list">• List</button>
                    <button type="button" data-action="orderedList" title="Numbered list">1. List</button>
                    <button type="button" data-action="blockquote" title="Quote">" Quote</button>
                    <button type="button" data-action="codeBlock" title="Code block">&lt;/&gt;</button>
                    <span class="tt-sep"></span>
                    <button type="button" data-action="link" title="Add link">🔗 Link</button>
                    <button type="button" data-action="unlink" title="Remove link">🔗✕</button>
                    <button type="button" data-action="image" title="Insert image">🖼 Image</button>
                </div>
                <div id="tiptap-editor" class="tiptap-content"></div>
                <input type="file" id="tiptap-image-input" accept="image/*" style="display:none;">
            </div>
            <!-- Real field submitted to PHP — kept in sync with the editor on every change -->
            <textarea name="content" id="content-editor" style="display:none;"><?= $blog['content'] ?></textarea>
        </div>

        <h3>SEO (optional — falls back to Title/Excerpt if left empty)</h3>
        <div>
            <label>Meta Title</label>
            <input type="text" name="meta_title" value="<?= e($blog['meta_title']) ?>">
        </div>
        <div>
            <label>Meta Description</label>
            <textarea name="meta_description" rows="2" maxlength="300"><?= e($blog['meta_description']) ?></textarea>
        </div>
        <div>
            <label>Meta Keywords (comma separated)</label>
            <input type="text" name="meta_keywords" value="<?= e($blog['meta_keywords']) ?>">
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Blog' : 'Publish Blog' ?></button>
    </form>

    <script type="module">
        import { Editor } from 'https://esm.sh/@tiptap/core@2.6.6';
        import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2.6.6';
        import Underline from 'https://esm.sh/@tiptap/extension-underline@2.6.6';
        import Link from 'https://esm.sh/@tiptap/extension-link@2.6.6';
        import Image from 'https://esm.sh/@tiptap/extension-image@2.6.6';
        import Placeholder from 'https://esm.sh/@tiptap/extension-placeholder@2.6.6';

        const hiddenTextarea = document.getElementById('content-editor');
        const toolbar         = document.getElementById('tiptap-toolbar');
        const csrfToken       = document.querySelector('input[name=csrf_token]').value;
        const featuredInput   = document.getElementById('featured-image-input');
        const featuredPreviewWrapper = document.getElementById('featured-image-preview-wrapper');
        const featuredPreview = document.getElementById('featured-image-preview');
        const featuredCurrent = document.getElementById('featured-image-current');

        if (featuredInput && featuredPreviewWrapper && featuredPreview) {
            featuredInput.addEventListener('change', () => {
                const file = featuredInput.files && featuredInput.files[0];
                if (!file) {
                    featuredPreviewWrapper.style.display = 'none';
                    featuredPreview.removeAttribute('src');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    featuredPreview.src = event.target.result;
                    featuredPreviewWrapper.style.display = 'block';
                    if (featuredCurrent) {
                        featuredCurrent.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            });

            featuredInput.addEventListener('click', () => {
                if (featuredCurrent && featuredInput.value === '') {
                    featuredCurrent.style.display = 'block';
                }
            });
        }

        const editor = new Editor({
            element: document.getElementById('tiptap-editor'),
            extensions: [
                StarterKit,
                Underline,
                Link.configure({ openOnClick: false }),
                Image,
                Placeholder.configure({ placeholder: 'Write your blog content here…' }),
            ],
            content: hiddenTextarea.value || '<p></p>',
            onUpdate: ({ editor }) => {
                hiddenTextarea.value = editor.getHTML();
            },
        });

        // Safety net: sync one more time right before the form submits
        document.querySelector('form').addEventListener('submit', () => {
            hiddenTextarea.value = editor.getHTML();
        });

        // ---- Toolbar buttons ----
        toolbar.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            e.preventDefault();

            switch (btn.dataset.action) {
                case 'bold': editor.chain().focus().toggleBold().run(); break;
                case 'italic': editor.chain().focus().toggleItalic().run(); break;
                case 'underline': editor.chain().focus().toggleUnderline().run(); break;
                case 'strike': editor.chain().focus().toggleStrike().run(); break;
                case 'h2': editor.chain().focus().toggleHeading({ level: 2 }).run(); break;
                case 'h3': editor.chain().focus().toggleHeading({ level: 3 }).run(); break;
                case 'bulletList': editor.chain().focus().toggleBulletList().run(); break;
                case 'orderedList': editor.chain().focus().toggleOrderedList().run(); break;
                case 'blockquote': editor.chain().focus().toggleBlockquote().run(); break;
                case 'codeBlock': editor.chain().focus().toggleCodeBlock().run(); break;
                case 'undo': editor.chain().focus().undo().run(); break;
                case 'redo': editor.chain().focus().redo().run(); break;
                case 'link': {
                    const url = window.prompt('Enter URL:');
                    if (url) editor.chain().focus().setLink({ href: url }).run();
                    break;
                }
                case 'unlink': editor.chain().focus().unsetLink().run(); break;
                case 'image': document.getElementById('tiptap-image-input').click(); break;
            }
            updateToolbarState();
        });

        // ---- Image upload via toolbar button ----
        document.getElementById('tiptap-image-input').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            await uploadAndInsertImage(file);
            e.target.value = '';
        });

        // ---- Drag-and-drop image support directly into the editor ----
        const editorEl = document.getElementById('tiptap-editor');
        editorEl.addEventListener('dragover', (e) => e.preventDefault());
        editorEl.addEventListener('drop', async (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;
            e.preventDefault();
            await uploadAndInsertImage(file);
        });

        async function uploadAndInsertImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('csrf_token', csrfToken);

            try {
                const res  = await fetch('blog-image-upload.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.url) {
                    editor.chain().focus().setImage({ src: data.url }).run();
                } else {
                    alert(data.error || 'Image upload failed.');
                }
            } catch (err) {
                alert('Image upload failed. Please try again.');
            }
        }

        // ---- Highlight active toolbar buttons ----
        function updateToolbarState() {
            toolbar.querySelectorAll('button[data-action]').forEach((btn) => {
                let isActive = false;
                switch (btn.dataset.action) {
                    case 'bold': isActive = editor.isActive('bold'); break;
                    case 'italic': isActive = editor.isActive('italic'); break;
                    case 'underline': isActive = editor.isActive('underline'); break;
                    case 'strike': isActive = editor.isActive('strike'); break;
                    case 'h2': isActive = editor.isActive('heading', { level: 2 }); break;
                    case 'h3': isActive = editor.isActive('heading', { level: 3 }); break;
                    case 'bulletList': isActive = editor.isActive('bulletList'); break;
                    case 'orderedList': isActive = editor.isActive('orderedList'); break;
                    case 'blockquote': isActive = editor.isActive('blockquote'); break;
                    case 'codeBlock': isActive = editor.isActive('codeBlock'); break;
                    case 'link': isActive = editor.isActive('link'); break;
                }
                btn.classList.toggle('is-active', isActive);
            });
        }

        editor.on('selectionUpdate', updateToolbarState);
        editor.on('transaction', updateToolbarState);
    </script>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>