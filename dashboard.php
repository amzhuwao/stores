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

// Get dashboard statistics
$stats = [];

// Total Products
$result = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$stats['total_products'] = $result->fetch_assoc()['count'];

// Total Stock Value (sum of quantity * unit price from last GRN)
$result = $db->query("
    SELECT COALESCE(SUM(s.quantity_on_hand * 
        COALESCE((SELECT unit_price FROM grn_items WHERE product_id = s.product_id ORDER BY grn_item_id DESC LIMIT 1), 0))
    , 0) as total_value 
    FROM stock s
");
$stats['stock_value'] = $result->fetch_assoc()['total_value'];

// Low Stock Items
$result = $db->query("
    SELECT COUNT(*) as count FROM stock 
    WHERE quantity_on_hand <= reorder_level AND quantity_on_hand > 0
");
$stats['low_stock'] = $result->fetch_assoc()['count'];

// Out of Stock
$result = $db->query("
    SELECT COUNT(*) as count FROM stock 
    WHERE quantity_on_hand = 0
");
$stats['out_of_stock'] = $result->fetch_assoc()['count'];

// Recent GRNs
$recentGRNs = $db->query("
    SELECT g.*, s.store_name, sup.supplier_name 
    FROM grn g
    JOIN stores s ON g.store_id = s.store_id
    JOIN suppliers sup ON g.supplier_id = sup.supplier_id
    ORDER BY g.receipt_date DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Pending Requisitions
$pendingReqs = $db->query("
    SELECT r.*, d.dept_name, s.store_name, u.full_name
    FROM requisitions r
    JOIN departments d ON r.department_id = d.dept_id
    JOIN stores s ON r.store_id = s.store_id
    JOIN users u ON r.requested_by = u.user_id
    WHERE r.status = 'pending'
    ORDER BY r.requested_date DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent Transactions
$recentTransactions = $db->query("
    SELECT st.*, p.product_name, s.store_name
    FROM stock_transactions st
    JOIN products p ON st.product_id = p.product_id
    JOIN stores s ON st.store_id = s.store_id
    ORDER BY st.transaction_date DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

include 'app/views/layout-header.php';
?>

<div class="page-header">
    <h1>Welcome, <?php echo $user['full_name']; ?>! 👋</h1>
    <p>Here's an overview of your inventory management system</p>
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
