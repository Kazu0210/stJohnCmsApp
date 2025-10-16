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
                    <td>${escapeHtml(r.reservationId)}</td>
                    <td>${escapeHtml(r.area)}</td>
                    <td>${escapeHtml(r.block)}</td>
                    <td>${escapeHtml(r.lotNumber)}</td>
                    <td>${escapeHtml(r.status || '')}</td>
                    <td>${escapeHtml(r.createdAt)}</td>
                </tr>
            `;
        }).join('');

        const table = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Area</th>
                            <th>Block</th>
                            <th>Lot Number</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
        `;

        $container.html(table);
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

    // Fetch reservations from the API
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
});
