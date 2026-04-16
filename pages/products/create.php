<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new ProductController();
$categories = $controller->getCategories();
$formData = [
    'product_name' => '',
    'product_code' => '',
    'category_id' => '',
    'unit_of_measure' => '',
    'reorder_level' => '0',
    'reorder_quantity' => '1'
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'product_name' => $_POST['product_name'] ?? '',
            'product_code' => $_POST['product_code'] ?? '',
            'category_id' => $_POST['category_id'] ?? '',
            'unit_of_measure' => $_POST['unit_of_measure'] ?? '',
            'reorder_level' => $_POST['reorder_level'] ?? '0',
            'reorder_quantity' => $_POST['reorder_quantity'] ?? '1'
        ];

        $result = $controller->create($formData);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/products/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Add Product';
$activePage = 'products';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Add Product</h1>
        <p>Create a new product and initialize stock records for active stores.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/products/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-plus"></i> Product Details</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?php echo getCSRFTokenField(); ?>

            <div class="col-md-6">
                <label class="form-label">Product Name</label>
                <input type="text" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($formData['product_name']); ?>" maxlength="100">
            </div>

            <div class="col-md-3">
                <label class="form-label">Product Code</label>
                <input type="text" name="product_code" class="form-control" required value="<?php echo htmlspecialchars($formData['product_code']); ?>" maxlength="50">
            </div>

            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['category_id']; ?>" <?php echo ((string)$formData['category_id'] === (string)$category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Unit of Measure</label>
                <input type="text" name="unit_of_measure" class="form-control" required value="<?php echo htmlspecialchars($formData['unit_of_measure']); ?>" placeholder="e.g., KG, Liters, Bottles">
            </div>

            <div class="col-md-4">
                <label class="form-label">Reorder Level</label>
                <input type="number" name="reorder_level" class="form-control" min="0" required value="<?php echo htmlspecialchars($formData['reorder_level']); ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Reorder Quantity</label>
                <input type="number" name="reorder_quantity" class="form-control" min="1" required value="<?php echo htmlspecialchars($formData['reorder_quantity']); ?>">
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Product
                </button>
                <a href="<?php echo SITE_URL; ?>pages/products/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
