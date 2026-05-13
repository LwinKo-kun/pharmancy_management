<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

// Date filtering: use GET params or default to current month
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// Prepare and execute query with JOIN and grouping by medicine & date
$stmt = $pdo->prepare("
    SELECT m.name AS medicine_name,
           DATE(s.sold_at) AS sale_date,
           SUM(s.quantity) AS total_qty,
           SUM(s.total_amount) AS total_sales
    FROM sales s
    JOIN medicines m ON s.medicine_id = m.id
    WHERE DATE(s.sold_at) BETWEEN :from AND :to
    GROUP BY s.medicine_id, DATE(s.sold_at)
    ORDER BY sale_date ASC, total_qty DESC
");
$stmt->execute([
    ':from' => $from,
    ':to' => $to
]);

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
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

        .report-container {
            max-width: 1000px;
            width: 100%;
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #cfdae6;
            box-shadow: 0 14px 28px rgba(31, 41, 55, 0.12);
            margin: 28px auto;
        }

        .page-title {
            font-weight: 700;
            color: #1d4ed8;
            margin-bottom: 25px;
        }

        .btn-soft { border-radius: 12px; font-weight: 500; }

        /* Dark Mode Styles */
        :root[data-theme="dark"] body {
            background: linear-gradient(180deg, #0b1220 0%, #111a2e 100%);
            color: #e5edf7;
        }

        :root[data-theme="dark"] .report-container {
            background: #111a2e;
            border-color: #26334d;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.35);
        }

        :root[data-theme="dark"] .page-title,
        :root[data-theme="dark"] .form-label {
            color: #b8d0f2;
        }

        :root[data-theme="dark"] .form-control {
            background: #0e1628;
            border-color: #2b3b5c;
            color: #e5edf7;
        }

        /* Table Dark Theme */
        :root[data-theme="dark"] .table {
            color: #e5edf7;
            border-color: #2b3b5c;
        }

        :root[data-theme="dark"] .table thead {
            background-color: #1e293b;
            color: #b8d0f2;
        }

        :root[data-theme="dark"] .table-striped>tbody>tr:nth-of-type(odd) {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.03);
            color: #e5edf7;
        }
        
        :root[data-theme="dark"] .table-bordered td, 
        :root[data-theme="dark"] .table-bordered th {
            border-color: #2b3b5c;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="report-container">
    <h2 class="page-title text-center">Sales Report</h2>
    
    <!-- Filter Form -->
    <form class="row g-3 mb-4 justify-content-center" method="GET">
        <div class="col-md-4">
            <label for="from" class="form-label">From Date</label>
            <input type="date" id="from" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="col-md-4">
            <label for="to" class="form-label">To Date</label>
            <input type="date" id="to" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-soft w-100 me-2">Filter</button>
            <a href="sales_report.php" class="btn btn-secondary btn-soft w-100">Reset</a>
        </div>
    </form>

    <div class="table-responsive mt-4">
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Medicine</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-end">Total (Ks)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sales): ?>
                    <?php foreach ($sales as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['sale_date']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['medicine_name']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['total_qty']) ?></td>
                            <td class="text-end"><?= number_format($row['total_sales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">No sales data found for this period</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>