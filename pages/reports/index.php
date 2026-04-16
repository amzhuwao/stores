<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new ReportController();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$storeIdRaw = $_GET['store_id'] ?? '';
$budgetPeriod = ($_GET['budget_period'] ?? 'monthly') === 'weekly' ? 'weekly' : 'monthly';
$storeId = ctype_digit((string)$storeIdRaw) ? (int)$storeIdRaw : null;

$stores = $controller->getStores();
$summary = $controller->getSummary($dateFrom, $dateTo, $storeId);
$financial = $controller->getFinancialKpis($dateFrom, $dateTo, $storeId);
$movement = $controller->getStockMovement($dateFrom, $dateTo, $storeId);
$lowStock = $controller->getLowStockItems($storeId);
$topConsumed = $controller->getTopConsumedItems($dateFrom, $dateTo, $storeId);
$trend = $controller->getMonthlyTrend(6, $storeId);

$stockValuation = $controller->getStockValuationReport($storeId);
$departmentConsumption = $controller->getDepartmentConsumptionReport($dateFrom, $dateTo, $storeId);
$budgetVsSpend = $controller->getBudgetVsSpendReport($dateFrom, $dateTo, $budgetPeriod, $storeId);
$monthlyPurchases = $controller->getMonthlyPurchaseReport($dateFrom, $dateTo, $storeId);
$supplierPerformance = $controller->getSupplierPerformanceReport($dateFrom, $dateTo, $storeId);
$variance = $controller->getVarianceReport($dateFrom, $dateTo, $storeId);
$profitImpact = $controller->getProfitImpactAnalysis($dateFrom, $dateTo, $storeId);

if (($_GET['export'] ?? '') === 'pdf') {
    $html = '<p><strong>Range:</strong> ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . '</p>';
    $html .= '<table><thead><tr><th>Financial Metric</th><th>Value</th></tr></thead><tbody>';
    $html .= '<tr><td>Stock Valuation</td><td>$' . number_format((float)$financial['stock_valuation'], 2) . '</td></tr>';
    $html .= '<tr><td>COGI</td><td>$' . number_format((float)$financial['cogi'], 2) . '</td></tr>';
    $html .= '<tr><td>Purchase Total</td><td>$' . number_format((float)$financial['purchase_total'], 2) . '</td></tr>';
    $html .= '<tr><td>Variance Impact</td><td>$' . number_format((float)$financial['variance_value'], 2) . '</td></tr>';
    $html .= '</tbody></table>';
    exportPdfOrPrintHtml('Financial Reports Dashboard', $html, 'financial-reports-dashboard');
}

$pageTitle = 'Financial Reports Dashboard';
$activePage = 'reports';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header">
    <h1>Financial Reports Dashboard</h1>
    <p>Stock valuation, COGI, budgets, supplier spend, variance, and profit-impact analytics.</p>
    <div class="mt-2">
        <a class="btn btn-outline-primary" href="<?php
            $query = $_GET;
            $query['export'] = 'pdf';
            echo SITE_URL . 'pages/reports/index.php?' . http_build_query($query);
        ?>">Export PDF</a>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-filter"></i> Report Filters</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Store</label>
                <select name="store_id" class="form-control">
                    <option value="">All Stores</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo (int)$store['store_id']; ?>" <?php echo $storeId === (int)$store['store_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($store['store_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Budget Period</label>
                <select name="budget_period" class="form-control">
                    <option value="monthly" <?php echo $budgetPeriod === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="weekly" <?php echo $budgetPeriod === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="<?php echo SITE_URL; ?>pages/reports/index.php" class="btn btn-outline-primary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3"><div class="stat-card"><div class="stat-label">Stock Valuation</div><div class="stat-value">$<?php echo number_format((float)$financial['stock_valuation'], 2); ?></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="stat-card warning"><div class="stat-label">COGI</div><div class="stat-value">$<?php echo number_format((float)$financial['cogi'], 2); ?></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="stat-card success"><div class="stat-label">Purchases</div><div class="stat-value">$<?php echo number_format((float)$financial['purchase_total'], 2); ?></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="stat-card danger"><div class="stat-label">Variance Impact</div><div class="stat-value">$<?php echo number_format((float)$financial['variance_value'], 2); ?></div></div></div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-coins"></i> Stock Valuation by Store</h5></div>
            <div class="card-body">
                <?php if (!empty($stockValuation)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Store</th><th>Total Qty</th><th>Stock Value</th></tr></thead>
                            <tbody>
                                <?php foreach ($stockValuation as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['store_name']); ?></td>
                                        <td><?php echo (int)$row['total_qty']; ?></td>
                                        <td>$<?php echo number_format((float)$row['stock_value'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No stock valuation data available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-exclamation-triangle"></i> Low Stock (Top 20)</h5></div>
            <div class="card-body">
                <?php if (!empty($lowStock)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th>Qty</th><th>Reorder</th></tr></thead>
                            <tbody>
                                <?php foreach ($lowStock as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br><small><?php echo htmlspecialchars($row['store_name']); ?></small></td>
                                        <td><?php echo (int)$row['quantity_on_hand']; ?></td>
                                        <td><?php echo (int)$row['reorder_level']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-0">No low-stock lines found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-utensils"></i> Department Consumption (COGI)</h5></div>
            <div class="card-body">
                <?php if (!empty($departmentConsumption)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Department</th><th>Qty</th><th>Value</th><th>Issues</th></tr></thead>
                            <tbody>
                                <?php foreach ($departmentConsumption as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                        <td><?php echo number_format((float)$row['issued_qty'], 0); ?></td>
                                        <td>$<?php echo number_format((float)$row['issued_value'], 2); ?></td>
                                        <td><?php echo (int)$row['issue_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No departmental consumption data for this range.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-wallet"></i> Budget vs Spending (<?php echo ucfirst($budgetPeriod); ?>)</h5></div>
            <div class="card-body">
                <?php if (!empty($budgetVsSpend)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Department</th><th>Budget</th><th>Spent</th><th>Variance</th><th>Utilization</th></tr></thead>
                            <tbody>
                                <?php foreach ($budgetVsSpend as $row): ?>
                                    <?php $over = (float)$row['variance_value'] < 0; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                        <td>$<?php echo number_format((float)$row['budget_value'], 2); ?></td>
                                        <td>$<?php echo number_format((float)$row['spent_value'], 2); ?></td>
                                        <td class="<?php echo $over ? 'text-danger' : 'text-success'; ?>">$<?php echo number_format((float)$row['variance_value'], 2); ?></td>
                                        <td><?php echo number_format((float)$row['utilization_pct'], 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No budget/spend data available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-truck-loading"></i> Monthly Purchase Tracking</h5></div>
            <div class="card-body">
                <?php if (!empty($monthlyPurchases)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Month</th><th>Supplier</th><th>Deliveries</th><th>Value</th></tr></thead>
                            <tbody>
                                <?php foreach ($monthlyPurchases as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['period_month']); ?></td>
                                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                        <td><?php echo (int)$row['delivery_count']; ?></td>
                                        <td>$<?php echo number_format((float)$row['purchased_value'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No purchase records in this date range.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-user-check"></i> Supplier Performance</h5></div>
            <div class="card-body">
                <?php if (!empty($supplierPerformance)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Supplier</th><th>Deliveries</th><th>Spend</th><th>Fulfillment</th></tr></thead>
                            <tbody>
                                <?php foreach ($supplierPerformance as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                        <td><?php echo (int)$row['deliveries']; ?></td>
                                        <td>$<?php echo number_format((float)$row['total_spend'], 2); ?></td>
                                        <td><?php echo number_format((float)$row['fulfillment_pct'], 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No supplier activity in this date range.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-random"></i> Variance & Loss Tracking</h5></div>
            <div class="card-body">
                <?php if (!empty($variance)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Adjustment</th><th>Product</th><th>Qty Δ</th><th>Value Impact</th></tr></thead>
                            <tbody>
                                <?php foreach ($variance as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['adjustment_number']); ?></strong><br><small><?php echo htmlspecialchars($row['adjustment_date']); ?></small></td>
                                        <td><?php echo htmlspecialchars($row['product_name']); ?><br><small><?php echo htmlspecialchars($row['store_name']); ?></small></td>
                                        <td><?php echo (int)$row['quantity_change']; ?></td>
                                        <td>$<?php echo number_format((float)$row['variance_value'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-0">No approved count-variance adjustments in this period.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-chart-line"></i> Profit Impact Analysis (POS Linked)</h5></div>
            <div class="card-body">
                <p class="mb-2"><strong>Revenue:</strong> $<?php echo number_format((float)($profitImpact['revenue'] ?? 0), 2); ?></p>
                <p class="mb-2"><strong>COGS:</strong> $<?php echo number_format((float)($profitImpact['cogs'] ?? 0), 2); ?></p>
                <p class="mb-2"><strong>Gross Profit:</strong> $<?php echo number_format((float)($profitImpact['gross_profit'] ?? 0), 2); ?></p>
                <p class="mb-0"><strong>Gross Margin:</strong> <?php echo number_format((float)($profitImpact['gross_margin_pct'] ?? 0), 2); ?>%</p>
                <?php if ((int)($profitImpact['sales_lines'] ?? 0) === 0): ?>
                    <div class="alert alert-warning mt-3 mb-0">No POS usage rows found. Add POS integration feed into <code>pos_sales_usage</code> to activate this analysis.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-fire"></i> Top Consumed Items</h5></div>
            <div class="card-body">
                <?php if (!empty($topConsumed)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th>Qty</th><th>Value</th></tr></thead>
                            <tbody>
                                <?php foreach ($topConsumed as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br><small><?php echo htmlspecialchars($row['store_name']); ?></small></td>
                                        <td><?php echo (int)$row['consumed_qty']; ?></td>
                                        <td>$<?php echo number_format((float)$row['consumed_value'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No consumption records in this range.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-chart-line"></i> 6-Month Trend</h5></div>
            <div class="card-body">
                <canvas id="trendChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const trendLabels = <?php echo json_encode(array_map(function($r) { return $r['month_key']; }, $trend)); ?>;
const trendReceipts = <?php echo json_encode(array_map(function($r) { return (int)$r['receipts_qty']; }, $trend)); ?>;
const trendIssues = <?php echo json_encode(array_map(function($r) { return (int)$r['issues_qty']; }, $trend)); ?>;

const trendCtx = document.getElementById('trendChart');
if (trendCtx && trendLabels.length > 0) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label: 'Receipts Qty',
                    data: trendReceipts,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.12)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Issues Qty',
                    data: trendIssues,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.10)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
