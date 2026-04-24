<?php
/**
 * Requisition Controller
 */

class RequisitionController extends Controller {
    private $requisitionModel;

    public function __construct() {
        parent::__construct();
        $this->requisitionModel = new Requisition();
    }

    public function getDepartmentForRole($roleName) {
        $roleName = trim((string)$roleName);
        if ($roleName === '') {
            return null;
        }

        $sql = "SELECT dept_id, dept_name, dept_code
                FROM departments
                WHERE status = 'active' AND dept_name = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $roleName);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

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

    public function getDepartments($userId = null) {
        $sql = "SELECT dept_id, dept_name FROM departments WHERE status = 'active'";
        if (!empty($userId) && ctype_digit((string)$userId)) {
            $department = $this->getDepartmentForUser((int)$userId);
            if (!$department) {
                return [];
            }
            $sql .= " AND dept_id = " . (int)$department['dept_id'];
        }
        $sql .= " ORDER BY dept_name ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getProducts($storeId = null) {
        $sql = "SELECT DISTINCT p.product_id, p.product_name, p.product_code, p.unit_of_measure,
                    COALESCE(s.quantity_on_hand, 0) AS quantity_on_hand
                FROM products p
                LEFT JOIN stock s ON p.product_id = s.product_id";

        $types = '';
        $params = [];

        if ($storeId && ctype_digit((string)$storeId)) {
            $sql .= " AND s.store_id = ?";
            $types .= 'i';
            $params[] = (int)$storeId;
        }

        $sql .= " WHERE p.status = 'active' ORDER BY p.product_name ASC";

        if ($types !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getRequisitions($filters = [], $userId = null) {
        $sql = "SELECT r.*, d.dept_name, s.store_name, u.full_name AS requested_by_name,
                    ua.full_name AS approved_by_name
                FROM requisitions r
                JOIN departments d ON r.department_id = d.dept_id
                JOIN stores s ON r.store_id = s.store_id
                JOIN users u ON r.requested_by = u.user_id
                LEFT JOIN users ua ON r.approved_by = ua.user_id
                WHERE 1 = 1";

        $types = '';
        $params = [];
        $storeIds = $this->getVisibleStoreIds($userId);
        if ($storeIds === []) {
            return [];
        }

        if (is_array($storeIds) && !empty($storeIds)) {
            $sql .= " AND r.store_id IN (" . implode(',', array_map('intval', $storeIds)) . ")";
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (r.requisition_number LIKE ? OR d.dept_name LIKE ? OR s.store_name LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['department_id']) && ctype_digit((string)$filters['department_id'])) {
            $sql .= " AND r.department_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['department_id'];
        }

        if (!empty($filters['store_id']) && ctype_digit((string)$filters['store_id'])) {
            $sql .= " AND r.store_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['store_id'];
        }

        $status = $filters['status'] ?? 'all';
        if (in_array($status, ['draft', 'pending', 'approved', 'rejected', 'issued'], true)) {
            $sql .= " AND r.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " ORDER BY r.requested_date DESC, r.requisition_id DESC";

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
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'issued' => 0
        ];

        $storeIds = $this->getVisibleStoreIds($userId);
        if ($storeIds === []) {
            return $stats;
        }

        $storeFilter = '';
        if (is_array($storeIds) && !empty($storeIds)) {
            $storeFilter = ' WHERE store_id IN (' . implode(',', array_map('intval', $storeIds)) . ')';
        }

        $result = $this->db->query("SELECT status, COUNT(*) AS c FROM requisitions" . $storeFilter . " GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            if (array_key_exists($row['status'], $stats)) {
                $stats[$row['status']] = (int)$row['c'];
            }
        }

        return $stats;
    }

    public function getById($id) {
        return $this->requisitionModel->getWithDetails((int)$id);
    }

    public function getItems($id) {
        return $this->requisitionModel->getItems((int)$id);
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

    public function create($data, $items, $userId) {
        if (!can('requisition.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $department = $this->getDepartmentForUser((int)$userId);
        if (!$department) {
            return ['success' => false, 'message' => 'Your account is not mapped to a department. Ask an administrator to assign your role to a department first.'];
        }

        $validationError = $this->validateHeader($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        $validItems = $this->normalizeItems($items);
        if (empty($validItems)) {
            return ['success' => false, 'message' => 'Add at least one valid requisition item'];
        }

        $this->db->begin_transaction();

        try {
            $header = [
                'requisition_number' => $this->requisitionModel->generateRequisitionNumber(),
                'department_id' => (int)$department['dept_id'],
                'store_id' => (int)$data['store_id'],
                'requested_by' => (int)$userId,
                'status' => 'pending',
                'notes' => trim($data['notes'] ?? '')
            ];

            if (!$this->requisitionModel->insert($header)) {
                throw new Exception('Failed to create requisition');
            }

            $requisitionId = $this->db->insert_id;

            foreach ($validItems as $item) {
                $added = $this->requisitionModel->addItem(
                    $requisitionId,
                    (int)$item['product_id'],
                    (int)$item['quantity_requested'],
                    $item['remarks']
                );

                if (!$added) {
                    throw new Exception('Failed to add requisition item');
                }
            }

            $this->logAudit('Create', 'requisition', $requisitionId, '', json_encode($header));
            $this->db->commit();
            return ['success' => true, 'message' => 'Requisition created successfully', 'requisition_id' => $requisitionId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function approve($id, $approvedBy) {
        if (!can('requisition.approve')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $requisition = $this->getById($id);
        if (!$requisition) {
            return ['success' => false, 'message' => 'Requisition not found'];
        }
        if ($requisition['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requisitions can be approved'];
        }

        if (!$this->requisitionModel->approve((int)$id, (int)$approvedBy)) {
            return ['success' => false, 'message' => 'Failed to approve requisition'];
        }

        $lineSql = "UPDATE requisition_items
                    SET quantity_approved = quantity_requested
                    WHERE requisition_id = ? AND quantity_approved IS NULL";
        $lineStmt = $this->db->prepare($lineSql);
        $lineStmt->bind_param('i', $id);
        $lineStmt->execute();

        $this->logAudit('Approve', 'requisition', (int)$id, 'pending', 'approved');
        return ['success' => true, 'message' => 'Requisition approved'];
    }

    public function reject($id, $reason) {
        if (!can('requisition.reject')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $requisition = $this->getById($id);
        if (!$requisition) {
            return ['success' => false, 'message' => 'Requisition not found'];
        }
        if ($requisition['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requisitions can be rejected'];
        }

        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'message' => 'Rejection reason is required'];
        }

        if (!$this->requisitionModel->reject((int)$id, $reason)) {
            return ['success' => false, 'message' => 'Failed to reject requisition'];
        }

        $this->logAudit('Reject', 'requisition', (int)$id, 'pending', $reason);
        return ['success' => true, 'message' => 'Requisition rejected'];
    }

    private function validateHeader($data) {
        if (empty($data['store_id']) || !ctype_digit((string)$data['store_id'])) {
            return 'Please select a store';
        }
        return null;
    }

    private function getDepartmentForUser($userId) {
        $sql = "SELECT d.dept_id, d.dept_name, d.dept_code
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                JOIN departments d ON d.dept_name = r.role_name AND d.status = 'active'
                WHERE u.user_id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    private function normalizeItems($items) {
        $products = $items['product_id'] ?? [];
        $quantities = $items['quantity_requested'] ?? [];
        $remarks = $items['remarks'] ?? [];

        $rows = [];
        $count = count($products);
        for ($i = 0; $i < $count; $i++) {
            $productId = $products[$i] ?? '';
            $quantity = $quantities[$i] ?? '';

            if (!ctype_digit((string)$productId)) {
                continue;
            }
            if (!is_numeric($quantity) || (int)$quantity < 1) {
                continue;
            }

            $rows[] = [
                'product_id' => (int)$productId,
                'quantity_requested' => (int)$quantity,
                'remarks' => trim($remarks[$i] ?? '')
            ];
        }

        return $rows;
    }
}
