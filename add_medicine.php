<?php
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $dosage_form = $_POST['dosage_form'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $unit = trim($_POST['unit'] ?? '');
    $lot_number = trim($_POST['lot_number'] ?? '');
    $mfg_date = $_POST['mfg_date'] ?? '';
    $exp_date = $_POST['exp_date'] ?? '';
    $cost_price = filter_var($_POST['cost_price'], FILTER_VALIDATE_FLOAT);
    $sell_price = filter_var($_POST['sell_price'], FILTER_VALIDATE_FLOAT);
    $reorder_level = filter_var($_POST['reorder_level'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if (!$name || !$barcode || !$dosage_form || !$category || $quantity === false || !$unit || !$lot_number
        || !$mfg_date || !$exp_date || $cost_price === false || $sell_price === false || $reorder_level === false) {
        $message = "❌ Please fill in all fields correctly.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO medicines 
                (name, barcode, dosage_form, category, quantity, unit, lot_number, mfg_date, exp_date, cost_price, sell_price, reorder_level) 
                VALUES (:name, :barcode, :dosage_form, :category, :quantity, :unit, :lot_number, :mfg_date, :exp_date, :cost_price, :sell_price, :reorder_level)");
            $stmt->execute([
                ':name'=>$name, ':barcode'=>$barcode, ':dosage_form'=>$dosage_form, ':category'=>$category,
                ':quantity'=>$quantity, ':unit'=>$unit, ':lot_number'=>$lot_number,
                ':mfg_date'=>$mfg_date, ':exp_date'=>$exp_date, ':cost_price'=>$cost_price,
                ':sell_price'=>$sell_price, ':reorder_level'=>$reorder_level
            ]);
            header("Location: medicine_list.php");
            exit();
        } catch (PDOException $e) {
            $message = "❌ Failed to add medicine: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Medicine</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

<style>
body {
    background: url('images/ph3.jpg') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    overflow-x: hidden;
    position: relative;
    padding-left: 270px;
    padding-top: 20px;
    padding-right: 20px;
    padding-bottom: 20px;
}
@media (max-width: 980px) {
    body { padding-left: 0; padding-top: 116px; }
}

/* Subtle blur overlay */
body::before {
    content: "";
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(4px);
    z-index: -1;
}

/* Modern frosted card */
.card-modern {
    background: rgba(255,255,255,0.75); /* semi-transparent for frosted look */
    border-radius: 1rem;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    padding: 2rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    backdrop-filter: blur(8px);
}
.card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

/* Form controls */
.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(93, 173, 226,0.25);
    border-color: #5DADE2;
}

.btn-modern {
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}
.btn-modern:hover {
    transform: translateY(-2px);
}

.alert-modern {
    border-radius: 0.8rem;
    font-size: 0.95rem;
}
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container pt-5">
    <div class="card-modern mx-auto" style="max-width: 1000px;">
        <h3 class="mb-4">Add New Medicine</h3>

        <?php if($message): ?>
            <div class="alert alert-danger alert-modern"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3">
            <!-- All form fields remain same -->
            <div class="col-md-6">
                <label for="name" class="form-label">Drug Name (Brand & Generic)</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Paracetamol (Acetaminophen)" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label for="barcode" class="form-label">Barcode</label>
                <input type="text" id="barcode" name="barcode" class="form-control" value="<?= htmlspecialchars($_POST['barcode'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="dosage_form" class="form-label">Dosage Form</label>
                <select id="dosage_form" name="dosage_form" class="form-select" required>
                    <option value="">Choose...</option>
                    <?php
                    $dosage_options = ['Tablet','Capsule','Syrup','Injection','Ointment'];
                    $selected_dosage = $_POST['dosage_form'] ?? '';
                    foreach($dosage_options as $option){
                        $sel = ($selected_dosage==$option)?'selected':'';
                        echo "<option value=\"$option\" $sel>$option</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="category" class="form-label">Category</label>
                <input type="text" id="category" name="category" class="form-control" placeholder="Fever Medicine" value="<?= htmlspecialchars($_POST['category'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" min="0" id="quantity" name="quantity" class="form-control" value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="unit" class="form-label">Unit of Measurement</label>
                <input type="text" id="unit" name="unit" class="form-control" placeholder="e.g., box, ml" value="<?= htmlspecialchars($_POST['unit'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="lot_number" class="form-label">Lot Number</label>
                <input type="text" id="lot_number" name="lot_number" class="form-control" value="<?= htmlspecialchars($_POST['lot_number'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="mfg_date" class="form-label">Manufacturing Date</label>
                <input type="date" id="mfg_date" name="mfg_date" class="form-control" value="<?= htmlspecialchars($_POST['mfg_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="exp_date" class="form-label">Expiry Date</label>
                <input type="date" id="exp_date" name="exp_date" class="form-control" value="<?= htmlspecialchars($_POST['exp_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="cost_price" class="form-label">Cost Price (Ks)</label>
                <input type="number" step="0.01" min="0" id="cost_price" name="cost_price" class="form-control" value="<?= htmlspecialchars($_POST['cost_price'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="sell_price" class="form-label">Selling Price (Ks)</label>
                <input type="number" step="0.01" min="0" id="sell_price" name="sell_price" class="form-control" value="<?= htmlspecialchars($_POST['sell_price'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="reorder_level" class="form-label">Minimum Reorder Level</label>
                <input type="number" min="0" id="reorder_level" name="reorder_level" class="form-control" value="<?= htmlspecialchars($_POST['reorder_level'] ?? '') ?>" required>
            </div>
            <div class="col-12 text-end mt-3">
                <button type="submit" class="btn btn-success btn-modern">💾 Save</button>
                <a href="medicine_list.php" class="btn btn-secondary btn-modern">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
