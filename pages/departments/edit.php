<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new DepartmentController();
$departmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$department = $controller->getById($departmentId);

if (!$department) {
    $_SESSION['flash_message'] = 'Department not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . SITE_URL . 'pages/departments/index.php');
    exit;
}

$error = '';
$formData = [
    'dept_name' => $department['dept_name'],
    'dept_code' => $department['dept_code'],
    'head_user_id' => (string)($department['head_user_id'] ?? ''),
    'monthly_budget' => (string)number_format((float)($department['monthly_budget'] ?? 0), 2, '.', ''),
    'weekly_budget' => (string)number_format((float)($department['weekly_budget'] ?? 0), 2, '.', ''),
    'status' => $department['status']
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
            'weekly_budget' => $_POST['weekly_budget'] ?? '0.00',
            'status' => $_POST['status'] ?? 'active'
        ];

        $result = $controller->update($departmentId, $formData);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/departments/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Edit Department';
$activePage = 'departments';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Edit Department</h1>
        <p>Update department details, owner, and status.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/departments/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Departments
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-edit"></i> Department Details</h5>
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
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Department</button>
                <a href="<?php echo SITE_URL; ?>pages/departments/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>