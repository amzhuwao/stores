<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new CategoryController();
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = $controller->getById($categoryId);

if (!$category) {
    $_SESSION['flash_message'] = 'Category not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . SITE_URL . 'pages/categories/index.php');
    exit;
}

$error = '';
$formData = [
    'category_name' => $category['category_name'],
    'description' => $category['description'] ?? '',
    'status' => $category['status']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'category_name' => $_POST['category_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];

        $result = $controller->update($categoryId, $formData);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/categories/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Edit Category';
$activePage = 'categories';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Edit Category</h1>
        <p>Update category details and status.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/categories/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Categories
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-edit"></i> Category Details</h5>
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
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($formData['description']); ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Category</button>
                <a href="<?php echo SITE_URL; ?>pages/categories/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>