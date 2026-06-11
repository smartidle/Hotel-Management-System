/**
 * Staff Module JS
 */
document.addEventListener('DOMContentLoaded', function() { initStaff(); });
document.addEventListener('ajaxPageLoaded', function() { initStaff(); });

function initStaff() {
    // Toggle status
    document.querySelectorAll('.btn-toggle-status').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const msg = this.dataset.confirm || 'Toggle staff status?';
            if (confirm(msg)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.dataset.url;
                form.innerHTML = `<input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="${this.dataset.id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Delete
    document.querySelectorAll('.btn-delete-staff').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm(this.dataset.confirm || 'Delete this staff member?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.dataset.url;
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${this.dataset.id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Password confirmation
    const staffForm = document.getElementById('staffForm');
    if (staffForm) {
        staffForm.addEventListener('submit', function(e) {
            const pw = document.getElementById('password');
            const cpw = document.getElementById('confirm_password');
            if (pw && cpw && pw.value && pw.value !== cpw.value) {
                e.preventDefault();
                alert('Passwords do not match.');
                cpw.focus();
            }
        });
    }
}
