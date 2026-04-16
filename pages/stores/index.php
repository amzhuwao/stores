<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => $_GET['status'] ?? 'active'
];

$controller = new StoreController();
$stores = $controller->getStores($filters);
$stats = $controller->getStats();

$pageTitle = 'Stores';
$activePage = 'stores';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Stores</h1>
        <p>View all physical store locations, responsible users, and stock coverage.</p>
    </div>
    <?php if (can('stores.create')): ?>
        <a href="<?php echo SITE_URL; ?>pages/stores/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Store
        </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Stores</div>
            <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-label">Active</div>
            <div class="stat-value"><?php echo (int)$stats['active']; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-label">Inactive</div>
            <div class="stat-value"><?php echo (int)$stats['inactive']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-filter"></i> Search & Filters</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Store name, code, location, or responsible person" value="<?php echo htmlspecialchars($filters['q']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-outline-primary" href="<?php echo SITE_URL; ?>pages/stores/index.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-building"></i> Store Directory</h5></div>
    <div class="card-body">
        <?php if (!empty($stores)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Store</th>
                            <th>Code</th>
                            <th>Location</th>
                            <th>Responsible</th>
                            <th>Products</th>
                            <th>Total Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stores as $store): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($store['store_name']); ?></strong>
                                    <?php if (!empty($store['description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($store['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($store['store_code']); ?></td>
                                <td><?php echo htmlspecialchars($store['location'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($store['responsible_person'] ?: '-'); ?></td>
                                <td><?php echo (int)$store['products_count']; ?></td>
                                <td><?php echo (int)$store['total_stock_qty']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $store['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($store['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (can('stores.edit')): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>pages/stores/edit.php?id=<?php echo (int)$store['store_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">No stores found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>