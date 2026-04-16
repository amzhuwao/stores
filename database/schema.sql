-- Hotel Stores Management System Database Schema
-- Created: April 15, 2026

-- ====================================
-- 1. USERS & ROLES
-- ====================================
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
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

CREATE TABLE password_reset_tokens (
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

-- ====================================
-- 2. STORES (Kitchen, Bar, Housekeeping, Maintenance)
-- ====================================
CREATE TABLE stores (
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
CREATE TABLE departments (
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
CREATE TABLE suppliers (
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
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ====================================
-- 6. PRODUCTS/ITEMS
-- ====================================
CREATE TABLE products (
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
CREATE TABLE stock (
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
CREATE TABLE grn (
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
CREATE TABLE grn_items (
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
CREATE TABLE requisitions (
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
CREATE TABLE requisition_items (
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
CREATE TABLE stock_issues (
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
CREATE TABLE stock_issue_items (
    issue_item_id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_issued INT NOT NULL,
    batch_number VARCHAR(50),
    expiry_date DATE,
    unit_price DECIMAL(10, 2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES stock_issues(issue_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ====================================
-- 14. STOCK TRANSACTIONS (Audit trail)
-- ====================================
CREATE TABLE stock_transactions (
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

CREATE TABLE pos_sales_usage (
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
-- 15. BATCH & EXPIRY TRACKING
-- ====================================
CREATE TABLE batch_tracking (
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
-- 16. STOCK ADJUSTMENTS
-- ====================================
CREATE TABLE stock_adjustments (
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
-- 17. ADJUSTMENT ITEMS
-- ====================================
CREATE TABLE adjustment_items (
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
-- 18. REORDER ALERTS
-- ====================================
CREATE TABLE reorder_alerts (
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
-- 19. AUDIT LOG
-- ====================================
CREATE TABLE audit_log (
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

-- ====================================
-- INITIAL DATA
-- ====================================

-- Insert Roles
INSERT INTO roles (role_name, description) VALUES
('Admin', 'System administrator with full access'),
('Storekeeper', 'Manages stock and inventory'),
('Kitchen', 'Kitchen department'),
('Bar', 'Bar department'),
('Housekeeping', 'Housekeeping department'),
('Maintenance', 'Maintenance department'),
('Accounts', 'Accounts and cost tracking'),
('Manager', 'Store manager');

-- Insert Stores
INSERT INTO stores (store_name, store_code, location, description) VALUES
('Kitchen Store', 'KIT001', 'Ground Floor Kitchen', 'Main kitchen storage'),
('Bar Store', 'BAR001', 'Ground Floor Bar', 'Beverage and bar supplies'),
('Housekeeping Store', 'HNK001', 'Basement', 'Cleaning and laundry supplies'),
('Maintenance Store', 'MNT001', 'Basement', 'Tools and maintenance supplies');

-- Insert Departments
INSERT INTO departments (dept_name, dept_code) VALUES
('Kitchen', 'KIT'),
('Bar', 'BAR'),
('Housekeeping', 'HNK'),
('Maintenance', 'MNT'),
('Front Office', 'FRO');

-- Insert Categories
INSERT INTO categories (category_name, description) VALUES
('Food Items', 'Cooking ingredients and food'),
('Beverages', 'Drinks and beverage supplies'),
('Cleaning Supplies', 'Cleaning and maintenance chemicals'),
('Utensils & Equipment', 'Kitchen utensils and equipment'),
('Linen & Fabric', 'Bed linen and fabric items'),
('Tools & Hardware', 'Maintenance tools and hardware'),
('Packaging Materials', 'Boxes, bags, and packaging');

-- Insert Sample Products
INSERT INTO products (product_name, product_code, category_id, unit_of_measure, reorder_level, reorder_quantity) VALUES
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
INSERT INTO users (username, email, password, full_name, role_id) VALUES
('admin', 'admin@hotel.com', SHA2('admin123', 256), 'System Admin', 1),
('storekeeper1', 'storekeeper@hotel.com', SHA2('store123', 256), 'John Storekeeper', 2);

-- ====================================
-- INDEXES FOR PERFORMANCE
-- ====================================
CREATE INDEX idx_grn_date ON grn(receipt_date);
CREATE INDEX idx_requisition_date ON requisitions(requested_date);
CREATE INDEX idx_issue_date ON stock_issues(issue_date);
CREATE INDEX idx_stock_level ON stock(quantity_on_hand);
CREATE INDEX idx_product_code ON products(product_code);
