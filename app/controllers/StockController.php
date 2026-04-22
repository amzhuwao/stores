<?php
/**
 * Stock Controller
 */

class StockController extends Controller {
    public function getStores() {
        $user = getCurrentUser();
        if ($this->shouldRestrictToDepartmentStore($user)) {
            $allowedStoreIds = $this->getAllowedStoreIdsForUser($user);
            if (empty($allowedStoreIds)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($allowedStoreIds), '?'));
            $sql = "SELECT store_id, store_name FROM stores WHERE status = 'active' AND store_id IN ($placeholders) ORDER BY store_name ASC";
            $stmt = $this->db->prepare($sql);
            $types = str_repeat('i', count($allowedStoreIds));
            $stmt->bind_param($types, ...$allowedStoreIds);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $sql = "SELECT store_id, store_name FROM stores WHERE status = 'active' ORDER BY store_name ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategories() {
        $sql = "SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockLevels($filters = []) {
        $user = getCurrentUser();
        $allowedStoreIds = [];
        if ($this->shouldRestrictToDepartmentStore($user)) {
            $allowedStoreIds = $this->getAllowedStoreIdsForUser($user);
            if (empty($allowedStoreIds)) {
                return [];
            }
        }

        $sql = "SELECT
                    s.stock_id,
                    s.product_id,
                    s.store_id,
                    p.product_name,
                    p.product_code,
                    p.unit_of_measure,
                    c.category_name,
                    st.store_name,
                    s.quantity_on_hand,
                    s.reorder_level,
                    COALESCE((
                        SELECT gi.unit_price
                        FROM grn_items gi
                        WHERE gi.product_id = s.product_id
                        ORDER BY gi.grn_item_id DESC
                        LIMIT 1
                    ), 0) AS unit_price
                FROM stock s
                JOIN products p ON p.product_id = s.product_id
                LEFT JOIN categories c ON c.category_id = p.category_id
                JOIN stores st ON st.store_id = s.store_id
                WHERE p.status = 'active'";

        $types = '';
        $params = [];

        if (!empty($allowedStoreIds)) {
            $placeholders = implode(',', array_fill(0, count($allowedStoreIds), '?'));
            $sql .= " AND s.store_id IN ($placeholders)";
            $types .= str_repeat('i', count($allowedStoreIds));
            foreach ($allowedStoreIds as $allowedStoreId) {
                $params[] = (int)$allowedStoreId;
            }
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ? OR st.store_name LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['store_id']) && ctype_digit((string)$filters['store_id'])) {
            if (!empty($allowedStoreIds) && !in_array((int)$filters['store_id'], $allowedStoreIds, true)) {
                return [];
            }
            $sql .= " AND s.store_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['store_id'];
        }

        if (!empty($filters['category_id']) && ctype_digit((string)$filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['category_id'];
        }

        $stockState = $filters['stock_state'] ?? 'all';
        if ($stockState === 'low') {
            $sql .= " AND s.quantity_on_hand > 0 AND s.quantity_on_hand <= s.reorder_level";
        } elseif ($stockState === 'out') {
            $sql .= " AND s.quantity_on_hand = 0";
        } elseif ($stockState === 'ok') {
            $sql .= " AND s.quantity_on_hand > s.reorder_level";
        }

        $sql .= " ORDER BY st.store_name ASC, p.product_name ASC";

        if ($types !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    private function shouldRestrictToDepartmentStore($user) {
        if (!$user) {
            return false;
        }

        $roleName = (string)($user['role_name'] ?? '');
        $departmentRoles = ['Kitchen', 'Bar', 'Housekeeping', 'Maintenance'];
        return in_array($roleName, $departmentRoles, true);
    }

    private function getAllowedStoreIdsForUser($user) {
        $userId = (int)($user['user_id'] ?? 0);
        $roleName = trim((string)($user['role_name'] ?? ''));
        if ($userId <= 0 || $roleName === '') {
            return [];
        }

        $normalizedRole = strtolower($roleName);
        $rolePrefix = strtolower(substr(preg_replace('/[^a-z0-9]/i', '', $roleName), 0, 3));
        $storeNameLike = '%' . $normalizedRole . '%';
        $storeCodeLike = ($rolePrefix !== '') ? ($rolePrefix . '%') : '%';

        $sql = "SELECT DISTINCT s.store_id
                FROM stores s
                LEFT JOIN departments d
                    ON (LOWER(d.dept_name) = ? OR d.head_user_id = ?)
                LEFT JOIN requisitions r
                    ON r.store_id = s.store_id
                    AND (r.requested_by = ? OR (d.dept_id IS NOT NULL AND r.department_id = d.dept_id))
                WHERE s.status = 'active'
                  AND (
                    s.responsible_user_id = ?
                    OR LOWER(s.store_name) LIKE ?
                    OR LOWER(s.store_code) LIKE ?
                    OR r.requisition_id IS NOT NULL
                    OR (d.dept_id IS NOT NULL AND LOWER(s.store_name) LIKE CONCAT('%', LOWER(d.dept_name), '%'))
                  )";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('siiiss', $normalizedRole, $userId, $userId, $userId, $storeNameLike, $storeCodeLike);
        $stmt->execute();

        $storeIds = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $storeIds[] = (int)$row['store_id'];
        }

        return array_values(array_unique($storeIds));
    }

    public function getStats($rows) {
        $stats = [
            'lines' => 0,
            'low' => 0,
            'out' => 0,
            'value' => 0.0
        ];

        foreach ($rows as $row) {
            $stats['lines']++;
            $qty = (int)$row['quantity_on_hand'];
            $reorder = (int)$row['reorder_level'];
            $price = (float)$row['unit_price'];

            if ($qty === 0) {
                $stats['out']++;
            } elseif ($qty <= $reorder) {
                $stats['low']++;
            }

            $stats['value'] += ($qty * $price);
        }

        return $stats;
    }
}
