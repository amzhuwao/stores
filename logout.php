<?php
require_once 'app/bootstrap.php';

$auth = new Auth();
$auth->logout();

header('Location: ' . SITE_URL . 'login.php');
exit;
?>
