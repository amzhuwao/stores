<?php
/**
 * Report Controller
 */

class ReportController extends Controller {
    public function getStores() {
        $sql = "SELECT store_id, store_name FROM stores WHERE status = 'active' ORDER BY store_name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getSummary($dateFrom, $dateTo, $storeId = null) {
        $summary = [
            'receipts_qty' => 0,
            'issues_qty' => 0,
            'receipts_value' => 0.0,
            'issues_value' => 0.0,
            'active_products' => 0,
            'low_stock_lines' => 0
        ];

        $filterSql = " WHERE DATE(st.transaction_date) BETWEEN ? AND ?";
        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $filterSql .= " AND st.store_id = ?";
            $types .= 'i';
            $params[] = $storeId;
        }

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN st.transaction_type = 'receipt' THEN st.quantity_change ELSE 0 END), 0) AS receipts_qty,
                    COALESCE(SUM(CASE WHEN st.transaction_type = 'issue' THEN ABS(st.quantity_change) ELSE 0 END), 0) AS issues_qty,
                    COALESCE(SUM(CASE WHEN st.transaction_type = 'receipt' THEN COALESCE(st.total_value, st.quantity_change * COALESCE(st.unit_price, 0)) ELSE 0 END), 0) AS receipts_value,
                    COALESCE(SUM(CASE WHEN st.transaction_type = 'issue' THEN COALESCE(st.total_value, ABS(st.quantity_change) * COALESCE(st.unit_price, 0)) ELSE 0 END), 0) AS issues_value
                FROM stock_transactions st" . $filterSql;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $summary['receipts_qty'] = (int)$row['receipts_qty'];
        $summary['issues_qty'] = (int)$row['issues_qty'];
        $summary['receipts_value'] = (float)$row['receipts_value'];
        $summary['issues_value'] = (float)$row['issues_value'];

        $summary['active_products'] = (int)$this->db->query("SELECT COUNT(*) AS c FROM products WHERE status = 'active'")->fetch_assoc()['c'];

        $lowStockSql = "SELECT COUNT(*) AS c
                        FROM stock s
                        JOIN products p ON p.product_id = s.product_id
                        WHERE p.status = 'active' AND s.quantity_on_hand > 0 AND s.quantity_on_hand <= s.reorder_level";

        if ($storeId) {
            $lowStockSql .= " AND s.store_id = " . (int)$storeId;
        }

        $summary['low_stock_lines'] = (int)$this->db->query($lowStockSql)->fetch_assoc()['c'];

        return $summary;
    }

    public function getFinancialKpis($dateFrom, $dateTo, $storeId = null) {
        $kpis = [
            'stock_valuation' => 0.0,
            'cogi' => 0.0,
            'purchase_total' => 0.0,
            'variance_value' => 0.0
        ];

        $valuationSql = "SELECT
                COALESCE(SUM(
                    s.quantity_on_hand * COALESCE(
                        (SELECT gi.unit_price FROM grn_items gi WHERE gi.product_id = s.product_id ORDER BY gi.grn_item_id DESC LIMIT 1),
                        0
                    )
                ), 0) AS valuation
            FROM stock s";

        if ($storeId) {
            $valuationSql .= " WHERE s.store_id = " . (int)$storeId;
        }
        $kpis['stock_valuation'] = (float)$this->db->query($valuationSql)->fetch_assoc()['valuation'];

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN st.transaction_type = 'issue' THEN COALESCE(st.total_value, ABS(st.quantity_change) * COALESCE(st.unit_price, 0)) ELSE 0 END), 0) AS cogi,
                    COALESCE(SUM(CASE WHEN st.transaction_type = 'receipt' THEN COALESCE(st.total_value, st.quantity_change * COALESCE(st.unit_price, 0)) ELSE 0 END), 0) AS purchase_total
                FROM stock_transactions st
                WHERE DATE(st.transaction_date) BETWEEN ? AND ?";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND st.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $kpis['cogi'] = (float)$row['cogi'];
        $kpis['purchase_total'] = (float)$row['purchase_total'];

        $varianceSql = "SELECT COALESCE(SUM(COALESCE(st.total_value, ABS(st.quantity_change) * COALESCE(st.unit_price, 0))), 0) AS variance_value
                        FROM stock_transactions st
                        JOIN stock_adjustments sa ON sa.adjustment_id = st.reference_id AND st.reference_type = 'ADJUSTMENT'
                        WHERE st.transaction_type = 'adjustment'
                          AND sa.adjustment_reason = 'count_variance'
                          AND sa.status = 'approved'
                          AND DATE(st.transaction_date) BETWEEN ? AND ?";

        $vTypes = 'ss';
        $vParams = [$dateFrom, $dateTo];
        if ($storeId) {
            $varianceSql .= " AND st.store_id = ?";
            $vTypes .= 'i';
            $vParams[] = (int)$storeId;
        }

        $stmt = $this->db->prepare($varianceSql);
        $stmt->bind_param($vTypes, ...$vParams);
        $stmt->execute();
        $kpis['variance_value'] = (float)$stmt->get_result()->fetch_assoc()['variance_value'];

        return $kpis;
    }

    public function getStockValuationReport($storeId = null) {
        $sql = "SELECT
                    s.store_id,
                    st.store_name,
                    COALESCE(SUM(s.quantity_on_hand), 0) AS total_qty,
                    COALESCE(SUM(s.quantity_on_hand * COALESCE((
                        SELECT gi.unit_price
                        FROM grn_items gi
                        WHERE gi.product_id = s.product_id
                        ORDER BY gi.grn_item_id DESC
                        LIMIT 1
                    ), 0)), 0) AS stock_value
                FROM stock s
                JOIN stores st ON st.store_id = s.store_id
                GROUP BY s.store_id, st.store_name
                ORDER BY st.store_name ASC";

        if ($storeId) {
            $stmt = $this->db->prepare(str_replace('GROUP BY', 'WHERE s.store_id = ? GROUP BY', $sql));
            $stmt->bind_param('i', $storeId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getDepartmentConsumptionReport($dateFrom, $dateTo, $storeId = null) {
        $sql = "SELECT
                    d.dept_id,
                    d.dept_name,
                    COALESCE(SUM(sii.quantity_issued), 0) AS issued_qty,
                    COALESCE(SUM(sii.quantity_issued * COALESCE(sii.unit_price, 0)), 0) AS issued_value,
                    COUNT(DISTINCT si.issue_id) AS issue_count
                FROM departments d
                LEFT JOIN stock_issues si ON si.department_id = d.dept_id
                    AND DATE(si.issue_date) BETWEEN ? AND ?
                    AND si.status IN ('issued', 'received')
                LEFT JOIN stock_issue_items sii ON sii.issue_id = si.issue_id";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND si.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $sql .= " GROUP BY d.dept_id, d.dept_name ORDER BY issued_value DESC, d.dept_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBudgetVsSpendReport($dateFrom, $dateTo, $period = 'monthly', $storeId = null) {
        $budgetColumn = $period === 'weekly' ? 'weekly_budget' : 'monthly_budget';

        $sql = "SELECT
                    d.dept_id,
                    d.dept_name,
                    d.monthly_budget,
                    d.weekly_budget,
                    COALESCE(SUM(sii.quantity_issued * COALESCE(sii.unit_price, 0)), 0) AS spent_value
                FROM departments d
                LEFT JOIN stock_issues si ON si.department_id = d.dept_id
                    AND DATE(si.issue_date) BETWEEN ? AND ?
                    AND si.status IN ('issued', 'received')
                LEFT JOIN stock_issue_items sii ON sii.issue_id = si.issue_id";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND si.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $sql .= " GROUP BY d.dept_id, d.dept_name, d.monthly_budget, d.weekly_budget ORDER BY d.dept_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as &$row) {
            $budget = (float)$row[$budgetColumn];
            $spent = (float)$row['spent_value'];
            $row['budget_period'] = $period;
            $row['budget_value'] = $budget;
            $row['variance_value'] = $budget - $spent;
            $row['utilization_pct'] = $budget > 0 ? (($spent / $budget) * 100) : 0;
        }
        unset($row);

        return $rows;
    }

    public function getMonthlyPurchaseReport($dateFrom, $dateTo, $storeId = null) {
        $sql = "SELECT
                    DATE_FORMAT(g.receipt_date, '%Y-%m') AS period_month,
                    sup.supplier_name,
                    COUNT(DISTINCT g.grn_id) AS delivery_count,
                    COALESCE(SUM(gi.quantity_received), 0) AS purchased_qty,
                    COALESCE(SUM(gi.quantity_received * gi.unit_price), 0) AS purchased_value,
                    COALESCE(AVG(gi.unit_price), 0) AS avg_unit_price
                FROM grn g
                JOIN suppliers sup ON sup.supplier_id = g.supplier_id
                JOIN grn_items gi ON gi.grn_id = g.grn_id
                WHERE g.status IN ('received', 'verified')
                  AND DATE(g.receipt_date) BETWEEN ? AND ?";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND g.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $sql .= " GROUP BY DATE_FORMAT(g.receipt_date, '%Y-%m'), g.supplier_id, sup.supplier_name
                  ORDER BY period_month DESC, purchased_value DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSupplierPerformanceReport($dateFrom, $dateTo, $storeId = null) {
        $sql = "SELECT
                    sup.supplier_name,
                    COUNT(DISTINCT g.grn_id) AS deliveries,
                    COALESCE(SUM(g.total_cost), 0) AS total_spend,
                    COALESCE(AVG(g.total_cost), 0) AS avg_delivery_cost,
                    COALESCE(SUM(gi.quantity_received), 0) AS total_units,
                    COALESCE(AVG(CASE WHEN gi.quantity_expected > 0 THEN (gi.quantity_received / gi.quantity_expected) * 100 ELSE 100 END), 100) AS fulfillment_pct
                FROM suppliers sup
                LEFT JOIN grn g ON g.supplier_id = sup.supplier_id
                    AND DATE(g.receipt_date) BETWEEN ? AND ?
                    AND g.status IN ('received', 'verified')
                LEFT JOIN grn_items gi ON gi.grn_id = g.grn_id";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND g.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $sql .= " GROUP BY sup.supplier_id, sup.supplier_name
                  ORDER BY total_spend DESC, sup.supplier_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getVarianceReport($dateFrom, $dateTo, $storeId = null) {
        $sql = "SELECT
                    sa.adjustment_number,
                    sa.adjustment_date,
                    sa.adjustment_reason,
                    st.store_name,
                    p.product_name,
                    ai.quantity_change,
                    COALESCE((SELECT gi.unit_price FROM grn_items gi WHERE gi.product_id = ai.product_id ORDER BY gi.grn_item_id DESC LIMIT 1), 0) AS unit_price,
                    ABS(ai.quantity_change) * COALESCE((SELECT gi.unit_price FROM grn_items gi WHERE gi.product_id = ai.product_id ORDER BY gi.grn_item_id DESC LIMIT 1), 0) AS variance_value
                FROM stock_adjustments sa
                JOIN stores st ON st.store_id = sa.store_id
                JOIN adjustment_items ai ON ai.adjustment_id = sa.adjustment_id
                JOIN products p ON p.product_id = ai.product_id
                WHERE sa.status = 'approved'
                  AND sa.adjustment_reason = 'count_variance'
                  AND DATE(sa.adjustment_date) BETWEEN ? AND ?";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND sa.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $sql .= " ORDER BY sa.adjustment_date DESC, sa.adjustment_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProfitImpactAnalysis($dateFrom, $dateTo, $storeId = null) {
        $sql = "SELECT
                    COALESCE(SUM(psu.revenue), 0) AS revenue,
                    COALESCE(SUM(psu.cogs), 0) AS cogs,
                    COALESCE(SUM(psu.revenue - psu.cogs), 0) AS gross_profit,
                    COALESCE(AVG(CASE WHEN psu.revenue > 0 THEN ((psu.revenue - psu.cogs) / psu.revenue) * 100 ELSE NULL END), 0) AS gross_margin_pct,
                    COUNT(*) AS sales_lines
                FROM pos_sales_usage psu
                WHERE DATE(psu.sale_date) BETWEEN ? AND ?";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND psu.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getStockMovement($dateFrom, $dateTo, $storeId = null) {
        $sql = "SELECT
                    DATE(st.transaction_date) AS movement_date,
                    st.transaction_type,
                    SUM(st.quantity_change) AS net_quantity,
                    SUM(COALESCE(st.total_value, ABS(st.quantity_change) * COALESCE(st.unit_price, 0))) AS movement_value
                FROM stock_transactions st
                WHERE DATE(st.transaction_date) BETWEEN ? AND ?";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND st.store_id = ?";
            $types .= 'i';
            $params[] = $storeId;
        }

        $sql .= " GROUP BY DATE(st.transaction_date), st.transaction_type
                  ORDER BY movement_date DESC, st.transaction_type ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowStockItems($storeId = null) {
        $sql = "SELECT
                    s.stock_id,
                    st.store_name,
                    p.product_name,
                    p.product_code,
                    s.quantity_on_hand,
                    s.reorder_level
                FROM stock s
                JOIN products p ON p.product_id = s.product_id
                JOIN stores st ON st.store_id = s.store_id
                WHERE p.status = 'active'
                  AND s.quantity_on_hand > 0
                  AND s.quantity_on_hand <= s.reorder_level";

        $types = '';
        $params = [];

        if ($storeId) {
            $sql .= " AND s.store_id = ?";
            $types .= 'i';
            $params[] = $storeId;
        }

        $sql .= " ORDER BY (s.reorder_level - s.quantity_on_hand) DESC, p.product_name ASC LIMIT 20";

        if ($types !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopConsumedItems($dateFrom, $dateTo, $storeId = null) {
        $sql = "SELECT
                    p.product_name,
                    p.product_code,
                    st.store_name,
                    SUM(ABS(tx.quantity_change)) AS consumed_qty,
                    SUM(COALESCE(tx.total_value, ABS(tx.quantity_change) * COALESCE(tx.unit_price, 0))) AS consumed_value
                FROM stock_transactions tx
                JOIN products p ON p.product_id = tx.product_id
                JOIN stores st ON st.store_id = tx.store_id
                WHERE tx.transaction_type = 'issue'
                  AND DATE(tx.transaction_date) BETWEEN ? AND ?";

        $types = 'ss';
        $params = [$dateFrom, $dateTo];

        if ($storeId) {
            $sql .= " AND tx.store_id = ?";
            $types .= 'i';
            $params[] = $storeId;
        }

        $sql .= " GROUP BY tx.product_id, tx.store_id
                  ORDER BY consumed_qty DESC
                  LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMonthlyTrend($months = 6, $storeId = null) {
        $months = max(1, min(24, (int)$months));

        $sql = "SELECT
                    DATE_FORMAT(tx.transaction_date, '%Y-%m') AS month_key,
                    SUM(CASE WHEN tx.transaction_type = 'receipt' THEN tx.quantity_change ELSE 0 END) AS receipts_qty,
                    SUM(CASE WHEN tx.transaction_type = 'issue' THEN ABS(tx.quantity_change) ELSE 0 END) AS issues_qty
                FROM stock_transactions tx
                WHERE tx.transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";

        $types = 'i';
        $params = [$months];

        if ($storeId) {
            $sql .= " AND tx.store_id = ?";
            $types .= 'i';
            $params[] = $storeId;
        }

        $sql .= " GROUP BY DATE_FORMAT(tx.transaction_date, '%Y-%m')
                  ORDER BY month_key ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
