/**
 * App - Global JavaScript (non-navigation utilities)
 * Navigation is handled by ajax-nav.js (loaded before sidebar)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss flash toasts
    var toasts = document.querySelectorAll('.toast');
    toasts.forEach(function(toast) {
        setTimeout(function() {
            if (typeof bootstrap !== 'undefined') {
                var bsToast = bootstrap.Toast.getOrCreateInstance(toast);
                bsToast.hide();
            }
        }, 4000);
    });

    // Sidebar toggle (mobile)
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // Confirm delete (event delegation)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-delete');
        if (btn) {
            if (!confirm(btn.dataset.confirm || 'Are you sure you want to delete this record?')) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    });

    // Intercept clicks on links inside #mainContent for SPA navigation
    var mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.addEventListener('click', function(e) {
            var link = e.target.closest('a[href]');
            if (!link) return;
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('://') > 0 || href.indexOf('javascript') === 0 || link.target === '_blank' || link.hasAttribute('data-full-load')) return;
            if (href.indexOf('download') > -1 || href.indexOf('export') > -1) return;
            e.preventDefault();
            loadPage(href, true);
        });
    }
});

/**
 * Generic fetch wrapper
 */
async function apiFetch(url, options) {
    options = options || {};
    try {
        var response = await fetch(url, Object.assign({
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }, options, {
            headers: Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {})
        }));
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Animate counter
 */
function animateCounter(element, target, duration) {
    duration = duration || 1000;
    var start = 0;
    var startTime = performance.now();
    function update(currentTime) {
        var elapsed = currentTime - startTime;
        var progress = Math.min(elapsed / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        var current = Math.round(start + (target - start) * eased);
        element.textContent = current;
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
}
