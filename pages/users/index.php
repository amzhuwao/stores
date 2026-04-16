<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new UserController();

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'role_id' => $_GET['role_id'] ?? '',
    'status' => $_GET['status'] ?? 'all'
];

$users = $controller->getUsers($filters);
$roles = $controller->getRoles();
$stats = $controller->getStats();

$pageTitle = 'Users Management';
$activePage = 'users';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Users Management</h1>
        <p>Create and manage system users, roles, and account status.</p>
    </div>
    <?php if (can('users.create')): ?>
        <a href="<?php echo SITE_URL; ?>pages/users/create.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add User
        </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Users</div>
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
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Username, full name, or email" value="<?php echo htmlspecialchars($filters['q']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-control">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo (int)$role['role_id']; ?>" <?php echo ((string)$filters['role_id'] === (string)$role['role_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100" type="submit">Apply</button>
                <a class="btn btn-outline-primary w-100" href="<?php echo SITE_URL; ?>pages/users/index.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-users"></i> Users</h5></div>
    <div class="card-body">
        <?php if (!empty($users)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(formatDate($user['created_at'], 'Y-m-d')); ?></td>
                                <td>
                                    <?php if (can('users.edit')): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>pages/users/edit.php?id=<?php echo (int)$user['user_id']; ?>">
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
            <div class="alert alert-info mb-0">No users found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>