<?php
/**
 * Requisition Model
 */

class Requisition extends Model {
    protected $table = 'requisitions';

    /**
     * Get requisition with details
     */
    public function getWithDetails($requisitionId) {
        $sql = "SELECT r.*, d.dept_name, s.store_name, 
                u.full_name as requested_by_name,
                um.full_name as approved_by_name
                FROM requisitions r
                JOIN departments d ON r.department_id = d.dept_id
                JOIN stores s ON r.store_id = s.store_id
                JOIN users u ON r.requested_by = u.user_id
                LEFT JOIN users um ON r.approved_by = um.user_id
                WHERE r.requisition_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $requisitionId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get requisition items
     */
    public function getItems($requisitionId) {
        $sql = "SELECT ri.*, p.product_name, p.product_code, p.unit_of_measure
                FROM requisition_items ri
                JOIN products p ON ri.product_id = p.product_id
                WHERE ri.requisition_id = ?
                ORDER BY ri.req_item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $requisitionId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get pending requisitions
     */
    public function getPending($storeId = null) {
        $sql = "SELECT r.*, d.dept_name, s.store_name, u.full_name
                FROM requisitions r
                JOIN departments d ON r.department_id = d.dept_id
                JOIN stores s ON r.store_id = s.store_id
                JOIN users u ON r.requested_by = u.user_id
                WHERE r.status = 'pending'";
        
        if ($storeId) {
            $sql .= " AND r.store_id = $storeId";
        }
        
        $sql .= " ORDER BY r.requested_date DESC";
        
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get by department
     */
    public function getByDepartment($departmentId) {
        $sql = "SELECT r.*, d.dept_name, s.store_name, u.full_name
                FROM requisitions r
                JOIN departments d ON r.department_id = d.dept_id
                JOIN stores s ON r.store_id = s.store_id
                JOIN users u ON r.requested_by = u.user_id
                WHERE r.department_id = ?
                ORDER BY r.requested_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Generate requisition number
     */
    public function generateRequisitionNumber() {
        $date = date('Ymd');
        // Get last requisition number for today
        $sql = "SELECT COUNT(*) as count FROM requisitions WHERE DATE(requested_date) = CURDATE()";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $sequence = str_pad($row['count'] + 1, 4, '0', STR_PAD_LEFT);
        
        return 'REQ-' . $date . '-' . $sequence;
    }

    /**
     * Add item to requisition
     */
    public function addItem($requisitionId, $productId, $quantity, $remarks = '') {
        $sql = "INSERT INTO requisition_items (requisition_id, product_id, quantity_requested, remarks) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiis', $requisitionId, $productId, $quantity, $remarks);
        
        return $stmt->execute();
    }

    /**
     * Approve requisition
     */
    public function approve($requisitionId, $approvedBy) {
        $status = 'approved';
        $sql = "UPDATE requisitions 
                SET status = ?, approved_by = ?, approval_date = NOW()
                WHERE requisition_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $status, $approvedBy, $requisitionId);
        
        return $stmt->execute();
    }

    /**
     * Reject requisition
     */
    public function reject($requisitionId, $reason) {
        $status = 'rejected';
        $sql = "UPDATE requisitions 
                SET status = ?, rejection_reason = ?
                WHERE requisition_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $status, $reason, $requisitionId);
        
        return $stmt->execute();
    }
}
