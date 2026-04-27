<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requirePermission('stock-issues.view');

$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

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

// Get consumption records
$consumptionRecords = $consumption->getConsumptionRecordsForIssue($issueId);

// Get items summary
$items = $issueController->getIssueItemsForConsumption($issueId);

$pageTitle = 'Consumption History - Issue ' . htmlspecialchars($issue['issue_number']);
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
        <div class="col-auto">
            <a href="<?php echo SITE_URL; ?>pages/stock-issues/consumption.php" class="btn btn-outline-secondary">
                Back to Consumption List
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Consumption Records</h5>
                </div>
                <?php if (empty($consumptionRecords)): ?>
                    <div class="card-body text-center text-muted">
                        <p>No consumption records found for this issue.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Code</th>
                                    <th>Quantity</th>
                                    <th>Logged By</th>
                                    <th>Date & Time</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consumptionRecords as $record): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['product_name']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['product_code']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo (int)$record['quantity_consumed']; ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($record['logged_by_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y H:i', strtotime($record['log_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($record['notes']): ?>
                                                <small><?php echo htmlspecialchars(substr($record['notes'], 0, 50)); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">—</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Item Summary</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Issued</th>
                                <th>Consumed</th>
                                <th>Returned</th>
                                <th>Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): 
                                $pending = (int)$item['quantity_issued'] - (int)$item['quantity_consumed'] - (int)$item['quantity_returned'];
                            ?>
                                <tr>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($item['product_name'], 0, 15)); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo (int)$item['quantity_issued']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo (int)$item['quantity_consumed']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark"><?php echo (int)$item['quantity_returned']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($pending > 0): ?>
                                            <span class="badge bg-danger"><?php echo $pending; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Overall Status</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $totalIssued = array_sum(array_column($items, 'quantity_issued'));
                    $totalConsumed = array_sum(array_column($items, 'quantity_consumed'));
                    $totalReturned = array_sum(array_column($items, 'quantity_returned'));
                    $totalPending = $totalIssued - $totalConsumed - $totalReturned;
                    $completionPercent = $totalIssued > 0 ? round((($totalConsumed + $totalReturned) / $totalIssued) * 100) : 0;
                    ?>
                    <div class="mb-3">
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $completionPercent; ?>%">
                                <?php echo $completionPercent; ?>%
                            </div>
                        </div>
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Total Issued:</dt>
                        <dd class="col-sm-6"><strong><?php echo $totalIssued; ?></strong></dd>
                        
                        <dt class="col-sm-6">Consumed:</dt>
                        <dd class="col-sm-6"><span class="badge bg-success"><?php echo $totalConsumed; ?></span></dd>
                        
                        <dt class="col-sm-6">Returned:</dt>
                        <dd class="col-sm-6"><span class="badge bg-warning text-dark"><?php echo $totalReturned; ?></span></dd>
                        
                        <dt class="col-sm-6">Pending:</dt>
                        <dd class="col-sm-6">
                            <?php if ($totalPending > 0): ?>
                                <span class="badge bg-danger"><?php echo $totalPending; ?></span>
                            <?php else: ?>
                                <span class="badge bg-success">Complete</span>
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
                    <dl class="row mb-0">
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
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
