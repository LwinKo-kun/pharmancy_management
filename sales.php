<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

$lang = $_SESSION['lang'] ?? 'en';
$langFile = ($lang === 'mm') ? 'lang_mm.php' : 'lang_en.php';
$langData = include $langFile;

$username = $_SESSION['username'] ?? 'User';

// Fetch all medicines for autocomplete
$stmt = $pdo->query("SELECT id, name FROM medicines ORDER BY name");
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale & Disposal - Smart Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(180deg, #eef3f8 0%, #f9fcff 100%);
            color: #111827;
            padding-left: 270px;
            padding-top: 20px;
            padding-right: 20px;
            padding-bottom: 20px;
            transition: all 0.3s ease;
        }

        @media (max-width: 980px) {
            body { padding-left: 0; padding-top: 116px; }
        }

        /* Glassmorphism Containers */
        .form-section {
            background: #ffffff;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 16px;
            border: 1px solid #cfdae6;
            box-shadow: 0 14px 28px rgba(31, 41, 55, 0.12);
        }

        h2.section-title {
            font-weight: 700;
            margin-bottom: 25px;
            color: #1d4ed8;
        }

        .btn-custom {
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-custom:hover {
            transform: translateY(-1px);
        }

        /* Dark Mode Reference Styles */
        :root[data-theme="dark"] body {
            background: linear-gradient(180deg, #0b1220 0%, #111a2e 100%);
            color: #e5edf7;
        }

        :root[data-theme="dark"] .form-section {
            background: #111a2e;
            border-color: #26334d;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.35);
        }

        :root[data-theme="dark"] .section-title,
        :root[data-theme="dark"] h3,
        :root[data-theme="dark"] .form-label {
            color: #b8d0f2;
        }

        :root[data-theme="dark"] .form-control,
        :root[data-theme="dark"] .form-select {
            background: #0e1628;
            border-color: #2b3b5c;
            color: #e5edf7;
        }

        :root[data-theme="dark"] .form-control[readonly] {
            background: #1a243a;
            color: #60a5fa;
            border-color: #2b3b5c;
        }

        /* Table Styling for Dark Mode */
        :root[data-theme="dark"] .table {
            color: #e5edf7;
            border-color: #2b3b5c;
        }

        :root[data-theme="dark"] .table-light {
            background: #1e293b;
            color: #b8d0f2;
        }

        :root[data-theme="dark"] .table-striped>tbody>tr:nth-of-type(odd) {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.02);
        }

        :root[data-theme="dark"] .table-info {
            background: #1e3a8a !important;
            color: #fff;
        }

        /* Mobile Adjustments */
        @media (max-width: 576px) {
            .form-section { padding: 20px 15px; }
            h2.section-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid" style="max-width: 1100px;">

        <!-- Sale Form Section -->
        <div class="form-section">
            <h2 class="section-title">Record Sale</h2>
            <form id="saleForm" class="row g-3" autocomplete="off">
                <div class="col-md-6">
                    <label for="medicine" class="form-label">Select Medicine</label>
                    <input type="text" id="medicine" class="form-control" list="medicineList" placeholder="Type medicine name..." required />
                    <datalist id="medicineList">
                        <?php foreach ($medicines as $med): ?>
                            <option data-id="<?= htmlspecialchars($med['id']); ?>" value="<?= htmlspecialchars($med['name']); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label for="lot_number" class="form-label">Lot Number</label>
                    <select id="lot_number" class="form-select" required>
                        <option value="">Select medicine first</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="selling_price" class="form-label">Price (Ks)</label>
                    <input type="number" id="selling_price" class="form-control" step="0.01" required />
                </div>
                <div class="col-md-2">
                    <label for="quantity" class="form-label">Qty</label>
                    <input type="number" id="quantity" class="form-control" min="1" value="1" required />
                </div>
                <div class="col-md-2">
                    <label for="discount" class="form-label">Disc (%)</label>
                    <input type="number" id="discount" class="form-control" min="0" max="100" value="0" />
                </div>
                <div class="col-md-4">
                    <label for="total_amount" class="form-label">Total Amount</label>
                    <input type="text" id="total_amount" class="form-control" readonly />
                </div>
                <div class="col-md-4">
                    <label for="payment_method" class="form-label">Payment</label>
                    <select id="payment_method" class="form-select" required>
                        <option value="Cash">Cash</option>
                        <option value="KBZ Pay">KBZ Pay</option>
                        <option value="WaveMoney">WaveMoney</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="button" id="addToCart" class="btn btn-primary btn-custom px-4">Add to Cart</button>
                </div>
            </form>

            <hr class="my-4" style="border-color: #2b3b5c;">

            <h3 class="mb-3">Sale Cart</h3>
            <div class="table-responsive">
                <table class="table cart-table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Lot</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Disc%</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr><td colspan="7" class="text-center opacity-50">No items added</td></tr>
                    </tbody>
                </table>
            </div>
            <button id="checkoutBtn" class="btn btn-success btn-custom w-100" disabled>Checkout & Print Receipt</button>
        </div>

        <!-- Disposal Form Section -->
        <div class="form-section">
            <h2 class="section-title" style="color: #f59e0b;">Record Disposal</h2>
            <form id="disposalForm" class="row g-3" autocomplete="off">
                <div class="col-md-5">
                    <label for="disposal_medicine" class="form-label">Select Medicine</label>
                    <input type="text" id="disposal_medicine" class="form-control" list="medicineList" required />
                </div>
                <div class="col-md-3">
                    <label for="disposal_lot_number" class="form-label">Lot Number</label>
                    <select id="disposal_lot_number" class="form-select" required>
                        <option value="">Select medicine first</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="disposal_quantity" class="form-label">Qty</label>
                    <input type="number" id="disposal_quantity" class="form-control" min="1" value="1" required />
                </div>
                <div class="col-md-2">
                    <label for="disposal_reason" class="form-label">Reason</label>
                    <select id="disposal_reason" class="form-select" required>
                        <option value="Expired">Expired</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="button" id="addToDisposalCart" class="btn btn-warning btn-custom px-4 text-dark">Add to Disposal List</button>
                </div>
            </form>

            <hr class="my-4" style="border-color: #2b3b5c;">

            <h3 class="mb-3">Disposal List</h3>
            <div class="table-responsive">
                <table class="table cart-table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Lot Number</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="disposalCartBody">
                        <tr><td colspan="5" class="text-center opacity-50">No items added</td></tr>
                    </tbody>
                </table>
            </div>
            <button id="disposalCheckoutBtn" class="btn btn-danger btn-custom w-100" disabled>Submit Disposal Records</button>
        </div>
    </div>

    <!-- JavaScript Logic stays the same as your original provided code -->
    <script>
        // ... (Keep your existing JS logic for fetchLotNumbers, cart management, etc.) ...
        // Note: Make sure fetchLotNumbers points to the correct 'get_lots.php'
    </script>
</body>
</html>