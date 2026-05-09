<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

require_once 'config/db.php'; // PDO connection

// Expect JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['disposalCart']) || !is_array($data['disposalCart'])) {
    http_response_code(400);
    echo "Invalid input data";
    exit();
}

$disposalCart = $data['disposalCart'];

try {
    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO disposals 
        (medicine_name, lot_number, quantity, reason, recorded_by, recorded_at) 
        VALUES (:medicine_name, :lot_number, :quantity, :reason, :recorded_by, NOW())
    ");

    $updateStockStmt = $pdo->prepare("
        UPDATE medicines 
        SET quantity = quantity - :qty 
        WHERE name = :medicine AND lot_number = :lot AND quantity >= :qty
    ");

    foreach ($disposalCart as $item) {
        // Basic validation and sanitization
        $medicine = $item['medicine'] ?? '';
        $lot = $item['lot'] ?? '';
        $qty = (int)($item['qty'] ?? 0);
        $reason = $item['reason'] ?? '';

        if (!$medicine || !$lot || $qty <= 0 || !$reason) {
            throw new Exception("Invalid disposal item data");
        }

        // Update stock - ensure quantity is enough
        $updateStockStmt->execute([
            ':qty' => $qty,
            ':medicine' => $medicine,
            ':lot' => $lot
        ]);

        if ($updateStockStmt->rowCount() === 0) {
            // Not enough stock or medicine/lot not found
            throw new Exception("Insufficient stock for $medicine (Lot: $lot) to dispose.");
        }

        // Insert disposal record
        $insertStmt->execute([
            ':medicine_name' => $medicine,
            ':lot_number' => $lot,
            ':quantity' => $qty,
            ':reason' => $reason,
            ':recorded_by' => $_SESSION['username']
        ]);
    }

    $pdo->commit();

    echo "Disposal records saved successfully.";

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Error saving disposal records: " . $e->getMessage();
}
