<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new DepartmentController();
$error = '';
$formData = [
    'dept_name' => '',
    'dept_code' => '',
    'head_user_id' => '',
    'monthly_budget' => '0.00',
    'weekly_budget' => '0.00'
];

$heads = $controller->getHeads();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'dept_name' => $_POST['dept_name'] ?? '',
            'dept_code' => $_POST['dept_code'] ?? '',
            'head_user_id' => $_POST['head_user_id'] ?? '',
            'monthly_budget' => $_POST['monthly_budget'] ?? '0.00',
            'weekly_budget' => $_POST['weekly_budget'] ?? '0.00'
        ];

        $result = $controller->create($formData);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/departments/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Add Department';
$activePage = 'departments';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Add Department</h1>
        <p>Create a new department for requisition and issue workflows.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/departments/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Departments
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-sitemap"></i> Department Details</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?php echo getCSRFTokenField(); ?>
            <div class="col-md-6">
                <label class="form-label">Department Name</label>
                <input type="text" name="dept_name" class="form-control" required value="<?php echo htmlspecialchars($formData['dept_name']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Department Code</label>
                <input type="text" name="dept_code" class="form-control" required maxlength="20" value="<?php echo htmlspecialchars($formData['dept_code']); ?>">
                <small class="text-muted">Use short code like KIT, BAR, HSK.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Department Head (Optional)</label>
                <select name="head_user_id" class="form-control">
                    <option value="">Not Assigned</option>
                    <?php foreach ($heads as $head): ?>
                        <option value="<?php echo (int)$head['user_id']; ?>" <?php echo ((string)$formData['head_user_id'] === (string)$head['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($head['full_name'] . ' (' . $head['username'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Monthly Budget</label>
                <input type="number" step="0.01" min="0" name="monthly_budget" class="form-control" value="<?php echo htmlspecialchars($formData['monthly_budget']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Weekly Budget</label>
                <input type="number" step="0.01" min="0" name="weekly_budget" class="form-control" value="<?php echo htmlspecialchars($formData['weekly_budget']); ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Department</button>
                <a href="<?php echo SITE_URL; ?>pages/departments/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>