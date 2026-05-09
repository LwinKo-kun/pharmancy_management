<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Get supplier ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: suppliers.php');
    exit();
}

// Fetch supplier
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id");
$stmt->execute(['id' => $id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    echo "Supplier not found.";
    exit();
}

// Update supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactPerson = trim($_POST['contact_person']);
    $companyName = trim($_POST['company_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $stmt = $pdo->prepare("UPDATE suppliers 
        SET contact_person = :contact_person,
            company_name = :company_name,
            phone = :phone,
            email = :email,
            address = :address
        WHERE id = :id");
    $stmt->execute([
        'contact_person' => $contactPerson,
        'company_name' => $companyName,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'id' => $id
    ]);

    header('Location: suppliers.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Supplier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2>Edit Supplier</h2>
    <form method="post">
        <div class="mb-3">
            <label for="contact_person" class="form-label">Contact Person</label>
            <input type="text" name="contact_person" id="contact_person" class="form-control" value="<?= htmlspecialchars($supplier['contact_person']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="company_name" class="form-label">Company Name</label>
            <input type="text" name="company_name" id="company_name" class="form-control" value="<?= htmlspecialchars($supplier['company_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone']); ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($supplier['email']); ?>">
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea name="address" id="address" class="form-control"><?= htmlspecialchars($supplier['address']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update Supplier</button>
        <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
