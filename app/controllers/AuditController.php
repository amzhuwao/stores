<?php
/**
 * Audit Controller
 */

class AuditController extends Controller {
    public function getUsers() {
        $sql = "SELECT user_id, full_name, username FROM users ORDER BY full_name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getLogs($filters = [], $limit = 200) {
        $sql = "SELECT a.*, u.full_name, u.username
                FROM audit_log a
                JOIN users u ON a.user_id = u.user_id
                WHERE 1 = 1";

        $types = '';
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (a.action LIKE ? OR a.entity_type LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            $types .= 'ssss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['user_id']) && ctype_digit((string)$filters['user_id'])) {
            $sql .= " AND a.user_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['user_id'];
        }

        if (!empty($filters['entity_type'])) {
            $sql .= " AND a.entity_type = ?";
            $types .= 's';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.action_date) >= ?";
            $types .= 's';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.action_date) <= ?";
            $types .= 's';
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY a.action_date DESC LIMIT ?";
        $types .= 'i';
        $params[] = (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getEntityTypes() {
        $sql = "SELECT DISTINCT entity_type FROM audit_log WHERE entity_type IS NOT NULL AND entity_type <> '' ORDER BY entity_type ASC";
        $result = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
        return array_map(function ($row) {
            return $row['entity_type'];
        }, $result);
    }

    public function getSummary($filters = []) {
        $summary = [
            'total' => 0,
            'today' => 0,
            'users' => 0
        ];

        $baseSql = "FROM audit_log a WHERE 1 = 1";
        $types = '';
        $params = [];

        if (!empty($filters['user_id']) && ctype_digit((string)$filters['user_id'])) {
            $baseSql .= " AND a.user_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['user_id'];
        }

        if (!empty($filters['entity_type'])) {
            $baseSql .= " AND a.entity_type = ?";
            $types .= 's';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $baseSql .= " AND DATE(a.action_date) >= ?";
            $types .= 's';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $baseSql .= " AND DATE(a.action_date) <= ?";
            $types .= 's';
            $params[] = $filters['date_to'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) AS c " . $baseSql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $summary['total'] = (int)$stmt->get_result()->fetch_assoc()['c'];

        $todaySql = "SELECT COUNT(*) AS c FROM audit_log WHERE DATE(action_date) = CURDATE()";
        $summary['today'] = (int)$this->db->query($todaySql)->fetch_assoc()['c'];

        $usersSql = "SELECT COUNT(DISTINCT user_id) AS c FROM audit_log";
        $summary['users'] = (int)$this->db->query($usersSql)->fetch_assoc()['c'];

        return $summary;
    }

    public function exportCsv($rows) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit-log-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Action Date', 'User', 'Username', 'Action', 'Entity Type', 'Entity ID', 'IP Address', 'Old Value', 'New Value']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['action_date'],
                $row['full_name'],
                $row['username'],
                $row['action'],
                $row['entity_type'],
                $row['entity_id'],
                $row['ip_address'],
                $row['old_value'],
                $row['new_value']
            ]);
        }

        fclose($out);
        exit;
    }
}
