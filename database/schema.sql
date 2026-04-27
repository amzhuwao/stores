-- Hotel Stores Management System Database Schema
-- Created: April 15, 2026

-- ====================================
-- 1. USERS & ROLES
-- ====================================
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
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
);

CREATE TABLE IF NOT EXISTS backup_settings (
    setting_id TINYINT PRIMARY KEY DEFAULT 1,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    run_time TIME NOT NULL DEFAULT '02:00:00',
    day_of_week TINYINT NOT NULL DEFAULT 1,
    day_of_month TINYINT NOT NULL DEFAULT 1,
    retention_days INT NOT NULL DEFAULT 30,
    schedule_token VARCHAR(64) NOT NULL,
    last_run_at DATETIME NULL,
    next_run_at DATETIME NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS backup_history (
    backup_id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size_bytes BIGINT NOT NULL DEFAULT 0,
    trigger_type ENUM('manual', 'scheduled') NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    initiated_by INT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (initiated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_backup_history_started_at (started_at),
    INDEX idx_backup_history_status (status),
    INDEX idx_backup_history_trigger (trigger_type)
);

CREATE TABLE IF NOT EXISTS restore_history (
    restore_id INT PRIMARY KEY AUTO_INCREMENT,
    source_type ENUM('stored_backup', 'upload') NOT NULL,
    source_label VARCHAR(255) NOT NULL,
    source_path VARCHAR(500) NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    initiated_by INT NULL,
    safety_backup_id INT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (initiated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (safety_backup_id) REFERENCES backup_history(backup_id) ON DELETE SET NULL,
    INDEX idx_restore_history_started_at (started_at),
    INDEX idx_restore_history_status (status),
    INDEX idx_restore_history_source_type (source_type)
);

-- ====================================
-- 2. STORES (Kitchen, Bar, Housekeeping, Maintenance)
-- ====================================
CREATE TABLE IF NOT EXISTS stores (
    store_id INT PRIMARY KEY AUTO_INCREMENT,
    store_name VARCHAR(100) NOT NULL,
    store_code VARCHAR(20) UNIQUE NOT NULL,
    location VARCHAR(100),
    responsible_user_id INT,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responsible_user_id) REFERENCES users(user_id)
);

-- ====================================
-- 3. DEPARTMENTS (Requesters of stock)
-- ====================================
CREATE TABLE IF NOT EXISTS departments (
    dept_id INT PRIMARY KEY AUTO_INCREMENT,
    dept_name VARCHAR(100) NOT NULL,
    dept_code VARCHAR(20) UNIQUE NOT NULL,
    head_user_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    monthly_budget DECIMAL(12,2) NOT NULL DEFAULT 0,
    weekly_budget DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_user_id) REFERENCES users(user_id)
);

-- ====================================
-- 4. SUPPLIERS
-- ====================================
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(20),
    payment_terms VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ====================================
-- 5. PRODUCT CATEGORIES
-- ====================================
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ====================================
-- 6. PRODUCTS/ITEMS
-- ====================================
CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    category_id INT NOT NULL,
    unit_of_measure VARCHAR(20),
    reorder_level INT DEFAULT 10,
    reorder_quantity INT DEFAULT 50,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- ====================================
-- 7. STOCK (Current inventory levels per store)
-- ====================================
CREATE TABLE IF NOT EXISTS stock (
    stock_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    store_id INT NOT NULL,
    quantity_on_hand INT DEFAULT 0,
    reorder_level INT DEFAULT 0,
    last_counted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_store (product_id, store_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (store_id) REFERENCES stores(store_id)
);

-- ====================================
-- 8. GOODS RECEIVED NOTES (GRN)
-- ====================================
CREATE TABLE IF NOT EXISTS grn (
    grn_id INT PRIMARY KEY AUTO_INCREMENT,
    grn_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    store_id INT NOT NULL,
    received_by INT NOT NULL,
    receipt_date DATE NOT NULL,
    receipt_time TIME,
    delivery_note_ref VARCHAR(100),
    invoice_reference VARCHAR(100),
    total_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'received', 'verified') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (store_id) REFERENCES stores(store_id),
    FOREIGN KEY (received_by) REFERENCES users(user_id)
);

-- ====================================
-- 9. GRN ITEMS (Line items in GRN)
-- ====================================
CREATE TABLE IF NOT EXISTS grn_items (
    grn_item_id INT PRIMARY KEY AUTO_INCREMENT,
    grn_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_expected INT NOT NULL,
    quantity_received INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    batch_number VARCHAR(50),
    expiry_date DATE,
    condition_status ENUM('good', 'damaged', 'partial') DEFAULT 'good',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grn_id) REFERENCES grn(grn_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ====================================
-- 10. REQUISITIONS (Department requests)
-- ====================================
CREATE TABLE IF NOT EXISTS requisitions (
    requisition_id INT PRIMARY KEY AUTO_INCREMENT,
    requisition_number VARCHAR(50) UNIQUE NOT NULL,
    department_id INT NOT NULL,
    store_id INT NOT NULL,
    requested_by INT NOT NULL,
    requested_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'pending', 'approved', 'rejected', 'issued') DEFAULT 'draft',
    approved_by INT,
    approval_date DATETIME,
    rejection_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(dept_id),
    FOREIGN KEY (store_id) REFERENCES stores(store_id),
    FOREIGN KEY (requested_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- ====================================
-- 11. REQUISITION ITEMS
-- ====================================
CREATE TABLE IF NOT EXISTS requisition_items (
    req_item_id INT PRIMARY KEY AUTO_INCREMENT,
    requisition_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_requested INT NOT NULL,
    quantity_approved INT,
    quantity_issued INT,
    unit_price DECIMAL(10, 2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(requisition_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ====================================
-- 12. STOCK ISSUES (Actual stock distribution)
-- ====================================
CREATE TABLE IF NOT EXISTS stock_issues (
    issue_id INT PRIMARY KEY AUTO_INCREMENT,
    issue_number VARCHAR(50) UNIQUE NOT NULL,
    requisition_id INT,
    store_id INT NOT NULL,
    department_id INT NOT NULL,
    issued_by INT NOT NULL,
    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    received_by INT,
    received_date DATETIME,
    status ENUM('issued', 'received', 'cancelled') DEFAULT 'issued',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(requisition_id),
    FOREIGN KEY (store_id) REFERENCES stores(store_id),
    FOREIGN KEY (department_id) REFERENCES departments(dept_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id),
    FOREIGN KEY (received_by) REFERENCES users(user_id)
);

-- ====================================
-- 13. STOCK ISSUE ITEMS
-- ====================================
CREATE TABLE IF NOT EXISTS stock_issue_items (
    issue_item_id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_issued INT NOT NULL,
    quantity_consumed INT DEFAULT 0,
    quantity_returned INT DEFAULT 0,
    batch_number VARCHAR(50),
    expiry_date DATE,
    unit_price DECIMAL(10, 2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES stock_issues(issue_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ====================================
-- 14. CONSUMPTION LOGGING PERMISSIONS
-- ====================================
CREATE TABLE IF NOT EXISTS consumption_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    assigned_by INT NOT NULL,
    can_log_consumption TINYINT(1) DEFAULT 1,
    can_view_reports TINYINT(1) DEFAULT 0,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    status ENUM('active', 'revoked') DEFAULT 'active',
    UNIQUE KEY unique_user_dept (user_id, department_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(dept_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id),
    INDEX idx_user_permissions (user_id, status),
    INDEX idx_dept_permissions (department_id, status)
);

-- ====================================
-- 15. CONSUMPTION RECORDS
-- ====================================
CREATE TABLE IF NOT EXISTS consumption_records (
    consumption_id INT PRIMARY KEY AUTO_INCREMENT,
    issue_item_id INT NOT NULL,
    quantity_consumed INT NOT NULL,
    logged_by INT NOT NULL,
    log_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_item_id) REFERENCES stock_issue_items(issue_item_id) ON DELETE CASCADE,
    FOREIGN KEY (logged_by) REFERENCES users(user_id),
    INDEX idx_consumption_date (log_date),
    INDEX idx_issue_item_consumption (issue_item_id)
);

-- ====================================
-- 16. STOCK TRANSACTIONS (Audit trail)
-- ====================================
CREATE TABLE IF NOT EXISTS stock_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    store_id INT NOT NULL,
    transaction_type ENUM('receipt', 'issue', 'adjustment', 'return', 'consumption') NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    quantity_change INT NOT NULL,
    unit_price DECIMAL(10, 2),
    total_value DECIMAL(12, 2),
    performed_by INT NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (store_id) REFERENCES stores(store_id),
    FOREIGN KEY (performed_by) REFERENCES users(user_id),
    INDEX idx_product_store (product_id, store_id),
    INDEX idx_transaction_date (transaction_date)
);

CREATE TABLE IF NOT EXISTS pos_sales_usage (
    pos_usage_id INT PRIMARY KEY AUTO_INCREMENT,
    integration_source VARCHAR(50) NOT NULL DEFAULT 'manual',
    sale_reference VARCHAR(100) NOT NULL,
    product_id INT NOT NULL,
    store_id INT,
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
);

-- ====================================
-- 17. BATCH & EXPIRY TRACKING
-- ====================================
CREATE TABLE IF NOT EXISTS batch_tracking (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    store_id INT NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    expiry_date DATE NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2),
    grn_item_id INT,
    received_date DATE,
    status ENUM('available', 'expired', 'consumed') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (store_id) REFERENCES stores(store_id),
    FOREIGN KEY (grn_item_id) REFERENCES grn_items(grn_item_id)
);

-- ====================================
-- 18. STOCK ADJUSTMENTS
-- ====================================
CREATE TABLE IF NOT EXISTS stock_adjustments (
    adjustment_id INT PRIMARY KEY AUTO_INCREMENT,
    adjustment_number VARCHAR(50) UNIQUE NOT NULL,
    store_id INT NOT NULL,
    adjustment_reason ENUM('damage', 'loss', 'correction', 'count_variance', 'recall') NOT NULL,
    adjusted_by INT NOT NULL,
    adjustment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    approval_date DATETIME,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(store_id),
    FOREIGN KEY (adjusted_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- ====================================
-- 19. ADJUSTMENT ITEMS
-- ====================================
CREATE TABLE IF NOT EXISTS adjustment_items (
    adj_item_id INT PRIMARY KEY AUTO_INCREMENT,
    adjustment_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_change INT NOT NULL,
    reason_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (adjustment_id) REFERENCES stock_adjustments(adjustment_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ====================================
-- 20. REORDER ALERTS
-- ===================================
CREATE TABLE IF NOT EXISTS reorder_alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    store_id INT NOT NULL,
    current_quantity INT NOT NULL,
    reorder_level INT NOT NULL,
    alert_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_by INT,
    acknowledged_date DATETIME,
    status ENUM('new', 'acknowledged', 'ordered', 'received') DEFAULT 'new',
    notes TEXT,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (store_id) REFERENCES stores(store_id),
    FOREIGN KEY (acknowledged_by) REFERENCES users(user_id)
);

-- ====================================
-- 21. AUDIT LOG
-- ====================================
CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_action_date (action_date),
    INDEX idx_user_action (user_id, action_date)
);

CREATE TABLE IF NOT EXISTS offline_sync_log (
    sync_id INT PRIMARY KEY AUTO_INCREMENT,
    client_record_id VARCHAR(100) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    record_type VARCHAR(80) NOT NULL,
    page_url VARCHAR(255) NULL,
    payload_json LONGTEXT NOT NULL,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    server_reference_type VARCHAR(50) NULL,
    server_reference_id INT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_offline_sync_status (status, created_at),
    INDEX idx_offline_sync_user (user_id, created_at)
);

-- ====================================
-- INITIAL DATA
-- ====================================

-- Insert Roles
INSERT IGNORE INTO roles (role_name, description) VALUES
('Admin', 'System administrator with full access'),
('Storekeeper', 'Manages stock and inventory'),
('Kitchen', 'Kitchen department'),
('Bar', 'Bar department'),
('Housekeeping', 'Housekeeping department'),
('Maintenance', 'Maintenance department'),
('Accounts', 'Accounts and cost tracking'),
('Manager', 'Store manager');

-- Insert Stores
INSERT IGNORE INTO stores (store_name, store_code, location, description) VALUES
('Kitchen Store', 'KIT001', 'Ground Floor Kitchen', 'Main kitchen storage'),
('Bar Store', 'BAR001', 'Ground Floor Bar', 'Beverage and bar supplies'),
('Housekeeping Store', 'HNK001', 'Basement', 'Cleaning and laundry supplies'),
('Maintenance Store', 'MNT001', 'Basement', 'Tools and maintenance supplies');

-- Insert Departments
INSERT IGNORE INTO departments (dept_name, dept_code) VALUES
('Kitchen', 'KIT'),
('Bar', 'BAR'),
('Housekeeping', 'HNK'),
('Maintenance', 'MNT'),
('Front Office', 'FRO');

-- Insert Categories
INSERT IGNORE INTO categories (category_name, description) VALUES
('Food Items', 'Cooking ingredients and food'),
('Beverages', 'Drinks and beverage supplies'),
('Cleaning Supplies', 'Cleaning and maintenance chemicals'),
('Utensils & Equipment', 'Kitchen utensils and equipment'),
('Linen & Fabric', 'Bed linen and fabric items'),
('Tools & Hardware', 'Maintenance tools and hardware'),
('Packaging Materials', 'Boxes, bags, and packaging');

-- Insert Sample Products
INSERT IGNORE INTO products (product_name, product_code, category_id, unit_of_measure, reorder_level, reorder_quantity) VALUES
('Cooking Oil - 5L', 'OIL-001', 1, 'Liters', 10, 50),
('Salt - 1KG', 'SAL-001', 1, 'KG', 5, 20),
('Sugar - 1KG', 'SUG-001', 1, 'KG', 5, 20),
('Flour - 1KG', 'FLO-001', 1, 'KG', 10, 50),
('Red Wine - 750ml', 'WIN-001', 2, 'Bottles', 20, 100),
('Beer - 330ml', 'BEE-001', 2, 'Bottles', 30, 150),
('Floor Cleaner - 5L', 'CLN-001', 3, 'Liters', 5, 20),
('Disinfectant - 1L', 'DIS-001', 3, 'Liters', 10, 30),
('Chef Knife', 'KNF-001', 4, 'Pieces', 3, 10),
('Bed Sheets - Queen', 'BED-001', 5, 'Pieces', 10, 30),
('Hand Soap - 500ml', 'SOP-001', 3, 'Bottles', 15, 50),
('Wrench Set', 'WRN-001', 6, 'Set', 2, 5);

-- Create admin user (password: admin123 - should be hashed in practice)
INSERT IGNORE INTO users (username, email, password, full_name, role_id) VALUES
('admin', 'admin@hotel.com', SHA2('admin123', 256), 'System Admin', 1),
('storekeeper1', 'storekeeper@hotel.com', SHA2('store123', 256), 'John Storekeeper', 2);

-- ====================================
-- INDEXES FOR PERFORMANCE
-- ====================================
CREATE INDEX IF NOT EXISTS idx_grn_date ON grn(receipt_date);
CREATE INDEX IF NOT EXISTS idx_requisition_date ON requisitions(requested_date);
CREATE INDEX IF NOT EXISTS idx_issue_date ON stock_issues(issue_date);
CREATE INDEX IF NOT EXISTS idx_stock_level ON stock(quantity_on_hand);
CREATE INDEX IF NOT EXISTS idx_product_code ON products(product_code);
