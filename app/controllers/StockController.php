<?php
/**
 * Stock Controller
 */

class StockController extends Controller {
    public function getStores() {
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

        if (!empty($filters['q'])) {
            $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ? OR st.store_name LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['store_id']) && ctype_digit((string)$filters['store_id'])) {
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
