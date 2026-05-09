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

$stmt = $pdo->query("
    SELECT medicine_name, lot_number, quantity, reason, recorded_at
    FROM disposals
    ORDER BY recorded_at DESC
");
$wastes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Waste Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
<h2>Waste / Disposal Report</h2>

<table class="table table-bordered table-striped mt-3">
    <thead class="table-light">
        <tr>
            <th>Medicine</th>
            <th>Lot Number</th>
            <th>Quantity</th>
            <th>Reason</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($wastes): ?>
            <?php foreach ($wastes as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['medicine_name']) ?></td>
                    <td><?= htmlspecialchars($row['lot_number']) ?></td>
                    <td><?= htmlspecialchars($row['quantity']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= htmlspecialchars($row['recorded_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center">No waste records</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
