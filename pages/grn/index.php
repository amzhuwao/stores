<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new GRNController();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUser = getCurrentUser();
$roleName = trim((string)($currentUser['role_name'] ?? ''));
$isGlobalGrnRole = in_array($roleName, ['Admin', 'Manager', 'Storekeeper'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . SITE_URL . 'pages/grn/index.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'verify') {
        $result = $controller->verify($_POST['grn_id'] ?? 0);
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        header('Location: ' . SITE_URL . 'pages/grn/view.php?id=' . (int)($_POST['grn_id'] ?? 0));
        exit;
    }
}

$filters = [
    'q' => $_GET['q'] ?? '',
    'store_id' => $_GET['store_id'] ?? '',
    'supplier_id' => $_GET['supplier_id'] ?? '',
    'status' => $_GET['status'] ?? 'all'
];

$grns = $controller->getGRNs($filters, $currentUserId);
$stores = $controller->getStores($currentUserId);
$suppliers = $controller->getSuppliers();
$stats = $controller->getStats($currentUserId);

$pageTitle = 'GRN Management';
$activePage = 'grn';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>GRN</h1>
        <p>Record deliveries from suppliers and verify them into stock.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/grn/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> New GRN
    </a>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Draft</div>
            <div class="stat-value"><?php echo (int)$stats['draft']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">Received</div>
            <div class="stat-value"><?php echo (int)$stats['received']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Verified</div>
            <div class="stat-value"><?php echo (int)$stats['verified']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">GRN Value</div>
            <div class="stat-value">$<?php echo number_format($stats['total_value'], 2); ?></div>
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
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="GRN, supplier, or store">
            </div>
            <div class="col-md-3">
                <label class="form-label">Store</label>
                <?php if ($isGlobalGrnRole): ?>
                    <select name="store_id" class="form-control">
                        <option value="">All Stores</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo ((string)$filters['store_id'] === (string)$store['store_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($store['store_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (count($stores) === 1): ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($stores[0]['store_name']); ?>" readonly>
                    <input type="hidden" name="store_id" value="<?php echo (int)$stores[0]['store_id']; ?>">
                <?php else: ?>
                    <select name="store_id" class="form-control">
                        <option value="">Your Stores</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo ((string)$filters['store_id'] === (string)$store['store_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($store['store_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-control">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo (int)$supplier['supplier_id']; ?>" <?php echo ((string)$filters['supplier_id'] === (string)$supplier['supplier_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="draft" <?php echo $filters['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="received" <?php echo $filters['status'] === 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="verified" <?php echo $filters['status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo SITE_URL; ?>pages/grn/index.php" class="btn btn-outline-primary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-file-import"></i> GRN List</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($grns)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>GRN #</th>
                            <th>Invoice #</th>
                            <th>Supplier</th>
                            <th>Store</th>
                            <th>Date</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                            <th>Received By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grns as $grn): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($grn['grn_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($grn['invoice_reference'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($grn['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($grn['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($grn['receipt_date']); ?></td>
                                <td>$<?php echo number_format((float)($grn['total_cost'] ?? 0), 2); ?></td>
                                <td><span class="badge badge-<?php echo $grn['status'] === 'verified' ? 'success' : ($grn['status'] === 'received' ? 'warning' : 'info'); ?>"><?php echo ucfirst($grn['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($grn['received_by_name']); ?></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>pages/grn/view.php?id=<?php echo (int)$grn['grn_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No GRNs found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
