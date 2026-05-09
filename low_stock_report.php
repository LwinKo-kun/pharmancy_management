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
