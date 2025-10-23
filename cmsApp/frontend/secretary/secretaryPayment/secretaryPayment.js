document.addEventListener("DOMContentLoaded", () => {
    
    // --- CONSTANTS ---
    const RESERVATION_FEE = 5000; // Fixed initial reservation fee
    const INSTALLMENT_MONTHS = 50; 

    // --- MOCK PAYMENT DATA (Same data structure, simplified calculations) ---
    function calculateInstallment(total, fee) {
        return (total - fee) / INSTALLMENT_MONTHS;
    }
    
    let paymentRecords = [
        {
            id: 1,
            clientName: "Juan Dela Cruz",
            location: { area: "A", block: "1", row: "1", lot: "1" },
            lotType: "Regular Lot (₱50,000)",
            totalAmount: 50000,
            reservationFee: RESERVATION_FEE,
            monthlyPayment: calculateInstallment(50000, RESERVATION_FEE),
            reservationDate: "2025-10-09 14:00:00", 
            reservationStatus: "Pending",
            installmentStatus: "Pending", 
            installments: [],
            document: { name: "juan_gcash.jpg", url: "https://via.placeholder.com/150/007bff/FFFFFF?text=GCash+Proof" } 
        },
        {
            id: 2,
            clientName: "Maria Santos",
            location: { area: "B", block: "2", row: "5", lot: "10" },
            lotType: "Premium Lot (₱70,000)",
            totalAmount: 70000,
            reservationFee: RESERVATION_FEE,
            monthlyPayment: calculateInstallment(70000, RESERVATION_FEE),
            reservationDate: "2025-09-20 00:00:00", 
            reservationStatus: "Cancelled",
            installmentStatus: "N/A",
            installments: [],
            document: null 
        },
        {
            id: 3,
            clientName: "Jose Ramirez",
            location: { area: "C", block: "1", row: "10", lot: "1" },
            lotType: "Mausoleum (₱500,000)",
            totalAmount: 500000,
            reservationFee: RESERVATION_FEE,
            monthlyPayment: calculateInstallment(500000, RESERVATION_FEE),
            reservationDate: "2024-05-01 10:00:00",
            reservationStatus: "Reserved",
            installmentStatus: "Deferred",
            installments: [
                { datePaid: "2024-05-02", amount: RESERVATION_FEE, reference: "BT-RES-1", method: "Bank Transfer" }, 
                { datePaid: "2024-06-05", amount: calculateInstallment(500000, RESERVATION_FEE), reference: "BT-98765", method: "Bank Transfer" },
                { datePaid: "2024-07-05", amount: calculateInstallment(500000, RESERVATION_FEE), reference: "BT-87654", method: "Bank Transfer" }
            ],
            document: { name: "jose_bank.pdf", url: "https://via.placeholder.com/150/28a745/FFFFFF?text=Bank+Proof" }
        },
        {
            id: 4,
            clientName: "Lani Mercado",
            location: { area: "D", block: "1", row: "2", lot: "15" },
            lotType: "4-Lot Package (₱300,000)",
            totalAmount: 300000,
            reservationFee: RESERVATION_FEE,
            monthlyPayment: calculateInstallment(300000, RESERVATION_FEE),
            reservationDate: "2025-09-28 10:00:00",
            reservationStatus: "Reserved", 
            installmentStatus: "Paid", 
            installments: [
                { datePaid: "2025-09-28", amount: RESERVATION_FEE, reference: "TR-RES-1", method: "Bank Transfer" },
                { datePaid: "2025-10-01", amount: calculateInstallment(300000, RESERVATION_FEE), reference: "TR-88990", method: "Bank Transfer" }
            ],
            document: { name: "lani_transfer.png", url: "https://via.placeholder.com/150/ffc107/000000?text=Transfer+Proof" }
        },
        { id: 5, clientName: "Benjo Aquino", location: { area: "A", block: "2", row: "3", lot: "3" }, lotType: "Regular Lot (₱50,000)", totalAmount: 50000, reservationFee: RESERVATION_FEE, monthlyPayment: calculateInstallment(50000, RESERVATION_FEE), reservationDate: "2025-10-09 00:00:00", reservationStatus: "Pending", installmentStatus: "Pending", installments: [], document: null },
        { id: 6, clientName: "Sisa Torres", location: { area: "B", block: "3", row: "1", lot: "1" }, lotType: "Premium Lot (₱70,000)", totalAmount: 70000, reservationFee: RESERVATION_FEE, monthlyPayment: calculateInstallment(70000, RESERVATION_FEE), reservationDate: "2025-01-01 12:00:00", reservationStatus: "Completed", installmentStatus: "Paid", installments: [{ datePaid: "2025-01-02", amount: 70000, reference: "FULL-PAY", method: "Cash" }], document: null },
        { id: 7, clientName: "Carlos Reyes", location: { area: "A", block: "5", row: "7", lot: "7" }, lotType: "Regular Lot (₱60,000)", totalAmount: 60000, reservationFee: RESERVATION_FEE, monthlyPayment: calculateInstallment(60000, RESERVATION_FEE), reservationDate: "2025-10-08 09:00:00", reservationStatus: "Reserved", installmentStatus: "Pending", installments: [{ datePaid: "2025-10-08", amount: RESERVATION_FEE, reference: "GC-RES-1", method: "GCash" }], document: { name: "carlos_gcash.png", url: "https://via.placeholder.com/150/007bff/FFFFFF?text=GCash+Proof" } },
        { id: 8, clientName: "Gina Perez", location: { area: "N/A", block: "N/A", row: "N/A", lot: "N/A" }, lotType: "Exhumation (₱15,000)", totalAmount: 15000, reservationFee: 0, monthlyPayment: 0, reservationDate: "2025-10-09 17:00:00", reservationStatus: "Completed", installmentStatus: "Paid", installments: [{ datePaid: "2025-10-09", amount: 15000, reference: "OR-201", method: "Cash" }], document: { name: "gina_cash.png", url: "https://via.placeholder.com/150/20c997/FFFFFF?text=Cash+Receipt" } },
        { id: 9, clientName: "Hector Cruz", location: { area: "D", block: "5", row: "2", lot: "8" }, lotType: "Mausoleum Roadside (₱600,000)", totalAmount: 600000, reservationFee: RESERVATION_FEE, monthlyPayment: calculateInstallment(600000, RESERVATION_FEE), reservationDate: "2025-08-01 00:00:00", reservationStatus: "Reserved", installmentStatus: "Paid", installments: [{ datePaid: "2025-08-01", amount: RESERVATION_FEE, reference: "BT-RES-2", method: "Bank Transfer" }, { datePaid: "2025-09-05", amount: calculateInstallment(600000, RESERVATION_FEE), reference: "BT-77777", method: "Bank Transfer" }, { datePaid: "2025-10-05", amount: calculateInstallment(600000, RESERVATION_FEE), reference: "BT-66666", method: "Bank Transfer" }], document: null },
        { id: 10, clientName: "Ivy Lim", location: { area: "A", block: "4", row: "9", lot: "12" }, lotType: "Regular Lot (₱50,000)", totalAmount: 50000, reservationFee: RESERVATION_FEE, monthlyPayment: calculateInstallment(50000, RESERVATION_FEE), reservationDate: "2025-10-07 10:00:00", reservationStatus: "Cancelled", installmentStatus: "N/A", installments: [], document: null }
    ];

    // Duplicate records to ensure pagination works (Total 20 records)
    for (let i = 11; i <= 20; i++) {
        const base = paymentRecords[i - 11];
        paymentRecords.push({ ...base, id: i, clientName: `${base.clientName} (Dupe ${i})`, installments: [...base.installments] });
    }

    // --- 2. PAGINATION & STATE ---
    let currentPage = 1;
    const recordsPerPage = 10; 
    let filteredRecords = []; 

    // --- 3. DOM ELEMENTS ---
    const tableBody = document.getElementById("paymentTableBody");
    const filterClient = document.getElementById("filterClient");
    const filterStatus = document.getElementById("filterStatus");
    const filterInstallment = document.getElementById("filterInstallment");
    const paginationContainer = document.getElementById("paginationContainer");
    const filterForm = document.getElementById("filterForm");
    const manualPaymentForm = document.getElementById("manualPaymentForm");
    
    // Modals
    const manageModal = new bootstrap.Modal(document.getElementById("manageModal"));
    const clientNameInModal = document.getElementById("clientNameInModal");
    const manageContent = document.getElementById("manageContent");
    const documentModal = new bootstrap.Modal(document.getElementById("documentModal"));
    
    // Toast
    const toastEl = document.getElementById("appToast");
    const toastBody = document.getElementById("appToastBody");
    const toast = new bootstrap.Toast(toastEl);

    // --- 4. UTILITY FUNCTIONS ---
    function showToast(message) {
        toastBody.textContent = message;
        toast.show();
    }

    function getRecordById(id) {
        return paymentRecords.find(r => r.id === id);
    }
    
    // Determine the next payment due month and status
    function getNextDueInfo(record) {
        const now = new Date();
        const resDate = new Date(record.reservationDate);
        
        if (record.reservationStatus === 'Cancelled' || record.reservationStatus === 'Completed') {
            return 'N/A';
        }
        
        // 1. PENDING RESERVATION (24hr rule)
        if (record.reservationStatus === 'Pending Reservation') {
            const dueDate = new Date(resDate.getTime() + 24 * 60 * 60 * 1000);
            if (now > dueDate) {
                // Time's up! Auto-cancel.
                record.reservationStatus = 'Cancelled';
                return `Cancelled (24hr exceeded)`;
            }
            const diffMs = dueDate - now;
            const diffHours = Math.ceil(diffMs / (1000 * 60 * 60));
            return `Reserve Fee Due: ${dueDate.toLocaleDateString()} (${diffHours} hrs left)`;
        }
        
        // 2. RESERVED (Installment Logic)
        if (record.reservationStatus === 'Reserved') {
            const monthsPaid = record.installments.length;
            
            // Find the date the Reservation Fee was paid (the 1st installment)
            const resFeePayment = record.installments.find(i => i.amount === record.reservationFee);
            if (!resFeePayment) return `Error: Missing Res Fee Payment`;

            const installmentStart = new Date(resFeePayment.datePaid);
            
            // Calculate the date of the next monthly installment
            let nextDueDate = new Date(installmentStart);
            nextDueDate.setMonth(installmentStart.getMonth() + monthsPaid - 1); // Subtract 1 because the first payment covers the first month
            
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const nextDueMonth = monthNames[nextDueDate.getMonth()];

            // If the client is deferred, display deferred status
            if (record.installmentStatus === 'Deferred') {
                return `DEFERRED (Next due: ${nextDueMonth} ${nextDueDate.getFullYear()})`;
            }
            
            // Check if the current payment is overdue (simplistic check for mock)
            if (now > nextDueDate) {
                 return `OVERDUE (Next due: ${nextDueMonth} ${nextDueDate.getFullYear()})`;
            }
            
            return `Next Installment Due: ${nextDueMonth} ${nextDueDate.getFullYear()}`;
        }
        
        return 'N/A';
    }

    // --- 5. RENDER TABLE (UPDATED for Method column and button styling) ---
    function renderTable() {
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const recordsToDisplay = filteredRecords.slice(startIndex, endIndex);

        tableBody.innerHTML = "";
        if (recordsToDisplay.length === 0) {
            tableBody.innerHTML = `
                <tr><td colspan="13" class="text-center text-muted py-3">
                    <i class="fas fa-info-circle me-2"></i>No payment records found.
                </td></tr>`;
            return;
        }

        recordsToDisplay.forEach((record, index) => {
            const displayIndex = startIndex + index + 1;
            // Get the last payment made (index 0 is the most recent)
            const lastPayment = record.installments.length > 0 ? record.installments[0] : null; 
            
            // Use text-bg-secondary for neutral status badges
            const statusBadgeClass = 'badge text-bg-secondary';
            
            // Set action button classes to outline dark
            const actionBtnClass = 'btn btn-sm btn-outline-dark rounded-circle';
            
            // Document is only available if it's not a Cash payment and a document object exists
            const hasDocument = lastPayment && lastPayment.method !== 'Cash' && !!record.document;

            tableBody.insertAdjacentHTML(
                "beforeend",
                `
                <tr data-id="${record.id}" class="${record.reservationStatus === 'Cancelled' ? 'table-danger' : ''}">
                    <td>${displayIndex}</td>
                    <td>${record.clientName}</td>
                    <td>${record.location.area}</td>
                    <td>${record.location.block}</td>
                    <td>${record.location.row}</td>
                    <td>${record.location.lot}</td>
                    <td>${getNextDueInfo(record)}</td>
                    <td>${lastPayment ? lastPayment.datePaid : 'N/A'}</td>
                    <td>${lastPayment ? '₱' + lastPayment.amount.toLocaleString() : 'N/A'}</td>
                    <td>${lastPayment ? lastPayment.method : 'N/A'}</td>
                    <td>${lastPayment ? (lastPayment.method === 'Cash' ? '' : lastPayment.reference) : ''}</td>
                    <td><span class="${statusBadgeClass}">${record.reservationStatus}</span></td>
                    <td class="text-center">
                        <button class="${actionBtnClass} btn-view-manage me-1" data-id="${record.id}" title="Manage Payments">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button class="${actionBtnClass} btn-view-doc" data-id="${record.id}" title="View Proof of Payment" ${!hasDocument ? 'disabled' : ''}>
                            <i class="fas fa-file-invoice"></i>
                        </button>
                    </td>
                </tr>
                `
            );
        });
        
        renderPagination();
    }
    
    // --- 6. RENDER PAGINATION (unchanged) ---
    function renderPagination() {
        const totalRecords = filteredRecords.length;
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        paginationContainer.innerHTML = '';

        if (totalPages <= 1) return;

        let paginationHTML = `<ul class="pagination pagination-sm mb-0">`;
        paginationHTML += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;

        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) paginationHTML += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }

        paginationHTML += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
        paginationHTML += `</ul>`;
        
        paginationContainer.insertAdjacentHTML('beforeend', paginationHTML);
    }
    
    // --- 7. FILTER FUNCTION (Automatic filtering remains) ---
    window.applyFilters = function(e) {
        if (e) {
             if (e.type === 'submit') e.preventDefault();
        }
        
        // APPLY 24-HOUR CANCELLATION RULE
        paymentRecords.forEach(record => {
            const now = new Date();
            const resDate = new Date(record.reservationDate);
            const dueDate = new Date(resDate.getTime() + 24 * 60 * 60 * 1000);
            
            if (record.installments.length === 0 && record.reservationStatus === 'Pending' && now > dueDate) {
                record.reservationStatus = 'Cancelled';
                record.installmentStatus = 'N/A';
            }
        });

        const searchTerm = filterClient.value.toLowerCase();
        const status = filterStatus.value;
        const installment = filterInstallment.value;

        filteredRecords = paymentRecords.filter((r) => {
            // Concatenate location fields for searching
            const locationString = `${r.location.area}/${r.location.block}/${r.location.row}/${r.location.lot}`.toLowerCase();
            
            // Match client name OR location details
            const matchSearch = r.clientName.toLowerCase().includes(searchTerm) || locationString.includes(searchTerm);
            
            const matchStatus = !status || r.reservationStatus === status;
            const matchInstallment = !installment || r.installmentStatus === installment;
            
            return matchSearch && matchStatus && matchInstallment;
        });
        
        currentPage = 1;
        renderTable();
    }
    
    // --- 8. SHOW PAYMENT MANAGEMENT MODAL (Status badges updated to neutral color) ---
    function showManageModal(id) {
        const record = getRecordById(id);
        if (!record) return;

        clientNameInModal.textContent = record.clientName;
        
        const totalPaid = record.installments.reduce((sum, i) => sum + i.amount, 0);
        const remainingBalance = record.totalAmount - totalPaid;
        const installmentsPaid = record.installments.filter(i => i.amount === record.monthlyPayment).length;

        // Display summary of all payments made
        const installmentsHtml = record.installments.map(inst => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <strong>₱${inst.amount.toLocaleString()}</strong> on ${inst.datePaid} 
                    <small class="text-muted">(${inst.method})</small>
                </span>
                <span class="badge text-bg-secondary rounded-pill">${inst.reference || 'N/A'}</span>
            </li>
        `).join('');

        manageContent.innerHTML = `
            <h6 class="text-primary">${record.lotType} at ${record.location.area}/${record.location.block}/${record.location.row}/${record.location.lot}</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 border rounded text-center bg-light">
                        <p class="mb-0 text-muted">Total Lot Price</p>
                        <h5 class="fw-bold text-dark">₱${record.totalAmount.toLocaleString()}</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded text-center bg-success text-white">
                        <p class="mb-0">Total Paid</p>
                        <h5 class="fw-bold">₱${totalPaid.toLocaleString()}</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded text-center bg-danger text-white">
                        <p class="mb-0">Remaining Balance</p>
                        <h5 class="fw-bold">₱${remainingBalance.toLocaleString()}</h5>
                    </div>
                </div>
            </div>
            
            <p class="text-muted small">Current Status: <span class="badge text-bg-secondary">${record.reservationStatus}</span>, Installment Status: <span class="badge text-bg-secondary">${record.installmentStatus}</span></p>

            ${record.reservationStatus === 'Reserved' ? `
                <div class="alert alert-info border-0">
                    <i class="fas fa-info-circle me-2"></i> Monthly Installment: ₱${record.monthlyPayment.toLocaleString()} | <strong>Total Installments Paid: ${installmentsPaid} of ${INSTALLMENT_MONTHS}</strong>
                </div>
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-sm btn-${record.installmentStatus === 'Deferred' ? 'success' : 'warning'} me-2" data-action="${record.installmentStatus === 'Deferred' ? 'Un-defer' : 'Defer'}" data-id="${record.id}">
                        <i class="fas fa-pause me-1"></i> ${record.installmentStatus === 'Deferred' ? 'Mark as Active' : 'Mark as Deferred'}
                    </button>
                    <button class="btn btn-sm btn-primary" data-action="AddPayment" data-id="${record.id}">
                        <i class="fas fa-plus me-1"></i> Record New Payment
                    </button>
                </div>
            ` : ''}

            <h6 class="mt-4"><i class="fas fa-history me-2"></i>Payment History (Most Recent First)</h6>
            <ul class="list-group">${installmentsHtml || '<li class="list-group-item text-center text-muted">No recorded payments yet.</li>'}</ul>
        `;
        manageModal.show();
    }
    
    // --- 9. SHOW DOCUMENT MODAL (Unchanged functionality) ---
    function showDocument(id) {
        const record = getRecordById(id);
        const doc = record.document;
        
        const previewEl = document.getElementById("docPreview");
        const filenameEl = document.getElementById("docFilename");
        const downloadLink = document.getElementById("docDownloadLink");
        const errorEl = document.getElementById("docError");

        errorEl.style.display = 'none';
        previewEl.style.display = 'none';
        downloadLink.classList.add('disabled');

        if (doc && doc.url) {
            filenameEl.textContent = doc.name;
            previewEl.src = doc.url;
            previewEl.style.display = 'block';
            downloadLink.href = doc.url;
            downloadLink.download = doc.name;
            downloadLink.classList.remove('disabled');
        } else {
            filenameEl.textContent = "No document available";
            errorEl.style.display = 'block';
        }

        documentModal.show();
    }

    // --- 10. HANDLE MANUAL CASH ENTRY FORM (LOCATION FIELDS REMOVED FROM LOGIC) ---
    manualPaymentForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const newRecordId = paymentRecords.length + 1;
        const amount = parseFloat(document.getElementById("amountPaid").value);
        
        // **CLEANED:** Location fields are now permanently set to 'N/A' for manual cash entries.
        const defaultLocation = { area: 'N/A', block: 'N/A', row: 'N/A', lot: 'N/A' };

        const newPayment = {
            id: newRecordId,
            clientName: document.getElementById("clientName").value,
            location: defaultLocation, // Always N/A for general cash entry
            lotType: "Manual Cash Entry",
            totalAmount: amount,
            reservationFee: 0,
            monthlyPayment: 0,
            reservationDate: document.getElementById("paymentDate").value,
            reservationStatus: "Completed",
            installmentStatus: "Paid",
            installments: [{
                datePaid: document.getElementById("paymentDate").value,
                amount: amount,
                reference: '', // Blank for Cash
                method: "Cash"
            }],
            document: null
        };

        paymentRecords.unshift(newPayment);
        applyFilters();
        manualPaymentForm.reset();
        showToast("Cash payment recorded successfully!");
    });

    // --- 11. EVENT LISTENERS ---
    
    // Automatic Filtering on Input/Change
    filterClient.addEventListener('keyup', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterInstallment.addEventListener('change', applyFilters);
    
    filterForm.addEventListener("reset", () => {
        // Reset inputs and trigger filter
        setTimeout(() => applyFilters(), 0); 
    });

    // Pagination Click Listener (Delegated)
    paginationContainer.addEventListener('click', (event) => {
        event.preventDefault();
        const pageLink = event.target.closest('.page-link');
        if (!pageLink) return;

        const newPage = parseInt(pageLink.dataset.page);
        const totalPages = Math.ceil(filteredRecords.length / recordsPerPage);

        if (newPage > 0 && newPage <= totalPages) {
            currentPage = newPage;
            renderTable();
        }
    });

    // Table Action Clicks (Manage and Document)
    tableBody.addEventListener("click", (e) => {
        const manageBtn = e.target.closest(".btn-view-manage");
        const viewDocBtn = e.target.closest(".btn-view-doc");
        const row = e.target.closest("tr");
        if (!row) return;
        const id = parseInt(row.dataset.id);

        if (manageBtn) {
            showManageModal(id);
        } else if (viewDocBtn) {
            showDocument(id);
        }
    });
    
    // Deferred/Active status toggle logic inside the manage modal
    manageContent.addEventListener('click', (e) => {
        const actionBtn = e.target.closest('button[data-action]');
        if (!actionBtn) return;
        
        const recordId = parseInt(actionBtn.dataset.id);
        const record = getRecordById(recordId);
        const action = actionBtn.dataset.action;
        
        if (!record || record.reservationStatus !== 'Reserved') return;
        
        if (action === 'Defer') {
            record.installmentStatus = 'Deferred';
            showToast(`Reservation for ${record.clientName} marked as DEFERRED.`);
        } else if (action === 'Un-defer') {
            record.installmentStatus = 'Pending';
            showToast(`Reservation for ${record.clientName} marked as ACTIVE.`);
        } else if (action === 'AddPayment') {
             showToast(`Simulating payment addition for ${record.clientName}. Status updated.`);
             
             // MOCK: Add a mock payment and update status
             const nextAmount = record.installments.length === 0 ? record.reservationFee : record.monthlyPayment;
             const today = new Date().toISOString().split('T')[0];
             
             // If this is the first payment, change reservation status to 'Reserved'
             if (record.installments.length === 0) {
                record.reservationStatus = 'Reserved'; 
             }
             
             record.installments.unshift({ 
                 datePaid: today, 
                 amount: nextAmount, 
                 reference: 'MOCK-PAY-' + (record.installments.length + 1),
                 method: 'System-Mock'
             });
             
             record.installmentStatus = 'Paid';
        }

        manageModal.hide();
        applyFilters(); 
    });


    // --- 12. INITIALIZE ---
    applyFilters(); 
});