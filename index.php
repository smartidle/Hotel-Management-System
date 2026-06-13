<?php
/**
 * Login Page
 */
require_once __DIR__ . '/includes/session_init.php';
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

$LANG = loadLanguage();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = t('login_error');
    } else {
        $stmt = $pdo->prepare("SELECT s.*, r.role_name FROM staff s JOIN roles r ON s.role_id = r.id WHERE s.username = ? AND s.status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['lang'] = $_SESSION['lang'] ?? DEFAULT_LANG;

            // Update last login
            $stmt = $pdo->prepare("UPDATE staff SET last_login = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $user['id']]);

            // Log activity
            logActivity($pdo, $user['id'], 'login', 'auth', 'User logged in');

            // Regenerate session ID for security
            session_regenerate_id(true);

            header('Location: dashboard.php');
            exit();
        } else {
            $error = t('login_error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login'); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="bi bi-building"></i> <?php echo APP_NAME; ?></h2>
                <p><?php echo t('login_subtitle'); ?></p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label"><?php echo t('username'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username"
                                   placeholder="<?php echo t('username'); ?>" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label"><?php echo t('password'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="<?php echo t('password'); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> <?php echo t('login'); ?>
                    </button>
                </form>

                <!-- Language Switch -->
                <div class="text-center mt-3">
                    <form method="POST" action="api/language.php" class="d-inline">
                        <input type="hidden" name="lang" value="<?php echo getCurrentLang() === 'en' ? 'fil' : 'en'; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-globe2"></i>
                            <?php echo getCurrentLang() === 'en' ? 'Filipino' : 'English'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
