<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    <script>window.APP_BASE_URL = <?php echo json_encode(SITE_URL); ?>;</script>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(Security::generateCSRFToken()); ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars(APP_NAME); ?>">
    <link rel="manifest" href="<?php echo SITE_URL; ?>public/manifest.php">
    <link rel="icon" href="<?php echo SITE_URL; ?>public/img/pwa-icon-192.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>public/img/pwa-icon-192.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo SITE_URL; ?>public/css/style.css" rel="stylesheet">
    <script src="<?php echo SITE_URL; ?>public/js/pwa.js" defer></script>
</head>
<body>
    <?php
    $breadcrumbItems = [
        [
            'label' => 'Home',
            'url' => SITE_URL . 'dashboard.php',
        ],
    ];

    if (!isset($activePage) || $activePage !== 'dashboard') {
        $breadcrumbItems[] = [
            'label' => $pageTitle ?? APP_NAME,
            'url' => null,
        ];
    }

    $canDashboard = can('dashboard.view');

    $canProducts = can('products.view');
    $canStock = can('stock.view');

    $canGrn = can('grn.view');
    $canRequisition = can('requisition.view');
    $canIssues = can('stock-issues.view');
    $canAdjustments = can('adjustments.view');

    $canStores = can('stores.view');
    $canSuppliers = can('suppliers.view');
    $canDepartments = can('departments.view');
    $canCategories = can('categories.view');

    $canReports = can('reports.view');
    $canAudit = can('audit.view');

    $canUsers = can('users.view');
    $canSettings = can('settings.view');
    $canBackupSettings = can('settings.backup');

    $hasInventory = $canProducts || $canStock;
    $hasTransactions = $canGrn || $canRequisition || $canIssues || $canAdjustments;
    $hasConfiguration = $canStores || $canSuppliers || $canDepartments || $canCategories;
    $hasReporting = $canReports || $canAudit;
    $hasAdmin = $canUsers || $canSettings || $canBackupSettings;
    ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4>🏨 Manica Skyview Stores</h4>
            <p>Inventory Management System</p>
        </div>

        <nav class="nav flex-column">
            <?php include __DIR__ . '/partials/sidebar-nav.php'; ?>
        </nav>
    </div>

    <div class="mobile-nav-overlay" data-mobile-nav-close></div>

    <aside class="mobile-nav-drawer" id="mobileNavDrawer" aria-hidden="true">
        <div class="mobile-nav-header">
            <div>
                <h4>Navigation</h4>
                <p>Tap a section to open it</p>
            </div>
            <button type="button" class="mobile-nav-close" data-mobile-nav-close aria-label="Close navigation">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="nav flex-column mobile-nav-links">
            <?php include __DIR__ . '/partials/sidebar-nav.php'; ?>
        </nav>
    </aside>

    <!-- Top Navigation Bar -->
    <div class="topbar">
        <button type="button" class="mobile-nav-toggle" data-mobile-nav-toggle aria-label="Open navigation">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-title desktop-title">
            <?php echo $pageTitle ?? APP_NAME; ?>
        </div>

        <nav class="mobile-breadcrumb" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbItems as $index => $item): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">/</span>
                <?php endif; ?>

                <?php if (!empty($item['url'])): ?>
                    <a href="<?php echo $item['url']; ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                <?php else: ?>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($item['label']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="topbar-right">
            <div class="user-profile dropdown">
                <div class="user-avatar dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php 
                    $user = getCurrentUser();
                    if ($user) {
                        echo strtoupper(substr($user['full_name'], 0, 1));
                    }
                    ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <h6 class="dropdown-header">
                            <?php echo $user['full_name'] ?? 'User'; ?>
                        </h6>
                    </li>
                    <li>
                        <p style="padding: 0.5rem 1rem; margin: 0; font-size: 12px; color: #666;">
                            <?php echo $user['role_name'] ?? 'User'; ?>
                        </p>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/change-password.php"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Flash Messages -->
        <?php 
        $flash = getFlashMessage();
        if ($flash) {
            $alertClass = 'alert-' . ($flash['type'] === 'error' ? 'danger' : $flash['type']);
            echo '<div class="alert ' . $alertClass . '" role="alert">';
            echo htmlspecialchars($flash['message']);
            echo '</div>';
        }
        ?>

        <!-- Content -->
        <?php
