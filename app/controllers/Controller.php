<?php
/**
 * Base Controller Class
 * Parent class for all controllers
 */

class Controller {
    protected $db;
    protected $currentUser = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->checkAuth();
        $this->loadCurrentUser();
    }

    /**
     * Check if user is authenticated
     */
    protected function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . SITE_URL . 'login.php');
            exit;
        }
    }

    /**
     * Load current user data
     */
    protected function loadCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            $sql = "SELECT u.*, r.role_name FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE u.user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $this->currentUser = $stmt->get_result()->fetch_assoc();
        }
    }

    /**
     * Get current user
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }

    /**
     * Check if user has permission
     */
    protected function checkPermission($requiredRole) {
        if (!$this->currentUser || $this->currentUser['role_name'] !== $requiredRole) {
            header('HTTP/1.0 403 Forbidden');
            die('Access Denied');
        }
    }

    /**
     * Load model
     */
    protected function model($modelName) {
        require_once __DIR__ . '/../models/' . $modelName . '.php';
        return new $modelName();
    }

    /**
     * Load view
     */
    protected function view($viewName, $data = []) {
        extract($data);
        require_once __DIR__ . '/../views/' . $viewName . '.php';
    }

    /**
     * Redirect
     */
    protected function redirect($path) {
        header('Location: ' . SITE_URL . $path);
        exit;
    }

    /**
     * Set flash message
     */
    protected function setFlash($message, $type = 'info') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    /**
     * Log audit trail
     */
    protected function logAudit($action, $entityType, $entityId, $oldValue = '', $newValue = '') {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            
            $sql = "INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_value, new_value, ip_address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ississs', $userId, $action, $entityType, $entityId, $oldValue, $newValue, $ipAddress);
            $stmt->execute();
        }
    }
}
