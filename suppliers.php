<?php
session_start();
require_once 'config/db.php'; // PDO connection

// Check login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Fetch all suppliers
$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Add Supplier form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $contactPerson = trim($_POST['contact_person']);
    $companyName = trim($_POST['company_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $stmt = $pdo->prepare("INSERT INTO suppliers (contact_person, company_name, phone, email, address) VALUES (:contact_person, :company_name, :phone, :email, :address)");
    $stmt->execute([
        'contact_person' => $contactPerson,
        'company_name' => $companyName,
        'phone' => $phone,
        'email' => $email,
        'address' => $address
    ]);

    header('Location: suppliers.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers - Smart Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h2>Suppliers</h2>

    <!-- Add Supplier Button -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSupplierModal">Add Supplier</button>

    <!-- Suppliers Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Contact Person</th>
                <th>Company Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($suppliers): ?>
                <?php foreach ($suppliers as $sup): ?>
                    <tr>
                        <td><?= htmlspecialchars($sup['id']); ?></td>
                        <td><?= htmlspecialchars($sup['contact_person']); ?></td>
                        <td><?= htmlspecialchars($sup['company_name']); ?></td>
                        <td><?= htmlspecialchars($sup['phone']); ?></td>
                        <td><?= htmlspecialchars($sup['email']); ?></td>
                        <td><?= htmlspecialchars($sup['address']); ?></td>
                        <td>
                            <a href="edit_supplier.php?id=<?= $sup['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete_supplier.php?id=<?= $sup['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">No suppliers found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSupplierModalLabel">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input type="text" name="company_name" id="company_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea name="address" id="address" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
