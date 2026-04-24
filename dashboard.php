<?php
require_once __DIR__ . '/app/bootstrap.php';

// Development-only: display PHP errors on this page.

// Check authentication
if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$user = getCurrentUser();
$db = Database::getInstance()->getConnection();

$roleName = trim((string)($user['role_name'] ?? ''));
$isGlobalDashboardRole = in_array($roleName, ['Admin', 'Manager', 'Storekeeper'], true);
$scopedStoreIds = [];
$scopedDepartmentId = 0;
$denyScopedDashboard = false;

if (!$isGlobalDashboardRole && $roleName !== '') {
    $scopeSql = "SELECT DISTINCT s.store_id
                 FROM departments d
                 JOIN stores s ON s.status = 'active'
                    AND (
                        s.store_code = CONCAT(d.dept_code, '001')
                        OR s.store_code LIKE CONCAT(d.dept_code, '%')
                        OR s.store_name LIKE CONCAT(d.dept_name, '%')
                    )
                 WHERE d.status = 'active' AND d.dept_name = ?";

    $scopeStmt = $db->prepare($scopeSql);
    $scopeStmt->bind_param('s', $roleName);
    $scopeStmt->execute();
    $scopeResult = $scopeStmt->get_result();

    while ($scopeRow = $scopeResult->fetch_assoc()) {
        $scopedStoreIds[] = (int)$scopeRow['store_id'];
    }

    $deptStmt = $db->prepare("SELECT dept_id FROM departments WHERE status = 'active' AND dept_name = ? LIMIT 1");
    $deptStmt->bind_param('s', $roleName);
    $deptStmt->execute();
    $deptRow = $deptStmt->get_result()->fetch_assoc();
    $scopedDepartmentId = $deptRow ? (int)$deptRow['dept_id'] : 0;

    $scopedStoreIds = array_values(array_unique($scopedStoreIds));
    if (empty($scopedStoreIds)) {
        $denyScopedDashboard = true;
    }
}

$storeScopeFilter = '';
if (!$isGlobalDashboardRole && !$denyScopedDashboard && !empty($scopedStoreIds)) {
    $storeScopeFilter = ' AND s.store_id IN (' . implode(',', array_map('intval', $scopedStoreIds)) . ')';
}

$grnStoreScopeFilter = '';
$requisitionScopeFilter = '';
$transactionStoreScopeFilter = '';
if (!$isGlobalDashboardRole && !$denyScopedDashboard && !empty($scopedStoreIds)) {
    $storeScopeList = implode(',', array_map('intval', $scopedStoreIds));
    $grnStoreScopeFilter = ' WHERE g.store_id IN (' . $storeScopeList . ')';
    $transactionStoreScopeFilter = ' WHERE st.store_id IN (' . $storeScopeList . ')';
    $requisitionScopeFilter = ' AND r.store_id IN (' . $storeScopeList . ')';

    if ($scopedDepartmentId > 0) {
        $requisitionScopeFilter .= ' AND r.department_id = ' . (int)$scopedDepartmentId;
    }
}

// Get dashboard statistics
$stats = [];

if ($denyScopedDashboard) {
    $stats['total_products'] = 0;
    $stats['stock_value'] = 0;
    $stats['low_stock'] = 0;
    $stats['out_of_stock'] = 0;
} else {
    // Total Products (within scoped stores for departmental users)
    $result = $db->query("\n        SELECT COUNT(DISTINCT p.product_id) as count\n        FROM products p\n        JOIN stock s ON s.product_id = p.product_id\n        WHERE p.status = 'active'" . $storeScopeFilter);
    $stats['total_products'] = (int)$result->fetch_assoc()['count'];

    // Total Stock Value (sum of quantity * unit price from latest GRN within scope)
    $result = $db->query("\n        SELECT COALESCE(SUM(s.quantity_on_hand *\n            COALESCE((SELECT unit_price FROM grn_items WHERE product_id = s.product_id ORDER BY grn_item_id DESC LIMIT 1), 0))\n        , 0) as total_value\n        FROM stock s\n        WHERE 1 = 1" . $storeScopeFilter);
    $stats['stock_value'] = (float)$result->fetch_assoc()['total_value'];

    // Low Stock Items
    $result = $db->query("\n        SELECT COUNT(*) as count\n        FROM stock s\n        JOIN products p ON p.product_id = s.product_id\n        WHERE p.status = 'active'\n          AND s.quantity_on_hand <= s.reorder_level\n          AND s.quantity_on_hand > 0" . $storeScopeFilter);
    $stats['low_stock'] = (int)$result->fetch_assoc()['count'];

    // Out of Stock
    $result = $db->query("\n        SELECT COUNT(*) as count\n        FROM stock s\n        JOIN products p ON p.product_id = s.product_id\n        WHERE p.status = 'active'\n          AND s.quantity_on_hand = 0" . $storeScopeFilter);
    $stats['out_of_stock'] = (int)$result->fetch_assoc()['count'];
}

// Recent GRNs
$recentGRNs = [];
if (!$denyScopedDashboard) {
    $recentGRNs = $db->query("
        SELECT g.*, s.store_name, sup.supplier_name
        FROM grn g
        JOIN stores s ON g.store_id = s.store_id
        JOIN suppliers sup ON g.supplier_id = sup.supplier_id"
        . $grnStoreScopeFilter . "
        ORDER BY g.receipt_date DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
}

// Pending Requisitions
$pendingReqs = [];
if (!$denyScopedDashboard) {
    $pendingReqs = $db->query("
        SELECT r.*, d.dept_name, s.store_name, u.full_name
        FROM requisitions r
        JOIN departments d ON r.department_id = d.dept_id
        JOIN stores s ON r.store_id = s.store_id
        JOIN users u ON r.requested_by = u.user_id
        WHERE r.status = 'pending'"
        . $requisitionScopeFilter . "
        ORDER BY r.requested_date DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
}

// Recent Transactions
$recentTransactions = [];
if (!$denyScopedDashboard) {
    $recentTransactions = $db->query("
        SELECT st.*, p.product_name, s.store_name
        FROM stock_transactions st
        JOIN products p ON st.product_id = p.product_id
        JOIN stores s ON st.store_id = s.store_id"
        . $transactionStoreScopeFilter . "
        ORDER BY st.transaction_date DESC
        LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);
}

include 'app/views/layout-header.php';
?>

<div class="page-header">
    <h1>Welcome, <?php echo $user['full_name']; ?>! 👋</h1>
    <p>Here's an overview of your permitted inventory scope.</p>
</div>

<!-- Statistics Row -->
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><i class="fas fa-box"></i> Total Products</div>
            <div class="stat-value"><?php echo $stats['total_products']; ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card success">
            <div class="stat-label"><i class="fas fa-dollar-sign"></i> Stock Value</div>
            <div class="stat-value">$<?php echo number_format($stats['stock_value'], 2); ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card warning">
            <div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Low Stock</div>
            <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card danger">
            <div class="stat-label"><i class="fas fa-times-circle"></i> Out of Stock</div>
            <div class="stat-value"><?php echo $stats['out_of_stock']; ?></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent GRNs -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-file-import"></i> Recent GRN Receipts</h5>
            </div>
            <div class="card-body">
                <?php if (count($recentGRNs) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>GRN #</th>
                            <th>Store</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentGRNs as $grn): ?>
                        <tr>
                            <td><small><strong><?php echo htmlspecialchars($grn['grn_number']); ?></strong></small></td>
                            <td><small><?php echo htmlspecialchars($grn['store_name']); ?></small></td>
                            <td><small><?php echo formatDate($grn['receipt_date'], 'Y-m-d'); ?></small></td>
                            <td>
                                <span class="badge badge-<?php echo $grn['status'] === 'received' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($grn['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No GRNs found</p>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>pages/grn/index.php" class="btn btn-sm btn-outline-primary mt-3">View All GRNs</a>
            </div>
        </div>
    </div>

    <!-- Pending Requisitions -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Pending Requisitions</h5>
            </div>
            <div class="card-body">
                <?php if (count($pendingReqs) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Req #</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingReqs as $req): ?>
                        <tr>
                            <td><small><strong><?php echo htmlspecialchars($req['requisition_number']); ?></strong></small></td>
                            <td><small><?php echo htmlspecialchars($req['dept_name']); ?></small></td>
                            <td><small><?php echo formatDate($req['requested_date'], 'Y-m-d'); ?></small></td>
                            <td>
                                <span class="badge badge-warning">
                                    <?php echo ucfirst($req['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No pending requisitions</p>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>pages/requisition/index.php" class="btn btn-sm btn-outline-primary mt-3">View All Requisitions</a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-exchange-alt"></i> Recent Stock Transactions</h5>
            </div>
            <div class="card-body">
                <?php if (count($recentTransactions) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Store</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <tr>
                            <td><small><?php echo formatDate($transaction['transaction_date'], 'Y-m-d H:i'); ?></small></td>
                            <td><small><?php echo htmlspecialchars($transaction['product_name']); ?></small></td>
                            <td><small><?php echo htmlspecialchars($transaction['store_name']); ?></small></td>
                            <td>
                                <span class="badge badge-<?php echo $transaction['transaction_type'] === 'receipt' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                </span>
                            </td>
                            <td><small><?php echo $transaction['quantity_change']; ?></small></td>
                            <td><small><?php echo $transaction['unit_price'] ? '$' . number_format($transaction['unit_price'], 2) : '-'; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No transactions found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'app/views/layout-footer.php'; ?>
