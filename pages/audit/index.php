<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new AuditController();

$filters = [
    'q' => $_GET['q'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'entity_type' => $_GET['entity_type'] ?? '',
    'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
    'date_to' => $_GET['date_to'] ?? date('Y-m-d')
];

$limit = isset($_GET['limit']) && ctype_digit((string)$_GET['limit']) ? (int)$_GET['limit'] : 200;
$limit = max(50, min(1000, $limit));

$logs = $controller->getLogs($filters, $limit);

if (($_GET['export'] ?? '') === 'csv') {
    $controller->exportCsv($logs);
}

$users = $controller->getUsers();
$entityTypes = $controller->getEntityTypes();
$summary = $controller->getSummary($filters);

function auditFieldLabel($field) {
    $map = [
        'dept_name' => 'Department Name',
        'dept_code' => 'Department Code',
        'head_user_id' => 'Department Head',
        'store_name' => 'Store Name',
        'store_code' => 'Store Code',
        'responsible_user_id' => 'Responsible User',
        'category_name' => 'Category Name',
        'product_name' => 'Product Name',
        'product_code' => 'Product Code',
        'supplier_name' => 'Supplier Name',
        'contact_person' => 'Contact Person',
        'payment_terms' => 'Payment Terms',
        'reorder_level' => 'Reorder Level',
        'reorder_quantity' => 'Reorder Quantity',
        'unit_of_measure' => 'Unit',
        'status' => 'Status',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'city' => 'City',
        'postal_code' => 'Postal Code',
        'location' => 'Location',
        'description' => 'Description'
    ];

    if (isset($map[$field])) {
        return $map[$field];
    }

    return ucwords(str_replace('_', ' ', $field));
}

function auditActionLabel($action) {
    $key = strtolower(trim((string)$action));
    $map = [
        'create' => 'Created',
        'update' => 'Updated',
        'delete' => 'Deleted',
        'status change' => 'Status Changed',
        'approve' => 'Approved',
        'reject' => 'Rejected',
        'verify' => 'Verified',
        'issue' => 'Issued'
    ];

    return $map[$key] ?? ucwords($key);
}

function auditDecodeJsonObject($value) {
    $raw = trim((string)$value);
    if ($raw === '' || $raw === 'null') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    return null;
}

function auditToFlatString($value) {
    if ($value === null) {
        return '-';
    }

    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }

    if (is_array($value)) {
        if (empty($value)) {
            return '-';
        }
        $parts = [];
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $parts[] = auditFieldLabel((string)$k) . ': [complex]';
            } else {
                $parts[] = auditFieldLabel((string)$k) . ': ' . auditToFlatString($v);
            }
        }
        return implode(', ', $parts);
    }

    $text = trim((string)$value);
    return $text === '' ? '-' : $text;
}

function auditBuildChanges($log) {
    $oldObj = auditDecodeJsonObject($log['old_value'] ?? '');
    $newObj = auditDecodeJsonObject($log['new_value'] ?? '');

    if (!is_array($oldObj) && !is_array($newObj)) {
        return [];
    }

    $oldObj = is_array($oldObj) ? $oldObj : [];
    $newObj = is_array($newObj) ? $newObj : [];

    $keys = array_unique(array_merge(array_keys($oldObj), array_keys($newObj)));
    $changes = [];

    foreach ($keys as $key) {
        $oldVal = array_key_exists($key, $oldObj) ? $oldObj[$key] : null;
        $newVal = array_key_exists($key, $newObj) ? $newObj[$key] : null;

        if ($oldVal == $newVal) {
            continue;
        }

        $changes[] = [
            'field' => auditFieldLabel((string)$key),
            'old' => auditToFlatString($oldVal),
            'new' => auditToFlatString($newVal)
        ];
    }

    return $changes;
}

$pageTitle = 'Audit Log';
$activePage = 'audit';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Audit Log</h1>
        <p>Track who changed what and when across the system.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?php
        $query = $_GET;
        $query['export'] = 'csv';
        echo SITE_URL . 'pages/audit/index.php?' . http_build_query($query);
    ?>">
        <i class="fas fa-download"></i> Export CSV
    </a>
</div>

<div class="row">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Total Logs (Filtered)</div><div class="stat-value"><?php echo (int)$summary['total']; ?></div></div></div>
    <div class="col-md-4"><div class="stat-card success"><div class="stat-label">Today</div><div class="stat-value"><?php echo (int)$summary['today']; ?></div></div></div>
    <div class="col-md-4"><div class="stat-card warning"><div class="stat-label">Users With Activity</div><div class="stat-value"><?php echo (int)$summary['users']; ?></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-filter"></i> Search & Filters</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Action, entity, user">
            </div>
            <div class="col-md-2">
                <label class="form-label">User</label>
                <select name="user_id" class="form-control">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int)$user['user_id']; ?>" <?php echo ((string)$filters['user_id'] === (string)$user['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Entity Type</label>
                <select name="entity_type" class="form-control">
                    <option value="">All</option>
                    <?php foreach ($entityTypes as $entityType): ?>
                        <option value="<?php echo htmlspecialchars($entityType); ?>" <?php echo $filters['entity_type'] === $entityType ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($entityType)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">Limit</label>
                <input type="number" name="limit" class="form-control" min="50" max="1000" step="50" value="<?php echo (int)$limit; ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="<?php echo SITE_URL; ?>pages/audit/index.php" class="btn btn-outline-primary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-history"></i> Activity Stream</h5></div>
    <div class="card-body">
        <?php if (!empty($logs)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>ID</th>
                            <th>IP</th>
                            <th>What Changed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php $changes = auditBuildChanges($log); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['action_date']); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br><small><?php echo htmlspecialchars($log['username']); ?></small></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars(auditActionLabel($log['action'])); ?></span></td>
                                <td><?php echo htmlspecialchars($log['entity_type'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['entity_id'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($changes)): ?>
                                        <div style="display:flex;flex-direction:column;gap:6px;">
                                            <?php foreach (array_slice($changes, 0, 3) as $change): ?>
                                                <div style="font-size:12px;line-height:1.35;">
                                                    <strong><?php echo htmlspecialchars($change['field']); ?>:</strong>
                                                    <span style="color:#c0392b;"><?php echo htmlspecialchars($change['old']); ?></span>
                                                    <span style="padding:0 6px;color:#7f8c8d;">-></span>
                                                    <span style="color:#1e8449;"><?php echo htmlspecialchars($change['new']); ?></span>
                                                </div>
                                            <?php endforeach; ?>

                                            <?php if (count($changes) > 3): ?>
                                                <details>
                                                    <summary style="cursor:pointer;font-size:12px;color:#8B0000;">Show <?php echo count($changes) - 3; ?> more changes</summary>
                                                    <div style="margin-top:6px;display:flex;flex-direction:column;gap:6px;">
                                                        <?php foreach (array_slice($changes, 3) as $change): ?>
                                                            <div style="font-size:12px;line-height:1.35;">
                                                                <strong><?php echo htmlspecialchars($change['field']); ?>:</strong>
                                                                <span style="color:#8B0000;"><?php echo htmlspecialchars($change['old']); ?></span>
                                                                <span style="padding:0 6px;color:#7f8c8d;">-></span>
                                                                <span style="color:#DAA520;"><?php echo htmlspecialchars($change['new']); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </details>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <details>
                                            <summary style="cursor:pointer;font-size:12px;color:#8B0000;">View entry details</summary>
                                            <div style="margin-top:6px;font-size:12px;line-height:1.4;">
                                                <div><strong>Old:</strong> <?php echo htmlspecialchars(auditToFlatString($log['old_value'] ?? '')); ?></div>
                                                <div><strong>New:</strong> <?php echo htmlspecialchars(auditToFlatString($log['new_value'] ?? '')); ?></div>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No audit entries found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
