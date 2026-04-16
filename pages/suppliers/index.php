<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new SupplierController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . SITE_URL . 'pages/suppliers/index.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'toggle_status') {
        $result = $controller->setStatus($_POST['supplier_id'] ?? 0, $_POST['target_status'] ?? 'inactive');
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        header('Location: ' . SITE_URL . 'pages/suppliers/index.php');
        exit;
    }
}

$filters = [
    'q' => $_GET['q'] ?? '',
    'status' => $_GET['status'] ?? 'active'
];

$suppliers = $controller->getSuppliers($filters);
$stats = $controller->getStats();

$pageTitle = 'Suppliers';
$activePage = 'suppliers';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Suppliers</h1>
        <p>Manage supplier master data used by GRN and purchasing workflows.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/suppliers/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Supplier
    </a>
</div>

<div class="row">
    <div class="col-md-4 col-lg-4">
        <div class="stat-card">
            <div class="stat-label">Total Suppliers</div>
            <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-4">
        <div class="stat-card success">
            <div class="stat-label">Active</div>
            <div class="stat-value"><?php echo (int)$stats['active']; ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-4">
        <div class="stat-card warning">
            <div class="stat-label">Inactive</div>
            <div class="stat-value"><?php echo (int)$stats['inactive']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Search & Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Supplier, contact, email, or phone" value="<?php echo htmlspecialchars($filters['q']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
                <a href="<?php echo SITE_URL; ?>pages/suppliers/index.php" class="btn btn-outline-primary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-truck"></i> Supplier List</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($suppliers)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($supplier['contact_person'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['email'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['phone'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['city'] ?: '-'); ?></td>
                                <td><span class="badge badge-<?php echo $supplier['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo ucfirst($supplier['status']); ?></span></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>pages/suppliers/edit.php?id=<?php echo (int)$supplier['supplier_id']; ?>"><i class="fas fa-edit"></i></a>
                                        <form method="POST" style="display:inline;">
                                            <?php echo getCSRFTokenField(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="supplier_id" value="<?php echo (int)$supplier['supplier_id']; ?>">
                                            <input type="hidden" name="target_status" value="<?php echo $supplier['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $supplier['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                                <i class="fas <?php echo $supplier['status'] === 'active' ? 'fa-eye-slash' : 'fa-check'; ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No suppliers found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
