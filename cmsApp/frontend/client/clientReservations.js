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
            return `
                <tr>
                    <td>${escapeHtml(r.area)}</td>
                    <td>${escapeHtml(r.block)}</td>
                    <td>${escapeHtml(r.lotNumber)}</td>
                    <td>${escapeHtml(r.status || '')}</td>
                    <td>${escapeHtml(r.createdAt)}</td>
                    <td>
                        <div class="btn-group" role="group" aria-label="Actions">
                            <button class="btn btn-sm btn-outline-danger btn-cancel" data-id="${escapeHtml(r.reservationId)}">Cancel</button>
                        </div>
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
            const reservationId = $(this).data('id');
            if (!reservationId) return;

            if (!confirm('Are you sure you want to cancel this reservation? This action cannot be undone.')) return;

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
                    // Refresh list
                    loadReservations();
                } else {
                    const msg = (data && data.message) ? data.message : 'Failed to cancel reservation';
                    showAlert(msg, 'danger');
                }
            })
            .catch(err => {
                showAlert('Request failed: ' + err, 'danger');
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
