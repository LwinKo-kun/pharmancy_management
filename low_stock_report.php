<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';
include 'navbar.php';

try {
    $stmt = $pdo->query("
        SELECT name, lot_number, quantity, reorder_level
        FROM medicines
        WHERE quantity < reorder_level
        ORDER BY quantity ASC
    ");
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Low Stock Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --trust-blue: #2563eb;
            --health-green: #12b981;
            --light-gray: #eef3f8;
            --dark-charcoal: #111827;
            --border-gray: #cfd9e4;
        }
        :root[data-theme="dark"] {
            --light-gray: #0b1220;
            --dark-charcoal: #e5edf7;
            --border-gray: #26334d;
        }
        body {
            background: var(--light-gray);
            color: var(--dark-charcoal);
            padding-left: 270px;
            padding-top: 20px;
            padding-right: 20px;
            padding-bottom: 20px;
        }
        @media (max-width: 980px) {
            body { padding-left: 0; padding-top: 116px; }
        }
        .table {
            background: #fff;
            border-color: var(--border-gray);
        }
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
    </style>
</head>
<body class="container mt-5">
<h2>Low Stock Medicines</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Medicine</th>
            <th>Lot Number</th>
            <th>Quantity</th>
            <th>Reorder Level</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($medicines): ?>
            <?php foreach ($medicines as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['lot_number']) ?></td>
                    <td><?= htmlspecialchars($row['quantity']) ?></td>
                    <td><?= htmlspecialchars($row['reorder_level']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4" class="text-center">No low stock medicines</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
