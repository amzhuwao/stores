<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requirePermission('settings.manage');

$currentUser = getCurrentUser();
$consumption = new Consumption();

$message = '';
$messageType = '';
$tableError = false;

// Check if consumption_permissions table exists
$db = Database::getInstance()->getConnection();
$result = $db->query("SHOW TABLES LIKE 'consumption_permissions'");
if ($result->num_rows === 0) {
    $tableError = true;
    $message = 'Error: Database tables for consumption tracking have not been created yet. Please run the database schema update.';
    $messageType = 'danger';
}

$action = $_GET['action'] ?? '';
$departmentId = (int)($_GET['dept_id'] ?? 0);

if (!$tableError && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'assign_permission') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $deptId = (int)($_POST['department_id'] ?? 0);
        
        if (!$userId || !$deptId) {
            $message = 'Invalid user or department selected';
            $messageType = 'danger';
        } else {
            if ($consumption->assignPermission($userId, $deptId, $currentUser['user_id'])) {
                $message = 'Permission assigned successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error assigning permission';
                $messageType = 'danger';
            }
        }
    } elseif ($postAction === 'revoke_permission') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $deptId = (int)($_POST['department_id'] ?? 0);
        
        if (!$userId || !$deptId) {
            $message = 'Invalid user or department';
            $messageType = 'danger';
        } else {
            if ($consumption->revokePermission($userId, $deptId)) {
                $message = 'Permission revoked successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error revoking permission';
                $messageType = 'danger';
            }
        }
    }
}

// Get all departments and users
$deptSql = "SELECT dept_id, dept_name FROM departments WHERE status = 'active' ORDER BY dept_name ASC";
$departments = $db->query($deptSql)->fetch_all(MYSQLI_ASSOC);

$userSql = "SELECT u.user_id, u.full_name, u.username, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.status = 'active'
            ORDER BY u.full_name ASC";
$users = $db->query($userSql)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Consumption Logging Permissions';
?>

<?php include __DIR__ . '/../../app/views/layout-header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Manage which users can log consumption for each department</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($tableError): ?>
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">⚠️ Setup Required</h5>
            </div>
            <div class="card-body">
                <p>The consumption tracking feature requires database tables that have not been created yet.</p>
                <h6>Steps to enable:</h6>
                <ol>
                    <li>Make a backup of your database</li>
                    <li>Run the updated <code>database/schema.sql</code> file</li>
                    <li>Refresh this page</li>
                </ol>
                <p class="mb-0"><small class="text-muted">The schema update includes the <code>consumption_permissions</code> and <code>consumption_records</code> tables.</small></p>
            </div>
        </div>
    <?php else: ?>

    <div class="row">
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <?php foreach ($departments as $idx => $dept): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                id="dept-tab-<?php echo (int)$dept['dept_id']; ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#dept-panel-<?php echo (int)$dept['dept_id']; ?>" 
                                type="button">
                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <?php foreach ($departments as $idx => $dept): 
                    $permissionedUsers = $consumption->getPermissionedUsersForDepartment((int)$dept['dept_id']);
                ?>
                    <div class="tab-pane fade <?php echo $idx === 0 ? 'show active' : ''; ?>" 
                         id="dept-panel-<?php echo (int)$dept['dept_id']; ?>" 
                         role="tabpanel">
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Users with Consumption Logging Permission</h5>
                            </div>
                            
                            <?php if (empty($permissionedUsers)): ?>
                                <div class="card-body text-center text-muted">
                                    <p>No users have been assigned consumption logging permission for this department.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Assigned At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($permissionedUsers as $user): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($user['role_name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y H:i', strtotime($user['assigned_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="revoke_permission">
                                                            <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                                                            <input type="hidden" name="department_id" value="<?php echo (int)$user['department_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                                    onclick="return confirm('Revoke permission for this user?')">
                                                                Revoke
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Assign New Permission</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="assign_permission">
                                    <input type="hidden" name="department_id" value="<?php echo (int)$dept['dept_id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="user-select-<?php echo (int)$dept['dept_id']; ?>" class="form-label">Select User</label>
                                        <select name="user_id" id="user-select-<?php echo (int)$dept['dept_id']; ?>" class="form-select" required>
                                            <option value="">-- Select a user --</option>
                                            <?php foreach ($users as $user): 
                                                // Check if user already has permission for this department
                                                $hasPermission = false;
                                                foreach ($permissionedUsers as $pu) {
                                                    if ((int)$pu['user_id'] === (int)$user['user_id']) {
                                                        $hasPermission = true;
                                                        break;
                                                    }
                                                }
                                                if (!$hasPermission):
                                            ?>
                                                <option value="<?php echo (int)$user['user_id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?> 
                                                    (<?php echo htmlspecialchars($user['role_name']); ?>)
                                                </option>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Only users without existing permission are shown</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Assign Permission</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Overview</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Total Departments:</dt>
                        <dd class="col-sm-6"><strong><?php echo count($departments); ?></strong></dd>
                        
                        <dt class="col-sm-6">Total Users:</dt>
                        <dd class="col-sm-6"><strong><?php echo count($users); ?></strong></dd>
                        
                        <dt class="col-sm-6">Total Permissions:</dt>
                        <dd class="col-sm-6">
                            <strong>
                                <?php 
                                $totalPerms = 0;
                                foreach ($departments as $d) {
                                    $totalPerms += count($consumption->getPermissionedUsersForDepartment((int)$d['dept_id']));
                                }
                                echo $totalPerms;
                                ?>
                            </strong>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Tips</h5>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Assign consumption logging permissions to staff members</li>
                        <li>Users can only log consumption for departments they have permission for</li>
                        <li>Department heads can also manage permissions from their staff page</li>
                        <li>All consumption records are audit-logged</li>
                        <li>Revoke permissions when staff members change roles</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Permission Levels</h5>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <strong>Can Log Consumption:</strong>
                        <p class="text-muted mb-0">User can record when issued items are consumed</p>
                    </div>
                    <hr>
                    <div>
                        <strong>Can View Reports:</strong>
                        <p class="text-muted mb-0">User can access consumption reports and analytics</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>


<?php include __DIR__ . '/../../app/views/layout-header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Manage which users can log consumption for each department</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <?php foreach ($departments as $idx => $dept): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                id="dept-tab-<?php echo (int)$dept['dept_id']; ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#dept-panel-<?php echo (int)$dept['dept_id']; ?>" 
                                type="button">
                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <?php foreach ($departments as $idx => $dept): 
                    $permissionedUsers = $consumption->getPermissionedUsersForDepartment((int)$dept['dept_id']);
                ?>
                    <div class="tab-pane fade <?php echo $idx === 0 ? 'show active' : ''; ?>" 
                         id="dept-panel-<?php echo (int)$dept['dept_id']; ?>" 
                         role="tabpanel">
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Users with Consumption Logging Permission</h5>
                            </div>
                            
                            <?php if (empty($permissionedUsers)): ?>
                                <div class="card-body text-center text-muted">
                                    <p>No users have been assigned consumption logging permission for this department.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Assigned At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($permissionedUsers as $user): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($user['role_name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y H:i', strtotime($user['assigned_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="revoke_permission">
                                                            <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                                                            <input type="hidden" name="department_id" value="<?php echo (int)$user['department_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                                    onclick="return confirm('Revoke permission for this user?')">
                                                                Revoke
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Assign New Permission</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="assign_permission">
                                    <input type="hidden" name="department_id" value="<?php echo (int)$dept['dept_id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="user-select-<?php echo (int)$dept['dept_id']; ?>" class="form-label">Select User</label>
                                        <select name="user_id" id="user-select-<?php echo (int)$dept['dept_id']; ?>" class="form-select" required>
                                            <option value="">-- Select a user --</option>
                                            <?php foreach ($users as $user): 
                                                // Check if user already has permission for this department
                                                $hasPermission = false;
                                                foreach ($permissionedUsers as $pu) {
                                                    if ((int)$pu['user_id'] === (int)$user['user_id']) {
                                                        $hasPermission = true;
                                                        break;
                                                    }
                                                }
                                                if (!$hasPermission):
                                            ?>
                                                <option value="<?php echo (int)$user['user_id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?> 
                                                    (<?php echo htmlspecialchars($user['role_name']); ?>)
                                                </option>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Only users without existing permission are shown</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Assign Permission</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Overview</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Total Departments:</dt>
                        <dd class="col-sm-6"><strong><?php echo count($departments); ?></strong></dd>
                        
                        <dt class="col-sm-6">Total Users:</dt>
                        <dd class="col-sm-6"><strong><?php echo count($users); ?></strong></dd>
                        
                        <dt class="col-sm-6">Total Permissions:</dt>
                        <dd class="col-sm-6">
                            <strong>
                                <?php 
                                $totalPerms = 0;
                                foreach ($departments as $d) {
                                    $totalPerms += count($consumption->getPermissionedUsersForDepartment((int)$d['dept_id']));
                                }
                                echo $totalPerms;
                                ?>
                            </strong>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Tips</h5>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Assign consumption logging permissions to staff members</li>
                        <li>Users can only log consumption for departments they have permission for</li>
                        <li>Department heads can also manage permissions from their staff page</li>
                        <li>All consumption records are audit-logged</li>
                        <li>Revoke permissions when staff members change roles</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Permission Levels</h5>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <strong>Can Log Consumption:</strong>
                        <p class="text-muted mb-0">User can record when issued items are consumed</p>
                    </div>
                    <hr>
                    <div>
                        <strong>Can View Reports:</strong>
                        <p class="text-muted mb-0">User can access consumption reports and analytics</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
