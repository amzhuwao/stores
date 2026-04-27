<?php
require_once 'app/bootstrap.php';
$db = Database::getInstance()->getConnection();
$result = $db->query("SHOW COLUMNS FROM stock_issue_items");
if ($result === false) { fwrite(STDERR, "SHOW COLUMNS failed: " . $db->error . PHP_EOL); exit(1); }
while ($row = $result->fetch_assoc()) { echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL; }