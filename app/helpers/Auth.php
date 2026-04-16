<?php
/**
 * Authentication Helper Class
 * Handles user registration, login, password reset
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensurePasswordResetTable();
    }

    /**
     * Ensure the password reset token table exists.
     */
    private function ensurePasswordResetTable() {
        $sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            token_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_reset_token_hash (token_hash),
            INDEX idx_reset_token_user (user_id),
            INDEX idx_reset_token_expires (expires_at)
        )";

        try {
            $this->db->query($sql);
        } catch (Throwable $e) {
            // The flow will still fail gracefully if the database user cannot create tables.
        }
    }

    /**
     * Register a new user
     */
    public function register($data) {
        // Validate input
        if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Check if user exists
        $sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $data['username'], $data['email']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $roleId = $data['role_id'] ?? 3; // Default role

        // Insert user
        $sql = "INSERT INTO users (username, email, password, full_name, role_id, status) 
                VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssi', $data['username'], $data['email'], $hashedPassword, $data['full_name'], $roleId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User registered successfully', 'user_id' => $this->db->insert_id];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }

        $sql = "SELECT user_id, username, password, status, role_id FROM users WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        $user = $result->fetch_assoc();

        // Check if user is active
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'User account is inactive'];
        }

        // Verify password (supports bcrypt and legacy SHA-256 from seed data)
        $isValidPassword = password_verify($password, $user['password']);

        if (!$isValidPassword) {
            $legacyHash = hash('sha256', $password);
            $isValidPassword = hash_equals($user['password'], $legacyHash);

            // Auto-upgrade legacy hash to bcrypt after successful legacy login
            if ($isValidPassword) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->bind_param('si', $newHash, $user['user_id']);
                $updateStmt->execute();
            }
        }

        if (!$isValidPassword) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['login_time'] = time();

        return ['success' => true, 'message' => 'Login successful', 'user_id' => $user['user_id']];
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return true;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     */
    public function getCurrentUserRole() {
        return $_SESSION['role_id'] ?? null;
    }

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        if (password_verify($password, $hash)) {
            return true;
        }

        return hash_equals((string)$hash, hash('sha256', $password));
    }

    /**
     * Check session timeout
     */
    public function checkSessionTimeout() {
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        $_SESSION['login_time'] = time();
        return true;
    }

    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = $this->hashPassword($newPassword);
        
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $hashedPassword, $userId);
        
        return $stmt->execute();
    }

    /**
     * Create a password reset token for a user.
     */
    public function createPasswordResetToken($userId, $expiresInMinutes = 60) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60));

        $this->db->begin_transaction();

        try {
            $deleteSql = "DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->bind_param('i', $userId);
            $deleteStmt->execute();

            $sql = "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iss', $userId, $tokenHash, $expiresAt);

            if (!$stmt->execute()) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Unable to create reset token'];
            }

            $this->db->commit();

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Unable to create reset token'];
        }
    }

    /**
     * Find a valid password reset token.
     */
    public function getPasswordResetToken($token) {
        if (empty($token)) {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $sql = "SELECT prt.*, u.user_id, u.username, u.email, u.full_name, u.status
                FROM password_reset_tokens prt
                JOIN users u ON prt.user_id = u.user_id
                WHERE prt.token_hash = ?
                  AND prt.used_at IS NULL
                  AND prt.expires_at > NOW()
                                    AND u.status = 'active'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Mark a reset token as used.
     */
    public function markPasswordResetTokenUsed($tokenId) {
        $sql = "UPDATE password_reset_tokens SET used_at = NOW() WHERE token_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $tokenId);

        return $stmt->execute();
    }

    /**
     * Complete a password reset using a valid token.
     */
    public function resetPasswordWithToken($token, $newPassword) {
        $tokenRow = $this->getPasswordResetToken($token);

        if (!$tokenRow) {
            return ['success' => false, 'message' => 'This reset link is invalid or has expired.'];
        }

        $this->db->begin_transaction();

        try {
            if (!$this->updatePassword((int)$tokenRow['user_id'], $newPassword)) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Unable to update the password right now.'];
            }

            $used = $this->markPasswordResetTokenUsed((int)$tokenRow['token_id']);
            if (!$used) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Unable to finalize the reset.'];
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Password reset successfully.'];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Unable to reset the password right now.'];
        }
    }
}
