<?php
/**
 * Department Model
 */

class Department extends Model {
    protected $table = 'departments';

    /**
     * Get department with head
     */
    public function getWithHead($departmentId) {
        $sql = "SELECT d.*, u.full_name as head_name
                FROM departments d
                LEFT JOIN users u ON d.head_user_id = u.user_id
                WHERE d.dept_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all active departments
     */
    public function getActive() {
        return $this->getAll($this->table, "status = 'active'");
    }

    /**
     * Get department consumption summary
     */
    public function getConsumptionSummary($departmentId, $dateFrom = null, $dateTo = null) {
        $dateCondition = '';
        if ($dateFrom && $dateTo) {
            $dateCondition = " AND si.issue_date BETWEEN '$dateFrom' AND '$dateTo'";
        }

        $sql = "SELECT
                COALESCE(SUM(sii.quantity_issued), 0) as total_quantity,
                COALESCE(SUM(sii.quantity_issued * COALESCE(sii.unit_price, 0)), 0) as total_cost,
                COUNT(DISTINCT si.issue_id) as number_of_issues
                FROM stock_issue_items sii
                JOIN stock_issues si ON sii.issue_id = si.issue_id
                WHERE si.department_id = ?
                $dateCondition";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}
