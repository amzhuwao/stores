<?php
/**
 * Store Controller
 */

class StoreController extends Controller {
    private $storeModel;

    public function __construct() {
        parent::__construct();
        $this->storeModel = new Store();
    }

    public function getStores($filters = []) {
        $sql = "
            SELECT
                s.store_id,
                s.store_name,
                s.store_code,
                s.location,
                s.description,
                s.status,
                s.created_at,
                u.full_name AS responsible_person,
                COUNT(DISTINCT st.product_id) AS products_count,
                COALESCE(SUM(st.quantity_on_hand), 0) AS total_stock_qty
            FROM stores s
            LEFT JOIN users u ON s.responsible_user_id = u.user_id
            LEFT JOIN stock st ON s.store_id = st.store_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (s.store_name LIKE ? OR s.store_code LIKE ? OR s.location LIKE ? OR u.full_name LIKE ?)";
            $q = '%' . trim($filters['q']) . '%';
            $types .= 'ssss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $status = $filters['status'] ?? 'active';
        if (in_array($status, ['active', 'inactive'], true)) {
            $sql .= " AND s.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " GROUP BY s.store_id ORDER BY s.store_name ASC";

        if ($types !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getStats() {
        $stats = [
            'active' => 0,
            'inactive' => 0,
            'total' => 0
        ];

        $result = $this->db->query("SELECT status, COUNT(*) AS c FROM stores GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'active') {
                $stats['active'] = (int)$row['c'];
            }
            if ($row['status'] === 'inactive') {
                $stats['inactive'] = (int)$row['c'];
            }
            $stats['total'] += (int)$row['c'];
        }

        return $stats;
    }

    public function getResponsibleUsers() {
        $sql = "SELECT user_id, full_name, username FROM users WHERE status = 'active' ORDER BY full_name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($storeId) {
        return $this->storeModel->findById((int)$storeId, 'stores');
    }

    public function create($data) {
        if (!can('stores.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->codeExists($data['store_code'] ?? '')) {
            return ['success' => false, 'message' => 'Store code already exists'];
        }

        $payload = [
            'store_name' => trim($data['store_name']),
            'store_code' => strtoupper(trim($data['store_code'])),
            'location' => trim($data['location'] ?? ''),
            'responsible_user_id' => !empty($data['responsible_user_id']) ? (int)$data['responsible_user_id'] : null,
            'description' => trim($data['description'] ?? ''),
            'status' => 'active'
        ];

        if (!$this->storeModel->insert($payload, 'stores')) {
            return ['success' => false, 'message' => 'Failed to create store'];
        }

        $storeId = $this->db->insert_id;

        // Ensure every active product has an initial stock row for the new store.
        $this->initializeStoreStock($storeId);

        $this->logAudit('Create', 'store', $storeId, '', json_encode($payload));

        return ['success' => true, 'message' => 'Store created successfully'];
    }

    public function update($storeId, $data) {
        if (!can('stores.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $storeId = (int)$storeId;
        $existing = $this->getById($storeId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Store not found'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->codeExists($data['store_code'] ?? '', $storeId)) {
            return ['success' => false, 'message' => 'Store code already exists'];
        }

        $status = $data['status'] ?? $existing['status'];
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $payload = [
            'store_name' => trim($data['store_name']),
            'store_code' => strtoupper(trim($data['store_code'])),
            'location' => trim($data['location'] ?? ''),
            'responsible_user_id' => !empty($data['responsible_user_id']) ? (int)$data['responsible_user_id'] : null,
            'description' => trim($data['description'] ?? ''),
            'status' => $status
        ];

        if (!$this->storeModel->update($storeId, $payload, 'stores')) {
            return ['success' => false, 'message' => 'Failed to update store'];
        }

        $this->logAudit('Update', 'store', $storeId, json_encode($existing), json_encode($payload));

        return ['success' => true, 'message' => 'Store updated successfully'];
    }

    private function validatePayload($data) {
        if (empty(trim($data['store_name'] ?? ''))) {
            return 'Store name is required';
        }

        $code = strtoupper(trim($data['store_code'] ?? ''));
        if ($code === '') {
            return 'Store code is required';
        }

        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
            return 'Store code must be 2-20 characters using letters, numbers, dash, or underscore';
        }

        return null;
    }

    private function codeExists($code, $ignoreId = null) {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return false;
        }

        if ($ignoreId) {
            $sql = "SELECT store_id FROM stores WHERE store_code = ? AND store_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $code, $ignoreId);
        } else {
            $sql = "SELECT store_id FROM stores WHERE store_code = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $code);
        }

        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function initializeStoreStock($storeId) {
        $sql = "
            INSERT INTO stock (product_id, store_id, quantity_on_hand, reorder_level)
            SELECT p.product_id, ?, 0, p.reorder_level
            FROM products p
            WHERE p.status = 'active'
              AND NOT EXISTS (
                SELECT 1
                FROM stock s
                WHERE s.product_id = p.product_id
                  AND s.store_id = ?
              )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $storeId, $storeId);
        $stmt->execute();
    }
}
