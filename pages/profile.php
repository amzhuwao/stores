<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$pageTitle = 'My Profile';
$activePage = 'profile';
$user = getCurrentUser();

include __DIR__ . '/../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1>My Profile</h1>
        <p>Account details for the currently signed-in user.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/change-password.php" class="btn btn-primary">Change Password</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="user-avatar" style="width: 72px; height: 72px; font-size: 28px;">
                        <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div>
                        <h3 class="mb-1"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h3>
                        <div class="text-muted"><?php echo htmlspecialchars($user['role_name'] ?? 'User'); ?></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role_name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user['status'] ?? '')); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Created</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['created_at'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Updated</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['updated_at'] ?? 'N/A'); ?>" readonly>
                    </div>
                </div>

                <div class="alert alert-info mt-4 mb-0">
                    Profile editing is intentionally limited in this release. Use the password screen to rotate credentials when needed.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layout-footer.php'; ?>