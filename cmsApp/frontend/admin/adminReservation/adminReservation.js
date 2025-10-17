document.addEventListener('DOMContentLoaded', () => {
    // -------------------------
    // Config: adjust if needed
    // -------------------------
    const API_BASE = '/stJohnCmsApp/cms.api/'; // <-- path to folder containing the PHP files
    const UPLOADS_URL = API_BASE + 'reservations/uploads/client_ids/'; // where uploaded files are served publicly (adjust if different)

    // DOM elements
    const tableBody = document.getElementById('reservationTableBody');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const clearSearchBtn = document.getElementById('clearSearchBtn');

    // Pagination elements
    const entriesPerPageSelect = document.getElementById('entriesPerPage');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');

    // Modals
    const editModalEl = document.getElementById('editReservationModal');
    const editModal = new bootstrap.Modal(editModalEl);
    const editForm = document.getElementById('editReservationForm');

    const docModalEl = document.getElementById('docModal');
    const docModal = new bootstrap.Modal(docModalEl);
    const clientIdFileInput = document.getElementById('replaceFileInput');

    const cancelModalEl = document.getElementById('cancelReservationModal');
    const cancelModal = new bootstrap.Modal(cancelModalEl);
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');

    const archiveModalEl = document.getElementById('archiveOrDeleteModal');
    const archiveModal = new bootstrap.Modal(archiveModalEl);
    const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    // Local state
    let reservations = []; // fetched from server
    let lotTypeMap = {}; // map for lot type names/prices
    let filteredReservations = []; // filtered results
    let currentPage = 1;
    let entriesPerPage = 25;

    // -------------------------
    // Utility helpers
    // -------------------------
    function showToast(msg, type = 'success') {
        // Basic toast using bootstrap alert appended to body
        const wrapper = document.createElement('div');
        wrapper.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
        wrapper.style.zIndex = 1060;
        wrapper.innerText = msg;
        document.body.appendChild(wrapper);
        setTimeout(() => wrapper.remove(), 3000);
    }

    function formatCurrency(n) {
        // Ensure n is a number and fallback to 0 if null/undefined
        const value = Number(n) || 0;
        return value.toLocaleString('en-PH', {
            style: 'currency',
            currency: 'PHP'
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    }

    // Global logout function (for navbar links)
    window.handleLogout = function() {
        alert('Logging out... (Placeholder for actual logout logic)');
        // Implement your actual logout logic here, e.g., redirect to login page.
    }

    // -------------------------
    // Fetch reservations
    // -------------------------
    async function fetchReservations() {
        tableBody.innerHTML = '<tr><td colspan="16" class="text-center">Loading...</td></tr>';
        try {
            const res = await fetch(API_BASE + 'fetchAllReservation.php', {
                credentials: 'same-origin'
            });
            const json = await res.json();

            if (!json.success) {
                tableBody.innerHTML = `<tr><td colspan="16" class="text-center text-danger">${json.error || 'Failed to load reservations'}</td></tr>`;
                return;
            }
            
            // Map the new API response format to match existing code expectations
            reservations = (json.data || []).map(r => ({
                reservationId: r.reservationId,
                clientName: r.clientName,
                address: r.clientAddress,
                contactNumber: r.clientContact,
                clientValidId: r.clientId,
                reservationDate: r.reservationDate,
                area: r.area,
                block: r.block,
                    rowNumber: r.row,
                    lotId: r.lotId,
                lotNumber: r.lotNumber,
                lotTypeID: r.lotType,
                typeName: r.lotTypeName,
                burialDepth: r.burialDepth,
                price: r.amount,
                status: r.status,
                createdAt: r.submittedOn,
                updatedAt: r.updatedOn,
                userId: r.userId
            }));

            // Build lot type map for display and editing
            lotTypeMap = {
                '1': { name: 'Regular Lot (₱50,000)', price: 50000, monthly: 0 },
                '2': { name: 'Regular Lot (₱60,000)', price: 60000, monthly: 0 },
                '3': { name: 'Premium Lot (₱70,000)', price: 70000, monthly: 0 },
                '4': { name: 'Mausoleum Inside (₱500,000)', price: 500000, monthly: 0 },
                '5': { name: 'Mausoleum Roadside (₱600,000)', price: 600000, monthly: 0 },
                '6': { name: '4-Lot Package (₱300,000)', price: 300000, monthly: 0 },
                '7': { name: 'Exhumation (₱15,000)', price: 15000, monthly: 0 }
            };

            filterAndRender(); // Initial render
        } catch (err) {
            console.error(err);
            tableBody.innerHTML = '<tr><td colspan="16" class="text-center text-danger">Network or server error</td></tr>';
        }
    }

    // -------------------------
    // Filtering and Searching
    // -------------------------
    function filterAndRender() {
        const q = (searchInput.value || '').trim().toLowerCase();
        const status = statusFilter.value;

        filteredReservations = reservations.filter(r => {
            const matchesSearch = (!q) ||
                (r.clientName && r.clientName.toLowerCase().includes(q)) ||
                (r.lotNumber && r.lotNumber.toLowerCase().includes(q)) ||
                (r.block && r.block.toLowerCase().includes(q)) ||
                (r.rowNumber && r.rowNumber.toLowerCase().includes(q));

            const matchesStatus = (status === 'all') || (r.status.toLowerCase() === status);
            return matchesSearch && matchesStatus;
        });

        currentPage = 1; // Reset to first page when filtering
        renderTable();
        updatePagination();
    }


    // -------------------------
    // Render table
    // -------------------------
    function renderTable() {
        tableBody.innerHTML = '';
        
        if (!filteredReservations.length) {
            tableBody.innerHTML = '<tr><td colspan="16" class="text-center text-muted">No reservations found matching the criteria.</td></tr>';
            return;
        }

        // Calculate pagination
        const startIndex = (currentPage - 1) * entriesPerPage;
        const endIndex = startIndex + entriesPerPage;
        const pageData = filteredReservations.slice(startIndex, endIndex);

        pageData.forEach(rec => {
            const normalizedStatus = (rec.status || '').toLowerCase().trim();
            // Determine button state and text for Archive/Restore
            let archiveButtonHtml = '';
            if (rec.status.toLowerCase() === 'archived') {
                archiveButtonHtml = `<button class="btn btn-sm btn-success btn-restore" title="Restore"><i class="fas fa-undo"></i> Restore</button>`;
            } else {
                archiveButtonHtml = `<button class="btn btn-sm btn-secondary btn-archive-delete" title="Archive / Delete"><i class="fas fa-archive"></i> Options</button>`;
            }
            // Determine whether to show "Confirm" button: show for any status that is NOT reserved/cancelled/archived
            // This is intentionally permissive to catch various status string variants.
            const showConfirmBtn = normalizedStatus !== '' && !/(reserved|cancelled|canceled|archived)/.test(normalizedStatus);

            tableBody.insertAdjacentHTML('beforeend', `
                <tr data-id="${rec.reservationId}">
                    <td class="text-start">${escapeHtml(rec.clientName)}</td>
                    <td>${escapeHtml(rec.address || '—')}</td>
                    <td>${escapeHtml(rec.contactNumber || '—')}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-info btn-view-doc" title="View Client ID">
                            <i class="fas fa-id-card"></i>
                        </button>
                    </td>
                    <td>${rec.reservationDate ? new Date(rec.reservationDate).toLocaleDateString() : '—'}</td>
                    <td>${escapeHtml(rec.area || '—')}</td>
                    <td>${escapeHtml(rec.block || '—')}</td>
                    <td>${escapeHtml(rec.rowNumber || '—')}</td>
                    <td>${escapeHtml(rec.lotNumber || '—')}</td>
                    <td>${escapeHtml(rec.typeName || '—')}</td>
                    <td>${escapeHtml(rec.burialDepth || '—')}</td>
                    <td>${formatCurrency(rec.price || 0)}</td>
                    <td><span class="badge ${getStatusBadgeClass(rec.status)}">${escapeHtml(rec.status)}</span></td>
                    <td>${rec.createdAt || '—'}</td>
                    <td>${rec.updatedAt || '—'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary btn-edit text-white" title="Edit">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-cancel" title="Cancel Reservation">
                            <i class="fas fa-times-circle"></i>
                        </button>
                        ${showConfirmBtn ? `<button class="btn btn-sm btn-success btn-confirm text-white ms-1" title="Confirm Reservation"><i class="fas fa-check-circle"></i></button>` : ''}
                        ${archiveButtonHtml}
                    </td>
                </tr>`);
        });
    }

    function getStatusBadgeClass(status) {
        const lowerStatus = status.toLowerCase();
        if (lowerStatus === 'pending') return 'bg-warning text-dark';
        if (lowerStatus === 'reserved') return 'bg-success';
        if (lowerStatus === 'cancelled') return 'bg-danger';
        if (lowerStatus === 'archived') return 'bg-secondary';
        return 'bg-info';
    }

    // -------------------------
    // Event delegation for table actions
    // -------------------------
    tableBody.addEventListener('click', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;
        const id = tr.dataset.id;

        if (e.target.closest('.btn-edit')) {
            openEditModal(id);
        } else if (e.target.closest('.btn-cancel')) {
            prepareCancelModal(id);
        } else if (e.target.closest('.btn-archive-delete')) {
            prepareArchiveDeleteModal(id);
        } else if (e.target.closest('.btn-restore')) {
            updateReservationStatus(id, 'pending', 'Restoring reservation to Pending status.');
        } else if (e.target.closest('.btn-confirm')) {
            // Confirm reservation: change reservation status to Reserved and mark lot as Reserved
            const rec = reservations.find(r => String(r.reservationId) === String(id));
            if (!rec) return showToast('Reservation not found', 'danger');
            if (!confirm(`Confirm reservation for ${rec.clientName} (Lot ${rec.lotNumber}) and mark lot as Reserved?`)) return;
            confirmReservation(id);
        } else if (e.target.closest('.btn-view-doc')) {
            openDocModal(id);
        }
    });

    // -------------------------
    // Search & Filter event listeners
    // -------------------------
    searchInput?.addEventListener('input', filterAndRender);
    statusFilter?.addEventListener('change', filterAndRender);
    clearSearchBtn?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        filterAndRender();
    });

    // -------------------------
    // Pagination functions
    // -------------------------
    function updatePagination() {
        const totalEntries = filteredReservations.length;
        const totalPages = Math.ceil(totalEntries / entriesPerPage) || 1;
        const startEntry = totalEntries === 0 ? 0 : (currentPage - 1) * entriesPerPage + 1;
        const endEntry = Math.min(currentPage * entriesPerPage, totalEntries);

        // Update pagination info
        paginationInfo.textContent = `Showing ${startEntry} to ${endEntry} of ${totalEntries} entries`;

        // Update pagination controls
        updatePaginationControls(currentPage, totalPages);
    }

    function updatePaginationControls(current, total) {
        paginationControls.innerHTML = '';

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${current === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `
            <button class="page-link" ${current === 1 ? 'disabled' : ''} aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </button>
        `;
        prevLi.addEventListener('click', () => {
            if (current > 1) {
                currentPage = current - 1;
                renderTable();
                updatePagination();
            }
        });
        paginationControls.appendChild(prevLi);

        // Page numbers
        const startPage = Math.max(1, current - 2);
        const endPage = Math.min(total, current + 2);

        if (startPage > 1) {
            addPageButton(1);
            if (startPage > 2) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'page-item disabled';
                ellipsis.innerHTML = '<span class="page-link">...</span>';
                paginationControls.appendChild(ellipsis);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            addPageButton(i, i === current);
        }

        if (endPage < total) {
            if (endPage < total - 1) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'page-item disabled';
                ellipsis.innerHTML = '<span class="page-link">...</span>';
                paginationControls.appendChild(ellipsis);
            }
            addPageButton(total);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${current === total ? 'disabled' : ''}`;
        nextLi.innerHTML = `
            <button class="page-link" ${current === total ? 'disabled' : ''} aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </button>
        `;
        nextLi.addEventListener('click', () => {
            if (current < total) {
                currentPage = current + 1;
                renderTable();
                updatePagination();
            }
        });
        paginationControls.appendChild(nextLi);
    }

    function addPageButton(pageNum, active = false) {
        const li = document.createElement('li');
        li.className = `page-item ${active ? 'active' : ''}`;
        li.innerHTML = `<button class="page-link" data-page="${pageNum}">${pageNum}</button>`;
        li.addEventListener('click', () => {
            currentPage = pageNum;
            renderTable();
            updatePagination();
        });
        paginationControls.appendChild(li);
    }

    // Pagination event listeners
    entriesPerPageSelect?.addEventListener('change', (e) => {
        entriesPerPage = parseInt(e.target.value);
        currentPage = 1;
        renderTable();
        updatePagination();
    });

    // -------------------------
    // Edit modal: open & populate
    // -------------------------
    function openEditModal(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
        if (!rec) {
            showToast('Reservation not found', 'danger');
            return;
        }

        // populate fields in edit modal
        editModalEl.dataset.id = reservationId;
        document.getElementById('editingClientName').textContent = rec.clientName || '';
        document.getElementById('editClientName').value = rec.clientName || '';
        document.getElementById('editClientAddress').value = rec.address || '';
        document.getElementById('editClientContact').value = rec.contactNumber || '';
        document.getElementById('editReservationDate').value = rec.reservationDate || '';
        document.getElementById('editArea').value = rec.area || '';
        document.getElementById('editBlock').value = rec.block || '';
        document.getElementById('editRow').value = rec.rowNumber || '';
        document.getElementById('editLot').value = rec.lotNumber || '';
        document.getElementById('editLotType').value = rec.lotTypeID || '';
        document.getElementById('editBurialDepth').value = rec.burialDepth || '';

        toggleBurialDepthUI();
        editModal.show();
    }

    // toggle burial depth visibility based on lot type (4 & 5 hide)
    function toggleBurialDepthUI() {
        const lotType = document.getElementById('editLotType').value;
        const burialDepthField = document.getElementById('burialDepthField');
        // Based on the HTML, lot types 4 (Mausoleum Inside) and 5 (Mausoleum Roadside) should hide burial depth.
        burialDepthField.style.display = (lotType === '4' || lotType === '5') ? 'none' : 'block';
    }
    document.getElementById('editLotType')?.addEventListener('change', toggleBurialDepthUI);

    // -------------------------
    // Submit edit (updateReservation.php)
    // -------------------------
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const reservationId = editModalEl.dataset.id;
        if (!reservationId) return showToast('No reservation selected', 'danger');

        // Build payload from form fields.
        const payload = {
            reservationID: parseInt(reservationId, 10),
            clientName: document.getElementById('editClientName').value.trim(),
            address: document.getElementById('editClientAddress').value.trim(),
            contactNumber: document.getElementById('editClientContact').value.trim(),
            reservationDate: document.getElementById('editReservationDate').value,
            area: document.getElementById('editArea').value.trim(),
            block: document.getElementById('editBlock').value.trim(),
            rowNumber: document.getElementById('editRow').value.trim(),
            lotNumber: document.getElementById('editLot').value.trim(),
            lotTypeID: document.getElementById('editLotType').value,
            burialDepth: document.getElementById('editBurialDepth').value,
        };

        try {
            const res = await fetch(API_BASE + 'updateReservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            });
            const json = await res.json();

            if (json.status === 'success') {
                showToast(json.message || 'Changes saved!', 'success');
                editModal.hide();
                await fetchReservations();
            } else {
                showToast(json.message || 'Update failed', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error during update', 'danger');
        }
    });

    // -------------------------
    // Cancel reservation modal preparation
    // -------------------------
    function prepareCancelModal(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
        if (!rec) return showToast('Reservation not found', 'danger');

        const modalText = document.getElementById('cancelModalText');
        modalText.innerHTML = `You are about to cancel the reservation for <strong>${escapeHtml(rec.clientName)}</strong> (Lot ${escapeHtml(rec.lotNumber)}). <br><br>The system's policy states that if the first payment isn't received within <strong>24 hours</strong> of submission, the reservation is automatically cancelled. Confirm the cancellation now?`;
        confirmCancelBtn.dataset.id = reservationId;
        cancelModal.show();
    }

    // Actual cancel submission
    confirmCancelBtn.addEventListener('click', () => {
        const reservationId = confirmCancelBtn.dataset.id;
        updateReservationStatus(reservationId, 'cancelled', 'Cancelling reservation...');
        cancelModal.hide();
    });

    // -------------------------
    // Archive/Delete modal preparation
    // -------------------------
    function prepareArchiveDeleteModal(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId)); // Corrected find
        if (!rec) return showToast('Reservation not found', 'danger');

        const modalText = document.getElementById('archiveModalText');
        document.getElementById('archiveModalTitle').textContent = `Manage Reservation for ${escapeHtml(rec.clientName)}`;
        modalText.innerHTML = `Choose an action for the reservation made by <strong>${escapeHtml(rec.clientName)}</strong>.`;

        confirmArchiveBtn.dataset.id = reservationId;
        confirmDeleteBtn.dataset.id = reservationId;
        archiveModal.show();
    }

    // Handle Archive
    confirmArchiveBtn.addEventListener('click', () => {
        const reservationId = confirmArchiveBtn.dataset.id;
        updateReservationStatus(reservationId, 'archived', 'Archiving reservation...');
        archiveModal.hide();
    });

    // Handle Permanent Delete
    confirmDeleteBtn.addEventListener('click', () => {
        const reservationId = confirmDeleteBtn.dataset.id;
        if (confirm('WARNING: Deleting permanently removes the record from the database. Are you absolutely sure?')) {
            onPermanentDelete(reservationId);
            archiveModal.hide();
        }
    });


    // -------------------------
    // General Status Update Function (for Cancel/Archive/Restore)
    // -------------------------
    async function updateReservationStatus(reservationId, newStatus, loadingMsg) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
        if (!rec) return showToast('Reservation not found', 'danger');

        showToast(loadingMsg, 'info');

        try {
            // Using a generic update payload. A dedicated status-only endpoint would be more efficient.
            const payload = {
                reservationID: parseInt(reservationId, 10),
                status: newStatus,
                clientName: rec.clientName,
                address: rec.address,
                contactNumber: rec.contactNumber,
                reservationDate: rec.reservationDate,
                area: rec.area,
                block: rec.block,
                rowNumber: rec.rowNumber,
                lotNumber: rec.lotNumber,
                lotTypeID: rec.lotTypeID,
                burialDepth: rec.burialDepth
            };

            const res = await fetch(API_BASE + 'updateReservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            });
            const json = await res.json();

            if (json.status === 'success') {
                showToast(json.message || `Status changed to ${newStatus}!`, 'success');
                await fetchReservations();
            } else {
                showToast(json.message || `Status update to ${newStatus} failed`, 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error during status update', 'danger');
        }
    }

    // -------------------------
    // Confirm Reservation (update reservation + lot status)
    // -------------------------
    async function confirmReservation(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
        if (!rec) return showToast('Reservation not found', 'danger');

        showToast('Confirming reservation...', 'info');

        try {
            const res = await fetch(API_BASE + 'confirmReservation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ reservationID: parseInt(reservationId, 10), lotId: rec.lotId || null })
            });
            const json = await res.json();
            if (json.status === 'success') {
                showToast(json.message || 'Reservation confirmed and lot reserved', 'success');
                await fetchReservations();
            } else {
                showToast(json.message || 'Failed to confirm reservation', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error during confirmation', 'danger');
        }
    }

    // -------------------------
    // Permanent Delete (Separate for clarity)
    // -------------------------
    async function onPermanentDelete(reservationId) {
        try {
            const res = await fetch(API_BASE + 'deleteReservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reservationID: reservationId // API expects reservationID
                }),
                credentials: 'same-origin'
            });
            const json = await res.json();
            if (json.status === 'success') {
                showToast(json.message || 'Deleted permanently!', 'success');
                await fetchReservations();
            } else {
                showToast(json.message || 'Delete failed', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error during delete', 'danger');
        }
    }


    // -------------------------
    // Document modal (view / upload client ID)
    // -------------------------
    function openDocModal(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
        if (!rec) return showToast('Reservation not found', 'danger');

        docModalEl.dataset.id = reservationId; // Correctly set the ID
        const docFilename = document.getElementById('docFilename');
        const imgPreview = document.getElementById('img-preview');
        const downloadLink = document.getElementById('downloadLink');
        const pdfCanvas = document.getElementById('pdf-canvas');
        const pdfControls = document.getElementById('pdfControls');
        const deleteBtn = document.getElementById('deleteBtn');

        // reset UI
        docFilename.textContent = '';
        imgPreview.classList.add('d-none');
        pdfCanvas.classList.add('d-none');
        pdfControls.classList.add('d-none');
        downloadLink.classList.add('disabled');
        deleteBtn.classList.add('disabled');
        imgPreview.src = '';
        pdfCanvas.width = 0;
        pdfCanvas.height = 0;

        if (rec.clientValidId) {
            docFilename.textContent = `File: ${rec.clientValidId}`;
            const fileUrl = UPLOADS_URL + rec.clientValidId;
            const fileExt = rec.clientValidId.split('.').pop().toLowerCase();

            // Enable download and delete
            downloadLink.href = fileUrl;
            downloadLink.classList.remove('disabled');
            deleteBtn.classList.remove('disabled');

            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                imgPreview.src = fileUrl;
                imgPreview.classList.remove('d-none');
            } else if (fileExt === 'pdf') {
                docFilename.textContent += ' (PDF preview requires additional libraries/logic)';
            } else {
                docFilename.textContent += ' (Preview not available)';
            }
        } else {
            docFilename.textContent = 'No ID uploaded for this reservation.';
        }
        docModal.show();
    }

    // handle file upload inside doc modal (Add/Replace)
    clientIdFileInput?.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        const reservationId = docModalEl.dataset.id;
        if (!file || !reservationId) return;

        const formData = new FormData();
        formData.append('reservationID', reservationId);
        formData.append('clientIdFile', file);

        try {
            const res = await fetch(API_BASE + 'uploadClientId.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const json = await res.json();
            if (json.status === 'success') {
                showToast('Client ID file uploaded successfully!', 'success');
                docModal.hide();
                await fetchReservations();
            } else {
                showToast(json.message || 'Client ID upload failed', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error during file upload', 'danger');
        } finally {
            e.target.value = ''; // Clear file input
        }
    });

    // delete client id from reservation
    document.getElementById('deleteBtn')?.addEventListener('click', async () => {
        const reservationId = docModalEl.dataset.id;
        if (!reservationId) return;
        if (!confirm('Are you sure you want to delete the uploaded Client ID? This action is permanent.')) return;

        try {
            const res = await fetch(API_BASE + 'updateReservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reservationID: parseInt(reservationId, 10),
                    clientValidId: '' // The backend should handle file deletion here too
                }),
                credentials: 'same-origin'
            });
            const json = await res.json();
            if (json.status === 'success') {
                showToast('Client ID file removed', 'success');
                await fetchReservations();
                docModal.hide();
            } else {
                showToast(json.message || 'Failed to remove Client ID', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error', 'danger');
        }
    });

    // -------------------------
    // Init
    // -------------------------
    fetchReservations();
});
