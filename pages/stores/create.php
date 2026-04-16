<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new StoreController();
$error = '';
$formData = [
    'store_name' => '',
    'store_code' => '',
    'location' => '',
    'responsible_user_id' => '',
    'description' => ''
];

$responsibleUsers = $controller->getResponsibleUsers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'store_name' => $_POST['store_name'] ?? '',
            'store_code' => $_POST['store_code'] ?? '',
            'location' => $_POST['location'] ?? '',
            'responsible_user_id' => $_POST['responsible_user_id'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];

        $result = $controller->create($formData);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/stores/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Add Store';
$activePage = 'stores';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Add Store</h1>
        <p>Create a new physical store location.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/stores/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Stores
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-building"></i> Store Details</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?php echo getCSRFTokenField(); ?>
            <div class="col-md-6">
                <label class="form-label">Store Name</label>
                <input type="text" name="store_name" class="form-control" required value="<?php echo htmlspecialchars($formData['store_name']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Store Code</label>
                <input type="text" name="store_code" class="form-control" required maxlength="20" value="<?php echo htmlspecialchars($formData['store_code']); ?>">
                <small class="text-muted">Use code like KIT001, BAR001, MNT001.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($formData['location']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Responsible User (Optional)</label>
                <select name="responsible_user_id" class="form-control">
                    <option value="">Not Assigned</option>
                    <?php foreach ($responsibleUsers as $user): ?>
                        <option value="<?php echo (int)$user['user_id']; ?>" <?php echo ((string)$formData['responsible_user_id'] === (string)$user['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($formData['description']); ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Store</button>
                <a href="<?php echo SITE_URL; ?>pages/stores/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>