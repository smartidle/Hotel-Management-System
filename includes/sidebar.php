<?php
/**
 * Sidebar Navigation
 * Variable: $active_page
 */
global $LANG;
$active_page = $active_page ?? 'dashboard';
$baseUrl = getBaseUrl();
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo $baseUrl; ?>/dashboard.php">
            <img src="<?php echo $baseUrl; ?>/assets/img/logo.jpg" alt="Logo" class="sidebar-logo" id="sidebarLogo">
            <span class="sidebar-brand-text"><?php echo APP_NAME; ?></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <!-- MAIN -->
            <li class="nav-section-title"><?php echo t('nav_section_main'); ?></li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span><?php echo t('nav_dashboard'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'calendar' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/calendar/index.php">
                    <i class="bi bi-calendar3"></i>
                    <span><?php echo t('nav_calendar'); ?></span>
                </a>
            </li>

            <!-- ROOM MANAGEMENT -->
            <li class="nav-section-title"><?php echo t('nav_section_rooms'); ?></li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'rooms' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/rooms/index.php">
                    <i class="bi bi-door-open"></i>
                    <span><?php echo t('nav_rooms'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'roomtypes' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/roomtypes/index.php">
                    <i class="bi bi-tags"></i>
                    <span><?php echo t('nav_room_types'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'housekeeping' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/housekeeping/index.php">
                    <i class="bi bi-brush"></i>
                    <span><?php echo t('nav_housekeeping'); ?></span>
                </a>
            </li>

            <!-- BOOKING -->
            <li class="nav-section-title"><?php echo t('nav_section_booking'); ?></li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'reservations' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/reservations/index.php">
                    <i class="bi bi-calendar-check"></i>
                    <span><?php echo t('nav_reservations'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'guests' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/guests/index.php">
                    <i class="bi bi-people"></i>
                    <span><?php echo t('nav_guests'); ?></span>
                </a>
            </li>

            <!-- FRONT DESK -->
            <li class="nav-section-title"><?php echo t('nav_section_frontdesk'); ?></li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'checkin' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/checkinout/checkin.php">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span><?php echo t('nav_checkin'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'checkout' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/checkinout/checkout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span><?php echo t('nav_checkout'); ?></span>
                </a>
            </li>

            <!-- FINANCE -->
            <li class="nav-section-title"><?php echo t('nav_section_finance'); ?></li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'billing' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/billing/index.php">
                    <i class="bi bi-receipt"></i>
                    <span><?php echo t('nav_billing'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'reports' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/reports/index.php">
                    <i class="bi bi-bar-chart-line"></i>
                    <span><?php echo t('nav_reports'); ?></span>
                </a>
            </li>

            <!-- ADMINISTRATION -->
            <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN): ?>
            <li class="nav-section-title"><?php echo t('nav_section_admin'); ?></li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'staff' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/staff/index.php">
                    <i class="bi bi-person-badge"></i>
                    <span><?php echo t('nav_staff'); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page === 'settings' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/modules/settings/index.php">
                    <i class="bi bi-gear"></i>
                    <span><?php echo t('nav_settings'); ?></span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <i class="bi bi-person-circle"></i>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
        </div>
    </div>
</div>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- AJAX Navigation — inline script, no external dependency -->
<script>
(function() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    sidebar.addEventListener('click', function(e) {
        // Find the clicked nav-link
        var link = e.target.closest('a.nav-link');
        if (!link) return;

        var href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#' || href.indexOf('://') > 0) return;

        // Always prevent default navigation
        e.preventDefault();
        e.stopImmediatePropagation();

        // Update active state
        var allLinks = sidebar.querySelectorAll('.nav-link');
        for (var i = 0; i < allLinks.length; i++) allLinks[i].classList.remove('active');
        link.classList.add('active');

        // Save sidebar scroll position
        var savedScroll = sidebar.scrollTop;

        // Load page content via fetch
        var mainContent = document.getElementById('mainContent');
        if (!mainContent) { window.location.href = href; return; }

        mainContent.style.opacity = '0.4';

        fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function(resp) {
                if (!resp.ok) throw new Error(resp.status);
                return resp.text();
            })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newContent = doc.getElementById('mainContent');
                if (newContent) mainContent.innerHTML = newContent.innerHTML;

                // Update title
                var t = doc.querySelector('title');
                if (t) document.title = t.textContent;

                // Push history
                history.pushState({ url: href }, '', href);

                // Scroll main to top
                mainContent.scrollTop = 0;

                // Restore sidebar scroll (multiple attempts)
                var target = savedScroll;
                var count = 0;
                var timer = setInterval(function() {
                    var sb = document.getElementById('sidebar');
                    if (sb) sb.scrollTop = target;
                    count++;
                    if ((sb && sb.scrollTop >= target - 2) || count > 20) clearInterval(timer);
                }, 30);

                // Load module script
                var modScript = doc.querySelector('script[src*="assets/js/"]');
                if (modScript) {
                    var src = modScript.getAttribute('href') || modScript.getAttribute('src');
                    var name = src.split('/').pop();
                    if (name !== 'app.js') {
                        var old = document.querySelector('script[data-module-script]');
                        if (old) old.remove();
                        var s = document.createElement('script');
                        s.src = src;
                        s.setAttribute('data-module-script', 'true');
                        document.body.appendChild(s);
                    }
                }

                // Destroy old charts
                if (window._chartInstances) {
                    for (var c = 0; c < window._chartInstances.length; c++) {
                        try { window._chartInstances[c].destroy(); } catch(ex) {}
                    }
                }
                window._chartInstances = [];

                // Fire event for module JS
                document.dispatchEvent(new CustomEvent('ajaxPageLoaded'));

                mainContent.style.opacity = '1';
            })
            .catch(function(err) {
                console.error('AJAX nav error:', err);
                window.location.href = href;
            });
    }, true); // capture phase — runs before any other handler
})();

// Browser back/forward
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.url) {
        var mainContent = document.getElementById('mainContent');
        if (!mainContent) return;
        fetch(e.state.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var nc = doc.getElementById('mainContent');
                if (nc) mainContent.innerHTML = nc.innerHTML;
                var t = doc.querySelector('title');
                if (t) document.title = t.textContent;
                document.dispatchEvent(new CustomEvent('ajaxPageLoaded'));
            });
    }
});
</script>
