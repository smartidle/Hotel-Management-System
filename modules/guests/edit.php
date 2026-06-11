<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('edit_guest');
$active_page = 'guests';
$baseUrl = getBaseUrl();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM guests WHERE id = ?");
$stmt->execute([$id]);
$guest = $stmt->fetch();

if (!$guest) {
    setFlash('error', t('no_data'));
    redirect($baseUrl . '/modules/guests/index.php');
}

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
            $stmt = $pdo->prepare("UPDATE guests SET first_name=?, last_name=?, email=?, phone=?, id_type=?, id_number=?, nationality=?, address=?, city=?, country=?, zip_code=?, notes=?, vip_status=? WHERE id=?");
            $stmt->execute([$first_name, $last_name, $email, $phone, $id_type, $id_number, $nationality, $address, $city, $country, $zip_code, $notes, $vip, $id]);
            logActivity($pdo, $_SESSION['user_id'], 'update', 'guests', "Updated guest $first_name $last_name");
            setFlash('success', t('guest_updated'));
            redirect($baseUrl . '/modules/guests/index.php');
        } catch (Exception $e) {
            setFlash('error', t('operation_failed'));
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-pencil-square"></i> <?php echo t('edit_guest'); ?> - <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/guests/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
</div>

<div class="card"><div class="card-body">
<form method="POST" action="">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label"><?php echo t('first_name'); ?> *</label>
            <input type="text" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($guest['first_name']); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('last_name'); ?> *</label>
            <input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($guest['last_name']); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('phone'); ?> *</label>
            <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($guest['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('email'); ?></label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($guest['email'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('id_type'); ?></label>
            <select name="id_type" class="form-select">
                <option value="">-- Select --</option>
                <option value="Passport" <?php echo ($guest['id_type'] ?? '') === 'Passport' ? 'selected' : ''; ?>>Passport</option>
                <option value="Driver License" <?php echo ($guest['id_type'] ?? '') === 'Driver License' ? 'selected' : ''; ?>>Driver License</option>
                <option value="National ID" <?php echo ($guest['id_type'] ?? '') === 'National ID' ? 'selected' : ''; ?>>National ID</option>
                <option value="Other" <?php echo ($guest['id_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('id_number'); ?></label>
            <input type="text" name="id_number" class="form-control" value="<?php echo htmlspecialchars($guest['id_number'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('nationality'); ?></label>
            <input type="text" name="nationality" class="form-control" value="<?php echo htmlspecialchars($guest['nationality'] ?? ''); ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label"><?php echo t('address'); ?></label>
            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($guest['address'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('city'); ?></label>
            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($guest['city'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('country'); ?></label>
            <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($guest['country'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label"><?php echo t('zip_code'); ?></label>
            <input type="text" name="zip_code" class="form-control" value="<?php echo htmlspecialchars($guest['zip_code'] ?? ''); ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="vip_status" value="1" class="form-check-input" id="vip" <?php echo !empty($guest['vip_status']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="vip"><i class="bi bi-star-fill text-warning"></i> <?php echo t('vip_status'); ?></label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label"><?php echo t('notes'); ?></label>
            <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($guest['notes'] ?? ''); ?></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo t('save'); ?></button>
            <a href="<?php echo $baseUrl; ?>/modules/guests/index.php" class="btn btn-outline-secondary"><?php echo t('cancel'); ?></a>
        </div>
    </div>
</form>
</div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
