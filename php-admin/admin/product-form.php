<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

$product = [
    'name' => '', 'slug' => '', 'canonical' => '', 'category' => '', 'short_description' => '',
    'description' => '', 'has_detail' => '0', 'featured_image' => '', 'price' => '', 'tags' => '', 'specifications' => '',
    'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '',
    'status' => 'active', 'sort_order' => 0,
];
$galleryImages = [];
$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header('Location: products.php');
        exit;
    }
    $product = $existing;
    if (!isset($product['price'])) {
        $product['price'] = '';
    }
    if (!isset($product['has_detail'])) {
        $product['has_detail'] = trim($product['description'] ?? '') !== '' ? '1' : '0';
    }

    $gStmt = db()->prepare('SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
    $gStmt->execute([$id]);
    $galleryImages = $gStmt->fetchAll();
}

// Categories for the dropdown (active ones only)
$categoryOptions = db()->query("SELECT name FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC")
                        ->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $product['name']              = trim($_POST['name'] ?? '');
    $product['canonical']         = trim($_POST['canonical'] ?? '');
    $product['category']          = trim($_POST['category'] ?? '');
    $product['short_description']  = trim($_POST['short_description'] ?? '');
    $product['description']       = $_POST['description'] ?? '';   // HTML from Tiptap
    $product['has_detail']        = isset($_POST['has_detail']) ? '1' : '0';
    $product['price']             = trim($_POST['price'] ?? '');
    $product['tags']              = trim($_POST['tags'] ?? '');
    $product['specifications']    = trim($_POST['specifications'] ?? '');
    $product['meta_title']        = trim($_POST['meta_title'] ?? '');
    $product['meta_description']  = trim($_POST['meta_description'] ?? '');
    $product['meta_keywords']     = trim($_POST['meta_keywords'] ?? '');
    $product['status']            = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $product['sort_order']        = (int)($_POST['sort_order'] ?? 0);

    if ($product['name'] === '') {
        $errors[] = 'Product name is required.';
    }

    // ---- Featured image upload (optional on edit, required on new) ----
    $featuredImage = $product['featured_image'] ?? '';
    if (!empty($_FILES['featured_image']['name'])) {
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $file    = $_FILES['featured_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Featured image upload failed. Please try again.';
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Featured image must be under 3MB.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = 'Featured image must be a PNG, JPG or WEBP file.';
            } else {
                $destDir = UPLOAD_DIR . '/product';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $filename = 'product-' . time() . '-' . rand(100, 999) . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
                    $featuredImage = 'product/' . $filename;
                } else {
                    $errors[] = 'Could not save the featured image.';
                }
            }
        }
    } elseif (!$isEdit) {
        $errors[] = 'Please upload a featured image.';
    }

    if (empty($errors)) {
        if ($product['has_detail'] !== '1') {
            $product['description'] = '';
        }
        $product['featured_image'] = $featuredImage;

        if ($isEdit) {
            // Slug stays the same on edit so existing links never break
            $sql = 'UPDATE products SET
                        name = :name, canonical = :canonical, category = :category, featured_image = :featured_image,
                        short_description = :short_description, description = :description,
                        price = :price, tags = :tags, specifications = :specifications,
                        meta_title = :meta_title, meta_description = :meta_description,
                        meta_keywords = :meta_keywords, status = :status, sort_order = :sort_order
                    WHERE id = :id';
            $params = [
                'name' => $product['name'],
                'canonical' => $product['canonical'],
                'category' => $product['category'],
                'featured_image' => $product['featured_image'],
                'short_description' => $product['short_description'],
                'description' => $product['description'],
                'price' => $product['price'],
                'tags' => $product['tags'],
                'specifications' => $product['specifications'],
                'meta_title' => $product['meta_title'],
                'meta_description' => $product['meta_description'],
                'meta_keywords' => $product['meta_keywords'],
                'status' => $product['status'],
                'sort_order' => $product['sort_order'],
                'id' => $id,
            ];
            db()->prepare($sql)->execute($params);
            $productId = $id;
        } else {
            $baseSlug = slugify($product['name']);
            $product['slug'] = unique_product_slug($baseSlug);

            $sql = 'INSERT INTO products
                        (name, slug, canonical, category, featured_image, short_description, description,
                         price, tags, specifications, meta_title, meta_description, meta_keywords, status, sort_order)
                    VALUES
                        (:name, :slug, :canonical, :category, :featured_image, :short_description, :description,
                         :price, :tags, :specifications, :meta_title, :meta_description, :meta_keywords, :status, :sort_order)';
            $params = [
                'name' => $product['name'],
                'slug' => $product['slug'],
                'canonical' => $product['canonical'],
                'category' => $product['category'],
                'featured_image' => $product['featured_image'],
                'short_description' => $product['short_description'],
                'description' => $product['description'],
                'price' => $product['price'],
                'tags' => $product['tags'],
                'specifications' => $product['specifications'],
                'meta_title' => $product['meta_title'],
                'meta_description' => $product['meta_description'],
                'meta_keywords' => $product['meta_keywords'],
                'status' => $product['status'],
                'sort_order' => $product['sort_order'],
            ];
            db()->prepare($sql)->execute($params);
            $productId = (int) db()->lastInsertId();
        }

        // ---- Remove any gallery images the admin checked for deletion ----
        if (!empty($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $imgId) {
                $imgId = (int)$imgId;
                $imgStmt = db()->prepare('SELECT image_path FROM product_images WHERE id = ? AND product_id = ?');
                $imgStmt->execute([$imgId, $productId]);
                if ($img = $imgStmt->fetch()) {
                    $filePath = UPLOAD_DIR . '/' . $img['image_path'];
                    if (is_file($filePath)) unlink($filePath);
                    db()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imgId]);
                }
            }
        }

        // ---- Add any newly uploaded gallery images ----
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
            $destDir = UPLOAD_DIR . '/product/gallery';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);

            $maxStmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) AS m FROM product_images WHERE product_id = ?');
            $maxStmt->execute([$productId]);
            $nextOrder = (int)$maxStmt->fetch()['m'] + 1;

            $count = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['gallery_images']['size'][$i] > 3 * 1024 * 1024) continue;

                $tmpName = $_FILES['gallery_images']['tmp_name'][$i];
                $mime    = mime_content_type($tmpName);
                if (!isset($allowed[$mime])) continue;

                $filename = 'gallery-' . time() . '-' . rand(1000, 9999) . '.' . $allowed[$mime];
                if (move_uploaded_file($tmpName, $destDir . '/' . $filename)) {
                    db()->prepare('INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)')
                        ->execute([$productId, 'product/gallery/' . $filename, $nextOrder]);
                    $nextOrder++;
                }
            }
        }

        header('Location: products.php?saved=1');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Product' : 'Add New Product';
$active    = 'products';
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
            <label>Product Name</label>
            <input type="text" name="name" value="<?= e($product['name']) ?>" required>
        </div>
        <div>
            <label>Canonical URL (optional)</label>
            <input type="text" name="canonical" value="<?= e($product['canonical']) ?>" placeholder="https://www.amrpackwell.com/products/your-preferred-url">
            <small>Leave blank to use the default product URL. Set this only if this product's content is duplicated elsewhere and you want search engines to treat another URL as the primary one.</small>
        </div>

        <div class="form-row">
            <div>
                <label>Category</label>
                <select name="category">
                    <option value="" <?= $product['category'] === '' ? 'selected' : '' ?>>Standalone / no category</option>
                    <?php foreach ($categoryOptions as $catName): ?>
                        <option value="<?= e($catName) ?>" <?= $product['category'] === $catName ? 'selected' : '' ?>><?= e($catName) ?></option>
                    <?php endforeach; ?>
                    <?php if ($product['category'] !== '' && !in_array($product['category'], $categoryOptions, true)): ?>
                        <option value="<?= e($product['category']) ?>" selected><?= e($product['category']) ?> (inactive)</option>
                    <?php endif; ?>
                </select>
                <small>Choose a category or leave blank for a standalone product.</small>
            </div>
            <div>
                <label>Tags (comma separated, shown as small badges)</label>
                <input type="text" name="tags" value="<?= e($product['tags']) ?>" placeholder="e.g. Kraft, Eco, Heavy Duty">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label>Sort Order (lower numbers show first)</label>
                <input type="number" name="sort_order" value="<?= (int)$product['sort_order'] ?>">
            </div>
        </div>

        <div>
            <label>Featured Image <?= $isEdit ? '(leave empty to keep current)' : '' ?></label>
            <?php if (!empty($product['featured_image'])): ?>
                <div class="logo-preview" id="featured-image-current">
                    <img src="<?= e(UPLOAD_URL . '/' . $product['featured_image']) ?>" alt="Current image" height="120" style="max-width: 100%; object-fit: cover; border-radius: 8px;">
                </div>
            <?php endif; ?>
            <div class="logo-preview" id="featured-image-preview-wrapper" style="display:none;">
                <img id="featured-image-preview" alt="Selected image preview" height="120" style="max-width: 100%; object-fit: cover; border-radius: 8px;">
            </div>
            <input type="file" name="featured_image" id="featured-image-input" accept=".png,.jpg,.jpeg,.webp">
            <small>PNG/JPG/WEBP, max 3MB. This is the main card image shown on the products page.</small>
        </div>

        <div>
            <label>Gallery Images (optional — extra photos shown on the product detail page)</label>
            <?php if (!empty($galleryImages)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:10px;">
                    <?php foreach ($galleryImages as $img): ?>
                        <label style="text-align:center;font-size:12px;">
                            <img src="<?= e(UPLOAD_URL . '/' . $img['image_path']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;display:block;margin-bottom:4px;">
                            <input type="checkbox" name="delete_images[]" value="<?= (int)$img['id'] ?>"> Remove
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <input type="file" name="gallery_images[]" accept=".png,.jpg,.jpeg,.webp" multiple="multiple" title="Hold Ctrl (Windows) or Cmd (Mac) to select multiple files">
            <small>Use Ctrl/Cmd or Shift to select multiple files. Each image must be under 3MB.</small>
        </div>

        <div>
            <label>Short Description (shown on the product listing card)</label>
            <textarea name="short_description" rows="2" maxlength="500"><?= e($product['short_description']) ?></textarea>
        </div>

        <div>
            <label>Price (shown on the product card)</label>
            <input type="text" name="price" value="<?= e($product['price']) ?>" placeholder="e.g. 13, 1250.00, 20 per piece">
            <small>Enter a price label for the product, for example ₹13 or 1250.00.</small>
        </div>

        <div class="form-row items-end">
            <div>
                <label>Full Description</label>
                <p class="form-help">Add a detail page for this product. If unchecked, the product card will show only specifications and tags.</p>
            </div>
            <div>
                <label class="inline-flex items-center gap-2 mt-1">
                    <input type="checkbox" id="has-detail" name="has_detail" value="1" <?= $product['has_detail'] === '1' ? 'checked' : '' ?> />
                    <span class="font-poppins text-sm">Has product detail page</span>
                </label>
            </div>
        </div>

        <div id="description-block" style="display: <?= $product['has_detail'] === '1' ? 'block' : 'none' ?>;">
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
                    <span class="tt-sep"></span>
                    <button type="button" data-action="link" title="Add link">🔗 Link</button>
                    <button type="button" data-action="unlink" title="Remove link">🔗✕</button>
                    <button type="button" data-action="image" title="Insert image">🖼 Image</button>
                </div>
                <div id="tiptap-editor" class="tiptap-content"></div>
                <input type="file" id="tiptap-image-input" accept="image/*" style="display:none;">
            </div>
            <textarea name="description" id="content-editor" style="display:none;"><?= $product['description'] ?></textarea>
        </div>

        <div>
            <label>Specifications (one per line, format: <code>Label: Value</code>)</label>
            <textarea name="specifications" rows="5" placeholder="Material: Kraft Paper&#10;Thickness: 2mm&#10;Load Capacity: 500kg"><?= e($product['specifications']) ?></textarea>
        </div>

        <h3>SEO (optional — falls back to Name/Short Description if left empty)</h3>
        <div>
            <label>Meta Title</label>
            <input type="text" name="meta_title" value="<?= e($product['meta_title']) ?>">
        </div>
        <div>
            <label>Meta Description</label>
            <textarea name="meta_description" rows="2" maxlength="300"><?= e($product['meta_description']) ?></textarea>
        </div>
        <div>
            <label>Meta Keywords (comma separated)</label>
            <input type="text" name="meta_keywords" value="<?= e($product['meta_keywords']) ?>">
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Product' : 'Publish Product' ?></button>
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
        }

        const editor = new Editor({
            element: document.getElementById('tiptap-editor'),
            extensions: [
                StarterKit,
                Underline,
                Link.configure({ openOnClick: false }),
                Image,
                Placeholder.configure({ placeholder: 'Write the full product description here…' }),
            ],
            content: hiddenTextarea.value || '<p></p>',
            onUpdate: ({ editor }) => {
                hiddenTextarea.value = editor.getHTML();
            },
        });

        document.querySelector('form').addEventListener('submit', () => {
            hiddenTextarea.value = editor.getHTML();
        });

        document.getElementById('has-detail').addEventListener('change', function () {
            const descriptionBlock = document.getElementById('description-block');
            if (this.checked) {
                descriptionBlock.style.display = 'block';
            } else {
                descriptionBlock.style.display = 'none';
            }
        });

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
        });

        document.getElementById('tiptap-image-input').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            await uploadAndInsertImage(file);
            e.target.value = '';
        });

        async function uploadAndInsertImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('csrf_token', csrfToken);
            try {
                const res  = await fetch('product-image-upload.php', { method: 'POST', body: formData });
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
    </script>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>