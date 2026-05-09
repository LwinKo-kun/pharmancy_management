<?php
session_start();
include 'navbar.php';
require_once 'config/db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (
    !isset($data['medicine'], $data['lot_number'], $data['quantity'], $data['reason']) ||
    empty($data['medicine']) || empty($data['lot_number']) || empty($data['reason']) ||
    !is_numeric($data['quantity']) || intval($data['quantity']) <= 0
) {
    http_response_code(400);
    echo "Invalid input data.";
    exit;
}

$medicineName = $data['medicine'];
$lotNumber = $data['lot_number'];
$quantity = intval($data['quantity']);
$reason = $data['reason'];

try {
    // Find medicine entry with lot number
    $stmt = $pdo->prepare("SELECT id, quantity FROM medicines WHERE name = :name AND lot_number = :lot LIMIT 1");
    $stmt->execute(['name' => $medicineName, 'lot' => $lotNumber]);
    $medicine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medicine) {
        http_response_code(404);
        echo "Medicine or lot number not found.";
        exit;
    }

    if ($medicine['quantity'] < $quantity) {
        http_response_code(400);
        echo "Insufficient stock to record wastage.";
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Update stock quantity
    $newQty = $medicine['quantity'] - $quantity;
    $stmt = $pdo->prepare("UPDATE medicines SET quantity = :qty WHERE id = :id");
    $stmt->execute(['qty' => $newQty, 'id' => $medicine['id']]);

    // Insert into wastage/disposal table
    $stmt = $pdo->prepare("INSERT INTO wastage (medicine_id, lot_number, quantity, reason, recorded_by, recorded_at) VALUES (:medicine_id, :lot_number, :quantity, :reason, :recorded_by, NOW())");
    $stmt->execute([
        'medicine_id' => $medicine['id'],
        'lot_number' => $lotNumber,
        'quantity' => $quantity,
        'reason' => $reason,
        'recorded_by' => $_SESSION['username']
    ]);

    $pdo->commit();

    echo "Wastage recorded successfully!";
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>
