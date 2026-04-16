<?php
/**
 * User Controller
 */

class UserController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function getUsers($filters = []) {
        $sql = "
            SELECT u.user_id, u.username, u.email, u.full_name, u.status, u.created_at, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
            $q = '%' . trim($filters['q']) . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['role_id']) && ctype_digit((string)$filters['role_id'])) {
            $sql .= " AND u.role_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['role_id'];
        }

        $status = $filters['status'] ?? 'all';
        if (in_array($status, ['active', 'inactive'], true)) {
            $sql .= " AND u.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $sql .= " ORDER BY u.created_at DESC";

        $stmt = $this->db->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoles() {
        return $this->db->query("SELECT role_id, role_name FROM roles ORDER BY role_name ASC")->fetch_all(MYSQLI_ASSOC);
    }

    public function getStats() {
        $stats = ['total' => 0, 'active' => 0, 'inactive' => 0];

        $result = $this->db->query("SELECT status, COUNT(*) AS c FROM users GROUP BY status");
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

    public function getById($userId) {
        $sql = "
            SELECT u.user_id, u.username, u.email, u.full_name, u.role_id, u.status, u.created_at, u.updated_at, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        if (!can('users.create')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $validationError = $this->validatePayload($data, true);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $payload = [
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'password' => Auth::hashPassword($data['password']),
            'full_name' => trim($data['full_name']),
            'role_id' => (int)$data['role_id'],
            'status' => in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active'
        ];

        if (!$this->userModel->insert($payload, 'users')) {
            return ['success' => false, 'message' => 'Failed to create user'];
        }

        $userId = $this->db->insert_id;
        $this->logAudit('Create', 'user', $userId, '', json_encode([
            'username' => $payload['username'],
            'email' => $payload['email'],
            'full_name' => $payload['full_name'],
            'role_id' => $payload['role_id'],
            'status' => $payload['status']
        ]));

        return ['success' => true, 'message' => 'User created successfully'];
    }

    public function update($userId, $data) {
        if (!can('users.edit')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $userId = (int)$userId;
        $existing = $this->getById($userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $validationError = $this->validatePayload($data, false);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        if ($this->usernameExists($data['username'], $userId)) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        if ($this->emailExists($data['email'], $userId)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $payload = [
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'full_name' => trim($data['full_name']),
            'role_id' => (int)$data['role_id'],
            'status' => in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active'
        ];

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters'];
            }
            $payload['password'] = Auth::hashPassword($data['password']);
        }

        if (!$this->userModel->update($userId, $payload, 'users')) {
            return ['success' => false, 'message' => 'Failed to update user'];
        }

        if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['role_id'] = (int)$payload['role_id'];
        }

        $auditNew = [
            'username' => $payload['username'],
            'email' => $payload['email'],
            'full_name' => $payload['full_name'],
            'role_id' => $payload['role_id'],
            'status' => $payload['status']
        ];
        if (isset($payload['password'])) {
            $auditNew['password'] = '[updated]';
        }

        $this->logAudit('Update', 'user', $userId, json_encode($existing), json_encode($auditNew));

        return ['success' => true, 'message' => 'User updated successfully'];
    }

    private function validatePayload($data, $isCreate = false) {
        if (empty(trim($data['username'] ?? ''))) {
            return 'Username is required';
        }

        if (empty(trim($data['email'] ?? ''))) {
            return 'Email is required';
        }

        if (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address';
        }

        if (empty(trim($data['full_name'] ?? ''))) {
            return 'Full name is required';
        }

        if (empty($data['role_id']) || !ctype_digit((string)$data['role_id'])) {
            return 'Role is required';
        }

        if ($isCreate && empty($data['password'])) {
            return 'Password is required';
        }

        if (!empty($data['password']) && strlen($data['password']) < 8) {
            return 'Password must be at least 8 characters';
        }

        return null;
    }

    private function usernameExists($username, $ignoreId = null) {
        $username = trim($username);
        if ($ignoreId) {
            $sql = "SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $username, $ignoreId);
        } else {
            $sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $username);
        }

        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function emailExists($email, $ignoreId = null) {
        $email = trim($email);
        if ($ignoreId) {
            $sql = "SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $email, $ignoreId);
        } else {
            $sql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $email);
        }

        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
