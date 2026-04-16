<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new CategoryController();

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => $_GET['status'] ?? 'active'
];

$categories = $controller->getCategories($filters);
$stats = $controller->getStats();

$pageTitle = 'Categories';
$activePage = 'categories';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Categories</h1>
        <p>Review product categories and their product coverage.</p>
    </div>
    <?php if (can('categories.create')): ?>
        <a href="<?php echo SITE_URL; ?>pages/categories/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Category
        </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Categories</div>
            <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-label">Active</div>
            <div class="stat-value"><?php echo (int)$stats['active']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-label">Inactive</div>
            <div class="stat-value"><?php echo (int)$stats['inactive']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">With Products</div>
            <div class="stat-value"><?php echo (int)$stats['with_products']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-filter"></i> Search & Filters</h5></div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Category name or description" value="<?php echo htmlspecialchars($filters['q']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-outline-primary" href="<?php echo SITE_URL; ?>pages/categories/index.php">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-tags"></i> Category List</h5></div>
    <div class="card-body">
        <?php if (!empty($categories)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Active Products</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($category['description'] ?: '-'); ?></td>
                                <td><?php echo (int)$category['products_count']; ?></td>
                                <td><?php echo (int)$category['active_products']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $category['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($category['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (can('categories.edit')): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>pages/categories/edit.php?id=<?php echo (int)$category['category_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">No categories found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>