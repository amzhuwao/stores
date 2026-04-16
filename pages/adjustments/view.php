<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new AdjustmentController();
$adjustmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$adjustment = $controller->getById($adjustmentId);

if (!$adjustment) {
    $_SESSION['flash_message'] = 'Adjustment not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . SITE_URL . 'pages/adjustments/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . SITE_URL . 'pages/adjustments/view.php?id=' . $adjustmentId);
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $result = $controller->approve($adjustmentId, (int)($_SESSION['user_id'] ?? 0));
    } elseif ($action === 'reject') {
        $result = $controller->reject($adjustmentId, (int)($_SESSION['user_id'] ?? 0), $_POST['reject_reason'] ?? '');
    } else {
        $result = ['success' => false, 'message' => 'Invalid action'];
    }

    $_SESSION['flash_message'] = $result['message'];
    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
    header('Location: ' . SITE_URL . 'pages/adjustments/view.php?id=' . $adjustmentId);
    exit;
}

$items = $controller->getItems($adjustmentId);

$pageTitle = 'Adjustment Details';
$activePage = 'adjustments';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Adjustment <?php echo htmlspecialchars($adjustment['adjustment_number']); ?></h1>
        <p>Review submitted lines and approve or reject to finalize stock posting.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/adjustments/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Adjustments
    </a>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-info-circle"></i> Header</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><strong>Status:</strong><br><span class="badge badge-<?php echo $adjustment['status'] === 'approved' ? 'success' : ($adjustment['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars(ucfirst($adjustment['status'])); ?></span></div>
            <div class="col-md-3"><strong>Store:</strong><br><?php echo htmlspecialchars($adjustment['store_name']); ?></div>
            <div class="col-md-3"><strong>Reason:</strong><br><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $adjustment['adjustment_reason']))); ?></div>
            <div class="col-md-3"><strong>Date:</strong><br><?php echo htmlspecialchars(formatDate($adjustment['adjustment_date'], 'Y-m-d H:i')); ?></div>
            <div class="col-md-4"><strong>Adjusted By:</strong><br><?php echo htmlspecialchars($adjustment['adjusted_by_name']); ?></div>
            <div class="col-md-4"><strong>Approved By:</strong><br><?php echo htmlspecialchars($adjustment['approved_by_name'] ?: '-'); ?></div>
            <div class="col-md-4"><strong>Approval Date:</strong><br><?php echo htmlspecialchars($adjustment['approval_date'] ?: '-'); ?></div>
            <div class="col-md-12"><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($adjustment['notes'] ?: '-')); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-list"></i> Line Items</h5></div>
    <div class="card-body">
        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Code</th>
                            <th>Unit</th>
                            <th>Quantity Change</th>
                            <th>Reason Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                                <td><?php echo (int)$item['quantity_change']; ?></td>
                                <td><?php echo htmlspecialchars($item['reason_details'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">No items found.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($adjustment['status'] === 'pending' && can('adjustments.approve')): ?>
    <div class="card">
        <div class="card-header"><h5><i class="fas fa-check-circle"></i> Approval</h5></div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <form method="POST">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve and Post Stock</button>
                </form>

                <form method="POST" class="d-flex gap-2">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="action" value="reject">
                    <input type="text" name="reject_reason" class="form-control" placeholder="Reason for rejection (optional)">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>