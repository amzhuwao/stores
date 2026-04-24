<?php
require_once __DIR__ . '/../app/bootstrap.php';

$auth = new Auth();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');

        if ($identifier === '') {
            $message = 'Please enter your username or email address.';
        } else {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT user_id, email, username FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                $tokenResult = $auth->createPasswordResetToken((int)$user['user_id']);

                if ($tokenResult['success']) {
                    $resetLink = SITE_URL . 'pages/reset-password.php?token=' . urlencode($tokenResult['token']);
                    $displayName = $user['username'] ?: ($user['email'] ?: 'User');
                    $subject = APP_NAME . ' Password Reset Request';
                    $htmlBody = '<p>Hello ' . htmlspecialchars($displayName) . ',</p>'
                        . '<p>We received a request to reset your password. Use the link below to choose a new password:</p>'
                        . '<p><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>'
                        . '<p>This link expires in 60 minutes. If you did not request this reset, you can ignore this email.</p>';
                    $textBody = "Hello {$displayName},\n\nWe received a request to reset your password. Use the link below to choose a new password:\n{$resetLink}\n\nThis link expires in 60 minutes. If you did not request this reset, you can ignore this email.";

                    $mailResult = Mailer::send($user['email'], $subject, $htmlBody, $textBody);
                    if (!$mailResult['success']) {
                        error_log('Password reset email failed for user_id ' . $user['user_id'] . ': ' . $mailResult['message']);
                    }
                }
            }

            $message = 'If an active account matches that identifier, a reset link has been sent to the registered email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Forgot Password</title>
    <script>window.APP_BASE_URL = <?php echo json_encode(SITE_URL); ?>;</script>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(Security::generateCSRFToken()); ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars(APP_NAME); ?>">
    <link rel="manifest" href="<?php echo SITE_URL; ?>public/manifest.json">
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
                <p>Request a password reset link</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert alert-info" role="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <?php echo getCSRFTokenField(); ?>
                <div class="form-group mb-3">
                    <label for="identifier" class="form-label">Username or Email</label>
                    <input type="text" class="form-control" id="identifier" name="identifier" placeholder="Enter your username or email" required>
                </div>

                <button type="submit" class="btn btn-login w-100">Generate Reset Link</button>
            </form>

            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>login.php">Back to login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>