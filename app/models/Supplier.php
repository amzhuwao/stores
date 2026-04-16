<?php
/**
 * Supplier Model
 */

class Supplier extends Model {
    protected $table = 'suppliers';

    /**
     * Get all active suppliers
     */
    public function getActive() {
        return $this->getAll($this->table, "status = 'active'");
    }

    /**
     * Get supplier performance
     */
    public function getPerformance($supplierId, $monthsBack = 3) {
        $dateFrom = date('Y-m-d', strtotime("-$monthsBack months"));

        $sql = "SELECT
                COUNT(DISTINCT g.grn_id) as total_orders,
                COALESCE(SUM(gi.quantity_received), 0) as total_quantity_received,
                COALESCE(SUM(gi.quantity_expected), 0) as total_quantity_ordered,
                COALESCE(SUM(gi.quantity_expected - gi.quantity_received), 0) as shortfall,
                ROUND(COALESCE(SUM(gi.quantity_received) / NULLIF(SUM(gi.quantity_expected), 0) * 100, 0), 2) as fulfillment_rate,
                COALESCE(SUM(gi.quantity_received * gi.unit_price), 0) as total_spent
                FROM grn g
                JOIN grn_items gi ON g.grn_id = gi.grn_id
                WHERE g.supplier_id = ?
                AND g.receipt_date >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $supplierId, $dateFrom);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Search suppliers
     */
    public function search($keyword) {
        $keyword = '%' . $keyword . '%';

        $sql = "SELECT * FROM suppliers
                WHERE (supplier_name LIKE ? OR contact_person LIKE ? OR email LIKE ?)
                AND status = 'active'
                ORDER BY supplier_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $keyword, $keyword, $keyword);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
