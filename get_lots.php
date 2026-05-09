<?php
require_once 'config/db.php'; // PDO connection

if (!isset($_GET['medicine']) || empty($_GET['medicine'])) {
    echo json_encode([]);
    exit;
}

$medicineName = $_GET['medicine'];

$stmt = $pdo->prepare("SELECT lot_number, sell_price FROM medicines WHERE name = :name");
$stmt->execute(['name' => $medicineName]);
$lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($lots);
