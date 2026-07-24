<?php
// admin/contact-settings.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Contact Settings';
$active    = 'contact-settings';

$errors  = [];
$success = false;

// Fetch current row (single-row table, id = 1)
$stmt = db()->prepare('SELECT * FROM contact_page_settings WHERE id = 1');
$stmt->execute();
$cs = $stmt->fetch();

if (!$cs) {
    db()->exec('INSERT INTO contact_page_settings (id) VALUES (1)');
    $cs = db()->query('SELECT * FROM contact_page_settings WHERE id = 1')->fetch();
}

// Decode existing JSON lists (fallback to a single empty row so the form always shows at least one input)
function cs_decode_list($json)
{
    $list = json_decode($json ?? '', true);
    if (!is_array($list) || empty(array_filter($list, fn($v) => trim((string)$v) !== ''))) {
        return [''];
    }
    return array_values($list);
}

$addressesArr = cs_decode_list($cs['addresses'] ?? null);
$phonesArr    = cs_decode_list($cs['phones'] ?? null);
$emailsArr    = cs_decode_list($cs['emails'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    // ---- Collect repeatable fields, dropping blank entries ----
    $addresses = array_values(array_filter(array_map('trim', $_POST['addresses'] ?? []), fn($v) => $v !== ''));
    $phones    = array_values(array_filter(array_map('trim', $_POST['phones'] ?? []), fn($v) => $v !== ''));
    $emails    = array_values(array_filter(array_map('trim', $_POST['emails'] ?? []), fn($v) => $v !== ''));

    $fields = [
        'working_hours' => trim($_POST['working_hours'] ?? ''),
    ];

    // ---- Validation ----
    foreach ($emails as $idx => $emailVal) {
        if (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email #' . ($idx + 1) . ' ("' . $emailVal . '") is not a valid email address.';
        }
    }

    if (empty($errors)) {
        $fields['addresses'] = json_encode($addresses, JSON_UNESCAPED_UNICODE);
        $fields['phones']    = json_encode($phones, JSON_UNESCAPED_UNICODE);
        $fields['emails']    = json_encode($emails, JSON_UNESCAPED_UNICODE);

        $sql = 'UPDATE contact_page_settings SET
                    addresses = :addresses,
                    phones = :phones,
                    emails = :emails,
                    working_hours = :working_hours
                WHERE id = 1';
        db()->prepare($sql)->execute($fields);

        $success = true;
        $cs = db()->query('SELECT * FROM contact_page_settings WHERE id = 1')->fetch();

        $addressesArr = cs_decode_list($cs['addresses'] ?? null);
        $phonesArr    = cs_decode_list($cs['phones'] ?? null);
        $emailsArr    = cs_decode_list($cs['emails'] ?? null);
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
        <div class="alert alert-success">Contact settings updated successfully.</div>
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

    <form method="post" class="card form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <h3>Contact Details</h3>

        <div>
            <label>Address(es)</label>
            <div id="addresses-wrap">
                <?php foreach ($addressesArr as $addr): ?>
                <div class="repeat-row">
                    <textarea name="addresses[]" rows="2" placeholder="e.g. E-11, EPIP Site-5, Kasna, Greater Noida"><?= e($addr) ?></textarea>
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
                    <input type="text" name="phones[]" value="<?= e($phone) ?>" placeholder="+919871523344">
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
                    <input type="email" name="emails[]" value="<?= e($email) ?>" placeholder="info@amrpackwell.com">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm repeat-add-btn" data-target="emails-wrap" data-type="email">+ Add Email Address</button>
        </div>

        <div>
            <label>Working Hours</label>
            <input type="text" name="working_hours" value="<?= e($cs['working_hours']) ?>" placeholder="Mon - Sat: 9:00 AM - 7:00 PM">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
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
                field.placeholder = 'e.g. E-11, EPIP Site-5, Kasna, Greater Noida';
            } else if (type === 'phone') {
                field = document.createElement('input');
                field.type = 'text';
                field.name = 'phones[]';
                field.placeholder = '+919871523344';
            } else {
                field = document.createElement('input');
                field.type = 'email';
                field.name = 'emails[]';
                field.placeholder = 'info@amrpackwell.com';
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
                    var field = wrap.querySelector('textarea, input');
                    if (field) field.value = '';
                }
            }
        });
    })();
    </script>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>