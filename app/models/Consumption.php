<?php
/**
 * Consumption Model
 * Handles consumption record tracking and permissions
 */

class Consumption extends Model {
    protected $table = 'consumption_records';

    /**
     * Get consumption permissions for a user
     */
    public function getPermissionsForUser($userId) {
        $sql = "SELECT cp.*, d.dept_name, u.full_name as assigned_by_name
                FROM consumption_permissions cp
                JOIN departments d ON cp.department_id = d.dept_id
                JOIN users u ON cp.assigned_by = u.user_id
                WHERE cp.user_id = ? AND cp.status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getPermissionsForUser: " . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Check if a user has permission to log consumption for a department
     */
    public function hasPermission($userId, $departmentId) {
        $sql = "SELECT permission_id FROM consumption_permissions 
                WHERE user_id = ? AND department_id = ? AND status = 'active' 
                AND can_log_consumption = 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in hasPermission: " . $this->db->error);
            return false;
        }
        $stmt->bind_param('ii', $userId, $departmentId);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Assign consumption logging permission
     */
    public function assignPermission($userId, $departmentId, $assignedByUserId) {
        $sql = "INSERT INTO consumption_permissions (user_id, department_id, assigned_by, can_log_consumption, status)
                VALUES (?, ?, ?, 1, 'active')
                ON DUPLICATE KEY UPDATE
                status = 'active', revoked_at = NULL, assigned_by = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in assignPermission: " . $this->db->error);
            return false;
        }
        $stmt->bind_param('iiii', $userId, $departmentId, $assignedByUserId, $assignedByUserId);
        
        return $stmt->execute();
    }

    /**
     * Revoke consumption logging permission
     */
    public function revokePermission($userId, $departmentId) {
        $sql = "UPDATE consumption_permissions 
                SET status = 'revoked', revoked_at = NOW()
                WHERE user_id = ? AND department_id = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in revokePermission: " . $this->db->error);
            return false;
        }
        $stmt->bind_param('ii', $userId, $departmentId);
        
        return $stmt->execute();
    }

    /**
     * Get all users with consumption permissions for a department
     */
    public function getPermissionedUsersForDepartment($departmentId) {
        $sql = "SELECT cp.*, u.full_name, u.username, r.role_name
                FROM consumption_permissions cp
                JOIN users u ON cp.user_id = u.user_id
                JOIN roles r ON u.role_id = r.role_id
                WHERE cp.department_id = ? AND cp.status = 'active'
                ORDER BY u.full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getPermissionedUsersForDepartment: " . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Log consumption for an issue item
     */
    public function logConsumption($issueItemId, $quantityConsumed, $loggedByUserId, $notes = '') {
        $this->db->begin_transaction();
        
        try {
            // Insert consumption record
            $sql = "INSERT INTO consumption_records (issue_item_id, quantity_consumed, logged_by, notes)
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Error in logConsumption: " . $this->db->error);
            }
            $stmt->bind_param('iiis', $issueItemId, $quantityConsumed, $loggedByUserId, $notes);
            $stmt->execute();
            
            $consumptionId = $this->db->insert_id;
            
            // Update stock_issue_items with consumed quantity
            $sql = "UPDATE stock_issue_items 
                    SET quantity_consumed = quantity_consumed + ?
                    WHERE issue_item_id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Error updating stock_issue_items: " . $this->db->error);
            }
            $stmt->bind_param('ii', $quantityConsumed, $issueItemId);
            $stmt->execute();
            
                // Get issue details and resolve store to update stock immediately
                $sql = "SELECT sii.product_id, si.store_id AS source_store_id, si.department_id
                    FROM stock_issue_items sii
                    JOIN stock_issues si ON sii.issue_id = si.issue_id
                    WHERE sii.issue_item_id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Error fetching issue details: " . $this->db->error);
            }
            $stmt->bind_param('i', $issueItemId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result) {
                $targetStoreId = $this->resolveDepartmentStoreId((int)$result['department_id']);
                if ($targetStoreId <= 0) {
                    $targetStoreId = (int)$result['source_store_id'];
                }

                // Decrease department/store stock immediately after consumption is logged.
                $this->adjustStoreStock((int)$result['product_id'], $targetStoreId, -1 * (int)$quantityConsumed);

                // Create stock transaction for consumption
                $negativeQty = -$quantityConsumed;
                $sql = "INSERT INTO stock_transactions 
                        (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, performed_by, notes)
                        VALUES (?, ?, 'consumption', 'consumption_record', ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new Exception("SQL Error creating stock transaction: " . $this->db->error);
                }
                $stmt->bind_param('iiiiis', $result['product_id'], $targetStoreId, $consumptionId, $negativeQty, $loggedByUserId, $notes);
                $stmt->execute();
            }
            
            $this->db->commit();
            return ['success' => true, 'consumption_id' => $consumptionId, 'message' => 'Consumption logged successfully'];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Exception in logConsumption: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error logging consumption: ' . $e->getMessage()];
        }
    }

    /**
     * Get consumption records for an issue
     */
    public function getConsumptionRecordsForIssue($issueId) {
        $sql = "SELECT cr.*, sii.product_id, p.product_name, p.product_code, sii.quantity_issued,
                        sii.quantity_consumed, sii.quantity_returned, u.full_name as logged_by_name
                FROM consumption_records cr
                JOIN stock_issue_items sii ON cr.issue_item_id = sii.issue_item_id
                JOIN products p ON sii.product_id = p.product_id
                JOIN users u ON cr.logged_by = u.user_id
                WHERE sii.issue_id = ?
                ORDER BY cr.log_date DESC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getConsumptionRecordsForIssue: " . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $issueId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get consumption records for a department within a date range
     */
    public function getConsumptionRecordsForDepartment($departmentId, $startDate = null, $endDate = null) {
        $sql = "SELECT cr.*, sii.product_id, p.product_name, p.product_code, sii.quantity_issued,
                        sii.quantity_consumed, sii.quantity_returned, u.full_name as logged_by_name,
                        si.issue_number, d.dept_name, st.store_name
                FROM consumption_records cr
                JOIN stock_issue_items sii ON cr.issue_item_id = sii.issue_item_id
                JOIN stock_issues si ON sii.issue_id = si.issue_id
                JOIN products p ON sii.product_id = p.product_id
                JOIN users u ON cr.logged_by = u.user_id
                JOIN departments d ON si.department_id = d.dept_id
                JOIN stores st ON si.store_id = st.store_id
                WHERE si.department_id = ?";
        
        $types = 'i';
        $params = [$departmentId];
        
        if ($startDate) {
            $sql .= " AND cr.log_date >= ?";
            $types .= 's';
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND cr.log_date <= ?";
            $types .= 's';
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY cr.log_date DESC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getConsumptionRecordsForDepartment: " . $this->db->error);
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Log item return/damage
     */
    public function logReturn($issueItemId, $quantityReturned, $loggedByUserId, $reason = '', $notes = '') {
        $this->db->begin_transaction();
        
        try {
            // Update stock_issue_items with returned quantity
            $sql = "UPDATE stock_issue_items 
                    SET quantity_returned = quantity_returned + ?
                    WHERE issue_item_id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Error updating stock_issue_items for return: " . $this->db->error);
            }
            $stmt->bind_param('ii', $quantityReturned, $issueItemId);
            $stmt->execute();
            
                // Get issue details and resolve store to update stock immediately
                $sql = "SELECT sii.product_id, si.store_id AS source_store_id, si.department_id
                    FROM stock_issue_items sii
                    JOIN stock_issues si ON sii.issue_id = si.issue_id
                    WHERE sii.issue_item_id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Error fetching issue details for return: " . $this->db->error);
            }
            $stmt->bind_param('i', $issueItemId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result) {
                $targetStoreId = $this->resolveDepartmentStoreId((int)$result['department_id']);
                if ($targetStoreId <= 0) {
                    $targetStoreId = (int)$result['source_store_id'];
                }

                // Increase department/store stock immediately after return is logged.
                $this->adjustStoreStock((int)$result['product_id'], $targetStoreId, (int)$quantityReturned);

                // Create stock transaction for return
                $sql = "INSERT INTO stock_transactions 
                        (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, performed_by, notes)
                        VALUES (?, ?, 'return', 'issue_item_return', ?, ?, ?, ?)";
                
                $note = "Return: $reason - $notes";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new Exception("SQL Error creating return transaction: " . $this->db->error);
                }
                $stmt->bind_param('iiiiis', $result['product_id'], $targetStoreId, $issueItemId, $quantityReturned, $loggedByUserId, $note);
                $stmt->execute();
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Item return logged successfully'];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Exception in logReturn: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error logging return: ' . $e->getMessage()];
        }
    }

    /**
     * Get summary of consumption for an issue item
     */
    public function getItemConsumptionSummary($issueItemId) {
        $sql = "SELECT 
                sii.quantity_issued,
                sii.quantity_consumed,
                sii.quantity_returned,
                (sii.quantity_issued - sii.quantity_consumed - sii.quantity_returned) as quantity_unaccounted,
                p.product_name,
                COUNT(cr.consumption_id) as consumption_logs
                FROM stock_issue_items sii
                JOIN products p ON sii.product_id = p.product_id
                LEFT JOIN consumption_records cr ON sii.issue_item_id = cr.issue_item_id
                WHERE sii.issue_item_id = ?
                GROUP BY sii.issue_item_id";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getItemConsumptionSummary: " . $this->db->error);
            return null;
        }
        $stmt->bind_param('i', $issueItemId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Resolve department store by convention (dept_code + store mapping).
     */
    private function resolveDepartmentStoreId($departmentId) {
        $sql = "SELECT s.store_id
                FROM departments d
                JOIN stores s ON s.status = 'active'
                    AND (
                        s.store_code = CONCAT(d.dept_code, '001')
                        OR s.store_code LIKE CONCAT(d.dept_code, '%')
                        OR s.store_name LIKE CONCAT(d.dept_name, '%')
                    )
                WHERE d.dept_id = ?
                ORDER BY (s.store_code = CONCAT(d.dept_code, '001')) DESC, s.store_id ASC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error resolving department store: " . $this->db->error);
        }

        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ? (int)$row['store_id'] : 0;
    }

    /**
     * Apply stock delta immediately for a product/store.
     */
    private function adjustStoreStock($productId, $storeId, $deltaQty) {
        $sql = "UPDATE stock
                SET quantity_on_hand = quantity_on_hand + ?, updated_at = NOW()
                WHERE product_id = ? AND store_id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error updating stock quantity: " . $this->db->error);
        }

        $stmt->bind_param('iii', $deltaQty, $productId, $storeId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return;
        }

        $reorderLevel = 0;
        $reorderSql = "SELECT reorder_level FROM products WHERE product_id = ? LIMIT 1";
        $reorderStmt = $this->db->prepare($reorderSql);
        if (!$reorderStmt) {
            throw new Exception("SQL Error loading product reorder level: " . $this->db->error);
        }

        $reorderStmt->bind_param('i', $productId);
        $reorderStmt->execute();
        $row = $reorderStmt->get_result()->fetch_assoc();
        if ($row) {
            $reorderLevel = (int)$row['reorder_level'];
        }

        $initialQty = max(0, $deltaQty);
        $insertSql = "INSERT INTO stock (product_id, store_id, quantity_on_hand, reorder_level)
                      VALUES (?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertSql);
        if (!$insertStmt) {
            throw new Exception("SQL Error creating stock row: " . $this->db->error);
        }

        $insertStmt->bind_param('iiii', $productId, $storeId, $initialQty, $reorderLevel);
        $insertStmt->execute();

        if ($deltaQty < 0) {
            $followSql = "UPDATE stock
                          SET quantity_on_hand = quantity_on_hand + ?, updated_at = NOW()
                          WHERE product_id = ? AND store_id = ?";
            $followStmt = $this->db->prepare($followSql);
            if (!$followStmt) {
                throw new Exception("SQL Error applying negative stock delta: " . $this->db->error);
            }

            $followStmt->bind_param('iii', $deltaQty, $productId, $storeId);
            $followStmt->execute();
        }
    }
}
?>

