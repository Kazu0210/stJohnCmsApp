// --- Add Payment Modal Logic ---
const addPaymentBtn = document.getElementById('addPaymentBtn');
const addPaymentModal = document.getElementById('addPaymentModal');
const addPaymentForm = document.getElementById('addPaymentForm');
const addClientReservation = document.getElementById('addClientReservation');
const addMonthDue = document.getElementById('addMonthDue');
const addAmountPaid = document.getElementById('addAmountPaid');
const addPaymentMethod = document.getElementById('addPaymentMethod');
const addReference = document.getElementById('addReference');

// Fetch reservations for dropdown
async function populateReservationDropdown() {
    addClientReservation.innerHTML = '<option value="">Select Reservation</option>';
    try {
        const res = await fetch('/stJohnCmsApp/cms.api/fetchReservations.php');
        const data = await res.json();
        if (data.success && data.data) {
            data.data.forEach(r => {
                const label = `ID:${r.reservationId} | Lot: ${r.area}-${r.block}-${r.lotNumber} | User: ${r.userId}`;
                const opt = document.createElement('option');
                opt.value = r.reservationId;
                opt.textContent = label;
                addClientReservation.appendChild(opt);
            });
        }
    } catch (e) {
        addClientReservation.innerHTML = '<option value="">Error loading reservations</option>';
    }
}

if (addPaymentBtn) {
    addPaymentBtn.addEventListener('click', () => {
        populateReservationDropdown();
        // Set default month to current month
        const now = new Date();
        addMonthDue.value = now.toLocaleString('default', { month: 'long' });
        addAmountPaid.value = '';
        addPaymentMethod.value = '3'; // Cash
        addReference.value = '';
    });
}

if (addPaymentForm) {
    addPaymentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
    const formData = new FormData(addPaymentForm);
    // Map paymentMethodId to backend values (1: GCash, 2: Bank, 3: Cash)
    const paymentMethodId = formData.get('paymentMethodId');
    formData.set('paymentMethodId', paymentMethodId);
    // Add paymentType to backend
    const paymentType = formData.get('paymentType');
    formData.set('paymentType', paymentType);
    // Add dummy userId if needed (handled by backend session)
        try {
            const res = await fetch('/stJohnCmsApp/cms.api/save_payment.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const result = await res.json();
            if (result.status === 'success') {
                alert('Payment added successfully!');
                addPaymentForm.reset();
                var modal = bootstrap.Modal.getInstance(addPaymentModal);
                modal && modal.hide();
                // Optionally reload payment records table
                location.reload();
            } else {
                alert(result.message || 'Failed to add payment.');
            }
        } catch (err) {
            alert('Error submitting payment.');
        }
    });
}
// adminFinancial.js
document.addEventListener('DOMContentLoaded', function () {

    // --- 1. MOCK DATA & STATE ---

    const today = new Date();
    const months = [];
    // Generate the last 12 month labels for dropdown and chart
    for (let i = 0; i < 12; i++) {
        const d = new Date(today.getFullYear(), today.getMonth() - i, 1);
        months.push(d.toLocaleString('en-US', { year: 'numeric', month: 'long' }));
    }

    // Mock file objects for proof simulation
    const mockFile = { name: 'GCash-05-2024.jpg', type: 'image/jpeg', dataURL: 'mock-image-data-url' };
    const mockPDF = { name: 'BankTransfer-04-2024.pdf', type: 'application/pdf', dataURL: 'mock-pdf-data-url' };
    
    // MOCK PDF.js implementation for demonstration. 
    // In a real app, you would load the PDF.js library.
    if (typeof window.pdfjsLib === 'undefined') {
        window.pdfjsLib = {
            getDocument: (url) => ({
                promise: new Promise(resolve => {
                    console.log(`Simulating PDF.js loading for: ${url}`);
                    // Simulate a simplified PDF object
                    resolve({
                        getPage: (pageNo) => ({
                            then: (callback) => {
                                callback({
                                    getViewport: (options) => ({ height: 500, width: 700 }),
                                    render: (context) => ({ promise: Promise.resolve() })
                                });
                            }
                        })
                    });
                })
            }),
            GlobalWorkerOptions: { workerSrc: 'path/to/pdf.worker.js' }
        };
    }

    // Mock payment records using the specified status conditions:
    let paymentRecords = [
        // Pending Digital Payment (Needs Validation)
        { id: 100, clientName: "Jane M. Smith", lot: "A-12-03-04", monthDue: months[0], amountPaid: 1500.00, method: 'GCash', reference: 'GC-REF-1001', status: 'Pending', proof: mockFile, date: new Date(today.getFullYear(), today.getMonth(), 5) },
        // Deferred Status (Client chose to skip payment)
        { id: 101, clientName: "Peter Jones", lot: "B-05-01-01", monthDue: months[0], amountPaid: 0.00, method: 'N/A', reference: 'N/A', status: 'Deferred', proof: null, date: new Date(today.getFullYear(), today.getMonth(), 1) },
        // Paid Status (Exact Installment) - PDF Example
        { id: 102, clientName: "Mary Williams", lot: "C-18-02-02", monthDue: months[1], amountPaid: 1500.00, method: 'Bank Transfer', reference: 'BT-REF-9005', status: 'Paid', proof: mockPDF, date: new Date(today.getFullYear(), today.getMonth() - 1, 10) },
        // Pending Payment (Overdue)
        { id: 103, clientName: "Overdue Client", lot: "E-07-04-05", monthDue: months[5], amountPaid: 0.00, method: 'N/A', reference: 'N/A', status: 'Pending', proof: null, date: new Date(today.getFullYear(), today.getMonth() - 5, 1) },
        // Partially Paid Status (Advance Payment)
        { id: 104, clientName: "Advance Client", lot: "D-01-01-01", monthDue: months[2], amountPaid: 3000.00, method: 'GCash', reference: 'ADV-REF-500', status: 'Partially Paid', proof: mockFile, date: new Date(today.getFullYear(), today.getMonth() - 2, 10) },
        // Completed Status (Final payment)
        { id: 105, clientName: "Completed Client", lot: "F-10-01-02", monthDue: months[11], amountPaid: 1500.00, method: 'Cash', reference: 'OR-5501', status: 'Completed', proof: null, date: new Date(today.getFullYear(), today.getMonth() - 11, 15) },
        // More mock data for chart representation
        ...Array(15).fill(null).map((_, i) => ({ 
            id: i + 107, clientName: `Client ${i + 7}`, lot: `Z-${(i % 5).toString().padStart(2, '0')}-${(i % 10).toString().padStart(2, '0')}-${(i % 3).toString().padStart(2, '0')}`, monthDue: months[(i % 12)], 
            amountPaid: (i % 3 === 0 ? 1500 : (i % 3 === 1 ? 3000 : 0)), 
            method: i % 3 === 0 ? 'GCash' : (i % 3 === 1 ? 'Bank Transfer' : 'Cash'), 
            reference: `REF-${i + 107}`, 
            status: i % 5 === 0 ? 'Pending' : (i % 5 === 1 ? 'Paid' : (i % 5 === 2 ? 'Partially Paid' : (i % 5 === 3 ? 'Deferred' : 'Completed'))),
            proof: (i % 5 === 0 || i % 5 === 1) ? mockFile : null,
            date: new Date(today.getFullYear(), today.getMonth() - (i % 12), (i % 20) + 1)
        }))
    ].map((record, index) => ({ ...record, id: record.id || index + 1 })); // Ensure all records have an ID

    let currentPage = 1;
    const recordsPerPage = 10;
    let currentFilteredRecords = paymentRecords;
    
    // --- 2. DOM ELEMENTS & MODALS ---
    const tableBody = document.getElementById('paymentTableBody');
    const searchInput = document.getElementById('paymentSearch');
    const statusFilter = document.getElementById('paymentStatusFilter');
    const monthFilter = document.getElementById('paymentMonthFilter');
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const proofViewerModal = new bootstrap.Modal(document.getElementById('proofViewerModal'));
    const cancelReservationModal = new bootstrap.Modal(document.getElementById('cancelReservationModal')); // Added

    // Pagination elements
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    
    // Hidden elements used for proof logic transfer
    const viewProofBtn = document.getElementById('viewProofBtn');
    const proofStatusValue = document.getElementById('proofStatusValue');

    // Logout Links
    const logoutLinks = document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile');
    
    // --- 3. LOGOUT LOGIC 🔥 NEW CODE FROM PROMPT ---


    const handleLogout = (e) => {
        e.preventDefault(); // Stop the link from navigating immediately
        
        if (!confirm("Are you sure you want to log out?")) {
            return; 
        }

        // Use the link's href attribute for redirection
        const clickedLink = e.currentTarget;
        const redirectPath = clickedLink.getAttribute('href');
        
        // Simple redirection logic
        if (redirectPath && redirectPath !== '#') {
            window.location.href = redirectPath; 
        } else {
            // Fallback to a known path if href is empty or not set
            // Assumes a relative path from current file
            window.location.href = "../../../frontend/auth/login/login.php";
        }
    };
    
    // --- 4. HELPER FUNCTIONS (Rest of the original code) ---

    const getRecordById = (id) => paymentRecords.find(r => r.id === id);

    const getStatusClass = (status) => {
        switch(status) {
            case 'Paid':
            case 'Partially Paid': 
            case 'Completed': return 'status-successful'; 
            case 'Pending': return 'status-pending';
            case 'Deferred': return 'status-unpaid'; 
            default: return '';
        }
    };
    
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
        toast.style.zIndex = '1056';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // --- 5. CORE LOGIC (Summary & Chart) ---

    function updateSummary() {
        const incomeRecords = paymentRecords
            .filter(r => (r.status === 'Paid' || r.status === 'Partially Paid' || r.status === 'Completed') && r.amountPaid > 0);

        const totalIncomeYTD = incomeRecords
            .filter(r => r.date.getFullYear() === today.getFullYear())
            .reduce((sum, r) => sum + r.amountPaid, 0);

        const incomeThisMonth = incomeRecords
            .filter(r => r.date.getMonth() === today.getMonth() && r.date.getFullYear() === today.getFullYear())
            .reduce((sum, r) => sum + r.amountPaid, 0);

        const pendingCount = paymentRecords.filter(r => r.status === 'Pending').length;
        const deferredCount = paymentRecords.filter(r => r.status === 'Deferred').length;

        document.getElementById('totalIncomeYTD').textContent = `₱${totalIncomeYTD.toFixed(2).toLocaleString('en-US')}`;
        document.getElementById('incomeThisMonth').textContent = `₱${incomeThisMonth.toFixed(2).toLocaleString('en-US')}`;
        document.getElementById('attentionCount').textContent = `${pendingCount} Pending / ${deferredCount} Deferred`;
        
        renderIncomeChart(incomeRecords);
    }
    
    function renderIncomeChart(records) {
        const ctx = document.getElementById('monthlyIncomeChart');
        if (!ctx) return;

        const monthlyData = {};
        const labels = [];
        for (let i = 11; i >= 0; i--) {
            const d = new Date(today.getFullYear(), today.getMonth() - i, 1);
            const monthLabel = d.toLocaleString('en-US', { year: 'numeric', month: 'short' }); 
            labels.push(monthLabel);
            monthlyData[monthLabel] = 0;
        }

        records.forEach(r => {
            const recordDate = r.date;
            const monthLabel = recordDate.toLocaleString('en-US', { year: 'numeric', month: 'short' });
            if (monthlyData.hasOwnProperty(monthLabel)) {
                monthlyData[monthLabel] += r.amountPaid;
            }
        });

        const dataValues = labels.map(label => monthlyData[label]);
        
        if (window.incomeChart) {
            window.incomeChart.destroy();
        }

        const goldColor = getComputedStyle(document.documentElement).getPropertyValue('--gold').trim();
        const infoColor = getComputedStyle(document.documentElement).getPropertyValue('--info').trim();

        window.incomeChart = new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Successful Payments (₱)',
                    data: dataValues,
                    backgroundColor: goldColor, 
                    borderColor: infoColor, 
                    borderWidth: 1,
                    borderRadius: 5,
                    hoverBackgroundColor: 'rgba(239, 191, 4, 0.8)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Income Amount (₱)' },
                        ticks: { callback: function(value) { return '₱' + value.toLocaleString(); } }
                    },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (c) => `₱${c.parsed.y.toFixed(2).toLocaleString('en-US')}` } }
                }
            }
        });
    }

    // --- 6. TABLE RENDERING & FILTERING ---

    function populateMonthFilter() {
        monthFilter.innerHTML = '<option value="all">Filter by Month (All)</option>';
        months.forEach(month => {
            const option = document.createElement('option');
            option.value = month;
            option.textContent = month;
            monthFilter.appendChild(option);
        });
    }

    function renderTable(data) {
        tableBody.innerHTML = '';
        const start = (currentPage - 1) * recordsPerPage;
        const end = start + recordsPerPage;
        const pageRecords = data.slice(start, end);

        const noMessage = document.getElementById('noPaymentsMessage');
        const totalPages = Math.ceil(data.length / recordsPerPage);

        noMessage.classList.add('d-none');
        // Check if the table-responsive element exists before trying to access its style
        const tableContainer = tableBody.closest('.table-responsive');
        if (tableContainer) {
            tableContainer.style.display = 'block';
        }

        if (pageRecords.length === 0) {
            if (tableContainer) {
                tableContainer.style.display = 'none';
            }
            noMessage.classList.remove('d-none');
            document.getElementById('pageInfo').textContent = `Page 0 of ${totalPages || 1}`;
            prevPageBtn.disabled = true;
            nextPageBtn.disabled = true;
            return;
        }

        pageRecords.forEach(record => {
            const row = tableBody.insertRow();
            row.dataset.recordId = record.id;
            
            row.innerHTML = `
                <td>${record.clientName}</td>
                <td>${record.lot}</td>
                <td>${record.monthDue}</td>
                <td>₱${record.amountPaid.toFixed(2).toLocaleString('en-US')}</td>
                <td>${record.method}</td>
                <td>${record.reference}</td>
                <td><span class="${getStatusClass(record.status)}">${record.status}</span></td>
                <td class="text-center">
                    <button class="action-btn btn-view-proof" title="View Proof" data-id="${record.id}" ${record.method === 'Cash' || !record.proof || record.method === 'N/A' ? 'disabled' : ''}><i class="fas fa-eye"></i></button>
                    <button class="action-btn btn-edit-payment" title="Edit/Validate Payment" data-id="${record.id}"><i class="fas fa-edit"></i></button>
                </td>
            `;
        });
        
        attachTableListeners();
        
        // Update Pagination Controls
        document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage >= totalPages;
    }

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const status = statusFilter.value;
        const month = monthFilter.value;

        const filtered = paymentRecords.filter(r => {
            const matchesSearch = r.clientName.toLowerCase().includes(searchTerm) || 
                                     r.lot.toLowerCase().includes(searchTerm) ||
                                     r.reference.toLowerCase().includes(searchTerm);
            
            const matchesStatus = status === 'all' || r.status === status;
            const matchesMonth = month === 'all' || r.monthDue === month;

            return matchesSearch && matchesStatus && matchesMonth;
        });

        currentFilteredRecords = filtered;
        currentPage = 1;
        renderTable(currentFilteredRecords);
    }
    
    // --- 7. MODAL HANDLERS ---
    
    function openPaymentModal(id) {
        const record = getRecordById(id);
        if (!record) return;

        // Populate form fields
        document.getElementById('editPaymentId').value = record.id;
        document.getElementById('recordClientName').textContent = record.clientName;
        document.getElementById('recordLot').textContent = record.lot;
        document.getElementById('editMonthDue').value = record.monthDue;
        document.getElementById('editAmountPaid').value = record.amountPaid.toFixed(2);
        document.getElementById('editPaymentMethod').value = record.method;
        document.getElementById('editStatus').value = record.status;
        document.getElementById('editReference').value = record.reference;

        // Logic for the HIDDEN proof elements (for consistency, though they are not visible)
        const hasProof = record.proof && record.method !== 'Cash' && record.method !== 'N/A';
        proofStatusValue.textContent = hasProof ? record.proof.name : (record.method === 'Cash' ? 'N/A (Cash/OR)' : 'None Uploaded');
        viewProofBtn.disabled = !hasProof;
        viewProofBtn.onclick = hasProof ? () => openProofViewerModal(record.id) : null;

        paymentModal.show();
    }

    // START OF MODIFIED PROOF VIEWER LOGIC
    function openProofViewerModal(id) {
        const record = getRecordById(id);
        if (!record || !record.proof || record.method === 'Cash' || record.method === 'N/A') {
            alert("No digital proof available for this record.");
            return; 
        }

        const proof = record.proof;
        const img = document.getElementById('proofImage'); // The <img> element
        const canvas = document.getElementById('proofCanvas'); // The <canvas> for PDF
        const placeholder = document.getElementById('proofPlaceholder'); // The message placeholder
        const downloadLink = document.getElementById('proofDownloadLink');
        const loadingMessage = document.getElementById('proofLoadingMessage'); // The loading element

        // 1. Reset all view elements
        img.classList.add('d-none');
        canvas.classList.add('d-none');
        placeholder.classList.add('d-none');
        loadingMessage.classList.add('d-none');
        
        // 2. Update modal header/details
        document.getElementById('proofClientName').textContent = record.clientName;

        // 3. Setup download link (always available if proof exists)
        downloadLink.disabled = false;
        downloadLink.href = proof.dataURL; 
        downloadLink.download = proof.name;

        // 4. Handle file preview based on type
        if (proof.type.startsWith('image/')) {
            
            // --- IMAGE PREVIEW LOGIC ---
            // For production, proof.dataURL should be the actual file path
            img.src = `https://via.placeholder.com/800x600/EFBF04/000000?text=Digital+Proof+for+${record.clientName}`; 
            img.classList.remove('d-none');
            
        } else if (proof.type === 'application/pdf' && window.pdfjsLib) {
            
            // --- PDF PREVIEW LOGIC (Requires PDF.js library) ---
            loadingMessage.classList.remove('d-none'); // Show loading indicator
            canvas.classList.remove('d-none');

            const pdfUrl = proof.dataURL; 
            
            window.pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
                
                // Get the first page
                pdf.getPage(1).then(page => {
                    const viewport = page.getViewport({ scale: 1.5 });
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    const renderContext = {
                        canvasContext: canvas.getContext('2d'),
                        viewport: viewport
                    };
                    page.render(renderContext).promise.then(() => {
                        loadingMessage.classList.add('d-none'); // Hide loading indicator on success
                    });
                });
            }).catch(error => {
                console.error('Error loading PDF:', error);
                loadingMessage.classList.add('d-none');
                placeholder.textContent = `Error loading PDF: ${error.message}. Please click Download.`;
                placeholder.classList.remove('d-none');
                canvas.classList.add('d-none');
            });

        } else {
            
            // --- Fallback for unsupported types (e.g., DOCX, XLS) ---
            placeholder.textContent = `File uploaded: ${proof.name}. Document type (${proof.type}) not viewable inline. Click Download.`;
            placeholder.classList.remove('d-none');
        }

        proofViewerModal.show();
    }
    // END OF MODIFIED PROOF VIEWER LOGIC

    // --- 8. Modal Save Logic Update ---
    function savePaymentChanges(e) {
        e.preventDefault();
        
        const id = parseInt(document.getElementById('editPaymentId').value);
        const record = getRecordById(id);
        if (!record) return;

        const newMethod = document.getElementById('editPaymentMethod').value;
        const newReference = document.getElementById('editReference').value.trim();
        const newStatus = document.getElementById('editStatus').value; 
        const newAmountPaid = parseFloat(document.getElementById('editAmountPaid').value);

        // Simple validation based on flow
        if ((newStatus === 'Paid' || newStatus === 'Partially Paid' || newStatus === 'Completed') && newAmountPaid <= 0) {
            alert("Successful status requires a payment amount greater than zero.");
            return;
        }
        if (newStatus === 'Deferred' && newAmountPaid > 0) {
             if (!confirm("Warning: Status is 'Deferred' but amount paid is greater than zero. Are you sure you want to proceed?")) {
                 return;
             }
        }
        
        // Update record
        record.amountPaid = newAmountPaid;
        record.method = newMethod;
        record.reference = newReference; 
        record.status = newStatus;

        paymentModal.hide();
        updateSummary(); 
        renderTable(currentFilteredRecords);
        showToast(`Record ${id} updated to status: ${newStatus}`, newStatus === 'Paid' || newStatus === 'Completed' ? 'success' : 'warning');
    }

    // --- 9. EVENT LISTENERS ---
    
    function attachTableListeners() {
        tableBody.querySelectorAll('.btn-edit-payment').forEach(btn => {
            btn.addEventListener('click', () => openPaymentModal(parseInt(btn.dataset.id)));
        });
        tableBody.querySelectorAll('.btn-view-proof').forEach(btn => {
            if (!btn.disabled) {
                btn.addEventListener('click', () => openProofViewerModal(parseInt(btn.dataset.id)));
            }
        });
    }

    // Filter and Pagination Listeners
    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    monthFilter.addEventListener('change', applyFilters);
    document.getElementById('clearFiltersBtn').addEventListener('click', () => {
        searchInput.value = '';
        statusFilter.value = 'all';
        monthFilter.value = 'all';
        applyFilters();
    });

    document.getElementById('prevPageBtn').addEventListener('click', () => {
        if (currentPage > 1) { currentPage--; renderTable(currentFilteredRecords); }
    });
    document.getElementById('nextPageBtn').addEventListener('click', () => {
        const totalPages = Math.ceil(currentFilteredRecords.length / recordsPerPage);
        if (currentPage < totalPages) { currentPage++; renderTable(currentFilteredRecords); }
    });

    document.getElementById('paymentForm').addEventListener('submit', savePaymentChanges);
    
 
    logoutLinks.forEach(link => {
        if (link) {
            link.addEventListener("click", handleLogout);
        }
    });

    // --- 10. INITIALIZATION ---
    populateMonthFilter();
    updateSummary();
    applyFilters();
});
