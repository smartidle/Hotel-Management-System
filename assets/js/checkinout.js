/**
 * Check-in / Check-out Module JS
 */
document.addEventListener('DOMContentLoaded', function() { initCheckinout(); });
document.addEventListener('ajaxPageLoaded', function() { initCheckinout(); });

function initCheckinout() {
    // Calculate charges on checkout
    const calcBtn = document.getElementById('calculateCharges');
    const checkinIdField = document.getElementById('checkin_id');
    const roomChargeField = document.getElementById('room_charges');
    const nightsField = document.getElementById('stay_nights');

    if (calcBtn) {
        calcBtn.addEventListener('click', async function() {
            const checkinId = checkinIdField?.value;
            if (!checkinId) return;

            try {
                const res = await fetch(`api.php?action=calculate_charges&checkin_id=${checkinId}`);
                const data = await res.json();
                if (data.success) {
                    if (roomChargeField) roomChargeField.value = data.room_charges;
                    if (nightsField) nightsField.textContent = data.nights + ' nights';
                    recalcTotal();
                }
            } catch (e) { console.error(e); }
        });
    }

    // Add extra charge row
    const addChargeBtn = document.getElementById('addChargeRow');
    const chargeContainer = document.getElementById('extraChargesContainer');
    if (addChargeBtn && chargeContainer) {
        let chargeIndex = chargeContainer.querySelectorAll('.charge-row').length;
        addChargeBtn.addEventListener('click', function() {
            chargeIndex++;
            const row = document.createElement('div');
            row.className = 'charge-row row g-2 mb-2 align-items-end';
            row.innerHTML = `
                <div class="col-md-3">
                    <select name="charge_type[]" class="form-select form-select-sm">
                        <option value="minibar">Minibar</option>
                        <option value="laundry">Laundry</option>
                        <option value="room_service">Room Service</option>
                        <option value="phone">Phone</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" name="charge_desc[]" class="form-control form-control-sm" placeholder="Description">
                </div>
                <div class="col-md-3">
                    <input type="number" name="charge_amount[]" class="form-control form-control-sm charge-amount" placeholder="0.00" step="0.01" min="0">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-charge"><i class="bi bi-x"></i></button>
                </div>`;
            chargeContainer.appendChild(row);

            row.querySelector('.remove-charge').addEventListener('click', () => {
                row.remove();
                recalcTotal();
            });
            row.querySelector('.charge-amount').addEventListener('input', recalcTotal);
        });
    }

    function recalcTotal() {
        const roomCharges = parseFloat(roomChargeField?.value || 0);
        let extraTotal = 0;
        document.querySelectorAll('.charge-amount').forEach(input => {
            extraTotal += parseFloat(input.value || 0);
        });
        const subtotal = roomCharges + extraTotal;
        const tax = subtotal * 0.12;
        const total = subtotal + tax;

        const extraField = document.getElementById('extra_charges_total');
        const taxField = document.getElementById('tax_amount');
        const totalField = document.getElementById('total_amount');

        if (extraField) extraField.value = extraTotal.toFixed(2);
        if (taxField) taxField.value = tax.toFixed(2);
        if (totalField) totalField.value = total.toFixed(2);
    }

    // Search functionality
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.getElementById('searchResults');

    if (searchForm && searchInput && resultsContainer) {
        searchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (!query) return;

            try {
                const action = this.dataset.action;
                const res = await fetch(`api.php?action=${action}&query=${encodeURIComponent(query)}`);
                const data = await res.json();

                if (data.success && data.results) {
                    resultsContainer.innerHTML = '';
                    if (data.results.length === 0) {
                        resultsContainer.innerHTML = '<div class="text-center text-muted py-3">No results found</div>';
                        return;
                    }
                    data.results.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'list-group-item list-group-item-action p-3';
                        div.style.cursor = 'pointer';
                        div.innerHTML = item.html;
                        div.addEventListener('click', () => selectResult(item));
                        resultsContainer.appendChild(div);
                    });
                }
            } catch (e) { console.error(e); }
        });
    }

    function selectResult(item) {
        // Fill form fields based on result data
        const fields = item.fields || {};
        Object.keys(fields).forEach(key => {
            const el = document.getElementById(key);
            if (el) el.value = fields[key];
        });
        // Hide search results
        if (resultsContainer) resultsContainer.innerHTML = '';
    }
}
