<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new StockIssueController();
$canDirectIssue = hasRole(['Admin', 'Storekeeper', 'Manager']) || can('stock-issues.direct');

$modeRaw = trim((string)($_GET['mode'] ?? 'requisition'));
$issueMode = ($modeRaw === 'direct' && $canDirectIssue) ? 'direct' : 'requisition';

$approvedRequisitions = $controller->getApprovedRequisitions();
$requisitionId = isset($_GET['requisition_id']) ? (int)$_GET['requisition_id'] : 0;
$requisition = null;
$requisitionItems = [];

$stores = $controller->getStores();
$departments = $controller->getDepartments();
$directStoreId = isset($_GET['store_id']) ? (int)$_GET['store_id'] : 0;
$directDepartmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$directProducts = [];
$error = '';

if ($issueMode === 'requisition' && $requisitionId > 0) {
    $requisition = $controller->getRequisitionForIssue($requisitionId);
    if ($requisition && $requisition['status'] === 'approved') {
        $requisitionItems = $controller->getRequisitionItemsForIssue($requisitionId);
    }
}

if ($issueMode === 'direct' && $directStoreId > 0) {
    $directProducts = $controller->getStoreProductsForDirectIssue($directStoreId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $postedMode = trim((string)($_POST['issue_mode'] ?? 'requisition'));
        $issueMode = ($postedMode === 'direct' && $canDirectIssue) ? 'direct' : 'requisition';

        if ($issueMode === 'direct') {
            $directStoreId = (int)($_POST['store_id'] ?? 0);
            $directDepartmentId = (int)($_POST['department_id'] ?? 0);
            $result = $controller->createDirectIssue(
                $directStoreId,
                $directDepartmentId,
                $_POST['items'] ?? [],
                $_POST['notes'] ?? '',
                (int)$_SESSION['user_id']
            );
        } else {
            $postRequisitionId = (int)($_POST['requisition_id'] ?? 0);
            $result = $controller->createIssue(
                $postRequisitionId,
                $_POST['items'] ?? [],
                $_POST['notes'] ?? '',
                (int)$_SESSION['user_id']
            );
        }

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/stock-issues/view.php?id=' . (int)$result['issue_id']);
            exit;
        }

        $error = $result['message'];

        if ($issueMode === 'direct') {
            if ($directStoreId > 0) {
                $directProducts = $controller->getStoreProductsForDirectIssue($directStoreId);
            }
        } else {
            $requisitionId = (int)($_POST['requisition_id'] ?? 0);
            $requisition = $controller->getRequisitionForIssue($requisitionId);
            if ($requisition && $requisition['status'] === 'approved') {
                $requisitionItems = $controller->getRequisitionItemsForIssue($requisitionId);
            }
        }
    }
}

$pageTitle = 'New Stock Issue';
$activePage = 'issues';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>New Stock Issue</h1>
        <p>Issue stock against an approved requisition and post inventory movements.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/stock-issues/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Stock Issues
    </a>
</div>

<div class="card mb-4">
    <div class="card-header"><h5><i class="fas fa-random"></i> Issue Mode</h5></div>
    <div class="card-body d-flex flex-wrap gap-2">
        <a href="<?php echo SITE_URL; ?>pages/stock-issues/create.php?mode=requisition" class="btn <?php echo $issueMode === 'requisition' ? 'btn-primary' : 'btn-outline-primary'; ?>">
            From Approved Requisition
        </a>
        <?php if ($canDirectIssue): ?>
            <a href="<?php echo SITE_URL; ?>pages/stock-issues/create.php?mode=direct" class="btn <?php echo $issueMode === 'direct' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                Direct Issue (No Requisition)
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($issueMode === 'requisition'): ?>
    <div class="card mb-4">
        <div class="card-header"><h5><i class="fas fa-list"></i> Select Approved Requisition</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="mode" value="requisition">
                <div class="col-md-8">
                    <label class="form-label">Requisition</label>
                    <select name="requisition_id" class="form-control" required>
                        <option value="">Select approved requisition</option>
                        <?php foreach ($approvedRequisitions as $approved): ?>
                            <option value="<?php echo (int)$approved['requisition_id']; ?>" <?php echo $requisitionId === (int)$approved['requisition_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($approved['requisition_number'] . ' - ' . $approved['dept_name'] . ' / ' . $approved['store_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Load Requisition</button>
                </div>
            </form>
            <?php if (empty($approvedRequisitions)): ?>
                <div class="alert alert-warning mt-3 mb-0">No approved requisitions available for issuing.</div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($issueMode === 'requisition' && $requisition && $requisition['status'] === 'approved'): ?>
    <div class="card">
        <div class="card-header"><h5><i class="fas fa-arrow-right"></i> Issue Details</h5></div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-md-3"><strong>Requisition #</strong><br><?php echo htmlspecialchars($requisition['requisition_number']); ?></div>
                <div class="col-md-3"><strong>Department</strong><br><?php echo htmlspecialchars($requisition['dept_name']); ?></div>
                <div class="col-md-3"><strong>Store</strong><br><?php echo htmlspecialchars($requisition['store_name']); ?></div>
                <div class="col-md-3"><strong>Requested By</strong><br><?php echo htmlspecialchars($requisition['requested_by_name']); ?></div>
            </div>

            <form method="POST">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="issue_mode" value="requisition">
                <input type="hidden" name="requisition_id" value="<?php echo (int)$requisition['requisition_id']; ?>">

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Approved Qty</th>
                                <th>Already Issued</th>
                                <th>Available Stock</th>
                                <th>Issue Qty</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requisitionItems as $item): ?>
                                <?php $remaining = max(0, (int)$item['quantity_approved'] - (int)$item['quantity_issued']); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($item['product_code']); ?></small>
                                        <input type="hidden" name="items[req_item_id][]" value="<?php echo (int)$item['req_item_id']; ?>">
                                        <input type="hidden" name="items[product_id][]" value="<?php echo (int)$item['product_id']; ?>">
                                    </td>
                                    <td><?php echo (int)$item['quantity_approved']; ?></td>
                                    <td><?php echo (int)$item['quantity_issued']; ?></td>
                                    <td><?php echo (int)$item['available_stock']; ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            name="items[quantity_issued][]"
                                            class="form-control"
                                            min="0"
                                            max="<?php echo min($remaining, (int)$item['available_stock']); ?>"
                                            value="<?php echo $remaining > 0 ? min($remaining, (int)$item['available_stock']) : 0; ?>"
                                        >
                                    </td>
                                    <td><input type="text" name="items[remarks][]" class="form-control" placeholder="Optional"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Issue Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional issue notes"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Post this stock issue? This will reduce stock quantities.')">Create Stock Issue</button>
                    <a href="<?php echo SITE_URL; ?>pages/stock-issues/index.php" class="btn btn-outline-primary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($issueMode === 'requisition' && $requisitionId > 0): ?>
    <div class="alert alert-warning">Selected requisition is not in approved status.</div>
<?php endif; ?>

<?php if ($issueMode === 'direct' && $canDirectIssue): ?>
    <div class="card mb-4">
        <div class="card-header"><h5><i class="fas fa-sliders-h"></i> Direct Issue Setup</h5></div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="GET" class="row g-3">
                <input type="hidden" name="mode" value="direct">
                <div class="col-md-5">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-control" required>
                        <option value="">Select store</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo $directStoreId === (int)$store['store_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo (int)$department['dept_id']; ?>" <?php echo $directDepartmentId === (int)$department['dept_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Load Items</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($directStoreId > 0 && $directDepartmentId > 0): ?>
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-arrow-right"></i> Direct Issue Items</h5></div>
            <div class="card-body">
                <?php if (empty($directProducts)): ?>
                    <div class="alert alert-warning mb-0">No in-stock products found in the selected store.</div>
                <?php else: ?>
                    <form method="POST">
                        <?php echo getCSRFTokenField(); ?>
                        <input type="hidden" name="issue_mode" value="direct">
                        <input type="hidden" name="store_id" value="<?php echo (int)$directStoreId; ?>">
                        <input type="hidden" name="department_id" value="<?php echo (int)$directDepartmentId; ?>">

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Available Stock</th>
                                        <th>Issue Qty</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($directProducts as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($item['product_code']); ?></small>
                                                <input type="hidden" name="items[product_id][]" value="<?php echo (int)$item['product_id']; ?>">
                                            </td>
                                            <td><?php echo (int)$item['available_stock']; ?></td>
                                            <td>
                                                <input
                                                    type="number"
                                                    name="items[quantity_issued][]"
                                                    class="form-control"
                                                    min="0"
                                                    max="<?php echo (int)$item['available_stock']; ?>"
                                                    value="0"
                                                >
                                            </td>
                                            <td><input type="text" name="items[remarks][]" class="form-control" placeholder="Optional"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Issue Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Optional issue notes"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Post this direct stock issue? This will reduce stock quantities.')">Create Direct Issue</button>
                            <a href="<?php echo SITE_URL; ?>pages/stock-issues/index.php" class="btn btn-outline-primary">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
