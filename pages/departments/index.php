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

$controller = new DepartmentController();
$departments = $controller->getDepartments($filters);
$stats = $controller->getStats();

$pageTitle = 'Departments';
$activePage = 'departments';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Departments</h1>
        <p>View requesting departments, assigned heads, and requisition activity.</p>
    </div>
    <?php if (can('departments.create')): ?>
        <a href="<?php echo SITE_URL; ?>pages/departments/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Department
        </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Departments</div>
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
                <input type="text" name="q" class="form-control" placeholder="Department name, code, or head" value="<?php echo htmlspecialchars($filters['q']); ?>">
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
                <a class="btn btn-outline-primary" href="<?php echo SITE_URL; ?>pages/departments/index.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-sitemap"></i> Department Directory</h5></div>
    <div class="card-body">
        <?php if (!empty($departments)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Code</th>
                            <th>Head</th>
                            <th>Monthly Budget</th>
                            <th>Weekly Budget</th>
                            <th>Requisitions</th>
                            <th>Pending</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $department): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($department['dept_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($department['dept_code']); ?></td>
                                <td><?php echo htmlspecialchars($department['head_name'] ?: '-'); ?></td>
                                <td>$<?php echo number_format((float)($department['monthly_budget'] ?? 0), 2); ?></td>
                                <td>$<?php echo number_format((float)($department['weekly_budget'] ?? 0), 2); ?></td>
                                <td><?php echo (int)$department['requisitions_count']; ?></td>
                                <td><?php echo (int)$department['pending_requisitions']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $department['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($department['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (can('departments.edit')): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>pages/departments/edit.php?id=<?php echo (int)$department['dept_id']; ?>">
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
            <div class="alert alert-info mb-0">No departments found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>