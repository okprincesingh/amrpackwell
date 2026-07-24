<?php
// includes/layout-top.php
// Include this after require_login(); expects $pageTitle to be set.

$topbarNewEnquiries = 0;
$topbarNewQuotes    = 0;
$topbarNewSms       = 0;
try { $topbarNewEnquiries = (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn(); } catch (Throwable $e) {}
try { $topbarNewQuotes    = (int) db()->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'new'")->fetchColumn(); } catch (Throwable $e) {}
try { $topbarNewSms       = (int) db()->query("SELECT COUNT(*) FROM sms_requests WHERE status = 'new'")->fetchColumn(); } catch (Throwable $e) {}

$adminInitials = '';
foreach (preg_split('/\s+/', trim($_SESSION['admin_name'] ?? '')) as $part) {
    if ($part !== '') $adminInitials .= mb_strtoupper(mb_substr($part, 0, 1));
}
$adminInitials = mb_substr($adminInitials, 0, 2) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($pageTitle ?? 'Admin') ?> — AMR Packwell Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">
                <img src="../../assets/images/logo/AMR-LOGO-2.png" alt="AMR Packwell">
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Overview</div>
            <a href="dashboard.php" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <div class="sidebar-section">Content</div>
            <a href="footer-settings.php" class="<?= ($active ?? '') === 'footer' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M3 16h18"/></svg>
                Footer
            </a>
            <a href="blogs.php" class="<?= ($active ?? '') === 'blogs' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H7a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2z"/><path stroke-linecap="round" d="M9 12h6M9 16h6M9 8h2"/></svg>
                Blogs
            </a>
            <a href="categories.php" class="<?= ($active ?? '') === 'categories' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41L11 3.83A2 2 0 009.59 3H4a1 1 0 00-1 1v5.59a2 2 0 00.59 1.41l9.58 9.59a2 2 0 002.83 0l4.59-4.59a2 2 0 000-2.83z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
                Categories
            </a>
            <a href="products.php" class="<?= ($active ?? '') === 'products' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Products
            </a>
            <a href="contact-settings.php" class="<?= ($active ?? '') === 'contact-settings' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                Contact Settings
            </a>
            <a href="navbar.php" class="<?= ($active ?? '') === 'navbar' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                Navbar
            </a>
            <a href="directors.php" class="<?= ($active ?? '') === 'directors' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-9.13a4 4 0 110 8 4 4 0 010-8zm6 3a4 4 0 010 8"/></svg>
                About Us / Directors
            </a>

            <div class="sidebar-section">Requests</div>
            <a href="enquiries.php" class="<?= ($active ?? '') === 'enquiries' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Enquiries
                <?php if ($topbarNewEnquiries > 0): ?><span class="nav-count"><?= $topbarNewEnquiries ?></span><?php endif; ?>
            </a>
            <a href="sms-requests.php" class="<?= ($active ?? '') === 'sms-requests' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                SMS Requests
                <?php if ($topbarNewSms > 0): ?><span class="nav-count"><?= $topbarNewSms ?></span><?php endif; ?>
            </a>
            <a href="quote-requests.php" class="<?= ($active ?? '') === 'quote-requests' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-5 9l2 2 4-4"/></svg>
                Quote Requests
                <?php if ($topbarNewQuotes > 0): ?><span class="nav-count"><?= $topbarNewQuotes ?></span><?php endif; ?>
            </a>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <form class="topbar-search" action="quote-requests.php" method="get">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3"/></svg>
                <input type="text" name="q" placeholder="Search quote requests — name, phone, product...">
            </form>
            <div class="topbar-right">
                <a class="icon-btn" href="enquiries.php?status=new" title="New enquiries">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <?php if ($topbarNewEnquiries > 0): ?><span class="icon-badge"><?= $topbarNewEnquiries ?></span><?php endif; ?>
                </a>
                <a class="icon-btn" href="quote-requests.php?status=new" title="New quote requests">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <?php if ($topbarNewQuotes > 0): ?><span class="icon-badge icon-badge-orange"><?= $topbarNewQuotes ?></span><?php endif; ?>
                </a>
                <div class="topbar-divider"></div>
                <div class="topbar-user">
                    <div class="user-avatar"><?= e($adminInitials) ?></div>
                    <div class="user-meta">
                        <span class="user-name"><?= e($_SESSION['admin_name'] ?? '') ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                    <a href="logout.php" class="btn btn-sm">Logout</a>
                </div>
            </div>
        </header>
        <main class="content dash-content">