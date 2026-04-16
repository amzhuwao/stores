<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new GRNController();
$stores = $controller->getStores();
$suppliers = $controller->getSuppliers();
$products = $controller->getProducts();
$error = '';

$formData = [
    'supplier_id' => '',
    'store_id' => '',
    'receipt_date' => date('Y-m-d'),
    'receipt_time' => date('H:i'),
    'delivery_note_ref' => '',
    'invoice_reference' => '',
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'supplier_id' => $_POST['supplier_id'] ?? '',
            'store_id' => $_POST['store_id'] ?? '',
            'receipt_date' => $_POST['receipt_date'] ?? date('Y-m-d'),
            'receipt_time' => $_POST['receipt_time'] ?? date('H:i'),
            'delivery_note_ref' => $_POST['delivery_note_ref'] ?? '',
            'invoice_reference' => $_POST['invoice_reference'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];

        $result = $controller->create($formData, $_POST['items'] ?? [], $userId = $_SESSION['user_id']);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/grn/view.php?id=' . (int)$result['grn_id']);
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'New GRN';
$activePage = 'grn';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>New GRN</h1>
        <p>Record supplier delivery with quantities, prices, batches, and expiry dates.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/grn/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to GRN List
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-file-import"></i> GRN Header</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php echo getCSRFTokenField(); ?>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo (int)$supplier['supplier_id']; ?>" <?php echo ((string)$formData['supplier_id'] === (string)$supplier['supplier_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($suppliers)): ?>
                        <small class="text-danger">No active suppliers found. Create one before posting a GRN.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-control" required>
                        <option value="">Select Store</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo ((string)$formData['store_id'] === (string)$store['store_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($store['store_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Receipt Date</label>
                    <input type="date" name="receipt_date" class="form-control" value="<?php echo htmlspecialchars($formData['receipt_date']); ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Receipt Time</label>
                    <input type="time" name="receipt_time" class="form-control" value="<?php echo htmlspecialchars($formData['receipt_time']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Delivery Note Reference</label>
                    <input type="text" name="delivery_note_ref" class="form-control" value="<?php echo htmlspecialchars($formData['delivery_note_ref']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice Reference</label>
                    <input type="text" name="invoice_reference" class="form-control" value="<?php echo htmlspecialchars($formData['invoice_reference']); ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($formData['notes']); ?>">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">GRN Items</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="cloneRow(this)">Add Row</button>
            </div>

            <div class="table-responsive">
                <table class="table" id="grn-items-table">
                    <thead>
                        <tr>
                            <th style="min-width:220px;">Product</th>
                            <th style="min-width:120px;">Expected</th>
                            <th style="min-width:120px;">Received</th>
                            <th style="min-width:120px;">Unit Price</th>
                            <th style="min-width:160px;">Batch Number</th>
                            <th style="min-width:140px;">Expiry Date</th>
                            <th style="width:80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < 2; $i++): ?>
                        <tr>
                            <td>
                                <select name="items[product_id][]" class="form-control" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo (int)$product['product_id']; ?>"><?php echo htmlspecialchars($product['product_name'] . ' (' . $product['product_code'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="items[quantity_expected][]" class="form-control" min="1" required></td>
                            <td><input type="number" name="items[quantity_received][]" class="form-control" min="0" required></td>
                            <td><input type="number" name="items[unit_price][]" class="form-control" min="0" step="0.01" required></td>
                            <td><input type="text" name="items[batch_number][]" class="form-control"></td>
                            <td><input type="date" name="items[expiry_date][]" class="form-control"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">X</button></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Save GRN</button>
                <a href="<?php echo SITE_URL; ?>pages/grn/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
