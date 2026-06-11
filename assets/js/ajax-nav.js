/**
 * AJAX Navigation Core
 * Loaded in <body> before sidebar so onclick handlers work immediately
 */

// ========== AJAX Navigation Function (called from sidebar onclick) ==========
function ajaxNav(el) {
    var href = el.getAttribute('href');
    if (!href || href.charAt(0) === '#' || href.indexOf('://') > 0 || el.target === '_blank') return true;

    // Update active state on sidebar
    var sidebar = document.getElementById('sidebar');
    if (sidebar) {
        var links = sidebar.querySelectorAll('.nav-link');
        for (var i = 0; i < links.length; i++) links[i].classList.remove('active');
        el.classList.add('active');
    }

    loadPage(href, true);
    return false;
}

// ========== Tracked chart instances (for cleanup) ==========
window._chartInstances = [];
function trackChart(chartInstance) {
    window._chartInstances.push(chartInstance);
    return chartInstance;
}

// ========== Page Ready Helper ==========
function onPageReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
}

// ========== Load Page via AJAX ==========
function loadPage(url, pushState) {
    var mainContent = document.getElementById('mainContent');
    if (!mainContent) return;

    // Save sidebar scroll position
    var sidebar = document.getElementById('sidebar');
    var savedSidebarScroll = sidebar ? sidebar.scrollTop : 0;

    // Show loading state
    mainContent.style.opacity = '0.4';
    mainContent.style.pointerEvents = 'none';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function(response) {
            if (!response.ok) throw new Error('Page load failed: ' + response.status);
            // Check if redirected to login (session expired)
            if (response.redirected && response.url.indexOf('index.php') > -1) {
                window.location.href = response.url;
                return;
            }
            return response.text();
        })
        .then(function(html) {
            if (!html) return;

            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');

            // Replace main content
            var newMainContent = doc.getElementById('mainContent');
            if (newMainContent) {
                mainContent.innerHTML = newMainContent.innerHTML;
            }

            // Scroll main content to top
            mainContent.scrollTop = 0;

            // Restore sidebar scroll position
            if (sidebar && savedSidebarScroll > 0) {
                var scrollTarget = savedSidebarScroll;
                var attempts = 0;
                var restore = setInterval(function() {
                    var sb = document.getElementById('sidebar');
                    if (sb) sb.scrollTop = scrollTarget;
                    attempts++;
                    if ((sb && sb.scrollTop >= scrollTarget - 1) || attempts > 15) {
                        clearInterval(restore);
                    }
                }, 30);
            }

            // Update page title
            var newTitle = doc.querySelector('title');
            if (newTitle) document.title = newTitle.textContent;

            // Update active state in sidebar
            var newActivePage = doc.querySelector('#sidebar a.nav-link.active');
            if (newActivePage && sidebar) {
                var links = sidebar.querySelectorAll('.nav-link');
                for (var i = 0; i < links.length; i++) links[i].classList.remove('active');
                // Find matching link in current sidebar by href
                var newHref = newActivePage.getAttribute('href');
                var currentLinks = sidebar.querySelectorAll('.nav-link');
                for (var j = 0; j < currentLinks.length; j++) {
                    if (currentLinks[j].getAttribute('href') === newHref) {
                        currentLinks[j].classList.add('active');
                    }
                }
            }

            // Load module-specific scripts
            var moduleScript = doc.querySelector('script[src*="assets/js/"]');
            if (moduleScript) {
                var src = moduleScript.getAttribute('src');
                var scriptName = src.split('/').pop();
                if (scriptName !== 'app.js') {
                    // Remove old module script
                    var oldScript = document.querySelector('script[data-module-script]');
                    if (oldScript) oldScript.remove();

                    var newScript = document.createElement('script');
                    newScript.src = src;
                    newScript.setAttribute('data-module-script', 'true');
                    document.body.appendChild(newScript);
                }
            }

            // Add any new CSS links
            var newStyles = doc.querySelectorAll('link[rel="stylesheet"]');
            for (var s = 0; s < newStyles.length; s++) {
                var href = newStyles[s].getAttribute('href');
                if (href && !document.querySelector('link[href="' + href + '"]')) {
                    document.head.appendChild(newStyles[s].cloneNode(true));
                }
            }

            // Push browser history
            if (pushState) {
                history.pushState({ url: url }, '', url);
            }

            // Clean up old charts and init dynamic content
            initDynamicContent();

            // Fire custom event for module JS to listen to
            document.dispatchEvent(new CustomEvent('ajaxPageLoaded'));

            // Restore opacity
            mainContent.style.opacity = '1';
            mainContent.style.pointerEvents = '';

            // Final sidebar scroll restore
            if (sidebar && savedSidebarScroll > 0) {
                var sb2 = document.getElementById('sidebar');
                if (sb2) sb2.scrollTop = savedSidebarScroll;
            }
        })
        .catch(function(error) {
            console.error('AJAX Navigation Error:', error);
            // Fallback to normal navigation
            window.location.href = url;
        });
}

// ========== Dynamic Content Init ==========
function initDynamicContent() {
    // Destroy old Chart.js instances
    if (window._chartInstances) {
        for (var i = 0; i < window._chartInstances.length; i++) {
            try { window._chartInstances[i].destroy(); } catch(e) {}
        }
    }
    window._chartInstances = [];
}

// ========== Browser Back/Forward ==========
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.url) {
        loadPage(e.state.url, false);
    }
});
