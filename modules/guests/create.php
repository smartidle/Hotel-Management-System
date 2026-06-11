<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('add_guest');
$active_page = 'guests';
$baseUrl = getBaseUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $id_type = sanitize($_POST['id_type'] ?? '');
    $id_number = sanitize($_POST['id_number'] ?? '');
    $nationality = sanitize($_POST['nationality'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $zip_code = sanitize($_POST['zip_code'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $vip = isset($_POST['vip_status']) ? 1 : 0;

    if (empty($first_name) || empty($last_name) || empty($phone)) {
        setFlash('error', t('required_field'));
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, id_type, id_number, nationality, address, city, country, zip_code, notes, vip_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$first_name, $last_name, $email, $phone, $id_type, $id_number, $nationality, $address, $city, $country, $zip_code, $notes, $vip]);
            logActivity($pdo, $_SESSION['user_id'], 'create', 'guests', "Created guest $first_name $last_name");
            setFlash('success', t('guest_created'));
            redirect($baseUrl . '/modules/guests/index.php');
        } catch (Exception $e) {
            setFlash('error', t('operation_failed'));
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-plus"></i> <?php echo t('add_guest'); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/guests/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
</div>

<div class="card"><div class="card-body">
<form method="POST" action="">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label"><?php echo t('first_name'); ?> *</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('last_name'); ?> *</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('phone'); ?> *</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('email'); ?></label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('id_type'); ?></label>
            <select name="id_type" class="form-select">
                <option value="">-- Select --</option>
                <option value="Passport">Passport</option>
                <option value="Driver License">Driver License</option>
                <option value="National ID">National ID</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('id_number'); ?></label>
            <input type="text" name="id_number" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('nationality'); ?></label>
            <input type="text" name="nationality" class="form-control">
        </div>
        <div class="col-md-8">
            <label class="form-label"><?php echo t('address'); ?></label>
            <input type="text" name="address" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('city'); ?></label>
            <input type="text" name="city" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('country'); ?></label>
            <input type="text" name="country" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label"><?php echo t('zip_code'); ?></label>
            <input type="text" name="zip_code" class="form-control">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="vip_status" value="1" class="form-check-input" id="vip">
                <label class="form-check-label" for="vip"><i class="bi bi-star-fill text-warning"></i> <?php echo t('vip_status'); ?></label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label"><?php echo t('notes'); ?></label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo t('save'); ?></button>
            <a href="<?php echo $baseUrl; ?>/modules/guests/index.php" class="btn btn-outline-secondary"><?php echo t('cancel'); ?></a>
        </div>
    </div>
</form>
</div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
