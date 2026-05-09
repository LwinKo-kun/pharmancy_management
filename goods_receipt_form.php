<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php';

// Load language
$lang = $_SESSION['lang'] ?? 'en';
$langFile = 'lang_' . $lang . '.php';
$langData = file_exists($langFile) ? include($langFile) : include('lang_en.php');

// Suppliers
$supStmt = $pdo->query("SELECT id, company_name, contact_person FROM suppliers ORDER BY company_name");
$suppliers = $supStmt->fetchAll(PDO::FETCH_ASSOC);

// Medicines
$medStmt = $pdo->query("SELECT id, name, barcode FROM medicines ORDER BY name");
$medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($langData['goods_receipt_title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: url('images/md2.avif') no-repeat center center fixed;
    background-size: cover;
}
.overlay {
    position: fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.4);
    z-index:-1;
}
.form-container {
    max-width: 780px;
    width: 100%;
    background: rgba(255,255,255,0.85);
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    margin: 50px auto;
    position: relative;
}
.form-title {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 700;
    color: #0d6efd;
}
.btn-soft { border-radius: 12px; }
#autocompleteList {
    max-height: 200px;
    overflow-y: auto;
}
</style>
</head>
<body>
<div class="overlay"></div>
<?php include 'navbar.php'; ?>

<div class="form-container">
    <h2 class="form-title"><?= htmlspecialchars($langData['goods_receipt_title']) ?></h2>

    <div id="formAlert" class="alert d-none" role="alert"></div>

    <form id="goodsForm" action="goods_receipt_process.php" method="POST" novalidate>
        <!-- Supplier -->
        <div class="mb-3">
            <label for="supplier_id" class="form-label"><?= $langData['supplier'] ?></label>
            <select class="form-select" id="supplier_id" name="supplier_id" required>
                <option value=""><?= $langData['select_supplier'] ?></option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= htmlspecialchars($s['id']) ?>">
                        <?= htmlspecialchars($s['company_name']) ?>
                        <?= $s['contact_person'] ? ' — ' . htmlspecialchars($s['contact_person']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Medicine -->
        <div class="mb-3 position-relative">
            <label for="medicine_search" class="form-label"><?= $langData['medicine'] ?></label>
            <input type="text" class="form-control" id="medicine_search"
                   placeholder="<?= $langData['medicine_placeholder'] ?>"
                   autocomplete="off" required>
            <ul id="autocompleteList" class="list-group position-absolute w-100 d-none" style="z-index:1000;"></ul>
            <input type="hidden" name="medicine_id" id="medicine_id" required>
        </div>

        <!-- Barcode -->
        <div class="row g-3">
            <div class="col-md-8">
                <label for="barcode" class="form-label"><?= $langData['barcode'] ?></label>
                <input type="text" class="form-control" id="barcode" name="barcode"
                       placeholder="<?= $langData['barcode_placeholder'] ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" id="btnGenBarcode" class="btn btn-outline-secondary w-100 btn-soft">
                    <?= $langData['generate'] ?>
                </button>
            </div>
        </div>

        <!-- Lot / Dates -->
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label for="lot_number" class="form-label"><?= $langData['lot_number'] ?></label>
                <input type="text" class="form-control" id="lot_number" name="lot_number" required>
            </div>
            <div class="col-md-3">
                <label for="mfg_date" class="form-label"><?= $langData['mfg_date'] ?></label>
                <input type="date" class="form-control" id="mfg_date" name="mfg_date">
            </div>
            <div class="col-md-3">
                <label for="expiry_date" class="form-label"><?= $langData['expiry_date'] ?></label>
                <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
            </div>
        </div>

        <!-- Prices / Qty -->
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label for="purchase_price" class="form-label"><?= $langData['purchase_price'] ?></label>
                <input type="number" step="0.01" min="0" class="form-control" id="purchase_price" name="purchase_price" required>
            </div>
            <div class="col-md-4">
                <label for="selling_price" class="form-label"><?= $langData['selling_price'] ?></label>
                <input type="number" step="0.01" min="0" class="form-control" id="selling_price" name="selling_price" required>
            </div>
            <div class="col-md-4">
                <label for="quantity" class="form-label"><?= $langData['quantity'] ?></label>
                <input type="number" min="1" class="form-control" id="quantity" name="quantity" required>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary btn-soft flex-grow-1"><?= $langData['save'] ?></button>
            <button type="button" id="btnReset" class="btn btn-secondary btn-soft"><?= $langData['reset'] ?></button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const medicines = <?= json_encode($medicines); ?>;
const form = document.getElementById('goodsForm');
const alertBox = document.getElementById('formAlert');
const medSearch = document.getElementById('medicine_search');
const medId = document.getElementById('medicine_id');
const autocompleteList = document.getElementById('autocompleteList');
const btnGenBarcode = document.getElementById('btnGenBarcode');
const barcodeInput = document.getElementById('barcode');
const btnReset = document.getElementById('btnReset');
const mfgDate = document.getElementById('mfg_date');
const expDate = document.getElementById('expiry_date');

function showAlert(message, type='danger') {
    alertBox.className = 'alert alert-' + type;
    alertBox.textContent = message;
    alertBox.classList.remove('d-none');
}
function clearAlert() {
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
}

// Autocomplete (name only in input, barcode auto-fill)
medSearch.addEventListener('input', function() {
    const val = medSearch.value.toLowerCase();
    autocompleteList.innerHTML = '';
    if(!val) { autocompleteList.classList.add('d-none'); medId.value=''; return; }
    
    const matches = medicines.filter(m => m.name.toLowerCase().includes(val));
    if(matches.length===0) { autocompleteList.classList.add('d-none'); medId.value=''; return; }

    matches.forEach(m => {
        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action';
        li.textContent = m.name; // only name
        li.dataset.id = m.id;
        li.dataset.barcode = m.barcode; // hidden barcode

        li.addEventListener('click', ()=> {
            medSearch.value = li.textContent; // name only
            medId.value = li.dataset.id;
            if(barcodeInput && li.dataset.barcode) barcodeInput.value = li.dataset.barcode;
            autocompleteList.classList.add('d-none');
        });
        autocompleteList.appendChild(li);
    });
    autocompleteList.classList.remove('d-none');
});

// Barcode generation: MED-YYMMDD-XXX
btnGenBarcode.addEventListener('click', ()=>{
    const date = new Date();
    const yy = String(date.getFullYear()).slice(-2);
    const mm = String(date.getMonth()+1).padStart(2,'0');
    const dd = String(date.getDate()).padStart(2,'0');
    const rand = Math.floor(Math.random()*900+100);
    barcodeInput.value = `MED-${yy}${mm}${dd}-${rand}`;
});

// Reset form
btnReset.addEventListener('click', ()=>{
    form.reset(); medId.value=''; clearAlert(); autocompleteList.classList.add('d-none');
});

// Form validation
form.addEventListener('submit', e=>{
    clearAlert();
    if(!medId.value) { 
        e.preventDefault(); showAlert("<?= $langData['error_select_medicine'] ?>"); 
        medSearch.focus(); return; 
    }
    const today = new Date(); today.setHours(0,0,0,0);
    const exp = expDate.value ? new Date(expDate.value) : null;
    const mfg = mfgDate.value ? new Date(mfgDate.value) : null;
    if(exp && exp < today) { e.preventDefault(); showAlert("<?= $langData['error_expiry_past'] ?>"); expDate.focus(); return; }
    if(mfg && exp && mfg>exp) { e.preventDefault(); showAlert("<?= $langData['error_mfg_after_exp'] ?>"); mfgDate.focus(); return; }
});
</script>
</body>
</html>
