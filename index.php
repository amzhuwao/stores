<?php
require_once __DIR__ . '/app/bootstrap.php';

// Development-only: display PHP errors on this page.

if (isAuthenticated()) {
    header('Location: ' . SITE_URL . 'dashboard.php');
    exit;
}

header('Location: ' . SITE_URL . 'login.php');
exit;
