<?php if ($canDashboard): ?>
    <div class="nav-section-title">Main</div>
    <a class="nav-link <?php echo (isset($activePage) && $activePage === 'dashboard') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>dashboard.php">
        <i class="fas fa-home"></i> Dashboard
    </a>
<?php endif; ?>

<?php if ($hasInventory): ?>
    <div class="nav-section-title">Inventory</div>
    <?php if ($canProducts): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'products') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/products/index.php">
            <i class="fas fa-box"></i> Products
        </a>
    <?php endif; ?>
    <?php if ($canStock): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'stock') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/stock/view.php">
            <i class="fas fa-warehouse"></i> Stock Levels
        </a>
    <?php endif; ?>
<?php endif; ?>

<?php if ($hasTransactions): ?>
    <div class="nav-section-title">Transactions</div>
    <?php if ($canGrn): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'grn') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/grn/index.php">
            <i class="fas fa-file-import"></i> GRN
        </a>
    <?php endif; ?>
    <?php if ($canRequisition): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'requisition') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/requisition/index.php">
            <i class="fas fa-list"></i> Requisitions
        </a>
    <?php endif; ?>
    <?php if ($canIssues): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'issues') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/stock-issues/index.php">
            <i class="fas fa-arrow-right"></i> Stock Issues
        </a>
    <?php endif; ?>
    <?php if ($canAdjustments): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'adjustments') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/adjustments/index.php">
            <i class="fas fa-sync"></i> Adjustments
        </a>
    <?php endif; ?>
<?php endif; ?>

<?php if ($hasConfiguration): ?>
    <div class="nav-section-title">Configuration</div>
    <?php if ($canStores): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'stores') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/stores/index.php">
            <i class="fas fa-building"></i> Stores
        </a>
    <?php endif; ?>
    <?php if ($canSuppliers): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'suppliers') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/suppliers/index.php">
            <i class="fas fa-truck"></i> Suppliers
        </a>
    <?php endif; ?>
    <?php if ($canDepartments): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'departments') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/departments/index.php">
            <i class="fas fa-sitemap"></i> Departments
        </a>
    <?php endif; ?>
    <?php if ($canCategories): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'categories') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/categories/index.php">
            <i class="fas fa-tags"></i> Categories
        </a>
    <?php endif; ?>
<?php endif; ?>

<?php if ($hasReporting): ?>
    <div class="nav-section-title">Reporting</div>
    <?php if ($canReports): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'reports') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/reports/index.php">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
    <?php endif; ?>
    <?php if ($canAudit): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'audit') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/audit/index.php">
            <i class="fas fa-history"></i> Audit Log
        </a>
    <?php endif; ?>
<?php endif; ?>

<?php if ($hasAdmin): ?>
    <div class="nav-section-title">Admin</div>
    <?php if ($canUsers): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'users') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/users/index.php">
            <i class="fas fa-users"></i> Users
        </a>
    <?php endif; ?>
    <?php if ($canSettings): ?>
        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'settings') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>pages/settings/index.php">
            <i class="fas fa-cogs"></i> Settings
        </a>
    <?php endif; ?>
<?php endif; ?>
