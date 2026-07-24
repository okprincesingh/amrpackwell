<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$pageTitle = 'Dashboard';
$active    = 'dashboard';

// ---- Catalog counts ----
$productsCount   = (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
$categoriesCount = (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$blogsCount      = (int) db()->query('SELECT COUNT(*) FROM blogs')->fetchColumn();
$directorsCount  = (int) db()->query('SELECT COUNT(*) FROM directors')->fetchColumn();

// ---- Helpers ----
function dash_daily_counts(string $table, array $days, string $extraWhere = ''): array
{
    $stmt = db()->prepare("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM $table WHERE created_at >= :start" . $extraWhere . " GROUP BY DATE(created_at)");
    $stmt->execute(['start' => $days[0] . ' 00:00:00']);
    $rows = [];
    foreach ($stmt->fetchAll() as $r) { $rows[$r['d']] = (int) $r['c']; }
    return array_map(fn($d) => $rows[$d] ?? 0, $days);
}
function dash_monthly_counts(string $table, array $months): array
{
    $stmt = db()->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS c FROM $table WHERE created_at >= :start GROUP BY m");
    $stmt->execute(['start' => $months[0] . '-01 00:00:00']);
    $rows = [];
    foreach ($stmt->fetchAll() as $r) { $rows[$r['m']] = (int) $r['c']; }
    return array_map(fn($m) => $rows[$m] ?? 0, $months);
}
function dash_period_total(string $table, string $from, string $to, string $extraWhere = ''): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM $table WHERE created_at >= :from AND created_at < :to" . $extraWhere);
    $stmt->execute(['from' => $from, 'to' => $to]);
    return (int) $stmt->fetchColumn();
}
function dash_trend(int $current, int $previous): float
{
    if ($previous <= 0) return $current > 0 ? 100.0 : 0.0;
    return round((($current - $previous) / $previous) * 100, 1);
}

$days7 = [];
for ($i = 6; $i >= 0; $i--) { $days7[] = date('Y-m-d', strtotime("-$i day")); }
$dayLabels7 = array_map(fn($d) => date('D', strtotime($d)), $days7);

$months6 = [];
for ($i = 5; $i >= 0; $i--) { $months6[] = date('Y-m', strtotime("-$i month")); }
$monthLabels6 = array_map(fn($m) => date('M', strtotime($m . '-01')), $months6);

// Line chart data: quote requests vs enquiries, last 7 days
$quoteDaily7   = dash_daily_counts('quote_requests', $days7);
$enquiryDaily7 = dash_daily_counts('enquiries', $days7);

// Bar chart data: quote requests per month, last 6 months
$quoteMonthly6 = dash_monthly_counts('quote_requests', $months6);

// Donut data: quote requests by status
$statusCounts = ['new' => 0, 'contacted' => 0, 'closed' => 0];
foreach (db()->query("SELECT status, COUNT(*) AS c FROM quote_requests GROUP BY status")->fetchAll() as $r) {
    if (isset($statusCounts[$r['status']])) $statusCounts[$r['status']] = (int) $r['c'];
}
$quoteRequestsCount = array_sum($statusCounts);

// Stat cards: this-week vs last-week trend + 7-day sparkline
$today       = date('Y-m-d 00:00:00');
$weekAgo     = date('Y-m-d 00:00:00', strtotime('-7 day'));
$twoWeeksAgo = date('Y-m-d 00:00:00', strtotime('-14 day'));

$quoteThisWeek = dash_period_total('quote_requests', $weekAgo, $today);
$quoteTrend    = dash_trend($quoteThisWeek, dash_period_total('quote_requests', $twoWeeksAgo, $weekAgo));

$enquiriesCount  = (int) db()->query('SELECT COUNT(*) FROM enquiries')->fetchColumn();
$enquiryThisWeek = dash_period_total('enquiries', $weekAgo, $today);
$enquiryTrend    = dash_trend($enquiryThisWeek, dash_period_total('enquiries', $twoWeeksAgo, $weekAgo));

$smsRequestsCount = (int) db()->query('SELECT COUNT(*) FROM sms_requests')->fetchColumn();
$smsThisWeek      = dash_period_total('sms_requests', $weekAgo, $today);
$smsTrend         = dash_trend($smsThisWeek, dash_period_total('sms_requests', $twoWeeksAgo, $weekAgo));

$pendingQuotes    = $statusCounts['new'];
$pendingThisWeek  = dash_period_total('quote_requests', $weekAgo, $today, " AND status = 'new'");
$pendingTrend     = dash_trend($pendingThisWeek, dash_period_total('quote_requests', $twoWeeksAgo, $weekAgo, " AND status = 'new'"));

$sparkQuote   = dash_daily_counts('quote_requests', $days7);
$sparkEnquiry = dash_daily_counts('enquiries', $days7);
$sparkSms     = dash_daily_counts('sms_requests', $days7);
$sparkPending = dash_daily_counts('quote_requests', $days7, " AND status = 'new'");

function dash_sparkline_html(array $values, string $color): string
{
    $max = max(1, max($values));
    $html = '';
    foreach ($values as $v) {
        $h = max(3, (int) round(($v / $max) * 30));
        $html .= '<i style="height:' . $h . 'px;background:' . $color . '"></i>';
    }
    return $html;
}

require __DIR__ . '/../includes/layout-top.php';
?>
    <div class="card welcome-card">
        <h2 style="margin:0;">Welcome, <?= e($_SESSION['admin_name']) ?></h2>
        <p style="margin:6px 0 0;color:var(--muted);font-size:14px;">Here's what's happening across your site this week.</p>
    </div>

    <div class="dash-row dash-row-charts">
        <div class="chart-card">
            <div class="chart-card-head">
                <h3>Weekly Activity</h3>
                <div class="chart-legend">
                    <span><i style="background:#f97316"></i> Quote Requests</span>
                    <span><i style="background:#1d4ed8"></i> Enquiries</span>
                </div>
            </div>
            <canvas id="weeklyActivityChart" height="110"></canvas>
        </div>
        <div class="chart-card">
            <div class="chart-card-head"><h3>Monthly Quote Requests</h3></div>
            <canvas id="monthlyRequestsChart" height="110"></canvas>
        </div>
    </div>

    <div class="dash-row dash-row-mid">
        <div class="chart-card donut-card">
            <div class="chart-card-head" style="width:100%;"><h3>Quote Requests by Status</h3></div>
            <div class="donut-wrap">
                <canvas id="statusDonutChart"></canvas>
                <div class="donut-center">
                    <span class="n"><?= $quoteRequestsCount ?></span>
                    <span class="l">Total</span>
                </div>
            </div>
            <div class="donut-legend">
                <div class="donut-legend-row"><span class="left"><i class="dot" style="background:#1d4ed8"></i>New</span><span class="right"><?= $statusCounts['new'] ?></span></div>
                <div class="donut-legend-row"><span class="left"><i class="dot" style="background:#f97316"></i>Contacted</span><span class="right"><?= $statusCounts['contacted'] ?></span></div>
                <div class="donut-legend-row"><span class="left"><i class="dot" style="background:#64748b"></i>Closed</span><span class="right"><?= $statusCounts['closed'] ?></span></div>
            </div>
        </div>

        <div class="stats-grid-2x2">
            <div class="stat-card-v2">
                <div class="stat-card-v2-top">
                    <div><p class="stat-card-v2-label">Total Quote Requests</p><p class="stat-card-v2-value"><?= $quoteRequestsCount ?></p></div>
                    <div class="stat-card-v2-icon icon-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                </div>
                <div class="stat-card-v2-bottom">
                    <div class="sparkline"><?= dash_sparkline_html($sparkQuote, '#f97316') ?></div>
                    <span class="trend <?= $quoteTrend >= 0 ? 'trend-up' : 'trend-down' ?>"><?= ($quoteTrend >= 0 ? '+' : '') . $quoteTrend ?>%</span>
                </div>
            </div>

            <div class="stat-card-v2">
                <div class="stat-card-v2-top">
                    <div><p class="stat-card-v2-label">Total Enquiries</p><p class="stat-card-v2-value"><?= $enquiriesCount ?></p></div>
                    <div class="stat-card-v2-icon icon-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                </div>
                <div class="stat-card-v2-bottom">
                    <div class="sparkline"><?= dash_sparkline_html($sparkEnquiry, '#1d4ed8') ?></div>
                    <span class="trend <?= $enquiryTrend >= 0 ? 'trend-up' : 'trend-down' ?>"><?= ($enquiryTrend >= 0 ? '+' : '') . $enquiryTrend ?>%</span>
                </div>
            </div>

            <div class="stat-card-v2">
                <div class="stat-card-v2-top">
                    <div><p class="stat-card-v2-label">Total SMS Requests</p><p class="stat-card-v2-value"><?= $smsRequestsCount ?></p></div>
                    <div class="stat-card-v2-icon icon-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div>
                </div>
                <div class="stat-card-v2-bottom">
                    <div class="sparkline"><?= dash_sparkline_html($sparkSms, '#15803d') ?></div>
                    <span class="trend <?= $smsTrend >= 0 ? 'trend-up' : 'trend-down' ?>"><?= ($smsTrend >= 0 ? '+' : '') . $smsTrend ?>%</span>
                </div>
            </div>

            <div class="stat-card-v2">
                <div class="stat-card-v2-top">
                    <div><p class="stat-card-v2-label">Pending Quotes</p><p class="stat-card-v2-value"><?= $pendingQuotes ?></p></div>
                    <div class="stat-card-v2-icon icon-gray"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg></div>
                </div>
                <div class="stat-card-v2-bottom">
                    <div class="sparkline"><?= dash_sparkline_html($sparkPending, '#64748b') ?></div>
                    <span class="trend <?= $pendingTrend >= 0 ? 'trend-up' : 'trend-down' ?>"><?= ($pendingTrend >= 0 ? '+' : '') . $pendingTrend ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="quick-stats-row">
        <a href="products.php" class="quick-stat-card" style="text-decoration:none;">
            <div class="qs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <div><p class="qs-value"><?= $productsCount ?></p><p class="qs-label">Products</p></div>
        </a>
        <a href="categories.php" class="quick-stat-card" style="text-decoration:none;">
            <div class="qs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41L11 3.83A2 2 0 009.59 3H4a1 1 0 00-1 1v5.59a2 2 0 00.59 1.41l9.58 9.59a2 2 0 002.83 0l4.59-4.59a2 2 0 000-2.83z"/></svg></div>
            <div><p class="qs-value"><?= $categoriesCount ?></p><p class="qs-label">Categories</p></div>
        </a>
        <a href="blogs.php" class="quick-stat-card" style="text-decoration:none;">
            <div class="qs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H7a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2z"/></svg></div>
            <div><p class="qs-value"><?= $blogsCount ?></p><p class="qs-label">Blog Posts</p></div>
        </a>
        <a href="directors.php" class="quick-stat-card" style="text-decoration:none;">
            <div class="qs-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-9.13a4 4 0 110 8 4 4 0 010-8zm6 3a4 4 0 010 8"/></svg></div>
            <div><p class="qs-value"><?= $directorsCount ?></p><p class="qs-label">Directors</p></div>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const dayLabels   = <?= json_encode($dayLabels7) ?>;
        const quoteDaily   = <?= json_encode($quoteDaily7) ?>;
        const enquiryDaily = <?= json_encode($enquiryDaily7) ?>;
        const monthLabels  = <?= json_encode($monthLabels6) ?>;
        const quoteMonthly = <?= json_encode($quoteMonthly6) ?>;
        const statusData   = <?= json_encode(array_values($statusCounts)) ?>;

        Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        Chart.defaults.color = '#64748b';

        const lineCtx = document.getElementById('weeklyActivityChart').getContext('2d');
        const orangeGrad = lineCtx.createLinearGradient(0, 0, 0, 220);
        orangeGrad.addColorStop(0, 'rgba(249,115,22,0.25)');
        orangeGrad.addColorStop(1, 'rgba(249,115,22,0)');
        const blueGrad = lineCtx.createLinearGradient(0, 0, 0, 220);
        blueGrad.addColorStop(0, 'rgba(29,78,216,0.20)');
        blueGrad.addColorStop(1, 'rgba(29,78,216,0)');

        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: dayLabels,
                datasets: [
                    { label: 'Quote Requests', data: quoteDaily, borderColor: '#f97316', backgroundColor: orangeGrad, fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#f97316' },
                    { label: 'Enquiries', data: enquiryDaily, borderColor: '#1d4ed8', backgroundColor: blueGrad, fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#1d4ed8' },
                ],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                },
            },
        });

        new Chart(document.getElementById('monthlyRequestsChart'), {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{ data: quoteMonthly, backgroundColor: '#f97316', borderRadius: 6, maxBarThickness: 34 }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                },
            },
        });

        new Chart(document.getElementById('statusDonutChart'), {
            type: 'doughnut',
            data: {
                labels: ['New', 'Contacted', 'Closed'],
                datasets: [{ data: statusData, backgroundColor: ['#1d4ed8', '#f97316', '#64748b'], borderWidth: 0 }],
            },
            options: {
                cutout: '72%',
                plugins: { legend: { display: false } },
            },
        });
    })();
    </script>
<?php require __DIR__ . '/../includes/layout-bottom.php'; ?>