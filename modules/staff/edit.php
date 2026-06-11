<?php
require_once __DIR__ . '/../../includes/auth_check.php';
if ($_SESSION['role_id'] != ROLE_ADMIN) { redirect(getBaseUrl() . '/dashboard.php'); }

$page_title = t('edit_staff');
$active_page = 'staff';
$baseUrl = getBaseUrl();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$id]);
$staffMember = $stmt->fetch();
if (!$staffMember) { setFlash('error', t('no_data')); redirect($baseUrl . '/modules/staff/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? ROLE_STAFF);
    $password = $_POST['password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if ($password && $password !== $confirm_pw) {
        setFlash('error', t('password_mismatch'));
    } else {
        try {
            if ($password) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE staff SET first_name=?, last_name=?, email=?, phone=?, role_id=?, password=? WHERE id=?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $role_id, $hashed, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE staff SET first_name=?, last_name=?, email=?, phone=?, role_id=? WHERE id=?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $role_id, $id]);
            }
            logActivity($pdo, $_SESSION['user_id'], 'update', 'staff', "Updated staff ID $id");
            setFlash('success', t('staff_updated'));
            redirect($baseUrl . '/modules/staff/index.php');
        } catch (Exception $e) {
            setFlash('error', t('operation_failed'));
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-gear"></i> <?php echo t('edit_staff'); ?> - <?php echo htmlspecialchars($staffMember['first_name'] . ' ' . $staffMember['last_name']); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/staff/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
</div>

<div class="card"><div class="card-body">
<form id="staffForm" method="POST" action="">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label"><?php echo t('first_name'); ?> *</label>
            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($staffMember['first_name']); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('last_name'); ?> *</label>
            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($staffMember['last_name']); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('username'); ?></label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($staffMember['username']); ?>" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('email'); ?></label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staffMember['email']); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('phone'); ?></label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($staffMember['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('role'); ?> *</label>
            <select name="role_id" class="form-select" required>
                <option value="1" <?php echo $staffMember['role_id']==1?'selected':''; ?>><?php echo t('admin_role'); ?></option>
                <option value="2" <?php echo $staffMember['role_id']==2?'selected':''; ?>><?php echo t('staff_role'); ?></option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('new_password'); ?> <small class="text-muted">(leave blank to keep current)</small></label>
            <input type="password" name="password" id="password" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?php echo t('confirm_password'); ?></label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo t('update'); ?></button>
            <a href="<?php echo $baseUrl; ?>/modules/staff/index.php" class="btn btn-outline-secondary"><?php echo t('cancel'); ?></a>
        </div>
    </div>
</form>
</div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
