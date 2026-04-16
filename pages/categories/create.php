<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new CategoryController();
$error = '';
$formData = [
    'category_name' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'category_name' => $_POST['category_name'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];

        $result = $controller->create($formData);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/categories/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Add Category';
$activePage = 'categories';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Add Category</h1>
        <p>Create a new product category for inventory grouping.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/categories/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Categories
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-tags"></i> Category Details</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?php echo getCSRFTokenField(); ?>
            <div class="col-md-8">
                <label class="form-label">Category Name</label>
                <input type="text" name="category_name" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($formData['category_name']); ?>">
            </div>
            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($formData['description']); ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Category</button>
                <a href="<?php echo SITE_URL; ?>pages/categories/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>