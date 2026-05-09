<?php 
include 'navbar.php';
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

// ဆေးအမည်တွေ ဖေါ်ထုတ်
$stmt = $pdo->query("SELECT id, name FROM medicines ORDER BY name");
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Record Disposal / Wastage - Smart Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { padding: 20px; background-color: #f9f9f9; }
    </style>
</head>
<body>
<div class="container">
    <h2>Record Disposal / Wastage</h2>

    <form id="wastageForm" class="row g-3">
        <div class="col-md-6">
            <label for="medicine" class="form-label">Select Medicine</label>
            <input type="text" id="medicine" name="medicine" class="form-control" list="medicineList" autocomplete="off" required />
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

        <div class="col-md-2">
            <label for="quantity" class="form-label">Quantity of Waste</label>
            <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1" required />
        </div>

        <div class="col-md-3">
            <label for="reason" class="form-label">Reason for Disposal</label>
            <select id="reason" name="reason" class="form-select" required>
                <option value="">Select reason</option>
                <option value="Expired">Expired</option>
                <option value="Damaged">Damaged</option>
                <option value="Abnormal">Abnormal</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="col-md-12">
            <button type="submit" class="btn btn-danger">Record Disposal</button>
        </div>
    </form>

    <div id="result" class="mt-3"></div>
</div>

<script>
// မိမိရဲ့ PHP က medicines data ကို JS မှာယူထားတယ်
const lotNumberSelect = document.getElementById('lot_number');
const medicineInput = document.getElementById('medicine');
const wastageForm = document.getElementById('wastageForm');
const resultDiv = document.getElementById('result');

// Medicine နဲ့ lot number တွေ ရယူဖို့
async function fetchLotNumbers(medicineName) {
    try {
        const res = await fetch('get_lots.php?medicine=' + encodeURIComponent(medicineName));
        const data = await res.json();
        return data;
    } catch (err) {
        return [];
    }
}

medicineInput.addEventListener('change', async () => {
    const medName = medicineInput.value.trim();
    if (!medName) {
        lotNumberSelect.innerHTML = '<option value="">Select medicine first</option>';
        return;
    }
    lotNumberSelect.innerHTML = '<option>Loading...</option>';
    const lots = await fetchLotNumbers(medName);
    if (lots.length === 0) {
        lotNumberSelect.innerHTML = '<option value="">No lots found</option>';
        return;
    }
    lotNumberSelect.innerHTML = '<option value="">Select lot number</option>';
    lots.forEach(lot => {
        const option = document.createElement('option');
        option.value = lot.lot_number;
        option.textContent = lot.lot_number;
        lotNumberSelect.appendChild(option);
    });
});

wastageForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const medicine = medicineInput.value.trim();
    const lot_number = lotNumberSelect.value;
    const quantity = parseInt(document.getElementById('quantity').value);
    const reason = document.getElementById('reason').value;

    if (!medicine || !lot_number || !quantity || !reason) {
        alert('Please fill all fields correctly.');
        return;
    }

    try {
        const response = await fetch('wastage_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ medicine, lot_number, quantity, reason })
        });
        const text = await response.text();
        resultDiv.textContent = text;
        if (response.ok) {
            wastageForm.reset();
            lotNumberSelect.innerHTML = '<option value="">Select medicine first</option>';
        }
    } catch (error) {
        resultDiv.textContent = 'Error: ' + error.message;
    }
});
</script>
</body>
</html>
