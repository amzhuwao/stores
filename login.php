<?php
require_once __DIR__ . '/app/bootstrap.php';

// Development-only: show PHP errors directly on this page.

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    $result = $auth->login($_POST['username'] ?? '', $_POST['password'] ?? '');

    if (!$result['success']) {
        $loginError = $result['message'];
    } else {
        header('Location: ' . SITE_URL . 'dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
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
            max-width: 400px;
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 12px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            color: white;
        }
        .alert {
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🏨 <?php echo APP_NAME; ?></h1>
                <p>Hotel Inventory Management System</p>
            </div>

            <?php
            $flash = getFlashMessage();
            if ($flash) {
                $alertClass = 'alert-' . ($flash['type'] === 'error' ? 'danger' : $flash['type']);
                echo '<div class="alert ' . $alertClass . '" role="alert">';
                echo htmlspecialchars($flash['message']);
                echo '</div>';
            }
            ?>

            <?php if (!empty($loginError)): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-login">Login</button>
            </form>

            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>pages/forgot-password.php">Forgot password?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

