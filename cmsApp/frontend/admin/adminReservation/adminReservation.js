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
    let lotTypeMap = {}; // map for lot type names/prices (optional, depends on your PHP fetch)
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

            // Placeholder for lotTypeMap if it's not fetched separately
            lotTypeMap = {
                '1': { name: 'Regular Lot (₱50,000)', price: 50000, monthly: 0 },
                // ... include all your lot types here ...
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
    // Render table (REVISED to include Confirm button)
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
            const lowerStatus = rec.status.toLowerCase();

            // --- Confirm/Reserve Button HTML ---
            let confirmButtonHtml = '';
            // Show only if status is pending
            if (lowerStatus === 'pending') { 
                confirmButtonHtml = `<button class="btn btn-sm btn-success btn-confirm-reserve ms-1" title="Confirm Reservation/Set Occupied">
                                        <i class="fas fa-check"></i>
                                     </button>`;
            }

            // --- Determine Archive/Restore Button HTML ---
            let archiveButtonHtml = '';
            if (lowerStatus === 'archived') {
                archiveButtonHtml = `<button class="btn btn-sm btn-success btn-restore ms-1" title="Restore"><i class="fas fa-undo"></i> Restore</button>`;
            } else if (lowerStatus !== 'cancelled' && lowerStatus !== 'occupied') { 
                // Only allow Archive/Delete if not Cancelled or Occupied
                archiveButtonHtml = `<button class="btn btn-sm btn-secondary btn-archive-delete ms-1" title="Archive / Delete"><i class="fas fa-archive"></i> Options</button>`;
            }


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
                        ${confirmButtonHtml}
                        <button class="btn btn-sm btn-danger btn-cancel ms-1" title="Cancel Reservation">
                            <i class="fas fa-times-circle"></i>
                        </button>
                        ${archiveButtonHtml}
                    </td>
                </tr>`);
        });
    }

    function getStatusBadgeClass(status) {
        const lowerStatus = status.toLowerCase();
        if (lowerStatus === 'pending') return 'bg-warning text-dark';
        if (lowerStatus === 'reserved') return 'bg-success';
        if (lowerStatus === 'occupied') return 'bg-primary'; 
        if (lowerStatus === 'cancelled') return 'bg-danger';
        if (lowerStatus === 'archived') return 'bg-secondary';
        return 'bg-info';
    }

    // -------------------------
    // Event delegation for table actions (MODIFIED to handle new button)
    // -------------------------
    tableBody.addEventListener('click', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;
        const id = tr.dataset.id;

        if (e.target.closest('.btn-edit')) {
            openEditModal(id);
        } else if (e.target.closest('.btn-confirm-reserve')) {
            confirmReservation(id); // <-- New function call
        } else if (e.target.closest('.btn-cancel')) {
            prepareCancelModal(id);
        } else if (e.target.closest('.btn-archive-delete')) {
            prepareArchiveDeleteModal(id);
        } else if (e.target.closest('.btn-restore')) {
            updateReservationStatus(id, 'pending', 'Restoring reservation to Pending status.');
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

        paginationInfo.textContent = `Showing ${startEntry} to ${endEntry} of ${totalEntries} entries`;

        updatePaginationControls(currentPage, totalPages);
    }

    function updatePaginationControls(current, total) {
        paginationControls.innerHTML = '';

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${current === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<button class="page-link" ${current === 1 ? 'disabled' : ''} aria-label="Previous"><span aria-hidden="true">&laquo;</span></button>`;
        prevLi.addEventListener('click', () => {
            if (current > 1) {
                currentPage = current - 1;
                renderTable();
                updatePagination();
            }
        });
        paginationControls.appendChild(prevLi);

        // Page numbers logic here (similar to prior implementation)
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
        nextLi.innerHTML = `<button class="page-link" ${current === total ? 'disabled' : ''} aria-label="Next"><span aria-hidden="true">&raquo;</span></button>`;
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
    document.getElementById('editReservationDate').value = formatDateForInput(rec.reservationDate);
    // Helper to format date as YYYY-MM-DD for input[type=date]
    function formatDateForInput(dateStr) {
        if (!dateStr) return '';
        // If already in YYYY-MM-DD, return as is
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
        // Try to parse and format
        const d = new Date(dateStr);
        if (isNaN(d)) return '';
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${d.getFullYear()}-${month}-${day}`;
    }
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
        burialDepthField.style.display = (lotType === '4' || lotType === '5') ? 'none' : 'block';
    }
    document.getElementById('editLotType')?.addEventListener('change', toggleBurialDepthUI);

    // -------------------------
    // Submit edit (updateReservation.php)
    // -------------------------
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const reservationId = editModalEl.dataset.id;
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
            status: null
        };
        console.log('Form data payload:', payload);

        const formData = new FormData();
        for (const key in payload) {
            formData.append(key, payload[key]);
        }

        try {
            const response = await fetch('../../../../cms.api/updateReservation.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.text();
            console.log('Server response:', result);
            // Refresh the page after update
            window.location.reload();
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // -------------------------
    // NEW: Confirm Reservation/Set Occupied Function (Calls consolidated PHP)
    // -------------------------
    async function confirmReservation(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
        if (!rec) return showToast('Reservation not found', 'danger');
        
        if (!confirm(`Confirming reservation for ${rec.clientName} (Lot ${rec.lotNumber}). This will set status to 'Reserved' or 'Occupied' and synchronize the lot status. Continue?`)) {
            return;
        }

        showToast('Checking burial records and confirming status...', 'info');

        try {
            // Call the single, consolidated PHP file: confirmReservationAndLot.php
            const res = await fetch(API_BASE + 'confirmReservationAndLot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reservationID: parseInt(reservationId, 10)
                }),
                credentials: 'same-origin'
            });
            const json = await res.json();

            if (json.status === 'success') {
                showToast(json.message || 'Reservation confirmed!', 'success');
                await fetchReservations();
            } else {
                showToast(json.message || 'Confirmation failed.', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error during reservation confirmation', 'danger');
        }
    }

    // -------------------------
    // Cancel reservation modal preparation
    // -------------------------
    function prepareCancelModal(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
        if (!rec) return showToast('Reservation not found', 'danger');

        const modalText = document.getElementById('cancelModalText');
        modalText.innerHTML = `You are about to cancel the reservation for <strong>${escapeHtml(rec.clientName)}</strong> (Lot ${escapeHtml(rec.lotNumber)}). Confirm the cancellation now?`;
        confirmCancelBtn.dataset.id = reservationId;
        cancelModal.show();
    }

    // Actual cancel submission
    confirmCancelBtn.addEventListener('click', () => {
        const reservationId = confirmCancelBtn.dataset.id;
        // Use generic status update, status = 'Cancelled'
        updateReservationStatus(reservationId, 'Cancelled', 'Cancelling reservation...'); 
        cancelModal.hide();
    });

    // -------------------------
    // Archive/Delete modal preparation
    // -------------------------
    function prepareArchiveDeleteModal(reservationId) {
        const rec = reservations.find(r => String(r.reservationId) === String(reservationId)); 
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
        // Use generic status update, status = 'Archived'
        updateReservationStatus(reservationId, 'Archived', 'Archiving reservation...');
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
            // Pass all fields plus the status to updateReservation.php as form data
            const formData = new FormData();
            formData.append('reservationID', parseInt(reservationId, 10));
            formData.append('status', newStatus);
            formData.append('clientName', rec.clientName);
            formData.append('address', rec.address);
            formData.append('contactNumber', rec.contactNumber);
            formData.append('reservationDate', rec.reservationDate);
            formData.append('area', rec.area);
            formData.append('block', rec.block);
            formData.append('rowNumber', rec.rowNumber);
            formData.append('lotNumber', rec.lotNumber);
            formData.append('lotTypeID', rec.lotTypeID);
            formData.append('burialDepth', rec.burialDepth);

            const res = await fetch(API_BASE + 'updateReservation.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const json = await res.json();

            if (json.success) {
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

        docModalEl.dataset.id = reservationId; 
        const docFilename = document.getElementById('docFilename');
        const imgPreview = document.getElementById('img-preview');
        const downloadLink = document.getElementById('downloadLink');
        const deleteBtn = document.getElementById('deleteBtn');

        // reset UI
        docFilename.textContent = '';
        imgPreview.classList.add('d-none');
        downloadLink.classList.add('disabled');
        deleteBtn.classList.add('disabled');
        imgPreview.src = '';

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
            const rec = reservations.find(r => String(r.reservationId) === String(reservationId));
            
            const res = await fetch(API_BASE + 'updateReservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reservationID: parseInt(reservationId, 10),
                    // Pass the file name to be deleted in the backend
                    clientValidId: '' 
                    // Pass other mandatory fields to satisfy the updateReservation.php structure
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