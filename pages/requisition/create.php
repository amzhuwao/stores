<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new RequisitionController();
$stores = $controller->getStores();
$products = $controller->getProducts();
$currentUser = getCurrentUser();
$currentDepartment = $controller->getDepartmentForRole($currentUser['role_name'] ?? '');
$error = '';

$formData = [
    'department_id' => $currentDepartment['dept_id'] ?? '',
    'store_id' => '',
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        if (!$currentDepartment) {
            $error = 'Your account is not mapped to a department. Ask an administrator to assign your role to a department first.';
        }

        $formData = [
            'department_id' => $currentDepartment['dept_id'] ?? '',
            'store_id' => $_POST['store_id'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];

        if (!$error) {
            $result = $controller->create($formData, $_POST['items'] ?? [], $_SESSION['user_id']);
            if ($result['success']) {
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . SITE_URL . 'pages/requisition/view.php?id=' . (int)$result['requisition_id']);
                exit;
            }

            $error = $result['message'];
        }
    }
}

$pageTitle = 'New Requisition';
$activePage = 'requisition';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>New Requisition</h1>
        <p>Create a department request for stock from a selected store.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/requisition/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Requisitions
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-plus"></i> Requisition Details</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php echo getCSRFTokenField(); ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentDepartment['dept_name'] ?? 'Unassigned'); ?>" readonly>
                    <input type="hidden" name="department_id" value="<?php echo htmlspecialchars((string)($currentDepartment['dept_id'] ?? '')); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-control" required>
                        <option value="">Select Store</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo ((string)$formData['store_id'] === (string)$store['store_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Requested By</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>" readonly>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($formData['notes']); ?>" placeholder="Any additional request details">
                </div>
            </div>

            <?php if (!$currentDepartment): ?>
                <div class="alert alert-warning">
                    Your role is not mapped to a department, so requisition creation is disabled for this account.
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Requested Items</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="cloneRow(this)">Add Row</button>
            </div>

            <div class="table-responsive">
                <table class="table" id="req-items-table">
                    <thead>
                        <tr>
                            <th style="min-width:260px;">Product</th>
                            <th style="min-width:140px;">Quantity Requested</th>
                            <th style="min-width:240px;">Remarks</th>
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
                                            <option value="<?php echo (int)$product['product_id']; ?>">
                                                <?php echo htmlspecialchars($product['product_name'] . ' (' . $product['product_code'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="items[quantity_requested][]" class="form-control" min="1" required></td>
                                <td><input type="text" name="items[remarks][]" class="form-control" placeholder="Optional"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">X</button></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary" <?php echo !$currentDepartment ? 'disabled' : ''; ?>>Submit Requisition</button>
                <a href="<?php echo SITE_URL; ?>pages/requisition/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
