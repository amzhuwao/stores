<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new RequisitionController();

$filters = [
    'q' => $_GET['q'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'store_id' => $_GET['store_id'] ?? '',
    'status' => $_GET['status'] ?? 'all'
];

$requisitions = $controller->getRequisitions($filters);
$departments = $controller->getDepartments();
$stores = $controller->getStores();
$stats = $controller->getStats();

$pageTitle = 'Requisitions';
$activePage = 'requisition';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Requisitions</h1>
        <p>Create and review department stock requests from each store.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/requisition/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Requisition
    </a>
</div>

<div class="row">
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-label">Pending</div><div class="stat-value"><?php echo (int)$stats['pending']; ?></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-label">Approved</div><div class="stat-value"><?php echo (int)$stats['approved']; ?></div></div></div>
    <div class="col-md-3"><div class="stat-card danger"><div class="stat-label">Rejected</div><div class="stat-value"><?php echo (int)$stats['rejected']; ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Issued</div><div class="stat-value"><?php echo (int)$stats['issued']; ?></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-filter"></i> Search & Filters</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Requisition no, department, or store">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo (int)$department['dept_id']; ?>" <?php echo ((string)$filters['department_id'] === (string)$department['dept_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['dept_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Store</label>
                <select name="store_id" class="form-control">
                    <option value="">All Stores</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo (int)$store['store_id']; ?>" <?php echo ((string)$filters['store_id'] === (string)$store['store_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($store['store_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filters['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="issued" <?php echo $filters['status'] === 'issued' ? 'selected' : ''; ?>>Issued</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo SITE_URL; ?>pages/requisition/index.php" class="btn btn-outline-primary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-list"></i> Requisition List</h5></div>
    <div class="card-body">
        <?php if (!empty($requisitions)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Requisition #</th>
                            <th>Department</th>
                            <th>Store</th>
                            <th>Requested By</th>
                            <th>Requested Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requisitions as $req): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($req['requisition_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['requested_by_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['requested_date']); ?></td>
                                <td>
                                    <?php
                                    $badge = 'info';
                                    if ($req['status'] === 'approved') $badge = 'success';
                                    if ($req['status'] === 'pending') $badge = 'warning';
                                    if ($req['status'] === 'rejected') $badge = 'danger';
                                    ?>
                                    <span class="badge badge-<?php echo $badge; ?>"><?php echo ucfirst($req['status']); ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>pages/requisition/view.php?id=<?php echo (int)$req['requisition_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No requisitions found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
