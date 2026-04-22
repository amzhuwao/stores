<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requirePermission('settings.view');

$currentUser = getCurrentUser();
$currentRole = $currentUser['role_name'] ?? '';
$canManagePermissions = in_array($currentRole, ['Admin', 'Manager'], true);

if (!$canManagePermissions) {
    http_response_code(403);
    die('Access denied: high-level users only.');
}

$db = Database::getInstance()->getConnection();
$defaultPermissions = getDefaultRolePermissions();
$effectivePermissions = getRolePermissions();
$overrides = getRolePermissionOverrides();

function permissionInList(array $permissions, string $permission): bool
{
    if (in_array('*', $permissions, true)) {
        return true;
    }

    if (in_array($permission, $permissions, true)) {
        return true;
    }

    $parts = explode('.', $permission, 2);
    if (count($parts) === 2) {
        $moduleWildcard = $parts[0] . '.*';
        if (in_array($moduleWildcard, $permissions, true)) {
            return true;
        }
    }

    return false;
}

function isRoleEditable(string $actorRole, string $targetRole): bool
{
    if ($actorRole === 'Admin') {
        return true;
    }

    if ($actorRole === 'Manager') {
        return !in_array($targetRole, ['Admin', 'Manager'], true);
    }

    return false;
}

function isPermissionEditable(string $actorRole, string $permission): bool
{
    if ($actorRole === 'Admin') {
        return true;
    }

    if ($actorRole === 'Manager') {
        $protectedPrefixes = ['users.', 'settings.', 'audit.'];
        foreach ($protectedPrefixes as $prefix) {
            if (strpos($permission, $prefix) === 0) {
                return false;
            }
        }
        return true;
    }

    return false;
}

function getPermissionGroup(string $permission): string
{
    $module = explode('.', $permission, 2)[0] ?? $permission;

    $groups = [
        'dashboard' => 'Main',
        'products' => 'Inventory',
        'stock' => 'Inventory',
        'grn' => 'Transactions',
        'requisition' => 'Transactions',
        'stock-issues' => 'Transactions',
        'adjustments' => 'Transactions',
        'stores' => 'Configuration',
        'suppliers' => 'Configuration',
        'departments' => 'Configuration',
        'categories' => 'Configuration',
        'reports' => 'Reporting',
        'audit' => 'Reporting',
        'users' => 'Administration',
        'settings' => 'Administration'
    ];

    return $groups[$module] ?? 'Other';
}

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

$roles = [];
$rolesResult = $db->query("SELECT role_name FROM roles ORDER BY role_name ASC");
if ($rolesResult) {
    while ($row = $rolesResult->fetch_assoc()) {
        $roles[] = (string)$row['role_name'];
    }
}

foreach (array_keys($defaultPermissions) as $roleName) {
    if (!in_array($roleName, $roles, true)) {
        $roles[] = $roleName;
    }
}
sort($roles);

$routePermissions = array_values(array_unique(array_values(getRoutePermissionMap())));
$additionalPermissions = [];
foreach ($defaultPermissions as $perms) {
    foreach ($perms as $perm) {
        if ($perm !== '*') {
            $additionalPermissions[] = $perm;
        }
    }
}

$allPermissions = array_values(array_unique(array_merge($routePermissions, $additionalPermissions)));
$allPermissions = array_values(array_unique(array_merge($allPermissions, getSupplementalPermissionCatalog())));
sort($allPermissions);

$groupOrder = ['Main', 'Inventory', 'Transactions', 'Configuration', 'Reporting', 'Administration', 'Other'];
$groupedPermissions = [];
foreach ($allPermissions as $permission) {
    $group = getPermissionGroup($permission);
    if (!isset($groupedPermissions[$group])) {
        $groupedPermissions[$group] = [];
    }
    $groupedPermissions[$group][] = $permission;
}

uksort($groupedPermissions, function ($a, $b) use ($groupOrder) {
    $aIndex = array_search($a, $groupOrder, true);
    $bIndex = array_search($b, $groupOrder, true);
    $aIndex = ($aIndex === false) ? 999 : $aIndex;
    $bIndex = ($bIndex === false) ? 999 : $bIndex;
    return $aIndex <=> $bIndex;
});

$editableRoles = array_values(array_filter($roles, function ($roleName) use ($currentRole) {
    return isRoleEditable($currentRole, $roleName);
}));

$selectedRole = $_GET['role'] ?? ($editableRoles[0] ?? ($roles[0] ?? ''));
if (!in_array($selectedRole, $roles, true)) {
    $selectedRole = $editableRoles[0] ?? ($roles[0] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $targetRole = trim($_POST['target_role'] ?? '');
        if (!in_array($targetRole, $roles, true) || !isRoleEditable($currentRole, $targetRole)) {
            $_SESSION['flash_message'] = 'You are not allowed to modify that role.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $selectedRole = $targetRole;
            $postedPermissions = $_POST['permissions'] ?? [];
            $postedPermissions = is_array($postedPermissions) ? $postedPermissions : [];
            $postedPermissions = array_values(array_intersect($allPermissions, $postedPermissions));

            $defaultsForRole = $defaultPermissions[$targetRole] ?? [];

            $db->begin_transaction();
            try {
                $deleteStmt = $db->prepare("DELETE FROM role_permission_overrides WHERE role_name = ? AND permission = ?");
                $upsertStmt = $db->prepare(
                    "INSERT INTO role_permission_overrides (role_name, permission, is_allowed)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE is_allowed = VALUES(is_allowed)"
                );

                foreach ($allPermissions as $permission) {
                    if (!isPermissionEditable($currentRole, $permission)) {
                        continue;
                    }

                    $desired = in_array($permission, $postedPermissions, true);
                    $defaultAllowed = permissionInList($defaultsForRole, $permission);

                    if ($desired === $defaultAllowed) {
                        $deleteStmt->bind_param('ss', $targetRole, $permission);
                        $deleteStmt->execute();
                        continue;
                    }

                    $allowedValue = $desired ? 1 : 0;
                    $upsertStmt->bind_param('ssi', $targetRole, $permission, $allowedValue);
                    $upsertStmt->execute();
                }

                $db->commit();
                $_SESSION['flash_message'] = 'Role page access updated successfully for ' . $targetRole . '.';
                $_SESSION['flash_type'] = 'success';
            } catch (Throwable $e) {
                $db->rollback();
                $_SESSION['flash_message'] = 'Unable to update permissions right now.';
                $_SESSION['flash_type'] = 'danger';
                if (APP_DEBUG) {
                    error_log('Role permission update error: ' . $e->getMessage());
                }
            }

            header('Location: ' . SITE_URL . 'pages/settings/index.php?role=' . urlencode($selectedRole));
            exit;
        }
    }
}

$effectivePermissions = getRolePermissions();
$overrides = getRolePermissionOverrides();
$selectedRoleEffective = $effectivePermissions[$selectedRole] ?? [];
$selectedRoleDefaults = $defaultPermissions[$selectedRole] ?? [];
$selectedRoleOverrides = $overrides[$selectedRole] ?? ['allow' => [], 'deny' => []];

$systemInfo = [
    ['label' => 'Application', 'value' => APP_NAME],
    ['label' => 'Version', 'value' => APP_VERSION],
    ['label' => 'Environment', 'value' => APP_ENV],
    ['label' => 'Debug Mode', 'value' => APP_DEBUG ? 'Enabled' : 'Disabled'],
    ['label' => 'Site URL', 'value' => SITE_URL],
    ['label' => 'Database', 'value' => DB_NAME],
    ['label' => 'Current User', 'value' => $currentUser['full_name'] ?? 'Unknown'],
    ['label' => 'Current Role', 'value' => $currentRole],
    ['label' => 'Editable Roles', 'value' => implode(', ', $editableRoles ?: ['None'])]
];

$pageTitle = 'System Settings';
$activePage = 'settings';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1>System Settings</h1>
        <p>Manage role access to specialized pages and operational modules.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo SITE_URL; ?>pages/users/index.php" class="btn btn-primary">Manage Users</a>
        <?php if (can('settings.backup')): ?>
            <a href="<?php echo SITE_URL; ?>pages/settings/backup.php" class="btn btn-outline-danger">Backups</a>
        <?php endif; ?>
        <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
        <?php if (can('audit.view')): ?>
            <a href="<?php echo SITE_URL; ?>pages/audit/index.php" class="btn btn-outline-secondary">View Audit Log</a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-2"><div class="stat-card"><div class="stat-label">Users</div><div class="stat-value"><?php echo (int)$summary['users']; ?></div></div></div>
    <div class="col-md-6 col-lg-2"><div class="stat-card success"><div class="stat-label">Roles</div><div class="stat-value"><?php echo (int)$summary['roles']; ?></div></div></div>
    <div class="col-md-6 col-lg-2"><div class="stat-card warning"><div class="stat-label">Stores</div><div class="stat-value"><?php echo (int)$summary['stores']; ?></div></div></div>
    <div class="col-md-6 col-lg-2"><div class="stat-card danger"><div class="stat-label">Departments</div><div class="stat-value"><?php echo (int)$summary['departments']; ?></div></div></div>
    <div class="col-md-6 col-lg-2"><div class="stat-card"><div class="stat-label">Products</div><div class="stat-value"><?php echo (int)$summary['products']; ?></div></div></div>
    <div class="col-md-6 col-lg-2"><div class="stat-card success"><div class="stat-label">Suppliers</div><div class="stat-value"><?php echo (int)$summary['suppliers']; ?></div></div></div>
</div>

<div class="row mt-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h5><i class="fas fa-shield-alt"></i> Environment & Security</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($systemInfo as $item): ?>
                                <tr>
                                    <th style="width:45%;"><?php echo htmlspecialchars($item['label']); ?></th>
                                    <td><?php echo htmlspecialchars((string)$item['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($currentRole === 'Manager'): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        Manager safety guard: you can edit operational roles but cannot change <strong>Admin/Manager</strong> roles or permissions for <strong>users/settings/audit</strong> modules.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h5><i class="fas fa-key"></i> Role Page Access Editor</h5></div>
            <div class="card-body">
                <?php if (empty($roles)): ?>
                    <div class="alert alert-info mb-0">No roles found.</div>
                <?php else: ?>
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Select Role</label>
                            <select name="role" class="form-control" onchange="this.form.submit()">
                                <?php foreach ($roles as $roleName): ?>
                                    <option value="<?php echo htmlspecialchars($roleName); ?>" <?php echo $selectedRole === $roleName ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($roleName); ?>
                                        <?php echo isRoleEditable($currentRole, $roleName) ? '' : ' (Read Only)'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>

                    <div class="mb-3">
                        <strong>Current Role:</strong> <?php echo htmlspecialchars($selectedRole); ?><br>
                        <small class="text-muted">
                            Effective grants: <?php echo count($selectedRoleEffective); ?>
                            | Override allow: <?php echo count($selectedRoleOverrides['allow']); ?>
                            | Override deny: <?php echo count($selectedRoleOverrides['deny']); ?>
                        </small>
                    </div>

                    <form method="POST" class="border rounded p-3">
                        <?php echo getCSRFTokenField(); ?>
                        <input type="hidden" name="save_permissions" value="1">
                        <input type="hidden" name="target_role" value="<?php echo htmlspecialchars($selectedRole); ?>">

                        <div class="row g-3">
                            <?php foreach ($groupedPermissions as $groupName => $permissions): ?>
                                <div class="col-md-6">
                                    <div class="border rounded p-2 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong><?php echo htmlspecialchars($groupName); ?></strong>
                                            <span class="badge bg-light text-dark"><?php echo count($permissions); ?></span>
                                        </div>

                                        <?php foreach ($permissions as $permission): ?>
                                            <?php
                                            $checked = permissionInList($selectedRoleEffective, $permission);
                                            $editableRole = isRoleEditable($currentRole, $selectedRole);
                                            $editablePermission = isPermissionEditable($currentRole, $permission);
                                            $disabled = (!$editableRole || !$editablePermission) ? 'disabled' : '';
                                            $defaultAllowed = permissionInList($selectedRoleDefaults, $permission);
                                            $isAllowOverride = in_array($permission, $selectedRoleOverrides['allow'], true);
                                            $isDenyOverride = in_array($permission, $selectedRoleOverrides['deny'], true);
                                            ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" id="perm_<?php echo md5($permission); ?>" name="permissions[]" value="<?php echo htmlspecialchars($permission); ?>" <?php echo $checked ? 'checked' : ''; ?> <?php echo $disabled; ?>>
                                                <label class="form-check-label" for="perm_<?php echo md5($permission); ?>">
                                                    <?php echo htmlspecialchars($permission); ?>
                                                    <?php if ($isAllowOverride): ?>
                                                        <span class="badge bg-success ms-1">Override Allow</span>
                                                    <?php elseif ($isDenyOverride): ?>
                                                        <span class="badge bg-danger ms-1">Override Deny</span>
                                                    <?php elseif ($defaultAllowed): ?>
                                                        <span class="badge bg-secondary ms-1">Default</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" <?php echo isRoleEditable($currentRole, $selectedRole) ? '' : 'disabled'; ?>>Save Access Rules</button>
                            <a href="<?php echo SITE_URL; ?>pages/settings/index.php?role=<?php echo urlencode($selectedRole); ?>" class="btn btn-outline-primary">Reload</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
