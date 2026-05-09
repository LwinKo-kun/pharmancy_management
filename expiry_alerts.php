<?php
// Start session at the very top before any output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$today = date('Y-m-d');
$targetDate = date('Y-m-d', strtotime("+$days days"));

$stmt = $pdo->prepare("
    SELECT name, lot_number, exp_date, quantity
    FROM medicines
    WHERE exp_date BETWEEN :today AND :target
    ORDER BY exp_date ASC
");
$stmt->execute([
    ':today' => $today,
    ':target' => $targetDate
]);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Now include navbar.php (which may output HTML)
include 'navbar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expiry Alerts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
<h2>Medicines Expiring in Next <?= htmlspecialchars($days) ?> Days</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Medicine</th>
            <th>Lot Number</th>
            <th>Expiry Date</th>
            <th>Quantity</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($medicines): ?>
            <?php foreach ($medicines as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['lot_number']) ?></td>
                    <td><?= htmlspecialchars($row['exp_date']) ?></td>
                    <td><?= htmlspecialchars($row['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4" class="text-center">No records found</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>