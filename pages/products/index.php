<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new ProductController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $_SESSION['flash_message'] = 'Invalid security token. Please refresh and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . SITE_URL . 'pages/products/index.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'toggle_status') {
        $productId = $_POST['product_id'] ?? 0;
        $targetStatus = $_POST['target_status'] ?? 'inactive';
        $result = $controller->setStatus($productId, $targetStatus);

        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        header('Location: ' . SITE_URL . 'pages/products/index.php');
        exit;
    }
}

$filters = [
    'q' => $_GET['q'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'status' => $_GET['status'] ?? 'active'
];

$products = $controller->getProducts($filters);
$categories = $controller->getCategories();
$stats = $controller->getStats();

$pageTitle = 'Products Management';
$activePage = 'products';

include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Products</h1>
        <p>Manage product master data, codes, and reorder settings.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/products/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Product
    </a>
</div>

<div class="row">
    <div class="col-md-4 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Active Products</div>
            <div class="stat-value"><?php echo $stats['active']; ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="stat-card warning">
            <div class="stat-label">Inactive Products</div>
            <div class="stat-value"><?php echo $stats['inactive']; ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="stat-card success">
            <div class="stat-label">Categories</div>
            <div class="stat-value"><?php echo $stats['categories']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Search & Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Name or code">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['category_id']; ?>" <?php echo ((string)$filters['category_id'] === (string)$category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
                <a href="<?php echo SITE_URL; ?>pages/products/index.php" class="btn btn-outline-primary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-box"></i> Product List</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($products)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Reorder Level</th>
                            <th>Reorder Qty</th>
                            <th>Total Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['unit_of_measure']); ?></td>
                                <td><?php echo (int)$product['reorder_level']; ?></td>
                                <td><?php echo (int)$product['reorder_quantity']; ?></td>
                                <td><?php echo (int)$product['total_stock']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>pages/products/edit.php?id=<?php echo (int)$product['product_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display:inline;">
                                            <?php echo getCSRFTokenField(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                                            <input type="hidden" name="target_status" value="<?php echo $product['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $product['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                                <i class="fas <?php echo $product['status'] === 'active' ? 'fa-eye-slash' : 'fa-check'; ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No products found for the selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
