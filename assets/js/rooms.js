/**
 * Rooms Module JS
 */
document.addEventListener('DOMContentLoaded', function() { initRooms(); });
document.addEventListener('ajaxPageLoaded', function() { initRooms(); });

function initRooms() {
    // Delete confirmation
    document.querySelectorAll('.btn-delete-room').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm(this.dataset.confirm || 'Delete this room?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.dataset.url;
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + this.dataset.id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Status quick change
    document.querySelectorAll('.status-change').forEach(select => {
        select.addEventListener('change', function() {
            const roomId = this.dataset.roomId;
            const newStatus = this.value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.dataset.url;
            form.innerHTML = '<input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="' + roomId + '"><input type="hidden" name="status" value="' + newStatus + '">';
            document.body.appendChild(form);
            form.submit();
        });
    });

    // Filter form
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.querySelectorAll('select, input').forEach(el => {
            el.addEventListener('change', () => filterForm.submit());
        });
    }
}
