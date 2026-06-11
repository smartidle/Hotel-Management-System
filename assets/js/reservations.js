/**
 * Reservations Module JS
 */
document.addEventListener('DOMContentLoaded', function() { initReservations(); });
document.addEventListener('ajaxPageLoaded', function() { initReservations(); });

function initReservations() {
    // Date calculation
    const checkinDate = document.getElementById('check_in_date');
    const checkoutDate = document.getElementById('check_out_date');
    const nightsDisplay = document.getElementById('totalNights');
    const nightsSummary = document.getElementById('nightsSummary');
    const totalDisplay = document.getElementById('totalAmount');
    const totalAmountDisplay = document.getElementById('totalAmountDisplay');
    const priceDisplay = document.getElementById('priceDisplay');
    const pricePerNight = document.getElementById('pricePerNight');

    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function calculateTotals() {
        if (checkinDate && checkoutDate && checkinDate.value && checkoutDate.value) {
            const d1 = new Date(checkinDate.value);
            const d2 = new Date(checkoutDate.value);
            const diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
            const nights = Math.max(diff, 0);

            if (nightsDisplay) nightsDisplay.textContent = nights;
            if (nightsSummary) nightsSummary.textContent = nights + (nights === 1 ? ' Night' : ' Nights');

            if (pricePerNight) {
                const price = parseFloat(pricePerNight.value || pricePerNight.dataset.price || 0);
                const total = (nights * price).toFixed(2);

                if (totalDisplay) totalDisplay.value = total;
                if (totalAmountDisplay) totalAmountDisplay.textContent = formatCurrency(total);
            }

            // Update checkout min date
            if (checkoutDate && checkinDate.value) {
                checkoutDate.min = checkinDate.value;
            }
        }
    }

    if (checkinDate) checkinDate.addEventListener('change', calculateTotals);
    if (checkoutDate) checkoutDate.addEventListener('change', calculateTotals);

    // Load available rooms when dates change
    const roomSelect = document.getElementById('room_id');
    if (roomSelect && checkinDate && checkoutDate) {
        async function loadRooms() {
            if (!checkinDate.value || !checkoutDate.value) return;

            try {
                const url = `api.php?action=get_available&check_in=${checkinDate.value}&check_out=${checkoutDate.value}`;
                const res = await fetch(url);
                const data = await res.json();

                if (data.success && data.rooms) {
                    roomSelect.innerHTML = '<option value="">-- Select Room --</option>';
                    data.rooms.forEach(room => {
                        const opt = document.createElement('option');
                        opt.value = room.id;
                        opt.textContent = `Room ${room.room_number} - ${room.type_name} (₱${parseFloat(room.base_price).toLocaleString()}/night)`;
                        opt.dataset.price = room.base_price;
                        roomSelect.appendChild(opt);
                    });
                }
            } catch (e) {
                console.error('Error loading rooms:', e);
            }
        }

        checkinDate.addEventListener('change', loadRooms);
        checkoutDate.addEventListener('change', loadRooms);

        roomSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (selected && selected.dataset.price) {
                if (pricePerNight) {
                    pricePerNight.value = selected.dataset.price;
                    pricePerNight.dataset.price = selected.dataset.price;
                }
                if (priceDisplay) {
                    priceDisplay.textContent = formatCurrency(selected.dataset.price);
                }
                calculateTotals();
            }
        });
    }

    // Guest search
    const guestSearch = document.getElementById('guestSearch');
    const guestSelect = document.getElementById('guest_id');
    if (guestSearch && guestSelect) {
        let timeout;
        guestSearch.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(async () => {
                const query = this.value.trim();
                if (query.length < 2) return;
                try {
                    const res = await fetch(`../guests/api.php?action=search&query=${encodeURIComponent(query)}`);
                    const data = await res.json();
                    if (data.success && data.guests) {
                        guestSelect.innerHTML = '<option value="">-- Select Guest --</option>';
                        data.guests.forEach(g => {
                            const opt = document.createElement('option');
                            opt.value = g.id;
                            opt.textContent = `${g.first_name} ${g.last_name} (${g.phone})`;
                            guestSelect.appendChild(opt);
                        });
                    }
                } catch (e) { console.error(e); }
            }, 300);
        });
    }

    // Quick create guest
    const btnQuickCreate = document.getElementById('btnQuickCreateGuest');
    if (btnQuickCreate) {
        btnQuickCreate.addEventListener('click', async function() {
            const form = document.getElementById('quickGuestForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            if (!data.first_name || !data.last_name) {
                alert('First name and Last name are required.');
                return;
            }

            try {
                const res = await fetch('../guests/api.php?action=create', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    // Add new guest to select dropdown
                    const opt = document.createElement('option');
                    opt.value = result.guest_id;
                    opt.textContent = `${data.first_name} ${data.last_name}`;
                    opt.selected = true;
                    guestSelect.appendChild(opt);

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('quickGuestModal'));
                    if (modal) modal.hide();
                    form.reset();
                } else {
                    alert(result.message || 'Failed to create guest.');
                }
            } catch (e) {
                console.error(e);
                alert('Error creating guest.');
            }
        });
    }
}
