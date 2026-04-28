-- Stores backup generated at 2026-04-23 12:15:18 UTC
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for `adjustment_items`
-- ----------------------------
DROP TABLE IF EXISTS `adjustment_items`;
CREATE TABLE `adjustment_items` (
  `adj_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `adjustment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `reason_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`adj_item_id`),
  KEY `adjustment_id` (`adjustment_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `adjustment_items_ibfk_1` FOREIGN KEY (`adjustment_id`) REFERENCES `stock_adjustments` (`adjustment_id`) ON DELETE CASCADE,
  CONSTRAINT `adjustment_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `adjustment_items`
-- ----------------------------

-- ----------------------------
-- Table structure for `audit_log`
-- ----------------------------
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_action_date` (`action_date`),
  KEY `idx_user_action` (`user_id`,`action_date`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `audit_log`
-- ----------------------------

-- ----------------------------
-- Table structure for `backup_history`
-- ----------------------------
DROP TABLE IF EXISTS `backup_history`;
CREATE TABLE `backup_history` (
  `backup_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size_bytes` bigint(20) NOT NULL DEFAULT 0,
  `trigger_type` enum('manual','scheduled') NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `started_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `initiated_by` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`backup_id`),
  KEY `initiated_by` (`initiated_by`),
  KEY `idx_backup_history_started_at` (`started_at`),
  KEY `idx_backup_history_status` (`status`),
  KEY `idx_backup_history_trigger` (`trigger_type`),
  CONSTRAINT `backup_history_ibfk_1` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `backup_history`
-- ----------------------------
INSERT INTO `backup_history` (`backup_id`, `file_name`, `file_path`, `file_size_bytes`, `trigger_type`, `status`, `started_at`, `completed_at`, `initiated_by`, `error_message`, `created_at`) VALUES
('1', 'pending', '', '0', 'manual', 'failed', '2026-04-23 12:15:18', NULL, '1', NULL, '2026-04-23 14:15:18');

-- ----------------------------
-- Table structure for `backup_settings`
-- ----------------------------
DROP TABLE IF EXISTS `backup_settings`;
CREATE TABLE `backup_settings` (
  `setting_id` tinyint(4) NOT NULL DEFAULT 1,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `frequency` enum('daily','weekly','monthly') NOT NULL DEFAULT 'daily',
  `run_time` time NOT NULL DEFAULT '02:00:00',
  `day_of_week` tinyint(4) NOT NULL DEFAULT 1,
  `day_of_month` tinyint(4) NOT NULL DEFAULT 1,
  `retention_days` int(11) NOT NULL DEFAULT 30,
  `schedule_token` varchar(64) NOT NULL,
  `last_run_at` datetime DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `backup_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `backup_settings`
-- ----------------------------
INSERT INTO `backup_settings` (`setting_id`, `is_enabled`, `frequency`, `run_time`, `day_of_week`, `day_of_month`, `retention_days`, `schedule_token`, `last_run_at`, `next_run_at`, `updated_by`, `updated_at`) VALUES
('1', '0', 'daily', '02:00:00', '1', '1', '30', '450b91273b645151193342608cf52456314d9cabee13d0bf', NULL, NULL, NULL, '2026-04-23 14:14:53');

-- ----------------------------
-- Table structure for `batch_tracking`
-- ----------------------------
DROP TABLE IF EXISTS `batch_tracking`;
CREATE TABLE `batch_tracking` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `grn_item_id` int(11) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `status` enum('available','expired','consumed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`batch_id`),
  KEY `product_id` (`product_id`),
  KEY `store_id` (`store_id`),
  KEY `grn_item_id` (`grn_item_id`),
  CONSTRAINT `batch_tracking_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `batch_tracking_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `batch_tracking_ibfk_3` FOREIGN KEY (`grn_item_id`) REFERENCES `grn_items` (`grn_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `batch_tracking`
-- ----------------------------

-- ----------------------------
-- Table structure for `categories`
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `categories`
-- ----------------------------
INSERT INTO `categories` (`category_id`, `category_name`, `description`, `status`, `created_at`) VALUES
('1', 'Food Items', 'Cooking ingredients and food', 'active', '2026-04-23 12:56:24'),
('2', 'Beverages', 'Drinks and beverage supplies', 'active', '2026-04-23 12:56:24'),
('3', 'Cleaning Supplies', 'Cleaning and maintenance chemicals', 'active', '2026-04-23 12:56:24'),
('4', 'Utensils & Equipment', 'Kitchen utensils and equipment', 'active', '2026-04-23 12:56:24'),
('5', 'Linen & Fabric', 'Bed linen and fabric items', 'active', '2026-04-23 12:56:24'),
('6', 'Tools & Hardware', 'Maintenance tools and hardware', 'active', '2026-04-23 12:56:24'),
('7', 'Packaging Materials', 'Boxes, bags, and packaging', 'active', '2026-04-23 12:56:24');

-- ----------------------------
-- Table structure for `departments`
-- ----------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `head_user_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `monthly_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `weekly_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`dept_id`),
  UNIQUE KEY `dept_code` (`dept_code`),
  KEY `head_user_id` (`head_user_id`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `departments`
-- ----------------------------
INSERT INTO `departments` (`dept_id`, `dept_name`, `dept_code`, `head_user_id`, `status`, `monthly_budget`, `weekly_budget`, `created_at`) VALUES
('1', 'Kitchen', 'KIT', NULL, 'active', '0.00', '0.00', '2026-04-23 12:56:24'),
('2', 'Bar', 'BAR', NULL, 'active', '0.00', '0.00', '2026-04-23 12:56:24'),
('3', 'Housekeeping', 'HNK', NULL, 'active', '0.00', '0.00', '2026-04-23 12:56:24'),
('4', 'Maintenance', 'MNT', NULL, 'active', '0.00', '0.00', '2026-04-23 12:56:24'),
('5', 'Front Office', 'FRO', NULL, 'active', '0.00', '0.00', '2026-04-23 12:56:24');

-- ----------------------------
-- Table structure for `grn`
-- ----------------------------
DROP TABLE IF EXISTS `grn`;
CREATE TABLE `grn` (
  `grn_id` int(11) NOT NULL AUTO_INCREMENT,
  `grn_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `receipt_time` time DEFAULT NULL,
  `delivery_note_ref` varchar(100) DEFAULT NULL,
  `invoice_reference` varchar(100) DEFAULT NULL,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','received','verified') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`grn_id`),
  UNIQUE KEY `grn_number` (`grn_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `store_id` (`store_id`),
  KEY `received_by` (`received_by`),
  KEY `idx_grn_date` (`receipt_date`),
  CONSTRAINT `grn_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `grn_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `grn_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `grn`
-- ----------------------------

-- ----------------------------
-- Table structure for `grn_items`
-- ----------------------------
DROP TABLE IF EXISTS `grn_items`;
CREATE TABLE `grn_items` (
  `grn_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `grn_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_expected` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `condition_status` enum('good','damaged','partial') DEFAULT 'good',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`grn_item_id`),
  KEY `grn_id` (`grn_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `grn_items_ibfk_1` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`grn_id`) ON DELETE CASCADE,
  CONSTRAINT `grn_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `grn_items`
-- ----------------------------

-- ----------------------------
-- Table structure for `password_reset_tokens`
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_id`),
  KEY `idx_reset_token_hash` (`token_hash`),
  KEY `idx_reset_token_user` (`user_id`),
  KEY `idx_reset_token_expires` (`expires_at`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `password_reset_tokens`
-- ----------------------------

-- ----------------------------
-- Table structure for `pos_sales_usage`
-- ----------------------------
DROP TABLE IF EXISTS `pos_sales_usage`;
CREATE TABLE `pos_sales_usage` (
  `pos_usage_id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_source` varchar(50) NOT NULL DEFAULT 'manual',
  `sale_reference` varchar(100) NOT NULL,
  `product_id` int(11) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `quantity_sold` decimal(12,3) NOT NULL DEFAULT 0.000,
  `consumed_quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cogs` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sale_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pos_usage_id`),
  KEY `store_id` (`store_id`),
  KEY `idx_pos_sale_date` (`sale_date`),
  KEY `idx_pos_product` (`product_id`),
  CONSTRAINT `pos_sales_usage_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `pos_sales_usage_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `pos_sales_usage`
-- ----------------------------

-- ----------------------------
-- Table structure for `products`
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `category_id` int(11) NOT NULL,
  `unit_of_measure` varchar(20) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `reorder_quantity` int(11) DEFAULT 50,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_id` (`category_id`),
  KEY `idx_product_code` (`product_code`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `products`
-- ----------------------------
INSERT INTO `products` (`product_id`, `product_name`, `product_code`, `category_id`, `unit_of_measure`, `reorder_level`, `reorder_quantity`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Cooking Oil - 5L', 'OIL-001', '1', 'Liters', '10', '50', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('2', 'Salt - 1KG', 'SAL-001', '1', 'KG', '5', '20', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('3', 'Sugar - 1KG', 'SUG-001', '1', 'KG', '5', '20', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('4', 'Flour - 1KG', 'FLO-001', '1', 'KG', '10', '50', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('5', 'Red Wine - 750ml', 'WIN-001', '2', 'Bottles', '20', '100', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('6', 'Beer - 330ml', 'BEE-001', '2', 'Bottles', '30', '150', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('7', 'Floor Cleaner - 5L', 'CLN-001', '3', 'Liters', '5', '20', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('8', 'Disinfectant - 1L', 'DIS-001', '3', 'Liters', '10', '30', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('9', 'Chef Knife', 'KNF-001', '4', 'Pieces', '3', '10', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('10', 'Bed Sheets - Queen', 'BED-001', '5', 'Pieces', '10', '30', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('11', 'Hand Soap - 500ml', 'SOP-001', '3', 'Bottles', '15', '50', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('12', 'Wrench Set', 'WRN-001', '6', 'Set', '2', '5', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24');

-- ----------------------------
-- Table structure for `reorder_alerts`
-- ----------------------------
DROP TABLE IF EXISTS `reorder_alerts`;
CREATE TABLE `reorder_alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `alert_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_date` datetime DEFAULT NULL,
  `status` enum('new','acknowledged','ordered','received') DEFAULT 'new',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`alert_id`),
  KEY `product_id` (`product_id`),
  KEY `store_id` (`store_id`),
  KEY `acknowledged_by` (`acknowledged_by`),
  CONSTRAINT `reorder_alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `reorder_alerts_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `reorder_alerts_ibfk_3` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `reorder_alerts`
-- ----------------------------

-- ----------------------------
-- Table structure for `requisition_items`
-- ----------------------------
DROP TABLE IF EXISTS `requisition_items`;
CREATE TABLE `requisition_items` (
  `req_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_requested` int(11) NOT NULL,
  `quantity_approved` int(11) DEFAULT NULL,
  `quantity_issued` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`req_item_id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`requisition_id`) ON DELETE CASCADE,
  CONSTRAINT `requisition_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `requisition_items`
-- ----------------------------

-- ----------------------------
-- Table structure for `requisitions`
-- ----------------------------
DROP TABLE IF EXISTS `requisitions`;
CREATE TABLE `requisitions` (
  `requisition_id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_number` varchar(50) NOT NULL,
  `department_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_date` datetime DEFAULT current_timestamp(),
  `status` enum('draft','pending','approved','rejected','issued') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`requisition_id`),
  UNIQUE KEY `requisition_number` (`requisition_number`),
  KEY `department_id` (`department_id`),
  KEY `store_id` (`store_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_requisition_date` (`requested_date`),
  CONSTRAINT `requisitions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`dept_id`),
  CONSTRAINT `requisitions_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `requisitions_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `requisitions_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `requisitions`
-- ----------------------------

-- ----------------------------
-- Table structure for `restore_history`
-- ----------------------------
DROP TABLE IF EXISTS `restore_history`;
CREATE TABLE `restore_history` (
  `restore_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_type` enum('stored_backup','upload') NOT NULL,
  `source_label` varchar(255) NOT NULL,
  `source_path` varchar(500) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `started_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `initiated_by` int(11) DEFAULT NULL,
  `safety_backup_id` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`restore_id`),
  KEY `initiated_by` (`initiated_by`),
  KEY `safety_backup_id` (`safety_backup_id`),
  KEY `idx_restore_history_started_at` (`started_at`),
  KEY `idx_restore_history_status` (`status`),
  KEY `idx_restore_history_source_type` (`source_type`),
  CONSTRAINT `restore_history_ibfk_1` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `restore_history_ibfk_2` FOREIGN KEY (`safety_backup_id`) REFERENCES `backup_history` (`backup_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `restore_history`
-- ----------------------------
INSERT INTO `restore_history` (`restore_id`, `source_type`, `source_label`, `source_path`, `status`, `started_at`, `completed_at`, `initiated_by`, `safety_backup_id`, `error_message`, `created_at`) VALUES
('1', 'upload', 'stores_backup_manual_20260423_121358.sql', 'C:\\xampp\\tmp\\phpD8F8.tmp', 'failed', '2026-04-23 12:15:18', NULL, '1', NULL, NULL, '2026-04-23 14:15:18');

-- ----------------------------
-- Table structure for `role_permission_overrides`
-- ----------------------------
DROP TABLE IF EXISTS `role_permission_overrides`;
CREATE TABLE `role_permission_overrides` (
  `override_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`override_id`),
  UNIQUE KEY `unique_role_permission` (`role_name`,`permission`),
  KEY `idx_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `role_permission_overrides`
-- ----------------------------

-- ----------------------------
-- Table structure for `roles`
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `roles`
-- ----------------------------
INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
('1', 'Admin', 'System administrator with full access', '2026-04-23 12:56:24'),
('2', 'Storekeeper', 'Manages stock and inventory', '2026-04-23 12:56:24'),
('3', 'Kitchen', 'Kitchen department', '2026-04-23 12:56:24'),
('4', 'Bar', 'Bar department', '2026-04-23 12:56:24'),
('5', 'Housekeeping', 'Housekeeping department', '2026-04-23 12:56:24'),
('6', 'Maintenance', 'Maintenance department', '2026-04-23 12:56:24'),
('7', 'Accounts', 'Accounts and cost tracking', '2026-04-23 12:56:24'),
('8', 'Manager', 'Store manager', '2026-04-23 12:56:24');

-- ----------------------------
-- Table structure for `stock`
-- ----------------------------
DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `quantity_on_hand` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 0,
  `last_counted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`stock_id`),
  UNIQUE KEY `unique_product_store` (`product_id`,`store_id`),
  KEY `store_id` (`store_id`),
  KEY `idx_stock_level` (`quantity_on_hand`),
  CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `stock_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock`
-- ----------------------------

-- ----------------------------
-- Table structure for `stock_adjustments`
-- ----------------------------
DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE `stock_adjustments` (
  `adjustment_id` int(11) NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) NOT NULL,
  `store_id` int(11) NOT NULL,
  `adjustment_reason` enum('damage','loss','correction','count_variance','recall') NOT NULL,
  `adjusted_by` int(11) NOT NULL,
  `adjustment_date` datetime DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`adjustment_id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  KEY `store_id` (`store_id`),
  KEY `adjusted_by` (`adjusted_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `stock_adjustments_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock_adjustments`
-- ----------------------------

-- ----------------------------
-- Table structure for `stock_issue_items`
-- ----------------------------
DROP TABLE IF EXISTS `stock_issue_items`;
CREATE TABLE `stock_issue_items` (
  `issue_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_issued` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`issue_item_id`),
  KEY `issue_id` (`issue_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_issue_items_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `stock_issues` (`issue_id`) ON DELETE CASCADE,
  CONSTRAINT `stock_issue_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock_issue_items`
-- ----------------------------

-- ----------------------------
-- Table structure for `stock_issues`
-- ----------------------------
DROP TABLE IF EXISTS `stock_issues`;
CREATE TABLE `stock_issues` (
  `issue_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_number` varchar(50) NOT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `issued_by` int(11) NOT NULL,
  `issue_date` datetime DEFAULT current_timestamp(),
  `received_by` int(11) DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `status` enum('issued','received','cancelled') DEFAULT 'issued',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`issue_id`),
  UNIQUE KEY `issue_number` (`issue_number`),
  KEY `requisition_id` (`requisition_id`),
  KEY `store_id` (`store_id`),
  KEY `department_id` (`department_id`),
  KEY `issued_by` (`issued_by`),
  KEY `received_by` (`received_by`),
  KEY `idx_issue_date` (`issue_date`),
  CONSTRAINT `stock_issues_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`requisition_id`),
  CONSTRAINT `stock_issues_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `stock_issues_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`dept_id`),
  CONSTRAINT `stock_issues_ibfk_4` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `stock_issues_ibfk_5` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock_issues`
-- ----------------------------

-- ----------------------------
-- Table structure for `stock_transactions`
-- ----------------------------
DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE `stock_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `transaction_type` enum('receipt','issue','adjustment','return','consumption') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `quantity_change` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_value` decimal(12,2) DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `store_id` (`store_id`),
  KEY `performed_by` (`performed_by`),
  KEY `idx_product_store` (`product_id`,`store_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `stock_transactions_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock_transactions`
-- ----------------------------

-- ----------------------------
-- Table structure for `stores`
-- ----------------------------
DROP TABLE IF EXISTS `stores`;
CREATE TABLE `stores` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_name` varchar(100) NOT NULL,
  `store_code` varchar(20) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `responsible_user_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`store_id`),
  UNIQUE KEY `store_code` (`store_code`),
  KEY `responsible_user_id` (`responsible_user_id`),
  CONSTRAINT `stores_ibfk_1` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stores`
-- ----------------------------
INSERT INTO `stores` (`store_id`, `store_name`, `store_code`, `location`, `responsible_user_id`, `description`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Kitchen Store', 'KIT001', 'Ground Floor Kitchen', NULL, 'Main kitchen storage', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('2', 'Bar Store', 'BAR001', 'Ground Floor Bar', NULL, 'Beverage and bar supplies', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('3', 'Housekeeping Store', 'HNK001', 'Basement', NULL, 'Cleaning and laundry supplies', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24'),
('4', 'Maintenance Store', 'MNT001', 'Basement', NULL, 'Tools and maintenance supplies', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24');

-- ----------------------------
-- Table structure for `suppliers`
-- ----------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `suppliers`
-- ----------------------------

-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `users`
-- ----------------------------
INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `role_id`, `status`, `created_at`, `updated_at`) VALUES
('1', 'admin', 'admin@hotel.com', '$2y$10$THkdV/HN/F.6pra6hiVJxuKhcUQ1XwveDBngPQLanxTJRx9Sw15cS', 'System Admin', '1', 'active', '2026-04-23 12:56:24', '2026-04-23 12:57:43'),
('2', 'storekeeper1', 'storekeeper@hotel.com', '8b3d9bca7c134a9190277204379941460eb33c39868309401f02fa8e0391d4a7', 'John Storekeeper', '2', 'active', '2026-04-23 12:56:24', '2026-04-23 12:56:24');

SET FOREIGN_KEY_CHECKS = 1;
