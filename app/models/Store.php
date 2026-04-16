<?php
/**
 * Store Model
 */

class Store extends Model {
    protected $table = 'stores';

    /**
     * Get store with responsible person
     */
    public function getWithResponsible($storeId) {
        $sql = "SELECT s.*, u.full_name as responsible_person
                FROM stores s
                LEFT JOIN users u ON s.responsible_user_id = u.user_id
                WHERE s.store_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all active stores
     */
    public function getActive() {
        return $this->getAll($this->table, "status = 'active'");
    }

    /**
     * Get store stock summary
     */
    public function getStockSummary($storeId) {
        $sql = "SELECT 
                COUNT(DISTINCT s.product_id) as total_products,
                SUM(CASE WHEN s.quantity_on_hand > 0 THEN 1 ELSE 0 END) as items_in_stock,
                SUM(CASE WHEN s.quantity_on_hand = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN s.quantity_on_hand <= s.reorder_level AND s.quantity_on_hand > 0 THEN 1 ELSE 0 END) as low_stock,
                COALESCE(SUM(s.quantity_on_hand), 0) as total_quantity,
                COALESCE(SUM(s.quantity_on_hand * COALESCE((SELECT unit_price FROM grn_items WHERE product_id = s.product_id ORDER BY grn_item_id DESC LIMIT 1), 0)), 0) as total_value
                FROM stock s
                WHERE s.store_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}
