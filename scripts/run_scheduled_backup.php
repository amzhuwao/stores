<?php
require_once __DIR__ . '/../app/bootstrap.php';

@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ignore_user_abort(true);

$manager = new BackupManager();
$settings = $manager->getSettings();

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    $providedToken = (string)($_GET['token'] ?? '');
    $expectedToken = (string)($settings['schedule_token'] ?? '');

    if ($providedToken === '' || $expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        echo 'Invalid token';
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
}

$result = $manager->runScheduledBackupIfDue();

if (!$result['ran']) {
    $reason = $result['reason'] ?? 'unknown';
    if ($reason === 'not_due' && !empty($result['next_run_at'])) {
        echo 'No backup run. Next scheduled run at ' . $result['next_run_at'] . " UTC\n";
    } else {
        echo 'No backup run. Reason: ' . $reason . "\n";
    }
    exit;
}

$backupResult = $result['result'] ?? [];
if (!empty($backupResult['success'])) {
    echo 'Scheduled backup completed: ' . ($backupResult['file_name'] ?? 'unknown file') . "\n";
    exit;
}

echo 'Scheduled backup failed: ' . ($backupResult['message'] ?? 'unknown error') . "\n";
exit;
