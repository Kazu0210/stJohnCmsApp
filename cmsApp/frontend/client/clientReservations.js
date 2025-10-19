$(document).ready(function() {
    const $container = $('#reservationsContainer');
    const $alerts = $('#alerts');

    function showAlert(message, type = 'danger') {
        const html = `<div class="alert alert-${type} alert-dismissible" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
        $alerts.html(html);
    }

    function renderTable(reservations) {
        if (!reservations || reservations.length === 0) {
            $container.html('<p class="text-muted">You have no reservations.</p>');
            return;
        }

    let rows = reservations.map(r => {
        // Don't render Cancel button for reservations already cancelled
        const statusText = (r.status || '').toString();
        const isCancelled = statusText.toLowerCase() === 'cancelled' || statusText.toLowerCase() === 'cancel';
        // Determine Pay button (show only when there is an amount due > 0, reservation is not cancelled, and status allows payment)
        // Normalize amount fields and show Pay when amount_due > 0
        const amtDueRaw = r.amount_due ?? r.amountDue ?? r.amount_due ?? 0;
        const hasAmountDue = Number(amtDueRaw) > 0;
        // Block payments when reservation is still in an unpayable state
        const resStatusLower = statusText.trim().toLowerCase();
        const blockedStatuses = ['for reservation', 'for reserved'];
        const isBlocked = blockedStatuses.includes(resStatusLower);
        // Build payment URL: include reservationId explicitly and lotId if available
        let payUrl = '/stJohnCmsApp/cmsApp/frontend/client/payment/payment.php?paymentType=installment';
        if (r.lotId) payUrl += `&lotId=${encodeURIComponent(r.lotId)}`;
        if (r.reservationId) payUrl += `&reservationId=${encodeURIComponent(r.reservationId)}`;

        const payButton = (!isCancelled && hasAmountDue && !isBlocked)
            ? `<a class="btn btn-sm btn-outline-success btn-pay me-1" href="${payUrl}">Pay</a>`
            : '';

        const cancelButton = isCancelled
            ? '<button class="btn btn-sm btn-secondary" disabled>Cancelled</button>'
            : `<button class="btn btn-sm btn-outline-danger btn-cancel" data-id="${escapeHtml(r.reservationId)}">Cancel</button>`;

        const actionButton = `<div class="btn-group" role="group" aria-label="Actions">${payButton}${cancelButton}</div>`;

        // Payment progress calculation
        const total = Number(r.total_amount ?? r.totalAmount ?? 0);
        const paid = Number(r.amount_paid ?? r.total_paid ?? r.totalPaid ?? 0);
        let percent = 0;
        if (total > 0) {
            percent = Math.min(100, Math.round((paid / total) * 100));
        }
        const progressBar = `
            <div class="progress" style="height: 20px;">
                <div class="progress-bar${percent === 100 ? ' bg-success' : ''}" role="progressbar" style="width: ${percent}%;" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100">
                    ${percent}%
                </div>
            </div>
        `;

        return `
            <tr>
                <td>${escapeHtml(String(r.area || '').trim().split(/\s+/).map(s => s ? s.charAt(0).toUpperCase() + s.slice(1) : '').join(' '))}</td>
                <td>${escapeHtml(String(r.block || '').trim().split(/\s+/).map(s => s ? s.charAt(0).toUpperCase() + s.slice(1) : '').join(' '))}</td>
                <td>${escapeHtml(String(r.lotNumber || '').trim())}</td>
                <td class="reservation-status">${escapeHtml(String(r.status || '').trim().split(/\s+/).map(s => s ? s.charAt(0).toUpperCase() + s.slice(1) : '').join(' '))}</td>
                <td>${escapeHtml(r.createdAt)}</td>
                <td class="text-end">${formatCurrency(r.total_amount)}</td>
                <td>${escapeHtml(String(r.payment_type || '').trim().split(/\s+/).map(s => s ? s.charAt(0).toUpperCase() + s.slice(1) : '').join(' '))}</td>
                <td class="text-end">${formatCurrency(r.amount_paid)}</td>
                <td class="text-end">${formatCurrency(r.amount_due)}</td>
                <td>${progressBar}</td>
                <td>
                    ${actionButton}
                </td>
            </tr>
        `;
    }).join('');

        const table = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Block</th>
                            <th>Lot Number</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="text-end">Total Amount</th>
                            <th>Payment Type</th>
                            <th class="text-end">Amount Paid</th>
                            <th class="text-end">Amount Due</th>
                            <th style="min-width:120px;">Payment Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
        `;

        $container.html(table);

        // Wire action buttons
        $container.find('.btn-cancel').on('click', function() {
            const $btn = $(this);
            const reservationId = $btn.data('id');
            if (!reservationId) return;

            if (!confirm('Are you sure you want to cancel this reservation? This action cannot be undone.')) return;

            // Immediately disable the button and show a pending state
            $btn.prop('disabled', true).removeClass('btn-outline-danger').addClass('btn-warning').text('Cancelling…');

            // Send update request to set status = 'Cancelled' (status-only endpoint)
            fetch('/stJohnCmsApp/cms.api/updateReservationStatus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reservationID: reservationId, status: 'Cancelled' })
            })
            .then(resp => resp.json())
            .then(data => {
                if (data && data.status === 'success') {
                    showAlert('Reservation cancelled successfully.', 'success');
                    // Update the row status text and replace the button with disabled 'Cancelled'
                    const $row = $btn.closest('tr');
                    $row.find('.reservation-status').text('Cancelled');
                    $btn.replaceWith('<button class="btn btn-sm btn-secondary" disabled>Cancelled</button>');
                } else {
                    const msg = (data && data.message) ? data.message : 'Failed to cancel reservation';
                    showAlert(msg, 'danger');
                    // Restore button state
                    $btn.prop('disabled', false).removeClass('btn-warning').addClass('btn-outline-danger').text('Cancel');
                }
            })
            .catch(err => {
                showAlert('Request failed: ' + err, 'danger');
                $btn.prop('disabled', false).removeClass('btn-warning').addClass('btn-outline-danger').text('Cancel');
            });
        });

        // Details button removed - only Cancel action is available
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function formatCurrency(value) {
        if (value === null || value === undefined || value === '') return '';
        const num = Number(value);
        if (isNaN(num)) return '';
        // Use Philippine Peso formatting; fallback to en-US if Intl not supported
        try {
            return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(num);
        } catch (e) {
            return '₱' + num.toFixed(2);
        }
    }

    // Load reservations helper (allows reload after delete)
    function loadReservations() {
        $.ajax({
            url: '/stJohnCmsApp/cms.api/fetchUserReservations.php',
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (!res) {
                    showAlert('Empty response from server');
                    $container.html('');
                    return;
                }

                if (res.success) {
                    renderTable(res.data || []);
                } else {
                    const err = res.error || 'Failed to fetch reservations';
                    showAlert(err);
                    $container.html('<p class="text-muted">Unable to load reservations.</p>');
                }
            },
            error: function(xhr, status, err) {
                showAlert('Request failed: ' + status + ' - ' + err);
                $container.html('<p class="text-muted">Unable to load reservations.</p>');
            }
        });
    }

    // Initial load
    loadReservations();
});
