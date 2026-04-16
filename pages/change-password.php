<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'All password fields are required.';
    } elseif (!Auth::verifyPassword($currentPassword, $user['password'] ?? '')) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirmation do not match.';
    } else {
        if ($auth->updatePassword((int)$user['user_id'], $newPassword)) {
            $_SESSION['flash_message'] = 'Password updated successfully.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/profile.php');
            exit;
        }

        $error = 'Unable to update the password right now.';
    }
}

$pageTitle = 'Change Password';
$activePage = 'profile';
include __DIR__ . '/../app/views/layout-header.php';
?>

<div class="page-header">
    <h1>Change Password</h1>
    <p>Rotate the password for <?php echo htmlspecialchars($user['full_name'] ?? 'your account'); ?>.</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                        <a href="<?php echo SITE_URL; ?>pages/profile.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layout-footer.php'; ?>