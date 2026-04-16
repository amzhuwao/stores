<?php
/**
 * GRN (Goods Received Note) Model
 */

class GRN extends Model {
    protected $table = 'grn';

    /**
     * Get GRN with details
     */
    public function getWithDetails($grnId) {
        $sql = "SELECT g.*, s.store_name, sup.supplier_name, 
                u.full_name as received_by_name
                FROM grn g
                JOIN stores s ON g.store_id = s.store_id
                JOIN suppliers sup ON g.supplier_id = sup.supplier_id
                JOIN users u ON g.received_by = u.user_id
                WHERE g.grn_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $grnId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get GRN items
     */
    public function getItems($grnId) {
        $sql = "SELECT gi.*, p.product_name, p.product_code, p.unit_of_measure
                FROM grn_items gi
                JOIN products p ON gi.product_id = p.product_id
                WHERE gi.grn_id = ?
                ORDER BY gi.grn_item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $grnId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get by supplier
     */
    public function getBySupplier($supplierId) {
        $sql = "SELECT g.*, s.store_name, sup.supplier_name
                FROM grn g
                JOIN stores s ON g.store_id = s.store_id
                JOIN suppliers sup ON g.supplier_id = sup.supplier_id
                WHERE g.supplier_id = ?
                ORDER BY g.receipt_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $supplierId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get by store
     */
    public function getByStore($storeId) {
        $sql = "SELECT g.*, s.store_name, sup.supplier_name
                FROM grn g
                JOIN stores s ON g.store_id = s.store_id
                JOIN suppliers sup ON g.supplier_id = sup.supplier_id
                WHERE g.store_id = ?
                ORDER BY g.receipt_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Generate GRN number
     */
    public function generateGRNNumber() {
        $date = date('Ymd');
        // Get last GRN number for today
        $sql = "SELECT COUNT(*) as count FROM grn WHERE DATE(receipt_date) = CURDATE()";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $sequence = str_pad($row['count'] + 1, 4, '0', STR_PAD_LEFT);
        
        return 'GRN-' . $date . '-' . $sequence;
    }

    /**
     * Add item to GRN
     */
    public function addItem($grnId, $productId, $quantityExpected, $quantityReceived, $unitPrice, $batchNumber = '', $expiryDate = null) {
        $sql = "INSERT INTO grn_items (grn_id, product_id, quantity_expected, quantity_received, unit_price, batch_number, expiry_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiiidss',
            $grnId, $productId, $quantityExpected, $quantityReceived, $unitPrice, $batchNumber, $expiryDate);
        
        return $stmt->execute();
    }

    /**
     * Verify GRN and update stock
     */
    public function verify($grnId, $verifiedBy) {
        // Start transaction
        Database::getInstance()->getConnection()->begin_transaction();

        try {
            // Update GRN status
            $status = 'verified';
            $sql = "UPDATE grn SET status = ?, updated_at = NOW() WHERE grn_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $status, $grnId);
            $stmt->execute();

            // Get GRN details
            $grn = $this->findById($grnId);
            $storeId = $grn['store_id'];

            // Get GRN items and update stock
            $items = $this->getItems($grnId);
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantityReceived = $item['quantity_received'];

                // Update or create stock record
                $stock = new Stock();
                $existingStock = $stock->getByProductAndStore($productId, $storeId);

                if ($existingStock) {
                    $stock->updateQuantity($productId, $storeId, $quantityReceived);
                } else {
                    $stock->createStock($productId, $storeId, $quantityReceived);
                }

                // Record in batch tracking if expiry date provided
                if ($item['expiry_date']) {
                    $sql = "INSERT INTO batch_tracking (product_id, store_id, batch_number, expiry_date, quantity, unit_price, grn_item_id, received_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('iissidi', 
                        $productId, $storeId, $item['batch_number'], $item['expiry_date'], 
                        $quantityReceived, $item['unit_price'], $item['grn_item_id']);
                    $stmt->execute();
                }

                // Record transaction
                $transactionType = 'receipt';
                $totalValue = (float)$quantityReceived * (float)$item['unit_price'];
                $sql = "INSERT INTO stock_transactions (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, unit_price, total_value, performed_by) 
                        VALUES (?, ?, ?, 'GRN', ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iisiiddi', 
                    $productId, $storeId, $transactionType, $grnId, $quantityReceived, $item['unit_price'], $totalValue, $verifiedBy);
                $stmt->execute();
            }

            Database::getInstance()->getConnection()->commit();
            return true;

        } catch (Exception $e) {
            Database::getInstance()->getConnection()->rollback();
            return false;
        }
    }
}
