<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/db.php'; // PDO connection
include_once 'lang.php'; // language data for $langData
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($langData['medicine_list'] ?? 'Medicine List'); ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body {
    background: url('images/ph3.jpg') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    overflow-x: hidden;
    position: relative;
}

/* Subtle blur overlay for background */
body::before {
    content: "";
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(4px);
    z-index: -1;
}

/* Frosted glass container for table */
.glass-container {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(8px);
    border-radius: 1.2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    padding: 25px;
    margin-top: 30px;
}

/* Table styling */
.table thead {
    background: rgba(52, 152, 219, 0.85);
    color: #fff;
    border-radius: 10px;
}
.table thead th { vertical-align: middle; }
.table tbody tr:hover { background: rgba(52, 152, 219, 0.1); }

.expiring-soon { background-color: #fff3cd !important; border-left: 5px solid #ffc107; }
.expired { background-color: #f8d7da !important; border-left: 5px solid #dc3545; }

h2 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 700;
    color: #212529;
}

.btn-sm, .btn-lg { border-radius: 20px; font-weight: 500; }
.btn-add {
    font-size: 1.1rem;
    padding: 10px 25px;
    border-radius: 50px;
}

.top-controls {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 10px;
}

.search-wrapper {
    flex: 1 1 400px;
    display: flex;
    justify-content: center;
    gap: 10px;
}

.search-wrapper .form-control,
.search-wrapper .btn {
    border-radius: 20px;
    transition: all 0.2s ease;
}

.search-wrapper .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(93, 173, 226,0.25);
    border-color: #5DADE2;
}

/* Modern link hover */
a.text-decoration-none.text-dark:hover {
    text-decoration: none;
    transform: scale(1.02);
    transition: transform 0.2s ease;
}
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container glass-container">
    <h2><?= htmlspecialchars($langData['medicine_list'] ?? 'Medicine List'); ?></h2>

    <div class="top-controls">
        <a href="add_medicine.php" class="btn btn-success btn-add mb-2">
            <i class="bi bi-plus-circle me-1"></i>
            <?= htmlspecialchars($langData['add_medicine'] ?? 'Add Medicine'); ?>
        </a>

        <div class="search-wrapper mb-2">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars($langData['search_placeholder'] ?? 'Search by name, barcode, category, lot...'); ?>" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($langData['search'] ?? 'Search'); ?></button>
                <a href="medicine_list.php" class="btn btn-secondary"><?= htmlspecialchars($langData['reset'] ?? 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead>
            <tr>
                <th><?= htmlspecialchars($langData['name'] ?? 'Name'); ?></th>
                <th><?= htmlspecialchars($langData['barcode'] ?? 'Barcode'); ?></th>
                <th><?= htmlspecialchars($langData['dosage_form'] ?? 'Dosage Form'); ?></th>
                <th><?= htmlspecialchars($langData['category'] ?? 'Category'); ?></th>
                <th><?= htmlspecialchars($langData['quantity'] ?? 'Quantity'); ?></th>
                <th><?= htmlspecialchars($langData['unit'] ?? 'Unit'); ?></th>
                <th><?= htmlspecialchars($langData['lot_number'] ?? 'Lot Number'); ?></th>
                <th><?= htmlspecialchars($langData['mfg_date'] ?? 'Mfg Date'); ?></th>
                <th><?= htmlspecialchars($langData['expiry_date'] ?? 'Expiry Date'); ?></th>
                <th><?= htmlspecialchars($langData['cost_price'] ?? 'Cost Price'); ?></th>
                <th><?= htmlspecialchars($langData['sell_price'] ?? 'Selling Price'); ?></th>
                <th><?= htmlspecialchars($langData['reorder_level'] ?? 'Reorder Level'); ?></th>
                <th><?= htmlspecialchars($langData['actions'] ?? 'Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        try {
            $today = date('Y-m-d');
            $search = $_GET['search'] ?? '';

            $sql = "SELECT * FROM medicines";
            $params = [];

            if (!empty($search)) {
                $sql .= " WHERE 
                    name LIKE :search OR
                    barcode LIKE :search OR
                    dosage_form LIKE :search OR
                    category LIKE :search OR
                    lot_number LIKE :search";
                $params['search'] = "%$search%";
            }

            $sql .= " ORDER BY exp_date ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($medicines) {
                foreach ($medicines as $med) {
                    $expiry = $med['exp_date'];
                    $rowClass = '';
                    if ($expiry < $today) {
                        $rowClass = 'expired';
                    } elseif ((strtotime($expiry) - strtotime($today)) <= (30 * 24 * 60 * 60)) {
                        $rowClass = 'expiring-soon';
                    }
                    ?>
                    <tr class="<?= $rowClass; ?>">
                        <td><?= htmlspecialchars($med['name']); ?></td>
                        <td><?= htmlspecialchars($med['barcode']); ?></td>
                        <td><?= htmlspecialchars($med['dosage_form']); ?></td>
                        <td><?= htmlspecialchars($med['category']); ?></td>
                        <td><?= htmlspecialchars($med['quantity']); ?></td>
                        <td><?= htmlspecialchars($med['unit']); ?></td>
                        <td><?= htmlspecialchars($med['lot_number']); ?></td>
                        <td><?= htmlspecialchars($med['mfg_date']); ?></td>
                        <td><?= htmlspecialchars($med['exp_date']); ?></td>
                        <td><?= number_format($med['cost_price'],2) . ' Ks'; ?></td>
                        <td><?= number_format($med['sell_price'],2) . ' Ks'; ?></td>
                        <td><?= htmlspecialchars($med['reorder_level']); ?></td>
                        <td>
                            <a href="edit.php?id=<?= urlencode($med['id']); ?>" class="btn btn-sm btn-primary mb-1">✏️ <?= htmlspecialchars($langData['edit'] ?? 'Edit'); ?></a>
                            <a href="delete.php?id=<?= urlencode($med['id']); ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('<?= htmlspecialchars($langData['confirm_delete'] ?? 'Are you sure?'); ?>');">❌ <?= htmlspecialchars($langData['delete'] ?? 'Delete'); ?></a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="13" class="text-center text-muted">' . htmlspecialchars($langData['no_results'] ?? 'No medicines found') . '</td></tr>';
            }
        } catch (PDOException $e) {
            echo '<tr><td colspan="13" class="text-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
        }
        ?>
        </tbody>
    </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
