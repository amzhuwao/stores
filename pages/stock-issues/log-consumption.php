<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requirePermission('stock-issues.view');

$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];
$departmentId = $currentUser['dept_id'] ?? null;

$issueId = (int)($_GET['issue_id'] ?? 0);

if (!$issueId) {
    header('Location: ' . SITE_URL . 'pages/stock-issues/consumption.php');
    exit;
}

$issueController = new StockIssueController();
$consumption = new Consumption();

// Get issue details
$issue = $issueController->getIssueById($issueId, $userId);

if (!$issue) {
    http_response_code(404);
    die('Issue not found');
}

// Check permission for this department
$hasPermission = $consumption->hasPermission($userId, (int)$issue['department_id']);

if (!$hasPermission && !can('stock-issues.approve')) {
    http_response_code(403);
    die('You do not have permission to log consumption for this department.');
}

// Get issue items
$items = $issueController->getIssueItemsForConsumption($issueId);

// Handle consumption logging
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);
    
    if ($action === 'log_consumption') {
        $quantity = (int)($_POST['quantity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($quantity <= 0) {
            $message = 'Quantity must be greater than 0';
            $messageType = 'danger';
        } else {
            // Find the item to check remaining quantity
            $item = null;
            foreach ($items as $i) {
                if ((int)$i['issue_item_id'] === $itemId) {
                    $item = $i;
                    break;
                }
            }
            
            if (!$item) {
                $message = 'Item not found';
                $messageType = 'danger';
            } else {
                $remaining = (int)$item['quantity_issued'] - (int)$item['quantity_consumed'] - (int)$item['quantity_returned'];
                
                if ($quantity > $remaining) {
                    $message = "Cannot consume more than $remaining units. Already consumed: " . (int)$item['quantity_consumed'] . ", Returned: " . (int)$item['quantity_returned'];
                    $messageType = 'danger';
                } else {
                    $result = $consumption->logConsumption($itemId, $quantity, $userId, $notes);
                    
                    if ($result['success']) {
                        $message = 'Consumption logged successfully!';
                        $messageType = 'success';
                        
                        // Refresh items list
                        $items = $issueController->getIssueItemsForConsumption($issueId);
                    } else {
                        $message = $result['message'] ?? 'Error logging consumption';
                        $messageType = 'danger';
                    }
                }
            }
        }
    } elseif ($action === 'log_return') {
        $quantity = (int)($_POST['return_quantity'] ?? 0);
        $reason = trim($_POST['return_reason'] ?? '');
        $notes = trim($_POST['return_notes'] ?? '');
        
        if ($quantity <= 0) {
            $message = 'Quantity must be greater than 0';
            $messageType = 'danger';
        } else {
            // Find the item to check remaining quantity
            $item = null;
            foreach ($items as $i) {
                if ((int)$i['issue_item_id'] === $itemId) {
                    $item = $i;
                    break;
                }
            }
            
            if (!$item) {
                $message = 'Item not found';
                $messageType = 'danger';
            } else {
                $remaining = (int)$item['quantity_issued'] - (int)$item['quantity_consumed'] - (int)$item['quantity_returned'];
                
                if ($quantity > $remaining) {
                    $message = "Cannot return more than $remaining units";
                    $messageType = 'danger';
                } else {
                    $result = $consumption->logReturn($itemId, $quantity, $userId, $reason, $notes);
                    
                    if ($result['success']) {
                        $message = 'Item return logged successfully!';
                        $messageType = 'success';
                        
                        // Refresh items list
                        $items = $issueController->getIssueItemsForConsumption($issueId);
                    } else {
                        $message = $result['message'] ?? 'Error logging return';
                        $messageType = 'danger';
                    }
                }
            }
        }
    }
}

// Get consumption records
$consumptionRecords = $consumption->getConsumptionRecordsForIssue($issueId);

$pageTitle = 'Log Consumption - Issue ' . htmlspecialchars($issue['issue_number']);
?>

<?php include __DIR__ . '/../../app/views/layout-header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Issue: <?php echo htmlspecialchars($issue['issue_number']); ?> | 
               Department: <?php echo htmlspecialchars($issue['dept_name']); ?> | 
               Date: <?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
    $totalIssued = array_sum(array_column($items, 'quantity_issued'));
    $totalConsumed = array_sum(array_column($items, 'quantity_consumed'));
    $totalReturned = array_sum(array_column($items, 'quantity_returned'));
    $totalPending = $totalIssued - $totalConsumed - $totalReturned;
    ?>

    <?php if ($totalPending <= 0): ?>
        <div class="alert alert-secondary" role="alert">
            All items in this issue are fully accounted for (consumed or returned). No new consumption entries can be recorded.
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Issue Items</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Issued</th>
                                <th>Consumed</th>
                                <th>Returned</th>
                                <th>Pending</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): 
                                $pending = (int)$item['quantity_issued'] - (int)$item['quantity_consumed'] - (int)$item['quantity_returned'];
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['product_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                                    <td><?php echo (int)$item['quantity_issued']; ?></td>
                                    <td><span class="badge bg-success"><?php echo (int)$item['quantity_consumed']; ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo (int)$item['quantity_returned']; ?></span></td>
                                    <td>
                                        <?php if ($pending > 0): ?>
                                            <span class="badge bg-danger"><?php echo $pending; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Done</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pending > 0): ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#consumptionModal"
                                                    data-item-id="<?php echo (int)$item['issue_item_id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                    data-pending="<?php echo $pending; ?>">
                                                Log
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                Complete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Total Issued:</dt>
                        <dd class="col-sm-6"><strong><?php echo $totalIssued; ?></strong></dd>
                        
                        <dt class="col-sm-6">Total Consumed:</dt>
                        <dd class="col-sm-6"><span class="badge bg-success"><?php echo $totalConsumed; ?></span></dd>
                        
                        <dt class="col-sm-6">Total Returned:</dt>
                        <dd class="col-sm-6"><span class="badge bg-warning text-dark"><?php echo $totalReturned; ?></span></dd>
                        
                        <dt class="col-sm-6">Still Pending:</dt>
                        <dd class="col-sm-6">
                            <?php if ($totalPending > 0): ?>
                                <span class="badge bg-danger fs-6"><?php echo $totalPending; ?></span>
                            <?php else: ?>
                                <span class="badge bg-success fs-6">Complete</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Issue Details</h5>
                </div>
                <div class="card-body small">
                    <dl class="row">
                        <dt class="col-sm-6">Issued By:</dt>
                        <dd class="col-sm-6"><?php echo htmlspecialchars($issue['issued_by_name']); ?></dd>
                        
                        <dt class="col-sm-6">Status:</dt>
                        <dd class="col-sm-6"><span class="badge bg-info"><?php echo ucfirst($issue['status']); ?></span></dd>
                        
                        <?php if ($issue['received_date']): ?>
                            <dt class="col-sm-6">Received:</dt>
                            <dd class="col-sm-6"><?php echo date('M d, Y', strtotime($issue['received_date'])); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($consumptionRecords)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Consumption Records</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Logged By</th>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($consumptionRecords, 0, 10) as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['product_name']); ?></td>
                                <td><span class="badge bg-success"><?php echo (int)$record['quantity_consumed']; ?></span></td>
                                <td><?php echo htmlspecialchars($record['logged_by_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($record['log_date'])); ?></td>
                                <td><small><?php echo htmlspecialchars(substr($record['notes'], 0, 50)); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Consumption Modal -->
<div class="modal fade" id="consumptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Consumption</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="log_consumption">
                    <input type="hidden" name="item_id" id="modal_item_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" class="form-control" id="modal_product_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity to Consume</label>
                        <input type="number" name="quantity" class="form-control" min="1" required 
                               id="modal_quantity" placeholder="Enter quantity">
                        <small class="text-muted">Max: <span id="modal_pending">0</span> units</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Any notes about the consumption..."></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="show_return_form" data-bs-toggle="collapse" data-bs-target="#returnForm">
                        <label class="form-check-label" for="show_return_form">
                            Also log a return/damage for this item?
                        </label>
                    </div>

                    <div class="collapse mt-3" id="returnForm">
                        <div class="card card-body">
                            <div class="mb-3">
                                <label class="form-label">Return Quantity</label>
                                <input type="number" class="form-control" id="return_quantity" min="0" placeholder="Leave blank if only logging consumption">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Return Reason</label>
                                <select class="form-select" id="return_reason">
                                    <option value="">Select a reason</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Lost">Lost</option>
                                    <option value="Defective">Defective</option>
                                    <option value="Wrong Item">Wrong Item</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Return Notes</label>
                                <textarea class="form-control" id="return_notes" rows="2" placeholder="Details about the return..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Consumption</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('consumptionModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('modal_item_id').value = btn.dataset.itemId;
    document.getElementById('modal_product_name').value = btn.dataset.productName;
    document.getElementById('modal_pending').textContent = btn.dataset.pending;
    document.getElementById('modal_quantity').max = btn.dataset.pending;
    document.getElementById('modal_quantity').value = '';
});
</script>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
