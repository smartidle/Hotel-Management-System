<?php
/**
 * HTML Header
 * Variables expected: $page_title, $active_page
 */
global $LANG;
$page_title = $page_title ?? t('app_name');
$active_page = $active_page ?? 'dashboard';
$current_lang = getCurrentLang();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <meta name="baseUrl" content="<?php echo getBaseUrl(); ?>">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo getBaseUrl(); ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Core AJAX Navigation (must load before sidebar) -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/ajax-nav.js"></script>
    <!-- Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
