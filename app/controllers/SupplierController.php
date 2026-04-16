<?php
/**
 * Supplier Controller
 */

class SupplierController extends Controller {
    private $supplierModel;

    public function __construct() {
        parent::__construct();
        $this->supplierModel = new Supplier();
    }

    public function getSuppliers($filters = []) {
        $sql = "SELECT * FROM suppliers WHERE 1 = 1";
        $types = '';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (supplier_name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'ssss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $status = $filters['status'] ?? 'active';
        if (in_array($status, ['active', 'inactive'], true)) {
            $sql .= " AND status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " ORDER BY supplier_name ASC";

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

        $result = $this->db->query("SELECT status, COUNT(*) AS c FROM suppliers GROUP BY status");
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

    public function getById($supplierId) {
        return $this->supplierModel->findById((int)$supplierId, 'suppliers');
    }

    public function create($data) {
        if (!can('suppliers.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->emailExists($data['email'] ?? '')) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $payload = [
            'supplier_name' => trim($data['supplier_name']),
            'contact_person' => trim($data['contact_person'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'address' => trim($data['address'] ?? ''),
            'city' => trim($data['city'] ?? ''),
            'postal_code' => trim($data['postal_code'] ?? ''),
            'payment_terms' => trim($data['payment_terms'] ?? ''),
            'status' => 'active'
        ];

        if (!$this->supplierModel->insert($payload)) {
            return ['success' => false, 'message' => 'Failed to create supplier'];
        }

        $supplierId = $this->db->insert_id;
        $this->logAudit('Create', 'supplier', $supplierId, '', json_encode($payload));

        return ['success' => true, 'message' => 'Supplier created successfully'];
    }

    public function update($supplierId, $data) {
        if (!can('suppliers.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $supplierId = (int)$supplierId;
        $existing = $this->getById($supplierId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Supplier not found'];
        }

        $validationError = $this->validatePayload($data);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->emailExists($data['email'] ?? '', $supplierId)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $payload = [
            'supplier_name' => trim($data['supplier_name']),
            'contact_person' => trim($data['contact_person'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'address' => trim($data['address'] ?? ''),
            'city' => trim($data['city'] ?? ''),
            'postal_code' => trim($data['postal_code'] ?? ''),
            'payment_terms' => trim($data['payment_terms'] ?? '')
        ];

        if (!$this->supplierModel->update($supplierId, $payload, 'suppliers')) {
            return ['success' => false, 'message' => 'Failed to update supplier'];
        }

        $this->logAudit('Update', 'supplier', $supplierId, json_encode($existing), json_encode($payload));

        return ['success' => true, 'message' => 'Supplier updated successfully'];
    }

    public function setStatus($supplierId, $status) {
        if (!can('suppliers.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $supplierId = (int)$supplierId;
        if (!in_array($status, ['active', 'inactive'], true)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $existing = $this->getById($supplierId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Supplier not found'];
        }

        if (!$this->supplierModel->update($supplierId, ['status' => $status], 'suppliers')) {
            return ['success' => false, 'message' => 'Failed to update supplier status'];
        }

        $this->logAudit('Status Change', 'supplier', $supplierId, $existing['status'], $status);
        return ['success' => true, 'message' => 'Supplier status updated'];
    }

    private function validatePayload($data) {
        if (empty(trim($data['supplier_name'] ?? ''))) {
            return 'Supplier name is required';
        }
        if (empty(trim($data['email'] ?? '')) && empty(trim($data['phone'] ?? ''))) {
            return 'At least one contact detail is required';
        }
        if (!empty(trim($data['email'] ?? '')) && !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address';
        }
        return null;
    }

    private function emailExists($email, $ignoreId = null) {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        if ($ignoreId) {
            $sql = "SELECT supplier_id FROM suppliers WHERE email = ? AND supplier_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $email, $ignoreId);
        } else {
            $sql = "SELECT supplier_id FROM suppliers WHERE email = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $email);
        }

        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
