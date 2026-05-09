<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php'; // Your PDO database connection

// Get the medicine ID from the URL, sanitize it as integer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('Invalid ID.');
}

try {
    // Prepare and execute the delete query safely
    $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$id]);

    // Redirect back to the medicine list after deletion
    header("Location: medicine_list.php");
    exit();
} catch (PDOException $e) {
    // Handle any database errors gracefully
    die("Database error: " . $e->getMessage());
}
?>
