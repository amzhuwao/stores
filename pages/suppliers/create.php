<?php
require_once __DIR__ . '/../../app/bootstrap.php';


if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$controller = new SupplierController();
$error = '';
$formData = [
    'supplier_name' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'postal_code' => '',
    'payment_terms' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData = [
            'supplier_name' => $_POST['supplier_name'] ?? '',
            'contact_person' => $_POST['contact_person'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'city' => $_POST['city'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'payment_terms' => $_POST['payment_terms'] ?? ''
        ];

        $result = $controller->create($formData);
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . 'pages/suppliers/index.php');
            exit;
        }

        $error = $result['message'];
    }
}

$pageTitle = 'Add Supplier';
$activePage = 'suppliers';
include __DIR__ . '/../../app/views/layout-header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Add Supplier</h1>
        <p>Create a new supplier record for GRN and procurement workflows.</p>
    </div>
    <a href="<?php echo SITE_URL; ?>pages/suppliers/index.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to Suppliers
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-truck"></i> Supplier Details</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <?php echo getCSRFTokenField(); ?>
            <div class="col-md-6">
                <label class="form-label">Supplier Name</label>
                <input type="text" name="supplier_name" class="form-control" required value="<?php echo htmlspecialchars($formData['supplier_name']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($formData['contact_person']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone']); ?>">
            </div>
            <div class="col-md-12">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($formData['address']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($formData['city']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Postal Code</label>
                <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($formData['postal_code']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Terms</label>
                <input type="text" name="payment_terms" class="form-control" value="<?php echo htmlspecialchars($formData['payment_terms']); ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Supplier</button>
                <a href="<?php echo SITE_URL; ?>pages/suppliers/index.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout-footer.php'; ?>
