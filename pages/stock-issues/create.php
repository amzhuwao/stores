<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new StockIssueController();
$approvedRequisitions = $controller->getApprovedRequisitions();
$requisitionId = isset($_GET['requisition_id']) ? (int)$_GET['requisition_id'] : 0;
$requisition = null;
$requisitionItems = [];
$error = '';

if ($requisitionId > 0) {
    $requisition = $controller->getRequisitionForIssue($requisitionId);
    if ($requisition && $requisition['status'] === 'approved') {
        $requisitionItems = $controller->getRequisitionItemsForIssue($requisitionId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $postRequisitionId = (int)($_POST['requisition_id'] ?? 0);
        $result = $controller->createIssue(
            $postRequisitionId,
            $_POST['items'] ?? [],
            $_POST['notes'] ?? '',
            (int)$_SESSION['user_id']
        );

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/stock-issues/view.php?id=' . (int)$result['issue_id']);
            exit;
        }

        $error = $result['message'];
        $requisitionId = $postRequisitionId;
        $requisition = $controller->getRequisitionForIssue($requisitionId);
        if ($requisition) {
            $requisitionItems = $controller->getRequisitionItemsForIssue($requisitionId);
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
    <div class="card-header"><h5><i class="fas fa-list"></i> Select Approved Requisition</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
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

<?php if ($requisition && $requisition['status'] === 'approved'): ?>
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
<?php elseif ($requisitionId > 0): ?>
    <div class="alert alert-warning">Selected requisition is not in approved status.</div>
<?php endif; ?>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
