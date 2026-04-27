<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];
$canIssues = can('stock-issues.view');
$canStock = can('stock.view');
$isApprover = can('stock-issues.approve');

// Check if consumption tables exist
$db = Database::getInstance()->getConnection();
$result = $db->query("SHOW TABLES LIKE 'consumption_permissions'");
$tablesExist = $result->num_rows > 0;

// Check if user has consumption logging permission
$consumption = new Consumption();
$issueController = new StockIssueController();

$hasPermission = false;
$tableError = false;
$message = '';
$messageType = '';

if ($tablesExist) {
    $hasPermission = !empty($consumption->getPermissionsForUser($userId));
}

if (!$tablesExist) {
    $tableError = true;
}

if (!$tableError && !$hasPermission && !$isApprover && !$canIssues && !$canStock) {
    http_response_code(403);
    die('You do not have permission to log consumption.');
}

$issues = [];
$pendingItems = [];

if ($tablesExist) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'log_item_consumption') {
            $issueItemId = (int)($_POST['issue_item_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');

            if ($issueItemId <= 0 || $quantity <= 0) {
                $message = 'Please provide a valid item and quantity.';
                $messageType = 'danger';
            } else {
                $currentPending = $issueController->getPendingConsumptionItemsForUser($userId);
                $itemMap = [];
                foreach ($currentPending as $item) {
                    $itemMap[(int)$item['issue_item_id']] = $item;
                }

                if (!isset($itemMap[$issueItemId])) {
                    $message = 'Item is not available for consumption in your scope or is already complete.';
                    $messageType = 'danger';
                } else {
                    $maxPending = (int)$itemMap[$issueItemId]['quantity_pending'];
                    if ($quantity > $maxPending) {
                        $message = 'Quantity exceeds pending amount for this item.';
                        $messageType = 'danger';
                    } else {
                        $result = $consumption->logConsumption($issueItemId, $quantity, $userId, $notes);
                        if (!empty($result['success'])) {
                            $message = 'Consumption recorded successfully.';
                            $messageType = 'success';
                        } else {
                            $message = $result['message'] ?? 'Unable to record consumption.';
                            $messageType = 'danger';
                        }
                    }
                }
            }
        }
    }

    $issues = $issueController->getConsumableIssuesForUser($userId, true);
    $pendingItems = $issueController->getPendingConsumptionItemsForUser($userId);
}

$pageTitle = 'Consumption Logging';
$activePage = 'consumption';
?>

<?php include __DIR__ . '/../../app/views/layout-header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="text-muted">View issued items in your scope and record consumption directly</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($tableError): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>⚠️ Setup Required</strong><br>
            The consumption tracking feature requires database tables that have not been created yet. 
            Please run the updated <code>database/schema.sql</code> file to enable this feature.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php else: ?>
        <?php if (!empty($issues)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Issue Summary</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Issue #</th>
                                <th>Date Issued</th>
                                <th>Total Items</th>
                                <th>Quantity Issued</th>
                                <th>Consumed</th>
                                <th>Returned</th>
                                <th>In stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issues as $issue): 
                                $pending = (int)$issue['total_quantity_issued'] - (int)($issue['total_quantity_consumed'] ?? 0) - (int)($issue['total_quantity_returned'] ?? 0);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($issue['issue_number']); ?></strong>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></td>
                                    <td><?php echo (int)$issue['total_items']; ?></td>
                                    <td><?php echo (int)$issue['total_quantity_issued']; ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo (int)($issue['total_quantity_consumed'] ?? 0); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark"><?php echo (int)($issue['total_quantity_returned'] ?? 0); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($pending > 0): ?>
                                            <span class="badge bg-danger"><?php echo $pending; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Done</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $issue['status'] === 'received' ? 'info' : 'primary'; ?>">
                                            <?php echo ucfirst($issue['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>pages/stock-issues/log-consumption.php?issue_id=<?php echo (int)$issue['issue_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            Log Consumption
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>pages/stock-issues/consumption-history.php?issue_id=<?php echo (int)$issue['issue_id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            History
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All In stock Items</h5>
                <span class="badge bg-danger"><?php echo count($pendingItems); ?> in stock</span>
            </div>
            <?php if (empty($pendingItems)): ?>
                <div class="card-body">
                    <div class="alert alert-info mb-0" role="alert">
                        No in stock items found. All issued items are fully accounted for.
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Issue #</th>
                                <?php if ($isApprover): ?>
                                    <th>Store</th>
                                    <th>Department</th>
                                <?php endif; ?>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Issued</th>
                                <th>Consumed</th>
                                <th>Returned</th>
                                <th>In stock</th>
                                <th>Record Consumption</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['issue_number']); ?></td>
                                    <?php if ($isApprover): ?>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['dept_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['product_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                                    <td><?php echo (int)$item['quantity_issued']; ?></td>
                                    <td><span class="badge bg-success"><?php echo (int)$item['quantity_consumed']; ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo (int)$item['quantity_returned']; ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo (int)$item['quantity_pending']; ?></span></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2 align-items-start flex-wrap">
                                            <input type="hidden" name="action" value="log_item_consumption">
                                            <input type="hidden" name="issue_item_id" value="<?php echo (int)$item['issue_item_id']; ?>">
                                            <input type="number" name="quantity" class="form-control form-control-sm" style="width: 90px;" min="1" max="<?php echo (int)$item['quantity_pending']; ?>" required>
                                            <input type="text" name="notes" class="form-control form-control-sm" style="width: 180px;" placeholder="Notes (optional)">
                                            <button type="submit" class="btn btn-sm btn-primary">Consume</button>
                                            <a href="<?php echo SITE_URL; ?>pages/stock-issues/consumption-history.php?issue_id=<?php echo (int)$item['issue_id']; ?>" class="btn btn-sm btn-outline-secondary">History</a>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
