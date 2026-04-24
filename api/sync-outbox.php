<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (!isAuthenticated()) {
    Response::error('Authentication required', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

if (!Security::verifyCSRFToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? ''))) {
    Response::error('Invalid request token', 419);
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    Response::error('Invalid sync payload', 400);
}

$records = $payload['records'] ?? [];
if (!is_array($records) || empty($records)) {
    Response::error('No queued records provided', 400);
}

$db = Database::getInstance()->getConnection();
$currentUser = getCurrentUser();
$userId = (int)($currentUser['user_id'] ?? 0);

ensureOfflineSyncLogTable($db);

$requisitionController = new RequisitionController();
$stockIssueController = new StockIssueController();
$grnController = new GRNController();
$adjustmentController = new AdjustmentController();

$results = [];

foreach ($records as $record) {
    $result = syncSingleRecord(
        $db,
        $userId,
        $record,
        $requisitionController,
        $stockIssueController,
        $grnController,
        $adjustmentController
    );
    $results[] = $result;
}

Response::json([
    'success' => true,
    'message' => 'Sync completed',
    'results' => $results
]);

function ensureOfflineSyncLogTable(mysqli $db): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS offline_sync_log (
            sync_id INT PRIMARY KEY AUTO_INCREMENT,
            client_record_id VARCHAR(100) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            record_type VARCHAR(80) NOT NULL,
            page_url VARCHAR(255) NULL,
            payload_json LONGTEXT NOT NULL,
            status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
            server_reference_type VARCHAR(50) NULL,
            server_reference_id INT NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            INDEX idx_offline_sync_status (status, created_at),
            INDEX idx_offline_sync_user (user_id, created_at)
        )
    ";
    $db->query($sql);
}

function syncSingleRecord(mysqli $db, int $userId, array $record, RequisitionController $requisitionController, StockIssueController $stockIssueController, GRNController $grnController, AdjustmentController $adjustmentController): array {
    $recordId = trim((string)($record['id'] ?? ''));
    $recordType = trim((string)($record['title'] ?? ''));
    $entries = $record['entries'] ?? [];
    $pageUrl = trim((string)($record['pageUrl'] ?? ''));

    if ($recordId === '' || !is_array($entries)) {
        return [
            'id' => $recordId,
            'success' => false,
            'message' => 'Invalid offline record payload'
        ];
    }

    $existing = getSyncLogEntry($db, $recordId);
    if ($existing && ($existing['status'] ?? '') === 'success') {
        return [
            'id' => $recordId,
            'success' => true,
            'duplicate' => true,
            'message' => 'Already synced'
        ];
    }

    $payloadJson = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    upsertSyncLogPending($db, $recordId, $userId, $recordType, $pageUrl, $payloadJson);

    try {
        $result = dispatchOfflineRecord($recordType, $entries, $userId, $requisitionController, $stockIssueController, $grnController, $adjustmentController);

        if (!is_array($result) || empty($result['success'])) {
            $message = is_array($result) && isset($result['message']) ? (string)$result['message'] : 'Sync failed';
            markSyncLogFailed($db, $recordId, $message);
            return [
                'id' => $recordId,
                'success' => false,
                'message' => $message
            ];
        }

        $referenceId = (int)($result['requisition_id'] ?? $result['issue_id'] ?? $result['grn_id'] ?? $result['adjustment_id'] ?? 0);
        $referenceType = syncReferenceType($recordType);
        markSyncLogSuccess($db, $recordId, $referenceType, $referenceId);

        return [
            'id' => $recordId,
            'success' => true,
            'reference_id' => $referenceId,
            'message' => $result['message'] ?? 'Synced'
        ];
    } catch (Throwable $exception) {
        markSyncLogFailed($db, $recordId, $exception->getMessage());
        return [
            'id' => $recordId,
            'success' => false,
            'message' => $exception->getMessage()
        ];
    }
}

function dispatchOfflineRecord(string $recordType, array $entries, int $userId, RequisitionController $requisitionController, StockIssueController $stockIssueController, GRNController $grnController, AdjustmentController $adjustmentController): array {
    switch ($recordType) {
        case 'requisition-create':
            $data = [
                'store_id' => firstEntry($entries, 'store_id'),
                'notes' => firstEntry($entries, 'notes')
            ];
            $items = buildItems($entries, [
                'product_id' => 'items[product_id][]',
                'quantity_requested' => 'items[quantity_requested][]',
                'remarks' => 'items[remarks][]'
            ]);
            return $requisitionController->create($data, $items, $userId);

        case 'stock-issue-create':
        case 'direct-issue-create':
            $issueMode = firstEntry($entries, 'issue_mode');
            $notes = firstEntry($entries, 'notes');
            if ($issueMode === 'direct') {
                $data = [
                    'store_id' => firstEntry($entries, 'store_id'),
                    'department_id' => firstEntry($entries, 'department_id')
                ];
                $items = buildItems($entries, [
                    'product_id' => 'items[product_id][]',
                    'quantity_issued' => 'items[quantity_issued][]',
                    'remarks' => 'items[remarks][]'
                ]);
                return $stockIssueController->createDirectIssue($data['store_id'], $data['department_id'], $items, $notes, $userId);
            }

            $items = buildItems($entries, [
                'req_item_id' => 'items[req_item_id][]',
                'product_id' => 'items[product_id][]',
                'quantity_issued' => 'items[quantity_issued][]',
                'remarks' => 'items[remarks][]'
            ]);

            return $stockIssueController->createIssue(firstEntry($entries, 'requisition_id'), $items, $notes, $userId);

        case 'grn-create':
            $data = [
                'supplier_id' => firstEntry($entries, 'supplier_id'),
                'store_id' => firstEntry($entries, 'store_id'),
                'receipt_date' => firstEntry($entries, 'receipt_date'),
                'receipt_time' => firstEntry($entries, 'receipt_time'),
                'delivery_note_ref' => firstEntry($entries, 'delivery_note_ref'),
                'invoice_reference' => firstEntry($entries, 'invoice_reference'),
                'notes' => firstEntry($entries, 'notes')
            ];
            $items = buildItems($entries, [
                'product_id' => 'items[product_id][]',
                'quantity_expected' => 'items[quantity_expected][]',
                'quantity_received' => 'items[quantity_received][]',
                'unit_price' => 'items[unit_price][]',
                'batch_number' => 'items[batch_number][]',
                'expiry_date' => 'items[expiry_date][]'
            ]);
            return $grnController->create($data, $items, $userId);

        case 'adjustment-create':
            $data = [
                'store_id' => firstEntry($entries, 'store_id'),
                'adjustment_reason' => firstEntry($entries, 'adjustment_reason'),
                'notes' => firstEntry($entries, 'notes')
            ];
            $items = buildItems($entries, [
                'product_id' => 'items[product_id][]',
                'quantity_change' => 'items[quantity_change][]',
                'reason_details' => 'items[reason_details][]'
            ]);
            return $adjustmentController->create($data, $items, $userId);

        default:
            return ['success' => false, 'message' => 'Unsupported offline record type: ' . $recordType];
    }
}

function firstEntry(array $entries, string $name): string {
    foreach ($entries as $entry) {
        if (!is_array($entry) || count($entry) < 2) {
            continue;
        }
        if ((string)$entry[0] === $name) {
            return (string)$entry[1];
        }
    }
    return '';
}

function buildItems(array $entries, array $map): array {
    $items = [];
    foreach ($map as $targetKey => $entryName) {
        $items[$targetKey] = listEntries($entries, $entryName);
    }

    return $items;
}

function listEntries(array $entries, string $name): array {
    $values = [];
    foreach ($entries as $entry) {
        if (!is_array($entry) || count($entry) < 2) {
            continue;
        }
        if ((string)$entry[0] === $name) {
            $values[] = (string)$entry[1];
        }
    }
    return $values;
}

function syncReferenceType(string $recordType): string {
    return match ($recordType) {
        'requisition-create' => 'REQUISITION',
        'stock-issue-create', 'direct-issue-create' => 'ISSUE',
        'grn-create' => 'GRN',
        'adjustment-create' => 'ADJUSTMENT',
        default => 'OFFLINE'
    };
}

function getSyncLogEntry(mysqli $db, string $clientRecordId): ?array {
    $sql = "SELECT * FROM offline_sync_log WHERE client_record_id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $clientRecordId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function upsertSyncLogPending(mysqli $db, string $clientRecordId, int $userId, string $recordType, string $pageUrl, string $payloadJson): void {
    $sql = "
        INSERT INTO offline_sync_log (client_record_id, user_id, record_type, page_url, payload_json, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            record_type = VALUES(record_type),
            page_url = VALUES(page_url),
            payload_json = VALUES(payload_json),
            status = 'pending',
            error_message = NULL,
            server_reference_type = NULL,
            server_reference_id = NULL,
            processed_at = NULL
    ";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sisss', $clientRecordId, $userId, $recordType, $pageUrl, $payloadJson);
    $stmt->execute();
}

function markSyncLogSuccess(mysqli $db, string $clientRecordId, string $referenceType, int $referenceId): void {
    $sql = "
        UPDATE offline_sync_log
        SET status = 'success', server_reference_type = ?, server_reference_id = ?, error_message = NULL, processed_at = NOW()
        WHERE client_record_id = ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sis', $referenceType, $referenceId, $clientRecordId);
    $stmt->execute();
}

function markSyncLogFailed(mysqli $db, string $clientRecordId, string $message): void {
    $sql = "
        UPDATE offline_sync_log
        SET status = 'failed', error_message = ?, processed_at = NOW()
        WHERE client_record_id = ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $message, $clientRecordId);
    $stmt->execute();
}