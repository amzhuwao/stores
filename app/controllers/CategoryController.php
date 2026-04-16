<?php
/**
 * Category Controller
 */

class CategoryController extends Controller {
    private $categoryModel;

    public function __construct() {
        parent::__construct();
        $this->categoryModel = new Category();
    }

    public function getCategories($filters = []) {
        $sql = "
            SELECT
                c.category_id,
                c.category_name,
                c.description,
                c.status,
                c.created_at,
                COUNT(DISTINCT p.product_id) AS products_count,
                COALESCE(SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END), 0) AS active_products
            FROM categories c
            LEFT JOIN products p ON c.category_id = p.category_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (c.category_name LIKE ? OR c.description LIKE ?)";
            $q = '%' . trim($filters['q']) . '%';
            $types .= 'ss';
            $params[] = $q;
            $params[] = $q;
        }

        $status = $filters['status'] ?? 'active';
        if (in_array($status, ['active', 'inactive'], true)) {
            $sql .= " AND c.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " GROUP BY c.category_id ORDER BY c.category_name ASC";

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
            'total' => 0,
            'with_products' => 0
        ];

        $statusResult = $this->db->query("SELECT status, COUNT(*) AS c FROM categories GROUP BY status");
        while ($row = $statusResult->fetch_assoc()) {
            if ($row['status'] === 'active') {
                $stats['active'] = (int)$row['c'];
            }
            if ($row['status'] === 'inactive') {
                $stats['inactive'] = (int)$row['c'];
            }
            $stats['total'] += (int)$row['c'];
        }

        $withProductsSql = "
            SELECT COUNT(*) AS c
            FROM (
                SELECT c.category_id
                FROM categories c
                JOIN products p ON c.category_id = p.category_id
                GROUP BY c.category_id
            ) t
        ";
        $withProductsResult = $this->db->query($withProductsSql);
        if ($withProductsResult) {
            $stats['with_products'] = (int)($withProductsResult->fetch_assoc()['c'] ?? 0);
        }

        return $stats;
    }

    public function getById($categoryId) {
        return $this->categoryModel->findById((int)$categoryId, 'categories');
    }

    public function create($data) {
        if (!can('categories.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->nameExists($data['category_name'] ?? '')) {
            return ['success' => false, 'message' => 'Category name already exists'];
        }

        $payload = [
            'category_name' => trim($data['category_name']),
            'description' => trim($data['description'] ?? ''),
            'status' => 'active'
        ];

        if (!$this->categoryModel->insert($payload, 'categories')) {
            return ['success' => false, 'message' => 'Failed to create category'];
        }

        $categoryId = $this->db->insert_id;
        $this->logAudit('Create', 'category', $categoryId, '', json_encode($payload));

        return ['success' => true, 'message' => 'Category created successfully'];
    }

    public function update($categoryId, $data) {
        if (!can('categories.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $categoryId = (int)$categoryId;
        $existing = $this->getById($categoryId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Category not found'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->nameExists($data['category_name'] ?? '', $categoryId)) {
            return ['success' => false, 'message' => 'Category name already exists'];
        }

        $status = $data['status'] ?? $existing['status'];
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $payload = [
            'category_name' => trim($data['category_name']),
            'description' => trim($data['description'] ?? ''),
            'status' => $status
        ];

        if (!$this->categoryModel->update($categoryId, $payload, 'categories')) {
            return ['success' => false, 'message' => 'Failed to update category'];
        }

        $this->logAudit('Update', 'category', $categoryId, json_encode($existing), json_encode($payload));

        return ['success' => true, 'message' => 'Category updated successfully'];
    }

    private function validatePayload($data) {
        if (empty(trim($data['category_name'] ?? ''))) {
            return 'Category name is required';
        }

        if (strlen(trim($data['category_name'])) > 100) {
            return 'Category name must not exceed 100 characters';
        }

        return null;
    }

    private function nameExists($name, $ignoreId = null) {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if ($ignoreId) {
            $sql = "SELECT category_id FROM categories WHERE category_name = ? AND category_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $name, $ignoreId);
        } else {
            $sql = "SELECT category_id FROM categories WHERE category_name = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $name);
        }

        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
