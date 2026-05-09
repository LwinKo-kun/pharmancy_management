<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load language file
$lang = $_SESSION['lang'] ?? 'en';
$langFile = ($lang === 'mm') ? 'lang_mm.php' : 'lang_en.php';
$langData = include $langFile;

$username = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'User';
$rawRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$identityHint = strtolower(trim((string)($_SESSION['username'] ?? '')));

$adminRoles = ['platform_admin', 'platform admin', 'admin', 'administrator', 'owner', 'super_admin', 'super admin'];
$isAdminRole = in_array($rawRole, $adminRoles, true) || ($rawRole === '' && in_array($identityHint, ['admin', 'administrator'], true));
$roleLabel = $isAdminRole ? 'Admin' : 'User';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

function nav_active(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'active' : '';
}
?>
<style>
    :root {
        --trust-blue: #2563eb;
        --health-green: #12b981;
        --light-gray: #eef3f8;
        --dark-charcoal: #111827;
        --border-gray: #cfd9e4;
        --page-bg: #eef3f8;
        --page-text: #111827;
        --sidebar-grad-a: #1d4ed8;
        --sidebar-grad-b: #0f766e;
        --glass-border: rgba(255, 255, 255, 0.22);
        --link-bg: rgba(255, 255, 255, 0.08);
        --link-hover-bg: rgba(255, 255, 255, 0.2);
        --link-text: rgba(255, 255, 255, 0.95);
    }
    :root[data-theme="dark"] {
        --page-bg: #0b1220;
        --page-text: #e5edf7;
        --sidebar-grad-a: #0f172a;
        --sidebar-grad-b: #1e293b;
        --glass-border: rgba(148, 163, 184, 0.24);
        --link-bg: rgba(30, 41, 59, 0.65);
        --link-hover-bg: rgba(51, 65, 85, 0.85);
        --link-text: #e2e8f0;
    }
    body {
        padding-left: 270px;
        background: var(--page-bg);
        color: var(--page-text);
        transition: background-color .2s ease, color .2s ease;
    }
    .app-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 250px;
        background: linear-gradient(180deg, var(--sidebar-grad-a) 0%, var(--sidebar-grad-b) 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        box-shadow: 8px 0 24px rgba(15, 23, 42, 0.28);
        padding: 14px 12px;
    }
    .brand {
        font-weight: 800;
        color: #ffffff;
        text-decoration: none;
        font-size: 18px;
        line-height: 1.3;
        padding: 10px 10px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.25);
        margin-bottom: 12px;
    }
    .nav-links {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: stretch;
        overflow-y: auto;
        padding-right: 2px;
    }
    .nav-links a {
        text-decoration: none;
        color: var(--link-text);
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        padding: 9px 10px;
        font-size: 14px;
        background: var(--link-bg);
        transition: .16s ease;
    }
    .nav-links a:hover {
        transform: translateX(2px);
        border-color: rgba(255, 255, 255, 0.45);
        background: var(--link-hover-bg);
    }
    .nav-links a.active {
        background: #ffffff;
        color: #10407f;
        border-color: #ffffff;
        font-weight: 700;
    }
    .nav-user {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        margin-top: auto;
        border-top: 1px solid rgba(255, 255, 255, 0.25);
        padding-top: 12px;
    }
    .theme-btn {
        border: 1px solid rgba(255, 255, 255, 0.48);
        border-radius: 8px;
        background: rgba(17, 24, 39, 0.2);
        color: #fff;
        padding: 7px 10px;
        font-size: 13px;
        cursor: pointer;
        text-align: left;
    }
    .theme-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    .role-chip {
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 700;
        background: #ffffff;
        color: #16499b;
        width: fit-content;
    }
    .role-chip.user {
        background: #d8ffef;
        color: #1e7f5f;
    }
    .logout-btn {
        text-decoration: none;
        background: #ffffff;
        color: #0e2a48;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
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
        width: 100%;
    }
    .nav-user span {
        color: #ffffff;
        font-size: 13px;
    }
    .welcome {
        line-height: 1.4;
        color: rgba(255, 255, 255, 0.95);
    }
    @media (max-width: 980px) {
        body {
            padding-left: 0;
            padding-top: 116px;
        }
        .app-sidebar {
            width: 100%;
            height: auto;
            bottom: auto;
            flex-direction: row;
            align-items: flex-start;
            gap: 10px;
            overflow-x: auto;
            white-space: normal;
            padding: 10px;
        }
        .brand {
            border-bottom: 0;
            margin: 0;
            padding: 6px 8px;
        }
        .nav-links {
            flex-direction: row;
            flex-wrap: nowrap;
            overflow: visible;
        }
        .nav-links a {
            white-space: nowrap;
        }
        .nav-user {
            margin-top: 0;
            border-top: 0;
            padding-top: 0;
            flex-direction: row;
            align-items: center;
            margin-left: auto;
        }
        .welcome {
            display: none;
        }
    }
</style>
<aside class="app-sidebar">
    <a class="brand" href="dashboard.php"><?= htmlspecialchars($langData['title'] ?? 'Smart Inventory'); ?></a>
    <nav class="nav-links">
        <a class="<?= nav_active('dashboard.php', $currentPage) ?>" href="dashboard.php">Dashboard</a>
        <a class="<?= nav_active('medicine_list.php', $currentPage) ?>" href="medicine_list.php"><?= $langData['medicine_list'] ?? 'Medicines'; ?></a>
        <a class="<?= nav_active('goods_receipt_form.php', $currentPage) ?>" href="goods_receipt_form.php"><?= $langData['goods_receipt'] ?? 'Goods Receipt'; ?></a>
        <a class="<?= nav_active('sales.php', $currentPage) ?>" href="sales.php"><?= $langData['sales'] ?? 'Sales'; ?></a>
        <a class="<?= nav_active('sales_report.php', $currentPage) ?>" href="sales_report.php"><?= $langData['nav_sales_report'] ?? 'Sales Report'; ?></a>
        <a class="<?= nav_active('low_stock_report.php', $currentPage) ?>" href="low_stock_report.php"><?= $langData['nav_low_stock_report'] ?? 'Low Stock Report'; ?></a>
        <a class="<?= nav_active('waste_report.php', $currentPage) ?>" href="waste_report.php"><?= $langData['nav_waste_report'] ?? 'Waste Report'; ?></a>
        <a class="<?= nav_active('reorder_alerts.php', $currentPage) ?>" href="reorder_alerts.php"><?= $langData['nav_reorder_alerts'] ?? 'Reorder Alerts'; ?></a>
        <a class="<?= nav_active('expiry_alerts.php', $currentPage) ?>" href="expiry_alerts.php"><?= $langData['nav_expiry_alerts'] ?? 'Expiry Alerts'; ?></a>
        <a class="<?= nav_active('suppliers.php', $currentPage) ?>" href="suppliers.php"><?= $langData['suppliers'] ?? 'Suppliers'; ?></a>
    </nav>
    <div class="nav-user">
        <form method="post" action="change_lang.php">
            <select class="lang-select" name="lang" onchange="this.form.submit()">
                <option value="en" <?= ($lang === 'en') ? 'selected' : '' ?>>English</option>
                <option value="mm" <?= ($lang === 'mm') ? 'selected' : '' ?>>Myanmar</option>
            </select>
        </form>
        <button type="button" class="theme-btn" id="themeToggleBtn">Night mode</button>
        <span class="welcome"><?= htmlspecialchars($langData['welcome'] ?? 'Welcome,') ?><br><?= htmlspecialchars($username); ?></span>
        <span class="role-chip <?= $roleLabel === 'User' ? 'user' : '' ?>"><?= htmlspecialchars($roleLabel) ?></span>
        <a class="logout-btn" href="logout.php"><?= htmlspecialchars($langData['logout'] ?? 'Logout'); ?></a>
    </div>
</aside>
<script>
(() => {
    const root = document.documentElement;
    const key = 'smart_inventory_theme';
    const btn = document.getElementById('themeToggleBtn');
    let saved = null;
    try {
        saved = localStorage.getItem(key);
    } catch (e) {
        saved = null;
    }
    const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial = (saved === 'dark' || saved === 'light') ? saved : (systemDark ? 'dark' : 'light');
    root.setAttribute('data-theme', initial);
    if (btn) btn.textContent = initial === 'dark' ? 'Day mode' : 'Night mode';

    if (btn) {
        btn.addEventListener('click', () => {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try {
                localStorage.setItem(key, next);
            } catch (e) {
                // Ignore storage write errors and still switch theme for this session.
            }
            btn.textContent = next === 'dark' ? 'Day mode' : 'Night mode';
        });
    }
})();
</script>
