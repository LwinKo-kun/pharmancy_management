<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

// Load language
$lang = $_SESSION['lang'] ?? 'en';
$langFile = 'lang_' . $lang . '.php';
$langData = file_exists($langFile) ? include($langFile) : include('lang_en.php');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($langData['medicine_list'] ?? 'Medicine List') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        /* --- GLOBAL BACKGROUND (Matches Goods Receipt) --- */
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(180deg, #eef3f8 0%, #f9fcff 100%);
            color: #111827;
            padding-left: 270px;
            padding-top: 20px;
            padding-right: 20px;
            padding-bottom: 20px;
            min-height: 100vh;
        }

        @media (max-width: 980px) {
            body { padding-left: 0; padding-top: 116px; }
        }

        /* --- CONTAINER (Matches Reference Form Color/Shadow) --- */
        .list-container {
            max-width: 1200px;
            width: 100%;
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #cfdae6;
            box-shadow: 0 14px 28px rgba(31, 41, 55, 0.12);
            margin: 28px auto;
        }

        .form-title {
            text-align: center;
            margin-bottom: 25px;
            font-weight: 700;
            color: #1d4ed8;
        }

        .btn-soft { border-radius: 12px; }

        /* --- TABLE STYLING --- */
        .table {
            --bs-table-bg: transparent;
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #f8fafc;
            color: #1d4ed8;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 12px;
        }
        .table td {
            color: #111827;
            padding: 14px 12px;
            vertical-align: middle;
        }

        /* Status colors for Light Mode */
        .expired { background-color: #fef2f2 !important; }
        .expiring-soon { background-color: #fffbeb !important; }

        /* --- DARK MODE OVERRIDES (Strict Table Darkening) --- */
        :root[data-theme="dark"] body {
            background: linear-gradient(180deg, #0b1220 0%, #111a2e 100%);
            color: #e5edf7;
        }

        :root[data-theme="dark"] .list-container {
            background: #111a2e;
            border-color: #26334d;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.35);
        }

        :root[data-theme="dark"] .form-title { color: #b8d0f2; }

        /* FORCING TABLE CELLS TO DARK */
        :root[data-theme="dark"] .table {
            color: #e5edf7;
            border-color: #2b3b5c;
        }

        :root[data-theme="dark"] .table > :not(caption) > * > * {
            background-color: #111a2e !important;
            color: #e5edf7;
            border-color: #2b3b5c;
            box-shadow: inset 0 0 0 9999px transparent;
        }

        :root[data-theme="dark"] .table thead th {
            background-color: #1e293b !important;
            color: #60a5fa !important;
            border-bottom: 2px solid #334155;
        }

        :root[data-theme="dark"] .table-hover tbody tr:hover > * {
            background-color: #1a243a !important;
        }

        /* Dark Mode Status Rows */
        :root[data-theme="dark"] .expired > * {
            background-color: #321616 !important;
            color: #fca5a5 !important;
        }

        :root[data-theme="dark"] .expiring-soon > * {
            background-color: #2d2615 !important;
            color: #fcd34d !important;
        }

        :root[data-theme="dark"] .form-control {
            background: #0e1628;
            border-color: #2b3b5c;
            color: #e5edf7;
        }
        :root[data-theme="dark"] .btn-outline-primary {
            color: #60a5fa;
            border-color: #2b3b5c;
        }
        :root[data-theme="dark"] .btn-outline-primary:hover {
            background: #1e3a8a;
            color: #fff;
        }
        :root[data-theme="dark"] .btn-outline-danger {
            color: #f87171;
            border-color: #2b3b5c;
        }
        :root[data-theme="dark"] .btn-outline-danger:hover {
            background: #dc2626;
            color: #fff;
        }
        :root[data-theme="dark"] .btn-outline-secondary {
            color: #9ca3af;
            border-color: #4b5563;
        }
        :root[data-theme="dark"] .btn-outline-secondary:hover {
            background: #374151;
            color: #fff;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="list-container">
    <h2 class="form-title"><?= htmlspecialchars($langData['medicine_list'] ?? 'Medicine List'); ?></h2>

    <!-- Controls Row -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <a href="add_medicine.php" class="btn btn-primary btn-soft px-4">
            <i class="bi bi-plus-circle me-2"></i><?= $langData['add_medicine'] ?? 'Add Medicine' ?>
        </a>

        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control btn-soft" 
                   placeholder="<?= $langData['search_placeholder'] ?? 'Search...' ?>" 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit" class="btn btn-outline-primary btn-soft"><?= $langData['search'] ?? 'Search' ?></button>
            <a href="medicine_list.php" class="btn btn-outline-secondary btn-soft">Reset</a>
        </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th><?= $langData['name'] ?? 'Name' ?></th>
                    <th><?= $langData['barcode'] ?? 'Barcode' ?></th>
                    <th><?= $langData['category'] ?? 'Category' ?></th>
                    <th><?= $langData['quantity'] ?? 'Qty' ?></th>
                    <th><?= $langData['expiry_date'] ?? 'Expiry' ?></th>
                    <th><?= $langData['sell_price'] ?? 'Price' ?></th>
                    <th class="text-center"><?= $langData['actions'] ?? 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $today = date('Y-m-d');
                    $search = $_GET['search'] ?? '';
                    $sql = "SELECT * FROM medicines";
                    $params = [];

                    if (!empty($search)) {
                        $sql .= " WHERE name LIKE :s OR barcode LIKE :s OR lot_number LIKE :s";
                        $params['s'] = "%$search%";
                    }
                    $sql .= " ORDER BY exp_date ASC";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($medicines):
                        foreach ($medicines as $med):
                            $expiry = $med['exp_date'];
                            $rowClass = '';
                            if ($expiry < $today) {
                                $rowClass = 'expired';
                            } elseif ((strtotime($expiry) - strtotime($today)) <= (30 * 86400)) {
                                $rowClass = 'expiring-soon';
                            }
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td><strong><?= htmlspecialchars($med['name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($med['barcode']) ?></code></td>
                        <td><?= htmlspecialchars($med['category']) ?></td>
                        <td><?= htmlspecialchars($med['quantity']) ?> <small><?= htmlspecialchars($med['unit']) ?></small></td>
                        <td><?= htmlspecialchars($med['exp_date']) ?></td>
                        <td><?= number_format($med['sell_price'], 2) ?> Ks</td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="edit.php?id=<?= $med['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?= $med['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Delete this item?')" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No medicines found matching your search.</td></tr>
                <?php endif;
                } catch (PDOException $e) {
                    echo "<tr><td colspan='7' class='text-danger'>Database Error: " . $e->getMessage() . "</td></tr>";
                } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>