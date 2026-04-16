<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new RequisitionController();
$requisitionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$requisition = $controller->getById($requisitionId);

if (!$requisition) {
    $_SESSION['flash_message'] = 'Requisition not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . SITE_URL . 'pages/requisition/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . SITE_URL . 'pages/requisition/view.php?id=' . $requisitionId);
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $result = $controller->approve($requisitionId, $_SESSION['user_id']);
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        header('Location: ' . SITE_URL . 'pages/requisition/view.php?id=' . $requisitionId);
        exit;
    }

    if ($action === 'reject') {
        $result = $controller->reject($requisitionId, $_POST['rejection_reason'] ?? '');
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        header('Location: ' . SITE_URL . 'pages/requisition/view.php?id=' . $requisitionId);
        exit;
    }
}

$items = $controller->getItems($requisitionId);
$pageTitle = 'Requisition Details';
$activePage = 'requisition';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><?php echo htmlspecialchars($requisition['requisition_number']); ?></h1>
        <p>Department stock request details and approval controls.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo SITE_URL; ?>pages/requisition/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <?php if ($requisition['status'] === 'pending'): ?>
            <form method="POST">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this requisition?')">Approve</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-file-alt"></i> Header Information</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Requisition #:</strong><br><?php echo htmlspecialchars($requisition['requisition_number']); ?></div>
                    <div class="col-md-4"><strong>Department:</strong><br><?php echo htmlspecialchars($requisition['dept_name']); ?></div>
                    <div class="col-md-4"><strong>Store:</strong><br><?php echo htmlspecialchars($requisition['store_name']); ?></div>
                    <div class="col-md-4"><strong>Requested By:</strong><br><?php echo htmlspecialchars($requisition['requested_by_name']); ?></div>
                    <div class="col-md-4"><strong>Requested Date:</strong><br><?php echo htmlspecialchars($requisition['requested_date']); ?></div>
                    <div class="col-md-4">
                        <strong>Status:</strong><br>
                        <?php
                        $badge = 'info';
                        if ($requisition['status'] === 'approved') $badge = 'success';
                        if ($requisition['status'] === 'pending') $badge = 'warning';
                        if ($requisition['status'] === 'rejected') $badge = 'danger';
                        ?>
                        <span class="badge badge-<?php echo $badge; ?>"><?php echo ucfirst($requisition['status']); ?></span>
                    </div>
                    <div class="col-md-6"><strong>Approved By:</strong><br><?php echo htmlspecialchars($requisition['approved_by_name'] ?? '-'); ?></div>
                    <div class="col-md-6"><strong>Approval Date:</strong><br><?php echo htmlspecialchars($requisition['approval_date'] ?? '-'); ?></div>
                    <?php if (!empty($requisition['rejection_reason'])): ?>
                        <div class="col-md-12"><strong>Rejection Reason:</strong><br><?php echo nl2br(htmlspecialchars($requisition['rejection_reason'])); ?></div>
                    <?php endif; ?>
                    <div class="col-md-12"><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($requisition['notes'] ?? '-')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($requisition['status'] === 'pending'): ?>
            <div class="card mb-3">
                <div class="card-header"><h5><i class="fas fa-times-circle"></i> Reject Requisition</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo getCSRFTokenField(); ?>
                        <input type="hidden" name="action" value="reject">
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this requisition?')">Reject</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h5><i class="fas fa-info-circle"></i> Summary</h5></div>
            <div class="card-body">
                <p class="mb-2"><strong>Items:</strong> <?php echo count($items); ?></p>
                <p class="mb-0"><strong>Created:</strong> <?php echo htmlspecialchars($requisition['created_at']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5><i class="fas fa-list"></i> Requested Items</h5></div>
    <div class="card-body">
        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Requested Qty</th>
                            <th>Approved Qty</th>
                            <th>Issued Qty</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($item['product_code']); ?></small>
                                </td>
                                <td><?php echo (int)$item['quantity_requested']; ?></td>
                                <td><?php echo $item['quantity_approved'] === null ? '-' : (int)$item['quantity_approved']; ?></td>
                                <td><?php echo $item['quantity_issued'] === null ? '-' : (int)$item['quantity_issued']; ?></td>
                                <td><?php echo htmlspecialchars($item['remarks'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No line items found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
