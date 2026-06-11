<?php
/**
 * Top Navbar
 */
global $LANG;
$baseUrl = getBaseUrl();
$page_title = $page_title ?? t('dashboard');
$current_lang = getCurrentLang();
?>
<!-- Top Navbar -->
<nav class="top-navbar navbar navbar-expand-lg">
    <div class="container-fluid">
        <!-- Sidebar Toggle (mobile) -->
        <button class="btn btn-link sidebar-toggle" id="sidebarToggle" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Page Title -->
        <span class="navbar-page-title"><?php echo $page_title; ?></span>

        <!-- Right Side -->
        <div class="d-flex align-items-center gap-2">
            <!-- Language Switcher -->
            <form method="POST" action="<?php echo $baseUrl; ?>/api/language.php" class="me-2">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-globe2"></i>
                        <span class="d-none d-md-inline"><?php echo $current_lang === 'fil' ? 'Filipino' : 'English'; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button type="submit" name="lang" value="en" class="dropdown-item <?php echo $current_lang === 'en' ? 'active' : ''; ?>">
                                🇬🇧 English
                            </button>
                        </li>
                        <li>
                            <button type="submit" name="lang" value="fil" class="dropdown-item <?php echo $current_lang === 'fil' ? 'active' : ''; ?>">
                                🇵🇭 Filipino
                            </button>
                        </li>
                    </ul>
                </div>
            </form>

            <!-- User Info -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted small"><?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>/logout.php">
                            <i class="bi bi-box-arrow-left"></i> <?php echo t('nav_logout'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
