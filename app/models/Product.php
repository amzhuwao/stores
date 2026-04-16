<?php
/**
 * Product Model
 */

class Product extends Model {
    protected $table = 'products';

    /**
     * Get product with category
     */
    public function getWithCategory($productId) {
        $sql = "SELECT p.*, c.category_name 
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                WHERE p.product_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all products with stock
     */
    public function getAllWithStock($storeId = null, $status = 'active') {
        $sql = "SELECT p.*, c.category_name, 
                COALESCE(s.quantity_on_hand, 0) as quantity_on_hand,
                COALESCE(s.reorder_level, p.reorder_level) as reorder_level
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN stock s ON p.product_id = s.product_id";
        
        if ($storeId) {
            $sql .= " AND s.store_id = $storeId";
        }
        
        $sql .= " WHERE p.status = '$status'
                ORDER BY p.product_name ASC";
        
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Search products
     */
    public function search($keyword, $categoryId = null) {
        $keyword = '%' . $keyword . '%';
        
        $sql = "SELECT p.*, c.category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE (p.product_name LIKE ? OR p.product_code LIKE ?)";
        
        $params = [$keyword, $keyword];
        $types = 'ss';
        
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        
        $sql .= " AND p.status = 'active'
                ORDER BY p.product_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems($storeId = null) {
        $sql = "SELECT p.*, c.category_name, 
                s.quantity_on_hand, s.reorder_level
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                JOIN stock s ON p.product_id = s.product_id
                WHERE s.quantity_on_hand <= s.reorder_level
                AND p.status = 'active'";
        
        if ($storeId) {
            $sql .= " AND s.store_id = $storeId";
        }
        
        $sql .= " ORDER BY s.quantity_on_hand ASC";
        
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems($storeId = null) {
        $sql = "SELECT p.*, c.category_name, s.store_id
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                JOIN stock s ON p.product_id = s.product_id
                WHERE s.quantity_on_hand = 0
                AND p.status = 'active'";
        
        if ($storeId) {
            $sql .= " AND s.store_id = $storeId";
        }
        
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}
