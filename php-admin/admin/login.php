<?php
// admin/login.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';

no_cache_headers();

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (!verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {
        $error = 'Please complete the "I\'m not a robot" check.';
    } else {
        $stmt = db()->prepare('SELECT id, username, password, full_name, is_active FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name']     = $user['full_name'];
            $_SESSION['last_activity']  = time();

            db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login — AMR Packwell</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/php-admin/assets/css/admin.css">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="login-page">
    <div class="login-box">
        <h1>AMR Packwell</h1>
        <p class="subtitle">Admin Panel</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-info">Your session expired. Please log in again.</div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE_KEY) ?>" style="margin: 16px 0;"></div>

            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
    </div>
</body>
</html>
