<?php
/**
 * Stock Adjustment Controller
 */

class AdjustmentController extends Controller {
    public function getStores($userId = null) {
        $storeIds = $this->getVisibleStoreIds($userId);
        if ($storeIds === []) {
            return [];
        }

        $sql = "SELECT store_id, store_name FROM stores WHERE status = 'active'";
        if (is_array($storeIds) && !empty($storeIds)) {
            $sql .= " AND store_id IN (" . implode(',', array_map('intval', $storeIds)) . ")";
        }
        $sql .= " ORDER BY store_name ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductsForStore($storeId) {
        $storeId = (int)$storeId;
        if ($storeId <= 0) {
            return [];
        }

        $sql = "
            SELECT
                p.product_id,
                p.product_name,
                p.product_code,
                p.unit_of_measure,
                COALESCE(s.quantity_on_hand, 0) AS quantity_on_hand,
                COALESCE((
                    SELECT gi.unit_price
                    FROM grn_items gi
                    WHERE gi.product_id = p.product_id
                    ORDER BY gi.grn_item_id DESC
                    LIMIT 1
                ), 0) AS unit_price
            FROM stock s
            JOIN products p ON p.product_id = s.product_id
            WHERE s.store_id = ? AND p.status = 'active'
            ORDER BY p.product_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAdjustments($filters = [], $userId = null) {
        $sql = "
            SELECT
                sa.adjustment_id,
                sa.adjustment_number,
                sa.adjustment_reason,
                sa.adjustment_date,
                sa.status,
                sa.notes,
                s.store_name,
                ua.full_name AS adjusted_by_name,
                ub.full_name AS approved_by_name,
                COUNT(ai.adj_item_id) AS item_count,
                COALESCE(SUM(ai.quantity_change), 0) AS net_quantity_change
            FROM stock_adjustments sa
            JOIN stores s ON sa.store_id = s.store_id
            JOIN users ua ON sa.adjusted_by = ua.user_id
            LEFT JOIN users ub ON sa.approved_by = ub.user_id
            LEFT JOIN adjustment_items ai ON sa.adjustment_id = ai.adjustment_id
            WHERE 1=1
        ";

        $types = '';
        $params = [];
        $storeIds = $this->getVisibleStoreIds($userId);
        if ($storeIds === []) {
            return [];
        }

        if (is_array($storeIds) && !empty($storeIds)) {
            $sql .= " AND sa.store_id IN (" . implode(',', array_map('intval', $storeIds)) . ")";
        }

        if (in_array($filters['status'] ?? 'all', ['pending', 'approved', 'rejected'], true)) {
            $sql .= " AND sa.status = ?";
            $types .= 's';
            $params[] = $filters['status'];
        }

        if (!empty($filters['store_id']) && ctype_digit((string)$filters['store_id'])) {
            $sql .= " AND sa.store_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['store_id'];
        }

        if (in_array($filters['reason'] ?? '', ['damage', 'loss', 'correction', 'count_variance', 'recall'], true)) {
            $sql .= " AND sa.adjustment_reason = ?";
            $types .= 's';
            $params[] = $filters['reason'];
        }

        $sql .= " GROUP BY sa.adjustment_id ORDER BY sa.adjustment_date DESC LIMIT 200";

        $stmt = $this->db->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStats($userId = null) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];

        $storeIds = $this->getVisibleStoreIds($userId);
        if ($storeIds === []) {
            return $stats;
        }

        $storeFilter = '';
        if (is_array($storeIds) && !empty($storeIds)) {
            $storeFilter = ' WHERE store_id IN (' . implode(',', array_map('intval', $storeIds)) . ')';
        }

        $statsQueries = [
            'total' => "SELECT COUNT(*) AS c FROM stock_adjustments" . $storeFilter,
            'pending' => "SELECT COUNT(*) AS c FROM stock_adjustments" . $storeFilter . (strpos($storeFilter, 'WHERE') !== false ? " AND status = 'pending'" : " WHERE status = 'pending'"),
            'approved' => "SELECT COUNT(*) AS c FROM stock_adjustments" . $storeFilter . (strpos($storeFilter, 'WHERE') !== false ? " AND status = 'approved'" : " WHERE status = 'approved'"),
            'rejected' => "SELECT COUNT(*) AS c FROM stock_adjustments" . $storeFilter . (strpos($storeFilter, 'WHERE') !== false ? " AND status = 'rejected'" : " WHERE status = 'rejected'"),
        ];

        foreach ($statsQueries as $key => $sql) {
            $result = $this->db->query($sql);
            $stats[$key] = (int)($result->fetch_assoc()['c'] ?? 0);
        }

        return $stats;
    }

    private function getVisibleStoreIds($userId = null) {
        if (empty($userId) || !ctype_digit((string)$userId)) {
            return null;
        }

        $sql = "SELECT r.role_name, d.dept_code, d.dept_name, s.store_id
                FROM users u
                JOIN roles r ON r.role_id = u.role_id
                LEFT JOIN departments d ON d.status = 'active' AND d.dept_name = r.role_name
                LEFT JOIN stores s ON s.status = 'active'
                    AND d.dept_code IS NOT NULL
                    AND (
                        s.store_code = CONCAT(d.dept_code, '001')
                        OR s.store_code LIKE CONCAT(d.dept_code, '%')
                        OR s.store_name LIKE CONCAT(d.dept_name, '%')
                    )
                WHERE u.user_id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            return [];
        }

        if (in_array((string)$row['role_name'], ['Admin', 'Manager', 'Storekeeper'], true)) {
            return null;
        }

        return !empty($row['store_id']) ? [(int)$row['store_id']] : [];
    }

    public function getById($adjustmentId) {
        $sql = "
            SELECT sa.*, s.store_name, ua.full_name AS adjusted_by_name, ub.full_name AS approved_by_name
            FROM stock_adjustments sa
            JOIN stores s ON sa.store_id = s.store_id
            JOIN users ua ON sa.adjusted_by = ua.user_id
            LEFT JOIN users ub ON sa.approved_by = ub.user_id
            WHERE sa.adjustment_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $adjustmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($adjustmentId) {
        $sql = "
            SELECT ai.*, p.product_name, p.product_code, p.unit_of_measure
            FROM adjustment_items ai
            JOIN products p ON p.product_id = ai.product_id
            WHERE ai.adjustment_id = ?
            ORDER BY ai.adj_item_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $adjustmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data, $items, $userId) {
        if (!can('adjustments.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        if (empty($data['store_id']) || !ctype_digit((string)$data['store_id'])) {
            return ['success' => false, 'message' => 'Please select a store'];
        }

        if (!in_array($data['adjustment_reason'] ?? '', ['damage', 'loss', 'correction', 'count_variance', 'recall'], true)) {
            return ['success' => false, 'message' => 'Please select a valid adjustment reason'];
        }

        $normalizedItems = $this->normalizeItems($items);
        if (empty($normalizedItems)) {
            return ['success' => false, 'message' => 'Add at least one item with non-zero quantity change'];
        }

        $storeId = (int)$data['store_id'];
        $allowedProducts = array_column($this->getProductsForStore($storeId), 'product_id');
        $allowedProducts = array_map('intval', $allowedProducts);

        foreach ($normalizedItems as $item) {
            if (!in_array((int)$item['product_id'], $allowedProducts, true)) {
                return ['success' => false, 'message' => 'One or more selected products do not belong to the selected store stock'];
            }
        }

        $this->db->begin_transaction();
        try {
            $adjustmentNumber = $this->generateAdjustmentNumber();
            $notes = trim($data['notes'] ?? '');
            $reason = $data['adjustment_reason'];

            $headerSql = "
                INSERT INTO stock_adjustments (adjustment_number, store_id, adjustment_reason, adjusted_by, status, notes)
                VALUES (?, ?, ?, ?, 'pending', ?)
            ";
            $headerStmt = $this->db->prepare($headerSql);
            $headerStmt->bind_param('sisis', $adjustmentNumber, $storeId, $reason, $userId, $notes);
            $headerStmt->execute();
            $adjustmentId = $this->db->insert_id;

            foreach ($normalizedItems as $item) {
                $itemSql = "
                    INSERT INTO adjustment_items (adjustment_id, product_id, quantity_change, reason_details)
                    VALUES (?, ?, ?, ?)
                ";
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->bind_param(
                    'iiis',
                    $adjustmentId,
                    $item['product_id'],
                    $item['quantity_change'],
                    $item['reason_details']
                );
                $itemStmt->execute();
            }

            $this->logAudit('Create', 'stock_adjustment', $adjustmentId, '', json_encode([
                'adjustment_number' => $adjustmentNumber,
                'store_id' => $storeId,
                'reason' => $reason,
                'line_count' => count($normalizedItems)
            ]));

            $this->db->commit();
            return ['success' => true, 'message' => 'Adjustment created and submitted for approval', 'adjustment_id' => $adjustmentId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create adjustment: ' . $e->getMessage()];
        }
    }

    public function approve($adjustmentId, $approverId) {
        if (!can('adjustments.approve')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $adjustmentId = (int)$adjustmentId;
        $adjustment = $this->getById($adjustmentId);
        if (!$adjustment) {
            return ['success' => false, 'message' => 'Adjustment not found'];
        }

        if ($adjustment['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Only pending adjustments can be approved'];
        }

        $items = $this->getItems($adjustmentId);
        if (empty($items)) {
            return ['success' => false, 'message' => 'Adjustment has no line items'];
        }

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $storeId = (int)$adjustment['store_id'];
            $change = (int)$item['quantity_change'];

            $stockSql = "SELECT quantity_on_hand FROM stock WHERE product_id = ? AND store_id = ? LIMIT 1";
            $stockStmt = $this->db->prepare($stockSql);
            $stockStmt->bind_param('ii', $productId, $storeId);
            $stockStmt->execute();
            $stockRow = $stockStmt->get_result()->fetch_assoc();
            $currentQty = (int)($stockRow['quantity_on_hand'] ?? 0);

            if (($currentQty + $change) < 0) {
                return ['success' => false, 'message' => 'Approval would create negative stock for one or more items'];
            }
        }

        $this->db->begin_transaction();
        try {
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $storeId = (int)$adjustment['store_id'];
                $change = (int)$item['quantity_change'];

                $updateStockSql = "
                    UPDATE stock
                    SET quantity_on_hand = quantity_on_hand + ?, updated_at = NOW()
                    WHERE product_id = ? AND store_id = ?
                ";
                $updateStockStmt = $this->db->prepare($updateStockSql);
                $updateStockStmt->bind_param('iii', $change, $productId, $storeId);
                $updateStockStmt->execute();

                $priceSql = "
                    SELECT unit_price
                    FROM grn_items
                    WHERE product_id = ?
                    ORDER BY grn_item_id DESC
                    LIMIT 1
                ";
                $priceStmt = $this->db->prepare($priceSql);
                $priceStmt->bind_param('i', $productId);
                $priceStmt->execute();
                $priceRow = $priceStmt->get_result()->fetch_assoc();
                $unitPrice = (float)($priceRow['unit_price'] ?? 0);

                $type = 'adjustment';
                $refType = 'ADJUSTMENT';
                $txnSql = "
                    INSERT INTO stock_transactions
                    (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, unit_price, total_value, performed_by, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $note = 'Stock adjustment approved: ' . $adjustment['adjustment_number'];
                $txnStmt = $this->db->prepare($txnSql);
                $totalValue = abs((float)$change) * (float)$unitPrice;
                $txnStmt->bind_param('iissiiddis', $productId, $storeId, $type, $refType, $adjustmentId, $change, $unitPrice, $totalValue, $approverId, $note);
                $txnStmt->execute();
            }

            $statusSql = "
                UPDATE stock_adjustments
                SET status = 'approved', approved_by = ?, approval_date = NOW()
                WHERE adjustment_id = ?
            ";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->bind_param('ii', $approverId, $adjustmentId);
            $statusStmt->execute();

            $this->logAudit('Approve', 'stock_adjustment', $adjustmentId, 'pending', 'approved');

            $this->db->commit();
            return ['success' => true, 'message' => 'Adjustment approved and stock updated'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to approve adjustment: ' . $e->getMessage()];
        }
    }

    public function reject($adjustmentId, $approverId, $reason = '') {
        if (!can('adjustments.approve')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $adjustmentId = (int)$adjustmentId;
        $adjustment = $this->getById($adjustmentId);
        if (!$adjustment) {
            return ['success' => false, 'message' => 'Adjustment not found'];
        }

        if ($adjustment['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Only pending adjustments can be rejected'];
        }

        $notes = trim((string)$adjustment['notes']);
        $rejectNote = trim((string)$reason);
        if ($rejectNote !== '') {
            $notes = trim($notes . "\nRejected reason: " . $rejectNote);
        }

        $sql = "
            UPDATE stock_adjustments
            SET status = 'rejected', approved_by = ?, approval_date = NOW(), notes = ?
            WHERE adjustment_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isi', $approverId, $notes, $adjustmentId);

        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Failed to reject adjustment'];
        }

        $this->logAudit('Reject', 'stock_adjustment', $adjustmentId, 'pending', 'rejected');
        return ['success' => true, 'message' => 'Adjustment rejected'];
    }

    private function normalizeItems($items) {
        $productIds = $items['product_id'] ?? [];
        $changes = $items['quantity_change'] ?? [];
        $reasons = $items['reason_details'] ?? [];

        $rows = [];
        $count = count($productIds);
        for ($i = 0; $i < $count; $i++) {
            $productId = $productIds[$i] ?? '';
            $change = $changes[$i] ?? '';

            if (!ctype_digit((string)$productId)) {
                continue;
            }
            if (!is_numeric($change) || (int)$change === 0) {
                continue;
            }

            $rows[] = [
                'product_id' => (int)$productId,
                'quantity_change' => (int)$change,
                'reason_details' => trim($reasons[$i] ?? '')
            ];
        }

        return $rows;
    }

    private function generateAdjustmentNumber() {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) AS c FROM stock_adjustments WHERE DATE(adjustment_date) = CURDATE()";
        $count = (int)$this->db->query($sql)->fetch_assoc()['c'] + 1;
        return 'ADJ-' . $date . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }
}
