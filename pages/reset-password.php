<?php
require_once __DIR__ . '/../app/bootstrap.php';

$auth = new Auth();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$tokenRow = $token !== '' ? $auth->getPasswordResetToken($token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif ($token === '' || !$tokenRow) {
        $error = 'This reset link is invalid or has expired.';
    } else {
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if ($newPassword === '' || $confirmPassword === '') {
            $error = 'All password fields are required.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match.';
        } else {
            $result = $auth->resetPasswordWithToken($token, $newPassword);

            if ($result['success']) {
                $_SESSION['flash_message'] = 'Password updated successfully. You can log in now.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . SITE_URL . 'login.php');
                exit;
            }

            $error = $result['message'] ?? 'Unable to reset the password right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Reset Password</title>
    <script>window.APP_BASE_URL = <?php echo json_encode(SITE_URL); ?>;</script>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(Security::generateCSRFToken()); ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars(APP_NAME); ?>">
    <link rel="manifest" href="<?php echo SITE_URL; ?>public/manifest.php">
    <link rel="icon" href="<?php echo SITE_URL; ?>public/img/pwa-icon-192.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>public/img/pwa-icon-192.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>public/css/style.css" rel="stylesheet">
    <script src="<?php echo SITE_URL; ?>public/js/pwa.js" defer></script>
    <style>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-box {
            width: 100%;
            max-width: 440px;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🏨 <?php echo APP_NAME; ?></h1>
                <p>Set a new password for your account</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!$tokenRow && $error === ''): ?>
                <div class="alert alert-warning" role="alert">
                    This reset link is invalid or has expired.
                </div>
            <?php endif; ?>

            <?php if ($tokenRow): ?>
                <div class="alert alert-info" role="alert">
                    Resetting password for <strong><?php echo htmlspecialchars($tokenRow['full_name']); ?></strong>.
                </div>

                <form method="POST" autocomplete="off">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn-login w-100">Reset Password</button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>login.php">Back to login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>