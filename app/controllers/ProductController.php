<?php
/**
 * Product Controller
 */

class ProductController extends Controller {
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function getCategories() {
        $sql = "SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getProducts($filters = []) {
        $sql = "SELECT p.*, c.category_name, COALESCE(SUM(s.quantity_on_hand), 0) AS total_stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN stock s ON p.product_id = s.product_id
                WHERE 1 = 1";

        $types = '';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'ss';
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['category_id']) && ctype_digit((string)$filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['category_id'];
        }

        $status = $filters['status'] ?? 'active';
        if (in_array($status, ['active', 'inactive'], true)) {
            $sql .= " AND p.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " GROUP BY p.product_id, c.category_name ORDER BY p.product_name ASC";

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
            'categories' => 0
        ];

        $result = $this->db->query("SELECT status, COUNT(*) AS c FROM products GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'active') {
                $stats['active'] = (int)$row['c'];
            }
            if ($row['status'] === 'inactive') {
                $stats['inactive'] = (int)$row['c'];
            }
        }

        $result = $this->db->query("SELECT COUNT(*) AS c FROM categories WHERE status = 'active'");
        $stats['categories'] = (int)$result->fetch_assoc()['c'];

        return $stats;
    }

    public function getById($id) {
        return $this->productModel->getWithCategory((int)$id);
    }

    public function create($data) {
        if (!can('products.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->productCodeExists($data['product_code'])) {
            return ['success' => false, 'message' => 'Product code already exists'];
        }

        $payload = [
            'product_name' => trim($data['product_name']),
            'product_code' => strtoupper(trim($data['product_code'])),
            'category_id' => (int)$data['category_id'],
            'unit_of_measure' => trim($data['unit_of_measure']),
            'reorder_level' => (int)$data['reorder_level'],
            'reorder_quantity' => (int)$data['reorder_quantity'],
            'status' => 'active'
        ];

        if (!$this->productModel->insert($payload)) {
            return ['success' => false, 'message' => 'Failed to create product'];
        }

        $productId = $this->db->insert_id;

        // Initialize stock rows for all active stores with zero quantity.
        $initSql = "INSERT INTO stock (product_id, store_id, quantity_on_hand, reorder_level)
                    SELECT ?, s.store_id, 0, ?
                    FROM stores s
                    WHERE s.status = 'active'";
        $initStmt = $this->db->prepare($initSql);
        $initStmt->bind_param('ii', $productId, $payload['reorder_level']);
        $initStmt->execute();

        $this->logAudit('Create', 'product', $productId, '', json_encode($payload));

        return ['success' => true, 'message' => 'Product created successfully'];
    }

    public function update($id, $data) {
        if (!can('products.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $id = (int)$id;
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->productCodeExists($data['product_code'], $id)) {
            return ['success' => false, 'message' => 'Product code already exists'];
        }

        $payload = [
            'product_name' => trim($data['product_name']),
            'product_code' => strtoupper(trim($data['product_code'])),
            'category_id' => (int)$data['category_id'],
            'unit_of_measure' => trim($data['unit_of_measure']),
            'reorder_level' => (int)$data['reorder_level'],
            'reorder_quantity' => (int)$data['reorder_quantity']
        ];

        if (!$this->productModel->update($id, $payload)) {
            return ['success' => false, 'message' => 'Failed to update product'];
        }

        // Keep store-level reorder levels aligned when unchanged manually.
        $syncSql = "UPDATE stock SET reorder_level = ? WHERE product_id = ?";
        $syncStmt = $this->db->prepare($syncSql);
        $syncStmt->bind_param('ii', $payload['reorder_level'], $id);
        $syncStmt->execute();

        $this->logAudit('Update', 'product', $id, json_encode($existing), json_encode($payload));

        return ['success' => true, 'message' => 'Product updated successfully'];
    }

    public function setStatus($id, $status) {
        if (!can('products.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $id = (int)$id;
        if (!in_array($status, ['active', 'inactive'], true)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $updated = $this->productModel->update($id, ['status' => $status]);
        if (!$updated) {
            return ['success' => false, 'message' => 'Failed to change product status'];
        }

        $this->logAudit('Status Change', 'product', $id, $existing['status'], $status);

        return ['success' => true, 'message' => 'Product status updated'];
    }

    private function validatePayload($data) {
        if (empty(trim($data['product_name'] ?? ''))) {
            return 'Product name is required';
        }
        if (empty(trim($data['product_code'] ?? ''))) {
            return 'Product code is required';
        }
        if (empty($data['category_id']) || !ctype_digit((string)$data['category_id'])) {
            return 'Please select a valid category';
        }
        if (empty(trim($data['unit_of_measure'] ?? ''))) {
            return 'Unit of measure is required';
        }

        $reorderLevel = $data['reorder_level'] ?? '';
        $reorderQty = $data['reorder_quantity'] ?? '';
        if (!is_numeric($reorderLevel) || (int)$reorderLevel < 0) {
            return 'Reorder level must be zero or greater';
        }
        if (!is_numeric($reorderQty) || (int)$reorderQty < 1) {
            return 'Reorder quantity must be at least 1';
        }

        return null;
    }

    private function productCodeExists($productCode, $ignoreId = null) {
        $productCode = strtoupper(trim($productCode));

        if ($ignoreId) {
            $sql = "SELECT product_id FROM products WHERE product_code = ? AND product_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $productCode, $ignoreId);
        } else {
            $sql = "SELECT product_id FROM products WHERE product_code = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $productCode);
        }

        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
