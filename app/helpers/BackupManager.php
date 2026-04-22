<?php
/**
 * Backup manager for manual and scheduled database backups.
 */
class BackupManager {
    private $db;
    private $projectRoot;
    private $backupDir;
    private $restoreConfirmationPhrase = 'RESTORE';
    private $dumpBatchSize = 200;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->projectRoot = realpath(__DIR__ . '/../../');
        $this->backupDir = $this->projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';

        $this->ensureBackupSchema();
        $this->ensureBackupDirectory();
    }

    private function ensureBackupSchema() {
        $this->db->query("CREATE TABLE IF NOT EXISTS backup_settings (
            setting_id TINYINT PRIMARY KEY DEFAULT 1,
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            frequency ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
            run_time TIME NOT NULL DEFAULT '02:00:00',
            day_of_week TINYINT NOT NULL DEFAULT 1,
            day_of_month TINYINT NOT NULL DEFAULT 1,
            retention_days INT NOT NULL DEFAULT 30,
            schedule_token VARCHAR(64) NOT NULL,
            last_run_at DATETIME NULL,
            next_run_at DATETIME NULL,
            updated_by INT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_backup_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL,
            CHECK (setting_id = 1),
            CHECK (day_of_week BETWEEN 0 AND 6),
            CHECK (day_of_month BETWEEN 1 AND 31),
            CHECK (retention_days BETWEEN 1 AND 3650)
        )");

        $this->db->query("CREATE TABLE IF NOT EXISTS backup_history (
            backup_id INT PRIMARY KEY AUTO_INCREMENT,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size_bytes BIGINT NOT NULL DEFAULT 0,
            trigger_type ENUM('manual', 'scheduled') NOT NULL,
            status ENUM('success', 'failed') NOT NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            initiated_by INT NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_backup_history_user FOREIGN KEY (initiated_by) REFERENCES users(user_id) ON DELETE SET NULL,
            INDEX idx_backup_history_started_at (started_at),
            INDEX idx_backup_history_status (status),
            INDEX idx_backup_history_trigger (trigger_type)
        )");

        $this->db->query("CREATE TABLE IF NOT EXISTS restore_history (
            restore_id INT PRIMARY KEY AUTO_INCREMENT,
            source_type ENUM('stored_backup', 'upload') NOT NULL,
            source_label VARCHAR(255) NOT NULL,
            source_path VARCHAR(500) NOT NULL,
            status ENUM('success', 'failed') NOT NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            initiated_by INT NULL,
            safety_backup_id INT NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_restore_history_user FOREIGN KEY (initiated_by) REFERENCES users(user_id) ON DELETE SET NULL,
            CONSTRAINT fk_restore_history_backup FOREIGN KEY (safety_backup_id) REFERENCES backup_history(backup_id) ON DELETE SET NULL,
            INDEX idx_restore_history_started_at (started_at),
            INDEX idx_restore_history_status (status),
            INDEX idx_restore_history_source_type (source_type)
        )");

        $result = $this->db->query("SELECT setting_id FROM backup_settings WHERE setting_id = 1 LIMIT 1");
        if (!$result->fetch_assoc()) {
            $token = bin2hex(random_bytes(24));
            $stmt = $this->db->prepare("INSERT INTO backup_settings (setting_id, schedule_token) VALUES (1, ?)");
            $stmt->bind_param('s', $token);
            $stmt->execute();
        }
    }

    private function ensureBackupDirectory() {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0775, true);
        }
    }

    public function getSettings() {
        $defaults = [
            'setting_id' => 1,
            'is_enabled' => 0,
            'frequency' => 'daily',
            'run_time' => '02:00:00',
            'day_of_week' => 1,
            'day_of_month' => 1,
            'retention_days' => 30,
            'schedule_token' => '',
            'last_run_at' => null,
            'next_run_at' => null,
            'updated_by' => null,
            'updated_at' => null,
        ];

        $result = $this->db->query("SELECT * FROM backup_settings WHERE setting_id = 1 LIMIT 1");
        $row = $result->fetch_assoc() ?: [];

        return array_merge($defaults, $row);
    }

    public function getRecentHistory($limit = 20) {
        $limit = max(1, min(100, (int)$limit));
        $sql = "SELECT h.*, u.full_name AS initiated_by_name
                FROM backup_history h
                LEFT JOIN users u ON h.initiated_by = u.user_id
                ORDER BY h.backup_id DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        $rows = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getHistoryById($backupId) {
        $backupId = (int)$backupId;
        $sql = "SELECT h.*, u.full_name AS initiated_by_name
                FROM backup_history h
                LEFT JOIN users u ON h.initiated_by = u.user_id
                WHERE h.backup_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $backupId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getRecentRestoreHistory($limit = 20) {
        $limit = max(1, min(100, (int)$limit));
        $sql = "SELECT r.*, u.full_name AS initiated_by_name, b.file_name AS safety_backup_file
                FROM restore_history r
                LEFT JOIN users u ON r.initiated_by = u.user_id
                LEFT JOIN backup_history b ON r.safety_backup_id = b.backup_id
                ORDER BY r.restore_id DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        $rows = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getRestoreHistoryById($restoreId) {
        $restoreId = (int)$restoreId;
        $sql = "SELECT r.*, u.full_name AS initiated_by_name, b.file_name AS safety_backup_file
                FROM restore_history r
                LEFT JOIN users u ON r.initiated_by = u.user_id
                LEFT JOIN backup_history b ON r.safety_backup_id = b.backup_id
                WHERE r.restore_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $restoreId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function saveSettings($input, $updatedBy) {
        $enabled = !empty($input['is_enabled']) ? 1 : 0;
        $allowedFreq = ['daily', 'weekly', 'monthly'];
        $frequency = in_array($input['frequency'] ?? '', $allowedFreq, true) ? $input['frequency'] : 'daily';

        $runTime = trim((string)($input['run_time'] ?? '02:00'));
        if (!preg_match('/^([01]\\d|2[0-3]):([0-5]\\d)$/', $runTime)) {
            throw new InvalidArgumentException('Run time must be in HH:MM 24-hour format.');
        }
        $runTime .= ':00';

        $dayOfWeek = (int)($input['day_of_week'] ?? 1);
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new InvalidArgumentException('Day of week must be between 0 (Sunday) and 6 (Saturday).');
        }

        $dayOfMonth = (int)($input['day_of_month'] ?? 1);
        if ($dayOfMonth < 1 || $dayOfMonth > 31) {
            throw new InvalidArgumentException('Day of month must be between 1 and 31.');
        }

        $retentionDays = (int)($input['retention_days'] ?? 30);
        if ($retentionDays < 1 || $retentionDays > 3650) {
            throw new InvalidArgumentException('Retention days must be between 1 and 3650.');
        }

        $settings = $this->getSettings();
        $scheduleToken = $settings['schedule_token'];
        if ($scheduleToken === '') {
            $scheduleToken = bin2hex(random_bytes(24));
        }

        $nextRunAt = null;
        if ($enabled) {
            $nextRunAt = $this->calculateNextRunAt($frequency, $runTime, $dayOfWeek, $dayOfMonth)->format('Y-m-d H:i:s');
        }

        $stmt = $this->db->prepare(
            "UPDATE backup_settings
             SET is_enabled = ?, frequency = ?, run_time = ?, day_of_week = ?, day_of_month = ?, retention_days = ?,
                 schedule_token = ?, next_run_at = ?, updated_by = ?
             WHERE setting_id = 1"
        );

        $updatedBy = $updatedBy ? (int)$updatedBy : null;
        $stmt->bind_param(
            'issiiissi',
            $enabled,
            $frequency,
            $runTime,
            $dayOfWeek,
            $dayOfMonth,
            $retentionDays,
            $scheduleToken,
            $nextRunAt,
            $updatedBy
        );
        $stmt->execute();

        return $this->getSettings();
    }

    public function rotateScheduleToken($updatedBy) {
        $newToken = bin2hex(random_bytes(24));
        $updatedBy = $updatedBy ? (int)$updatedBy : null;

        $stmt = $this->db->prepare("UPDATE backup_settings SET schedule_token = ?, updated_by = ? WHERE setting_id = 1");
        $stmt->bind_param('si', $newToken, $updatedBy);
        $stmt->execute();

        return $newToken;
    }

    public function createBackup($triggerType, $initiatedBy = null) {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ignore_user_abort(true);

        $startedAt = date('Y-m-d H:i:s');
        $backupId = $this->insertHistoryPlaceholder($triggerType, $initiatedBy, $startedAt);

        try {
            $timestamp = gmdate('Ymd_His');
            $fileName = sprintf('stores_backup_%s_%s.sql', $triggerType, $timestamp);
            $absolutePath = $this->backupDir . DIRECTORY_SEPARATOR . $fileName;

            $this->writeSqlDump($absolutePath);

            $size = filesize($absolutePath);
            $completedAt = date('Y-m-d H:i:s');

            $stmt = $this->db->prepare(
                "UPDATE backup_history
                 SET file_name = ?, file_path = ?, file_size_bytes = ?, status = 'success', completed_at = ?
                 WHERE backup_id = ?"
            );
            $stmt->bind_param('ssisi', $fileName, $absolutePath, $size, $completedAt, $backupId);
            $stmt->execute();

            $settings = $this->getSettings();
            if ($triggerType === 'scheduled') {
                $nextRunAt = null;
                if ((int)$settings['is_enabled'] === 1) {
                    $nextRunAt = $this->calculateNextRunAt(
                        (string)$settings['frequency'],
                        (string)$settings['run_time'],
                        (int)$settings['day_of_week'],
                        (int)$settings['day_of_month'],
                        new DateTime('now', new DateTimeZone('UTC'))
                    )->format('Y-m-d H:i:s');
                }

                $updateStmt = $this->db->prepare("UPDATE backup_settings SET last_run_at = ?, next_run_at = ? WHERE setting_id = 1");
                $updateStmt->bind_param('ss', $completedAt, $nextRunAt);
                $updateStmt->execute();
            }

            $this->applyRetentionPolicy((int)$settings['retention_days']);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'file_name' => $fileName,
                'file_path' => $absolutePath,
                'file_size_bytes' => (int)$size,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ];
        } catch (Throwable $e) {
            $completedAt = date('Y-m-d H:i:s');
            $errorMessage = $e->getMessage();
            $stmt = $this->db->prepare(
                "UPDATE backup_history
                 SET status = 'failed', completed_at = ?, error_message = ?
                 WHERE backup_id = ?"
            );
            $stmt->bind_param('ssi', $completedAt, $errorMessage, $backupId);
            $stmt->execute();

            return [
                'success' => false,
                'message' => $errorMessage,
                'backup_id' => $backupId,
            ];
        }
    }

    public function runScheduledBackupIfDue() {
        $settings = $this->getSettings();

        if ((int)$settings['is_enabled'] !== 1) {
            return ['ran' => false, 'reason' => 'disabled'];
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $nextRunAtRaw = $settings['next_run_at'];
        if (!$nextRunAtRaw) {
            $nextRunAtRaw = $this->calculateNextRunAt(
                (string)$settings['frequency'],
                (string)$settings['run_time'],
                (int)$settings['day_of_week'],
                (int)$settings['day_of_month']
            )->format('Y-m-d H:i:s');

            $stmt = $this->db->prepare("UPDATE backup_settings SET next_run_at = ? WHERE setting_id = 1");
            $stmt->bind_param('s', $nextRunAtRaw);
            $stmt->execute();
        }

        $nextRunAt = new DateTime($nextRunAtRaw, new DateTimeZone('UTC'));
        if ($now < $nextRunAt) {
            return ['ran' => false, 'reason' => 'not_due', 'next_run_at' => $nextRunAt->format('Y-m-d H:i:s')];
        }

        $lockResult = $this->db->query("SELECT GET_LOCK('stores_scheduled_backup', 2) AS l");
        $lockRow = $lockResult->fetch_assoc();
        if ((int)($lockRow['l'] ?? 0) !== 1) {
            return ['ran' => false, 'reason' => 'locked'];
        }

        try {
            $backupResult = $this->createBackup('scheduled', null);
            return ['ran' => true, 'result' => $backupResult];
        } finally {
            $this->db->query("SELECT RELEASE_LOCK('stores_scheduled_backup')");
        }
    }

    public function getRestoreConfirmationPhrase() {
        return $this->restoreConfirmationPhrase;
    }

    public function restoreFromStoredBackup($backupId, $initiatedBy, $confirmationText, $options = []) {
        $record = $this->getHistoryById($backupId);
        if (!$record || (string)$record['status'] !== 'success') {
            return ['success' => false, 'message' => 'Selected backup record is not available.'];
        }

        $path = (string)$record['file_path'];
        if (!is_file($path)) {
            return ['success' => false, 'message' => 'Selected backup file does not exist on disk.'];
        }

        return $this->performRestore('stored_backup', (string)$record['file_name'], $path, $initiatedBy, $confirmationText, $options);
    }

    public function restoreFromUploadedFile($uploadedFile, $initiatedBy, $confirmationText, $options = []) {
        if (!isset($uploadedFile['error']) || (int)$uploadedFile['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed. Please provide a valid .sql backup file.'];
        }

        $originalName = basename((string)($uploadedFile['name'] ?? 'uploaded.sql'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            return ['success' => false, 'message' => 'Only .sql files are allowed for restore.'];
        }

        $tmpPath = (string)$uploadedFile['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            return ['success' => false, 'message' => 'Invalid uploaded file source.'];
        }

        return $this->performRestore('upload', $originalName, $tmpPath, $initiatedBy, $confirmationText, $options);
    }

    public function rollbackToSafetyBackup($restoreId, $initiatedBy) {
        $record = $this->getRestoreHistoryById($restoreId);
        if (!$record) {
            return ['success' => false, 'message' => 'Restore record not found.'];
        }

        $safetyBackupId = (int)($record['safety_backup_id'] ?? 0);
        if ($safetyBackupId <= 0) {
            return ['success' => false, 'message' => 'No safety backup is linked to this restore record.'];
        }

        return $this->restoreFromStoredBackup(
            $safetyBackupId,
            $initiatedBy,
            $this->restoreConfirmationPhrase,
            ['skip_confirmation' => true]
        );
    }

    public function previewStoredBackup($backupId) {
        $record = $this->getHistoryById($backupId);
        if (!$record || (string)$record['status'] !== 'success') {
            return ['success' => false, 'message' => 'Selected backup record is not available.'];
        }

        $path = (string)$record['file_path'];
        if (!is_file($path)) {
            return ['success' => false, 'message' => 'Selected backup file does not exist on disk.'];
        }

        return $this->buildRestorePreview('stored_backup', (string)$record['file_name'], $path);
    }

    public function previewUploadedFile($uploadedFile) {
        if (!isset($uploadedFile['error']) || (int)$uploadedFile['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed. Please provide a valid .sql backup file.'];
        }

        $originalName = basename((string)($uploadedFile['name'] ?? 'uploaded.sql'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            return ['success' => false, 'message' => 'Only .sql files are allowed for preview.'];
        }

        $tmpPath = (string)$uploadedFile['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            return ['success' => false, 'message' => 'Invalid uploaded file source.'];
        }

        return $this->buildRestorePreview('upload', $originalName, $tmpPath);
    }

    private function insertHistoryPlaceholder($triggerType, $initiatedBy, $startedAt) {
        $triggerType = ($triggerType === 'scheduled') ? 'scheduled' : 'manual';
        $fileName = 'pending';
        $filePath = '';
        $size = 0;
        $status = 'failed';

        $initiatedBy = $initiatedBy ? (int)$initiatedBy : null;

        $stmt = $this->db->prepare(
            "INSERT INTO backup_history
            (file_name, file_path, file_size_bytes, trigger_type, status, started_at, initiated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssisssi', $fileName, $filePath, $size, $triggerType, $status, $startedAt, $initiatedBy);
        $stmt->execute();

        return (int)$this->db->insert_id;
    }

    private function performRestore($sourceType, $sourceLabel, $sourcePath, $initiatedBy, $confirmationText, $options = []) {
        $skipConfirmation = !empty($options['skip_confirmation']);
        $dryRun = !empty($options['dry_run']);

        if (!$skipConfirmation && trim((string)$confirmationText) !== $this->restoreConfirmationPhrase) {
            return ['success' => false, 'message' => 'Confirmation text mismatch. Type ' . $this->restoreConfirmationPhrase . ' to continue.'];
        }

        $sql = @file_get_contents($sourcePath);
        if ($sql === false || trim($sql) === '') {
            return ['success' => false, 'message' => 'Restore source is empty or unreadable.'];
        }

        $validation = $this->validateRestoreSql($sql);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => 'Validation failed: ' . $validation['message']];
        }

        if ($dryRun) {
            $dryRunMessage = sprintf(
                'Dry run passed: %d SQL statements validated.',
                (int)$validation['statement_count']
            );
            return [
                'success' => true,
                'dry_run' => true,
                'message' => $dryRunMessage,
                'statement_count' => (int)$validation['statement_count']
            ];
        }

        $startedAt = date('Y-m-d H:i:s');
        $safetyBackupId = null;
        $restoreId = $this->insertRestorePlaceholder($sourceType, $sourceLabel, $sourcePath, $initiatedBy, $startedAt);

        try {
            $safetyBackup = $this->createBackup('manual', $initiatedBy);
            if (!empty($safetyBackup['success'])) {
                $safetyBackupId = (int)$safetyBackup['backup_id'];
            }

            $updateSafetyStmt = $this->db->prepare("UPDATE restore_history SET safety_backup_id = ? WHERE restore_id = ?");
            $updateSafetyStmt->bind_param('ii', $safetyBackupId, $restoreId);
            $updateSafetyStmt->execute();

            $this->executeSqlBatch($sql);
            $this->ensureBackupSchema();

            $completedAt = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare(
                "UPDATE restore_history
                 SET status = 'success', completed_at = ?
                 WHERE restore_id = ?"
            );
            $stmt->bind_param('si', $completedAt, $restoreId);
            $stmt->execute();

            return [
                'success' => true,
                'restore_id' => $restoreId,
                'safety_backup_id' => $safetyBackupId,
                'message' => 'Restore completed successfully.',
                'statement_count' => (int)$validation['statement_count']
            ];
        } catch (Throwable $e) {
            $completedAt = date('Y-m-d H:i:s');
            $errorMessage = $e->getMessage();

            $this->ensureBackupSchema();

            $stmt = $this->db->prepare(
                "UPDATE restore_history
                 SET status = 'failed', completed_at = ?, error_message = ?
                 WHERE restore_id = ?"
            );
            $stmt->bind_param('ssi', $completedAt, $errorMessage, $restoreId);
            $stmt->execute();

            return [
                'success' => false,
                'restore_id' => $restoreId,
                'safety_backup_id' => $safetyBackupId,
                'message' => 'Restore failed: ' . $errorMessage
            ];
        }
    }

    private function validateRestoreSql($sql) {
        $trimmed = trim((string)$sql);
        if ($trimmed === '') {
            return ['valid' => false, 'message' => 'The SQL script is empty.', 'statement_count' => 0, 'blocked_patterns' => []];
        }

        $statementCount = preg_match_all('/;\s*(?:\r?\n|$)/', $trimmed, $matches);
        if ($statementCount < 1) {
            return ['valid' => false, 'message' => 'No executable SQL statements were detected.', 'statement_count' => 0, 'blocked_patterns' => []];
        }

        $dangerPatterns = [
            '/\bDROP\s+DATABASE\b/i',
            '/\bCREATE\s+DATABASE\b/i',
            '/\bALTER\s+USER\b/i',
            '/\bGRANT\b/i',
            '/\bREVOKE\b/i'
        ];

        $blockedPatterns = [];

        foreach ($dangerPatterns as $pattern) {
            if (preg_match($pattern, $trimmed)) {
                $blockedPatterns[] = $this->getDangerPatternLabel($pattern);
            }
        }

        if (!empty($blockedPatterns)) {
            return [
                'valid' => false,
                'message' => 'The script contains server-level operations that are not allowed in restore mode.',
                'statement_count' => $statementCount,
                'blocked_patterns' => $blockedPatterns
            ];
        }

        $hasDataOrSchema = preg_match('/\b(CREATE\s+TABLE|INSERT\s+INTO|DROP\s+TABLE|ALTER\s+TABLE|TRUNCATE\s+TABLE)\b/i', $trimmed);
        if (!$hasDataOrSchema) {
            return [
                'valid' => false,
                'message' => 'The script does not look like a database backup (no table/data statements found).',
                'statement_count' => $statementCount,
                'blocked_patterns' => []
            ];
        }

        return ['valid' => true, 'message' => 'Validation passed.', 'statement_count' => $statementCount, 'blocked_patterns' => []];
    }

    private function buildRestorePreview($sourceType, $sourceLabel, $sourcePath) {
        $sql = @file_get_contents($sourcePath);
        if ($sql === false || trim($sql) === '') {
            return ['success' => false, 'message' => 'Restore source is empty or unreadable.'];
        }

        $validation = $this->validateRestoreSql($sql);
        return [
            'success' => $validation['valid'],
            'source_type' => $sourceType,
            'source_label' => $sourceLabel,
            'source_path' => $sourcePath,
            'message' => $validation['message'],
            'statement_count' => (int)$validation['statement_count'],
            'blocked_patterns' => $validation['blocked_patterns'] ?? [],
            'looks_like_backup' => (bool)$validation['valid']
        ];
    }

    private function getDangerPatternLabel($pattern) {
        $labels = [
            '/\\bDROP\\s+DATABASE\\b/i' => 'DROP DATABASE',
            '/\\bCREATE\\s+DATABASE\\b/i' => 'CREATE DATABASE',
            '/\\bALTER\\s+USER\\b/i' => 'ALTER USER',
            '/\\bGRANT\\b/i' => 'GRANT',
            '/\\bREVOKE\\b/i' => 'REVOKE',
        ];

        return $labels[$pattern] ?? 'Restricted SQL';
    }

    private function executeSqlBatch($sql) {
        @set_time_limit(0);

        if (!$this->db->multi_query($sql)) {
            throw new RuntimeException('SQL restore failed: ' . $this->db->error);
        }

        do {
            if ($result = $this->db->store_result()) {
                $result->free();
            }
            if (!$this->db->more_results()) {
                break;
            }
            if (!$this->db->next_result()) {
                throw new RuntimeException('SQL restore failed while processing statements: ' . $this->db->error);
            }
        } while (true);
    }

    private function insertRestorePlaceholder($sourceType, $sourceLabel, $sourcePath, $initiatedBy, $startedAt) {
        $sourceType = ($sourceType === 'upload') ? 'upload' : 'stored_backup';
        $status = 'failed';
        $initiatedBy = $initiatedBy ? (int)$initiatedBy : null;

        $stmt = $this->db->prepare(
            "INSERT INTO restore_history
            (source_type, source_label, source_path, status, started_at, initiated_by)
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssi', $sourceType, $sourceLabel, $sourcePath, $status, $startedAt, $initiatedBy);
        $stmt->execute();

        return (int)$this->db->insert_id;
    }

    private function writeSqlDump($filePath) {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $handle = fopen($filePath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open backup file for writing.');
        }

        fwrite($handle, "-- Stores backup generated at " . gmdate('Y-m-d H:i:s') . " UTC\n");
        fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($handle, "SET time_zone = '+00:00';\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        $tables = [];
        $tablesRes = $this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $tablesRes->fetch_row()) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $safeTable = str_replace('`', '``', $table);
            fwrite($handle, "-- ----------------------------\n");
            fwrite($handle, "-- Table structure for `" . $safeTable . "`\n");
            fwrite($handle, "-- ----------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `" . $safeTable . "`;\n");

            $createRes = $this->db->query("SHOW CREATE TABLE `" . $safeTable . "`");
            $createRow = $createRes->fetch_row();
            fwrite($handle, $createRow[1] . ";\n\n");

            $columnNames = [];
            $columnsRes = $this->db->query("SHOW COLUMNS FROM `" . $safeTable . "`");
            while ($columnRow = $columnsRes->fetch_assoc()) {
                $columnNames[] = (string)$columnRow['Field'];
            }

            if (!empty($columnNames)) {
                fwrite($handle, "-- ----------------------------\n");
                fwrite($handle, "-- Records of `" . $safeTable . "`\n");
                fwrite($handle, "-- ----------------------------\n");

                $quotedColumns = [];
                foreach ($columnNames as $columnName) {
                    $quotedColumns[] = '`' . str_replace('`', '``', $columnName) . '`';
                }

                $rowsRes = $this->db->query("SELECT * FROM `" . $safeTable . "`", MYSQLI_USE_RESULT);
                $valueBatches = [];

                while ($row = $rowsRes->fetch_assoc()) {
                    $rowValues = [];
                    foreach ($columnNames as $columnName) {
                        $rowValues[] = $this->formatSqlValue($row[$columnName] ?? null);
                    }
                    $valueBatches[] = '(' . implode(', ', $rowValues) . ')';

                    if (count($valueBatches) >= $this->dumpBatchSize) {
                        $insertSql = "INSERT INTO `" . $safeTable . "` (" . implode(', ', $quotedColumns) . ") VALUES\n" . implode(",\n", $valueBatches) . ";\n";
                        fwrite($handle, $insertSql);
                        $valueBatches = [];
                    }
                }

                if (!empty($valueBatches)) {
                    $insertSql = "INSERT INTO `" . $safeTable . "` (" . implode(', ', $quotedColumns) . ") VALUES\n" . implode(",\n", $valueBatches) . ";\n";
                    fwrite($handle, $insertSql);
                }

                $rowsRes->free();
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($handle);
    }

    private function formatSqlValue($value) {
        if ($value === null) {
            return 'NULL';
        }

        return "'" . $this->db->real_escape_string((string)$value) . "'";
    }

    private function calculateNextRunAt($frequency, $runTime, $dayOfWeek, $dayOfMonth, $from = null) {
        $tz = new DateTimeZone('UTC');
        $from = $from ?: new DateTime('now', $tz);

        list($hour, $minute, $second) = array_map('intval', explode(':', $runTime));
        $candidate = clone $from;
        $candidate->setTimezone($tz);

        if ($frequency === 'daily') {
            $candidate->setTime($hour, $minute, $second);
            if ($candidate <= $from) {
                $candidate->modify('+1 day');
            }
            return $candidate;
        }

        if ($frequency === 'weekly') {
            $candidate->setTime($hour, $minute, $second);
            $currentDow = (int)$candidate->format('w');
            $delta = $dayOfWeek - $currentDow;
            $candidate->modify(($delta >= 0 ? '+' : '') . $delta . ' day');
            if ($candidate <= $from) {
                $candidate->modify('+1 week');
            }
            return $candidate;
        }

        // Monthly
        $year = (int)$candidate->format('Y');
        $month = (int)$candidate->format('m');
        $safeDay = min($dayOfMonth, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        $candidate->setDate($year, $month, $safeDay);
        $candidate->setTime($hour, $minute, $second);

        if ($candidate <= $from) {
            $candidate->modify('first day of next month');
            $year = (int)$candidate->format('Y');
            $month = (int)$candidate->format('m');
            $safeDay = min($dayOfMonth, cal_days_in_month(CAL_GREGORIAN, $month, $year));
            $candidate->setDate($year, $month, $safeDay);
            $candidate->setTime($hour, $minute, $second);
        }

        return $candidate;
    }

    private function applyRetentionPolicy($retentionDays) {
        $retentionDays = max(1, min(3650, (int)$retentionDays));
        $cutoff = new DateTime('now', new DateTimeZone('UTC'));
        $cutoff->modify('-' . $retentionDays . ' days');
        $cutoffString = $cutoff->format('Y-m-d H:i:s');

        $sql = "SELECT backup_id, file_path
                FROM backup_history
                WHERE status = 'success' AND completed_at IS NOT NULL AND completed_at < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $cutoffString);
        $stmt->execute();

        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['backup_id'];
            $path = (string)$row['file_path'];
            if ($path !== '' && is_file($path)) {
                @unlink($path);
            }
        }

        if (!empty($ids)) {
            $in = implode(',', $ids);
            $this->db->query("DELETE FROM backup_history WHERE backup_id IN (" . $in . ")");
        }
    }
}
