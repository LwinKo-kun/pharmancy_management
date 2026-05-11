<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load language file
$lang = $_SESSION['lang'] ?? 'en';
$langFile = ($lang === 'mm') ? 'lang_mm.php' : 'lang_en.php';
$langData = (file_exists($langFile)) ? include $langFile : [];

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
        /* Colors */
        --trust-blue: #2563eb;
        --health-green: #12b981;
        --page-bg: #eef3f8;
        --page-text: #111827;
        --sidebar-grad-a: #1d4ed8;
        --sidebar-grad-b: #0f766e;
        --glass-border: rgba(255, 255, 255, 0.22);
        --link-bg: rgba(255, 255, 255, 0.08);
        --link-hover-bg: rgba(255, 255, 255, 0.2);
        --link-text: rgba(255, 255, 255, 0.95);
        
        /* Role Chips */
        --role-chip-admin-bg: #ffffff;
        --role-chip-admin-color: #16499b;
        --role-chip-user-bg: #d8ffef; /* User Green Bg */
        --role-chip-user-color: #1e7f5f; /* User Green Text */
        
        --logout-btn-bg: #ffffff;
        --logout-btn-color: #0e2a48;
    }

    :root[data-theme="dark"] {
        --page-bg: #0b1220;
        --page-text: #e5edf7;
        --sidebar-grad-a: #0f172a;
        --sidebar-grad-b: #1e293b;
        --role-chip-admin-bg: #1e3a8a;
        --role-chip-admin-color: #bfdbfe;
        --role-chip-user-bg: #064e3b;
        --role-chip-user-color: #a7f3d0;
        --logout-btn-bg: #1e293b;
        --logout-btn-color: #e2e8f0;
    }

    body {
        padding: 20px 20px 20px 270px !important;
        background: var(--page-bg) !important;
        color: var(--page-text) !important;
        transition: background-color .2s ease, color .2s ease !important;
        min-height: 100vh;
        box-sizing: border-box;
        margin: 0;
    }

    .app-sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
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
        font-weight: 800; color: #ffffff; text-decoration: none;
        font-size: 18px; padding: 10px 10px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.25);
        margin-bottom: 12px;
    }

    .nav-links {
        display: flex; flex-direction: column; gap: 6px;
        overflow-y: auto; flex-grow: 1;
        padding-right: 2px;
    }

    .nav-links a {
        text-decoration: none; color: var(--link-text);
        border: 1px solid var(--glass-border);
        border-radius: 8px; padding: 9px 10px; font-size: 14px;
        background: var(--link-bg); transition: .16s ease;
    }

    .nav-links a:hover { transform: translateX(2px); background: var(--link-hover-bg); }
    .nav-links a.active { background: #ffffff; color: #10407f; font-weight: 700; border-color: #fff; }

    .nav-user {
        display: flex; flex-direction: column; gap: 8px;
        margin-top: auto; border-top: 1px solid rgba(255, 255, 255, 0.25);
        padding-top: 12px;
    }

    /* Restored Full-Width Buttons */
    .lang-select, .theme-btn {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid rgba(255, 255, 255, 0.48);
        border-radius: 8px;
        padding: 7px 10px;
        font-size: 13px;
        color: #ffffff;
        background: rgba(17, 24, 39, 0.15);
        cursor: pointer;
        text-align: left;
    }

    .role-chip {
        border-radius: 999px; padding: 6px 10px; font-size: 11px; font-weight: 700;
        background: var(--role-chip-admin-bg); color: var(--role-chip-admin-color);
        width: fit-content;
    }

    /* Fixed User Color Logic */
    .role-chip.user {
        background: var(--role-chip-user-bg) !important;
        color: var(--role-chip-user-color) !important;
    }

    .logout-btn {
        text-decoration: none; background: var(--logout-btn-bg);
        color: var(--logout-btn-color); padding: 8px 10px;
        border-radius: 8px; font-size: 13px; font-weight: 700;
        text-align: center;
    }

    .welcome { font-size: 13px; color: rgba(255, 255, 255, 0.95); line-height: 1.4; }

    @media (max-width: 980px) {
        body { padding: 116px 20px 20px 20px !important; }
        .app-sidebar {
            width: 100%; height: auto; bottom: auto;
            flex-direction: row; align-items: flex-start; overflow-x: auto;
            padding: 10px;
        }
        .nav-links { flex-direction: row; overflow: visible; }
        .nav-links a { white-space: nowrap; }
        .nav-user { margin-top: 0; border-top: 0; padding-top: 0; flex-direction: row; align-items: center; }
        .lang-select, .theme-btn { width: auto; }
        .welcome { display: none; }
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
        
        <!-- Role Chip with dynamic 'user' class -->
        <span class="role-chip <?= (!$isAdminRole) ? 'user' : '' ?>">
            <?= htmlspecialchars($roleLabel) ?>
        </span>
        
        <a class="logout-btn" href="logout.php"><?= htmlspecialchars($langData['logout'] ?? 'Logout'); ?></a>
    </div>
</aside>

<script>
(() => {
    const root = document.documentElement;
    const btn = document.getElementById('themeToggleBtn');
    const key = 'smart_inventory_theme';
    const saved = localStorage.getItem(key);
    const initial = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    root.setAttribute('data-theme', initial);
    if (btn) btn.textContent = initial === 'dark' ? 'Day mode' : 'Night mode';

    if (btn) {
        btn.addEventListener('click', () => {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem(key, next);
            btn.textContent = next === 'dark' ? 'Day mode' : 'Night mode';
        });
    }
})();
</script>