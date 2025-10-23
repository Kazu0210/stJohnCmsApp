// adminMaintenance.js

// Global variables
let maintenanceData = [];
const recordsPerPage = 10; 
let currentData = [];
let currentPage = 1;

// Function to fetch maintenance requests from the database
const fetchMaintenanceRequests = async () => {
    try {
        const response = await fetch('/stJohnCmsApp/cms.api/fetchMaintenanceRequests.php', {
            credentials: 'same-origin'
        });
        const result = await response.json();
        
        if (result.success) {
            // Transform the database data to match the expected format
            maintenanceData = result.data.map(request => {
                // Use area/block/lot from maintenance table first, fallback to reservation data
                const area = request.area || '';
                const block = request.block || '';
                const lotNumber = request.lotNumber || '';
                
                return {
                    id: parseInt(request.requestId),
                    client: request.clientName || 'Unknown Client',
                    contact: request.contactNumber ? 
                        `${request.contactNumber} / ${request.email || ''}` : 
                        request.email || 'No contact info',
                    lotNo: `${area}-${block}-${lotNumber}`.replace(/^-*|-*$/g, '') || 'Not specified',
                    areaBlock: area,
                    rowNo: block,
                    lotNoOnly: lotNumber,
                    service: request.serviceType || '',
                    requested: request.requestedDate || '',
                    submitted: request.createdAt ? request.createdAt.split(' ')[0] : '',
                    status: request.status || 'Pending',
                    notes: request.notes || '',
                    adminNotes: '', // This would need to be added to the database if needed
                    updatedAt: request.updatedAt || null // Include updatedAt for completion date
                };
            });
            
            currentData = maintenanceData;
            return maintenanceData;
        } else {
            return [];
        }
    } catch (error) {
        console.error('Error fetching maintenance requests:', error);
        return [];
    }
};

// Function to fetch maintenance counts from the database
const fetchMaintenanceCounts = async () => {
    try {
        const response = await fetch('/stJohnCmsApp/cms.api/getMaintenanceCounts.php', {
            credentials: 'same-origin'
        });
        const result = await response.json();
        
        if (result.success) {
            return result.data;
        } else {
            return null;
        }
    } catch (error) {
        console.error('Error fetching maintenance counts:', error);
        return null;
    }
};

// Function to update dashboard metrics using API counts
const updateDashboardMetricsFromAPI = async () => {
    const counts = await fetchMaintenanceCounts();
    
    if (counts) {
        const pendingElement = document.getElementById('pendingCount');
        const completedElement = document.getElementById('completedCount');
        const cancelledElement = document.getElementById('cancelledCount');
        
        if (pendingElement) pendingElement.textContent = counts.pending;
        if (completedElement) completedElement.textContent = counts.completed;
        if (cancelledElement) cancelledElement.textContent = counts.cancelled;
    }
};



// Function to refresh data and update UI
const refreshMaintenanceData = async () => {
    await fetchMaintenanceRequests();
    applyFilters();
    await updateDashboardMetricsFromAPI();
};

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return '-';
    try {
        if (dateTimeString.includes('T')) {
            const date = new Date(dateTimeString);
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            if (isNaN(date)) { return dateTimeString.replace('T', ' '); }
            return date.toLocaleDateString('en-US', options);
        }
        return new Date(dateTimeString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch {
        return dateTimeString;
    }
}

const getStatusBadge = (status) => {
    switch (status) {
        case "Pending": return `<span class="badge badge-pending">${status}</span>`;
        case "Completed": return `<span class="badge badge-completed">${status}</span>`;
        case "Cancelled": return `<span class="badge badge-cancelled">${status}</span>`;
        default: return status;
    }
};

const renderTable = (data, page) => {
    const tableBody = document.getElementById('maintenanceTableBody');
    tableBody.innerHTML = '';
    
    const start = (page - 1) * recordsPerPage;
    const end = start + recordsPerPage;
    const paginatedData = data.slice(start, end);

    paginatedData.forEach((record) => {
        const isCompletedOrCancelled = record.status === 'Completed' || record.status === 'Cancelled';
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${record.client}</td>
            <td class="text-center">${record.areaBlock}</td>
            <td class="text-center">${record.areaBlock}</td> 
            <td class="text-center">${record.rowNo}</td>
            <td class="text-center">${record.lotNoOnly}</td>
            <td>${formatDateTime(record.requested)}</td>
            <td class="text-center">${getStatusBadge(record.status)}</td>
            <td class="text-center text-nowrap">
                <button class="btn btn-sm btn-info text-white view-btn" title="View/Update" data-id="${record.id}" data-bs-toggle="modal" data-bs-target="#requestModal"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-success complete-btn" title="Mark Completed" data-id="${record.id}" ${isCompletedOrCancelled ? 'disabled' : ''}><i class="fas fa-check"></i></button>
                <button class="btn btn-sm btn-danger cancel-btn" title="Cancel Request" data-id="${record.id}" ${isCompletedOrCancelled ? 'disabled' : ''}><i class="fas fa-times"></i></button>
            </td>
        `;
        tableBody.appendChild(row);
    });

    updatePaginationControls(data.length, page);
};

const updateDashboardMetrics = (data) => {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    const pendingCount = data.filter(r => r.status === 'Pending').length;
    const completedCount = data.filter(r => {
        if (r.status === 'Completed') {
            // Use updatedAt if available (when status was changed to completed), otherwise use submitted date
            const completedDate = r.updatedAt ? new Date(r.updatedAt) : new Date(r.submitted);
            return completedDate.getMonth() === currentMonth && completedDate.getFullYear() === currentYear;
        }
        return false;
    }).length; 
    const cancelledCount = data.filter(r => r.status === 'Cancelled').length;
    document.getElementById('pendingCount').textContent = pendingCount;
    document.getElementById('completedCount').textContent = completedCount;
    document.getElementById('cancelledCount').textContent = cancelledCount;
};

const applyFilters = () => {
    const searchTerm = document.getElementById('searchClient').value.toLowerCase();
    const serviceFilter = document.getElementById('filterServiceType').value;
    const statusFilter = document.getElementById('filterStatus').value;
    currentData = maintenanceData.filter(record => {
        const matchesSearch = record.client.toLowerCase().includes(searchTerm) || 
                              record.lotNo.toLowerCase().includes(searchTerm) ||
                              record.areaBlock.toLowerCase().includes(searchTerm) ||
                              record.rowNo.toLowerCase().includes(searchTerm) ||
                              record.lotNoOnly.toLowerCase().includes(searchTerm);
        const matchesService = serviceFilter === "" || record.service === serviceFilter;
        const matchesStatus = statusFilter === "" || record.status === statusFilter;
        return matchesSearch && matchesService && matchesStatus;
    });
    currentPage = 1;
    renderTable(currentData, currentPage);
};

const clearFilters = () => {
    document.getElementById('searchClient').value = '';
    document.getElementById('filterServiceType').value = '';
    document.getElementById('filterStatus').value = '';
    applyFilters();
};

const showRequestDetails = (id) => {
    const record = maintenanceData.find(r => r.id === id);
    if (!record) return;
    document.getElementById('requestModalLabel').textContent = `Service Request #${record.id} Details`;
    document.getElementById('modalRequestId').value = record.id;
    document.getElementById('modalClientName').textContent = record.client;
    document.getElementById('modalClientContact').textContent = record.contact; 
    document.getElementById('modalLotLocation').textContent = `${record.areaBlock}-${record.rowNo}-${record.lotNoOnly} (Block: ${record.areaBlock}, Row: ${record.rowNo}, Lot: ${record.lotNoOnly})`;
    document.getElementById('modalServiceType').textContent = record.service;
    document.getElementById('modalRequestedDateTime').textContent = formatDateTime(record.requested);
    document.getElementById('modalSubmittedOn').textContent = record.submitted;
    document.getElementById('modalClientNotes').textContent = record.notes || 'No additional client notes.';
    document.getElementById('updateStatus').value = record.status;
    document.getElementById('scheduledDateTime').value = record.requested.includes('T') ? record.requested.substring(0, 16) : ''; 
    document.getElementById('adminNotes').value = record.adminNotes || '';
};

const saveStatusUpdate = async () => {
    const id = parseInt(document.getElementById('modalRequestId').value);
    const newStatus = document.getElementById('updateStatus').value;
    const newAdminNotes = document.getElementById('adminNotes').value;
    const scheduledDateTime = document.getElementById('scheduledDateTime').value;

    try {
        const response = await fetch('/stJohnCmsApp/cms.api/updateMaintenanceRequest.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                requestId: id,
                status: newStatus,
                adminNotes: newAdminNotes
            })
        });

        const result = await response.json();

        if (result.success) {
            // Refresh data from database and update counts
            await fetchMaintenanceRequests();
            applyFilters();
            await updateDashboardMetricsFromAPI();
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('requestModal'));
            modal.hide();
            alert(`Request #${id} status updated to: ${newStatus}.`);
        } else {
            alert(`Error updating status: ${result.message}`);
        }
    } catch (error) {
        alert('Error updating status. Please try again.');
    }
};

const updateRecordStatus = async (id, newStatus) => {
    if (!confirm(`Are you sure you want to mark Request #${id} as ${newStatus}?`)) return;

    try {
        const response = await fetch('/stJohnCmsApp/cms.api/updateMaintenanceRequest.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                requestId: id,
                status: newStatus
            })
        });

        const result = await response.json();

        if (result.success) {
            // Refresh data from database and update counts
            await fetchMaintenanceRequests();
            applyFilters();
            await updateDashboardMetricsFromAPI();
            alert(`Request #${id} successfully marked as ${newStatus}.`);
        } else {
            alert(`Error updating status: ${result.message}`);
        }
    } catch (error) {
        alert('Error updating status. Please try again.');
    }
}

const updatePaginationControls = (totalRecords, currentPage) => {
    const totalPages = Math.ceil(totalRecords / recordsPerPage);
    document.getElementById('recordTotal').textContent = totalRecords;
    document.getElementById('recordStart').textContent = totalRecords === 0 ? 0 : (currentPage - 1) * recordsPerPage + 1;
    document.getElementById('recordEnd').textContent = Math.min(currentPage * recordsPerPage, totalRecords);
    const ul = document.querySelector('.pagination');
    const prevPageItem = document.getElementById('prevPage');
    const nextPageItem = document.getElementById('nextPage');
    prevPageItem.classList.toggle('disabled', currentPage === 1 || totalRecords === 0);
    nextPageItem.classList.toggle('disabled', currentPage === totalPages || totalRecords === 0);
    ul.querySelectorAll('.page-item').forEach(item => {
        if (item.id !== 'prevPage' && item.id !== 'nextPage') {
            item.remove();
        }
    });
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.classList.add('page-item');
        if (i === currentPage) li.classList.add('active');
        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        ul.insertBefore(li, nextPageItem); 
    }
};

const handlePaginationClick = (e) => {
    e.preventDefault();
    const target = e.target.closest('.page-item');
    if (!target || target.classList.contains('disabled')) return;
    if (target.id === 'nextPage') {
        currentPage++;
    } else if (target.id === 'prevPage') {
        currentPage--;
    } else if (e.target.dataset.page) {
        currentPage = parseInt(e.target.dataset.page);
    } else {
        return;
    }
    renderTable(currentData, currentPage);
};

window.handleLogout = (e) => {
    e.preventDefault();
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "/stJohnCmsApp/cms.api/logout.php";
    }
};

document.addEventListener('DOMContentLoaded', async () => {
    // Show loading indicator
    const tableBody = document.getElementById('maintenanceTableBody');
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading maintenance requests...</td></tr>';
    }
    
    // Fetch maintenance requests from database
    await fetchMaintenanceRequests();
    
    // Initialize the page with fetched data
    if (maintenanceData.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No maintenance requests found.</td></tr>';
    }
    
    // Update dashboard metrics using API counts
    await updateDashboardMetricsFromAPI();
    
    // Also update with local data as fallback
    updateDashboardMetrics(maintenanceData);
    
    renderTable(maintenanceData, currentPage);
    
    // Set up event listeners
    document.getElementById('saveStatusBtn').addEventListener('click', saveStatusUpdate);
    document.querySelector('.pagination').addEventListener('click', handlePaginationClick);
    document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
    document.querySelectorAll('.auto-filter-trigger').forEach(element => {
        const eventType = (element.tagName === 'INPUT') ? 'keyup' : 'change';
        element.addEventListener(eventType, applyFilters);
    });
    document.getElementById('maintenanceTableBody').addEventListener('click', (e) => {
        const target = e.target.closest('button');
        if (!target) return;
        const id = parseInt(target.dataset.id);
        if (target.classList.contains('view-btn')) {
            showRequestDetails(id);
        } else if (target.classList.contains('complete-btn')) {
            updateRecordStatus(id, 'Completed');
        } else if (target.classList.contains('cancel-btn')) {
            updateRecordStatus(id, 'Cancelled');
        }
    });
    document.getElementById('logoutLinkDesktop')?.addEventListener('click', handleLogout);
    document.getElementById('logoutLinkMobile')?.addEventListener('click', handleLogout);
});
