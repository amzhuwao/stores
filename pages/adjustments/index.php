<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new AdjustmentController();

$filters = [
    'status' => $_GET['status'] ?? 'all',
    'store_id' => $_GET['store_id'] ?? '',
    'reason' => $_GET['reason'] ?? ''
];

$stores = $controller->getStores();
$stats = $controller->getStats();
$adjustments = $controller->getAdjustments($filters);

$pageTitle = 'Stock Adjustments';
$activePage = 'adjustments';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Stock Adjustments</h1>
        <p>Review stock corrections due to damage, count variance, loss, and recalls.</p>
    </div>
    <?php if (can('adjustments.create')): ?>
        <a href="<?php echo SITE_URL; ?>pages/adjustments/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Adjustment
        </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total</div>
            <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo (int)$stats['pending']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?php echo (int)$stats['approved']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-label">Rejected</div>
            <div class="stat-value"><?php echo (int)$stats['rejected']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-filter"></i> Filters</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filters['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Store</label>
                <select name="store_id" class="form-control">
                    <option value="">All Stores</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo (int)$store['store_id']; ?>" <?php echo ((string)$filters['store_id'] === (string)$store['store_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($store['store_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Reason</label>
                <select name="reason" class="form-control">
                    <option value="">All Reasons</option>
                    <option value="damage" <?php echo $filters['reason'] === 'damage' ? 'selected' : ''; ?>>Damage</option>
                    <option value="loss" <?php echo $filters['reason'] === 'loss' ? 'selected' : ''; ?>>Loss</option>
                    <option value="correction" <?php echo $filters['reason'] === 'correction' ? 'selected' : ''; ?>>Correction</option>
                    <option value="count_variance" <?php echo $filters['reason'] === 'count_variance' ? 'selected' : ''; ?>>Count Variance</option>
                    <option value="recall" <?php echo $filters['reason'] === 'recall' ? 'selected' : ''; ?>>Recall</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="<?php echo SITE_URL; ?>pages/adjustments/index.php" class="btn btn-outline-primary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-sync"></i> Adjustment Register</h5></div>
    <div class="card-body">
        <?php if (!empty($adjustments)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Adjustment #</th>
                            <th>Store</th>
                            <th>Reason</th>
                            <th>Items</th>
                            <th>Net Qty</th>
                            <th>Status</th>
                            <th>Adjusted By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjustments as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['adjustment_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['store_name']); ?></td>
                                <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['adjustment_reason']))); ?></td>
                                <td><?php echo (int)$row['item_count']; ?></td>
                                <td><?php echo (int)$row['net_quantity_change']; ?></td>
                                <td>
                                    <?php
                                        $badge = 'warning';
                                        if ($row['status'] === 'approved') {
                                            $badge = 'success';
                                        } elseif ($row['status'] === 'rejected') {
                                            $badge = 'danger';
                                        }
                                    ?>
                                    <span class="badge badge-<?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['adjusted_by_name']); ?></td>
                                <td><?php echo formatDate($row['adjustment_date'], 'Y-m-d H:i'); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>pages/adjustments/view.php?id=<?php echo (int)$row['adjustment_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">No stock adjustments found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>