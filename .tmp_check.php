require 'app/bootstrap.php';
$db = Database::getInstance()->getConnection();
$sql = "SELECT si.issue_id, si.issue_number, si.issue_date, si.store_id, si.department_id, COUNT(sii.issue_item_id) AS item_count FROM stock_issues si LEFT JOIN stock_issue_items sii ON sii.issue_id = si.issue_id GROUP BY si.issue_id, si.issue_number, si.issue_date, si.store_id, si.department_id ORDER BY si.issue_date DESC, si.issue_id DESC LIMIT 20";
echo $sql;
