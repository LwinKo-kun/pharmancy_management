<?php
include 'db.php';

$medicine_id = $_POST['medicine_id'];
$quantity = $_POST['quantity'];
$lot_number = $_POST['lot_number'];
$expiry_date = $_POST['expiry_date'];
$supplier = $_POST['supplier'] ?? null;

$stmt = $conn->prepare("INSERT INTO goods_receipts (medicine_id, quantity, lot_number, expiry_date, supplier) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $medicine_id, $quantity, $lot_number, $expiry_date, $supplier);

if ($stmt->execute()) {
    if (isset($_POST['add_more'])) {
        header("Location: goods_receipt_form.php?success=1");
    } else {
        echo "<script>alert('Goods receipt saved successfully!'); window.location.href='dashboard.php';</script>";
    }
} else {
    echo "Error: " . $stmt->error;
}
