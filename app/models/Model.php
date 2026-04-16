<?php
/**
 * Base Model Class
 * Parent class for all models
 */

class Model {
    protected $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find by ID
     */
    public function findById($id, $table = null) {
        $table = $table ?: $this->table;
        $id_field = $this->getIdField($table);
        
        $sql = "SELECT * FROM $table WHERE $id_field = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all records
     */
    public function getAll($table = null, $where = '', $limit = '') {
        $table = $table ?: $this->table;
        $sql = "SELECT * FROM $table";
        
        if (!empty($where)) {
            $sql .= " WHERE " . $where;
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT " . $limit;
        }
        
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Insert record
     */
    public function insert($data, $table = null) {
        $table = $table ?: $this->table;
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        
        $types = $this->getTypes($values);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    /**
     * Update record
     */
    public function update($id, $data, $table = null) {
        $table = $table ?: $this->table;
        $id_field = $this->getIdField($table);
        
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "$key = ?, ";
        }
        $set = rtrim($set, ', ');
        
        $values = array_values($data);
        $values[] = $id;
        
        $sql = "UPDATE $table SET $set WHERE $id_field = ?";
        $stmt = $this->db->prepare($sql);
        
        $types = $this->getTypes($values);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    /**
     * Delete record
     */
    public function delete($id, $table = null) {
        $table = $table ?: $this->table;
        $id_field = $this->getIdField($table);
        
        $sql = "DELETE FROM $table WHERE $id_field = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        
        return $stmt->execute();
    }

    /**
     * Count records
     */
    public function count($where = '', $table = null) {
        $table = $table ?: $this->table;
        $sql = "SELECT COUNT(*) as count FROM $table";
        
        if (!empty($where)) {
            $sql .= " WHERE " . $where;
        }
        
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    /**
     * Get ID field name for a table
     */
    protected function getIdField($table) {
        // Explicit mapping for schema fields that do not follow simple singularization.
        $map = [
            'roles' => 'role_id',
            'users' => 'user_id',
            'stores' => 'store_id',
            'departments' => 'dept_id',
            'suppliers' => 'supplier_id',
            'categories' => 'category_id',
            'products' => 'product_id',
            'stock' => 'stock_id',
            'grn' => 'grn_id',
            'grn_items' => 'grn_item_id',
            'requisitions' => 'requisition_id',
            'requisition_items' => 'req_item_id',
            'stock_issues' => 'issue_id',
            'stock_issue_items' => 'issue_item_id',
            'stock_transactions' => 'transaction_id',
            'batch_tracking' => 'batch_id',
            'stock_adjustments' => 'adjustment_id',
            'adjustment_items' => 'adj_item_id',
            'reorder_alerts' => 'alert_id',
            'audit_log' => 'log_id'
        ];

        if (isset($map[$table])) {
            return $map[$table];
        }

        // Fallback convention: singular table + _id.
        return rtrim($table, 's') . '_id';
    }

    /**
     * Get parameter types for bind_param
     */
    protected function getTypes($values) {
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
}
