<?php
// config/config.php


// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'jaikvikc2_amrpackwell');
define('DB_USER', 'jaikvikc2_amrpackwell');
define('DB_PASS', '?%LrLU6DZAsy');
define('DB_CHARSET', 'utf8mb4');

// ---- Site ----
define('SITE_URL', 'https://amrpackwell.com'); // no trailing slash
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../uploads');      // absolute filesystem path
define('UPLOAD_URL', SITE_URL . '/php-admin/uploads');       // public URL to uploads folder

// ---- Session ----
define('ADMIN_SESSION_LIFETIME', 60 * 60 * 4); // 4 hours

// ---- Google reCAPTCHA (v2 checkbox) ----
define('RECAPTCHA_SITE_KEY', '6LfB12ItAAAAAKvrgkjQfE-kPjb3WORmiqu5owDE');
define('RECAPTCHA_SECRET_KEY', '6LfB12ItAAAAAMmZW6r1ZykFazIkQfBk0zEKsw5x');

// ---- Error reporting (turn OFF display_errors on production) ----
ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');
