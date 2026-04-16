<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new StockIssueController();
$issueId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$issue = $controller->getIssueById($issueId);

if (!$issue) {
    $_SESSION['flash_message'] = 'Stock issue not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . SITE_URL . 'pages/stock-issues/index.php');
    exit;
}

$items = $controller->getIssueItems($issueId);

if (($_GET['export'] ?? '') === 'pdf') {
    $html = '<p><strong>Issue #:</strong> ' . htmlspecialchars($issue['issue_number']) . '</p>';
    $html .= '<p><strong>Department:</strong> ' . htmlspecialchars($issue['dept_name']) . ' | <strong>Store:</strong> ' . htmlspecialchars($issue['store_name']) . '</p>';
    $html .= '<table><thead><tr><th>Product</th><th>Qty Issued</th><th>Unit Price</th><th>Line Total</th></tr></thead><tbody>';
    $grand = 0;
    foreach ($items as $item) {
        $line = (float)$item['quantity_issued'] * (float)$item['unit_price'];
        $grand += $line;
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['product_name']) . '</td>';
        $html .= '<td>' . (int)$item['quantity_issued'] . '</td>';
        $html .= '<td>$' . number_format((float)$item['unit_price'], 2) . '</td>';
        $html .= '<td>$' . number_format($line, 2) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<p><strong>Total:</strong> $' . number_format($grand, 2) . '</p>';
    exportPdfOrPrintHtml('Stock Issue ' . $issue['issue_number'], $html, 'stock-issue-' . $issue['issue_number']);
}

$pageTitle = 'Stock Issue Details';
$activePage = 'issues';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><?php echo htmlspecialchars($issue['issue_number']); ?></h1>
        <p>Stock issue details and item movement summary.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo SITE_URL; ?>pages/stock-issues/view.php?id=<?php echo (int)$issueId; ?>&export=pdf" class="btn btn-outline-primary">
            <i class="fas fa-download"></i> Export PDF
        </a>
        <a href="<?php echo SITE_URL; ?>pages/stock-issues/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-file-alt"></i> Issue Information</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Issue #:</strong><br><?php echo htmlspecialchars($issue['issue_number']); ?></div>
                    <div class="col-md-4"><strong>Requisition #:</strong><br><?php echo htmlspecialchars($issue['requisition_number'] ?? '-'); ?></div>
                    <div class="col-md-4"><strong>Status:</strong><br><span class="badge badge-<?php echo $issue['status'] === 'received' ? 'success' : ($issue['status'] === 'cancelled' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($issue['status']); ?></span></div>
                    <div class="col-md-4"><strong>Department:</strong><br><?php echo htmlspecialchars($issue['dept_name']); ?></div>
                    <div class="col-md-4"><strong>Store:</strong><br><?php echo htmlspecialchars($issue['store_name']); ?></div>
                    <div class="col-md-4"><strong>Issued By:</strong><br><?php echo htmlspecialchars($issue['issued_by_name']); ?></div>
                    <div class="col-md-4"><strong>Issue Date:</strong><br><?php echo htmlspecialchars($issue['issue_date']); ?></div>
                    <div class="col-md-4"><strong>Received By:</strong><br><?php echo htmlspecialchars($issue['received_by_name'] ?? '-'); ?></div>
                    <div class="col-md-4"><strong>Received Date:</strong><br><?php echo htmlspecialchars($issue['received_date'] ?? '-'); ?></div>
                    <div class="col-md-12"><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($issue['notes'] ?? '-')); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-info-circle"></i> Summary</h5></div>
            <div class="card-body">
                <p class="mb-2"><strong>Items:</strong> <?php echo count($items); ?></p>
                <p class="mb-0"><strong>Created:</strong> <?php echo htmlspecialchars($issue['created_at']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5><i class="fas fa-list"></i> Issued Items</h5></div>
    <div class="card-body">
        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity Issued</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $grand = 0; ?>
                        <?php foreach ($items as $item): ?>
                            <?php $lineTotal = (float)$item['quantity_issued'] * (float)$item['unit_price']; $grand += $lineTotal; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br><small><?php echo htmlspecialchars($item['product_code']); ?></small></td>
                                <td><?php echo (int)$item['quantity_issued']; ?></td>
                                <td>$<?php echo number_format((float)$item['unit_price'], 2); ?></td>
                                <td>$<?php echo number_format($lineTotal, 2); ?></td>
                                <td><?php echo htmlspecialchars($item['remarks'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th>$<?php echo number_format($grand, 2); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No issue lines found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
