<?php
/**
 * HTML Footer
 * Optional variable: $module_js (module-specific JS filename without extension)
 */
global $LANG;
?>
    </div><!-- End .main-content -->

    <!-- Flash Message Toast -->
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999">
        <div class="toast show align-items-center text-bg-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <!-- App JS -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/app.js"></script>
    <?php if (isset($module_js) && $module_js): ?>
    <script src="<?php echo getBaseUrl(); ?>/assets/js/<?php echo $module_js; ?>.js"></script>
    <?php endif; ?>
</body>
</html>
