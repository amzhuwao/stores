<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// Check permissions
if (!can('reports.view') && !can('stock-issues.view') && !can('stock.view')) {
    http_response_code(403);
    die('You do not have permission to view reports.');
}

$consumption = new Consumption();
$productModel = new Product();
$deptModel = new Department();
$storeModel = new Store();

$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];
$isApprover = can('stock-issues.approve');

// Get filter values
$department_id = $_GET['department_id'] ?? '';
$product_id = $_GET['product_id'] ?? '';
$store_id = $_GET['store_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'detailed';

// Build filters array
$filters = [
    'start_date' => $start_date,
    'end_date' => $end_date
];

if (!empty($department_id)) {
    $filters['department_id'] = (int)$department_id;
}
if (!empty($product_id)) {
    $filters['product_id'] = (int)$product_id;
}
if (!empty($store_id)) {
    $filters['store_id'] = (int)$store_id;
}

// Get data based on report type
$reportData = [];
$stats = null;
$byProduct = [];
$byDepartment = [];

if ($report_type === 'detailed') {
    $reportData = $consumption->getConsumptionReport($filters);
} elseif ($report_type === 'by-product') {
    $byProduct = $consumption->getConsumptionByProduct($filters);
} elseif ($report_type === 'by-department') {
    $byDepartment = $consumption->getConsumptionByDepartment($filters);
}

$stats = $consumption->getConsumptionStats($filters);

// Get dropdown options
$products = $productModel->getAll('products', 'status = "active"');
$departments = $deptModel->getAll('departments', 'status = "active"');
$stores = $storeModel->getAll('stores', 'status = "active"');

$pageTitle = 'Consumption Reports';
$activePage = 'reports';
?>

<?php include __DIR__ . '/../../app/views/layout-header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="text-muted">Analyze consumption patterns and trends across your store</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo (int)$dept['dept_id']; ?>" <?php echo $department_id == $dept['dept_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="product_id" class="form-label">Product</label>
                    <select class="form-select" id="product_id" name="product_id">
                        <option value="">All Products</option>
                        <?php foreach ($products as $prod): ?>
                            <option value="<?php echo (int)$prod['product_id']; ?>" <?php echo $product_id == $prod['product_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prod['product_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="store_id" class="form-label">Store</label>
                    <select class="form-select" id="store_id" name="store_id">
                        <option value="">All Stores</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo (int)$store['store_id']; ?>" <?php echo $store_id == $store['store_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select class="form-select" id="report_type" name="report_type">
                        <option value="detailed" <?php echo $report_type === 'detailed' ? 'selected' : ''; ?>>Detailed</option>
                        <option value="by-product" <?php echo $report_type === 'by-product' ? 'selected' : ''; ?>>By Product</option>
                        <option value="by-department" <?php echo $report_type === 'by-department' ? 'selected' : ''; ?>>By Department</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="?report_type=detailed" class="btn btn-outline-secondary">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Summary -->
    <?php if ($stats): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Consumption Logs</h6>
                        <h3><?php echo (int)($stats['total_logs'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Quantity Consumed</h6>
                        <h3><?php echo (int)($stats['total_quantity'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Average per Log</h6>
                        <h3><?php echo round((float)($stats['avg_quantity'] ?? 0), 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Departments Involved</h6>
                        <h3><?php echo (int)($stats['num_departments'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Report Content -->
    <?php if ($report_type === 'detailed'): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detailed Consumption Log</h5>
                <div>
                    <a href="#" onclick="exportToCSV()" class="btn btn-sm btn-outline-success">Export CSV</a>
                </div>
            </div>
            <?php if (empty($reportData)): ?>
                <div class="card-body">
                    <div class="alert alert-info mb-0">No consumption records found for the selected filters.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="reportTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Issue #</th>
                                <th>Department</th>
                                <th>Store</th>
                                <th>Product</th>
                                <th>Code</th>
                                <th>Unit</th>
                                <th>Quantity Consumed</th>
                                <th>Logged By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($row['log_date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['issue_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['store_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($row['product_code']); ?></small></td>
                                    <td><?php echo htmlspecialchars($row['unit_of_measure']); ?></td>
                                    <td><span class="badge bg-success"><?php echo (int)$row['quantity_consumed']; ?></span></td>
                                    <td><?php echo htmlspecialchars($row['logged_by_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($row['notes']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($report_type === 'by-product'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Consumption by Product</h5>
            </div>
            <?php if (empty($byProduct)): ?>
                <div class="card-body">
                    <div class="alert alert-info mb-0">No consumption records found for the selected filters.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Code</th>
                                <th>Unit</th>
                                <th>Total Consumed</th>
                                <th>Consumption Logs</th>
                                <th>Issue Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byProduct as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['unit_of_measure']); ?></td>
                                    <td><span class="badge bg-success"><?php echo (int)$row['total_quantity_consumed']; ?></span></td>
                                    <td><?php echo (int)$row['num_consumption_logs']; ?></td>
                                    <td><?php echo (int)$row['num_issue_items']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($report_type === 'by-department'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Consumption by Department</h5>
            </div>
            <?php if (empty($byDepartment)): ?>
                <div class="card-body">
                    <div class="alert alert-info mb-0">No consumption records found for the selected filters.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Total Consumed</th>
                                <th>Consumption Logs</th>
                                <th>Active Issues</th>
                                <th>Products</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byDepartment as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['dept_name']); ?></strong></td>
                                    <td><span class="badge bg-success"><?php echo (int)$row['total_quantity_consumed']; ?></span></td>
                                    <td><?php echo (int)$row['num_consumption_logs']; ?></td>
                                    <td><?php echo (int)$row['num_issues']; ?></td>
                                    <td><?php echo (int)$row['num_products']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
function exportToCSV() {
    const table = document.getElementById('reportTable');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    const link = document.createElement('a');
    link.setAttribute('href', csvContent);
    link.setAttribute('download', 'consumption-report-' + new Date().getTime() + '.csv');
    link.click();
}
</script>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
