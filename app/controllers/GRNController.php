<?php
/**
 * GRN Controller
 */

class GRNController extends Controller {
    private $grnModel;

    public function __construct() {
        parent::__construct();
        $this->grnModel = new GRN();
    }

    public function getStores($userId = null) {
        $storeIds = $this->getVisibleStoreIds($userId);
        $sql = "SELECT store_id, store_name FROM stores WHERE status = 'active'";
        if (is_array($storeIds) && !empty($storeIds)) {
            $sql .= " AND store_id IN (" . implode(',', array_map('intval', $storeIds)) . ")";
        } elseif ($storeIds === []) {
            return [];
        }

        $sql .= " ORDER BY store_name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getSuppliers() {
        $sql = "SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'active' ORDER BY supplier_name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getProducts() {
        $sql = "SELECT product_id, product_name, product_code, unit_of_measure FROM products WHERE status = 'active' ORDER BY product_name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getGRNs($filters = [], $userId = null) {
        $sql = "SELECT g.*, s.store_name, sup.supplier_name, u.full_name AS received_by_name
                FROM grn g
                JOIN stores s ON g.store_id = s.store_id
                JOIN suppliers sup ON g.supplier_id = sup.supplier_id
                JOIN users u ON g.received_by = u.user_id
                WHERE 1 = 1";

        $types = '';
        $params = [];
        $storeIds = $this->getVisibleStoreIds($userId);
        if ($storeIds === []) {
            return [];
        }

        if (is_array($storeIds) && !empty($storeIds)) {
            $sql .= " AND g.store_id IN (" . implode(',', array_map('intval', $storeIds)) . ")";
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (g.grn_number LIKE ? OR sup.supplier_name LIKE ? OR s.store_name LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['store_id']) && ctype_digit((string)$filters['store_id'])) {
            $sql .= " AND g.store_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['store_id'];
        }

        if (!empty($filters['supplier_id']) && ctype_digit((string)$filters['supplier_id'])) {
            $sql .= " AND g.supplier_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['supplier_id'];
        }

        $status = $filters['status'] ?? 'all';
        if (in_array($status, ['draft', 'received', 'verified'], true)) {
            $sql .= " AND g.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " ORDER BY g.receipt_date DESC, g.grn_id DESC";

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
            'draft' => 0,
            'received' => 0,
            'verified' => 0,
            'total_value' => 0.0
        ];

        $storeIds = $this->getVisibleStoreIds($userId);
        if ($storeIds === []) {
            return $stats;
        }

        $storeFilter = '';
        if (is_array($storeIds) && !empty($storeIds)) {
            $storeFilter = ' WHERE store_id IN (' . implode(',', array_map('intval', $storeIds)) . ')';
        }

        $result = $this->db->query("SELECT status, COUNT(*) AS c FROM grn" . $storeFilter . " GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            if (array_key_exists($row['status'], $stats)) {
                $stats[$row['status']] = (int)$row['c'];
            }
        }

        $sql = "SELECT COALESCE(SUM(gi.quantity_received * gi.unit_price), 0) AS total_value
            FROM grn_items gi
            JOIN grn g ON g.grn_id = gi.grn_id
            WHERE g.status IN ('received', 'verified')";
        if (is_array($storeIds) && !empty($storeIds)) {
            $sql .= " AND g.store_id IN (" . implode(',', array_map('intval', $storeIds)) . ")";
        }
        $stats['total_value'] = (float)$this->db->query($sql)->fetch_assoc()['total_value'];

        return $stats;
    }

    public function getById($grnId) {
        return $this->grnModel->getWithDetails((int)$grnId);
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

    public function getItems($grnId) {
        return $this->grnModel->getItems((int)$grnId);
    }

    public function create($data, $items, $userId) {
        if (!can('grn.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $validationError = $this->validateHeader($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        $validItems = $this->normalizeItems($items);
        if (empty($validItems)) {
            return ['success' => false, 'message' => 'Add at least one GRN item'];
        }

        $this->db->begin_transaction();

        try {
            $grnNumber = $this->grnModel->generateGRNNumber();
            $totalCost = 0.0;
            foreach ($validItems as $item) {
                $totalCost += ((float)$item['quantity_received'] * (float)$item['unit_price']);
            }

            $header = [
                'grn_number' => $grnNumber,
                'supplier_id' => (int)$data['supplier_id'],
                'store_id' => (int)$data['store_id'],
                'received_by' => $userId,
                'receipt_date' => $data['receipt_date'],
                'receipt_time' => $data['receipt_time'] ?? null,
                'delivery_note_ref' => trim($data['delivery_note_ref'] ?? ''),
                'invoice_reference' => trim($data['invoice_reference'] ?? ''),
                'total_cost' => $totalCost,
                'status' => 'draft',
                'notes' => trim($data['notes'] ?? '')
            ];

            $inserted = $this->grnModel->insert($header);
            if (!$inserted) {
                throw new Exception('Unable to create GRN header');
            }

            $grnId = $this->db->insert_id;
            foreach ($validItems as $item) {
                $added = $this->grnModel->addItem(
                    $grnId,
                    (int)$item['product_id'],
                    (int)$item['quantity_expected'],
                    (int)$item['quantity_received'],
                    (float)$item['unit_price'],
                    $item['batch_number'],
                    $item['expiry_date']
                );

                if (!$added) {
                    throw new Exception('Unable to add GRN item');
                }
            }

            $this->logAudit('Create', 'grn', $grnId, '', json_encode($header));
            $this->db->commit();

            return ['success' => true, 'message' => 'GRN created successfully', 'grn_id' => $grnId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verify($grnId) {
        if (!can('grn.verify')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $grn = $this->getById($grnId);
        if (!$grn) {
            return ['success' => false, 'message' => 'GRN not found'];
        }

        if ($grn['status'] === 'verified') {
            return ['success' => false, 'message' => 'GRN is already verified'];
        }

        $verified = $this->grnModel->verify((int)$grnId, (int)$this->currentUser['user_id']);
        if (!$verified) {
            return ['success' => false, 'message' => 'Failed to verify GRN'];
        }

        $this->logAudit('Verify', 'grn', (int)$grnId, $grn['status'], 'verified');
        return ['success' => true, 'message' => 'GRN verified and stock updated'];
    }

    private function validateHeader($data) {
        if (empty($data['supplier_id']) || !ctype_digit((string)$data['supplier_id'])) {
            return 'Please select a supplier';
        }
        if (empty($data['store_id']) || !ctype_digit((string)$data['store_id'])) {
            return 'Please select a store';
        }
        if (empty($data['receipt_date'])) {
            return 'Receipt date is required';
        }
        if (empty(trim($data['delivery_note_ref'] ?? ''))) {
            return 'Delivery note reference is required';
        }
        if (empty(trim($data['invoice_reference'] ?? ''))) {
            return 'Invoice reference is required';
        }
        return null;
    }

    private function normalizeItems($items) {
        $products = $items['product_id'] ?? [];
        $expected = $items['quantity_expected'] ?? [];
        $received = $items['quantity_received'] ?? [];
        $prices = $items['unit_price'] ?? [];
        $batches = $items['batch_number'] ?? [];
        $expiries = $items['expiry_date'] ?? [];

        $rows = [];
        $count = count($products);
        for ($i = 0; $i < $count; $i++) {
            $productId = $products[$i] ?? '';
            $qtyExpected = $expected[$i] ?? '';
            $qtyReceived = $received[$i] ?? '';
            $price = $prices[$i] ?? '';

            if (!ctype_digit((string)$productId)) {
                continue;
            }
            if (!is_numeric($qtyExpected) || (int)$qtyExpected < 1) {
                continue;
            }
            if (!is_numeric($qtyReceived) || (int)$qtyReceived < 0) {
                continue;
            }
            if (!is_numeric($price) || (float)$price < 0) {
                continue;
            }

            $rows[] = [
                'product_id' => (int)$productId,
                'quantity_expected' => (int)$qtyExpected,
                'quantity_received' => (int)$qtyReceived,
                'unit_price' => (float)$price,
                'batch_number' => trim($batches[$i] ?? ''),
                'expiry_date' => trim($expiries[$i] ?? '') ?: null
            ];
        }

        return $rows;
    }
}
