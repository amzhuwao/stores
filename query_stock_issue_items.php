<?php
require_once 'app/bootstrap.php';
$db = Database::getInstance()->getConnection();
$sql = "SELECT issue_item_id, issue_id, quantity_issued, quantity_consumed, quantity_returned FROM stock_issue_items";
$result = $db->query($sql);
if ($result === false) { fwrite(STDERR, "Query failed: " . $db->error . PHP_EOL); exit(1); }
while ($row = $result->fetch_assoc()) { echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL; }
$countSql = "SELECT SUM(CASE WHEN quantity_consumed IS NULL THEN 1 ELSE 0 END) AS consumed_nulls, SUM(CASE WHEN quantity_returned IS NULL THEN 1 ELSE 0 END) AS returned_nulls FROM stock_issue_items";
$countResult = $db->query($countSql);
if ($countResult === false) { fwrite(STDERR, "Count query failed: " . $db->error . PHP_EOL); exit(1); }
$countRow = $countResult->fetch_assoc();
echo "quantity_consumed IS NULL: " . $countRow["consumed_nulls"] . PHP_EOL;
echo "quantity_returned IS NULL: " . $countRow["returned_nulls"] . PHP_EOL;