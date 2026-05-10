<?php
session_start();
require_once 'config/db.php';

$error = '';

function normalize_hash(?string $hash): string
{
    return trim(str_replace(["\r", "\n"], '', (string)$hash));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username နဲ့ Password နှစ်ခုလုံးဖြည့်ပါ။";
    } else {
        // Support both modern and legacy schemas by querying only existing columns.
        $availableColumns = [];
        $columnStmt = $pdo->query("SHOW COLUMNS FROM users");
        foreach ($columnStmt->fetchAll(PDO::FETCH_ASSOC) as $columnMeta) {
            $availableColumns[$columnMeta['Field']] = true;
        }

        $lookupColumns = [];
        foreach (['display_name', 'email', 'username'] as $candidate) {
            if (isset($availableColumns[$candidate])) {
                $lookupColumns[] = $candidate;
            }
        }

        if (empty($lookupColumns)) {
            $error = "Login configuration error: users table columns are missing.";
            $user = null;
        } else {
            $where = implode(' OR ', array_map(static fn($c) => "`$c` = :identifier", $lookupColumns));
            $stmt = $pdo->prepare("SELECT * FROM users WHERE $where LIMIT 1");
            $stmt->execute(['identifier' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $modernHash = normalize_hash($user['password_hash'] ?? '');
        $legacyHash = normalize_hash($user['password'] ?? '');
        $passwordValid = false;

        if ($modernHash !== '') {
            $passwordValid = password_verify($password, $modernHash);
        } elseif ($legacyHash !== '') {
            $passwordValid = password_verify($password, $legacyHash);
        }

        if ($user && $passwordValid) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'] ?? $user['display_name'] ?? $user['email'] ?? $username;
            $_SESSION['display_name'] = $user['display_name'] ?? $_SESSION['username'];
            $_SESSION['tenant_id'] = $user['tenant_id'] ?? null;
            $_SESSION['role'] = $user['role'] ?? (strtolower($_SESSION['username']) === 'admin' ? 'admin' : 'user');
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "အသုံးပြုသူအမည် (Username) သို့မဟုတ် စကားဝှက် (Password) မမှန်ပါ။";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Inventory Login</title>
    <style>
        :root {
            /* Primary Colors */
            --trust-blue: #2563eb;
            --health-green: #12b981;
            --warning-amber: #f59e0b;
            --danger-red: #ef4444;
            --vivid-cyan: #06b6d4;
            
            /* Light Theme */
            --light-gray: #eef3f8;
            --dark-charcoal: #111827;
            --border-gray: #cfd9e4;
            --page-bg: #eef3f8;
            --page-text: #111827;
            --muted-text: #5b6a79;
            --panel-bg: #ffffff;
            
            /* Cards & Panels */
            --card-bg: #ffffff;
            --card-border: #cfd9e4;
            --card-shadow: 0 8px 14px rgba(31, 41, 51, 0.08);
            
            /* Inputs */
            --input-bg: #ffffff;
            --input-border: #cfd9e4;
            --input-text: #111827;
        }
        :root[data-theme="dark"] {
            /* Dark Theme */
            --light-gray: #0b1220;
            --dark-charcoal: #e5edf7;
            --border-gray: #26334d;
            --page-bg: #0b1220;
            --page-text: #e5edf7;
            --muted-text: #9cb0c8;
            --panel-bg: #111a2e;
            
            /* Cards & Panels */
            --card-bg: #111a2e;
            --card-border: #26334d;
            --card-shadow: 0 8px 14px rgba(15, 23, 42, 0.28);
            
            /* Inputs */
            --input-bg: #0e1628;
            --input-border: #3a4a6b;
            --input-text: #e5edf7;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-gray);
            color: var(--dark-charcoal);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .login-shell {
            width: 100%;
            max-width: 980px;
            background: var(--panel-bg);
            border: 1px solid var(--border-gray);
            border-radius: 18px;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            overflow: hidden;
            box-shadow: 0 18px 36px rgba(31, 41, 51, 0.12);
        }
        .login-brand {
            padding: 36px;
            background: linear-gradient(160deg, #e8f0ff 0%, #e8fff6 100%);
            border-right: 1px solid var(--border-gray);
        }
        .brand-pill {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: #dce9ff;
            color: #16499b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .login-brand h1 {
            margin: 14px 0 10px;
            font-size: 32px;
            line-height: 1.2;
        }
        .login-brand p {
            margin: 0 0 22px;
            color: #344657;
            line-height: 1.6;
        }
        .theme-note {
            margin: 0;
            padding: 12px 14px;
            border-left: 4px solid var(--health-green);
            background: #ecfbf4;
            border-radius: 8px;
            font-size: 14px;
        }
        .login-panel {
            padding: 34px 28px;
        }
        .login-panel h2 {
            margin: 0 0 6px;
            font-size: 24px;
        }
        .login-panel .sub {
            margin: 0 0 22px;
            color: var(--muted-text);
            font-size: 14px;
        }
        .error-box {
            background: #fdeceb;
            border: 1px solid #f2c1be;
            color: #8b1f18;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 14px;
        }
        .field {
            margin-bottom: 14px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            border: 1px solid var(--border-gray);
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 15px;
            outline: none;
            transition: .2s border-color, .2s box-shadow;
        }
        input:focus {
            border-color: var(--trust-blue);
            box-shadow: 0 0 0 3px rgba(31, 111, 235, 0.18);
        }
        .row-inline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
            font-size: 14px;
        }
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5a6a;
        }
        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .01em;
            color: #fff;
            background: linear-gradient(90deg, var(--trust-blue), var(--health-green));
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(31, 111, 235, 0.28);
        }
        .theme-toggle-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }
        .theme-toggle {
            border: 1px solid var(--border-gray);
            background: transparent;
            color: var(--dark-charcoal);
            border-radius: 9px;
            padding: 6px 10px;
            font-size: 13px;
            cursor: pointer;
        }
        :root[data-theme="dark"] .theme-toggle {
            background: #0e1628;
            border-color: #3a4a6b;
            color: #e5edf7;
        }
        :root[data-theme="dark"] .theme-note {
            background: #112f27;
            color: #b3e8d7;
            border-left-color: #1f9d70;
        }
        :root[data-theme="dark"] input[type="text"],
        :root[data-theme="dark"] input[type="password"] {
            background: #0e1628;
            color: #e5edf7;
            border-color: #3a4a6b;
        }
        :root[data-theme="dark"] input:focus {
            border-color: var(--trust-blue);
            box-shadow: 0 0 0 3px rgba(31, 111, 235, 0.3);
        }
        :root[data-theme="dark"] .login-brand {
            background: linear-gradient(160deg, #0f1a3a 0%, #0a2a24 100%);
            color: #e5edf7;
        }
        :root[data-theme="dark"] .login-brand p {
            color: #b3c5d7;
        }
        :root[data-theme="dark"] .brand-pill {
            background: #1a2c5a;
            color: #8fb4ff;
        }
        :root[data-theme="dark"] .error-box {
            background: #2c1a1a;
            border-color: #5c2c2c;
            color: #ffa8a8;
        }
        :root[data-theme="dark"] .role-hint {
            color: #94a3b8;
            border-top-color: #3a4a6b;
        }
        .role-hint {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px dashed var(--border-gray);
            color: #5c6c7b;
            font-size: 13px;
        }
        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }
            .login-brand {
                border-right: 0;
                border-bottom: 1px solid var(--border-gray);
            }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="login-brand">
            <span class="brand-pill">Smart Inventory</span>
            <h1>Secure access for modern pharmacy operations</h1>
            <p>Clean interface, role-based workflows, and reliable stock control for both administrators and staff users.</p>
            <p class="theme-note">Theme colors: Trust Blue, Health Green, Light Gray, and Dark Charcoal.</p>
        </section>
        <section class="login-panel">
            <div class="theme-toggle-wrap">
                <button type="button" class="theme-toggle" id="themeToggleBtn">Night mode</button>
            </div>
            <h2>Sign in</h2>
            <p class="sub">Use your username, display name, or email.</p>
            <?php if ($error): ?>
                <div class="error-box"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="field">
                    <label for="username">Username / Display Name / Email</label>
                    <input type="text" id="username" name="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="row-inline">
                    <label class="remember" for="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Remember me</span>
                    </label>
                </div>
                <button class="btn-login" type="submit">Login to Dashboard</button>
            </form>
            <p class="role-hint">Admins get system control tools. Users get daily pharmacy operation tools.</p>
        </section>
    </main>
    <script>
    (() => {
        const root = document.documentElement;
        const key = 'smart_inventory_theme';
        const btn = document.getElementById('themeToggleBtn');
        const saved = localStorage.getItem(key);
        const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const initial = saved || (systemDark ? 'dark' : 'light');
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
</body>
</html>
