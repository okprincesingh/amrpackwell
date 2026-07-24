<?php
// admin/logout.php
require_once __DIR__ . '/../includes/auth.php';

$_SESSION = [];
session_unset();

// Also expire the cookie itself on the client, so the browser drops it
// immediately instead of holding on to an id that no longer maps to data.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

no_cache_headers();
header('Location: login.php');
exit;