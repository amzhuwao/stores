<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new GRNController();
$grnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$grn = $controller->getById($grnId);

if (!$grn) {
    $_SESSION['flash_message'] = 'GRN not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . SITE_URL . 'pages/grn/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . SITE_URL . 'pages/grn/view.php?id=' . $grnId);
        exit;
    }

    if (($_POST['action'] ?? '') === 'verify') {
        $result = $controller->verify($grnId);
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        header('Location: ' . SITE_URL . 'pages/grn/view.php?id=' . $grnId);
        exit;
    }
}

$items = $controller->getItems($grnId);

if (($_GET['export'] ?? '') === 'pdf') {
    $html = '<p><strong>GRN:</strong> ' . htmlspecialchars($grn['grn_number']) . '</p>';
    $html .= '<p><strong>Supplier:</strong> ' . htmlspecialchars($grn['supplier_name']) . ' | <strong>Store:</strong> ' . htmlspecialchars($grn['store_name']) . '</p>';
    $html .= '<table><thead><tr><th>Product</th><th>Expected</th><th>Received</th><th>Unit Price</th><th>Line Total</th></tr></thead><tbody>';
    $grand = 0;
    foreach ($items as $item) {
        $line = (float)$item['quantity_received'] * (float)$item['unit_price'];
        $grand += $line;
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['product_name']) . '</td>';
        $html .= '<td>' . (int)$item['quantity_expected'] . '</td>';
        $html .= '<td>' . (int)$item['quantity_received'] . '</td>';
        $html .= '<td>$' . number_format((float)$item['unit_price'], 2) . '</td>';
        $html .= '<td>$' . number_format($line, 2) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<p><strong>Total:</strong> $' . number_format($grand, 2) . '</p>';
    exportPdfOrPrintHtml('GRN ' . $grn['grn_number'], $html, 'grn-' . $grn['grn_number']);
}

$pageTitle = 'GRN Details';
$activePage = 'grn';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><?php echo htmlspecialchars($grn['grn_number']); ?></h1>
        <p>Supplier delivery details and line items.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo SITE_URL; ?>pages/grn/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="<?php echo SITE_URL; ?>pages/grn/view.php?id=<?php echo (int)$grnId; ?>&export=pdf" class="btn btn-outline-primary">
            <i class="fas fa-download"></i> Export PDF
        </a>
        <?php if ($grn['status'] !== 'verified'): ?>
        <form method="POST">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="verify">
            <button type="submit" class="btn btn-success" onclick="return confirm('Verify this GRN and post stock updates?')">Verify GRN</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-file-alt"></i> Header Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>GRN Number:</strong><br><?php echo htmlspecialchars($grn['grn_number']); ?></div>
                    <div class="col-md-4"><strong>Supplier:</strong><br><?php echo htmlspecialchars($grn['supplier_name']); ?></div>
                    <div class="col-md-4"><strong>Store:</strong><br><?php echo htmlspecialchars($grn['store_name']); ?></div>
                    <div class="col-md-4"><strong>Receipt Date:</strong><br><?php echo htmlspecialchars($grn['receipt_date']); ?></div>
                    <div class="col-md-4"><strong>Receipt Time:</strong><br><?php echo htmlspecialchars($grn['receipt_time'] ?? '-'); ?></div>
                    <div class="col-md-4"><strong>Status:</strong><br><span class="badge badge-<?php echo $grn['status'] === 'verified' ? 'success' : ($grn['status'] === 'received' ? 'warning' : 'info'); ?>"><?php echo ucfirst($grn['status']); ?></span></div>
                    <div class="col-md-4"><strong>Delivery Note:</strong><br><?php echo htmlspecialchars($grn['delivery_note_ref'] ?? '-'); ?></div>
                    <div class="col-md-4"><strong>Invoice Ref:</strong><br><?php echo htmlspecialchars($grn['invoice_reference'] ?? '-'); ?></div>
                    <div class="col-md-4"><strong>Total Cost:</strong><br>$<?php echo number_format((float)($grn['total_cost'] ?? 0), 2); ?></div>
                    <div class="col-md-4"><strong>Received By:</strong><br><?php echo htmlspecialchars($grn['received_by_name']); ?></div>
                    <div class="col-md-12"><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($grn['notes'] ?? '-')); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Summary</h5>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Items:</strong> <?php echo count($items); ?></p>
                <p class="mb-2"><strong>Status:</strong> <?php echo ucfirst($grn['status']); ?></p>
                <p class="mb-2"><strong>Total Cost:</strong> $<?php echo number_format((float)($grn['total_cost'] ?? 0), 2); ?></p>
                <p class="mb-0"><strong>Created:</strong> <?php echo htmlspecialchars($grn['created_at']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-list"></i> GRN Items</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Expected</th>
                        <th>Received</th>
                        <th>Unit Price</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $grandTotal = 0; ?>
                    <?php foreach ($items as $item): ?>
                        <?php $lineTotal = (float)$item['quantity_received'] * (float)$item['unit_price']; $grandTotal += $lineTotal; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br><small><?php echo htmlspecialchars($item['product_code']); ?></small></td>
                            <td><?php echo (int)$item['quantity_expected']; ?></td>
                            <td><?php echo (int)$item['quantity_received']; ?></td>
                            <td>$<?php echo number_format((float)$item['unit_price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($item['batch_number'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['expiry_date'] ?: '-'); ?></td>
                            <td>$<?php echo number_format($lineTotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">Total</th>
                        <th>$<?php echo number_format($grandTotal, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
