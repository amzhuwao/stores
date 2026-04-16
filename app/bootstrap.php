<?php
/**
 * Application Bootstrap File
 * Autoload all necessary classes and initialize the app
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers/Helpers.php';

// Define secret key for encryption
define('SECRET_KEY', 'your-secret-key-change-this-in-production');

/**
 * Autoloader function
 */
function autoload($class) {
    $paths = [
        __DIR__ . '/helpers/' . $class . '.php',
        __DIR__ . '/models/' . $class . '.php',
        __DIR__ . '/controllers/' . $class . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}

// Register autoloader
spl_autoload_register('autoload');

/**
 * Helper function to check if user is authenticated
 */
function isAuthenticated() {
    $auth = new Auth();
    return $auth->isLoggedIn() && $auth->checkSessionTimeout();
}

/**
 * Helper function to get current user
 */
function getCurrentUser() {
    if (isAuthenticated()) {
        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT u.*, r.role_name FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    return null;
}

/**
 * Helper function to format currency
 */
function formatCurrency($amount) {
    return number_format($amount, 2, '.', ',');
}

/**
 * Helper function to format date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Helper function to get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Helper function to generate CSRF token input
 */
function getCSRFTokenField() {
    $token = Security::generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Helper function to check user role
 */
function hasRole($roleNames) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $roles = is_array($roleNames) ? $roleNames : [$roleNames];
    return in_array($user['role_name'], $roles);
}

/**
 * Role permission matrix.
 */
function getRolePermissions() {
    return [
        'Admin' => ['*'],
        'Storekeeper' => [
            'dashboard.view', 'products.*', 'stores.view', 'stores.create', 'stores.edit', 'departments.view', 'departments.create', 'departments.edit', 'categories.view', 'categories.create', 'categories.edit', 'suppliers.*', 'stock.view',
            'grn.*', 'requisition.view', 'requisition.create',
            'stock-issues.*', 'adjustments.view', 'adjustments.create', 'reports.view'
        ],
        'Manager' => [
            'dashboard.view', 'stores.view', 'stores.create', 'stores.edit', 'departments.view', 'departments.create', 'departments.edit', 'categories.view', 'categories.create', 'categories.edit', 'stock.view', 'grn.view', 'grn.verify',
            'requisition.view', 'requisition.approve', 'requisition.reject',
            'stock-issues.view', 'adjustments.view', 'adjustments.create', 'adjustments.approve', 'reports.view', 'audit.view'
        ],
        'Accounts' => ['dashboard.view', 'stores.view', 'departments.view', 'categories.view', 'stock.view', 'adjustments.view', 'reports.view'],
        'Kitchen' => ['dashboard.view', 'stock.view', 'requisition.view', 'requisition.create'],
        'Bar' => ['dashboard.view', 'stock.view', 'requisition.view', 'requisition.create'],
        'Housekeeping' => ['dashboard.view', 'stock.view', 'requisition.view', 'requisition.create'],
        'Maintenance' => ['dashboard.view', 'stock.view', 'requisition.view', 'requisition.create']
    ];
}

/**
 * Check if current user can perform a permission.
 */
function can($permission) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }

    $role = $user['role_name'] ?? '';
    $matrix = getRolePermissions();
    $granted = $matrix[$role] ?? [];

    if (in_array('*', $granted, true)) {
        return true;
    }
    if (in_array($permission, $granted, true)) {
        return true;
    }

    $parts = explode('.', $permission, 2);
    if (count($parts) === 2) {
        $moduleWildcard = $parts[0] . '.*';
        if (in_array($moduleWildcard, $granted, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Enforce a permission for the current user.
 */
function requirePermission($permission) {
    if (!can($permission)) {
        http_response_code(403);
        die('Access denied: insufficient permissions for ' . htmlspecialchars($permission));
    }
}

/**
 * Route-level permission map.
 */
function getRoutePermissionMap() {
    return [
        '/dashboard.php' => 'dashboard.view',
        '/pages/products/index.php' => 'products.view',
        '/pages/products/create.php' => 'products.create',
        '/pages/products/edit.php' => 'products.edit',
        '/pages/stores/index.php' => 'stores.view',
        '/pages/stores/create.php' => 'stores.create',
        '/pages/stores/edit.php' => 'stores.edit',
        '/pages/departments/index.php' => 'departments.view',
        '/pages/departments/create.php' => 'departments.create',
        '/pages/departments/edit.php' => 'departments.edit',
        '/pages/categories/index.php' => 'categories.view',
        '/pages/categories/create.php' => 'categories.create',
        '/pages/categories/edit.php' => 'categories.edit',
        '/pages/suppliers/index.php' => 'suppliers.view',
        '/pages/suppliers/create.php' => 'suppliers.create',
        '/pages/suppliers/edit.php' => 'suppliers.edit',
        '/pages/stock/view.php' => 'stock.view',
        '/pages/grn/index.php' => 'grn.view',
        '/pages/grn/create.php' => 'grn.create',
        '/pages/grn/view.php' => 'grn.view',
        '/pages/requisition/index.php' => 'requisition.view',
        '/pages/requisition/create.php' => 'requisition.create',
        '/pages/requisition/view.php' => 'requisition.view',
        '/pages/stock-issues/index.php' => 'stock-issues.view',
        '/pages/stock-issues/create.php' => 'stock-issues.create',
        '/pages/stock-issues/view.php' => 'stock-issues.view',
        '/pages/adjustments/index.php' => 'adjustments.view',
        '/pages/adjustments/create.php' => 'adjustments.create',
        '/pages/adjustments/view.php' => 'adjustments.view',
        '/pages/reports/index.php' => 'reports.view',
        '/pages/audit/index.php' => 'audit.view',
        '/pages/users/index.php' => 'users.view',
        '/pages/users/create.php' => 'users.create',
        '/pages/users/edit.php' => 'users.edit',
        '/pages/settings/index.php' => 'settings.view'
    ];
}

/**
 * Enforce permission based on current script route.
 */
function enforceRoutePermission() {
    if (!isAuthenticated()) {
        return;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = '/stores';
    $pos = strpos($script, $base);
    if ($pos !== false) {
        $script = substr($script, $pos + strlen($base));
    }
    if ($script === '' || $script === '/') {
        $script = '/index.php';
    }

    $map = getRoutePermissionMap();
    if (isset($map[$script])) {
        requirePermission($map[$script]);
    }
}

/**
 * Export HTML as PDF when mPDF exists, else print-friendly HTML fallback.
 */
function exportPdfOrPrintHtml($title, $htmlBody, $fileBaseName = 'document') {
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $fileBaseName);

    if (class_exists('\\Mpdf\\Mpdf')) {
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML('<h2>' . htmlspecialchars($title) . '</h2>' . $htmlBody);
        $mpdf->Output($safeName . '.pdf', 'D');
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;padding:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}h2{margin-bottom:16px;}</style>';
    echo '</head><body>';
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    echo '<p><small>PDF library not installed; use browser Print to save as PDF.</small></p>';
    echo $htmlBody;
    echo '<script>window.print();</script>';
    echo '</body></html>';
    exit;
}

/**
 * Ensure financial schema extensions exist for live databases created before these features.
 */
function ensureFinancialSchema() {
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }

    $bootstrapped = true;

    try {
        $db = Database::getInstance()->getConnection();

        $columnExists = function($table, $column) use ($db) {
            $sql = "SELECT 1
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('ss', $table, $column);
            $stmt->execute();
            return (bool)$stmt->get_result()->fetch_assoc();
        };

        $tableExists = function($table) use ($db) {
            $sql = "SELECT 1
                    FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('s', $table);
            $stmt->execute();
            return (bool)$stmt->get_result()->fetch_assoc();
        };

        if (!$columnExists('departments', 'monthly_budget')) {
            $db->query("ALTER TABLE departments ADD COLUMN monthly_budget DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER status");
        }
        if (!$columnExists('departments', 'weekly_budget')) {
            $db->query("ALTER TABLE departments ADD COLUMN weekly_budget DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER monthly_budget");
        }

        if (!$columnExists('grn', 'invoice_reference')) {
            $db->query("ALTER TABLE grn ADD COLUMN invoice_reference VARCHAR(100) NULL AFTER delivery_note_ref");
        }
        if (!$columnExists('grn', 'total_cost')) {
            $db->query("ALTER TABLE grn ADD COLUMN total_cost DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER invoice_reference");
        }

        if (!$columnExists('stock_transactions', 'total_value')) {
            $db->query("ALTER TABLE stock_transactions ADD COLUMN total_value DECIMAL(12,2) NULL AFTER unit_price");
        }

        if (!$tableExists('pos_sales_usage')) {
            $db->query("CREATE TABLE pos_sales_usage (
                pos_usage_id INT PRIMARY KEY AUTO_INCREMENT,
                integration_source VARCHAR(50) NOT NULL DEFAULT 'manual',
                sale_reference VARCHAR(100) NOT NULL,
                product_id INT NOT NULL,
                store_id INT NULL,
                quantity_sold DECIMAL(12,3) NOT NULL DEFAULT 0,
                consumed_quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
                revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
                cogs DECIMAL(12,2) NOT NULL DEFAULT 0,
                sale_date DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(product_id),
                FOREIGN KEY (store_id) REFERENCES stores(store_id),
                INDEX idx_pos_sale_date (sale_date),
                INDEX idx_pos_product (product_id)
            )");
        }
    } catch (Throwable $e) {
        if (APP_DEBUG) {
            error_log('Financial schema bootstrap warning: ' . $e->getMessage());
        }
    }
}

ensureFinancialSchema();

enforceRoutePermission();

/**
 * Register available models
 */
// Register by requiring them as needed in controllers
