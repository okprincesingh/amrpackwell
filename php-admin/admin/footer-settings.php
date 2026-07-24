<?php
// admin/footer-settings.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Footer Settings';
$active    = 'footer';

$errors  = [];
$success = false;

// Fetch current row (single-row table, id = 1)
$stmt = db()->prepare('SELECT * FROM footer_settings WHERE id = 1');
$stmt->execute();
$footer = $stmt->fetch();

if (!$footer) {
    // Safety net in case the seed row is missing
    db()->exec("INSERT INTO footer_settings (id) VALUES (1)");
    $footer = db()->query('SELECT * FROM footer_settings WHERE id = 1')->fetch();
}

// Decode existing JSON lists (fallback to a single empty row so the form always shows at least one input)
function fs_decode_list($json)
{
    $list = json_decode($json ?? '', true);
    if (!is_array($list) || empty(array_filter($list, fn($v) => trim((string)$v) !== ''))) {
        return [''];
    }
    return array_values($list);
}

$addressesArr = fs_decode_list($footer['addresses'] ?? null);
$phonesArr    = fs_decode_list($footer['phones'] ?? null);
$emailsArr    = fs_decode_list($footer['emails'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    // ---- Collect repeatable fields, dropping blank entries ----
    $addresses = array_values(array_filter(array_map('trim', $_POST['addresses'] ?? []), fn($v) => $v !== ''));
    $phones    = array_values(array_filter(array_map('trim', $_POST['phones'] ?? []), fn($v) => $v !== ''));
    $emails    = array_values(array_filter(array_map('trim', $_POST['emails'] ?? []), fn($v) => $v !== ''));

    $fields = [
        'company_name'    => trim($_POST['company_name'] ?? ''),
        'company_tagline' => trim($_POST['company_tagline'] ?? ''),
        'description'     => trim($_POST['description'] ?? ''),
        'whatsapp_number' => preg_replace('/\D+/', '', $_POST['whatsapp_number'] ?? ''),
        'gst_number'      => trim($_POST['gst_number'] ?? ''),
        'facebook_url'    => trim($_POST['facebook_url'] ?? ''),
        'instagram_url'   => trim($_POST['instagram_url'] ?? ''),
        'linkedin_url'    => trim($_POST['linkedin_url'] ?? ''),
        'twitter_url'     => trim($_POST['twitter_url'] ?? ''),
        'youtube_url'     => trim($_POST['youtube_url'] ?? ''),
    ];

    // ---- Validation ----
    if ($fields['company_name'] === '') {
        $errors[] = 'Company name is required.';
    }
    foreach ($emails as $idx => $emailVal) {
        if (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email #' . ($idx + 1) . ' ("' . $emailVal . '") is not a valid email address.';
        }
    }
    foreach (['facebook_url', 'instagram_url', 'linkedin_url', 'twitter_url', 'youtube_url'] as $urlField) {
        $val = $fields[$urlField];
        if ($val !== '' && $val !== '#' && !filter_var($val, FILTER_VALIDATE_URL)) {
            $errors[] = ucfirst(str_replace('_url', '', $urlField)) . ' URL is not valid.';
        }
    }

    // ---- Logo upload (optional) ----
    $logoPath = $footer['logo_path'];
    if (!empty($_FILES['logo']['name'])) {
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        $file    = $_FILES['logo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Logo upload failed. Please try again.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Logo must be under 2MB.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = 'Logo must be a PNG, JPG, WEBP or SVG image.';
            } else {
                $destDir = UPLOAD_DIR . '/logo';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $filename = 'logo-' . time() . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
                    $logoPath = 'logo/' . $filename;
                } else {
                    $errors[] = 'Could not save the uploaded logo.';
                }
            }
        }
    }

    if (empty($errors)) {
        $fields['logo_path']  = $logoPath;
        $fields['addresses']  = json_encode($addresses, JSON_UNESCAPED_UNICODE);
        $fields['phones']     = json_encode($phones, JSON_UNESCAPED_UNICODE);
        $fields['emails']     = json_encode($emails, JSON_UNESCAPED_UNICODE);

        $sql = 'UPDATE footer_settings SET
                    company_name = :company_name,
                    company_tagline = :company_tagline,
                    description = :description,
                    addresses = :addresses,
                    phones = :phones,
                    emails = :emails,
                    whatsapp_number = :whatsapp_number,
                    gst_number = :gst_number,
                    facebook_url = :facebook_url,
                    instagram_url = :instagram_url,
                    linkedin_url = :linkedin_url,
                    twitter_url = :twitter_url,
                    youtube_url = :youtube_url,
                    logo_path = :logo_path
                WHERE id = 1';

        db()->prepare($sql)->execute($fields);

        $success = true;
        $stmt = db()->prepare('SELECT * FROM footer_settings WHERE id = 1');
        $stmt->execute();
        $footer = $stmt->fetch();

        $addressesArr = fs_decode_list($footer['addresses'] ?? null);
        $phonesArr    = fs_decode_list($footer['phones'] ?? null);
        $emailsArr    = fs_decode_list($footer['emails'] ?? null);
    } else {
        // Keep whatever the user just typed so they don't lose their edits
        $addressesArr = !empty($addresses) ? $addresses : [''];
        $phonesArr    = !empty($phones) ? $phones : [''];
        $emailsArr    = !empty($emails) ? $emails : [''];
    }
}

require __DIR__ . '/../includes/layout-top.php';
?>
    <?php if ($success): ?>
        <div class="alert alert-success">Footer settings updated successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <style>
        .repeat-row { display: flex; gap: 8px; align-items: flex-start; margin-bottom: 8px; }
        .repeat-row textarea,
        .repeat-row input { flex: 1; }
        .repeat-row .btn-remove-row { flex: 0 0 auto; margin-top: 2px; }
        .repeat-add-btn { margin-top: 4px; }
    </style>

    <form method="post" enctype="multipart/form-data" class="card form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <h3>Company</h3>
        <div class="form-row">
            <div>
                <label>Company Name</label>
                <input type="text" name="company_name" value="<?= e($footer['company_name']) ?>" required>
            </div>
            <div>
                <label>Tagline</label>
                <input type="text" name="company_tagline" value="<?= e($footer['company_tagline']) ?>">
            </div>
        </div>

        <div>
            <label>Description</label>
            <textarea name="description" rows="3"><?= e($footer['description']) ?></textarea>
        </div>

        <div>
            <label>Logo</label>
            <?php if (!empty($footer['logo_path'])): ?>
                <div class="logo-preview">
                    <img src="<?= e(UPLOAD_URL . '/' . $footer['logo_path']) ?>" alt="Current logo" height="48">
                </div>
            <?php endif; ?>
            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg">
            <small>PNG/JPG/WEBP/SVG, max 2MB. Leave empty to keep current logo.</small>
        </div>

        <h3>Contact Details</h3>

        <div>
            <label>Address(es)</label>
            <div id="addresses-wrap">
                <?php foreach ($addressesArr as $addr): ?>
                <div class="repeat-row">
                    <textarea name="addresses[]" rows="2" placeholder="e.g. Plot No. 12, Industrial Area, Delhi"><?= e($addr) ?></textarea>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm repeat-add-btn" data-target="addresses-wrap" data-type="address">+ Add Address</button>
        </div>

        <div>
            <label>Phone Number(s)</label>
            <div id="phones-wrap">
                <?php foreach ($phonesArr as $phone): ?>
                <div class="repeat-row">
                    <input type="text" name="phones[]" value="<?= e($phone) ?>" placeholder="e.g. 9876543210">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm repeat-add-btn" data-target="phones-wrap" data-type="phone">+ Add Phone Number</button>
        </div>

        <div>
            <label>Email Address(es)</label>
            <div id="emails-wrap">
                <?php foreach ($emailsArr as $email): ?>
                <div class="repeat-row">
                    <input type="email" name="emails[]" value="<?= e($email) ?>" placeholder="e.g. info@amrpackwell.com">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm repeat-add-btn" data-target="emails-wrap" data-type="email">+ Add Email Address</button>
        </div>

        <div class="form-row">
            <div>
                <label>WhatsApp Number (with country code, digits only)</label>
                <input type="text" name="whatsapp_number" value="<?= e($footer['whatsapp_number']) ?>" placeholder="918045800934">
            </div>
            <div>
                <label>GST Number</label>
                <input type="text" name="gst_number" value="<?= e($footer['gst_number']) ?>">
            </div>
        </div>

        <h3>Social Links</h3>
        <div class="form-row">
            <div>
                <label>Facebook URL</label>
                <input type="text" name="facebook_url" value="<?= e($footer['facebook_url']) ?>">
            </div>
            <div>
                <label>Instagram URL</label>
                <input type="text" name="instagram_url" value="<?= e($footer['instagram_url']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div>
                <label>LinkedIn URL</label>
                <input type="text" name="linkedin_url" value="<?= e($footer['linkedin_url']) ?>">
            </div>
            <div>
                <label>Twitter / X URL</label>
                <input type="text" name="twitter_url" value="<?= e($footer['twitter_url']) ?>">
            </div>
        </div>
        <div>
            <label>YouTube URL</label>
            <input type="text" name="youtube_url" value="<?= e($footer['youtube_url']) ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
    </form>

    <script>
    (function () {
        function makeRow(type) {
            var row = document.createElement('div');
            row.className = 'repeat-row';

            var field;
            if (type === 'address') {
                field = document.createElement('textarea');
                field.name = 'addresses[]';
                field.rows = 2;
                field.placeholder = 'e.g. Plot No. 12, Industrial Area, Delhi';
            } else if (type === 'phone') {
                field = document.createElement('input');
                field.type = 'text';
                field.name = 'phones[]';
                field.placeholder = 'e.g. 9876543210';
            } else {
                field = document.createElement('input');
                field.type = 'email';
                field.name = 'emails[]';
                field.placeholder = 'e.g. info@amrpackwell.com';
            }

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger btn-remove-row';
            removeBtn.innerHTML = '&times;';

            row.appendChild(field);
            row.appendChild(removeBtn);
            return row;
        }

        document.querySelectorAll('.repeat-add-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var wrap = document.getElementById(btn.dataset.target);
                wrap.appendChild(makeRow(btn.dataset.type));
            });
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove-row')) {
                var wrap = e.target.closest('div[id$="-wrap"]');
                if (wrap && wrap.children.length > 1) {
                    e.target.closest('.repeat-row').remove();
                } else if (wrap) {
                    // keep at least one input, just clear it
                    var field = wrap.querySelector('textarea, input');
                    if (field) field.value = '';
                }
            }
        });
    })();
    </script>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>