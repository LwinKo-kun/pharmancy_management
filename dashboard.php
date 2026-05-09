<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Language handling
$lang = $_SESSION['lang'] ?? 'en';
$langFile = 'lang_' . $lang . '.php';
$langData = file_exists($langFile) ? include($langFile) : include('lang_en.php');
$role = strtolower((string)($_SESSION['role'] ?? 'user'));
$isAdmin = in_array($role, ['admin', 'owner', 'platform_admin'], true);
require_once 'config/db.php';

$stats = [
    'medicine_count' => 0,
    'low_stock_count' => 0,
    'sales_today' => 0.0,
    'expiring_soon' => 0,
];
$monthlySales = [];
$stockMix = [
    'healthy' => 0,
    'low' => 0,
];

try {
    $stats['medicine_count'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
    $stats['low_stock_count'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE reorder_level IS NOT NULL AND quantity <= reorder_level")->fetchColumn();
    $stats['sales_today'] = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sold_at) = CURDATE()")->fetchColumn();
    $stats['expiring_soon'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE exp_date IS NOT NULL AND exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

    $monthlyStmt = $pdo->query("
        SELECT DATE_FORMAT(sold_at, '%Y-%m') AS ym, COALESCE(SUM(total_amount), 0) AS total
        FROM sales
        WHERE sold_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(sold_at, '%Y-%m')
        ORDER BY ym ASC
    ");
    $monthlyMap = [];
    foreach ($monthlyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $monthlyMap[$row['ym']] = (float)$row['total'];
    }
    for ($i = 5; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("-$i month"));
        $monthlySales[] = [
            'label' => date('M y', strtotime($key . '-01')),
            'total' => $monthlyMap[$key] ?? 0.0,
        ];
    }

    $stockMix['low'] = $stats['low_stock_count'];
    $stockMix['healthy'] = max(0, $stats['medicine_count'] - $stats['low_stock_count']);
} catch (Throwable $e) {
    // Keep dashboard functional even when analytics queries fail.
}

$maxMonthly = 0.0;
foreach ($monthlySales as $point) {
    if ($point['total'] > $maxMonthly) {
        $maxMonthly = $point['total'];
    }
}
$maxMonthly = max($maxMonthly, 1.0);
$linePoints = [];
foreach ($monthlySales as $idx => $point) {
    $x = 22 + ($idx * 78);
    $y = 182 - (int)round(($point['total'] / $maxMonthly) * 140);
    $linePoints[] = $x . ',' . $y;
}
$linePath = implode(' ', $linePoints);
$stockTotal = max(1, $stockMix['healthy'] + $stockMix['low']);
$lowPct = (int)round(($stockMix['low'] / $stockTotal) * 100);
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Smart Inventory</title>
<style>
:root {
    --trust-blue: #2563eb;
    --health-green: #12b981;
    --light-gray: #eef3f8;
    --dark-charcoal: #111827;
    --border-gray: #cfd9e4;
    --vivid-cyan: #06b6d4;
    --warning-amber: #f59e0b;
    --danger-red: #ef4444;
}
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    background:
        radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.12), transparent 30%),
        radial-gradient(circle at 90% 15%, rgba(18, 185, 129, 0.12), transparent 35%),
        var(--light-gray);
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    color: var(--dark-charcoal);
}
.wrapper {
    width: min(1200px, 96%);
    margin: 0 auto 32px;
}
.hero {
    margin: 12px 0 18px;
    background: linear-gradient(115deg, #1d4ed8, #0891b2 50%, #10b981);
    color: #fff;
    border: 0;
    border-radius: 18px;
    padding: 24px 22px;
    box-shadow: 0 16px 30px rgba(37, 99, 235, 0.28);
}
.hero h1 {
    margin: 0 0 8px;
    font-size: 28px;
}
.hero p {
    margin: 0;
    color: rgba(255, 255, 255, 0.94);
}
.pill {
    display: inline-block;
    margin-top: 12px;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 700;
    color: #103f8f;
    background: #ffffff;
}
.pill.user {
    color: #0f7f5d;
    background: #d9fff2;
}
.section-title {
    margin: 20px 2px 12px;
    font-size: 18px;
}
.metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}
.metric-card {
    border-radius: 14px;
    color: #fff;
    padding: 14px 14px 12px;
    box-shadow: 0 10px 18px rgba(17, 24, 39, 0.16);
}
.metric-card h3 {
    margin: 0;
    font-size: 13px;
    letter-spacing: .01em;
    opacity: .96;
    font-weight: 600;
}
.metric-card strong {
    display: block;
    margin-top: 10px;
    font-size: 28px;
    font-weight: 800;
}
.metric-card.blue { background: linear-gradient(135deg, #2563eb, #06b6d4); }
.metric-card.green { background: linear-gradient(135deg, #059669, #22c55e); }
.metric-card.amber { background: linear-gradient(135deg, #d97706, #f59e0b); }
.metric-card.red { background: linear-gradient(135deg, #dc2626, #ef4444); }
.charts {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
.panel {
    background: #fff;
    border: 1px solid var(--border-gray);
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 8px 14px rgba(31, 41, 51, 0.08);
}
.panel h3 {
    margin: 0 0 8px;
    font-size: 16px;
}
.panel p {
    margin: 0 0 12px;
    font-size: 13px;
    color: #5b6a79;
}
.line-chart {
    width: 100%;
    overflow-x: auto;
}
.line-chart svg {
    width: 100%;
    min-width: 520px;
    height: 220px;
}
.axis-labels {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 6px;
    margin-top: 4px;
    font-size: 12px;
    color: #637384;
}
.donut-wrap {
    display: grid;
    place-items: center;
    padding: 6px 0 2px;
}
.donut {
    width: 170px;
    aspect-ratio: 1;
    border-radius: 50%;
    background: conic-gradient(var(--danger-red) 0 <?= $lowPct ?>%, var(--health-green) <?= $lowPct ?>% 100%);
    position: relative;
}
.donut::after {
    content: "";
    position: absolute;
    inset: 24px;
    border-radius: 50%;
    background: #fff;
}
.donut-center {
    position: absolute;
    text-align: center;
    z-index: 2;
    font-weight: 700;
    color: #324353;
    font-size: 13px;
}
.legend {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 12px;
    font-size: 12px;
    color: #4f6172;
}
.dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 6px;
}
.dot.red { background: var(--danger-red); }
.dot.green { background: var(--health-green); }
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
}
.card {
    text-decoration: none;
    color: var(--dark-charcoal);
    background: #fff;
    border: 1px solid var(--border-gray);
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 6px 14px rgba(31, 41, 51, 0.06);
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
}
.card:hover {
    transform: translateY(-2px);
    border-color: #7fb0ff;
    box-shadow: 0 14px 24px rgba(37, 99, 235, 0.18);
}
.card h3 {
    margin: 0 0 8px;
    font-size: 16px;
}
.card p {
    margin: 0 0 10px;
    color: #526272;
    line-height: 1.45;
    font-size: 14px;
}
.chip {
    display: inline-block;
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 12px;
    font-weight: 700;
    color: #1f9d70;
    background: #e9faf3;
}
.chip.blue {
    color: #1352c8;
    background: #dcebff;
}
footer {
    margin-top: 26px;
    text-align: center;
    color: #657686;
    font-size: 13px;
}
@media (max-width: 900px) {
    .charts {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="wrapper">
    <section class="hero">
        <h1><?= $isAdmin ? 'Admin Operations Center' : 'User Operations Workspace' ?></h1>
        <p>
            <?= $isAdmin
                ? 'Manage system records, control inventory quality, and monitor pharmacy performance with trusted data.'
                : 'Handle daily medicine flow, sales, and stock actions quickly with a clean focused interface.' ?>
        </p>
        <span class="pill <?= $isAdmin ? '' : 'user' ?>"><?= $isAdmin ? 'ADMIN PURPOSE' : 'USER PURPOSE' ?></span>
    </section>

    <section class="metrics">
        <article class="metric-card blue">
            <h3>Total Medicines</h3>
            <strong><?= number_format($stats['medicine_count']) ?></strong>
        </article>
        <article class="metric-card green">
            <h3>Sales Today</h3>
            <strong><?= number_format($stats['sales_today'], 2) ?></strong>
        </article>
        <article class="metric-card amber">
            <h3>Low Stock Items</h3>
            <strong><?= number_format($stats['low_stock_count']) ?></strong>
        </article>
        <article class="metric-card red">
            <h3>Expiring in 30 Days</h3>
            <strong><?= number_format($stats['expiring_soon']) ?></strong>
        </article>
    </section>

    <section class="charts">
        <article class="panel">
            <h3>Monthly Sales Trend</h3>
            <p>Last six months total sales amount.</p>
            <div class="line-chart">
                <svg viewBox="0 0 430 210" preserveAspectRatio="none" role="img" aria-label="Monthly sales line chart">
                    <line x1="22" y1="182" x2="412" y2="182" stroke="#b6c6d6" stroke-width="1"/>
                    <line x1="22" y1="24" x2="22" y2="182" stroke="#b6c6d6" stroke-width="1"/>
                    <polyline fill="none" stroke="#2563eb" stroke-width="3" points="<?= htmlspecialchars($linePath) ?>"/>
                    <?php foreach ($linePoints as $pt): $xy = explode(',', $pt); ?>
                        <circle cx="<?= (int)$xy[0] ?>" cy="<?= (int)$xy[1] ?>" r="3.5" fill="#10b981"/>
                    <?php endforeach; ?>
                </svg>
            </div>
            <div class="axis-labels">
                <?php foreach ($monthlySales as $point): ?>
                    <span><?= htmlspecialchars($point['label']) ?></span>
                <?php endforeach; ?>
            </div>
        </article>
        <article class="panel">
            <h3>Stock Health Mix</h3>
            <p>Healthy stock vs low stock items.</p>
            <div class="donut-wrap">
                <div class="donut"></div>
                <div class="donut-center"><?= $lowPct ?>% low<br>stock</div>
            </div>
            <div class="legend">
                <span><i class="dot green"></i>Healthy: <?= number_format($stockMix['healthy']) ?></span>
                <span><i class="dot red"></i>Low: <?= number_format($stockMix['low']) ?></span>
            </div>
        </article>
    </section>

    <?php
    $commonCards = [
        ['title'=>$langData['manage_medicines_title'] ?? 'Manage Medicines','desc'=>$langData['manage_medicines_desc'] ?? 'Create, update, and review medicine records.','link'=>'medicine_list.php','chip'=>'Core'],
        ['title'=>$langData['goods_receipt_title'] ?? 'Goods Receipt','desc'=>$langData['goods_receipt_desc'] ?? 'Record received medicines and update stock.','link'=>'goods_receipt_form.php','chip'=>'Stock'],
        ['title'=>$langData['sales_title'] ?? 'Sales','desc'=>$langData['sales_desc'] ?? 'Run medicine sales and keep movement history.','link'=>'sales.php','chip'=>'Sales'],
    ];

    $adminCards = [
        ['title'=>$langData['suppliers_title'] ?? 'Suppliers','desc'=>$langData['suppliers_desc'] ?? 'Manage supplier profiles and purchasing channels.','link'=>'suppliers.php','chip'=>'Admin'],
        ['title'=>$langData['sales_report_title'] ?? 'Sales Report','desc'=>$langData['sales_report_desc'] ?? 'Track sales trends and product performance.','link'=>'sales_report.php','chip'=>'Insights'],
        ['title'=>$langData['low_stock_title'] ?? 'Low Stock Report','desc'=>$langData['low_stock_desc'] ?? 'Identify items below reorder level.','link'=>'low_stock_report.php','chip'=>'Alerts'],
        ['title'=>$langData['waste_report_title'] ?? 'Waste Report','desc'=>$langData['waste_report_desc'] ?? 'Review wastage reasons and quantities.','link'=>'waste_report.php','chip'=>'Audit'],
        ['title'=>$langData['reorder_title'] ?? 'Reorder Alerts','desc'=>$langData['reorder_desc'] ?? 'Get reorder suggestions before stockout.','link'=>'reorder_alerts.php','chip'=>'Planning'],
        ['title'=>$langData['expiry_title'] ?? 'Expiry Alerts','desc'=>$langData['expiry_desc'] ?? 'Monitor near-expiry medicine lots.','link'=>'expiry_alerts.php','chip'=>'Safety'],
    ];

    $userCards = [
        ['title'=>$langData['expiry_title'] ?? 'Expiry Alerts','desc'=>$langData['expiry_desc'] ?? 'Check medicine expiry before dispensing.','link'=>'expiry_alerts.php','chip'=>'Safety'],
        ['title'=>$langData['reorder_title'] ?? 'Reorder Alerts','desc'=>$langData['reorder_desc'] ?? 'See medicines that need restocking soon.','link'=>'reorder_alerts.php','chip'=>'Stock'],
    ];
    ?>

    <h2 class="section-title"><?= $isAdmin ? 'Core System Tools' : 'Daily Workflow Tools' ?></h2>
    <section class="grid">
        <?php foreach ($commonCards as $card): ?>
            <a class="card" href="<?= htmlspecialchars($card['link']) ?>">
                <h3><?= htmlspecialchars($card['title']) ?></h3>
                <p><?= htmlspecialchars($card['desc']) ?></p>
                <span class="chip blue"><?= htmlspecialchars($card['chip']) ?></span>
            </a>
        <?php endforeach; ?>
    </section>

    <h2 class="section-title"><?= $isAdmin ? 'Administrative Controls' : 'Operator Shortcuts' ?></h2>
    <section class="grid">
        <?php foreach (($isAdmin ? $adminCards : $userCards) as $card): ?>
            <a class="card" href="<?= htmlspecialchars($card['link']) ?>">
                <h3><?= htmlspecialchars($card['title']) ?></h3>
                <p><?= htmlspecialchars($card['desc']) ?></p>
                <span class="chip"><?= htmlspecialchars($card['chip']) ?></span>
            </a>
        <?php endforeach; ?>
    </section>

    <footer>
        &copy; <?= date("Y") ?> <?= htmlspecialchars($langData['footer'] ?? 'Medicines Sales System') ?>
    </footer>
</main>

</body>
</html>
