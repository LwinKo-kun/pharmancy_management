<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

$message = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('Invalid ID.');
}

// Fetch existing data
try {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    $medicine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medicine) {
        die('Medicine not found.');
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $dosage_form = $_POST['dosage_form'] ?? '';
    $category = $_POST['category'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = $_POST['unit'] ?? '';
    $lot_number = trim($_POST['lot_number'] ?? '');
    $mfg_date = $_POST['mfg_date'] ?? '';
    $exp_date = $_POST['exp_date'] ?? '';
    $cost_price = floatval($_POST['cost_price'] ?? 0);
    $sell_price = floatval($_POST['sell_price'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 0);

    try {
        $update = $pdo->prepare("UPDATE medicines SET 
            name = :name,
            barcode = :barcode,
            dosage_form = :dosage_form,
            category = :category,
            quantity = :quantity,
            unit = :unit,
            lot_number = :lot_number,
            mfg_date = :mfg_date,
            exp_date = :exp_date,
            cost_price = :cost_price,
            sell_price = :sell_price,
            reorder_level = :reorder_level
            WHERE id = :id");

        $update->execute([
            ':name' => $name,
            ':barcode' => $barcode,
            ':dosage_form' => $dosage_form,
            ':category' => $category,
            ':quantity' => $quantity,
            ':unit' => $unit,
            ':lot_number' => $lot_number,
            ':mfg_date' => $mfg_date,
            ':exp_date' => $exp_date,
            ':cost_price' => $cost_price,
            ':sell_price' => $sell_price,
            ':reorder_level' => $reorder_level,
            ':id' => $id
        ]);

        header("Location: medicine_list.php");
        exit();
    } catch (PDOException $e) {
        $message = "Failed to update medicine: " . $e->getMessage();
    }
}

// Include navbar after session checks
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <title>Edit Medicine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --trust-blue: #2563eb;
            --health-green: #12b981;
            --light-gray: #eef3f8;
            --dark-charcoal: #111827;
            --border-gray: #cfd9e4;
        }
        :root[data-theme="dark"] {
            --light-gray: #0b1220;
            --dark-charcoal: #e5edf7;
            --border-gray: #26334d;
        }
        body {
            background: var(--light-gray);
            color: var(--dark-charcoal);
            padding-left: 270px;
            padding-top: 20px;
            padding-right: 20px;
            padding-bottom: 20px;
        }
        @media (max-width: 980px) {
            body { padding-left: 0; padding-top: 116px; }
        }
        .container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 14px rgba(31, 41, 51, 0.08);
        }
        :root[data-theme="dark"] .container {
            background: #111a2e;
            box-shadow: 0 8px 14px rgba(0, 0, 0, 0.35);
        }
        :root[data-theme="dark"] .form-label {
            color: #b8d0f2;
        }
        :root[data-theme="dark"] .form-control,
        :root[data-theme="dark"] .form-select {
            background: #0e1628;
            border-color: #2b3b5c;
            color: #e5edf7;
        }
        :root[data-theme="dark"] .alert-danger {
            background: #2c1a1a;
            color: #fca5a5;
            border-color: #5c2c2c;
        }
        :root[data-theme="dark"] .btn-secondary {
            background: #3a4a6b;
            color: #e5edf7;
            border-color: #2b3b5c;
        }
        :root[data-theme="dark"] .btn-secondary:hover {
            background: #4b5d7d;
        }
        :root[data-theme="dark"] .btn-primary {
            background: #1e40af;
            color: #fff;
        }
        :root[data-theme="dark"] .btn-primary:hover {
            background: #1e3a8a;
        }
        :root[data-theme="dark"] .table {
            color: #e5edf7;
            border-color: #2b3b5c;
        }
        :root[data-theme="dark"] .table > :not(caption) > * > * {
            background-color: #111a2e !important;
            color: #e5edf7;
            border-color: #2b3b5c;
            box-shadow: inset 0 0 0 9999px transparent;
        }
        :root[data-theme="dark"] .table thead th {
            background-color: #1e293b !important;
            color: #60a5fa !important;
            border-bottom: 2px solid #334155;
        }
        :root[data-theme="dark"] .table-hover tbody tr:hover > * {
            background-color: #1a243a !important;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2>Edit Medicine</h2>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Drug Name (Brand & Generic)</label>
            <input type="text" name="name" required class="form-control" value="<?= htmlspecialchars($medicine['name']) ?>" />
        </div>
        <div class="col-md-6">
            <label class="form-label">Barcode</label>
            <input type="text" name="barcode" required class="form-control" value="<?= htmlspecialchars($medicine['barcode']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Dosage Form</label>
            <select name="dosage_form" class="form-select" required>
                <option value="">Choose...</option>
                <?php
                $dosage_options = ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Ointment'];
                foreach ($dosage_options as $option) {
                    $selected = ($medicine['dosage_form'] === $option) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($option) . "\" $selected>" . htmlspecialchars($option) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Category</label>
            <input type="text" name="category" required class="form-control" value="<?= htmlspecialchars($medicine['category']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" required class="form-control" min="0" value="<?= (int)$medicine['quantity'] ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Unit of Measurement</label>
            <input type="text" name="unit" required class="form-control" value="<?= htmlspecialchars($medicine['unit']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Lot Number</label>
            <input type="text" name="lot_number" required class="form-control" value="<?= htmlspecialchars($medicine['lot_number']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Manufacturing Date</label>
            <input type="date" name="mfg_date" required class="form-control" value="<?= htmlspecialchars($medicine['mfg_date']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="exp_date" required class="form-control" value="<?= htmlspecialchars($medicine['exp_date']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Cost Price (Ks)</label>
            <input type="number" step="0.01" name="cost_price" required class="form-control" min="0" value="<?= htmlspecialchars($medicine['cost_price']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Selling Price (Ks)</label>
            <input type="number" step="0.01" name="sell_price" required class="form-control" min="0" value="<?= htmlspecialchars($medicine['sell_price']) ?>" />
        </div>
        <div class="col-md-4">
            <label class="form-label">Minimum Reorder Level</label>
            <input type="number" name="reorder_level" required class="form-control" min="0" value="<?= (int)$medicine['reorder_level'] ?>" />
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">💾 Update</button>
            <a href="medicine_list.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
