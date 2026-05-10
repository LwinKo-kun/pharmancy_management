<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$lang = $_SESSION['lang'] ?? 'en';
$langFile = ($lang === 'mm') ? 'lang_mm.php' : 'lang_en.php';
$langData = include $langFile;

$username = $_SESSION['username'] ?? 'User';

require_once 'config/db.php'; // PDO connection

// Fetch all medicines for autocomplete
$stmt = $pdo->query("SELECT id, name FROM medicines ORDER BY name");
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale & Disposal - Smart Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-left: 270px;
            padding-top: 20px;
            padding-right: 20px;
            padding-bottom: 20px;
            background: url('background.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        @media (max-width: 980px) {
            body { padding-left: 0; padding-top: 116px; }
        }

        .form-section {
            background-color: rgba(255,255,255,0.95);
            padding: 20px;
            margin-bottom: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        h2.section-title {
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .cart-table td, .cart-table th {
            vertical-align: middle;
        }

        .btn-custom {
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        @media (max-width: 576px) {
            .form-section {
                padding: 15px;
            }
            .cart-table th, .cart-table td {
                font-size: 0.85rem;
            }
            h2.section-title {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">

        <!-- Sale Form Section -->
        <div class="form-section">
            <h2 class="section-title">Record Sale</h2>
            <form id="saleForm" class="row g-3" autocomplete="off">
                <div class="col-md-6">
                    <label for="medicine" class="form-label">Select Medicine</label>
                    <input type="text" id="medicine" name="medicine" class="form-control" list="medicineList" required />
                    <datalist id="medicineList">
                        <?php foreach ($medicines as $med): ?>
                            <option data-id="<?= htmlspecialchars($med['id']); ?>" value="<?= htmlspecialchars($med['name']); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label for="lot_number" class="form-label">Lot Number</label>
                    <select id="lot_number" name="lot_number" class="form-select" required>
                        <option value="">Select medicine first</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="selling_price" class="form-label">Selling Price (Ks)</label>
                    <input type="number" id="selling_price" name="selling_price" class="form-control" step="0.01" min="0" required />
                </div>
                <div class="col-md-2">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1" required />
                </div>
                <div class="col-md-2">
                    <label for="discount" class="form-label">Discount (%)</label>
                    <input type="number" id="discount" name="discount" class="form-control" min="0" max="100" value="0" />
                </div>
                <div class="col-md-4">
                    <label for="total_amount" class="form-label">Total Amount (Ks)</label>
                    <input type="text" id="total_amount" class="form-control" readonly />
                </div>
                <div class="col-md-4">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-select" required>
                        <option value="">Select payment method</option>
                        <option value="Cash">Cash</option>
                        <option value="KBZ Pay">KBZ Pay</option>
                        <option value="WaveMoney">WaveMoney</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="button" id="addToCart" class="btn btn-primary btn-custom">Add to Cart</button>
                </div>
            </form>

            <hr class="my-4">

            <h3>Sale Cart</h3>
            <div class="table-responsive">
                <table class="table cart-table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Lot Number</th>
                            <th>Price (Ks)</th>
                            <th>Quantity</th>
                            <th>Discount (%)</th>
                            <th>Total (Ks)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr><td colspan="7" class="text-center">No items added</td></tr>
                    </tbody>
                </table>
            </div>
            <button id="checkoutBtn" class="btn btn-success btn-custom" disabled>Checkout & Print Receipt</button>
        </div>

        <!-- Disposal Form Section -->
        <div class="form-section">
            <h2 class="section-title">Record Disposal / Wastage</h2>
            <form id="disposalForm" class="row g-3" autocomplete="off">
                <div class="col-md-6">
                    <label for="disposal_medicine" class="form-label">Select Medicine</label>
                    <input type="text" id="disposal_medicine" name="disposal_medicine" class="form-control" list="medicineList" required />
                </div>
                <div class="col-md-3">
                    <label for="disposal_lot_number" class="form-label">Lot Number</label>
                    <select id="disposal_lot_number" name="disposal_lot_number" class="form-select" required>
                        <option value="">Select medicine first</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="disposal_quantity" class="form-label">Quantity (Waste)</label>
                    <input type="number" id="disposal_quantity" name="disposal_quantity" class="form-control" min="1" value="1" required />
                </div>
                <div class="col-md-3">
                    <label for="disposal_reason" class="form-label">Reason</label>
                    <select id="disposal_reason" name="disposal_reason" class="form-select" required>
                        <option value="">Select reason</option>
                        <option value="Expired">Expired</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Abnormal">Abnormal</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="button" id="addToDisposalCart" class="btn btn-warning btn-custom">Add to Disposal List</button>
                </div>
            </form>

            <hr class="my-4">

            <h3>Disposal / Wastage Cart</h3>
            <div class="table-responsive">
                <table class="table cart-table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Lot Number</th>
                            <th>Quantity (Waste)</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="disposalCartBody">
                        <tr><td colspan="5" class="text-center">No items added</td></tr>
                    </tbody>
                </table>
            </div>
            <button id="disposalCheckoutBtn" class="btn btn-danger btn-custom" disabled>Submit Disposal Records</button>
        </div>

    </div>

<script>
// --- JavaScript logic (Sale & Disposal) ---

const medicines = <?= json_encode($medicines); ?>;

const lotNumberSelect = document.getElementById('lot_number');
const sellingPriceInput = document.getElementById('selling_price');
const quantityInput = document.getElementById('quantity');
const discountInput = document.getElementById('discount');
const totalAmountInput = document.getElementById('total_amount');
const medicineInput = document.getElementById('medicine');
const cartBody = document.getElementById('cartBody');
const checkoutBtn = document.getElementById('checkoutBtn');

const disposalMedicineInput = document.getElementById('disposal_medicine');
const disposalLotNumberSelect = document.getElementById('disposal_lot_number');
const disposalQuantityInput = document.getElementById('disposal_quantity');
const disposalReasonSelect = document.getElementById('disposal_reason');
const disposalCartBody = document.getElementById('disposalCartBody');
const disposalCheckoutBtn = document.getElementById('disposalCheckoutBtn');

let cart = [];
let disposalCart = [];

medicineInput.addEventListener('change', async () => {
    const medName = medicineInput.value.trim();
    if (!medName) return;

    lotNumberSelect.innerHTML = '<option value="">Loading...</option>';
    const lots = await fetchLotNumbers(medName);
    if (lots.length === 0) {
        lotNumberSelect.innerHTML = '<option value="">No lots found</option>';
        sellingPriceInput.value = '';
        return;
    }
    lotNumberSelect.innerHTML = '<option value="">Select lot number</option>';
    lots.forEach(lot => {
        const opt = document.createElement('option');
        opt.value = lot.lot_number;
        opt.textContent = lot.lot_number;
        lotNumberSelect.appendChild(opt);
    });
    sellingPriceInput.value = lots[0].sell_price;
    calculateTotal();
});

disposalMedicineInput.addEventListener('change', async () => {
    const medName = disposalMedicineInput.value.trim();
    if (!medName) return;

    disposalLotNumberSelect.innerHTML = '<option value="">Loading...</option>';
    const lots = await fetchLotNumbers(medName);
    if (lots.length === 0) {
        disposalLotNumberSelect.innerHTML = '<option value="">No lots found</option>';
        return;
    }
    disposalLotNumberSelect.innerHTML = '<option value="">Select lot number</option>';
    lots.forEach(lot => {
        const opt = document.createElement('option');
        opt.value = lot.lot_number;
        opt.textContent = lot.lot_number;
        disposalLotNumberSelect.appendChild(opt);
    });
});

lotNumberSelect.addEventListener('change', () => {
    const selectedLot = lotNumberSelect.value;
    const medName = medicineInput.value.trim();
    if (!selectedLot || !medName) return;

    fetch('get_lots.php?medicine=' + encodeURIComponent(medName))
        .then(res => res.json())
        .then(lots => {
            const lot = lots.find(l => l.lot_number === selectedLot);
            if (lot) sellingPriceInput.value = lot.sell_price;
            calculateTotal();
        });
});

quantityInput.addEventListener('input', calculateTotal);
discountInput.addEventListener('input', calculateTotal);
sellingPriceInput.addEventListener('input', calculateTotal);

function calculateTotal() {
    const price = parseFloat(sellingPriceInput.value) || 0;
    const quantity = parseInt(quantityInput.value) || 0;
    const discount = parseFloat(discountInput.value) || 0;

    let total = price * quantity;
    if (discount > 0 && discount <= 100) {
        total -= (total * discount / 100);
    }
    totalAmountInput.value = total.toFixed(2);
}

// Add to Sale Cart
document.getElementById('addToCart').addEventListener('click', () => {
    const medicine = medicineInput.value.trim();
    const lot = lotNumberSelect.value;
    const price = parseFloat(sellingPriceInput.value);
    const qty = parseInt(quantityInput.value);
    const discount = parseFloat(discountInput.value);
    const total = parseFloat(totalAmountInput.value);

    if (!medicine || !lot || !price || !qty || !total) {
        alert('Please fill all required fields correctly.');
        return;
    }

    cart.push({medicine, lot, price, qty, discount, total});
    renderCart();

    lotNumberSelect.innerHTML = '<option value="">Select medicine first</option>';
    sellingPriceInput.value = '';
    quantityInput.value = 1;
    discountInput.value = 0;
    totalAmountInput.value = '';
    medicineInput.value = '';
});

function renderCart() {
    cartBody.innerHTML = '';
    if (cart.length === 0) {
        cartBody.innerHTML = '<tr><td colspan="7" class="text-center">No items added</td></tr>';
        checkoutBtn.disabled = true;
        return;
    }

    let totalCartAmount = 0;

    cart.forEach((item, index) => {
        totalCartAmount += item.total;

        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${item.medicine}</td>
                        <td>${item.lot}</td>
                        <td>${item.price.toFixed(2)}</td>
                        <td>${item.qty}</td>
                        <td>${item.discount}</td>
                        <td>${item.total.toFixed(2)}</td>
                        <td><button class="btn btn-danger btn-sm" data-index="${index}">Remove</button></td>`;
        cartBody.appendChild(tr);
    });

    // Add total row
    const totalRow = document.createElement('tr');
    totalRow.innerHTML = `<td colspan="5" class="text-end fw-bold">Cart Total (Ks)</td>
                          <td colspan="2" class="fw-bold">${totalCartAmount.toFixed(2)}</td>`;
    totalRow.classList.add('table-info');
    cartBody.appendChild(totalRow);

    checkoutBtn.disabled = false;

    cartBody.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', e => {
            const idx = e.target.getAttribute('data-index');
            cart.splice(idx, 1);
            renderCart();
        });
    });
}


// Disposal Cart
document.getElementById('addToDisposalCart').addEventListener('click', () => {
    const medicine = disposalMedicineInput.value.trim();
    const lot = disposalLotNumberSelect.value;
    const qty = parseInt(disposalQuantityInput.value);
    const reason = disposalReasonSelect.value;

    if (!medicine || !lot || !qty || !reason) {
        alert('Please fill all disposal fields correctly.');
        return;
    }

    disposalCart.push({medicine, lot, qty, reason});
    renderDisposalCart();

    disposalLotNumberSelect.innerHTML = '<option value="">Select medicine first</option>';
    disposalQuantityInput.value = 1;
    disposalReasonSelect.value = '';
    disposalMedicineInput.value = '';
});

function renderDisposalCart() {
    disposalCartBody.innerHTML = '';
    if (disposalCart.length === 0) {
        disposalCartBody.innerHTML = '<tr><td colspan="5" class="text-center">No items added</td></tr>';
        disposalCheckoutBtn.disabled = true;
        return;
    }

    disposalCart.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${item.medicine}</td>
                        <td>${item.lot}</td>
                        <td>${item.qty}</td>
                        <td>${item.reason}</td>
                        <td><button class="btn btn-danger btn-sm" data-index="${index}">Remove</button></td>`;
        disposalCartBody.appendChild(tr);
    });
    disposalCheckoutBtn.disabled = false;

    disposalCartBody.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', e => {
            const idx = e.target.getAttribute('data-index');
            disposalCart.splice(idx, 1);
            renderDisposalCart();
        });
    });
}

checkoutBtn.addEventListener('click', async () => {
    if (cart.length === 0) { alert('Sale cart is empty!'); return; }

    try {
        const response = await fetch('sale_process.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cart})
        });
        const result = await response.text();
        alert(result);
        cart = [];
        renderCart();
        document.getElementById('saleForm').reset();
        lotNumberSelect.innerHTML = '<option value="">Select medicine first</option>';
        sellingPriceInput.value = '';
        totalAmountInput.value = '';
    } catch (err) {
        console.error(err);
        alert('Error processing sale.');
    }
});

disposalCheckoutBtn.addEventListener('click', async () => {
    if (disposalCart.length === 0) { alert('Disposal cart is empty!'); return; }

    try {
        const response = await fetch('disposal_process.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({disposalCart})
        });
        const result = await response.text();
        alert(result);
        disposalCart = [];
        renderDisposalCart();
        document.getElementById('disposalForm').reset();
        disposalLotNumberSelect.innerHTML = '<option value="">Select medicine first</option>';
    } catch (err) {
        console.error(err);
        alert('Error processing disposal.');
    }
});

// Dummy fetchLotNumbers function (replace with AJAX to server in production)
async function fetchLotNumbers(medName) {
    const med = medicines.find(m => m.name === medName);
    if (!med) return [];
    const res = await fetch(`get_lots.php?medicine=${encodeURIComponent(medName)}`);
    return res.ok ? await res.json() : [];
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
