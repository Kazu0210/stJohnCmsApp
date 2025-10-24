/* adminAppointment.js
    Handles client-side filtering, pagination, the Reschedule Modal, and Logout confirmation.
*/

// --- Logout Handler (NEW: Global function with confirmation) ---
window.handleLogout = () => {
    if (confirm("Are you sure you want to log out?")) {
        // In a real application, you would replace this with an API call to clear the session
        console.log("User logged out.");
        // Redirect to the login page (ensure this path is correct for your project structure)
        window.location.href = "../../../frontend/auth/login/login.php";
    }
};

// --- Fetch Appointment Counts ---
function fetchAppointmentCounts() {
    fetch('../../../../cms.api/fetchAppointments.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateAppointmentCounts(data.data);
            }
        })
        .catch(error => {
            // Handle fetch errors silently
        });
}


// --- Update Appointment Status via API ---
function updateAppointmentStatus(appointmentId, action, row) {
    const requestData = {
        appointmentId: parseInt(appointmentId),
        action: action
    };
    
    fetch('../../../../cms.api/updateAppointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update UI based on action
            let newStatus = '';
            switch(action) {
                case 'confirm': newStatus = 'confirmed'; break;
                case 'cancel': newStatus = 'cancelled'; break;
                case 'complete': newStatus = 'completed'; break;
            }
            
            if (newStatus && row) {
                updateStatusUI(row, newStatus);
                // Refresh the counts
                fetchAppointmentCounts();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating appointment: ' + error.message);
    });
}

// --- Update Appointment Reschedule via API ---
function updateAppointmentReschedule(appointmentId, newDate, newStart, newEnd, row) {
    const requestData = {
        appointmentId: parseInt(appointmentId),
        action: 'reschedule',
        newDate: newDate,
        newStart: newStart,
        newEnd: newEnd
    };
    
    fetch('../../../../cms.api/updateAppointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update the UI with new date and time range
            if (row) {
                const detailsCell = row.querySelector('.appointment-details');
                const readableDate = new Date(newDate).toLocaleDateString('en-US', { 
                    month: 'long', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
                
                const readableStart = formatDisplayTime(newStart);
                const readableEnd = formatDisplayTime(newEnd);
                
                detailsCell.innerHTML = `Date: ${readableDate} <br>Time: ${readableStart} - ${readableEnd}`;
                
                // Update status to 'scheduled'
                updateStatusUI(row, 'scheduled');
                // Refresh the counts
                fetchAppointmentCounts();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error rescheduling appointment: ' + error.message);
    });
}

// --- Status Update Helper (Global scope) ---
function updateStatusUI(row, newStatus) {
    row.setAttribute('data-status', newStatus);
    const badge = row.querySelector('.status-display');
    
    badge.classList.remove('confirmed', 'pending', 'cancelled', 'completed', 'scheduled');
    badge.classList.add(newStatus);
    badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
    
    // Only disable the button that represents the CURRENT status.
    row.querySelectorAll('.action-buttons button[data-action]').forEach(btn => {
        const action = btn.getAttribute('data-action');
        // Disable the button if its action matches the new (current) status
        if (action === 'confirm' && newStatus === 'confirmed') {
            btn.disabled = true;
        } else if (action === 'complete' && newStatus === 'completed') {
            btn.disabled = true;
        } else if (action === 'cancel' && newStatus === 'cancelled') {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
    });
    
    // Reinitialize table data if the function exists
    if (window.initializeTableData) {
        window.initializeTableData();
    }
}

// --- Fetch and Populate Appointment Table ---
function fetchAppointmentTable() {
    fetch('../../../../cms.api/fetchAppointments.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateAppointmentTable(data.data);
            }
        })
        .catch(error => {
            // Handle fetch errors silently
        });
}

// --- Populate Appointment Table with Real Data ---
function populateAppointmentTable(appointments) {
    const tableBody = document.getElementById('appointmentTableBody');
    
    if (!appointments || appointments.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No appointments found</td></tr>';
        return;
    }
    
    tableBody.innerHTML = '';
    
    appointments.forEach(appointment => {
        const row = document.createElement('tr');
        
        // Map status for display
        const statusText = getStatusDisplayText(appointment.status);
        const statusClass = getStatusClass(appointment.status);
        
    // Format date and time
    const appointmentDate = formatDisplayDate(appointment.dateRequested);
    const start = appointment.appointment_start_time || appointment.time || '';
    const end = appointment.appointment_end_time || '';
    const appointmentTime = start ? (end ? `${formatDisplayTime(start)} - ${formatDisplayTime(end)}` : formatDisplayTime(start)) : 'No time';
        const createdAt = formatDisplayDateTime(appointment.createdAt);
        
        row.setAttribute('data-status', statusClass);
        
        row.innerHTML = `
            <td>${appointment.clientName || 'N/A'}</td>
            <td>
                ${appointment.clientContactNumber ? `Phone: ${appointment.clientContactNumber}` : 'No phone'}<br>
                <span class="user-id-text">${appointment.clientAddress || 'No address'}</span>
            </td>
            <td class="appointment-details">
                Date: ${appointmentDate} <br>
                Time: ${appointmentTime}
            </td>
            <td>${appointment.purpose || 'No purpose specified'}</td>
            <td>${createdAt}</td>
            <td><span class="badge rounded-pill status-display ${statusClass}">${statusText}</span></td>
            <td class="action-buttons">
                <button class="btn btn-sm btn-success" data-action="confirm" data-id="${appointment.appointmentId}" 
                    ${statusClass === 'confirmed' ? 'disabled' : ''} title="Confirm">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-info text-white" data-action="reschedule" data-id="${appointment.appointmentId}" title="Reschedule">
                    <i class="fas fa-calendar-alt"></i>
                </button>
                <button class="btn btn-sm btn-secondary" data-action="complete" data-id="${appointment.appointmentId}" title="Mark Complete">
                    <i class="fas fa-flag-checkered"></i>
                </button>
                <button class="btn btn-sm btn-danger" data-action="cancel" data-id="${appointment.appointmentId}" title="Cancel">
                    <i class="fas fa-times"></i>
                </button>
            </td>
            <td><textarea class="form-control internal-note" placeholder="Add note..."></textarea></td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Reinitialize table data for filtering and pagination
    setTimeout(() => {
        if (window.initializeTableData) {
            window.initializeTableData();
        }
    }, 100);
}

// --- Helper Functions ---
function getStatusDisplayText(status) {
    if (status && status.trim() !== '') {
        return status.charAt(0).toUpperCase() + status.slice(1);
    }
    
    // Default status if empty
    return 'Pending';
}

function getStatusClass(status) {
    if (status && status.trim() !== '') {
        return status.toLowerCase();
    }
    
    // Default class if empty
    return 'pending';
}

function formatDisplayDate(dateString) {
    if (!dateString) return 'No date';
    
    const date = new Date(dateString);
    if (isNaN(date)) return dateString;
    
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function formatDisplayTime(timeString) {
    if (!timeString) return 'No time';
    
    // Handle time format (HH:MM:SS)
    const timeParts = timeString.split(':');
    if (timeParts.length >= 2) {
        const hours = parseInt(timeParts[0]);
        const minutes = timeParts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        return `${displayHours}:${minutes} ${ampm}`;
    }
    
    return timeString;
}

function formatDisplayDateTime(dateTimeString) {
    if (!dateTimeString) return 'No date';
    
    const date = new Date(dateTimeString);
    if (isNaN(date)) return dateTimeString;
    
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    }) + ' ' + date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Fetch and display appointment counts
    fetchAppointmentCounts();
    
    // Fetch and populate appointment table
    fetchAppointmentTable();
    
    const tableBody = document.getElementById('appointmentTableBody');
    const searchInput = document.getElementById('appointmentSearch');
    const filterStatus = document.getElementById('filterStatus');
    const filterDate = document.getElementById('filterDate');
    const pageInfo = document.getElementById('pageInfo');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const noLogsMessage = document.getElementById('noLogsMessage');
    const recordCountSpan = document.getElementById('recordCount');
    
    // Attach logout handler to the links (Requires changes in HTML, see note below)
    document.getElementById('logoutLinkDesktop')?.addEventListener('click', (e) => { e.preventDefault(); handleLogout(); });
    document.getElementById('logoutLinkMobile')?.addEventListener('click', (e) => { e.preventDefault(); handleLogout(); });

    // Modal elements
    const rescheduleModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
    const rescheduleClientName = document.getElementById('rescheduleClientName');
    const newAppointmentDate = document.getElementById('newAppointmentDate');
    const newAppointmentStart = document.getElementById('newAppointmentStart');
    const newAppointmentEnd = document.getElementById('newAppointmentEnd');
    const saveRescheduleBtn = document.getElementById('saveRescheduleBtn');
    
    const rowsPerPage = 10;
    let currentPage = 1;
    let allRows = []; 
    let filteredRows = [];
    let currentRescheduleRow = null;


    /** Converts a display date string (e.g., "May 5, 2025") to YYYY-MM-DD. */
    const formatDateToYMD = (dateString) => {
        const dateObj = new Date(dateString);
        if (isNaN(dateObj)) return null;
        return dateObj.toISOString().split('T')[0];
    };

    /** Initializes the table data by grabbing all rows from the DOM. */
    const initializeTableData = () => {
        allRows = Array.from(tableBody.querySelectorAll('tr'));
        filterAppointments();
    };
    
    // Make initializeTableData globally accessible
    window.initializeTableData = initializeTableData;

    // --- Core Filtering Logic (Kept the same) ---
    window.filterAppointments = () => {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const status = filterStatus.value.toLowerCase();
        const filterDateValue = filterDate.value;
        
        filteredRows = allRows.filter(row => {
            const rowText = row.textContent.toLowerCase();
            const rowStatus = row.getAttribute('data-status').toLowerCase();
            
            const searchMatch = !searchTerm || rowText.includes(searchTerm);
            const statusMatch = status === 'all' || rowStatus === status;

            let dateMatch = true;
            if (filterDateValue) {
                const dateCellText = row.querySelector('.appointment-details').textContent;
                const dateMatchRegex = dateCellText.match(/Date:\s*(\w+\s\d+,\s\d{4})/);
                
                if (dateMatchRegex && dateMatchRegex[1]) {
                    const rowDateYMD = formatDateToYMD(dateMatchRegex[1]);
                    dateMatch = rowDateYMD === filterDateValue;
                } else {
                    dateMatch = false; 
                }
            }
            return searchMatch && statusMatch && dateMatch;
        });

        currentPage = 1; 
        renderTable();
    };

    window.clearFilters = () => {
        searchInput.value = '';
        filterStatus.value = 'all';
        filterDate.value = '';
        filterAppointments();
    };

    // --- Table Rendering and Pagination (Kept the same) ---
    const renderTable = () => {
        tableBody.innerHTML = '';
        const rows = filteredRows;
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        
        currentPage = Math.min(Math.max(1, currentPage), totalPages || 1);
        
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageRows = rows.slice(start, end);

        pageRows.forEach(row => tableBody.appendChild(row));

        const displayTotalPages = totalPages || 1;
        pageInfo.textContent = `Page ${currentPage} of ${displayTotalPages}`;
        recordCountSpan.textContent = rows.length;

        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;

        if (rows.length === 0) {
            noLogsMessage.classList.remove('d-none');
        } else {
            noLogsMessage.classList.add('d-none');
        }
    };
    


    // --- Action Button Delegation ---
    tableBody.addEventListener('click', (e) => {
        const button = e.target.closest('button');
        // Allow click only if button is present AND NOT disabled
        if (!button || button.disabled) return; 

        const action = button.getAttribute('data-action');
        const row = button.closest('tr');
        const clientName = row.querySelector('td:first-child').textContent;
        currentRescheduleRow = row; 

        if (action === 'reschedule') {
                rescheduleClientName.textContent = clientName;
                newAppointmentDate.value = '';
                newAppointmentStart.value = '';
                newAppointmentEnd.value = '';
                rescheduleModal.show();
                return;
            }

        // Handle Confirm, Cancel, Complete actions
        let confirmationText = `Are you sure you want to ${action.toUpperCase()} the appointment for ${clientName}?`;
        if (action === 'confirm' && row.getAttribute('data-status') === 'confirmed') {
             // Allow re-confirmation without unnecessary alert if already confirmed
             updateStatusUI(row, 'confirmed');
             return;
        }
        
        if (confirm(confirmationText)) {
            const appointmentId = button.getAttribute('data-id');
            updateAppointmentStatus(appointmentId, action, row);
        }
    });
    
    // --- Save Reschedule Button Handler ---
    saveRescheduleBtn.addEventListener('click', () => {
        const newDate = newAppointmentDate.value;
        const newStart = newAppointmentStart.value;
        const newEnd = newAppointmentEnd.value;

        if (!newDate || !newStart || !newEnd) {
            alert("Please select a date, start time, and end time.");
            return;
        }

        if (newEnd <= newStart) {
            alert("End time must be later than start time.");
            return;
        }
        
        if (!currentRescheduleRow) return;

        // Get appointment ID from the reschedule button
        const rescheduleButton = currentRescheduleRow.querySelector('button[data-action="reschedule"]');
        const appointmentId = rescheduleButton.getAttribute('data-id');
        
        // Update appointment via API (send newStart/newEnd)
        updateAppointmentReschedule(appointmentId, newDate, newStart, newEnd, currentRescheduleRow);
        
        rescheduleModal.hide();
        currentRescheduleRow = null;
    });

    // --- Pagination Function (Globally accessible) ---
    window.changePage = (direction) => {
        currentPage += direction;
        renderTable();
    };

    // Initial application startup
    initializeTableData();
});