<?php
// Start session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

include 'navbar.php';
require_once 'config/db.php';

// Fetch medicines where quantity is less than reorder_level
$stmt = $pdo->query("
    SELECT id, name, quantity, reorder_level 
    FROM medicines
    WHERE quantity < reorder_level
    ORDER BY quantity ASC
");
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reorder Alerts - Low Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { padding: 20px; background-color: #f9f9f9; }
        h2 { margin-bottom: 30px; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body class="container mt-5">
    <h2>Reorder Alerts - Medicines Low on Stock</h2>

    <?php if ($medicines): ?>
    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>Medicine Name</th>
                <th>Current Quantity</th>
                <th>Reorder Level</th>
                <th>Suggested Reorder Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($medicines as $med): 
                $suggested_qty = $med['reorder_level'] - $med['quantity'];
            ?>
            <tr>
                <td><?= htmlspecialchars($med['name']) ?></td>
                <td><?= htmlspecialchars($med['quantity']) ?></td>
                <td><?= htmlspecialchars($med['reorder_level']) ?></td>
                <td><?= $suggested_qty > 0 ? $suggested_qty : 0 ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert alert-success">All medicines have sufficient stock.</div>
    <?php endif; ?>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
