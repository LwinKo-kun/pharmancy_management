<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load language file
$lang = $_SESSION['lang'] ?? 'en';
$langFile = ($lang === 'mm') ? 'lang_mm.php' : 'lang_en.php';
$langData = include $langFile;

$username = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'User';
$role = strtolower((string)($_SESSION['role'] ?? 'user'));
$roleLabel = ($role === 'platform_admin' || $role === 'admin' || $role === 'owner') ? 'Admin' : 'User';
?>
<style>
    :root {
        --trust-blue: #2563eb;
        --health-green: #12b981;
        --light-gray: #eef3f8;
        --dark-charcoal: #111827;
        --border-gray: #cfd9e4;
    }
    .app-nav {
        margin: 16px 18px 8px;
        background: linear-gradient(100deg, rgba(37, 99, 235, 0.94), rgba(6, 182, 212, 0.94) 45%, rgba(16, 185, 129, 0.94));
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 14px;
        box-shadow: 0 14px 24px rgba(37, 99, 235, 0.22);
        padding: 12px 16px;
    }
    .app-nav-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .brand {
        font-weight: 700;
        color: #ffffff;
        text-decoration: none;
        font-size: 18px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    }
    .nav-links {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .nav-links a {
        text-decoration: none;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.38);
        border-radius: 8px;
        padding: 7px 10px;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.14);
    }
    .nav-links a:hover {
        border-color: #ffffff;
        color: #0e2a48;
        background: #ffffff;
    }
    .nav-user {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .role-chip {
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 700;
        background: #ffffff;
        color: #16499b;
    }
    .role-chip.user {
        background: #d8ffef;
        color: #1e7f5f;
    }
    .logout-btn {
        text-decoration: none;
        background: #ffffff;
        color: #0e2a48;
        padding: 7px 10px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
    }
    .logout-btn:hover {
        background: #dbeafe;
    }
    .lang-select {
        border: 1px solid rgba(255, 255, 255, 0.48);
        border-radius: 8px;
        padding: 6px 8px;
        font-size: 13px;
        color: #ffffff;
        background: rgba(17, 24, 39, 0.15);
    }
    .nav-user span {
        color: #ffffff;
    }
</style>
<nav class="app-nav">
    <div class="app-nav-row">
        <a class="brand" href="dashboard.php"><?= htmlspecialchars($langData['title'] ?? 'Smart Inventory'); ?></a>
        <div class="nav-links">
            <a href="medicine_list.php"><?= $langData['medicine_list'] ?? 'Medicines'; ?></a>
            <a href="goods_receipt_form.php"><?= $langData['goods_receipt'] ?? 'Goods Receipt'; ?></a>
            <a href="sales.php"><?= $langData['sales'] ?? 'Sales'; ?></a>
            <a href="sales_report.php"><?= $langData['nav_sales_report'] ?? 'Sales Report'; ?></a>
            <a href="low_stock_report.php"><?= $langData['nav_low_stock_report'] ?? 'Low Stock Report'; ?></a>
            <a href="waste_report.php"><?= $langData['nav_waste_report'] ?? 'Waste Report'; ?></a>
            <a href="reorder_alerts.php"><?= $langData['nav_reorder_alerts'] ?? 'Reorder Alerts'; ?></a>
            <a href="expiry_alerts.php"><?= $langData['nav_expiry_alerts'] ?? 'Expiry Alerts'; ?></a>
            <a href="suppliers.php"><?= $langData['suppliers'] ?? 'Suppliers'; ?></a>
        </div>
        <div class="nav-user">
            <form method="post" action="change_lang.php">
                <select class="lang-select" name="lang" onchange="this.form.submit()">
                    <option value="en" <?= ($lang === 'en') ? 'selected' : '' ?>>English</option>
                    <option value="mm" <?= ($lang === 'mm') ? 'selected' : '' ?>>Myanmar</option>
                </select>
            </form>
            <span><?= htmlspecialchars($langData['welcome'] ?? 'Welcome,') ?> <?= htmlspecialchars($username); ?></span>
            <span class="role-chip <?= $roleLabel === 'User' ? 'user' : '' ?>"><?= $roleLabel ?></span>
            <a class="logout-btn" href="logout.php"><?= htmlspecialchars($langData['logout'] ?? 'Logout'); ?></a>
        </div>
    </div>
</nav>
