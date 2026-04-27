<?php
require_once 'app/bootstrap.php';
$db = Database::getInstance()->getConnection();
$sql = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND (COLUMN_NAME LIKE '%consum%' OR COLUMN_NAME LIKE '%return%') ORDER BY TABLE_NAME, ORDINAL_POSITION";
$result = $db->query($sql);
if ($result === false) { fwrite(STDERR, "Search failed: " . $db->error . PHP_EOL); exit(1); }
while ($row = $result->fetch_assoc()) { echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL; }