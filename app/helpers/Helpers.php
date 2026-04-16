<?php
/**
 * Validation Helper
 * Used for input validation across the system
 */

class Validation {
    private $errors = [];

    /**
     * Validate required field
     */
    public function required($field, $value) {
        if (empty(trim($value))) {
            $this->errors[$field] = ucfirst($field) . ' is required';
        }
        return $this;
    }

    /**
     * Validate email
     */
    public function email($field, $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = ucfirst($field) . ' must be a valid email';
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength($field, $value, $min) {
        if (strlen($value) < $min) {
            $this->errors[$field] = ucfirst($field) . ' must be at least ' . $min . ' characters';
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength($field, $value, $max) {
        if (strlen($value) > $max) {
            $this->errors[$field] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric($field, $value) {
        if (!is_numeric($value)) {
            $this->errors[$field] = ucfirst($field) . ' must be numeric';
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date($field, $value, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $value);
        if (!$d || $d->format($format) !== $value) {
            $this->errors[$field] = ucfirst($field) . ' must be in ' . $format . ' format';
        }
        return $this;
    }

    /**
     * Validate unique value in database
     */
    public function unique($field, $value, $table, $column = null) {
        $column = $column ?: $field;
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $this->errors[$field] = ucfirst($field) . ' already exists';
        }
        return $this;
    }

    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
}

/**
 * Security Helper
 */
class Security {
    /**
     * Sanitize input
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else {
            $data = htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', hash('sha256', SECRET_KEY), 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt data
     */
    public static function decrypt($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', hash('sha256', SECRET_KEY), 0, $iv);
    }

    /**
     * Generate random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

/**
 * Response Helper
 */
class Response {
    /**
     * JSON response
     */
    public static function json($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Success response
     */
    public static function success($message, $data = []) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    /**
     * Error response
     */
    public static function error($message, $statusCode = 400) {
        self::json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError($errors) {
        self::json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }
}
