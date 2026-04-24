<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new StockIssueController();

$filters = [
    'q' => $_GET['q'] ?? '',
    'store_id' => $_GET['store_id'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'status' => $_GET['status'] ?? 'all'
];

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$issues = $controller->getIssues($filters, $currentUserId);
$stores = $controller->getStores($currentUserId);
$departments = $controller->getDepartments($currentUserId);
$stats = $controller->getStats($currentUserId);

$pageTitle = 'Stock Issues';
$activePage = 'issues';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Stock Issues</h1>
        <p>Issue approved requisitions to departments and reduce store stock automatically.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/stock-issues/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Stock Issue
    </a>
</div>

<div class="row">
    <div class="col-md-4"><div class="stat-card warning"><div class="stat-label">Issued</div><div class="stat-value"><?php echo (int)$stats['issued']; ?></div></div></div>
    <div class="col-md-4"><div class="stat-card success"><div class="stat-label">Received</div><div class="stat-value"><?php echo (int)$stats['received']; ?></div></div></div>
    <div class="col-md-4"><div class="stat-card danger"><div class="stat-label">Cancelled</div><div class="stat-value"><?php echo (int)$stats['cancelled']; ?></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-filter"></i> Search & Filters</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Issue #, department, or store">
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
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo (int)$department['dept_id']; ?>" <?php echo ((string)$filters['department_id'] === (string)$department['dept_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['dept_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="issued" <?php echo $filters['status'] === 'issued' ? 'selected' : ''; ?>>Issued</option>
                    <option value="received" <?php echo $filters['status'] === 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo SITE_URL; ?>pages/stock-issues/index.php" class="btn btn-outline-primary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-arrow-right"></i> Issue List</h5></div>
    <div class="card-body">
        <?php if (!empty($issues)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Issue #</th>
                            <th>Requisition #</th>
                            <th>Department</th>
                            <th>Store</th>
                            <th>Issued By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issues as $issue): ?>
                            <?php
                            $badge = 'warning';
                            if ($issue['status'] === 'received') $badge = 'success';
                            if ($issue['status'] === 'cancelled') $badge = 'danger';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($issue['issue_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($issue['requisition_number'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($issue['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($issue['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($issue['issued_by_name']); ?></td>
                                <td><?php echo htmlspecialchars($issue['issue_date']); ?></td>
                                <td><span class="badge badge-<?php echo $badge; ?>"><?php echo ucfirst($issue['status']); ?></span></td>
                                <td><a href="<?php echo SITE_URL; ?>pages/stock-issues/view.php?id=<?php echo (int)$issue['issue_id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No stock issues found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
