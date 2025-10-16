document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('reservationsContainer');
  const alerts = document.getElementById('alerts');

  function showError(msg) {
    alerts.innerHTML = `<div class="alert alert-danger">${msg}</div>`;
  }

  function renderTable(items) {
    if (!items || items.length === 0) {
      container.innerHTML = '<p class="text-muted">You have no reservations.</p>';
      return;
    }

    let html = `
      <div class="d-flex justify-content-end mb-3">
        <button id="refreshBtn" class="btn btn-sm btn-outline-secondary">Refresh</button>
      </div>
      <div class="table-responsive">
      <table class="table table-striped reservation-table">
        <thead>
          <tr>
            <th>Reservation ID</th>
            <th>Lot</th>
            <th>Type</th>
            <th>Reserved On</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
    `;

    items.forEach(r => {
      const lotLabel = `${r.area || '-'} / ${r.block || '-'} / ${r.rowNumber || '-'} / ${r.lotNumber || '-'}`;
      const reservedOn = r.createdAt ? new Date(r.createdAt).toLocaleString() : '-';
      html += `<tr>
        <td>${r.reservationId || r.id || '-'}</td>
        <td>${lotLabel}</td>
        <td>${r.lotTypeName || '-'}</td>
        <td>${reservedOn}</td>
        <td>${r.status || '-'}</td>
        <td>${r.total_amount ? '₱' + Number(r.total_amount).toLocaleString() : '-'}</td>
        <td>
          <a href="/stJohnCmsApp/cmsApp/frontend/client/lotReservation/lotReservationForm.php?lotId=${encodeURIComponent(r.lotId || '')}&price=${encodeURIComponent(r.total_amount || '')}" class="btn btn-sm btn-outline-primary me-1">View/Edit</a>
        </td>
      </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;

    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) refreshBtn.addEventListener('click', () => fetchReservations());
  }

  async function fetchReservations() {
    alerts.innerHTML = '';
    container.innerHTML = '<p class="text-muted">Loading your reservations...</p>';
    try {
      // ensure cookies (PHP session) are sent with the request
      const res = await fetch('/stJohnCmsApp/cms.api/fetchUserReservations.php', { credentials: 'include' });
      // Log status for easier debugging
      console.log('fetchUserReservations status:', res.status);

      if (!res.ok) {
        // try to read response text for debugging (server may return plain text or JSON)
        let txt = '';
        try { txt = await res.text(); } catch (e) { txt = '<unable to read response body>'; }
        console.error('fetchUserReservations failed:', res.status, txt);
        if (res.status === 401) {
          showError('You are not authenticated. Please log in again.');
        } else {
          showError('Server error while fetching reservations.');
        }
        return;
      }

      let data;
      try {
        data = await res.json();
      } catch (e) {
        console.error('Invalid JSON from fetchUserReservations:', e);
        const txt = await res.text().catch(()=>'<no-body>');
        console.error('Response body:', txt);
        showError('Server returned an invalid response. Check server logs.');
        return;
      }
      if (data.status === 'success') {
        renderTable(data.data);
      } else {
        showError(data.message || 'Failed to load reservations');
      }
    } catch (err) {
      showError('Network error while fetching reservations');
      console.error(err);
    }
  }

  fetchReservations();
});
