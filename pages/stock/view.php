<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new StockController();
$currentUser = getCurrentUser();
$roleName = trim((string)($currentUser['role_name'] ?? ''));
$isGlobalStockRole = in_array($roleName, ['Admin', 'Manager', 'Storekeeper'], true);

$filters = [
    'q' => $_GET['q'] ?? '',
    'store_id' => $_GET['store_id'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'stock_state' => $_GET['stock_state'] ?? 'all'
];

$stores = $controller->getStores();
$categories = $controller->getCategories();
$rows = $controller->getStockLevels($filters);
$stats = $controller->getStats($rows);
$selectedStoreId = ctype_digit((string)$filters['store_id']) ? (int)$filters['store_id'] : null;

$pageTitle = 'Stock Levels';
$activePage = 'stock';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header">
    <h1>Stock Levels</h1>
    <p>Track current stock position across your permitted store scope with low-stock and out-of-stock visibility.</p>
</div>

<div class="row">
    <div class="col-md-4 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Stock Lines</div>
            <div class="stat-value"><?php echo (int)$stats['lines']; ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="stat-card warning">
            <div class="stat-label">Low Stock</div>
            <div class="stat-value"><?php echo (int)$stats['low']; ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="stat-card danger">
            <div class="stat-label">Out Of Stock</div>
            <div class="stat-value"><?php echo (int)$stats['out']; ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="stat-card success">
            <div class="stat-label">Estimated Value</div>
            <div class="stat-value">$<?php echo number_format($stats['value'], 2); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Search & Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Product name, code, or store" value="<?php echo htmlspecialchars($filters['q']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Store</label>
                <?php if ($isGlobalStockRole): ?>
                    <select name="store_id" class="form-control">
                        <option value="">All Stores</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo ((string)$filters['store_id'] === (string)$store['store_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (count($stores) === 1): ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($stores[0]['store_name']); ?>" readonly>
                    <input type="hidden" name="store_id" value="<?php echo (int)$stores[0]['store_id']; ?>">
                <?php else: ?>
                    <select name="store_id" class="form-control">
                        <option value="">Your Stores</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo $selectedStoreId === (int)$store['store_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['category_id']; ?>" <?php echo ((string)$filters['category_id'] === (string)$category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">State</label>
                <select name="stock_state" class="form-control">
                    <option value="all" <?php echo $filters['stock_state'] === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="ok" <?php echo $filters['stock_state'] === 'ok' ? 'selected' : ''; ?>>Healthy</option>
                    <option value="low" <?php echo $filters['stock_state'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="out" <?php echo $filters['stock_state'] === 'out' ? 'selected' : ''; ?>>Out</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <a class="btn btn-outline-primary" href="<?php echo SITE_URL; ?>pages/stock/view.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-warehouse"></i> Stock by Store Scope</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($rows)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Store</th>
                            <th>Product</th>
                            <th>Code</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Reorder</th>
                            <th>State</th>
                            <th>Unit Price</th>
                            <th>Line Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $qty = (int)$row['quantity_on_hand'];
                            $reorder = (int)$row['reorder_level'];
                            $unitPrice = (float)$row['unit_price'];
                            $lineValue = $qty * $unitPrice;

                            if ($qty === 0) {
                                $state = 'Out';
                                $badge = 'danger';
                            } elseif ($qty <= $reorder) {
                                $state = 'Low';
                                $badge = 'warning';
                            } else {
                                $state = 'Healthy';
                                $badge = 'success';
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['store_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['product_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['unit_of_measure']); ?></td>
                                <td><?php echo $qty; ?></td>
                                <td><?php echo $reorder; ?></td>
                                <td><span class="badge badge-<?php echo $badge; ?>"><?php echo $state; ?></span></td>
                                <td>$<?php echo number_format($unitPrice, 2); ?></td>
                                <td>$<?php echo number_format($lineValue, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No stock rows found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
