<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requirePermission('settings.backup');

$currentUser = getCurrentUser();
$currentUserId = (int)($currentUser['user_id'] ?? 0);
$canRestore = can('settings.restore');
$backupManager = new BackupManager();

if (isset($_GET['download'])) {
    $backupId = (int)$_GET['download'];
    $record = $backupManager->getHistoryById($backupId);

    if (!$record || $record['status'] !== 'success') {
        http_response_code(404);
        die('Backup file not found.');
    }

    $path = (string)$record['file_path'];
    if (!is_file($path)) {
        http_response_code(404);
        die('Backup file does not exist on disk.');
    }

    $fileName = basename((string)$record['file_name']);
    header('Content-Type: application/sql');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'manual_backup') {
        $result = $backupManager->createBackup('manual', $currentUserId);
        if ($result['success']) {
            $_SESSION['flash_message'] = 'Backup completed: ' . $result['file_name'];
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Backup failed: ' . ($result['message'] ?? 'Unknown error');
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    if ($action === 'save_schedule') {
        try {
            $backupManager->saveSettings($_POST, $currentUserId);
            $_SESSION['flash_message'] = 'Automated backup schedule saved.';
            $_SESSION['flash_type'] = 'success';
        } catch (Throwable $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    if ($action === 'rotate_token') {
        $backupManager->rotateScheduleToken($currentUserId);
        $_SESSION['flash_message'] = 'Scheduler token rotated successfully.';
        $_SESSION['flash_type'] = 'success';

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    if ($action === 'restore_stored_backup') {
        if (!$canRestore) {
            $_SESSION['flash_message'] = 'You do not have permission to restore backups.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . SITE_URL . 'pages/settings/backup.php');
            exit;
        }

        $backupId = (int)($_POST['backup_id'] ?? 0);
        $confirmationText = (string)($_POST['restore_confirmation'] ?? '');
        $dryRun = !empty($_POST['dry_run']);

        $result = $backupManager->restoreFromStoredBackup($backupId, $currentUserId, $confirmationText, ['dry_run' => $dryRun]);
        $_SESSION['flash_message'] = $result['message'] ?? ($result['success'] ? 'Restore completed.' : 'Restore failed.');
        $_SESSION['flash_type'] = !empty($result['success']) ? ($dryRun ? 'info' : 'success') : 'danger';

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    if ($action === 'preview_stored_backup') {
        if (!$canRestore) {
            $_SESSION['flash_message'] = 'You do not have permission to preview restores.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . SITE_URL . 'pages/settings/backup.php');
            exit;
        }

        $backupId = (int)($_POST['backup_id'] ?? 0);
        $_SESSION['restore_preview'] = $backupManager->previewStoredBackup($backupId);
        $_SESSION['flash_message'] = 'Restore preview generated.';
        $_SESSION['flash_type'] = 'info';

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    if ($action === 'restore_uploaded_backup') {
        if (!$canRestore) {
            $_SESSION['flash_message'] = 'You do not have permission to restore backups.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . SITE_URL . 'pages/settings/backup.php');
            exit;
        }

        $confirmationText = (string)($_POST['restore_confirmation_upload'] ?? '');
        $dryRun = !empty($_POST['dry_run_upload']);
        $result = $backupManager->restoreFromUploadedFile(
            $_FILES['restore_file'] ?? [],
            $currentUserId,
            $confirmationText,
            ['dry_run' => $dryRun]
        );
        $_SESSION['flash_message'] = $result['message'] ?? ($result['success'] ? 'Restore completed.' : 'Restore failed.');
        $_SESSION['flash_type'] = !empty($result['success']) ? ($dryRun ? 'info' : 'success') : 'danger';

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    if ($action === 'preview_uploaded_backup') {
        if (!$canRestore) {
            $_SESSION['flash_message'] = 'You do not have permission to preview restores.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . SITE_URL . 'pages/settings/backup.php');
            exit;
        }

        $_SESSION['restore_preview'] = $backupManager->previewUploadedFile($_FILES['restore_file'] ?? []);
        $_SESSION['flash_message'] = 'Restore preview generated.';
        $_SESSION['flash_type'] = 'info';

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }

    if ($action === 'rollback_restore') {
        if (!$canRestore) {
            $_SESSION['flash_message'] = 'You do not have permission to restore backups.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . SITE_URL . 'pages/settings/backup.php');
            exit;
        }

        $restoreId = (int)($_POST['restore_id'] ?? 0);
        $result = $backupManager->rollbackToSafetyBackup($restoreId, $currentUserId);
        $_SESSION['flash_message'] = $result['message'] ?? ($result['success'] ? 'Rollback completed.' : 'Rollback failed.');
        $_SESSION['flash_type'] = !empty($result['success']) ? 'success' : 'danger';

        header('Location: ' . SITE_URL . 'pages/settings/backup.php');
        exit;
    }
}

$settings = $backupManager->getSettings();
$history = $backupManager->getRecentHistory(30);
$restoreHistory = $backupManager->getRecentRestoreHistory(20);
$restoreConfirmationPhrase = $backupManager->getRestoreConfirmationPhrase();
$restorePreview = $_SESSION['restore_preview'] ?? null;
unset($_SESSION['restore_preview']);

$timezone = new DateTimeZone('UTC');
$now = new DateTime('now', $timezone);

$nextRunDisplay = 'Not scheduled';
if (!empty($settings['next_run_at'])) {
    $nextRunDisplay = (new DateTime($settings['next_run_at'], $timezone))->format('Y-m-d H:i:s') . ' UTC';
}

$lastRunDisplay = 'Never';
if (!empty($settings['last_run_at'])) {
    $lastRunDisplay = (new DateTime($settings['last_run_at'], $timezone))->format('Y-m-d H:i:s') . ' UTC';
}

$frequencyLabels = [
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
];

$weekdayOptions = [
    0 => 'Sunday',
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
];

$pageTitle = 'Backup Settings';
$activePage = 'settings-backup';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1>Backup Settings</h1>
        <p>Create manual backups and configure automatic backup schedules.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo SITE_URL; ?>pages/settings/index.php" class="btn btn-outline-primary">General Settings</a>
        <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Schedule Status</div>
            <div class="stat-value"><?php echo ((int)$settings['is_enabled'] === 1) ? 'Enabled' : 'Disabled'; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-label">Last Backup</div>
            <div class="stat-value" style="font-size: 1.1rem;"><?php echo htmlspecialchars($lastRunDisplay); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-label">Next Scheduled Run</div>
            <div class="stat-value" style="font-size: 1.1rem;"><?php echo htmlspecialchars($nextRunDisplay); ?></div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-5">
        <?php if (!empty($restorePreview)): ?>
            <div class="card mb-4 border-info">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-search"></i> Restore Preview</h5>
                    <span class="badge bg-info text-dark">Validation only</span>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Source:</strong> <?php echo htmlspecialchars((string)($restorePreview['source_label'] ?? 'Unknown')); ?></p>
                    <p class="mb-2"><strong>Statements detected:</strong> <?php echo (int)($restorePreview['statement_count'] ?? 0); ?></p>
                    <p class="mb-2"><strong>Status:</strong>
                        <?php if (!empty($restorePreview['success'])): ?>
                            <span class="badge bg-success">Safe to proceed</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Needs attention</span>
                        <?php endif; ?>
                    </p>
                    <p class="mb-3"><strong>Validation:</strong> <?php echo htmlspecialchars((string)($restorePreview['message'] ?? 'No details available.')); ?></p>

                    <?php if (!empty($restorePreview['blocked_patterns']) && is_array($restorePreview['blocked_patterns'])): ?>
                        <div class="alert alert-warning mb-0">
                            <strong>Blocked patterns:</strong>
                            <?php echo htmlspecialchars(implode(', ', $restorePreview['blocked_patterns'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><h5><i class="fas fa-cloud-download-alt"></i> Manual Backup</h5></div>
            <div class="card-body">
                <p class="mb-3">Create an immediate SQL snapshot of the current database.</p>
                <form method="POST">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="action" value="manual_backup">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-database"></i> Run Backup Now
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4 border-danger">
            <div class="card-header"><h5><i class="fas fa-undo"></i> Restore From Stored Backup</h5></div>
            <div class="card-body">
                <p class="mb-2">Restore the entire database from a backup listed in history.</p>
                <?php if (!$canRestore): ?>
                    <div class="alert alert-warning mb-0">Your role currently does not include <strong>settings.restore</strong> permission.</div>
                <?php else: ?>
                    <form method="POST" class="row g-3" data-restore-form="stored">
                        <?php echo getCSRFTokenField(); ?>
                        <input type="hidden" name="action" value="restore_stored_backup">

                        <div class="col-12">
                            <label class="form-label">Backup Record</label>
                            <select name="backup_id" class="form-control" required>
                                <option value="">Select backup</option>
                                <?php foreach ($history as $row): ?>
                                    <?php if ($row['status'] !== 'success') { continue; } ?>
                                    <option value="<?php echo (int)$row['backup_id']; ?>">
                                        #<?php echo (int)$row['backup_id']; ?> - <?php echo htmlspecialchars((string)$row['file_name']); ?> (<?php echo htmlspecialchars((string)$row['started_at']); ?> UTC)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Type <?php echo htmlspecialchars($restoreConfirmationPhrase); ?> to confirm</label>
                            <input type="text" name="restore_confirmation" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="dry_run" name="dry_run" value="1">
                                <label class="form-check-label" for="dry_run">Dry run validation only (do not apply changes)</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-outline-primary" name="action" value="preview_stored_backup" formnovalidate>Preview Restore</button>
                                <button type="submit" class="btn btn-danger" data-confirm-restore="1">Restore Selected Backup</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5><i class="fas fa-file-upload"></i> Restore From Uploaded SQL</h5></div>
            <div class="card-body">
                <p class="mb-2">Upload a .sql file to restore. A safety backup is created automatically first.</p>
                <?php if (!$canRestore): ?>
                    <div class="alert alert-warning mb-0">Your role currently does not include <strong>settings.restore</strong> permission.</div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" class="row g-3" data-restore-form="upload">
                        <?php echo getCSRFTokenField(); ?>
                        <input type="hidden" name="action" value="restore_uploaded_backup">

                        <div class="col-12">
                            <label class="form-label">SQL File</label>
                            <input type="file" name="restore_file" class="form-control" accept=".sql" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Type <?php echo htmlspecialchars($restoreConfirmationPhrase); ?> to confirm</label>
                            <input type="text" name="restore_confirmation_upload" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="dry_run_upload" name="dry_run_upload" value="1">
                                <label class="form-check-label" for="dry_run_upload">Dry run validation only (do not apply changes)</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-outline-primary" name="action" value="preview_uploaded_backup" formnovalidate>Preview Upload</button>
                                <button type="submit" class="btn btn-danger" data-confirm-restore="1">Upload And Restore</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5><i class="fas fa-clock"></i> Automated Schedule</h5></div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="action" value="save_schedule">

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo ((int)$settings['is_enabled'] === 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_enabled">Enable automatic backups</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Frequency</label>
                        <select name="frequency" id="frequency" class="form-control">
                            <?php foreach ($frequencyLabels as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($settings['frequency'] === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Run Time (UTC)</label>
                        <input type="time" name="run_time" class="form-control" value="<?php echo htmlspecialchars(substr((string)$settings['run_time'], 0, 5)); ?>" required>
                    </div>

                    <div class="col-md-6" id="weeklyField">
                        <label class="form-label">Day of Week</label>
                        <select name="day_of_week" class="form-control">
                            <?php foreach ($weekdayOptions as $value => $label): ?>
                                <option value="<?php echo (int)$value; ?>" <?php echo ((int)$settings['day_of_week'] === (int)$value) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6" id="monthlyField">
                        <label class="form-label">Day of Month</label>
                        <input type="number" min="1" max="31" name="day_of_month" class="form-control" value="<?php echo (int)$settings['day_of_month']; ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Retention (days)</label>
                        <input type="number" min="1" max="3650" name="retention_days" class="form-control" value="<?php echo (int)$settings['retention_days']; ?>" required>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><h5><i class="fas fa-key"></i> Scheduler Trigger</h5></div>
            <div class="card-body">
                <p class="mb-2">Run this script every 5-15 minutes via Task Scheduler or cron:</p>
                <pre class="bg-light p-2 border rounded mb-3" style="white-space: pre-wrap;">php <?php echo htmlspecialchars(realpath(__DIR__ . '/../../scripts/run_scheduled_backup.php')); ?></pre>

                <p class="mb-1">Optional HTTP trigger URL (keep token secret):</p>
                <pre class="bg-light p-2 border rounded mb-3" style="white-space: pre-wrap;"><?php echo htmlspecialchars(SITE_URL . 'scripts/run_scheduled_backup.php?token=' . $settings['schedule_token']); ?></pre>

                <form method="POST">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="action" value="rotate_token">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Rotate Token</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5><i class="fas fa-history"></i> Backup History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Started</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Size</th>
                                <th>By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr><td colspan="7" class="text-center py-3">No backups have been recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $row): ?>
                                    <tr>
                                        <td><?php echo (int)$row['backup_id']; ?></td>
                                        <td><?php echo htmlspecialchars((string)$row['started_at']); ?> UTC</td>
                                        <td><?php echo htmlspecialchars(ucfirst((string)$row['trigger_type'])); ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" title="<?php echo htmlspecialchars((string)$row['error_message']); ?>">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format(((int)$row['file_size_bytes']) / 1024, 2); ?> KB</td>
                                        <td><?php echo htmlspecialchars($row['initiated_by_name'] ?? 'System'); ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'success'): ?>
                                                <a href="<?php echo SITE_URL; ?>pages/settings/backup.php?download=<?php echo (int)$row['backup_id']; ?>" class="btn btn-sm btn-outline-primary">Download</a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5><i class="fas fa-history"></i> Restore History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Started</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Safety Backup</th>
                                <th>By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($restoreHistory)): ?>
                                <tr><td colspan="7" class="text-center py-3">No restores have been recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($restoreHistory as $row): ?>
                                    <tr>
                                        <td><?php echo (int)$row['restore_id']; ?></td>
                                        <td><?php echo htmlspecialchars((string)$row['started_at']); ?> UTC</td>
                                        <td><?php echo htmlspecialchars((string)$row['source_type']); ?>: <?php echo htmlspecialchars((string)$row['source_label']); ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" title="<?php echo htmlspecialchars((string)$row['error_message']); ?>">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['safety_backup_id'])): ?>
                                                #<?php echo (int)$row['safety_backup_id']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['initiated_by_name'] ?? 'System'); ?></td>
                                        <td>
                                            <?php if ($canRestore && !empty($row['safety_backup_id'])): ?>
                                                <form method="POST" onsubmit="return confirm('Rollback to the safety backup for this restore?');" style="display:inline-block;">
                                                    <?php echo getCSRFTokenField(); ?>
                                                    <input type="hidden" name="action" value="rollback_restore">
                                                    <input type="hidden" name="restore_id" value="<?php echo (int)$row['restore_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Rollback</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const frequency = document.getElementById('frequency');
    const weeklyField = document.getElementById('weeklyField');
    const monthlyField = document.getElementById('monthlyField');

    function refreshScheduleFields() {
        const value = frequency ? frequency.value : 'daily';
        if (weeklyField) {
            weeklyField.style.display = (value === 'weekly') ? '' : 'none';
        }
        if (monthlyField) {
            monthlyField.style.display = (value === 'monthly') ? '' : 'none';
        }
    }

    if (frequency) {
        frequency.addEventListener('change', refreshScheduleFields);
    }

    document.querySelectorAll('form[data-restore-form]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const submitter = event.submitter;
            if (submitter && submitter.dataset.confirmRestore === '1') {
                if (!confirm('This will overwrite current database data. Continue?')) {
                    event.preventDefault();
                }
            }
        });
    });

    refreshScheduleFields();
})();
</script>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
