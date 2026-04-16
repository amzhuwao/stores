<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requirePermission('settings.view');
if (!hasRole('Admin')) {
    http_response_code(403);
    die('Access denied: admin only page.');
}

$db = Database::getInstance()->getConnection();
$currentUser = getCurrentUser();
$rolePermissions = getRolePermissions();

$summaryQueries = [
    'users' => 'SELECT COUNT(*) AS c FROM users',
    'roles' => 'SELECT COUNT(*) AS c FROM roles',
    'stores' => 'SELECT COUNT(*) AS c FROM stores',
    'departments' => 'SELECT COUNT(*) AS c FROM departments',
    'products' => 'SELECT COUNT(*) AS c FROM products',
    'suppliers' => 'SELECT COUNT(*) AS c FROM suppliers'
];

$summary = [];
foreach ($summaryQueries as $key => $sql) {
    $result = $db->query($sql);
    $summary[$key] = (int)($result->fetch_assoc()['c'] ?? 0);
}

$systemInfo = [
    ['label' => 'Application', 'value' => APP_NAME],
    ['label' => 'Version', 'value' => APP_VERSION],
    ['label' => 'Environment', 'value' => APP_ENV],
    ['label' => 'Debug Mode', 'value' => APP_DEBUG ? 'Enabled' : 'Disabled'],
    ['label' => 'Site URL', 'value' => SITE_URL],
    ['label' => 'Database', 'value' => DB_NAME],
    ['label' => 'Timezone', 'value' => date_default_timezone_get()],
    ['label' => 'Session Timeout', 'value' => gmdate('H:i:s', SESSION_TIMEOUT)],
    ['label' => 'Upload Directory', 'value' => UPLOAD_DIR],
    ['label' => 'Uploads Ready', 'value' => is_dir(UPLOAD_DIR) ? 'Yes' : 'No'],
    ['label' => 'Current User', 'value' => $currentUser['full_name'] ?? 'Unknown'],
    ['label' => 'Current Role', 'value' => $currentUser['role_name'] ?? 'Unknown']
];

$pageTitle = 'System Settings';
$activePage = 'settings';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1>System Settings</h1>
        <p>Read-only configuration, security, and access overview for the stores platform.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo SITE_URL; ?>pages/users/index.php" class="btn btn-primary">Manage Users</a>
        <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
        <a href="<?php echo SITE_URL; ?>pages/audit/index.php" class="btn btn-outline-secondary">View Audit Log</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-2">
        <div class="stat-card">
            <div class="stat-label">Users</div>
            <div class="stat-value"><?php echo (int)$summary['users']; ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="stat-card success">
            <div class="stat-label">Roles</div>
            <div class="stat-value"><?php echo (int)$summary['roles']; ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="stat-card warning">
            <div class="stat-label">Stores</div>
            <div class="stat-value"><?php echo (int)$summary['stores']; ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="stat-card danger">
            <div class="stat-label">Departments</div>
            <div class="stat-value"><?php echo (int)$summary['departments']; ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="stat-card">
            <div class="stat-label">Products</div>
            <div class="stat-value"><?php echo (int)$summary['products']; ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-2">
        <div class="stat-card success">
            <div class="stat-label">Suppliers</div>
            <div class="stat-value"><?php echo (int)$summary['suppliers']; ?></div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-shield-alt"></i> Environment & Security</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($systemInfo as $item): ?>
                                <tr>
                                    <th style="width: 45%;"><?php echo htmlspecialchars($item['label']); ?></th>
                                    <td><?php echo htmlspecialchars((string)$item['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-key"></i> Access Snapshot</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">The role matrix below is read-only and reflects the hardcoded permissions currently enforced by the app.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Allowed Areas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rolePermissions as $roleName => $permissions): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($roleName); ?></strong></td>
                                    <td>
                                        <?php if (in_array('*', $permissions, true)): ?>
                                            <span class="badge bg-dark">All Access</span>
                                        <?php else: ?>
                                            <?php foreach ($permissions as $permission): ?>
                                                <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars($permission); ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>