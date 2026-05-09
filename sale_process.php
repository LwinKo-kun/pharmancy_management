<?php
session_start();
require_once 'config/db.php'; // PDO connection

// Check if user logged in
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cart']) || !is_array($data['cart']) || count($data['cart']) === 0) {
    http_response_code(400);
    echo "Invalid sale data.";
    exit;
}

$cart = $data['cart'];

// Begin transaction
$pdo->beginTransaction();

try {
    foreach ($cart as $item) {
        $medicineName = $item['medicine'];
        $lotNumber = $item['lot'];
        $quantity = intval($item['qty']);
        $sellingPrice = floatval($item['price']);
        $discountPercent = floatval($item['discount']);
        $totalAmount = floatval($item['total']);

        if ($quantity <= 0) throw new Exception("Invalid quantity");

        // Find medicine record by name and lot_number
        $stmt = $pdo->prepare("SELECT id, quantity FROM medicines WHERE name = :name AND lot_number = :lot LIMIT 1");
        $stmt->execute(['name' => $medicineName, 'lot' => $lotNumber]);
        $medicine = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$medicine) {
            throw new Exception("Medicine or lot not found: $medicineName / $lotNumber");
        }

        if ($medicine['quantity'] < $quantity) {
            throw new Exception("Not enough stock for $medicineName (Lot: $lotNumber). Available: {$medicine['quantity']}");
        }

        // Deduct stock
        $newQty = $medicine['quantity'] - $quantity;
        $stmt = $pdo->prepare("UPDATE medicines SET quantity = :qty WHERE id = :id");
        $stmt->execute(['qty' => $newQty, 'id' => $medicine['id']]);

        // Record sale in sales table
        $stmt = $pdo->prepare("INSERT INTO sales (medicine_id, lot_number, quantity, selling_price, discount_percent, total_amount, sold_by, sold_at) VALUES (:medicine_id, :lot_number, :quantity, :selling_price, :discount, :total, :sold_by, NOW())");
        $stmt->execute([
            'medicine_id' => $medicine['id'],
            'lot_number' => $lotNumber,
            'quantity' => $quantity,
            'selling_price' => $sellingPrice,
            'discount' => $discountPercent,
            'total' => $totalAmount,
            'sold_by' => $_SESSION['username']
        ]);
    }

    $pdo->commit();
    echo "Sale recorded successfully!";
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
