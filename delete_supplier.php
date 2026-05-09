<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

header('Location: suppliers.php');
exit();
