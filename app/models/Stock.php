<?php
/**
 * Stock Model
 */

class Stock extends Model {
    protected $table = 'stock';

    /**
     * Get stock with product and store details
     */
    public function getWithDetails($stockId) {
        $sql = "SELECT s.*, p.product_name, p.product_code, p.unit_of_measure,
                st.store_name
                FROM stock s
                JOIN products p ON s.product_id = p.product_id
                JOIN stores st ON s.store_id = st.store_id
                WHERE s.stock_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $stockId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get stock by product and store
     */
    public function getByProductAndStore($productId, $storeId) {
        $sql = "SELECT * FROM stock 
                WHERE product_id = ? AND store_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $productId, $storeId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all stock for a store
     */
    public function getByStore($storeId) {
        $sql = "SELECT s.*, p.product_name, p.product_code, p.unit_of_measure,
                c.category_name
                FROM stock s
                JOIN products p ON s.product_id = p.product_id
                JOIN categories c ON p.category_id = c.category_id
                WHERE s.store_id = ? AND p.status = 'active'
                ORDER BY p.product_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update stock quantity
     */
    public function updateQuantity($productId, $storeId, $quantityChange) {
        $sql = "UPDATE stock 
                SET quantity_on_hand = quantity_on_hand + ?,
                    updated_at = NOW()
                WHERE product_id = ? AND store_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $quantityChange, $productId, $storeId);
        
        return $stmt->execute();
    }

    /**
     * Set stock quantity
     */
    public function setQuantity($productId, $storeId, $quantity) {
        $sql = "UPDATE stock 
                SET quantity_on_hand = ?,
                    updated_at = NOW()
                WHERE product_id = ? AND store_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $quantity, $productId, $storeId);
        
        return $stmt->execute();
    }

    /**
     * Create new stock record
     */
    public function createStock($productId, $storeId, $quantity = 0, $reorderLevel = 0) {
        $sql = "INSERT INTO stock (product_id, store_id, quantity_on_hand, reorder_level) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiii', $productId, $storeId, $quantity, $reorderLevel);
        
        return $stmt->execute();
    }

    /**
     * Get total stock value
     */
    public function getTotalValue($storeId = null) {
        $sql = "SELECT COALESCE(SUM(s.quantity_on_hand * 
                COALESCE((SELECT unit_price FROM grn_items WHERE product_id = s.product_id 
                ORDER BY grn_item_id DESC LIMIT 1), 0)), 0) as total_value 
                FROM stock s";
        
        if ($storeId) {
            $sql .= " WHERE s.store_id = $storeId";
        }
        
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total_value'];
    }
}
