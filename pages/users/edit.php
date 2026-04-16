<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new UserController();
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userData = $controller->getById($userId);

if (!$userData) {
    $_SESSION['flash_message'] = 'User not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . SITE_URL . 'pages/users/index.php');
    exit;
}

$error = '';
$formData = [
    'username' => $userData['username'],
    'email' => $userData['email'],
    'full_name' => $userData['full_name'],
    'role_id' => (string)$userData['role_id'],
    'status' => $userData['status']
];

$roles = $controller->getRoles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'full_name' => $_POST['full_name'] ?? '',
            'role_id' => $_POST['role_id'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];

        $payload = $formData;
        $payload['password'] = $_POST['password'] ?? '';

        $result = $controller->update($userId, $payload);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/users/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Edit User';
$activePage = 'users';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Edit User</h1>
        <p>Update account details, role, status, and optionally reset password.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/users/index.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Back to Users</a>
</div>

<div class="card">
    <div class="card-header"><h5><i class="fas fa-user-edit"></i> User Details</h5></div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?php echo getCSRFTokenField(); ?>
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($formData['full_name']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($formData['username']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($formData['email']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">New Password (Optional)</label>
                <input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
            </div>
            <div class="col-md-6">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-control" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo (int)$role['role_id']; ?>" <?php echo ((string)$formData['role_id'] === (string)$role['role_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
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
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
                <a href="<?php echo SITE_URL; ?>pages/users/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>