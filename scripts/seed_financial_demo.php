<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script is CLI-only.' . PHP_EOL);
}

$db = Database::getInstance()->getConnection();

function scalar(mysqli $db, string $sql, string $types = '', ...$params)
{
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $db->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row ? $row[0] : null;
}

function ensureRole(mysqli $db, string $name, string $description): int
{
    $stmt = $db->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    $stmt->bind_param('ss', $name, $description);
    $stmt->execute();

    return (int)scalar($db, "SELECT role_id FROM roles WHERE role_name = ? LIMIT 1", 's', $name);
}

function ensureUser(mysqli $db, string $username, string $email, string $fullName, int $roleId, string $plainPassword): int
{
    $existing = scalar($db, "SELECT user_id FROM users WHERE username = ? LIMIT 1", 's', $username);
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT);

    if ($existing) {
        $userId = (int)$existing;
        $stmt = $db->prepare("UPDATE users SET email = ?, full_name = ?, role_id = ?, status = 'active' WHERE user_id = ?");
        $stmt->bind_param('ssii', $email, $fullName, $roleId, $userId);
        $stmt->execute();
        return $userId;
    }

    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param('ssssi', $username, $email, $hash, $fullName, $roleId);
    $stmt->execute();

    return (int)$db->insert_id;
}

function ensureStore(mysqli $db, string $name, string $code, string $location, string $description, int $responsibleUserId): int
{
    $existing = scalar($db, "SELECT store_id FROM stores WHERE store_code = ? LIMIT 1", 's', $code);
    if ($existing) {
        $storeId = (int)$existing;
        $stmt = $db->prepare("UPDATE stores SET store_name = ?, location = ?, description = ?, responsible_user_id = ?, status = 'active' WHERE store_id = ?");
        $stmt->bind_param('sssii', $name, $location, $description, $responsibleUserId, $storeId);
        $stmt->execute();
        return $storeId;
    }

    $stmt = $db->prepare("INSERT INTO stores (store_name, store_code, location, description, responsible_user_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param('ssssi', $name, $code, $location, $description, $responsibleUserId);
    $stmt->execute();

    return (int)$db->insert_id;
}

function ensureDepartment(mysqli $db, string $name, string $code, int $headUserId, float $monthlyBudget, float $weeklyBudget): int
{
    $existing = scalar($db, "SELECT dept_id FROM departments WHERE dept_code = ? LIMIT 1", 's', $code);
    if ($existing) {
        $deptId = (int)$existing;
        $stmt = $db->prepare("UPDATE departments SET dept_name = ?, head_user_id = ?, monthly_budget = ?, weekly_budget = ?, status = 'active' WHERE dept_id = ?");
        $stmt->bind_param('siddi', $name, $headUserId, $monthlyBudget, $weeklyBudget, $deptId);
        $stmt->execute();
        return $deptId;
    }

    $stmt = $db->prepare("INSERT INTO departments (dept_name, dept_code, head_user_id, status, monthly_budget, weekly_budget) VALUES (?, ?, ?, 'active', ?, ?)");
    $stmt->bind_param('ssidd', $name, $code, $headUserId, $monthlyBudget, $weeklyBudget);
    $stmt->execute();

    return (int)$db->insert_id;
}

function ensureSupplier(mysqli $db, string $name, string $email): int
{
    $existing = scalar($db, "SELECT supplier_id FROM suppliers WHERE supplier_name = ? LIMIT 1", 's', $name);
    if ($existing) {
        $supplierId = (int)$existing;
        $stmt = $db->prepare("UPDATE suppliers SET email = ?, status = 'active' WHERE supplier_id = ?");
        $stmt->bind_param('si', $email, $supplierId);
        $stmt->execute();
        return $supplierId;
    }

    $contact = 'Accounts Team';
    $phone = '+263700000000';
    $city = 'Mutare';
    $paymentTerms = '30 Days';
    $status = 'active';

    $stmt = $db->prepare("INSERT INTO suppliers (supplier_name, contact_person, email, phone, city, payment_terms, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssss', $name, $contact, $email, $phone, $city, $paymentTerms, $status);
    $stmt->execute();

    return (int)$db->insert_id;
}

function ensureCategory(mysqli $db, string $name, string $description): int
{
    $existing = scalar($db, "SELECT category_id FROM categories WHERE category_name = ? LIMIT 1", 's', $name);
    if ($existing) {
        return (int)$existing;
    }

    $status = 'active';
    $stmt = $db->prepare("INSERT INTO categories (category_name, description, status) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $description, $status);
    $stmt->execute();

    return (int)$db->insert_id;
}

function ensureProduct(mysqli $db, string $name, string $code, int $categoryId, string $uom, int $reorderLevel, int $reorderQty): int
{
    $existing = scalar($db, "SELECT product_id FROM products WHERE product_code = ? LIMIT 1", 's', $code);
    if ($existing) {
        $productId = (int)$existing;
        $status = 'active';
        $stmt = $db->prepare("UPDATE products SET product_name = ?, category_id = ?, unit_of_measure = ?, reorder_level = ?, reorder_quantity = ?, status = ? WHERE product_id = ?");
        $stmt->bind_param('sisissi', $name, $categoryId, $uom, $reorderLevel, $reorderQty, $status, $productId);
        $stmt->execute();
        return $productId;
    }

    $status = 'active';
    $stmt = $db->prepare("INSERT INTO products (product_name, product_code, category_id, unit_of_measure, reorder_level, reorder_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssisiis', $name, $code, $categoryId, $uom, $reorderLevel, $reorderQty, $status);
    $stmt->execute();

    return (int)$db->insert_id;
}

function upsertStock(mysqli $db, int $productId, int $storeId, int $qtyDelta, int $fallbackReorder): void
{
    $stmt = $db->prepare(
        "INSERT INTO stock (product_id, store_id, quantity_on_hand, reorder_level, last_counted_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            quantity_on_hand = quantity_on_hand + VALUES(quantity_on_hand),
            reorder_level = IF(reorder_level > 0, reorder_level, VALUES(reorder_level)),
            last_counted_at = NOW()"
    );
    $stmt->bind_param('iiii', $productId, $storeId, $qtyDelta, $fallbackReorder);
    $stmt->execute();
}

function insertStockTx(mysqli $db, int $productId, int $storeId, string $type, string $refType, int $refId, int $qtyChange, float $unitPrice, float $totalValue, int $userId, string $notes): void
{
    $stmt = $db->prepare(
        "INSERT INTO stock_transactions
            (product_id, store_id, transaction_type, reference_type, reference_id, quantity_change, unit_price, total_value, performed_by, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iissiiddis', $productId, $storeId, $type, $refType, $refId, $qtyChange, $unitPrice, $totalValue, $userId, $notes);
    $stmt->execute();
}

$db->begin_transaction();

try {
    $adminRoleId = ensureRole($db, 'Admin', 'System administrator with full access');
    $storekeeperRoleId = ensureRole($db, 'Storekeeper', 'Manages stock and inventory');

    $adminUserId = ensureUser($db, 'admin', 'admin@manicaskyview.local', 'System Administrator', $adminRoleId, 'admin123');
    $storekeeperUserId = ensureUser($db, 'storekeeper1', 'storekeeper@manicaskyview.local', 'Storekeeper One', $storekeeperRoleId, 'store123');

    $storeId = ensureStore($db, 'Main Stores', 'MAIN001', 'Ground Floor', 'Main central store', $storekeeperUserId);

    $kitchenDeptId = ensureDepartment($db, 'Kitchen', 'KIT', $adminUserId, 3500.00, 900.00);
    $barDeptId = ensureDepartment($db, 'Bar', 'BAR', $adminUserId, 2200.00, 600.00);
    $houseDeptId = ensureDepartment($db, 'Housekeeping', 'HSK', $adminUserId, 1800.00, 450.00);

    $supplierId = ensureSupplier($db, 'Prime Foods Distributors', 'orders@primefoods.example');

    $foodCategoryId = ensureCategory($db, 'Food Items', 'Cooking ingredients and food');
    $beverageCategoryId = ensureCategory($db, 'Beverages', 'Drinks and beverage supplies');
    $cleaningCategoryId = ensureCategory($db, 'Cleaning Supplies', 'Cleaning and maintenance products');

    $oilProductId = ensureProduct($db, 'Cooking Oil - 5L', 'OIL-001', $foodCategoryId, 'Liters', 10, 50);
    $wineProductId = ensureProduct($db, 'Red Wine - 750ml', 'WIN-001', $beverageCategoryId, 'Bottles', 20, 80);
    $soapProductId = ensureProduct($db, 'Hand Soap - 500ml', 'SOP-001', $cleaningCategoryId, 'Bottles', 15, 50);

    $products = [
        $oilProductId => ['reorder' => 10, 'price' => 32.50],
        $wineProductId => ['reorder' => 20, 'price' => 12.80],
        $soapProductId => ['reorder' => 15, 'price' => 4.20],
    ];

    $grnNumber = 'DEMO-FIN-GRN-001';
    $existingGrnId = scalar($db, "SELECT grn_id FROM grn WHERE grn_number = ? LIMIT 1", 's', $grnNumber);
    if (!$existingGrnId) {
        $receiptDate = date('Y-m-d', strtotime('-8 days'));
        $receiptTime = '09:20:00';
        $deliveryRef = 'DN-2026-0408';
        $invoiceRef = 'INV-2026-0408';
        $status = 'verified';
        $notes = 'Financial demo stock receipt';

        $stmt = $db->prepare(
            "INSERT INTO grn (grn_number, supplier_id, store_id, received_by, receipt_date, receipt_time, delivery_note_ref, invoice_reference, total_cost, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)"
        );
        $stmt->bind_param('siiissssss', $grnNumber, $supplierId, $storeId, $storekeeperUserId, $receiptDate, $receiptTime, $deliveryRef, $invoiceRef, $status, $notes);
        $stmt->execute();
        $grnId = (int)$db->insert_id;

        $grnItems = [
            ['product_id' => $oilProductId, 'expected' => 120, 'received' => 120, 'unit_price' => 32.50],
            ['product_id' => $wineProductId, 'expected' => 240, 'received' => 240, 'unit_price' => 12.80],
            ['product_id' => $soapProductId, 'expected' => 180, 'received' => 180, 'unit_price' => 4.20],
        ];

        $grnTotal = 0.0;
        foreach ($grnItems as $line) {
            $batch = 'B-' . $grnNumber . '-' . $line['product_id'];
            $expiry = date('Y-m-d', strtotime('+180 days'));
            $stmt = $db->prepare(
                "INSERT INTO grn_items (grn_id, product_id, quantity_expected, quantity_received, unit_price, batch_number, expiry_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iiiidss', $grnId, $line['product_id'], $line['expected'], $line['received'], $line['unit_price'], $batch, $expiry);
            $stmt->execute();

            $lineTotal = (float)$line['received'] * (float)$line['unit_price'];
            $grnTotal += $lineTotal;
            upsertStock($db, (int)$line['product_id'], $storeId, (int)$line['received'], (int)$products[$line['product_id']]['reorder']);
            insertStockTx($db, (int)$line['product_id'], $storeId, 'receipt', 'GRN', $grnId, (int)$line['received'], (float)$line['unit_price'], $lineTotal, $storekeeperUserId, 'Demo seed receipt');
        }

        $stmt = $db->prepare("UPDATE grn SET total_cost = ? WHERE grn_id = ?");
        $stmt->bind_param('di', $grnTotal, $grnId);
        $stmt->execute();
    }

    $issues = [
        [
            'number' => 'DEMO-ISS-001',
            'department_id' => $kitchenDeptId,
            'days_ago' => 6,
            'lines' => [
                ['product_id' => $oilProductId, 'qty' => 18, 'unit_price' => 32.50],
            ],
            'notes' => 'Kitchen weekly stock issue'
        ],
        [
            'number' => 'DEMO-ISS-002',
            'department_id' => $barDeptId,
            'days_ago' => 5,
            'lines' => [
                ['product_id' => $wineProductId, 'qty' => 30, 'unit_price' => 12.80],
            ],
            'notes' => 'Bar beverage issue'
        ],
        [
            'number' => 'DEMO-ISS-003',
            'department_id' => $houseDeptId,
            'days_ago' => 4,
            'lines' => [
                ['product_id' => $soapProductId, 'qty' => 24, 'unit_price' => 4.20],
            ],
            'notes' => 'Housekeeping consumables'
        ],
    ];

    foreach ($issues as $issueDef) {
        $existingIssueId = scalar($db, "SELECT issue_id FROM stock_issues WHERE issue_number = ? LIMIT 1", 's', $issueDef['number']);
        if ($existingIssueId) {
            continue;
        }

        $issueDate = date('Y-m-d H:i:s', strtotime('-' . (int)$issueDef['days_ago'] . ' days'));
        $status = 'received';
        $stmt = $db->prepare(
            "INSERT INTO stock_issues (issue_number, store_id, department_id, issued_by, issue_date, received_by, received_date, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('siiisssss', $issueDef['number'], $storeId, $issueDef['department_id'], $storekeeperUserId, $issueDate, $adminUserId, $issueDate, $status, $issueDef['notes']);
        $stmt->execute();
        $issueId = (int)$db->insert_id;

        foreach ($issueDef['lines'] as $line) {
            $stmt = $db->prepare("INSERT INTO stock_issue_items (issue_id, product_id, quantity_issued, unit_price, remarks) VALUES (?, ?, ?, ?, ?)");
            $remark = 'Demo issue line';
            $stmt->bind_param('iiids', $issueId, $line['product_id'], $line['qty'], $line['unit_price'], $remark);
            $stmt->execute();

            upsertStock($db, (int)$line['product_id'], $storeId, -1 * (int)$line['qty'], (int)$products[$line['product_id']]['reorder']);
            $lineTotal = (float)$line['qty'] * (float)$line['unit_price'];
            insertStockTx($db, (int)$line['product_id'], $storeId, 'issue', 'ISSUE', $issueId, -1 * (int)$line['qty'], (float)$line['unit_price'], $lineTotal, $storekeeperUserId, 'Demo seed issue');
        }
    }

    $adjustmentNumber = 'DEMO-ADJ-001';
    $existingAdjustmentId = scalar($db, "SELECT adjustment_id FROM stock_adjustments WHERE adjustment_number = ? LIMIT 1", 's', $adjustmentNumber);
    if (!$existingAdjustmentId) {
        $adjustmentDate = date('Y-m-d H:i:s', strtotime('-3 days'));
        $reason = 'count_variance';
        $status = 'approved';
        $notes = 'Demo variance write-off';

        $stmt = $db->prepare(
            "INSERT INTO stock_adjustments (adjustment_number, store_id, adjustment_reason, adjusted_by, adjustment_date, approved_by, approval_date, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sisisssss', $adjustmentNumber, $storeId, $reason, $storekeeperUserId, $adjustmentDate, $adminUserId, $adjustmentDate, $status, $notes);
        $stmt->execute();
        $adjustmentId = (int)$db->insert_id;

        $qtyChange = -4;
        $unitPrice = 32.50;
        $stmt = $db->prepare("INSERT INTO adjustment_items (adjustment_id, product_id, quantity_change, reason_details) VALUES (?, ?, ?, ?)");
        $reasonDetails = 'Counted less stock than expected';
        $stmt->bind_param('iiis', $adjustmentId, $oilProductId, $qtyChange, $reasonDetails);
        $stmt->execute();

        upsertStock($db, $oilProductId, $storeId, $qtyChange, 10);
        $totalValue = abs($qtyChange) * $unitPrice;
        insertStockTx($db, $oilProductId, $storeId, 'adjustment', 'ADJUSTMENT', $adjustmentId, $qtyChange, $unitPrice, $totalValue, $adminUserId, 'Demo seed variance adjustment');
    }

    $posRows = [
        ['sale_reference' => 'DEMO-SALE-001', 'product_id' => $oilProductId, 'qty' => 12, 'consumed' => 12, 'revenue' => 660.00, 'cogs' => 390.00, 'days_ago' => 2],
        ['sale_reference' => 'DEMO-SALE-002', 'product_id' => $wineProductId, 'qty' => 20, 'consumed' => 20, 'revenue' => 500.00, 'cogs' => 256.00, 'days_ago' => 1],
        ['sale_reference' => 'DEMO-SALE-003', 'product_id' => $soapProductId, 'qty' => 15, 'consumed' => 15, 'revenue' => 150.00, 'cogs' => 63.00, 'days_ago' => 1],
    ];

    foreach ($posRows as $row) {
        $exists = scalar(
            $db,
            "SELECT pos_usage_id FROM pos_sales_usage WHERE sale_reference = ? AND product_id = ? LIMIT 1",
            'si',
            $row['sale_reference'],
            $row['product_id']
        );
        if ($exists) {
            continue;
        }

        $integrationSource = 'manual';
        $saleDate = date('Y-m-d H:i:s', strtotime('-' . (int)$row['days_ago'] . ' days'));
        $stmt = $db->prepare(
            "INSERT INTO pos_sales_usage
                (integration_source, sale_reference, product_id, store_id, quantity_sold, consumed_quantity, revenue, cogs, sale_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssiidddds',
            $integrationSource,
            $row['sale_reference'],
            $row['product_id'],
            $storeId,
            $row['qty'],
            $row['consumed'],
            $row['revenue'],
            $row['cogs'],
            $saleDate
        );
        $stmt->execute();
    }

    $db->commit();

    $summaryChecks = [
        'Stock rows' => "SELECT COUNT(*) FROM stock",
        'Stock transactions' => "SELECT COUNT(*) FROM stock_transactions",
        'GRN rows' => "SELECT COUNT(*) FROM grn",
        'Stock issues' => "SELECT COUNT(*) FROM stock_issues",
        'Variance adjustments' => "SELECT COUNT(*) FROM stock_adjustments WHERE adjustment_reason = 'count_variance'",
        'POS usage rows' => "SELECT COUNT(*) FROM pos_sales_usage",
        'Departments with monthly budget' => "SELECT COUNT(*) FROM departments WHERE monthly_budget > 0",
    ];

    echo "Financial demo seed completed successfully." . PHP_EOL;
    foreach ($summaryChecks as $label => $sql) {
        $count = (int)scalar($db, $sql);
        echo "- " . $label . ': ' . $count . PHP_EOL;
    }
    echo PHP_EOL;
    echo "Run reports at: " . SITE_URL . "pages/reports/index.php" . PHP_EOL;
} catch (Throwable $e) {
    $db->rollback();
    fwrite(STDERR, 'Financial demo seed failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
