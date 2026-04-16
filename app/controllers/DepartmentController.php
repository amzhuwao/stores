<?php
/**
 * Department Controller
 */

class DepartmentController extends Controller {
    private $departmentModel;

    public function __construct() {
        parent::__construct();
        $this->departmentModel = new Department();
    }

    public function getDepartments($filters = []) {
        $sql = "
            SELECT
                d.dept_id,
                d.dept_name,
                d.dept_code,
                d.status,
                d.monthly_budget,
                d.weekly_budget,
                d.created_at,
                u.full_name AS head_name,
                COUNT(DISTINCT r.requisition_id) AS requisitions_count,
                COALESCE(SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_requisitions
            FROM departments d
            LEFT JOIN users u ON d.head_user_id = u.user_id
            LEFT JOIN requisitions r ON d.dept_id = r.department_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (d.dept_name LIKE ? OR d.dept_code LIKE ? OR u.full_name LIKE ?)";
            $q = '%' . trim($filters['q']) . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $status = $filters['status'] ?? 'active';
        if (in_array($status, ['active', 'inactive'], true)) {
            $sql .= " AND d.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " GROUP BY d.dept_id ORDER BY d.dept_name ASC";

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

        $result = $this->db->query("SELECT status, COUNT(*) AS c FROM departments GROUP BY status");
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

    public function getHeads() {
        $sql = "SELECT user_id, full_name, username FROM users WHERE status = 'active' ORDER BY full_name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($departmentId) {
        return $this->departmentModel->findById((int)$departmentId, 'departments');
    }

    public function create($data) {
        if (!can('departments.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->codeExists($data['dept_code'] ?? '')) {
            return ['success' => false, 'message' => 'Department code already exists'];
        }

        $payload = [
            'dept_name' => trim($data['dept_name']),
            'dept_code' => strtoupper(trim($data['dept_code'])),
            'head_user_id' => !empty($data['head_user_id']) ? (int)$data['head_user_id'] : null,
            'monthly_budget' => (float)($data['monthly_budget'] ?? 0),
            'weekly_budget' => (float)($data['weekly_budget'] ?? 0),
            'status' => 'active'
        ];

        if (!$this->departmentModel->insert($payload, 'departments')) {
            return ['success' => false, 'message' => 'Failed to create department'];
        }

        $departmentId = $this->db->insert_id;
        $this->logAudit('Create', 'department', $departmentId, '', json_encode($payload));

        return ['success' => true, 'message' => 'Department created successfully'];
    }

    public function update($departmentId, $data) {
        if (!can('departments.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $departmentId = (int)$departmentId;
        $existing = $this->getById($departmentId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Department not found'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->codeExists($data['dept_code'] ?? '', $departmentId)) {
            return ['success' => false, 'message' => 'Department code already exists'];
        }

        $status = $data['status'] ?? $existing['status'];
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $payload = [
            'dept_name' => trim($data['dept_name']),
            'dept_code' => strtoupper(trim($data['dept_code'])),
            'head_user_id' => !empty($data['head_user_id']) ? (int)$data['head_user_id'] : null,
            'monthly_budget' => (float)($data['monthly_budget'] ?? 0),
            'weekly_budget' => (float)($data['weekly_budget'] ?? 0),
            'status' => $status
        ];

        if (!$this->departmentModel->update($departmentId, $payload, 'departments')) {
            return ['success' => false, 'message' => 'Failed to update department'];
        }

        $this->logAudit('Update', 'department', $departmentId, json_encode($existing), json_encode($payload));

        return ['success' => true, 'message' => 'Department updated successfully'];
    }

    private function validatePayload($data) {
        if (empty(trim($data['dept_name'] ?? ''))) {
            return 'Department name is required';
        }

        $code = strtoupper(trim($data['dept_code'] ?? ''));
        if ($code === '') {
            return 'Department code is required';
        }

        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
            return 'Department code must be 2-20 characters using letters, numbers, dash, or underscore';
        }

        if (isset($data['monthly_budget']) && (!is_numeric($data['monthly_budget']) || (float)$data['monthly_budget'] < 0)) {
            return 'Monthly budget must be a non-negative amount';
        }

        if (isset($data['weekly_budget']) && (!is_numeric($data['weekly_budget']) || (float)$data['weekly_budget'] < 0)) {
            return 'Weekly budget must be a non-negative amount';
        }

        return null;
    }

    private function codeExists($code, $ignoreId = null) {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return false;
        }

        if ($ignoreId) {
            $sql = "SELECT dept_id FROM departments WHERE dept_code = ? AND dept_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $code, $ignoreId);
        } else {
            $sql = "SELECT dept_id FROM departments WHERE dept_code = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $code);
        }

        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
