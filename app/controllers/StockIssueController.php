<?php
/**
 * Stock Issue Controller
 */

class StockIssueController extends Controller {
    public function getIssues($filters = [], $userId = null) {
        $sql = "SELECT si.*, d.dept_name, s.store_name, u.full_name AS issued_by_name,
                    rr.requisition_number
                FROM stock_issues si
                JOIN departments d ON si.department_id = d.dept_id
                JOIN stores s ON si.store_id = s.store_id
                JOIN users u ON si.issued_by = u.user_id
                LEFT JOIN requisitions rr ON si.requisition_id = rr.requisition_id
                WHERE 1 = 1";

        $types = '';
        $params = [];

        $scope = $this->getIssueVisibilityScope($userId);
        if (!empty($scope['deny_all'])) {
            return [];
        }
        if (!empty($scope['department_id'])) {
            $sql .= " AND si.department_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['department_id'];
        } elseif (!empty($scope['store_id'])) {
            $sql .= " AND si.store_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['store_id'];
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (si.issue_number LIKE ? OR d.dept_name LIKE ? OR s.store_name LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['store_id']) && ctype_digit((string)$filters['store_id'])) {
            $sql .= " AND si.store_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['store_id'];
        }

        if (!empty($filters['department_id']) && ctype_digit((string)$filters['department_id'])) {
            $sql .= " AND si.department_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['department_id'];
        }

        $status = $filters['status'] ?? 'all';
        if (in_array($status, ['issued', 'received', 'cancelled'], true)) {
            $sql .= " AND si.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " ORDER BY si.issue_date DESC, si.issue_id DESC";

        if ($types !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getStats($userId = null) {
        $stats = [
            'issued' => 0,
            'received' => 0,
            'cancelled' => 0
        ];

        $scope = $this->getIssueVisibilityScope($userId);
        if (!empty($scope['deny_all'])) {
            return $stats;
        }

        $sql = "SELECT status, COUNT(*) AS c FROM stock_issues WHERE 1 = 1";
        $types = '';
        $params = [];

        if (!empty($scope['department_id'])) {
            $sql .= " AND department_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['department_id'];
        }
        if (!empty($scope['store_id'])) {
            $sql .= " AND store_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['store_id'];
        }

        $sql .= " GROUP BY status";

        if ($types !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        while ($row = $result->fetch_assoc()) {
            if (array_key_exists($row['status'], $stats)) {
                $stats[$row['status']] = (int)$row['c'];
            }
        }

        return $stats;
    }

    public function getStores($userId = null) {
        $scope = $this->getIssueVisibilityScope($userId);
        if (!empty($scope['deny_all'])) {
            return [];
        }

        $sql = "SELECT store_id, store_name FROM stores WHERE status = 'active'";
        if (!empty($scope['store_id'])) {
            $sql .= " AND store_id = " . (int)$scope['store_id'];
        }
        $sql .= " ORDER BY store_name ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getDepartments($userId = null) {
        $scope = $this->getIssueVisibilityScope($userId);
        if (!empty($scope['deny_all'])) {
            return [];
        }

        $sql = "SELECT dept_id, dept_name FROM departments WHERE status = 'active'";
        if (!empty($scope['department_id'])) {
            $sql .= " AND dept_id = " . (int)$scope['department_id'];
        }
        $sql .= " ORDER BY dept_name ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getApprovedRequisitions() {
        $sql = "SELECT r.requisition_id, r.requisition_number, r.requested_date,
                    d.dept_name, s.store_name
                FROM requisitions r
                JOIN departments d ON r.department_id = d.dept_id
                JOIN stores s ON r.store_id = s.store_id
                WHERE r.status = 'approved'
                ORDER BY r.requested_date DESC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getRequisitionForIssue($requisitionId) {
        $sql = "SELECT r.*, d.dept_name, s.store_name, u.full_name AS requested_by_name
                FROM requisitions r
                JOIN departments d ON r.department_id = d.dept_id
                JOIN stores s ON r.store_id = s.store_id
                JOIN users u ON r.requested_by = u.user_id
                WHERE r.requisition_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $requisitionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRequisitionItemsForIssue($requisitionId) {
        $sql = "SELECT ri.req_item_id, ri.product_id, ri.quantity_requested,
                    COALESCE(ri.quantity_approved, ri.quantity_requested) AS quantity_approved,
                    COALESCE(ri.quantity_issued, 0) AS quantity_issued,
                    p.product_name, p.product_code, p.unit_of_measure,
                    COALESCE(st.quantity_on_hand, 0) AS available_stock,
                    COALESCE((
                        SELECT gi.unit_price
                        FROM grn_items gi
                        WHERE gi.product_id = ri.product_id
                        ORDER BY gi.grn_item_id DESC
                        LIMIT 1
                    ), 0) AS unit_price
                FROM requisition_items ri
                JOIN requisitions r ON r.requisition_id = ri.requisition_id
                JOIN products p ON p.product_id = ri.product_id
                LEFT JOIN stock st ON st.product_id = ri.product_id AND st.store_id = r.store_id
                WHERE ri.requisition_id = ?
                ORDER BY ri.req_item_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $requisitionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStoreProductsForDirectIssue($storeId) {
        $sql = "SELECT p.product_id, p.product_name, p.product_code, p.unit_of_measure,
                    st.quantity_on_hand AS available_stock,
                    COALESCE((
                        SELECT gi.unit_price
                        FROM grn_items gi
                        WHERE gi.product_id = p.product_id
                        ORDER BY gi.grn_item_id DESC
                        LIMIT 1
                    ), 0) AS unit_price
                FROM stock st
                JOIN products p ON p.product_id = st.product_id
                WHERE st.store_id = ?
                  AND p.status = 'active'
                  AND st.quantity_on_hand > 0
                ORDER BY p.product_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getIssueById($issueId, $userId = null) {
        $sql = "SELECT si.*, d.dept_name, s.store_name, u.full_name AS issued_by_name,
                    ur.full_name AS received_by_name, r.requisition_number
                FROM stock_issues si
                JOIN departments d ON si.department_id = d.dept_id
                JOIN stores s ON si.store_id = s.store_id
                JOIN users u ON si.issued_by = u.user_id
                LEFT JOIN users ur ON si.received_by = ur.user_id
                LEFT JOIN requisitions r ON si.requisition_id = r.requisition_id
                WHERE si.issue_id = ?";

        $scope = $this->getIssueVisibilityScope($userId);
        if (!empty($scope['deny_all'])) {
            return null;
        }
        if (!empty($scope['department_id'])) {
            $sql .= " AND si.department_id = " . (int)$scope['department_id'];
        } elseif (!empty($scope['store_id'])) {
            $sql .= " AND si.store_id = " . (int)$scope['store_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $issueId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getIssueItems($issueId) {
        $sql = "SELECT sii.*, p.product_name, p.product_code, p.unit_of_measure
                FROM stock_issue_items sii
                JOIN products p ON p.product_id = sii.product_id
                WHERE sii.issue_id = ?
                ORDER BY sii.issue_item_id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $issueId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createIssue($requisitionId, $items, $notes, $issuedBy) {
        if (!can('stock-issues.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $requisition = $this->getRequisitionForIssue((int)$requisitionId);
        if (!$requisition) {
            return ['success' => false, 'message' => 'Requisition not found'];
        }

        if ($requisition['status'] !== 'approved') {
            return ['success' => false, 'message' => 'Only approved requisitions can be issued'];
        }

        $normalized = $this->normalizeIssueItems($items);
        if (empty($normalized)) {
            return ['success' => false, 'message' => 'Enter at least one issue quantity'];
        }

        $lineMap = [];
        foreach ($this->getRequisitionItemsForIssue((int)$requisitionId) as $line) {
            $lineMap[(int)$line['req_item_id']] = $line;
        }

        foreach ($normalized as $row) {
            $reqItemId = (int)$row['req_item_id'];
            if (!isset($lineMap[$reqItemId])) {
                return ['success' => false, 'message' => 'Invalid requisition line selected'];
            }

            $line = $lineMap[$reqItemId];
            if ((int)$line['product_id'] !== (int)$row['product_id']) {
                return ['success' => false, 'message' => 'Product mismatch on line validation'];
            }

            $remaining = max(0, (int)$line['quantity_approved'] - (int)$line['quantity_issued']);
            if ((int)$row['quantity_issued'] > $remaining) {
                return ['success' => false, 'message' => 'Issued quantity exceeds approved remaining quantity'];
            }

            if ((int)$row['quantity_issued'] > (int)$line['available_stock']) {
                return ['success' => false, 'message' => 'Insufficient stock for one or more items'];
            }
        }

        $this->db->begin_transaction();

        try {
            $issueNumber = $this->generateIssueNumber();
            $headerSql = "INSERT INTO stock_issues (issue_number, requisition_id, store_id, department_id, issued_by, notes, status)
                          VALUES (?, ?, ?, ?, ?, ?, 'issued')";
            $headerStmt = $this->db->prepare($headerSql);
            $headerStmt->bind_param(
                'siiiis',
                $issueNumber,
                $requisitionId,
                $requisition['store_id'],
                $requisition['department_id'],
                $issuedBy,
                $notes
            );
            $headerStmt->execute();
            $issueId = $this->db->insert_id;

            $destinationStoreId = $this->getDepartmentStoreId((int)$requisition['department_id']);

            foreach ($normalized as $row) {
                $reqItemId = (int)$row['req_item_id'];
                $line = $lineMap[$reqItemId];
                $productId = (int)$row['product_id'];
                $qty = (int)$row['quantity_issued'];
                $unitPrice = isset($line['unit_price']) ? (float)$line['unit_price'] : 0.0;
                $remarks = trim($row['remarks'] ?? '');

                $itemSql = "INSERT INTO stock_issue_items (issue_id, product_id, quantity_issued, unit_price, remarks)
                            VALUES (?, ?, ?, ?, ?)";
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->bind_param('iiids', $issueId, $productId, $qty, $unitPrice, $remarks);
                $itemStmt->execute();

                $stockSql = "UPDATE stock SET quantity_on_hand = quantity_on_hand - ?, updated_at = NOW()
                             WHERE product_id = ? AND store_id = ?";
                $stockStmt = $this->db->prepare($stockSql);
                $stockStmt->bind_param('iii', $qty, $productId, $requisition['store_id']);
                $stockStmt->execute();

                $reqLineSql = "UPDATE requisition_items
                               SET quantity_issued = COALESCE(quantity_issued, 0) + ?
                               WHERE req_item_id = ?";
                $reqLineStmt = $this->db->prepare($reqLineSql);
                $reqLineStmt->bind_param('ii', $qty, $reqItemId);
                $reqLineStmt->execute();

                $txnType = 'issue';
                $referenceType = 'ISSUE';
                $txnSql = "INSERT INTO stock_transactions (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, unit_price, total_value, performed_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $txnStmt = $this->db->prepare($txnSql);
                $negativeQty = -1 * $qty;
                $totalValue = (float)$qty * (float)$unitPrice;
                $txnStmt->bind_param(
                    'iissiiddi',
                    $productId,
                    $requisition['store_id'],
                    $txnType,
                    $referenceType,
                    $issueId,
                    $negativeQty,
                    $unitPrice,
                    $totalValue,
                    $issuedBy
                );
                $txnStmt->execute();

                if ($destinationStoreId > 0 && $destinationStoreId !== (int)$requisition['store_id']) {
                    $this->increaseStoreStock($productId, $destinationStoreId, $qty);

                    $receiptType = 'receipt';
                    $receiptReferenceType = 'ISSUE_RECEIPT';
                    $receiptSql = "INSERT INTO stock_transactions (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, unit_price, total_value, performed_by)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $receiptStmt = $this->db->prepare($receiptSql);
                    $receiptQty = (int)$qty;
                    $receiptTotal = (float)$qty * (float)$unitPrice;
                    $receiptStmt->bind_param(
                        'iissiiddi',
                        $productId,
                        $destinationStoreId,
                        $receiptType,
                        $receiptReferenceType,
                        $issueId,
                        $receiptQty,
                        $unitPrice,
                        $receiptTotal,
                        $issuedBy
                    );
                    $receiptStmt->execute();
                }
            }

            $reqStatusSql = "UPDATE requisitions SET status = 'issued', updated_at = NOW() WHERE requisition_id = ?";
            $reqStatusStmt = $this->db->prepare($reqStatusSql);
            $reqStatusStmt->bind_param('i', $requisitionId);
            $reqStatusStmt->execute();

            $this->logAudit('Create', 'stock_issue', $issueId, '', json_encode([
                'issue_number' => $issueNumber,
                'requisition_id' => $requisitionId,
                'line_count' => count($normalized)
            ]));

            $this->db->commit();
            return ['success' => true, 'message' => 'Stock issued successfully', 'issue_id' => $issueId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to issue stock: ' . $e->getMessage()];
        }
    }

    public function createDirectIssue($storeId, $departmentId, $items, $notes, $issuedBy) {
        if (!can('stock-issues.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $store = $this->getActiveStoreById((int)$storeId);
        if (!$store) {
            return ['success' => false, 'message' => 'Invalid or inactive store selected'];
        }

        $department = $this->getActiveDepartmentById((int)$departmentId);
        if (!$department) {
            return ['success' => false, 'message' => 'Invalid or inactive department selected'];
        }

        $normalized = $this->normalizeDirectIssueItems($items);
        if (empty($normalized)) {
            return ['success' => false, 'message' => 'Enter at least one issue quantity'];
        }

        $stockMap = [];
        foreach ($this->getStoreProductsForDirectIssue((int)$storeId) as $line) {
            $stockMap[(int)$line['product_id']] = $line;
        }

        foreach ($normalized as $row) {
            $productId = (int)$row['product_id'];
            if (!isset($stockMap[$productId])) {
                return ['success' => false, 'message' => 'Invalid product selected for this store'];
            }

            if ((int)$row['quantity_issued'] > (int)$stockMap[$productId]['available_stock']) {
                return ['success' => false, 'message' => 'Insufficient stock for one or more items'];
            }
        }

        $this->db->begin_transaction();

        try {
            $issueNumber = $this->generateIssueNumber();
            $headerSql = "INSERT INTO stock_issues (issue_number, requisition_id, store_id, department_id, issued_by, notes, status)
                          VALUES (?, NULL, ?, ?, ?, ?, 'issued')";
            $headerStmt = $this->db->prepare($headerSql);
            $headerStmt->bind_param('siiis', $issueNumber, $storeId, $departmentId, $issuedBy, $notes);
            $headerStmt->execute();
            $issueId = $this->db->insert_id;

            $destinationStoreId = $this->getDepartmentStoreId((int)$departmentId);

            foreach ($normalized as $row) {
                $productId = (int)$row['product_id'];
                $qty = (int)$row['quantity_issued'];
                $unitPrice = isset($stockMap[$productId]['unit_price']) ? (float)$stockMap[$productId]['unit_price'] : 0.0;
                $remarks = trim($row['remarks'] ?? '');

                $itemSql = "INSERT INTO stock_issue_items (issue_id, product_id, quantity_issued, unit_price, remarks)
                            VALUES (?, ?, ?, ?, ?)";
                $itemStmt = $this->db->prepare($itemSql);
                $itemStmt->bind_param('iiids', $issueId, $productId, $qty, $unitPrice, $remarks);
                $itemStmt->execute();

                $stockSql = "UPDATE stock SET quantity_on_hand = quantity_on_hand - ?, updated_at = NOW()
                             WHERE product_id = ? AND store_id = ?";
                $stockStmt = $this->db->prepare($stockSql);
                $stockStmt->bind_param('iii', $qty, $productId, $storeId);
                $stockStmt->execute();

                $txnType = 'issue';
                $referenceType = 'DIRECT_ISSUE';
                $txnSql = "INSERT INTO stock_transactions (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, unit_price, total_value, performed_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $txnStmt = $this->db->prepare($txnSql);
                $negativeQty = -1 * $qty;
                $totalValue = (float)$qty * (float)$unitPrice;
                $txnStmt->bind_param(
                    'iissiiddi',
                    $productId,
                    $storeId,
                    $txnType,
                    $referenceType,
                    $issueId,
                    $negativeQty,
                    $unitPrice,
                    $totalValue,
                    $issuedBy
                );
                $txnStmt->execute();

                if ($destinationStoreId > 0 && $destinationStoreId !== (int)$storeId) {
                    $this->increaseStoreStock($productId, $destinationStoreId, $qty);

                    $receiptType = 'receipt';
                    $receiptReferenceType = 'DIRECT_ISSUE_RECEIPT';
                    $receiptSql = "INSERT INTO stock_transactions (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, unit_price, total_value, performed_by)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $receiptStmt = $this->db->prepare($receiptSql);
                    $receiptQty = (int)$qty;
                    $receiptTotal = (float)$qty * (float)$unitPrice;
                    $receiptStmt->bind_param(
                        'iissiiddi',
                        $productId,
                        $destinationStoreId,
                        $receiptType,
                        $receiptReferenceType,
                        $issueId,
                        $receiptQty,
                        $unitPrice,
                        $receiptTotal,
                        $issuedBy
                    );
                    $receiptStmt->execute();
                }
            }

            $this->logAudit('Create', 'stock_issue', $issueId, '', json_encode([
                'issue_number' => $issueNumber,
                'direct_issue' => true,
                'store_id' => (int)$storeId,
                'department_id' => (int)$departmentId,
                'line_count' => count($normalized)
            ]));

            $this->db->commit();
            return ['success' => true, 'message' => 'Stock issued successfully', 'issue_id' => $issueId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to issue stock: ' . $e->getMessage()];
        }
    }

    private function generateIssueNumber() {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) AS c FROM stock_issues WHERE DATE(issue_date) = CURDATE()";
        $count = (int)$this->db->query($sql)->fetch_assoc()['c'] + 1;
        return 'ISS-' . $date . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }

    private function normalizeIssueItems($items) {
        $reqIds = $items['req_item_id'] ?? [];
        $productIds = $items['product_id'] ?? [];
        $quantities = $items['quantity_issued'] ?? [];
        $remarks = $items['remarks'] ?? [];

        $rows = [];
        $count = count($reqIds);
        for ($i = 0; $i < $count; $i++) {
            $reqItemId = $reqIds[$i] ?? '';
            $productId = $productIds[$i] ?? '';
            $qty = $quantities[$i] ?? '';

            if (!ctype_digit((string)$reqItemId) || !ctype_digit((string)$productId)) {
                continue;
            }
            if (!is_numeric($qty) || (int)$qty <= 0) {
                continue;
            }

            $rows[] = [
                'req_item_id' => (int)$reqItemId,
                'product_id' => (int)$productId,
                'quantity_issued' => (int)$qty,
                'remarks' => trim($remarks[$i] ?? '')
            ];
        }

        return $rows;
    }

    private function normalizeDirectIssueItems($items) {
        $productIds = $items['product_id'] ?? [];
        $quantities = $items['quantity_issued'] ?? [];
        $remarks = $items['remarks'] ?? [];

        $rows = [];
        $count = count($productIds);
        for ($i = 0; $i < $count; $i++) {
            $productId = $productIds[$i] ?? '';
            $qty = $quantities[$i] ?? '';

            if (!ctype_digit((string)$productId)) {
                continue;
            }
            if (!is_numeric($qty) || (int)$qty <= 0) {
                continue;
            }

            $rows[] = [
                'product_id' => (int)$productId,
                'quantity_issued' => (int)$qty,
                'remarks' => trim($remarks[$i] ?? '')
            ];
        }

        return $rows;
    }

    private function getActiveStoreById($storeId) {
        $sql = "SELECT store_id, store_name FROM stores WHERE store_id = ? AND status = 'active' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getActiveDepartmentById($departmentId) {
        $sql = "SELECT dept_id, dept_name FROM departments WHERE dept_id = ? AND status = 'active' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getDepartmentStoreId($departmentId) {
        $sql = "SELECT s.store_id
                FROM departments d
                JOIN stores s ON s.status = 'active'
                    AND (
                        s.store_code = CONCAT(d.dept_code, '001')
                        OR s.store_code LIKE CONCAT(d.dept_code, '%')
                        OR s.store_name LIKE CONCAT(d.dept_name, '%')
                    )
                WHERE d.dept_id = ?
                ORDER BY (s.store_code = CONCAT(d.dept_code, '001')) DESC, s.store_id ASC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ? (int)$row['store_id'] : 0;
    }

    private function increaseStoreStock($productId, $storeId, $qty) {
        $updateSql = "UPDATE stock
                      SET quantity_on_hand = quantity_on_hand + ?, updated_at = NOW()
                      WHERE product_id = ? AND store_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bind_param('iii', $qty, $productId, $storeId);
        $updateStmt->execute();

        if ($updateStmt->affected_rows > 0) {
            return;
        }

        $reorderLevel = 0;
        $reorderSql = "SELECT reorder_level FROM products WHERE product_id = ? LIMIT 1";
        $reorderStmt = $this->db->prepare($reorderSql);
        $reorderStmt->bind_param('i', $productId);
        $reorderStmt->execute();
        $reorderRow = $reorderStmt->get_result()->fetch_assoc();
        if ($reorderRow) {
            $reorderLevel = (int)$reorderRow['reorder_level'];
        }

        $insertSql = "INSERT INTO stock (product_id, store_id, quantity_on_hand, reorder_level)
                      VALUES (?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertSql);
        $insertStmt->bind_param('iiii', $productId, $storeId, $qty, $reorderLevel);
        $insertStmt->execute();
    }

    private function getIssueVisibilityScope($userId) {
        $scope = [
            'department_id' => null,
            'store_id' => null,
            'deny_all' => false
        ];

        if (empty($userId) || !ctype_digit((string)$userId)) {
            return $scope;
        }

        $sql = "SELECT r.role_name,
                       d.dept_id,
                       d.dept_code,
                       s.store_id
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
            $scope['deny_all'] = true;
            return $scope;
        }

        $roleName = (string)($row['role_name'] ?? '');
        if (in_array($roleName, ['Admin', 'Manager', 'Storekeeper'], true)) {
            return $scope;
        }

        $deptId = isset($row['dept_id']) ? (int)$row['dept_id'] : 0;
        $storeId = isset($row['store_id']) ? (int)$row['store_id'] : 0;

        if ($deptId <= 0) {
            $scope['deny_all'] = true;
            return $scope;
        }

        $scope['department_id'] = $deptId;
        if ($storeId > 0) {
            $scope['store_id'] = $storeId;
        }

        return $scope;
    }

    /**
     * Consumption Methods
     */

    /**
     * Get issue items for consumption logging
     */
    public function getIssueItemsForConsumption($issueId, $userId = null) {
        $sql = "SELECT sii.*, p.product_name, p.product_code, p.unit_of_measure,
                        si.department_id, d.dept_name
                FROM stock_issue_items sii
                JOIN products p ON p.product_id = sii.product_id
                JOIN stock_issues si ON sii.issue_id = si.issue_id
                JOIN departments d ON si.department_id = d.dept_id
                WHERE sii.issue_id = ?
                ORDER BY sii.issue_item_id ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getIssueItemsForConsumption: " . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $issueId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get consumable issues for a department
     */
    public function getConsumableIssuesForDepartment($departmentId, $includeCompleted = false) {
        $sql = "SELECT si.*, d.dept_name, s.store_name, u.full_name AS issued_by_name,
                        COUNT(sii.issue_item_id) as total_items,
                        SUM(sii.quantity_issued) as total_quantity_issued,
                        SUM(sii.quantity_consumed) as total_quantity_consumed,
                        SUM(sii.quantity_returned) as total_quantity_returned
                FROM stock_issues si
                JOIN departments d ON si.department_id = d.dept_id
                JOIN stores s ON si.store_id = s.store_id
                JOIN users u ON si.issued_by = u.user_id
                LEFT JOIN stock_issue_items sii ON si.issue_id = sii.issue_id
                WHERE si.department_id = ? AND si.status IN ('issued', 'received')";
        
        if (!$includeCompleted) {
            $sql .= " AND si.issue_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
        
        $sql .= " GROUP BY si.issue_id
                 ORDER BY si.issue_date DESC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getConsumableIssuesForDepartment: " . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get consumable issues across all departments
     */
    public function getConsumableIssues($includeCompleted = false) {
        $sql = "SELECT si.*, d.dept_name, s.store_name, u.full_name AS issued_by_name,
                        COUNT(sii.issue_item_id) as total_items,
                        SUM(sii.quantity_issued) as total_quantity_issued,
                        SUM(sii.quantity_consumed) as total_quantity_consumed,
                        SUM(sii.quantity_returned) as total_quantity_returned
                FROM stock_issues si
                JOIN departments d ON si.department_id = d.dept_id
                JOIN stores s ON si.store_id = s.store_id
                JOIN users u ON si.issued_by = u.user_id
                LEFT JOIN stock_issue_items sii ON si.issue_id = sii.issue_id
                WHERE si.status IN ('issued', 'received')";

        if (!$includeCompleted) {
            $sql .= " AND si.issue_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $sql .= " GROUP BY si.issue_id
                 ORDER BY si.issue_date DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getConsumableIssues: " . $this->db->error);
            return [];
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get consumable issues for current user's visibility scope.
     */
    public function getConsumableIssuesForUser($userId, $includeCompleted = true) {
        $scope = $this->getIssueVisibilityScope($userId);
        if (!empty($scope['deny_all'])) {
            return [];
        }

        $sql = "SELECT si.*, d.dept_name, s.store_name, u.full_name AS issued_by_name,
                        COUNT(sii.issue_item_id) as total_items,
                        COALESCE(SUM(sii.quantity_issued), 0) as total_quantity_issued,
                        COALESCE(SUM(sii.quantity_consumed), 0) as total_quantity_consumed,
                        COALESCE(SUM(sii.quantity_returned), 0) as total_quantity_returned
                FROM stock_issues si
                JOIN departments d ON si.department_id = d.dept_id
                JOIN stores s ON si.store_id = s.store_id
                JOIN users u ON si.issued_by = u.user_id
                LEFT JOIN stock_issue_items sii ON si.issue_id = sii.issue_id
                WHERE si.status IN ('issued', 'received')";

        $types = '';
        $params = [];

        if (!empty($scope['department_id'])) {
            $sql .= " AND si.department_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['department_id'];
        } elseif (!empty($scope['store_id'])) {
            $sql .= " AND si.store_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['store_id'];
        }
        if (!$includeCompleted) {
            $sql .= " AND si.issue_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $sql .= " GROUP BY si.issue_id
                  HAVING (total_quantity_issued - total_quantity_consumed - total_quantity_returned) > 0
                  ORDER BY si.issue_date DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getConsumableIssuesForUser: " . $this->db->error);
            return [];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get all pending issue items needing consumption logging
     */
    public function getPendingConsumptionItems($departmentId) {
        $sql = "SELECT sii.*, p.product_name, p.product_code, p.unit_of_measure,
                        si.issue_number, si.issue_date, d.dept_name,
                        (sii.quantity_issued - sii.quantity_consumed - sii.quantity_returned) as quantity_pending
                FROM stock_issue_items sii
                JOIN stock_issues si ON sii.issue_id = si.issue_id
                JOIN departments d ON si.department_id = d.dept_id
                JOIN products p ON sii.product_id = p.product_id
                WHERE si.department_id = ? 
                  AND si.status IN ('issued', 'received')
                  AND (sii.quantity_consumed + sii.quantity_returned) < sii.quantity_issued
                ORDER BY si.issue_date DESC, sii.issue_item_id ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getPendingConsumptionItems: " . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get all pending issue items needing consumption logging across departments
     */
    public function getPendingConsumptionItemsAll() {
        $sql = "SELECT sii.*, p.product_name, p.product_code, p.unit_of_measure,
                        si.issue_number, si.issue_date, d.dept_name,
                        (sii.quantity_issued - sii.quantity_consumed - sii.quantity_returned) as quantity_pending
                FROM stock_issue_items sii
                JOIN stock_issues si ON sii.issue_id = si.issue_id
                JOIN departments d ON si.department_id = d.dept_id
                JOIN products p ON sii.product_id = p.product_id
                WHERE si.status IN ('issued', 'received')
                  AND (sii.quantity_consumed + sii.quantity_returned) < sii.quantity_issued
                ORDER BY si.issue_date DESC, sii.issue_item_id ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getPendingConsumptionItemsAll: " . $this->db->error);
            return [];
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get pending issue items for current user's visibility scope.
     */
    public function getPendingConsumptionItemsForUser($userId) {
        $scope = $this->getIssueVisibilityScope($userId);
        if (!empty($scope['deny_all'])) {
            return [];
        }

        $sql = "SELECT sii.*, p.product_name, p.product_code, p.unit_of_measure,
                        si.issue_number, si.issue_date, si.department_id, si.store_id,
                        d.dept_name, s.store_name,
                        (sii.quantity_issued - sii.quantity_consumed - sii.quantity_returned) as quantity_pending
                FROM stock_issue_items sii
                JOIN stock_issues si ON sii.issue_id = si.issue_id
                JOIN departments d ON si.department_id = d.dept_id
                JOIN stores s ON si.store_id = s.store_id
                JOIN products p ON sii.product_id = p.product_id
                WHERE si.status IN ('issued', 'received')
                  AND (sii.quantity_consumed + sii.quantity_returned) < sii.quantity_issued";

        $types = '';
        $params = [];

        if (!empty($scope['department_id'])) {
            $sql .= " AND si.department_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['department_id'];
        }
        if (!empty($scope['store_id'])) {
            $sql .= " AND si.store_id = ?";
            $types .= 'i';
            $params[] = (int)$scope['store_id'];
        }

        $sql .= " ORDER BY si.issue_date DESC, sii.issue_item_id ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getPendingConsumptionItemsForUser: " . $this->db->error);
            return [];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get consumption summary for a department
     */
    public function getConsumptionSummary($departmentId, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.product_code,
                    p.unit_of_measure,
                    SUM(sii.quantity_issued) as total_issued,
                    SUM(sii.quantity_consumed) as total_consumed,
                    SUM(sii.quantity_returned) as total_returned,
                    (SUM(sii.quantity_issued) - SUM(sii.quantity_consumed) - SUM(sii.quantity_returned)) as total_unaccounted
                FROM stock_issue_items sii
                JOIN stock_issues si ON sii.issue_id = si.issue_id
                JOIN products p ON sii.product_id = p.product_id
                WHERE si.department_id = ?";
        
        $types = 'i';
        $params = [$departmentId];
        
        if ($startDate) {
            $sql .= " AND si.issue_date >= ?";
            $types .= 's';
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND si.issue_date <= ?";
            $types .= 's';
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY p.product_id
                 ORDER BY p.product_name ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getConsumptionSummary: " . $this->db->error);
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

