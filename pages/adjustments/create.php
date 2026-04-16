<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new AdjustmentController();
$error = '';

$stores = $controller->getStores();
$selectedStoreId = isset($_GET['store_id']) && ctype_digit((string)$_GET['store_id']) ? (int)$_GET['store_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ctype_digit((string)($_POST['store_id'] ?? ''))) {
    $selectedStoreId = (int)$_POST['store_id'];
}

$storeProducts = $selectedStoreId > 0 ? $controller->getProductsForStore($selectedStoreId) : [];

$formData = [
    'store_id' => $selectedStoreId > 0 ? (string)$selectedStoreId : '',
    'adjustment_reason' => $_POST['adjustment_reason'] ?? 'count_variance',
    'notes' => $_POST['notes'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $result = $controller->create($formData, $_POST['items'] ?? [], (int)($_SESSION['user_id'] ?? 0));
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/adjustments/view.php?id=' . (int)$result['adjustment_id']);
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Create Stock Adjustment';
$activePage = 'adjustments';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Create Stock Adjustment</h1>
        <p>Submit stock corrections for approval before stock is posted.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/adjustments/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Adjustments
    </a>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-sliders-h"></i> Adjustment Header</h5></div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Store</label>
                <select name="store_id" class="form-control" onchange="this.form.submit()" required>
                    <option value="">Select Store</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo (int)$store['store_id']; ?>" <?php echo $selectedStoreId === (int)$store['store_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($store['store_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <small class="text-muted">Choose a store first to load products available in stock.</small>
            </div>
        </form>

        <?php if ($selectedStoreId > 0): ?>
            <form method="POST" class="row g-3" id="adjustmentForm">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="store_id" value="<?php echo (int)$selectedStoreId; ?>">

                <div class="col-md-6">
                    <label class="form-label">Reason</label>
                    <select name="adjustment_reason" class="form-control" required>
                        <option value="damage" <?php echo $formData['adjustment_reason'] === 'damage' ? 'selected' : ''; ?>>Damage</option>
                        <option value="loss" <?php echo $formData['adjustment_reason'] === 'loss' ? 'selected' : ''; ?>>Loss</option>
                        <option value="correction" <?php echo $formData['adjustment_reason'] === 'correction' ? 'selected' : ''; ?>>Correction</option>
                        <option value="count_variance" <?php echo $formData['adjustment_reason'] === 'count_variance' ? 'selected' : ''; ?>>Count Variance</option>
                        <option value="recall" <?php echo $formData['adjustment_reason'] === 'recall' ? 'selected' : ''; ?>>Recall</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($formData['notes']); ?>" placeholder="Optional overall note">
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Items</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItemRow()"><i class="fas fa-plus"></i> Add Item</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width:45%;">Product</th>
                                    <th style="width:20%;">Current Qty</th>
                                    <th style="width:15%;">Change (+/-)</th>
                                    <th style="width:15%;">Details</th>
                                    <th style="width:5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Submit for Approval</button>
                    <a href="<?php echo SITE_URL; ?>pages/adjustments/index.php" class="btn btn-outline-primary">Cancel</a>
                </div>
            </form>

            <script>
            const storeProducts = <?php echo json_encode($storeProducts); ?>;

            function buildProductOptions() {
                let html = '<option value="">Select product</option>';
                for (const p of storeProducts) {
                    const label = `${p.product_name} (${p.product_code})`;
                    html += `<option value="${p.product_id}" data-qty="${p.quantity_on_hand}">${label}</option>`;
                }
                return html;
            }

            function addItemRow() {
                const body = document.getElementById('itemsBody');
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <select name="items[product_id][]" class="form-control product-select" required onchange="updateCurrentQty(this)">
                            ${buildProductOptions()}
                        </select>
                    </td>
                    <td><input type="text" class="form-control current-qty" readonly value="-"></td>
                    <td><input type="number" name="items[quantity_change][]" class="form-control" required step="1" placeholder="e.g. -2 or 5"></td>
                    <td><input type="text" name="items[reason_details][]" class="form-control" placeholder="Optional"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
                `;
                body.appendChild(row);
            }

            function updateCurrentQty(selectEl) {
                const option = selectEl.options[selectEl.selectedIndex];
                const qty = option ? option.getAttribute('data-qty') : null;
                const input = selectEl.closest('tr').querySelector('.current-qty');
                input.value = qty !== null ? qty : '-';
            }

            addItemRow();
            </script>
        <?php else: ?>
            <div class="alert alert-info mb-0">Select a store above to begin creating an adjustment.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>