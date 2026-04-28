-- Stores backup generated at 2026-04-27 15:33:26 UTC
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
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `audit_log`
-- ----------------------------
INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `ip_address`, `action_date`) VALUES
('1', '1', 'Update', 'product', '5', '{\"product_id\":5,\"product_name\":\"Red Wine - 750ml\",\"product_code\":\"WIN-001\",\"category_id\":2,\"unit_of_measure\":\"Bottles\",\"reorder_level\":20,\"reorder_quantity\":100,\"status\":\"active\",\"created_at\":\"2026-04-20 10:28:52\",\"updated_at\":\"2026-04-20 10:28:52\",\"category_name\":\"Beverages\"}', '{\"product_name\":\"Red Wine - 750ml\",\"product_code\":\"WIN-001\",\"category_id\":2,\"unit_of_measure\":\"Bottles\",\"reorder_level\":2,\"reorder_quantity\":5}', '::1', '2026-04-22 17:31:21'),
('2', '1', 'Update', 'product', '3', '{\"product_id\":3,\"product_name\":\"Sugar - 1KG\",\"product_code\":\"SUG-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":5,\"reorder_quantity\":20,\"status\":\"active\",\"created_at\":\"2026-04-20 10:28:52\",\"updated_at\":\"2026-04-20 10:28:52\",\"category_name\":\"Food Items\"}', '{\"product_name\":\"Sugar - 1KG\",\"product_code\":\"SUG-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":5,\"reorder_quantity\":10}', '::1', '2026-04-22 17:31:34'),
('3', '1', 'Update', 'product', '2', '{\"product_id\":2,\"product_name\":\"Salt - 1KG\",\"product_code\":\"SAL-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":5,\"reorder_quantity\":20,\"status\":\"active\",\"created_at\":\"2026-04-20 10:28:52\",\"updated_at\":\"2026-04-20 10:28:52\",\"category_name\":\"Food Items\"}', '{\"product_name\":\"Salt - 1KG\",\"product_code\":\"SAL-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":2,\"reorder_quantity\":10}', '::1', '2026-04-22 17:31:49'),
('4', '1', 'Update', 'product', '4', '{\"product_id\":4,\"product_name\":\"Flour - 1KG\",\"product_code\":\"FLO-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":10,\"reorder_quantity\":50,\"status\":\"active\",\"created_at\":\"2026-04-20 10:28:52\",\"updated_at\":\"2026-04-20 10:28:52\",\"category_name\":\"Food Items\"}', '{\"product_name\":\"Flour - 1KG\",\"product_code\":\"FLO-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":2,\"reorder_quantity\":10}', '::1', '2026-04-22 17:32:09'),
('5', '1', 'Create', 'product', '13', '', '{\"product_name\":\"Washing Powder -2kg\",\"product_code\":\"WPD-004\",\"category_id\":3,\"unit_of_measure\":\"KG\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:15:53'),
('6', '1', 'Create', 'product', '14', '', '{\"product_name\":\"Toilet Cleaner - 750ml\",\"product_code\":\"TLC-004\",\"category_id\":3,\"unit_of_measure\":\"Liters\",\"reorder_level\":4,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:16:50'),
('7', '1', 'Create', 'product', '15', '', '{\"product_name\":\"Handy Andy - 750ml\",\"product_code\":\"HNDY-002\",\"category_id\":3,\"unit_of_measure\":\"Bottles\",\"reorder_level\":3,\"reorder_quantity\":6,\"status\":\"active\"}', '::1', '2026-04-22 22:17:27'),
('8', '1', 'Create', 'product', '16', '', '{\"product_name\":\"Kitchen Towel\",\"product_code\":\"KTH-002\",\"category_id\":3,\"unit_of_measure\":\"Roll\",\"reorder_level\":5,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:18:35'),
('9', '1', 'Create', 'product', '17', '', '{\"product_name\":\"TISSUE -50 rolls\",\"product_code\":\"TSS-002\",\"category_id\":3,\"unit_of_measure\":\"Roll\",\"reorder_level\":20,\"reorder_quantity\":100,\"status\":\"active\"}', '::1', '2026-04-22 22:19:18'),
('10', '1', 'Create', 'product', '18', '', '{\"product_name\":\"Flip Chart -10\",\"product_code\":\"FLP-002\",\"category_id\":4,\"unit_of_measure\":\"Packets\",\"reorder_level\":3,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:20:07'),
('11', '1', 'Create', 'product', '19', '', '{\"product_name\":\"Bond Paper - 500\",\"product_code\":\"BDP-004\",\"category_id\":4,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":4,\"status\":\"active\"}', '::1', '2026-04-22 22:20:59'),
('12', '1', 'Create', 'product', '20', '', '{\"product_name\":\"Baking sheet\",\"product_code\":\"BKS-001\",\"category_id\":4,\"unit_of_measure\":\"Roll\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:21:38'),
('13', '1', 'Create', 'product', '21', '', '{\"product_name\":\"Sanitary Pads\",\"product_code\":\"SPD-002\",\"category_id\":3,\"unit_of_measure\":\"Packets\",\"reorder_level\":4,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:22:15'),
('14', '1', 'Create', 'product', '22', '', '{\"product_name\":\"Tile Cleaner - 5ltr\",\"product_code\":\"TCLN-001\",\"category_id\":3,\"unit_of_measure\":\"Bottles\",\"reorder_level\":1,\"reorder_quantity\":3,\"status\":\"active\"}', '::1', '2026-04-22 22:22:59'),
('15', '1', 'Create', 'product', '23', '', '{\"product_name\":\"Water- BOTTLED\",\"product_code\":\"WTR-003\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":50,\"reorder_quantity\":150,\"status\":\"active\"}', '::1', '2026-04-22 22:23:54'),
('16', '1', 'Create', 'product', '24', '', '{\"product_name\":\"Takeaway Cup\",\"product_code\":\"TKC-001\",\"category_id\":7,\"unit_of_measure\":\"Bottles\",\"reorder_level\":50,\"reorder_quantity\":100,\"status\":\"active\"}', '::1', '2026-04-22 22:24:45'),
('17', '1', 'Create', 'product', '25', '', '{\"product_name\":\"Fruit Juice - Victoria (1ltr)\",\"product_code\":\"FRTJ-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:25:21'),
('18', '1', 'Create', 'product', '26', '', '{\"product_name\":\"Milk- 1L\",\"product_code\":\"MLK-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":6,\"reorder_quantity\":12,\"status\":\"active\"}', '::1', '2026-04-22 22:25:48'),
('19', '1', 'Create', 'product', '27', '', '{\"product_name\":\"Juice boxes- 200ml\",\"product_code\":\"JBX-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":6,\"reorder_quantity\":12,\"status\":\"active\"}', '::1', '2026-04-22 22:26:18'),
('20', '1', 'Create', 'product', '28', '', '{\"product_name\":\"Takeaway Tray- PLASTIC\",\"product_code\":\"TKT-001\",\"category_id\":7,\"unit_of_measure\":\"Packets\",\"reorder_level\":60,\"reorder_quantity\":150,\"status\":\"active\"}', '::1', '2026-04-22 22:27:10'),
('21', '1', 'Create', 'product', '29', '', '{\"product_name\":\"Takeaway Box - PLASTIC\",\"product_code\":\"TKB-001\",\"category_id\":7,\"unit_of_measure\":\"Packets\",\"reorder_level\":100,\"reorder_quantity\":150,\"status\":\"active\"}', '::1', '2026-04-22 22:27:40'),
('22', '1', 'Create', 'product', '30', '', '{\"product_name\":\"Cornflakes\",\"product_code\":\"CRN-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:28:13'),
('23', '1', 'Create', 'product', '31', '', '{\"product_name\":\"Sugar (White) - 2KG\",\"product_code\":\"SUGW-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:28:54'),
('24', '1', 'Update', 'product', '3', '{\"product_id\":3,\"product_name\":\"Sugar - 1KG\",\"product_code\":\"SUG-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\",\"created_at\":\"2026-04-20 10:28:52\",\"updated_at\":\"2026-04-22 09:31:34\",\"category_name\":\"Food Items\"}', '{\"product_name\":\"Sugar - 2KG\",\"product_code\":\"SUG-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":5,\"reorder_quantity\":10}', '::1', '2026-04-22 22:29:11'),
('25', '1', 'Create', 'product', '32', '', '{\"product_name\":\"Cerevita\",\"product_code\":\"CRV-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:30:08'),
('26', '1', 'Create', 'product', '33', '', '{\"product_name\":\"Tanganda Tea\",\"product_code\":\"TNDT-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":2,\"reorder_quantity\":4,\"status\":\"active\"}', '::1', '2026-04-22 22:31:06'),
('27', '1', 'Create', 'product', '34', '', '{\"product_name\":\"Roibos Tea\",\"product_code\":\"RBS-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":2,\"reorder_quantity\":3,\"status\":\"active\"}', '::1', '2026-04-22 22:31:57'),
('28', '1', 'Create', 'product', '35', '', '{\"product_name\":\"Cappuccino\",\"product_code\":\"CPP-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:32:23'),
('29', '1', 'Create', 'product', '36', '', '{\"product_name\":\"Sweetener\",\"product_code\":\"SWT-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:33:16'),
('30', '1', 'Create', 'product', '37', '', '{\"product_name\":\"Mints\",\"product_code\":\"MNT-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:33:46'),
('31', '1', 'Create', 'product', '38', '', '{\"product_name\":\"Veetbix\",\"product_code\":\"VTB-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:34:20'),
('32', '1', 'Create', 'product', '39', '', '{\"product_name\":\"Honey -400g\",\"product_code\":\"HNY-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":3,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:34:47'),
('33', '1', 'Create', 'product', '40', '', '{\"product_name\":\"JAM - 400G\",\"product_code\":\"JAM-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:35:27'),
('34', '1', 'Create', 'product', '41', '', '{\"product_name\":\"Air Freshener\",\"product_code\":\"AFS-002\",\"category_id\":3,\"unit_of_measure\":\"Bottles\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:36:03'),
('35', '1', 'Create', 'product', '42', '', '{\"product_name\":\"Hot Chocolate\",\"product_code\":\"HCT-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:36:26'),
('36', '1', 'Create', 'product', '43', '', '{\"product_name\":\"Ricoffy\",\"product_code\":\"RIC-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:36:54'),
('37', '1', 'Create', 'product', '44', '', '{\"product_name\":\"Condensed Milk\",\"product_code\":\"CML-001\",\"category_id\":1,\"unit_of_measure\":\"Cans\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:37:24'),
('38', '1', 'Create', 'product', '45', '', '{\"product_name\":\"Custard\",\"product_code\":\"CSTD-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:37:52'),
('39', '1', 'Create', 'product', '46', '', '{\"product_name\":\"LAYS- 30pac\",\"product_code\":\"LAY-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:38:20'),
('40', '1', 'Create', 'product', '47', '', '{\"product_name\":\"Spaghetti - 450g\",\"product_code\":\"SPG-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":3,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:38:52'),
('41', '1', 'Create', 'product', '48', '', '{\"product_name\":\"Rice - 5KG\",\"product_code\":\"RICE-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":0,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 22:39:26'),
('42', '1', 'Create', 'product', '49', '', '{\"product_name\":\"Rice - 2kg\",\"product_code\":\"RCE-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:40:12'),
('43', '1', 'Create', 'product', '50', '', '{\"product_name\":\"Eggs\",\"product_code\":\"EGG-001\",\"category_id\":1,\"unit_of_measure\":\"Tray\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:40:41'),
('44', '1', 'Create', 'product', '51', '', '{\"product_name\":\"Envelopes - 50\",\"product_code\":\"ENV-004\",\"category_id\":7,\"unit_of_measure\":\"Packets\",\"reorder_level\":10,\"reorder_quantity\":50,\"status\":\"active\"}', '::1', '2026-04-22 22:41:02'),
('45', '1', 'Create', 'product', '52', '', '{\"product_name\":\"Jelly- ROYAL\",\"product_code\":\"JLY-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:41:26'),
('46', '1', 'Create', 'product', '53', '', '{\"product_name\":\"BBQ Seasoning- 1kg\",\"product_code\":\"BBQ-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 22:42:02'),
('47', '1', 'Create', 'product', '54', '', '{\"product_name\":\"Five Roses\",\"product_code\":\"FRS-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 22:42:31'),
('48', '1', 'Create', 'product', '55', '', '{\"product_name\":\"Garlic Seasoning-1kg\",\"product_code\":\"GLS-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 22:42:56'),
('49', '1', 'Create', 'product', '56', '', '{\"product_name\":\"Pellegrini - 125ml\",\"product_code\":\"PLG-001\",\"category_id\":2,\"unit_of_measure\":\"Bottles\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:43:31'),
('50', '1', 'Create', 'product', '57', '', '{\"product_name\":\"Potatoes\",\"product_code\":\"POT-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:44:07'),
('51', '1', 'Create', 'product', '58', '', '{\"product_name\":\"Hand Soap - 300ml\",\"product_code\":\"HNDW-002\",\"category_id\":3,\"unit_of_measure\":\"Bottles\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:44:58'),
('52', '1', 'Create', 'product', '59', '', '{\"product_name\":\"Fruit chutney - 420g\",\"product_code\":\"FRT-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:45:56'),
('53', '1', 'Create', 'product', '60', '', '{\"product_name\":\"Salad Dressing\",\"product_code\":\"SLD-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":2,\"reorder_quantity\":4,\"status\":\"active\"}', '::1', '2026-04-22 22:46:44'),
('54', '1', 'Create', 'product', '61', '', '{\"product_name\":\"Sweet Chilli - 700ml\",\"product_code\":\"SWTC-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":2,\"reorder_quantity\":4,\"status\":\"active\"}', '::1', '2026-04-22 22:47:25'),
('55', '1', 'Create', 'product', '62', '', '{\"product_name\":\"Mazoe-2LT\",\"product_code\":\"MZO-001\",\"category_id\":2,\"unit_of_measure\":\"Bottles\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:48:09'),
('56', '1', 'Create', 'product', '63', '', '{\"product_name\":\"Biscuits\",\"product_code\":\"BSC-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:48:55'),
('57', '1', 'Create', 'product', '64', '', '{\"product_name\":\"Tomato Sauce- All Gold (700ml)\",\"product_code\":\"TMS-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":2,\"reorder_quantity\":4,\"status\":\"active\"}', '::1', '2026-04-22 22:49:30'),
('58', '1', 'Create', 'product', '65', '', '{\"product_name\":\"Mutton cloth\",\"product_code\":\"MTC-002\",\"category_id\":3,\"unit_of_measure\":\"Meters\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:50:24'),
('59', '1', 'Create', 'product', '66', '', '{\"product_name\":\"Bar Soap\",\"product_code\":\"BSP-004\",\"category_id\":3,\"unit_of_measure\":\"Packets\",\"reorder_level\":20,\"reorder_quantity\":50,\"status\":\"active\"}', '::1', '2026-04-22 22:51:20'),
('60', '1', 'Create', 'product', '67', '', '{\"product_name\":\"Baking Powder\",\"product_code\":\"BKP-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:52:05'),
('61', '1', 'Create', 'product', '68', '', '{\"product_name\":\"Icing Sugar - 500g\",\"product_code\":\"ISG-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":2,\"reorder_quantity\":3,\"status\":\"active\"}', '::1', '2026-04-22 22:53:40'),
('62', '1', 'Create', 'product', '69', '', '{\"product_name\":\"Mayonnaise - 750g\",\"product_code\":\"MAY-001\",\"category_id\":1,\"unit_of_measure\":\"Bottles\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:54:15'),
('63', '1', 'Create', 'product', '70', '', '{\"product_name\":\"Straws\",\"product_code\":\"STRW-001\",\"category_id\":7,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":4,\"status\":\"active\"}', '::1', '2026-04-22 22:55:01'),
('64', '1', 'Create', 'product', '71', '', '{\"product_name\":\"Pork Chops\",\"product_code\":\"PRK-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":2,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:55:45'),
('65', '1', 'Create', 'product', '72', '', '{\"product_name\":\"Beef\",\"product_code\":\"BEF-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":3,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 22:56:12'),
('66', '1', 'Create', 'product', '73', '', '{\"product_name\":\"Chicken seasoning -1kg\",\"product_code\":\"CHS-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 22:56:40'),
('67', '1', 'Create', 'product', '74', '', '{\"product_name\":\"Stamp pad\",\"product_code\":\"STP-004\",\"category_id\":4,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:57:10'),
('68', '1', 'Create', 'product', '75', '', '{\"product_name\":\"Chip Sprinkle- 1kg\",\"product_code\":\"CPS-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 22:57:47'),
('69', '1', 'Create', 'product', '76', '', '{\"product_name\":\"Chicken\",\"product_code\":\"CHKN-001\",\"category_id\":1,\"unit_of_measure\":\"Head\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 22:58:20'),
('70', '1', 'Create', 'product', '77', '', '{\"product_name\":\"Chicken Breast - 1Kg\",\"product_code\":\"CHKNB-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":2,\"reorder_quantity\":4,\"status\":\"active\"}', '::1', '2026-04-22 22:58:50'),
('71', '1', 'Create', 'product', '78', '', '{\"product_name\":\"Pork Ribs - 1.8kg\",\"product_code\":\"PRB-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 22:59:53'),
('72', '1', 'Create', 'product', '79', '', '{\"product_name\":\"Bacon- 1kg\",\"product_code\":\"BCN-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 23:00:25'),
('73', '1', 'Create', 'product', '80', '', '{\"product_name\":\"Liver- 5kg\",\"product_code\":\"LVR-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":1,\"reorder_quantity\":5,\"status\":\"active\"}', '::1', '2026-04-22 23:01:01'),
('74', '1', 'Create', 'product', '81', '', '{\"product_name\":\"Tomato Paste\",\"product_code\":\"TMTP-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 23:02:07'),
('75', '1', 'Create', 'product', '82', '', '{\"product_name\":\"Tomatoes\",\"product_code\":\"TMT-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":5,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 23:02:51'),
('76', '1', 'Create', 'product', '83', '', '{\"product_name\":\"Lettuce\",\"product_code\":\"LTTC-001\",\"category_id\":1,\"unit_of_measure\":\"Head\",\"reorder_level\":3,\"reorder_quantity\":10,\"status\":\"active\"}', '::1', '2026-04-22 23:03:18'),
('77', '1', 'Create', 'product', '84', '', '{\"product_name\":\"Onions\",\"product_code\":\"ONS-001\",\"category_id\":1,\"unit_of_measure\":\"Pocket\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 23:04:04'),
('78', '1', 'Create', 'product', '85', '', '{\"product_name\":\"Cauliflower\",\"product_code\":\"CLFW-001\",\"category_id\":1,\"unit_of_measure\":\"Head\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 23:04:34'),
('79', '1', 'Create', 'product', '86', '', '{\"product_name\":\"Carrots\",\"product_code\":\"CRT-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 23:05:08'),
('80', '1', 'Create', 'product', '87', '', '{\"product_name\":\"Castor Sugar - 500g\",\"product_code\":\"CSS-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 23:05:42'),
('81', '1', 'Create', 'product', '88', '', '{\"product_name\":\"Broccoli\",\"product_code\":\"BRCL-001\",\"category_id\":1,\"unit_of_measure\":\"Head\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 23:06:14'),
('82', '1', 'Create', 'product', '89', '', '{\"product_name\":\"Hake Fillet - 5kg\",\"product_code\":\"HKE-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 23:06:51'),
('83', '1', 'Create', 'product', '90', '', '{\"product_name\":\"Bream- 4.5kg\",\"product_code\":\"BRM-001\",\"category_id\":1,\"unit_of_measure\":\"Box\",\"reorder_level\":0,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 23:07:14'),
('84', '1', 'Create', 'product', '91', '', '{\"product_name\":\"Beef Sausage- 400g\",\"product_code\":\"BSG-001\",\"category_id\":1,\"unit_of_measure\":\"Packets\",\"reorder_level\":4,\"reorder_quantity\":8,\"status\":\"active\"}', '::1', '2026-04-22 23:07:56'),
('85', '1', 'Create', 'product', '92', '', '{\"product_name\":\"Pork Sausage\",\"product_code\":\"PSG-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":2,\"reorder_quantity\":3,\"status\":\"active\"}', '::1', '2026-04-22 23:08:36'),
('86', '1', 'Create', 'product', '93', '', '{\"product_name\":\"Meat balls-1kg\",\"product_code\":\"MTB-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":1,\"reorder_quantity\":1,\"status\":\"active\"}', '::1', '2026-04-22 23:09:13'),
('87', '1', 'Create', 'product', '94', '', '{\"product_name\":\"Wings - 5kg\",\"product_code\":\"WNG-001\",\"category_id\":1,\"unit_of_measure\":\"KG\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 23:09:53'),
('88', '1', 'Create', 'product', '95', '', '{\"product_name\":\"Soft Drinks - bottled\",\"product_code\":\"SFTB-001\",\"category_id\":2,\"unit_of_measure\":\"Bottles\",\"reorder_level\":30,\"reorder_quantity\":50,\"status\":\"active\"}', '::1', '2026-04-22 23:10:44'),
('89', '1', 'Create', 'product', '96', '', '{\"product_name\":\"Soft Drink- cans\",\"product_code\":\"SFTC-001\",\"category_id\":2,\"unit_of_measure\":\"Cans\",\"reorder_level\":30,\"reorder_quantity\":50,\"status\":\"active\"}', '::1', '2026-04-22 23:11:24'),
('90', '1', 'Create', 'product', '97', '', '{\"product_name\":\"Soft Drinks- petty\",\"product_code\":\"SFTP-001\",\"category_id\":2,\"unit_of_measure\":\"Bottles\",\"reorder_level\":30,\"reorder_quantity\":50,\"status\":\"active\"}', '::1', '2026-04-22 23:12:20'),
('91', '1', 'Create', 'product', '98', '', '{\"product_name\":\"Pen - 50\",\"product_code\":\"PEN-004\",\"category_id\":4,\"unit_of_measure\":\"Box\",\"reorder_level\":1,\"reorder_quantity\":2,\"status\":\"active\"}', '::1', '2026-04-22 23:13:01'),
('92', '1', 'Create', 'user', '3', '', '{\"username\":\"kmbi\",\"email\":\"mugwagwakumbi@gmail.com\",\"full_name\":\"Kumbiraishe Mugwagwa\",\"role_id\":1,\"status\":\"active\"}', '::1', '2026-04-22 23:14:54'),
('93', '1', 'Create', 'department', '6', '', '{\"dept_name\":\"Restaurant\",\"dept_code\":\"RSTR\",\"head_user_id\":null,\"monthly_budget\":0,\"weekly_budget\":0,\"status\":\"active\"}', '::1', '2026-04-23 16:10:02'),
('94', '1', 'Create', 'department', '7', '', '{\"dept_name\":\"Functions\",\"dept_code\":\"FNCTS\",\"head_user_id\":null,\"monthly_budget\":0,\"weekly_budget\":0,\"status\":\"active\"}', '::1', '2026-04-23 16:26:10'),
('95', '1', 'Create', 'department', '8', '', '{\"dept_name\":\"School of Hospitality & Tourism\",\"dept_code\":\"MSSHT\",\"head_user_id\":null,\"monthly_budget\":0,\"weekly_budget\":0,\"status\":\"active\"}', '::1', '2026-04-23 16:26:47'),
('96', '1', 'Create', 'user', '4', '', '{\"username\":\"rukudzo1\",\"email\":\"mugadzarukudzo33@gmail.com\",\"full_name\":\"Rukudzo Mugadza\",\"role_id\":7,\"status\":\"active\"}', '::1', '2026-04-23 18:18:28'),
('97', '1', 'Create', 'user', '5', '', '{\"username\":\"aubree\",\"email\":\"amzhuwao@gmail.com\",\"full_name\":\"Aubrey Zhuwao\",\"role_id\":1,\"status\":\"active\"}', '::1', '2026-04-23 18:31:13'),
('98', '1', 'Create', 'user', '6', '', '{\"username\":\"John\",\"email\":\"email@doe.com\",\"full_name\":\"John Doe\",\"role_id\":4,\"status\":\"active\"}', '::1', '2026-04-23 18:48:19'),
('99', '1', 'Create', 'store', '5', '', '{\"store_name\":\"Main Stores\",\"store_code\":\"MAIN001\",\"location\":\"Hotel Ground Floor\",\"responsible_user_id\":2,\"description\":\"\",\"status\":\"active\"}', '::1', '2026-04-23 16:59:36'),
('100', '1', 'Update', 'user', '2', '{\"user_id\":2,\"username\":\"storekeeper1\",\"email\":\"storekeeper@hotel.com\",\"full_name\":\"John Storekeeper\",\"role_id\":2,\"status\":\"active\",\"created_at\":\"2026-04-20 16:28:52\",\"updated_at\":\"2026-04-20 16:28:52\",\"role_name\":\"Storekeeper\"}', '{\"username\":\"storekeeper1\",\"email\":\"storekeeper@hotel.com\",\"full_name\":\"John Storekeeper\",\"role_id\":2,\"status\":\"active\",\"password\":\"[updated]\"}', '::1', '2026-04-23 17:08:53'),
('101', '1', 'Create', 'department', '9', '', '{\"dept_name\":\"Main Stores\",\"dept_code\":\"MNSTR\",\"head_user_id\":2,\"monthly_budget\":40000,\"weekly_budget\":10000,\"status\":\"active\"}', '::1', '2026-04-23 17:12:39'),
('102', '1', 'Update', 'user', '2', '{\"user_id\":2,\"username\":\"storekeeper1\",\"email\":\"storekeeper@hotel.com\",\"full_name\":\"John Storekeeper\",\"role_id\":2,\"status\":\"active\",\"created_at\":\"2026-04-20 16:28:52\",\"updated_at\":\"2026-04-23 15:08:53\",\"role_name\":\"Storekeeper\"}', '{\"username\":\"storekeeper1\",\"email\":\"storekeeper@hotel.com\",\"full_name\":\"John Storekeeper\",\"role_id\":2,\"status\":\"active\",\"password\":\"[updated]\"}', '::1', '2026-04-23 17:13:03'),
('103', '6', 'Create', 'requisition', '1', '', '{\"requisition_number\":\"REQ-20260423-0001\",\"department_id\":2,\"store_id\":5,\"requested_by\":6,\"status\":\"pending\",\"notes\":\"\"}', '::1', '2026-04-23 17:13:59'),
('104', '5', 'Approve', 'requisition', '1', 'pending', 'approved', '::1', '2026-04-23 17:16:11'),
('105', '2', 'Create', 'supplier', '1', '', '{\"supplier_name\":\"Test Supplier Groceries\",\"contact_person\":\"M Zhuwao\",\"email\":\"azaways@gmail.com\",\"phone\":\"0719952811\",\"address\":\"123 Middle of Nowhere\",\"city\":\"Mutare\",\"postal_code\":\"06\",\"payment_terms\":\"cash\",\"status\":\"active\"}', '::1', '2026-04-23 17:20:59'),
('106', '2', 'Create', 'grn', '1', '', '{\"grn_number\":\"GRN-20260423-0001\",\"supplier_id\":1,\"store_id\":5,\"received_by\":2,\"receipt_date\":\"2026-04-23\",\"receipt_time\":\"13:21\",\"delivery_note_ref\":\"Test Delivery\",\"invoice_reference\":\"123\",\"total_cost\":75,\"status\":\"draft\",\"notes\":\"\"}', '::1', '2026-04-23 17:21:57'),
('107', '2', 'Verify', 'grn', '1', 'draft', 'verified', '::1', '2026-04-23 17:22:09'),
('108', '2', 'Create', 'stock_issue', '1', '', '{\"issue_number\":\"ISS-20260423-0001\",\"requisition_id\":1,\"line_count\":1}', '::1', '2026-04-23 17:23:16'),
('109', '5', 'Create', 'stock_issue', '2', '', '{\"issue_number\":\"ISS-20260424-0001\",\"direct_issue\":true,\"store_id\":5,\"department_id\":2,\"line_count\":1}', '::1', '2026-04-24 13:13:46'),
('110', '5', 'Create', 'stock_issue', '3', '', '{\"issue_number\":\"ISS-20260427-0001\",\"direct_issue\":true,\"store_id\":5,\"department_id\":2,\"line_count\":1}', '::1', '2026-04-27 18:32:29');

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
  KEY `fk_backup_history_user` (`initiated_by`),
  KEY `idx_backup_history_started_at` (`started_at`),
  KEY `idx_backup_history_status` (`status`),
  KEY `idx_backup_history_trigger` (`trigger_type`),
  CONSTRAINT `fk_backup_history_user` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `backup_history`
-- ----------------------------
INSERT INTO `backup_history` (`backup_id`, `file_name`, `file_path`, `file_size_bytes`, `trigger_type`, `status`, `started_at`, `completed_at`, `initiated_by`, `error_message`, `created_at`) VALUES
('1', 'stores_backup_manual_20260421_072641.sql', 'C:\\xampp\\htdocs\\stores\\storage\\backups\\stores_backup_manual_20260421_072641.sql', '36337', 'manual', 'success', '2026-04-21 07:26:41', '2026-04-21 07:26:41', '1', NULL, '2026-04-21 17:26:41'),
('2', 'pending', '', '0', 'manual', 'failed', '2026-04-22 13:22:43', NULL, '1', NULL, '2026-04-22 23:22:43'),
('3', 'pending', '', '0', 'manual', 'failed', '2026-04-23 12:13:58', NULL, '3', NULL, '2026-04-23 20:13:58'),
('4', 'pending', '', '0', 'manual', 'failed', '2026-04-23 12:52:17', NULL, '1', NULL, '2026-04-23 18:52:17'),
('5', 'stores_backup_manual_20260424_104819.sql', 'C:\\xampp\\htdocs\\stores\\storage\\backups\\stores_backup_manual_20260424_104819.sql', '124210', 'manual', 'success', '2026-04-24 10:48:19', '2026-04-24 10:48:20', '5', NULL, '2026-04-24 14:48:19'),
('6', 'stores_backup_manual_20260427_153010.sql', 'C:\\xampp\\htdocs\\stores\\storage\\backups\\stores_backup_manual_20260427_153010.sql', '130724', 'manual', 'success', '2026-04-27 15:30:10', '2026-04-27 15:30:11', '5', NULL, '2026-04-27 19:30:10'),
('7', 'pending', '', '0', 'manual', 'failed', '2026-04-27 15:30:38', NULL, '5', NULL, '2026-04-27 19:30:38'),
('8', 'pending', '', '0', 'manual', 'failed', '2026-04-27 15:33:26', NULL, '5', NULL, '2026-04-27 17:33:26');

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
  KEY `fk_backup_settings_updated_by` (`updated_by`),
  CONSTRAINT `fk_backup_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `CONSTRAINT_1` CHECK (`setting_id` = 1),
  CONSTRAINT `CONSTRAINT_2` CHECK (`day_of_week` between 0 and 6),
  CONSTRAINT `CONSTRAINT_3` CHECK (`day_of_month` between 1 and 31),
  CONSTRAINT `CONSTRAINT_4` CHECK (`retention_days` between 1 and 3650)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `backup_settings`
-- ----------------------------
INSERT INTO `backup_settings` (`setting_id`, `is_enabled`, `frequency`, `run_time`, `day_of_week`, `day_of_month`, `retention_days`, `schedule_token`, `last_run_at`, `next_run_at`, `updated_by`, `updated_at`) VALUES
('1', '1', 'daily', '02:00:00', '1', '1', '30', 'f1dc44cfc702427899fd9edb082580a84918a99d45a2f96e', NULL, '2026-04-22 02:00:00', '1', '2026-04-21 19:56:03');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `batch_tracking`
-- ----------------------------
INSERT INTO `batch_tracking` (`batch_id`, `product_id`, `store_id`, `batch_number`, `expiry_date`, `quantity`, `unit_price`, `grn_item_id`, `received_date`, `status`, `created_at`, `updated_at`) VALUES
('1', '35', '5', '456', '2026-06-30', '15', '5.00', '1', '2026-04-23', 'available', '2026-04-23 17:22:09', '2026-04-23 17:22:09');

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `categories`
-- ----------------------------
INSERT INTO `categories` (`category_id`, `category_name`, `description`, `status`, `created_at`) VALUES
('1', 'Food Items', 'Cooking ingredients and food', 'active', '2026-04-20 18:28:52'),
('2', 'Beverages', 'Drinks and beverage supplies', 'active', '2026-04-20 18:28:52'),
('3', 'Cleaning Supplies', 'Cleaning and maintenance chemicals', 'active', '2026-04-20 18:28:52'),
('4', 'Utensils & Equipment', 'Kitchen utensils and equipment', 'active', '2026-04-20 18:28:52'),
('5', 'Linen & Fabric', 'Bed linen and fabric items', 'active', '2026-04-20 18:28:52'),
('6', 'Tools & Hardware', 'Maintenance tools and hardware', 'active', '2026-04-20 18:28:52'),
('7', 'Packaging Materials', 'Boxes, bags, and packaging', 'active', '2026-04-20 18:28:52'),
('8', 'Food Items', 'Cooking ingredients and food', 'active', '2026-04-27 17:56:25'),
('9', 'Beverages', 'Drinks and beverage supplies', 'active', '2026-04-27 17:56:25'),
('10', 'Cleaning Supplies', 'Cleaning and maintenance chemicals', 'active', '2026-04-27 17:56:25'),
('11', 'Utensils & Equipment', 'Kitchen utensils and equipment', 'active', '2026-04-27 17:56:25'),
('12', 'Linen & Fabric', 'Bed linen and fabric items', 'active', '2026-04-27 17:56:25'),
('13', 'Tools & Hardware', 'Maintenance tools and hardware', 'active', '2026-04-27 17:56:25'),
('14', 'Packaging Materials', 'Boxes, bags, and packaging', 'active', '2026-04-27 17:56:25'),
('15', 'Food Items', 'Cooking ingredients and food', 'active', '2026-04-27 17:33:14'),
('16', 'Beverages', 'Drinks and beverage supplies', 'active', '2026-04-27 17:33:14'),
('17', 'Cleaning Supplies', 'Cleaning and maintenance chemicals', 'active', '2026-04-27 17:33:14'),
('18', 'Utensils & Equipment', 'Kitchen utensils and equipment', 'active', '2026-04-27 17:33:14'),
('19', 'Linen & Fabric', 'Bed linen and fabric items', 'active', '2026-04-27 17:33:14'),
('20', 'Tools & Hardware', 'Maintenance tools and hardware', 'active', '2026-04-27 17:33:14'),
('21', 'Packaging Materials', 'Boxes, bags, and packaging', 'active', '2026-04-27 17:33:14');

-- ----------------------------
-- Table structure for `consumption_permissions`
-- ----------------------------
DROP TABLE IF EXISTS `consumption_permissions`;
CREATE TABLE `consumption_permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `can_log_consumption` tinyint(1) DEFAULT 1,
  `can_view_reports` tinyint(1) DEFAULT 0,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_at` datetime DEFAULT NULL,
  `status` enum('active','revoked') DEFAULT 'active',
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `unique_user_dept` (`user_id`,`department_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_user_permissions` (`user_id`,`status`),
  KEY `idx_dept_permissions` (`department_id`,`status`),
  CONSTRAINT `consumption_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `consumption_permissions_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE,
  CONSTRAINT `consumption_permissions_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `consumption_permissions`
-- ----------------------------
INSERT INTO `consumption_permissions` (`permission_id`, `user_id`, `department_id`, `assigned_by`, `can_log_consumption`, `can_view_reports`, `assigned_at`, `revoked_at`, `status`) VALUES
('1', '6', '2', '5', '1', '0', '2026-04-27 16:02:49', NULL, 'active');

-- ----------------------------
-- Table structure for `consumption_records`
-- ----------------------------
DROP TABLE IF EXISTS `consumption_records`;
CREATE TABLE `consumption_records` (
  `consumption_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_item_id` int(11) NOT NULL,
  `quantity_consumed` int(11) NOT NULL,
  `logged_by` int(11) NOT NULL,
  `log_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`consumption_id`),
  KEY `logged_by` (`logged_by`),
  KEY `idx_consumption_date` (`log_date`),
  KEY `idx_issue_item_consumption` (`issue_item_id`),
  CONSTRAINT `consumption_records_ibfk_1` FOREIGN KEY (`issue_item_id`) REFERENCES `stock_issue_items` (`issue_item_id`) ON DELETE CASCADE,
  CONSTRAINT `consumption_records_ibfk_2` FOREIGN KEY (`logged_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `consumption_records`
-- ----------------------------
INSERT INTO `consumption_records` (`consumption_id`, `issue_item_id`, `quantity_consumed`, `logged_by`, `log_date`, `notes`, `created_at`) VALUES
('1', '2', '1', '6', '2026-04-27 17:12:39', '', '2026-04-27 19:12:39');

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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `departments`
-- ----------------------------
INSERT INTO `departments` (`dept_id`, `dept_name`, `dept_code`, `head_user_id`, `status`, `monthly_budget`, `weekly_budget`, `created_at`) VALUES
('1', 'Kitchen', 'KIT', NULL, 'active', '0.00', '0.00', '2026-04-20 18:28:52'),
('2', 'Bar', 'BAR', NULL, 'active', '0.00', '0.00', '2026-04-20 18:28:52'),
('3', 'Housekeeping', 'HNK', NULL, 'active', '0.00', '0.00', '2026-04-20 18:28:52'),
('4', 'Maintenance', 'MNT', NULL, 'active', '0.00', '0.00', '2026-04-20 18:28:52'),
('5', 'Front Office', 'FRO', NULL, 'active', '0.00', '0.00', '2026-04-20 18:28:52'),
('6', 'Restaurant', 'RSTR', NULL, 'active', '0.00', '0.00', '2026-04-23 16:10:02'),
('7', 'Functions', 'FNCTS', NULL, 'active', '0.00', '0.00', '2026-04-23 16:26:10'),
('8', 'School of Hospitality & Tourism', 'MSSHT', NULL, 'active', '0.00', '0.00', '2026-04-23 16:26:47'),
('9', 'Main Stores', 'MNSTR', '2', 'active', '40000.00', '10000.00', '2026-04-23 17:12:39');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `grn`
-- ----------------------------
INSERT INTO `grn` (`grn_id`, `grn_number`, `supplier_id`, `store_id`, `received_by`, `receipt_date`, `receipt_time`, `delivery_note_ref`, `invoice_reference`, `total_cost`, `status`, `notes`, `created_at`, `updated_at`) VALUES
('1', 'GRN-20260423-0001', '1', '5', '2', '2026-04-23', '13:21:00', 'Test Delivery', '123', '75.00', 'verified', '', '2026-04-23 17:21:57', '2026-04-23 17:22:09');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `grn_items`
-- ----------------------------
INSERT INTO `grn_items` (`grn_item_id`, `grn_id`, `product_id`, `quantity_expected`, `quantity_received`, `unit_price`, `batch_number`, `expiry_date`, `condition_status`, `remarks`, `created_at`) VALUES
('1', '1', '35', '20', '15', '5.00', '456', '2026-06-30', 'good', NULL, '2026-04-23 17:21:57');

-- ----------------------------
-- Table structure for `offline_sync_log`
-- ----------------------------
DROP TABLE IF EXISTS `offline_sync_log`;
CREATE TABLE `offline_sync_log` (
  `sync_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_record_id` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `record_type` varchar(80) NOT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `payload_json` longtext NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `server_reference_type` varchar(50) DEFAULT NULL,
  `server_reference_id` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sync_id`),
  UNIQUE KEY `client_record_id` (`client_record_id`),
  KEY `idx_offline_sync_status` (`status`,`created_at`),
  KEY `idx_offline_sync_user` (`user_id`,`created_at`),
  CONSTRAINT `offline_sync_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `offline_sync_log`
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
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `products`
-- ----------------------------
INSERT INTO `products` (`product_id`, `product_name`, `product_code`, `category_id`, `unit_of_measure`, `reorder_level`, `reorder_quantity`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Cooking Oil - 5L', 'OIL-001', '1', 'Liters', '10', '50', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('2', 'Salt - 1KG', 'SAL-001', '1', 'KG', '2', '10', 'active', '2026-04-20 18:28:52', '2026-04-22 17:31:49'),
('3', 'Sugar - 2KG', 'SUG-001', '1', 'KG', '5', '10', 'active', '2026-04-20 18:28:52', '2026-04-22 22:29:11'),
('4', 'Flour - 1KG', 'FLO-001', '1', 'KG', '2', '10', 'active', '2026-04-20 18:28:52', '2026-04-22 17:32:09'),
('5', 'Red Wine - 750ml', 'WIN-001', '2', 'Bottles', '2', '5', 'active', '2026-04-20 18:28:52', '2026-04-22 17:31:21'),
('6', 'Beer - 330ml', 'BEE-001', '2', 'Bottles', '30', '150', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('7', 'Floor Cleaner - 5L', 'CLN-001', '3', 'Liters', '5', '20', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('8', 'Disinfectant - 1L', 'DIS-001', '3', 'Liters', '10', '30', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('9', 'Chef Knife', 'KNF-001', '4', 'Pieces', '3', '10', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('10', 'Bed Sheets - Queen', 'BED-001', '5', 'Pieces', '10', '30', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('11', 'Hand Soap - 500ml', 'SOP-001', '3', 'Bottles', '15', '50', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('12', 'Wrench Set', 'WRN-001', '6', 'Set', '2', '5', 'active', '2026-04-20 18:28:52', '2026-04-20 18:28:52'),
('13', 'Washing Powder -2kg', 'WPD-004', '3', 'KG', '2', '5', 'active', '2026-04-22 22:15:53', '2026-04-22 22:15:53'),
('14', 'Toilet Cleaner - 750ml', 'TLC-004', '3', 'Liters', '4', '10', 'active', '2026-04-22 22:16:50', '2026-04-22 22:16:50'),
('15', 'Handy Andy - 750ml', 'HNDY-002', '3', 'Bottles', '3', '6', 'active', '2026-04-22 22:17:27', '2026-04-22 22:17:27'),
('16', 'Kitchen Towel', 'KTH-002', '3', 'Roll', '5', '5', 'active', '2026-04-22 22:18:35', '2026-04-22 22:18:35'),
('17', 'TISSUE -50 rolls', 'TSS-002', '3', 'Roll', '20', '100', 'active', '2026-04-22 22:19:18', '2026-04-22 22:19:18'),
('18', 'Flip Chart -10', 'FLP-002', '4', 'Packets', '3', '5', 'active', '2026-04-22 22:20:07', '2026-04-22 22:20:07'),
('19', 'Bond Paper - 500', 'BDP-004', '4', 'Packets', '1', '4', 'active', '2026-04-22 22:20:59', '2026-04-22 22:20:59'),
('20', 'Baking sheet', 'BKS-001', '4', 'Roll', '2', '5', 'active', '2026-04-22 22:21:38', '2026-04-22 22:21:38'),
('21', 'Sanitary Pads', 'SPD-002', '3', 'Packets', '4', '10', 'active', '2026-04-22 22:22:15', '2026-04-22 22:22:15'),
('22', 'Tile Cleaner - 5ltr', 'TCLN-001', '3', 'Bottles', '1', '3', 'active', '2026-04-22 22:22:59', '2026-04-22 22:22:59'),
('23', 'Water- BOTTLED', 'WTR-003', '1', 'Bottles', '50', '150', 'active', '2026-04-22 22:23:54', '2026-04-22 22:23:54'),
('24', 'Takeaway Cup', 'TKC-001', '7', 'Bottles', '50', '100', 'active', '2026-04-22 22:24:45', '2026-04-22 22:24:45'),
('25', 'Fruit Juice - Victoria (1ltr)', 'FRTJ-001', '1', 'Bottles', '5', '10', 'active', '2026-04-22 22:25:21', '2026-04-22 22:25:21'),
('26', 'Milk- 1L', 'MLK-001', '1', 'Bottles', '6', '12', 'active', '2026-04-22 22:25:48', '2026-04-22 22:25:48'),
('27', 'Juice boxes- 200ml', 'JBX-001', '1', 'Packets', '6', '12', 'active', '2026-04-22 22:26:18', '2026-04-22 22:26:18'),
('28', 'Takeaway Tray- PLASTIC', 'TKT-001', '7', 'Packets', '60', '150', 'active', '2026-04-22 22:27:10', '2026-04-22 22:27:10'),
('29', 'Takeaway Box - PLASTIC', 'TKB-001', '7', 'Packets', '100', '150', 'active', '2026-04-22 22:27:40', '2026-04-22 22:27:40'),
('30', 'Cornflakes', 'CRN-001', '1', 'Box', '2', '5', 'active', '2026-04-22 22:28:13', '2026-04-22 22:28:13'),
('31', 'Sugar (White) - 2KG', 'SUGW-001', '1', 'Packets', '2', '5', 'active', '2026-04-22 22:28:53', '2026-04-22 22:28:53'),
('32', 'Cerevita', 'CRV-001', '1', 'Box', '2', '5', 'active', '2026-04-22 22:30:08', '2026-04-22 22:30:08'),
('33', 'Tanganda Tea', 'TNDT-001', '1', 'Box', '2', '4', 'active', '2026-04-22 22:31:06', '2026-04-22 22:31:06'),
('34', 'Roibos Tea', 'RBS-001', '1', 'Box', '2', '3', 'active', '2026-04-22 22:31:57', '2026-04-22 22:31:57'),
('35', 'Cappuccino', 'CPP-001', '1', 'Packets', '5', '10', 'active', '2026-04-22 22:32:23', '2026-04-22 22:32:23'),
('36', 'Sweetener', 'SWT-001', '1', 'Box', '1', '2', 'active', '2026-04-22 22:33:16', '2026-04-22 22:33:16'),
('37', 'Mints', 'MNT-001', '1', 'Packets', '2', '5', 'active', '2026-04-22 22:33:46', '2026-04-22 22:33:46'),
('38', 'Veetbix', 'VTB-001', '1', 'Box', '1', '2', 'active', '2026-04-22 22:34:20', '2026-04-22 22:34:20'),
('39', 'Honey -400g', 'HNY-001', '1', 'Bottles', '3', '5', 'active', '2026-04-22 22:34:47', '2026-04-22 22:34:47'),
('40', 'JAM - 400G', 'JAM-001', '1', 'Bottles', '2', '5', 'active', '2026-04-22 22:35:27', '2026-04-22 22:35:27'),
('41', 'Air Freshener', 'AFS-002', '3', 'Bottles', '5', '10', 'active', '2026-04-22 22:36:03', '2026-04-22 22:36:03'),
('42', 'Hot Chocolate', 'HCT-001', '1', 'Bottles', '1', '2', 'active', '2026-04-22 22:36:26', '2026-04-22 22:36:26'),
('43', 'Ricoffy', 'RIC-001', '1', 'Bottles', '2', '5', 'active', '2026-04-22 22:36:54', '2026-04-22 22:36:54'),
('44', 'Condensed Milk', 'CML-001', '1', 'Cans', '1', '2', 'active', '2026-04-22 22:37:24', '2026-04-22 22:37:24'),
('45', 'Custard', 'CSTD-001', '1', 'Bottles', '1', '2', 'active', '2026-04-22 22:37:52', '2026-04-22 22:37:52'),
('46', 'LAYS- 30pac', 'LAY-001', '1', 'Box', '1', '2', 'active', '2026-04-22 22:38:20', '2026-04-22 22:38:20'),
('47', 'Spaghetti - 450g', 'SPG-001', '1', 'Packets', '3', '5', 'active', '2026-04-22 22:38:52', '2026-04-22 22:38:52'),
('48', 'Rice - 5KG', 'RICE-001', '1', 'KG', '0', '1', 'active', '2026-04-22 22:39:26', '2026-04-22 22:39:26'),
('49', 'Rice - 2kg', 'RCE-001', '1', 'KG', '2', '5', 'active', '2026-04-22 22:40:12', '2026-04-22 22:40:12'),
('50', 'Eggs', 'EGG-001', '1', 'Tray', '1', '2', 'active', '2026-04-22 22:40:41', '2026-04-22 22:40:41'),
('51', 'Envelopes - 50', 'ENV-004', '7', 'Packets', '10', '50', 'active', '2026-04-22 22:41:02', '2026-04-22 22:41:02'),
('52', 'Jelly- ROYAL', 'JLY-001', '1', 'Packets', '2', '5', 'active', '2026-04-22 22:41:26', '2026-04-22 22:41:26'),
('53', 'BBQ Seasoning- 1kg', 'BBQ-001', '1', 'Packets', '1', '1', 'active', '2026-04-22 22:42:02', '2026-04-22 22:42:02'),
('54', 'Five Roses', 'FRS-001', '1', 'Box', '1', '1', 'active', '2026-04-22 22:42:31', '2026-04-22 22:42:31'),
('55', 'Garlic Seasoning-1kg', 'GLS-001', '1', 'Packets', '1', '1', 'active', '2026-04-22 22:42:56', '2026-04-22 22:42:56'),
('56', 'Pellegrini - 125ml', 'PLG-001', '2', 'Bottles', '1', '2', 'active', '2026-04-22 22:43:31', '2026-04-22 22:43:31'),
('57', 'Potatoes', 'POT-001', '1', 'Packets', '1', '2', 'active', '2026-04-22 22:44:07', '2026-04-22 22:44:07'),
('58', 'Hand Soap - 300ml', 'HNDW-002', '3', 'Bottles', '5', '10', 'active', '2026-04-22 22:44:58', '2026-04-22 22:44:58'),
('59', 'Fruit chutney - 420g', 'FRT-001', '1', 'Bottles', '1', '2', 'active', '2026-04-22 22:45:56', '2026-04-22 22:45:56'),
('60', 'Salad Dressing', 'SLD-001', '1', 'Bottles', '2', '4', 'active', '2026-04-22 22:46:44', '2026-04-22 22:46:44'),
('61', 'Sweet Chilli - 700ml', 'SWTC-001', '1', 'Bottles', '2', '4', 'active', '2026-04-22 22:47:25', '2026-04-22 22:47:25'),
('62', 'Mazoe-2LT', 'MZO-001', '2', 'Bottles', '5', '10', 'active', '2026-04-22 22:48:09', '2026-04-22 22:48:09'),
('63', 'Biscuits', 'BSC-001', '1', 'Packets', '5', '10', 'active', '2026-04-22 22:48:55', '2026-04-22 22:48:55'),
('64', 'Tomato Sauce- All Gold (700ml)', 'TMS-001', '1', 'Bottles', '2', '4', 'active', '2026-04-22 22:49:30', '2026-04-22 22:49:30'),
('65', 'Mutton cloth', 'MTC-002', '3', 'Meters', '1', '2', 'active', '2026-04-22 22:50:24', '2026-04-22 22:50:24'),
('66', 'Bar Soap', 'BSP-004', '3', 'Packets', '20', '50', 'active', '2026-04-22 22:51:20', '2026-04-22 22:51:20'),
('67', 'Baking Powder', 'BKP-001', '1', 'Box', '1', '2', 'active', '2026-04-22 22:52:05', '2026-04-22 22:52:05'),
('68', 'Icing Sugar - 500g', 'ISG-001', '1', 'Packets', '2', '3', 'active', '2026-04-22 22:53:40', '2026-04-22 22:53:40'),
('69', 'Mayonnaise - 750g', 'MAY-001', '1', 'Bottles', '2', '5', 'active', '2026-04-22 22:54:15', '2026-04-22 22:54:15'),
('70', 'Straws', 'STRW-001', '7', 'Packets', '1', '4', 'active', '2026-04-22 22:55:01', '2026-04-22 22:55:01'),
('71', 'Pork Chops', 'PRK-001', '1', 'KG', '2', '5', 'active', '2026-04-22 22:55:45', '2026-04-22 22:55:45'),
('72', 'Beef', 'BEF-001', '1', 'KG', '3', '5', 'active', '2026-04-22 22:56:12', '2026-04-22 22:56:12'),
('73', 'Chicken seasoning -1kg', 'CHS-001', '1', 'Packets', '1', '1', 'active', '2026-04-22 22:56:40', '2026-04-22 22:56:40'),
('74', 'Stamp pad', 'STP-004', '4', 'Box', '1', '2', 'active', '2026-04-22 22:57:10', '2026-04-22 22:57:10'),
('75', 'Chip Sprinkle- 1kg', 'CPS-001', '1', 'Packets', '1', '1', 'active', '2026-04-22 22:57:47', '2026-04-22 22:57:47'),
('76', 'Chicken', 'CHKN-001', '1', 'Head', '5', '10', 'active', '2026-04-22 22:58:20', '2026-04-22 22:58:20'),
('77', 'Chicken Breast - 1Kg', 'CHKNB-001', '1', 'KG', '2', '4', 'active', '2026-04-22 22:58:50', '2026-04-22 22:58:50'),
('78', 'Pork Ribs - 1.8kg', 'PRB-001', '1', 'KG', '1', '2', 'active', '2026-04-22 22:59:53', '2026-04-22 22:59:53'),
('79', 'Bacon- 1kg', 'BCN-001', '1', 'Packets', '1', '2', 'active', '2026-04-22 23:00:25', '2026-04-22 23:00:25'),
('80', 'Liver- 5kg', 'LVR-001', '1', 'KG', '1', '5', 'active', '2026-04-22 23:01:01', '2026-04-22 23:01:01'),
('81', 'Tomato Paste', 'TMTP-001', '1', 'Packets', '5', '10', 'active', '2026-04-22 23:02:07', '2026-04-22 23:02:07'),
('82', 'Tomatoes', 'TMT-001', '1', 'KG', '5', '10', 'active', '2026-04-22 23:02:51', '2026-04-22 23:02:51'),
('83', 'Lettuce', 'LTTC-001', '1', 'Head', '3', '10', 'active', '2026-04-22 23:03:18', '2026-04-22 23:03:18'),
('84', 'Onions', 'ONS-001', '1', 'Pocket', '1', '1', 'active', '2026-04-22 23:04:04', '2026-04-22 23:04:04'),
('85', 'Cauliflower', 'CLFW-001', '1', 'Head', '1', '2', 'active', '2026-04-22 23:04:34', '2026-04-22 23:04:34'),
('86', 'Carrots', 'CRT-001', '1', 'Box', '1', '1', 'active', '2026-04-22 23:05:08', '2026-04-22 23:05:08'),
('87', 'Castor Sugar - 500g', 'CSS-001', '1', 'Packets', '1', '2', 'active', '2026-04-22 23:05:42', '2026-04-22 23:05:42'),
('88', 'Broccoli', 'BRCL-001', '1', 'Head', '1', '1', 'active', '2026-04-22 23:06:14', '2026-04-22 23:06:14'),
('89', 'Hake Fillet - 5kg', 'HKE-001', '1', 'Box', '1', '2', 'active', '2026-04-22 23:06:51', '2026-04-22 23:06:51'),
('90', 'Bream- 4.5kg', 'BRM-001', '1', 'Box', '0', '1', 'active', '2026-04-22 23:07:14', '2026-04-22 23:07:14'),
('91', 'Beef Sausage- 400g', 'BSG-001', '1', 'Packets', '4', '8', 'active', '2026-04-22 23:07:56', '2026-04-22 23:07:56'),
('92', 'Pork Sausage', 'PSG-001', '1', 'KG', '2', '3', 'active', '2026-04-22 23:08:36', '2026-04-22 23:08:36'),
('93', 'Meat balls-1kg', 'MTB-001', '1', 'KG', '1', '1', 'active', '2026-04-22 23:09:13', '2026-04-22 23:09:13'),
('94', 'Wings - 5kg', 'WNG-001', '1', 'KG', '1', '2', 'active', '2026-04-22 23:09:53', '2026-04-22 23:09:53'),
('95', 'Soft Drinks - bottled', 'SFTB-001', '2', 'Bottles', '30', '50', 'active', '2026-04-22 23:10:44', '2026-04-22 23:10:44'),
('96', 'Soft Drink- cans', 'SFTC-001', '2', 'Cans', '30', '50', 'active', '2026-04-22 23:11:24', '2026-04-22 23:11:24'),
('97', 'Soft Drinks- petty', 'SFTP-001', '2', 'Bottles', '30', '50', 'active', '2026-04-22 23:12:20', '2026-04-22 23:12:20'),
('98', 'Pen - 50', 'PEN-004', '4', 'Box', '1', '2', 'active', '2026-04-22 23:13:01', '2026-04-22 23:13:01');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `requisition_items`
-- ----------------------------
INSERT INTO `requisition_items` (`req_item_id`, `requisition_id`, `product_id`, `quantity_requested`, `quantity_approved`, `quantity_issued`, `unit_price`, `remarks`, `created_at`) VALUES
('1', '1', '35', '5', '5', '5', NULL, '', '2026-04-23 17:13:59');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `requisitions`
-- ----------------------------
INSERT INTO `requisitions` (`requisition_id`, `requisition_number`, `department_id`, `store_id`, `requested_by`, `requested_date`, `status`, `approved_by`, `approval_date`, `rejection_reason`, `notes`, `created_at`, `updated_at`) VALUES
('1', 'REQ-20260423-0001', '2', '5', '6', '2026-04-23 15:13:59', 'issued', '5', '2026-04-23 15:16:11', NULL, '', '2026-04-23 17:13:59', '2026-04-23 17:23:16');

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
  KEY `fk_restore_history_user` (`initiated_by`),
  KEY `fk_restore_history_backup` (`safety_backup_id`),
  KEY `idx_restore_history_started_at` (`started_at`),
  KEY `idx_restore_history_status` (`status`),
  KEY `idx_restore_history_source_type` (`source_type`),
  CONSTRAINT `fk_restore_history_backup` FOREIGN KEY (`safety_backup_id`) REFERENCES `backup_history` (`backup_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_restore_history_user` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `restore_history`
-- ----------------------------
INSERT INTO `restore_history` (`restore_id`, `source_type`, `source_label`, `source_path`, `status`, `started_at`, `completed_at`, `initiated_by`, `safety_backup_id`, `error_message`, `created_at`) VALUES
('1', 'upload', 'stores_backup_manual_20260427_153038.sql', 'C:\\xampp\\tmp\\phpBDEE.tmp', 'failed', '2026-04-27 15:33:26', NULL, '5', NULL, NULL, '2026-04-27 17:33:26');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `role_permission_overrides`
-- ----------------------------
INSERT INTO `role_permission_overrides` (`override_id`, `role_name`, `permission`, `is_allowed`, `created_at`, `updated_at`) VALUES
('1', 'Bar', 'stock-issues.*', '1', '2026-04-27 16:10:29', '2026-04-27 16:10:29'),
('2', 'Bar', 'stock-issues.create', '1', '2026-04-27 16:10:29', '2026-04-27 16:10:29'),
('3', 'Bar', 'stock-issues.view', '1', '2026-04-27 16:10:29', '2026-04-27 16:10:29');

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `roles`
-- ----------------------------
INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
('1', 'Admin', 'System administrator with full access', '2026-04-20 18:28:51'),
('2', 'Storekeeper', 'Manages stock and inventory', '2026-04-20 18:28:51'),
('3', 'Kitchen', 'Kitchen department', '2026-04-20 18:28:51'),
('4', 'Bar', 'Bar department', '2026-04-20 18:28:51'),
('5', 'Housekeeping', 'Housekeeping department', '2026-04-20 18:28:51'),
('6', 'Maintenance', 'Maintenance department', '2026-04-20 18:28:51'),
('7', 'Accounts', 'Accounts and cost tracking', '2026-04-20 18:28:51'),
('8', 'Manager', 'Store manager', '2026-04-20 18:28:51');

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
) ENGINE=InnoDB AUTO_INCREMENT=701 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock`
-- ----------------------------
INSERT INTO `stock` (`stock_id`, `product_id`, `store_id`, `quantity_on_hand`, `reorder_level`, `last_counted_at`, `created_at`, `updated_at`) VALUES
('1', '13', '1', '0', '2', '2026-04-22 22:15:53', '2026-04-22 22:15:53', '2026-04-22 22:15:53'),
('2', '13', '2', '0', '2', '2026-04-22 22:15:53', '2026-04-22 22:15:53', '2026-04-22 22:15:53'),
('3', '13', '3', '0', '2', '2026-04-22 22:15:53', '2026-04-22 22:15:53', '2026-04-22 22:15:53'),
('4', '13', '4', '0', '2', '2026-04-22 22:15:53', '2026-04-22 22:15:53', '2026-04-22 22:15:53'),
('8', '14', '1', '0', '4', '2026-04-22 22:16:50', '2026-04-22 22:16:50', '2026-04-22 22:16:50'),
('9', '14', '2', '0', '4', '2026-04-22 22:16:50', '2026-04-22 22:16:50', '2026-04-22 22:16:50'),
('10', '14', '3', '0', '4', '2026-04-22 22:16:50', '2026-04-22 22:16:50', '2026-04-22 22:16:50'),
('11', '14', '4', '0', '4', '2026-04-22 22:16:50', '2026-04-22 22:16:50', '2026-04-22 22:16:50'),
('15', '15', '1', '0', '3', '2026-04-22 22:17:27', '2026-04-22 22:17:27', '2026-04-22 22:17:27'),
('16', '15', '2', '0', '3', '2026-04-22 22:17:27', '2026-04-22 22:17:27', '2026-04-22 22:17:27'),
('17', '15', '3', '0', '3', '2026-04-22 22:17:27', '2026-04-22 22:17:27', '2026-04-22 22:17:27'),
('18', '15', '4', '0', '3', '2026-04-22 22:17:27', '2026-04-22 22:17:27', '2026-04-22 22:17:27'),
('22', '16', '1', '0', '5', '2026-04-22 22:18:35', '2026-04-22 22:18:35', '2026-04-22 22:18:35'),
('23', '16', '2', '0', '5', '2026-04-22 22:18:35', '2026-04-22 22:18:35', '2026-04-22 22:18:35'),
('24', '16', '3', '0', '5', '2026-04-22 22:18:35', '2026-04-22 22:18:35', '2026-04-22 22:18:35'),
('25', '16', '4', '0', '5', '2026-04-22 22:18:35', '2026-04-22 22:18:35', '2026-04-22 22:18:35'),
('29', '17', '1', '0', '20', '2026-04-22 22:19:18', '2026-04-22 22:19:18', '2026-04-22 22:19:18'),
('30', '17', '2', '0', '20', '2026-04-22 22:19:18', '2026-04-22 22:19:18', '2026-04-22 22:19:18'),
('31', '17', '3', '0', '20', '2026-04-22 22:19:18', '2026-04-22 22:19:18', '2026-04-22 22:19:18'),
('32', '17', '4', '0', '20', '2026-04-22 22:19:18', '2026-04-22 22:19:18', '2026-04-22 22:19:18'),
('36', '18', '1', '0', '3', '2026-04-22 22:20:07', '2026-04-22 22:20:07', '2026-04-22 22:20:07'),
('37', '18', '2', '0', '3', '2026-04-22 22:20:07', '2026-04-22 22:20:07', '2026-04-22 22:20:07'),
('38', '18', '3', '0', '3', '2026-04-22 22:20:07', '2026-04-22 22:20:07', '2026-04-22 22:20:07'),
('39', '18', '4', '0', '3', '2026-04-22 22:20:07', '2026-04-22 22:20:07', '2026-04-22 22:20:07'),
('43', '19', '1', '0', '1', '2026-04-22 22:20:59', '2026-04-22 22:20:59', '2026-04-22 22:20:59'),
('44', '19', '2', '0', '1', '2026-04-22 22:20:59', '2026-04-22 22:20:59', '2026-04-22 22:20:59'),
('45', '19', '3', '0', '1', '2026-04-22 22:20:59', '2026-04-22 22:20:59', '2026-04-22 22:20:59'),
('46', '19', '4', '0', '1', '2026-04-22 22:20:59', '2026-04-22 22:20:59', '2026-04-22 22:20:59'),
('50', '20', '1', '0', '2', '2026-04-22 22:21:38', '2026-04-22 22:21:38', '2026-04-22 22:21:38'),
('51', '20', '2', '0', '2', '2026-04-22 22:21:38', '2026-04-22 22:21:38', '2026-04-22 22:21:38'),
('52', '20', '3', '0', '2', '2026-04-22 22:21:38', '2026-04-22 22:21:38', '2026-04-22 22:21:38'),
('53', '20', '4', '0', '2', '2026-04-22 22:21:38', '2026-04-22 22:21:38', '2026-04-22 22:21:38'),
('57', '21', '1', '0', '4', '2026-04-22 22:22:15', '2026-04-22 22:22:15', '2026-04-22 22:22:15'),
('58', '21', '2', '0', '4', '2026-04-22 22:22:15', '2026-04-22 22:22:15', '2026-04-22 22:22:15'),
('59', '21', '3', '0', '4', '2026-04-22 22:22:15', '2026-04-22 22:22:15', '2026-04-22 22:22:15'),
('60', '21', '4', '0', '4', '2026-04-22 22:22:15', '2026-04-22 22:22:15', '2026-04-22 22:22:15'),
('64', '22', '1', '0', '1', '2026-04-22 22:22:59', '2026-04-22 22:22:59', '2026-04-22 22:22:59'),
('65', '22', '2', '0', '1', '2026-04-22 22:22:59', '2026-04-22 22:22:59', '2026-04-22 22:22:59'),
('66', '22', '3', '0', '1', '2026-04-22 22:22:59', '2026-04-22 22:22:59', '2026-04-22 22:22:59'),
('67', '22', '4', '0', '1', '2026-04-22 22:22:59', '2026-04-22 22:22:59', '2026-04-22 22:22:59'),
('71', '23', '1', '0', '50', '2026-04-22 22:23:54', '2026-04-22 22:23:54', '2026-04-22 22:23:54'),
('72', '23', '2', '0', '50', '2026-04-22 22:23:54', '2026-04-22 22:23:54', '2026-04-22 22:23:54'),
('73', '23', '3', '0', '50', '2026-04-22 22:23:54', '2026-04-22 22:23:54', '2026-04-22 22:23:54'),
('74', '23', '4', '0', '50', '2026-04-22 22:23:54', '2026-04-22 22:23:54', '2026-04-22 22:23:54'),
('78', '24', '1', '0', '50', '2026-04-22 22:24:45', '2026-04-22 22:24:45', '2026-04-22 22:24:45'),
('79', '24', '2', '0', '50', '2026-04-22 22:24:45', '2026-04-22 22:24:45', '2026-04-22 22:24:45'),
('80', '24', '3', '0', '50', '2026-04-22 22:24:45', '2026-04-22 22:24:45', '2026-04-22 22:24:45'),
('81', '24', '4', '0', '50', '2026-04-22 22:24:45', '2026-04-22 22:24:45', '2026-04-22 22:24:45'),
('85', '25', '1', '0', '5', '2026-04-22 22:25:21', '2026-04-22 22:25:21', '2026-04-22 22:25:21'),
('86', '25', '2', '0', '5', '2026-04-22 22:25:21', '2026-04-22 22:25:21', '2026-04-22 22:25:21'),
('87', '25', '3', '0', '5', '2026-04-22 22:25:21', '2026-04-22 22:25:21', '2026-04-22 22:25:21'),
('88', '25', '4', '0', '5', '2026-04-22 22:25:21', '2026-04-22 22:25:21', '2026-04-22 22:25:21'),
('92', '26', '1', '0', '6', '2026-04-22 22:25:48', '2026-04-22 22:25:48', '2026-04-22 22:25:48'),
('93', '26', '2', '0', '6', '2026-04-22 22:25:48', '2026-04-22 22:25:48', '2026-04-22 22:25:48'),
('94', '26', '3', '0', '6', '2026-04-22 22:25:48', '2026-04-22 22:25:48', '2026-04-22 22:25:48'),
('95', '26', '4', '0', '6', '2026-04-22 22:25:48', '2026-04-22 22:25:48', '2026-04-22 22:25:48'),
('99', '27', '1', '0', '6', '2026-04-22 22:26:18', '2026-04-22 22:26:18', '2026-04-22 22:26:18'),
('100', '27', '2', '0', '6', '2026-04-22 22:26:18', '2026-04-22 22:26:18', '2026-04-22 22:26:18'),
('101', '27', '3', '0', '6', '2026-04-22 22:26:18', '2026-04-22 22:26:18', '2026-04-22 22:26:18'),
('102', '27', '4', '0', '6', '2026-04-22 22:26:18', '2026-04-22 22:26:18', '2026-04-22 22:26:18'),
('106', '28', '1', '0', '60', '2026-04-22 22:27:10', '2026-04-22 22:27:10', '2026-04-22 22:27:10'),
('107', '28', '2', '0', '60', '2026-04-22 22:27:10', '2026-04-22 22:27:10', '2026-04-22 22:27:10'),
('108', '28', '3', '0', '60', '2026-04-22 22:27:10', '2026-04-22 22:27:10', '2026-04-22 22:27:10'),
('109', '28', '4', '0', '60', '2026-04-22 22:27:10', '2026-04-22 22:27:10', '2026-04-22 22:27:10'),
('113', '29', '1', '0', '100', '2026-04-22 22:27:40', '2026-04-22 22:27:40', '2026-04-22 22:27:40'),
('114', '29', '2', '0', '100', '2026-04-22 22:27:40', '2026-04-22 22:27:40', '2026-04-22 22:27:40'),
('115', '29', '3', '0', '100', '2026-04-22 22:27:40', '2026-04-22 22:27:40', '2026-04-22 22:27:40'),
('116', '29', '4', '0', '100', '2026-04-22 22:27:40', '2026-04-22 22:27:40', '2026-04-22 22:27:40'),
('120', '30', '1', '0', '2', '2026-04-22 22:28:13', '2026-04-22 22:28:13', '2026-04-22 22:28:13'),
('121', '30', '2', '0', '2', '2026-04-22 22:28:13', '2026-04-22 22:28:13', '2026-04-22 22:28:13'),
('122', '30', '3', '0', '2', '2026-04-22 22:28:13', '2026-04-22 22:28:13', '2026-04-22 22:28:13'),
('123', '30', '4', '0', '2', '2026-04-22 22:28:13', '2026-04-22 22:28:13', '2026-04-22 22:28:13'),
('127', '31', '1', '0', '2', '2026-04-22 22:28:53', '2026-04-22 22:28:53', '2026-04-22 22:28:53'),
('128', '31', '2', '0', '2', '2026-04-22 22:28:53', '2026-04-22 22:28:53', '2026-04-22 22:28:53'),
('129', '31', '3', '0', '2', '2026-04-22 22:28:53', '2026-04-22 22:28:53', '2026-04-22 22:28:53'),
('130', '31', '4', '0', '2', '2026-04-22 22:28:53', '2026-04-22 22:28:53', '2026-04-22 22:28:53'),
('134', '32', '1', '0', '2', '2026-04-22 22:30:08', '2026-04-22 22:30:08', '2026-04-22 22:30:08'),
('135', '32', '2', '0', '2', '2026-04-22 22:30:08', '2026-04-22 22:30:08', '2026-04-22 22:30:08'),
('136', '32', '3', '0', '2', '2026-04-22 22:30:08', '2026-04-22 22:30:08', '2026-04-22 22:30:08'),
('137', '32', '4', '0', '2', '2026-04-22 22:30:08', '2026-04-22 22:30:08', '2026-04-22 22:30:08'),
('141', '33', '1', '0', '2', '2026-04-22 22:31:06', '2026-04-22 22:31:06', '2026-04-22 22:31:06'),
('142', '33', '2', '0', '2', '2026-04-22 22:31:06', '2026-04-22 22:31:06', '2026-04-22 22:31:06'),
('143', '33', '3', '0', '2', '2026-04-22 22:31:06', '2026-04-22 22:31:06', '2026-04-22 22:31:06'),
('144', '33', '4', '0', '2', '2026-04-22 22:31:06', '2026-04-22 22:31:06', '2026-04-22 22:31:06'),
('148', '34', '1', '0', '2', '2026-04-22 22:31:57', '2026-04-22 22:31:57', '2026-04-22 22:31:57'),
('149', '34', '2', '0', '2', '2026-04-22 22:31:57', '2026-04-22 22:31:57', '2026-04-22 22:31:57'),
('150', '34', '3', '0', '2', '2026-04-22 22:31:57', '2026-04-22 22:31:57', '2026-04-22 22:31:57'),
('151', '34', '4', '0', '2', '2026-04-22 22:31:57', '2026-04-22 22:31:57', '2026-04-22 22:31:57'),
('155', '35', '1', '0', '5', '2026-04-22 22:32:23', '2026-04-22 22:32:23', '2026-04-22 22:32:23'),
('156', '35', '2', '10', '5', '2026-04-27 18:32:29', '2026-04-22 22:32:23', '2026-04-27 18:32:29'),
('157', '35', '3', '0', '5', '2026-04-22 22:32:23', '2026-04-22 22:32:23', '2026-04-22 22:32:23'),
('158', '35', '4', '0', '5', '2026-04-22 22:32:23', '2026-04-22 22:32:23', '2026-04-22 22:32:23'),
('162', '36', '1', '0', '1', '2026-04-22 22:33:16', '2026-04-22 22:33:16', '2026-04-22 22:33:16'),
('163', '36', '2', '0', '1', '2026-04-22 22:33:16', '2026-04-22 22:33:16', '2026-04-22 22:33:16'),
('164', '36', '3', '0', '1', '2026-04-22 22:33:16', '2026-04-22 22:33:16', '2026-04-22 22:33:16'),
('165', '36', '4', '0', '1', '2026-04-22 22:33:16', '2026-04-22 22:33:16', '2026-04-22 22:33:16'),
('169', '37', '1', '0', '2', '2026-04-22 22:33:46', '2026-04-22 22:33:46', '2026-04-22 22:33:46'),
('170', '37', '2', '0', '2', '2026-04-22 22:33:46', '2026-04-22 22:33:46', '2026-04-22 22:33:46'),
('171', '37', '3', '0', '2', '2026-04-22 22:33:46', '2026-04-22 22:33:46', '2026-04-22 22:33:46'),
('172', '37', '4', '0', '2', '2026-04-22 22:33:46', '2026-04-22 22:33:46', '2026-04-22 22:33:46'),
('176', '38', '1', '0', '1', '2026-04-22 22:34:20', '2026-04-22 22:34:20', '2026-04-22 22:34:20'),
('177', '38', '2', '0', '1', '2026-04-22 22:34:20', '2026-04-22 22:34:20', '2026-04-22 22:34:20'),
('178', '38', '3', '0', '1', '2026-04-22 22:34:20', '2026-04-22 22:34:20', '2026-04-22 22:34:20'),
('179', '38', '4', '0', '1', '2026-04-22 22:34:20', '2026-04-22 22:34:20', '2026-04-22 22:34:20'),
('183', '39', '1', '0', '3', '2026-04-22 22:34:47', '2026-04-22 22:34:47', '2026-04-22 22:34:47'),
('184', '39', '2', '0', '3', '2026-04-22 22:34:47', '2026-04-22 22:34:47', '2026-04-22 22:34:47'),
('185', '39', '3', '0', '3', '2026-04-22 22:34:47', '2026-04-22 22:34:47', '2026-04-22 22:34:47'),
('186', '39', '4', '0', '3', '2026-04-22 22:34:47', '2026-04-22 22:34:47', '2026-04-22 22:34:47'),
('190', '40', '1', '0', '2', '2026-04-22 22:35:27', '2026-04-22 22:35:27', '2026-04-22 22:35:27'),
('191', '40', '2', '0', '2', '2026-04-22 22:35:27', '2026-04-22 22:35:27', '2026-04-22 22:35:27'),
('192', '40', '3', '0', '2', '2026-04-22 22:35:27', '2026-04-22 22:35:27', '2026-04-22 22:35:27'),
('193', '40', '4', '0', '2', '2026-04-22 22:35:27', '2026-04-22 22:35:27', '2026-04-22 22:35:27'),
('197', '41', '1', '0', '5', '2026-04-22 22:36:03', '2026-04-22 22:36:03', '2026-04-22 22:36:03'),
('198', '41', '2', '0', '5', '2026-04-22 22:36:03', '2026-04-22 22:36:03', '2026-04-22 22:36:03'),
('199', '41', '3', '0', '5', '2026-04-22 22:36:03', '2026-04-22 22:36:03', '2026-04-22 22:36:03'),
('200', '41', '4', '0', '5', '2026-04-22 22:36:03', '2026-04-22 22:36:03', '2026-04-22 22:36:03'),
('204', '42', '1', '0', '1', '2026-04-22 22:36:26', '2026-04-22 22:36:26', '2026-04-22 22:36:26'),
('205', '42', '2', '0', '1', '2026-04-22 22:36:26', '2026-04-22 22:36:26', '2026-04-22 22:36:26'),
('206', '42', '3', '0', '1', '2026-04-22 22:36:26', '2026-04-22 22:36:26', '2026-04-22 22:36:26'),
('207', '42', '4', '0', '1', '2026-04-22 22:36:26', '2026-04-22 22:36:26', '2026-04-22 22:36:26'),
('211', '43', '1', '0', '2', '2026-04-22 22:36:54', '2026-04-22 22:36:54', '2026-04-22 22:36:54'),
('212', '43', '2', '0', '2', '2026-04-22 22:36:54', '2026-04-22 22:36:54', '2026-04-22 22:36:54'),
('213', '43', '3', '0', '2', '2026-04-22 22:36:54', '2026-04-22 22:36:54', '2026-04-22 22:36:54'),
('214', '43', '4', '0', '2', '2026-04-22 22:36:54', '2026-04-22 22:36:54', '2026-04-22 22:36:54'),
('218', '44', '1', '0', '1', '2026-04-22 22:37:24', '2026-04-22 22:37:24', '2026-04-22 22:37:24'),
('219', '44', '2', '0', '1', '2026-04-22 22:37:24', '2026-04-22 22:37:24', '2026-04-22 22:37:24'),
('220', '44', '3', '0', '1', '2026-04-22 22:37:24', '2026-04-22 22:37:24', '2026-04-22 22:37:24'),
('221', '44', '4', '0', '1', '2026-04-22 22:37:24', '2026-04-22 22:37:24', '2026-04-22 22:37:24'),
('225', '45', '1', '0', '1', '2026-04-22 22:37:52', '2026-04-22 22:37:52', '2026-04-22 22:37:52'),
('226', '45', '2', '0', '1', '2026-04-22 22:37:52', '2026-04-22 22:37:52', '2026-04-22 22:37:52'),
('227', '45', '3', '0', '1', '2026-04-22 22:37:52', '2026-04-22 22:37:52', '2026-04-22 22:37:52'),
('228', '45', '4', '0', '1', '2026-04-22 22:37:52', '2026-04-22 22:37:52', '2026-04-22 22:37:52'),
('232', '46', '1', '0', '1', '2026-04-22 22:38:20', '2026-04-22 22:38:20', '2026-04-22 22:38:20'),
('233', '46', '2', '0', '1', '2026-04-22 22:38:20', '2026-04-22 22:38:20', '2026-04-22 22:38:20'),
('234', '46', '3', '0', '1', '2026-04-22 22:38:20', '2026-04-22 22:38:20', '2026-04-22 22:38:20'),
('235', '46', '4', '0', '1', '2026-04-22 22:38:20', '2026-04-22 22:38:20', '2026-04-22 22:38:20'),
('239', '47', '1', '0', '3', '2026-04-22 22:38:52', '2026-04-22 22:38:52', '2026-04-22 22:38:52'),
('240', '47', '2', '0', '3', '2026-04-22 22:38:52', '2026-04-22 22:38:52', '2026-04-22 22:38:52'),
('241', '47', '3', '0', '3', '2026-04-22 22:38:52', '2026-04-22 22:38:52', '2026-04-22 22:38:52'),
('242', '47', '4', '0', '3', '2026-04-22 22:38:52', '2026-04-22 22:38:52', '2026-04-22 22:38:52'),
('246', '48', '1', '0', '0', '2026-04-22 22:39:26', '2026-04-22 22:39:26', '2026-04-22 22:39:26'),
('247', '48', '2', '0', '0', '2026-04-22 22:39:26', '2026-04-22 22:39:26', '2026-04-22 22:39:26'),
('248', '48', '3', '0', '0', '2026-04-22 22:39:26', '2026-04-22 22:39:26', '2026-04-22 22:39:26'),
('249', '48', '4', '0', '0', '2026-04-22 22:39:26', '2026-04-22 22:39:26', '2026-04-22 22:39:26'),
('253', '49', '1', '0', '2', '2026-04-22 22:40:12', '2026-04-22 22:40:12', '2026-04-22 22:40:12'),
('254', '49', '2', '0', '2', '2026-04-22 22:40:12', '2026-04-22 22:40:12', '2026-04-22 22:40:12'),
('255', '49', '3', '0', '2', '2026-04-22 22:40:12', '2026-04-22 22:40:12', '2026-04-22 22:40:12'),
('256', '49', '4', '0', '2', '2026-04-22 22:40:12', '2026-04-22 22:40:12', '2026-04-22 22:40:12'),
('260', '50', '1', '0', '1', '2026-04-22 22:40:41', '2026-04-22 22:40:41', '2026-04-22 22:40:41'),
('261', '50', '2', '0', '1', '2026-04-22 22:40:41', '2026-04-22 22:40:41', '2026-04-22 22:40:41'),
('262', '50', '3', '0', '1', '2026-04-22 22:40:41', '2026-04-22 22:40:41', '2026-04-22 22:40:41'),
('263', '50', '4', '0', '1', '2026-04-22 22:40:41', '2026-04-22 22:40:41', '2026-04-22 22:40:41'),
('267', '51', '1', '0', '10', '2026-04-22 22:41:02', '2026-04-22 22:41:02', '2026-04-22 22:41:02'),
('268', '51', '2', '0', '10', '2026-04-22 22:41:02', '2026-04-22 22:41:02', '2026-04-22 22:41:02'),
('269', '51', '3', '0', '10', '2026-04-22 22:41:02', '2026-04-22 22:41:02', '2026-04-22 22:41:02'),
('270', '51', '4', '0', '10', '2026-04-22 22:41:02', '2026-04-22 22:41:02', '2026-04-22 22:41:02'),
('274', '52', '1', '0', '2', '2026-04-22 22:41:26', '2026-04-22 22:41:26', '2026-04-22 22:41:26'),
('275', '52', '2', '0', '2', '2026-04-22 22:41:26', '2026-04-22 22:41:26', '2026-04-22 22:41:26'),
('276', '52', '3', '0', '2', '2026-04-22 22:41:26', '2026-04-22 22:41:26', '2026-04-22 22:41:26'),
('277', '52', '4', '0', '2', '2026-04-22 22:41:26', '2026-04-22 22:41:26', '2026-04-22 22:41:26'),
('281', '53', '1', '0', '1', '2026-04-22 22:42:02', '2026-04-22 22:42:02', '2026-04-22 22:42:02'),
('282', '53', '2', '0', '1', '2026-04-22 22:42:02', '2026-04-22 22:42:02', '2026-04-22 22:42:02'),
('283', '53', '3', '0', '1', '2026-04-22 22:42:02', '2026-04-22 22:42:02', '2026-04-22 22:42:02'),
('284', '53', '4', '0', '1', '2026-04-22 22:42:02', '2026-04-22 22:42:02', '2026-04-22 22:42:02'),
('288', '54', '1', '0', '1', '2026-04-22 22:42:31', '2026-04-22 22:42:31', '2026-04-22 22:42:31'),
('289', '54', '2', '0', '1', '2026-04-22 22:42:31', '2026-04-22 22:42:31', '2026-04-22 22:42:31'),
('290', '54', '3', '0', '1', '2026-04-22 22:42:31', '2026-04-22 22:42:31', '2026-04-22 22:42:31'),
('291', '54', '4', '0', '1', '2026-04-22 22:42:31', '2026-04-22 22:42:31', '2026-04-22 22:42:31'),
('295', '55', '1', '0', '1', '2026-04-22 22:42:56', '2026-04-22 22:42:56', '2026-04-22 22:42:56'),
('296', '55', '2', '0', '1', '2026-04-22 22:42:56', '2026-04-22 22:42:56', '2026-04-22 22:42:56'),
('297', '55', '3', '0', '1', '2026-04-22 22:42:56', '2026-04-22 22:42:56', '2026-04-22 22:42:56'),
('298', '55', '4', '0', '1', '2026-04-22 22:42:56', '2026-04-22 22:42:56', '2026-04-22 22:42:56'),
('302', '56', '1', '0', '1', '2026-04-22 22:43:31', '2026-04-22 22:43:31', '2026-04-22 22:43:31'),
('303', '56', '2', '0', '1', '2026-04-22 22:43:31', '2026-04-22 22:43:31', '2026-04-22 22:43:31'),
('304', '56', '3', '0', '1', '2026-04-22 22:43:31', '2026-04-22 22:43:31', '2026-04-22 22:43:31'),
('305', '56', '4', '0', '1', '2026-04-22 22:43:31', '2026-04-22 22:43:31', '2026-04-22 22:43:31'),
('309', '57', '1', '0', '1', '2026-04-22 22:44:07', '2026-04-22 22:44:07', '2026-04-22 22:44:07'),
('310', '57', '2', '0', '1', '2026-04-22 22:44:07', '2026-04-22 22:44:07', '2026-04-22 22:44:07'),
('311', '57', '3', '0', '1', '2026-04-22 22:44:07', '2026-04-22 22:44:07', '2026-04-22 22:44:07'),
('312', '57', '4', '0', '1', '2026-04-22 22:44:07', '2026-04-22 22:44:07', '2026-04-22 22:44:07'),
('316', '58', '1', '0', '5', '2026-04-22 22:44:58', '2026-04-22 22:44:58', '2026-04-22 22:44:58'),
('317', '58', '2', '0', '5', '2026-04-22 22:44:58', '2026-04-22 22:44:58', '2026-04-22 22:44:58'),
('318', '58', '3', '0', '5', '2026-04-22 22:44:58', '2026-04-22 22:44:58', '2026-04-22 22:44:58'),
('319', '58', '4', '0', '5', '2026-04-22 22:44:58', '2026-04-22 22:44:58', '2026-04-22 22:44:58'),
('323', '59', '1', '0', '1', '2026-04-22 22:45:56', '2026-04-22 22:45:56', '2026-04-22 22:45:56'),
('324', '59', '2', '0', '1', '2026-04-22 22:45:56', '2026-04-22 22:45:56', '2026-04-22 22:45:56'),
('325', '59', '3', '0', '1', '2026-04-22 22:45:56', '2026-04-22 22:45:56', '2026-04-22 22:45:56'),
('326', '59', '4', '0', '1', '2026-04-22 22:45:56', '2026-04-22 22:45:56', '2026-04-22 22:45:56'),
('330', '60', '1', '0', '2', '2026-04-22 22:46:44', '2026-04-22 22:46:44', '2026-04-22 22:46:44'),
('331', '60', '2', '0', '2', '2026-04-22 22:46:44', '2026-04-22 22:46:44', '2026-04-22 22:46:44'),
('332', '60', '3', '0', '2', '2026-04-22 22:46:44', '2026-04-22 22:46:44', '2026-04-22 22:46:44'),
('333', '60', '4', '0', '2', '2026-04-22 22:46:44', '2026-04-22 22:46:44', '2026-04-22 22:46:44'),
('337', '61', '1', '0', '2', '2026-04-22 22:47:25', '2026-04-22 22:47:25', '2026-04-22 22:47:25'),
('338', '61', '2', '0', '2', '2026-04-22 22:47:25', '2026-04-22 22:47:25', '2026-04-22 22:47:25'),
('339', '61', '3', '0', '2', '2026-04-22 22:47:25', '2026-04-22 22:47:25', '2026-04-22 22:47:25'),
('340', '61', '4', '0', '2', '2026-04-22 22:47:25', '2026-04-22 22:47:25', '2026-04-22 22:47:25'),
('344', '62', '1', '0', '5', '2026-04-22 22:48:09', '2026-04-22 22:48:09', '2026-04-22 22:48:09'),
('345', '62', '2', '0', '5', '2026-04-22 22:48:09', '2026-04-22 22:48:09', '2026-04-22 22:48:09'),
('346', '62', '3', '0', '5', '2026-04-22 22:48:09', '2026-04-22 22:48:09', '2026-04-22 22:48:09'),
('347', '62', '4', '0', '5', '2026-04-22 22:48:09', '2026-04-22 22:48:09', '2026-04-22 22:48:09');
INSERT INTO `stock` (`stock_id`, `product_id`, `store_id`, `quantity_on_hand`, `reorder_level`, `last_counted_at`, `created_at`, `updated_at`) VALUES
('351', '63', '1', '0', '5', '2026-04-22 22:48:55', '2026-04-22 22:48:55', '2026-04-22 22:48:55'),
('352', '63', '2', '0', '5', '2026-04-22 22:48:55', '2026-04-22 22:48:55', '2026-04-22 22:48:55'),
('353', '63', '3', '0', '5', '2026-04-22 22:48:55', '2026-04-22 22:48:55', '2026-04-22 22:48:55'),
('354', '63', '4', '0', '5', '2026-04-22 22:48:55', '2026-04-22 22:48:55', '2026-04-22 22:48:55'),
('358', '64', '1', '0', '2', '2026-04-22 22:49:30', '2026-04-22 22:49:30', '2026-04-22 22:49:30'),
('359', '64', '2', '0', '2', '2026-04-22 22:49:30', '2026-04-22 22:49:30', '2026-04-22 22:49:30'),
('360', '64', '3', '0', '2', '2026-04-22 22:49:30', '2026-04-22 22:49:30', '2026-04-22 22:49:30'),
('361', '64', '4', '0', '2', '2026-04-22 22:49:30', '2026-04-22 22:49:30', '2026-04-22 22:49:30'),
('365', '65', '1', '0', '1', '2026-04-22 22:50:24', '2026-04-22 22:50:24', '2026-04-22 22:50:24'),
('366', '65', '2', '0', '1', '2026-04-22 22:50:24', '2026-04-22 22:50:24', '2026-04-22 22:50:24'),
('367', '65', '3', '0', '1', '2026-04-22 22:50:24', '2026-04-22 22:50:24', '2026-04-22 22:50:24'),
('368', '65', '4', '0', '1', '2026-04-22 22:50:24', '2026-04-22 22:50:24', '2026-04-22 22:50:24'),
('372', '66', '1', '0', '20', '2026-04-22 22:51:20', '2026-04-22 22:51:20', '2026-04-22 22:51:20'),
('373', '66', '2', '0', '20', '2026-04-22 22:51:20', '2026-04-22 22:51:20', '2026-04-22 22:51:20'),
('374', '66', '3', '0', '20', '2026-04-22 22:51:20', '2026-04-22 22:51:20', '2026-04-22 22:51:20'),
('375', '66', '4', '0', '20', '2026-04-22 22:51:20', '2026-04-22 22:51:20', '2026-04-22 22:51:20'),
('379', '67', '1', '0', '1', '2026-04-22 22:52:05', '2026-04-22 22:52:05', '2026-04-22 22:52:05'),
('380', '67', '2', '0', '1', '2026-04-22 22:52:05', '2026-04-22 22:52:05', '2026-04-22 22:52:05'),
('381', '67', '3', '0', '1', '2026-04-22 22:52:05', '2026-04-22 22:52:05', '2026-04-22 22:52:05'),
('382', '67', '4', '0', '1', '2026-04-22 22:52:05', '2026-04-22 22:52:05', '2026-04-22 22:52:05'),
('386', '68', '1', '0', '2', '2026-04-22 22:53:40', '2026-04-22 22:53:40', '2026-04-22 22:53:40'),
('387', '68', '2', '0', '2', '2026-04-22 22:53:40', '2026-04-22 22:53:40', '2026-04-22 22:53:40'),
('388', '68', '3', '0', '2', '2026-04-22 22:53:40', '2026-04-22 22:53:40', '2026-04-22 22:53:40'),
('389', '68', '4', '0', '2', '2026-04-22 22:53:40', '2026-04-22 22:53:40', '2026-04-22 22:53:40'),
('393', '69', '1', '0', '2', '2026-04-22 22:54:15', '2026-04-22 22:54:15', '2026-04-22 22:54:15'),
('394', '69', '2', '0', '2', '2026-04-22 22:54:15', '2026-04-22 22:54:15', '2026-04-22 22:54:15'),
('395', '69', '3', '0', '2', '2026-04-22 22:54:15', '2026-04-22 22:54:15', '2026-04-22 22:54:15'),
('396', '69', '4', '0', '2', '2026-04-22 22:54:15', '2026-04-22 22:54:15', '2026-04-22 22:54:15'),
('400', '70', '1', '0', '1', '2026-04-22 22:55:01', '2026-04-22 22:55:01', '2026-04-22 22:55:01'),
('401', '70', '2', '0', '1', '2026-04-22 22:55:01', '2026-04-22 22:55:01', '2026-04-22 22:55:01'),
('402', '70', '3', '0', '1', '2026-04-22 22:55:01', '2026-04-22 22:55:01', '2026-04-22 22:55:01'),
('403', '70', '4', '0', '1', '2026-04-22 22:55:01', '2026-04-22 22:55:01', '2026-04-22 22:55:01'),
('407', '71', '1', '0', '2', '2026-04-22 22:55:45', '2026-04-22 22:55:45', '2026-04-22 22:55:45'),
('408', '71', '2', '0', '2', '2026-04-22 22:55:45', '2026-04-22 22:55:45', '2026-04-22 22:55:45'),
('409', '71', '3', '0', '2', '2026-04-22 22:55:45', '2026-04-22 22:55:45', '2026-04-22 22:55:45'),
('410', '71', '4', '0', '2', '2026-04-22 22:55:45', '2026-04-22 22:55:45', '2026-04-22 22:55:45'),
('414', '72', '1', '0', '3', '2026-04-22 22:56:12', '2026-04-22 22:56:12', '2026-04-22 22:56:12'),
('415', '72', '2', '0', '3', '2026-04-22 22:56:12', '2026-04-22 22:56:12', '2026-04-22 22:56:12'),
('416', '72', '3', '0', '3', '2026-04-22 22:56:12', '2026-04-22 22:56:12', '2026-04-22 22:56:12'),
('417', '72', '4', '0', '3', '2026-04-22 22:56:12', '2026-04-22 22:56:12', '2026-04-22 22:56:12'),
('421', '73', '1', '0', '1', '2026-04-22 22:56:40', '2026-04-22 22:56:40', '2026-04-22 22:56:40'),
('422', '73', '2', '0', '1', '2026-04-22 22:56:40', '2026-04-22 22:56:40', '2026-04-22 22:56:40'),
('423', '73', '3', '0', '1', '2026-04-22 22:56:40', '2026-04-22 22:56:40', '2026-04-22 22:56:40'),
('424', '73', '4', '0', '1', '2026-04-22 22:56:40', '2026-04-22 22:56:40', '2026-04-22 22:56:40'),
('428', '74', '1', '0', '1', '2026-04-22 22:57:10', '2026-04-22 22:57:10', '2026-04-22 22:57:10'),
('429', '74', '2', '0', '1', '2026-04-22 22:57:10', '2026-04-22 22:57:10', '2026-04-22 22:57:10'),
('430', '74', '3', '0', '1', '2026-04-22 22:57:10', '2026-04-22 22:57:10', '2026-04-22 22:57:10'),
('431', '74', '4', '0', '1', '2026-04-22 22:57:10', '2026-04-22 22:57:10', '2026-04-22 22:57:10'),
('435', '75', '1', '0', '1', '2026-04-22 22:57:47', '2026-04-22 22:57:47', '2026-04-22 22:57:47'),
('436', '75', '2', '0', '1', '2026-04-22 22:57:47', '2026-04-22 22:57:47', '2026-04-22 22:57:47'),
('437', '75', '3', '0', '1', '2026-04-22 22:57:47', '2026-04-22 22:57:47', '2026-04-22 22:57:47'),
('438', '75', '4', '0', '1', '2026-04-22 22:57:47', '2026-04-22 22:57:47', '2026-04-22 22:57:47'),
('442', '76', '1', '0', '5', '2026-04-22 22:58:20', '2026-04-22 22:58:20', '2026-04-22 22:58:20'),
('443', '76', '2', '0', '5', '2026-04-22 22:58:20', '2026-04-22 22:58:20', '2026-04-22 22:58:20'),
('444', '76', '3', '0', '5', '2026-04-22 22:58:20', '2026-04-22 22:58:20', '2026-04-22 22:58:20'),
('445', '76', '4', '0', '5', '2026-04-22 22:58:20', '2026-04-22 22:58:20', '2026-04-22 22:58:20'),
('449', '77', '1', '0', '2', '2026-04-22 22:58:50', '2026-04-22 22:58:50', '2026-04-22 22:58:50'),
('450', '77', '2', '0', '2', '2026-04-22 22:58:50', '2026-04-22 22:58:50', '2026-04-22 22:58:50'),
('451', '77', '3', '0', '2', '2026-04-22 22:58:50', '2026-04-22 22:58:50', '2026-04-22 22:58:50'),
('452', '77', '4', '0', '2', '2026-04-22 22:58:50', '2026-04-22 22:58:50', '2026-04-22 22:58:50'),
('456', '78', '1', '0', '1', '2026-04-22 22:59:53', '2026-04-22 22:59:53', '2026-04-22 22:59:53'),
('457', '78', '2', '0', '1', '2026-04-22 22:59:53', '2026-04-22 22:59:53', '2026-04-22 22:59:53'),
('458', '78', '3', '0', '1', '2026-04-22 22:59:53', '2026-04-22 22:59:53', '2026-04-22 22:59:53'),
('459', '78', '4', '0', '1', '2026-04-22 22:59:53', '2026-04-22 22:59:53', '2026-04-22 22:59:53'),
('463', '79', '1', '0', '1', '2026-04-22 23:00:25', '2026-04-22 23:00:25', '2026-04-22 23:00:25'),
('464', '79', '2', '0', '1', '2026-04-22 23:00:25', '2026-04-22 23:00:25', '2026-04-22 23:00:25'),
('465', '79', '3', '0', '1', '2026-04-22 23:00:25', '2026-04-22 23:00:25', '2026-04-22 23:00:25'),
('466', '79', '4', '0', '1', '2026-04-22 23:00:25', '2026-04-22 23:00:25', '2026-04-22 23:00:25'),
('470', '80', '1', '0', '1', '2026-04-22 23:01:01', '2026-04-22 23:01:01', '2026-04-22 23:01:01'),
('471', '80', '2', '0', '1', '2026-04-22 23:01:01', '2026-04-22 23:01:01', '2026-04-22 23:01:01'),
('472', '80', '3', '0', '1', '2026-04-22 23:01:01', '2026-04-22 23:01:01', '2026-04-22 23:01:01'),
('473', '80', '4', '0', '1', '2026-04-22 23:01:01', '2026-04-22 23:01:01', '2026-04-22 23:01:01'),
('477', '81', '1', '0', '5', '2026-04-22 23:02:07', '2026-04-22 23:02:07', '2026-04-22 23:02:07'),
('478', '81', '2', '0', '5', '2026-04-22 23:02:07', '2026-04-22 23:02:07', '2026-04-22 23:02:07'),
('479', '81', '3', '0', '5', '2026-04-22 23:02:07', '2026-04-22 23:02:07', '2026-04-22 23:02:07'),
('480', '81', '4', '0', '5', '2026-04-22 23:02:07', '2026-04-22 23:02:07', '2026-04-22 23:02:07'),
('484', '82', '1', '0', '5', '2026-04-22 23:02:51', '2026-04-22 23:02:51', '2026-04-22 23:02:51'),
('485', '82', '2', '0', '5', '2026-04-22 23:02:51', '2026-04-22 23:02:51', '2026-04-22 23:02:51'),
('486', '82', '3', '0', '5', '2026-04-22 23:02:51', '2026-04-22 23:02:51', '2026-04-22 23:02:51'),
('487', '82', '4', '0', '5', '2026-04-22 23:02:51', '2026-04-22 23:02:51', '2026-04-22 23:02:51'),
('491', '83', '1', '0', '3', '2026-04-22 23:03:18', '2026-04-22 23:03:18', '2026-04-22 23:03:18'),
('492', '83', '2', '0', '3', '2026-04-22 23:03:18', '2026-04-22 23:03:18', '2026-04-22 23:03:18'),
('493', '83', '3', '0', '3', '2026-04-22 23:03:18', '2026-04-22 23:03:18', '2026-04-22 23:03:18'),
('494', '83', '4', '0', '3', '2026-04-22 23:03:18', '2026-04-22 23:03:18', '2026-04-22 23:03:18'),
('498', '84', '1', '0', '1', '2026-04-22 23:04:04', '2026-04-22 23:04:04', '2026-04-22 23:04:04'),
('499', '84', '2', '0', '1', '2026-04-22 23:04:04', '2026-04-22 23:04:04', '2026-04-22 23:04:04'),
('500', '84', '3', '0', '1', '2026-04-22 23:04:04', '2026-04-22 23:04:04', '2026-04-22 23:04:04'),
('501', '84', '4', '0', '1', '2026-04-22 23:04:04', '2026-04-22 23:04:04', '2026-04-22 23:04:04'),
('505', '85', '1', '0', '1', '2026-04-22 23:04:34', '2026-04-22 23:04:34', '2026-04-22 23:04:34'),
('506', '85', '2', '0', '1', '2026-04-22 23:04:34', '2026-04-22 23:04:34', '2026-04-22 23:04:34'),
('507', '85', '3', '0', '1', '2026-04-22 23:04:34', '2026-04-22 23:04:34', '2026-04-22 23:04:34'),
('508', '85', '4', '0', '1', '2026-04-22 23:04:34', '2026-04-22 23:04:34', '2026-04-22 23:04:34'),
('512', '86', '1', '0', '1', '2026-04-22 23:05:08', '2026-04-22 23:05:08', '2026-04-22 23:05:08'),
('513', '86', '2', '0', '1', '2026-04-22 23:05:08', '2026-04-22 23:05:08', '2026-04-22 23:05:08'),
('514', '86', '3', '0', '1', '2026-04-22 23:05:08', '2026-04-22 23:05:08', '2026-04-22 23:05:08'),
('515', '86', '4', '0', '1', '2026-04-22 23:05:08', '2026-04-22 23:05:08', '2026-04-22 23:05:08'),
('519', '87', '1', '0', '1', '2026-04-22 23:05:42', '2026-04-22 23:05:42', '2026-04-22 23:05:42'),
('520', '87', '2', '0', '1', '2026-04-22 23:05:42', '2026-04-22 23:05:42', '2026-04-22 23:05:42'),
('521', '87', '3', '0', '1', '2026-04-22 23:05:42', '2026-04-22 23:05:42', '2026-04-22 23:05:42'),
('522', '87', '4', '0', '1', '2026-04-22 23:05:42', '2026-04-22 23:05:42', '2026-04-22 23:05:42'),
('526', '88', '1', '0', '1', '2026-04-22 23:06:14', '2026-04-22 23:06:14', '2026-04-22 23:06:14'),
('527', '88', '2', '0', '1', '2026-04-22 23:06:14', '2026-04-22 23:06:14', '2026-04-22 23:06:14'),
('528', '88', '3', '0', '1', '2026-04-22 23:06:14', '2026-04-22 23:06:14', '2026-04-22 23:06:14'),
('529', '88', '4', '0', '1', '2026-04-22 23:06:14', '2026-04-22 23:06:14', '2026-04-22 23:06:14'),
('533', '89', '1', '0', '1', '2026-04-22 23:06:51', '2026-04-22 23:06:51', '2026-04-22 23:06:51'),
('534', '89', '2', '0', '1', '2026-04-22 23:06:51', '2026-04-22 23:06:51', '2026-04-22 23:06:51'),
('535', '89', '3', '0', '1', '2026-04-22 23:06:51', '2026-04-22 23:06:51', '2026-04-22 23:06:51'),
('536', '89', '4', '0', '1', '2026-04-22 23:06:51', '2026-04-22 23:06:51', '2026-04-22 23:06:51'),
('540', '90', '1', '0', '0', '2026-04-22 23:07:14', '2026-04-22 23:07:14', '2026-04-22 23:07:14'),
('541', '90', '2', '0', '0', '2026-04-22 23:07:14', '2026-04-22 23:07:14', '2026-04-22 23:07:14'),
('542', '90', '3', '0', '0', '2026-04-22 23:07:14', '2026-04-22 23:07:14', '2026-04-22 23:07:14'),
('543', '90', '4', '0', '0', '2026-04-22 23:07:14', '2026-04-22 23:07:14', '2026-04-22 23:07:14'),
('547', '91', '1', '0', '4', '2026-04-22 23:07:56', '2026-04-22 23:07:56', '2026-04-22 23:07:56'),
('548', '91', '2', '0', '4', '2026-04-22 23:07:56', '2026-04-22 23:07:56', '2026-04-22 23:07:56'),
('549', '91', '3', '0', '4', '2026-04-22 23:07:56', '2026-04-22 23:07:56', '2026-04-22 23:07:56'),
('550', '91', '4', '0', '4', '2026-04-22 23:07:56', '2026-04-22 23:07:56', '2026-04-22 23:07:56'),
('554', '92', '1', '0', '2', '2026-04-22 23:08:36', '2026-04-22 23:08:36', '2026-04-22 23:08:36'),
('555', '92', '2', '0', '2', '2026-04-22 23:08:36', '2026-04-22 23:08:36', '2026-04-22 23:08:36'),
('556', '92', '3', '0', '2', '2026-04-22 23:08:36', '2026-04-22 23:08:36', '2026-04-22 23:08:36'),
('557', '92', '4', '0', '2', '2026-04-22 23:08:36', '2026-04-22 23:08:36', '2026-04-22 23:08:36'),
('561', '93', '1', '0', '1', '2026-04-22 23:09:13', '2026-04-22 23:09:13', '2026-04-22 23:09:13'),
('562', '93', '2', '0', '1', '2026-04-22 23:09:13', '2026-04-22 23:09:13', '2026-04-22 23:09:13'),
('563', '93', '3', '0', '1', '2026-04-22 23:09:13', '2026-04-22 23:09:13', '2026-04-22 23:09:13'),
('564', '93', '4', '0', '1', '2026-04-22 23:09:13', '2026-04-22 23:09:13', '2026-04-22 23:09:13'),
('568', '94', '1', '0', '1', '2026-04-22 23:09:53', '2026-04-22 23:09:53', '2026-04-22 23:09:53'),
('569', '94', '2', '0', '1', '2026-04-22 23:09:53', '2026-04-22 23:09:53', '2026-04-22 23:09:53'),
('570', '94', '3', '0', '1', '2026-04-22 23:09:53', '2026-04-22 23:09:53', '2026-04-22 23:09:53'),
('571', '94', '4', '0', '1', '2026-04-22 23:09:53', '2026-04-22 23:09:53', '2026-04-22 23:09:53'),
('575', '95', '1', '0', '30', '2026-04-22 23:10:44', '2026-04-22 23:10:44', '2026-04-22 23:10:44'),
('576', '95', '2', '0', '30', '2026-04-22 23:10:44', '2026-04-22 23:10:44', '2026-04-22 23:10:44'),
('577', '95', '3', '0', '30', '2026-04-22 23:10:44', '2026-04-22 23:10:44', '2026-04-22 23:10:44'),
('578', '95', '4', '0', '30', '2026-04-22 23:10:44', '2026-04-22 23:10:44', '2026-04-22 23:10:44'),
('582', '96', '1', '0', '30', '2026-04-22 23:11:24', '2026-04-22 23:11:24', '2026-04-22 23:11:24'),
('583', '96', '2', '0', '30', '2026-04-22 23:11:24', '2026-04-22 23:11:24', '2026-04-22 23:11:24'),
('584', '96', '3', '0', '30', '2026-04-22 23:11:24', '2026-04-22 23:11:24', '2026-04-22 23:11:24'),
('585', '96', '4', '0', '30', '2026-04-22 23:11:24', '2026-04-22 23:11:24', '2026-04-22 23:11:24'),
('589', '97', '1', '0', '30', '2026-04-22 23:12:20', '2026-04-22 23:12:20', '2026-04-22 23:12:20'),
('590', '97', '2', '0', '30', '2026-04-22 23:12:20', '2026-04-22 23:12:20', '2026-04-22 23:12:20'),
('591', '97', '3', '0', '30', '2026-04-22 23:12:20', '2026-04-22 23:12:20', '2026-04-22 23:12:20'),
('592', '97', '4', '0', '30', '2026-04-22 23:12:20', '2026-04-22 23:12:20', '2026-04-22 23:12:20'),
('596', '98', '1', '0', '1', '2026-04-22 23:13:01', '2026-04-22 23:13:01', '2026-04-22 23:13:01'),
('597', '98', '2', '0', '1', '2026-04-22 23:13:01', '2026-04-22 23:13:01', '2026-04-22 23:13:01'),
('598', '98', '3', '0', '1', '2026-04-22 23:13:01', '2026-04-22 23:13:01', '2026-04-22 23:13:01'),
('599', '98', '4', '0', '1', '2026-04-22 23:13:01', '2026-04-22 23:13:01', '2026-04-22 23:13:01'),
('603', '1', '5', '0', '10', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('604', '2', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('605', '3', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('606', '4', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('607', '5', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('608', '6', '5', '0', '30', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('609', '7', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('610', '8', '5', '0', '10', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('611', '9', '5', '0', '3', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('612', '10', '5', '0', '10', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('613', '11', '5', '0', '15', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('614', '12', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('615', '13', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('616', '14', '5', '0', '4', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('617', '15', '5', '0', '3', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('618', '16', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('619', '17', '5', '0', '20', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('620', '18', '5', '0', '3', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('621', '19', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('622', '20', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('623', '21', '5', '0', '4', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('624', '22', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('625', '23', '5', '0', '50', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('626', '24', '5', '0', '50', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('627', '25', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('628', '26', '5', '0', '6', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('629', '27', '5', '0', '6', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('630', '28', '5', '0', '60', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('631', '29', '5', '0', '100', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('632', '30', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('633', '31', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('634', '32', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('635', '33', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('636', '34', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('637', '35', '5', '5', '5', '2026-04-27 18:32:29', '2026-04-23 16:59:36', '2026-04-27 18:32:29'),
('638', '36', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('639', '37', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('640', '38', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('641', '39', '5', '0', '3', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('642', '40', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('643', '41', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('644', '42', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('645', '43', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('646', '44', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('647', '45', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('648', '46', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('649', '47', '5', '0', '3', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('650', '48', '5', '0', '0', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('651', '49', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('652', '50', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('653', '51', '5', '0', '10', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('654', '52', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('655', '53', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('656', '54', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('657', '55', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('658', '56', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36');
INSERT INTO `stock` (`stock_id`, `product_id`, `store_id`, `quantity_on_hand`, `reorder_level`, `last_counted_at`, `created_at`, `updated_at`) VALUES
('659', '57', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('660', '58', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('661', '59', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('662', '60', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('663', '61', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('664', '62', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('665', '63', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('666', '64', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('667', '65', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('668', '66', '5', '0', '20', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('669', '67', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('670', '68', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('671', '69', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('672', '70', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('673', '71', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('674', '72', '5', '0', '3', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('675', '73', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('676', '74', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('677', '75', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('678', '76', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('679', '77', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('680', '78', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('681', '79', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('682', '80', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('683', '81', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('684', '82', '5', '0', '5', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('685', '83', '5', '0', '3', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('686', '84', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('687', '85', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('688', '86', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('689', '87', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('690', '88', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('691', '89', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('692', '90', '5', '0', '0', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('693', '91', '5', '0', '4', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('694', '92', '5', '0', '2', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('695', '93', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('696', '94', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('697', '95', '5', '0', '30', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('698', '96', '5', '0', '30', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('699', '97', '5', '0', '30', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36'),
('700', '98', '5', '0', '1', '2026-04-23 16:59:36', '2026-04-23 16:59:36', '2026-04-23 16:59:36');

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
  `quantity_consumed` int(11) NOT NULL DEFAULT 0,
  `quantity_returned` int(11) NOT NULL DEFAULT 0,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`issue_item_id`),
  KEY `issue_id` (`issue_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_issue_items_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `stock_issues` (`issue_id`) ON DELETE CASCADE,
  CONSTRAINT `stock_issue_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock_issue_items`
-- ----------------------------
INSERT INTO `stock_issue_items` (`issue_item_id`, `issue_id`, `product_id`, `quantity_issued`, `quantity_consumed`, `quantity_returned`, `batch_number`, `expiry_date`, `unit_price`, `remarks`, `created_at`, `updated_at`) VALUES
('1', '1', '35', '5', '0', '0', NULL, NULL, '5.00', 'test remarks', '2026-04-23 17:23:16', '2026-04-27 19:02:02'),
('2', '2', '35', '2', '1', '0', NULL, NULL, '5.00', 'requested bar bar man', '2026-04-24 13:13:46', '2026-04-27 19:12:39'),
('3', '3', '35', '3', '0', '0', NULL, NULL, '5.00', '', '2026-04-27 18:32:29', '2026-04-27 19:02:02');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock_issues`
-- ----------------------------
INSERT INTO `stock_issues` (`issue_id`, `issue_number`, `requisition_id`, `store_id`, `department_id`, `issued_by`, `issue_date`, `received_by`, `received_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
('1', 'ISS-20260423-0001', '1', '5', '2', '2', '2026-04-23 15:23:16', NULL, NULL, 'issued', 'test issue note', '2026-04-23 17:23:16', '2026-04-23 17:23:16'),
('2', 'ISS-20260424-0001', NULL, '5', '2', '5', '2026-04-24 11:13:46', NULL, NULL, 'issued', '', '2026-04-24 13:13:46', '2026-04-24 13:13:46'),
('3', 'ISS-20260427-0001', NULL, '5', '2', '5', '2026-04-27 16:32:29', NULL, NULL, 'issued', '', '2026-04-27 18:32:29', '2026-04-27 18:32:29');

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stock_transactions`
-- ----------------------------
INSERT INTO `stock_transactions` (`transaction_id`, `product_id`, `store_id`, `transaction_type`, `reference_type`, `reference_id`, `quantity_change`, `unit_price`, `total_value`, `performed_by`, `transaction_date`, `notes`) VALUES
('1', '35', '5', 'receipt', 'GRN', '1', '15', '5.00', '75.00', '2', '2026-04-23 15:22:09', NULL),
('2', '35', '5', 'issue', 'ISSUE', '1', '-5', '5.00', '25.00', '2', '2026-04-23 15:23:16', NULL),
('3', '35', '2', 'receipt', 'ISSUE_RECEIPT', '1', '5', '5.00', '25.00', '2', '2026-04-23 15:23:16', NULL),
('4', '35', '5', 'issue', 'DIRECT_ISSUE', '2', '-2', '5.00', '10.00', '5', '2026-04-24 11:13:46', NULL),
('5', '35', '2', 'receipt', 'DIRECT_ISSUE_RECEIPT', '2', '2', '5.00', '10.00', '5', '2026-04-24 11:13:46', NULL),
('6', '35', '5', 'issue', 'DIRECT_ISSUE', '3', '-3', '5.00', '15.00', '5', '2026-04-27 16:32:29', NULL),
('7', '35', '2', 'receipt', 'DIRECT_ISSUE_RECEIPT', '3', '3', '5.00', '15.00', '5', '2026-04-27 16:32:29', NULL),
('8', '35', '5', 'consumption', 'consumption_record', '1', '-1', NULL, NULL, '6', '2026-04-27 17:12:39', '');

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `stores`
-- ----------------------------
INSERT INTO `stores` (`store_id`, `store_name`, `store_code`, `location`, `responsible_user_id`, `description`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Kitchen Store', 'KIT001', 'Ground Floor Kitchen', NULL, 'Main kitchen storage', 'active', '2026-04-20 18:28:51', '2026-04-20 18:28:51'),
('2', 'Bar Store', 'BAR001', 'Ground Floor Bar', NULL, 'Beverage and bar supplies', 'active', '2026-04-20 18:28:51', '2026-04-20 18:28:51'),
('3', 'Housekeeping Store', 'HNK001', 'Basement', NULL, 'Cleaning and laundry supplies', 'active', '2026-04-20 18:28:51', '2026-04-20 18:28:51'),
('4', 'Maintenance Store', 'MNT001', 'Basement', NULL, 'Tools and maintenance supplies', 'active', '2026-04-20 18:28:51', '2026-04-20 18:28:51'),
('5', 'Main Stores', 'MAIN001', 'Hotel Ground Floor', '2', '', 'active', '2026-04-23 16:59:36', '2026-04-23 16:59:36');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `suppliers`
-- ----------------------------
INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `city`, `postal_code`, `payment_terms`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Test Supplier Groceries', 'M Zhuwao', 'azaways@gmail.com', '0719952811', '123 Middle of Nowhere', 'Mutare', '06', 'cash', 'active', '2026-04-23 17:20:59', '2026-04-23 17:20:59');

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of `users`
-- ----------------------------
INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `role_id`, `status`, `created_at`, `updated_at`) VALUES
('1', 'admin', 'admin@hotel.com', '$2y$10$j2.FKXRaipAUbSlYU1NhQOBNCN/3R3lcSmYjhujl/gfdQ7pUc9./e', 'System Admin', '1', 'active', '2026-04-20 18:28:52', '2026-04-20 18:29:06'),
('2', 'storekeeper1', 'storekeeper@hotel.com', '$2y$10$kVyzVKyzdQ0gk9FqO7D/HuVhJUQ0hwHdSMbWQhzeLoxYegVJDqh.a', 'John Storekeeper', '2', 'active', '2026-04-20 18:28:52', '2026-04-23 17:13:03'),
('3', 'kmbi', 'mugwagwakumbi@gmail.com', '$2y$10$iyiDD/md.h858aFRstaYnufZICth1m6jIv0e54b3R5slsiFV3/hSC', 'Kumbiraishe Mugwagwa', '1', 'active', '2026-04-22 23:14:54', '2026-04-22 23:14:54'),
('4', 'rukudzo1', 'mugadzarukudzo33@gmail.com', '$2y$10$qsx.zlpCZPyldLRgmklSGutfJXXlMZ2lgOb.mrkgB6.eQUcL9m/oe', 'Rukudzo Mugadza', '7', 'active', '2026-04-23 18:18:27', '2026-04-23 18:18:27'),
('5', 'aubree', 'amzhuwao@gmail.com', '$2y$10$vV6wI.3NZl9QuQhue7ywxO1fsRchesyuhWlLLHZcCEzw75rqI8UoG', 'Aubrey Zhuwao', '1', 'active', '2026-04-23 18:31:13', '2026-04-23 18:31:13'),
('6', 'John', 'email@doe.com', '$2y$10$r8NoIsNQTnsEUVLgN7S6XOMv4ReWWY8PJ649lI8okGBbSn/HjJ1pa', 'John Doe', '4', 'active', '2026-04-23 18:48:18', '2026-04-23 18:48:18');

SET FOREIGN_KEY_CHECKS = 1;
