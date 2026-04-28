<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// Check permissions
if (!can('reports.view') && !can('stock-issues.view') && !can('stock.view')) {
    http_response_code(403);
    die('You do not have permission to view analytics.');
}

$consumption = new Consumption();
$deptModel = new Department();

$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];
$isApprover = can('stock-issues.approve');

// Get filter values - default to last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$department_id = $_GET['department_id'] ?? '';

// Build filters
$filters = [
    'start_date' => $start_date,
    'end_date' => $end_date
];

if (!empty($department_id)) {
    $filters['department_id'] = (int)$department_id;
}

// Get analytics data
$stats = $consumption->getConsumptionStats($filters);
$trends = $consumption->getConsumptionTrends($filters);
$topProducts = $consumption->getTopConsumedProducts(10, $filters);
$byDepartment = $consumption->getConsumptionByDepartment($filters);
$byProduct = $consumption->getConsumptionByProduct($filters);

// Get departments for filter
$departments = $deptModel->getAll('departments', 'status = "active"');

// Format trend data for chart
$trendDates = array_map(function($t) { return $t['consumption_date']; }, $trends);
$trendValues = array_map(function($t) { return (int)$t['daily_total']; }, $trends);

// Format top products for chart
$productNames = array_map(function($p) { return htmlspecialchars($p['product_code']); }, $topProducts);
$productValues = array_map(function($p) { return (int)$p['total_consumed']; }, $topProducts);

// Format department data for chart
$deptNames = array_map(function($d) { return htmlspecialchars($d['dept_name']); }, $byDepartment);
$deptValues = array_map(function($d) { return (int)$d['total_quantity_consumed']; }, $byDepartment);

$pageTitle = 'Consumption Analytics';
$activePage = 'analytics';
?>

<?php include __DIR__ . '/../../app/views/layout-header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="text-muted">Visual insights into consumption patterns and trends</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Date Range & Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-12 col-md-3">
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
                <div class="col-12 col-md-5">
                    <button type="submit" class="btn btn-primary w-100">Update Analytics</button>
                </div>
                <div class="col-12 col-md-12">
                    <a href="?" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Logs</h6>
                    <h3 class="text-primary"><?php echo (int)($stats['total_logs'] ?? 0); ?></h3>
                    <small class="text-muted">consumption entries</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Consumed</h6>
                    <h3 class="text-success"><?php echo (int)($stats['total_quantity'] ?? 0); ?></h3>
                    <small class="text-muted">units</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Avg per Log</h6>
                    <h3 class="text-info"><?php echo round((float)($stats['avg_quantity'] ?? 0), 2); ?></h3>
                    <small class="text-muted">units</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Products</h6>
                    <h3 class="text-warning"><?php echo (int)($stats['num_products'] ?? 0); ?></h3>
                    <small class="text-muted">tracked</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Consumption Trend (Daily)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="80"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Consumption by Department</h5>
                </div>
                <div class="card-body">
                    <canvas id="deptChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Consumed Products</h5>
                </div>
                <div class="card-body">
                    <canvas id="productsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Products (Details)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Qty Consumed</th>
                                <th class="text-end">Logs</th>
                                <th class="text-end">Avg</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $prod): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($prod['product_code']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($prod['product_name']); ?></small>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success"><?php echo (int)$prod['total_consumed']; ?></span>
                                    </td>
                                    <td class="text-end"><?php echo (int)$prod['consumption_count']; ?></td>
                                    <td class="text-end text-muted"><?php echo round((float)$prod['avg_per_log'], 1); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Breakdown -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Department Consumption Breakdown</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Department</th>
                        <th class="text-end">Total Consumed</th>
                        <th class="text-end">Logs</th>
                        <th class="text-end">Issues</th>
                        <th class="text-end">Products</th>
                        <th class="text-end">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = (int)($stats['total_quantity'] ?? 0);
                    foreach ($byDepartment as $dept): 
                        $deptTotal = (int)$dept['total_quantity_consumed'];
                        $percentage = $grandTotal > 0 ? round(($deptTotal / $grandTotal) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['dept_name']); ?></strong></td>
                            <td class="text-end">
                                <span class="badge bg-success"><?php echo $deptTotal; ?></span>
                            </td>
                            <td class="text-end"><?php echo (int)$dept['num_consumption_logs']; ?></td>
                            <td class="text-end"><?php echo (int)$dept['num_issues']; ?></td>
                            <td class="text-end"><?php echo (int)$dept['num_products']; ?></td>
                            <td class="text-end">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                         aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($trendDates); ?>,
        datasets: [{
            label: 'Daily Consumption',
            data: <?php echo json_encode($trendValues); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Department Chart
const deptCtx = document.getElementById('deptChart').getContext('2d');
const deptChart = new Chart(deptCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($deptNames); ?>,
        datasets: [{
            data: <?php echo json_encode($deptValues); ?>,
            backgroundColor: [
                '#0d6efd',
                '#6c757d',
                '#198754',
                '#dc3545',
                '#ffc107',
                '#0dcaf0',
                '#d63384',
                '#fd7e14',
                '#20c997',
                '#6f42c1'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Top Products Chart
const productsCtx = document.getElementById('productsChart').getContext('2d');
const productsChart = new Chart(productsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($productNames); ?>,
        datasets: [{
            label: 'Quantity Consumed',
            data: <?php echo json_encode($productValues); ?>,
            backgroundColor: '#198754',
            borderColor: '#146c43',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
