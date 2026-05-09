<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'navbar.php';

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
<html>
<head>
    <title>Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container mt-5">
<h2>Sales Report (<?= htmlspecialchars($from) ?> to <?= htmlspecialchars($to) ?>)</h2>

<form class="row g-3 mb-4" method="GET">
    <div class="col-auto">
        <label for="from" class="form-label">From:</label>
        <input type="date" id="from" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
    </div>
    <div class="col-auto">
        <label for="to" class="form-label">To:</label>
        <input type="date" id="to" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
    </div>
    <div class="col-auto align-self-end">
        <button type="submit" class="btn btn-primary me-2">Filter</button>
        <a href="sales_report.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<table class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th>Date</th>
            <th>Medicine</th>
            <th>Total Quantity Sold</th>
            <th>Total Sales (Ks)</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($sales): ?>
            <?php foreach ($sales as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['sale_date']) ?></td>
                    <td><?= htmlspecialchars($row['medicine_name']) ?></td>
                    <td><?= htmlspecialchars($row['total_qty']) ?></td>
                    <td><?= number_format($row['total_sales'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4" class="text-center">No sales data found</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
