/**
 * Billing Module JS
 */
document.addEventListener('DOMContentLoaded', function() { initBilling(); });
document.addEventListener('ajaxPageLoaded', function() { initBilling(); });

function initBilling() {
    // Print bill
    const printBtn = document.getElementById('printBill');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }

    // Add payment form submission
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Error adding payment');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        });
    }

    // Add extra charge row
    const addChargeBtn = document.getElementById('addChargeRow');
    const chargeContainer = document.getElementById('extraChargesContainer');
    if (addChargeBtn && chargeContainer) {
        addChargeBtn.addEventListener('click', function() {
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
                    <input type="text" name="charge_desc[]" class="form-control form-control-sm" placeholder="Description" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="charge_amount[]" class="form-control form-control-sm charge-amount" placeholder="0.00" step="0.01" min="0" required>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-charge"><i class="bi bi-x"></i></button>
                </div>`;
            chargeContainer.appendChild(row);
            row.querySelector('.remove-charge').addEventListener('click', () => row.remove());
            row.querySelector('.charge-amount').addEventListener('input', recalcBill);
        });
    }

    function recalcBill() {
        const roomCharges = parseFloat(document.getElementById('room_charges')?.value || 0);
        let extraTotal = 0;
        document.querySelectorAll('.charge-amount').forEach(input => {
            extraTotal += parseFloat(input.value || 0);
        });
        const discount = parseFloat(document.getElementById('discount')?.value || 0);
        const subtotal = roomCharges + extraTotal - discount;
        const tax = subtotal * 0.12;
        const total = subtotal + tax;

        const taxEl = document.getElementById('tax_amount');
        const totalEl = document.getElementById('total_amount');
        if (taxEl) taxEl.value = tax.toFixed(2);
        if (totalEl) totalEl.value = total.toFixed(2);
    }
}
