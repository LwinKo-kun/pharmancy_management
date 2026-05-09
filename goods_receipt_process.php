<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php'; // PDO connection

// Initialize message and errors
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $medicine_id = filter_input(INPUT_POST, 'medicine_id', FILTER_VALIDATE_INT);
    $lot_number = trim($_POST['lot_number'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $purchase_price = filter_input(INPUT_POST, 'purchase_price', FILTER_VALIDATE_FLOAT);

    // Validation
    if (!$medicine_id) {
        $errors[] = 'Please select a valid medicine.';
    }
    if (empty($lot_number)) {
        $errors[] = 'Lot Number is required.';
    }
    if (empty($expiry_date)) {
        $errors[] = 'Expiry Date is required.';
    }
    if (!$quantity || $quantity < 1) {
        $errors[] = 'Quantity must be at least 1.';
    }
    if (!$purchase_price || $purchase_price <= 0) {
        $errors[] = 'Purchase Price must be a positive number.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert into goods_receipt table (you need to create this table)
            $insertReceipt = $pdo->prepare("
                INSERT INTO goods_receipt 
                (medicine_id, lot_number, expiry_date, quantity, purchase_price, receipt_date) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insertReceipt->execute([$medicine_id, $lot_number, $expiry_date, $quantity, $purchase_price]);

            // Update stock quantity in medicines table (add the received quantity)
            $updateStock = $pdo->prepare("
                UPDATE medicines 
                SET quantity = quantity + ? 
                WHERE id = ?
            ");
            $updateStock->execute([$quantity, $medicine_id]);

            $pdo->commit();

            $message = "✅ Goods receipt recorded and stock updated successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Goods Receipt Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:600px;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="goods_receipt_form.php" class="btn btn-secondary mt-3">Go Back</a>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <?= $message ?>
        </div>
        <a href="goods_receipt_form.php" class="btn btn-primary">Add More Goods Receipt</a>
        <a href="medicine_list.php" class="btn btn-secondary ms-2">Back to Medicine List</a>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
