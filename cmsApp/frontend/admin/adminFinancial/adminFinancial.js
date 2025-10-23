// adminFinancial.js
document.addEventListener('DOMContentLoaded', function () {

    // --- 1. STATE MANAGEMENT ---
    let paymentRecords = [];
    let currentPage = 1;
    const recordsPerPage = 10;
    let currentFilteredRecords = [];
    
    // --- 2. DOM ELEMENTS & MODALS ---
    const tableBody = document.getElementById('paymentTableBody');
    const searchInput = document.getElementById('paymentSearch');
    const statusFilter = document.getElementById('paymentStatusFilter');
    const monthFilter = document.getElementById('paymentMonthFilter');
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const proofViewerModal = new bootstrap.Modal(document.getElementById('proofViewerModal'));
    const cancelReservationModal = new bootstrap.Modal(document.getElementById('cancelReservationModal'));

    // Pagination elements
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    
    // Hidden elements used for proof logic transfer
    const viewProofBtn = document.getElementById('viewProofBtn');
    const proofStatusValue = document.getElementById('proofStatusValue');

    // Logout Links
    const logoutLinks = document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile');
    
    // --- 3. LOGOUT LOGIC ---
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
            window.location.href = "../../../frontend/auth/login/login.php";
        }
    };
    
    // --- 4. API FUNCTIONS ---
    async function fetchFinancialData() {
        try {
            const response = await fetch('/stJohnCmsApp/cms.api/adminFinancialData.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                paymentRecords = data.data.payments;
                updateSummary(data.data.summary);
                renderIncomeChart(data.data.chartData);
                populateMonthFilter();
                applyFilters();
            } else {
                throw new Error(data.message || 'Failed to fetch financial data');
            }
        } catch (error) {
            console.error('Error fetching financial data:', error);
            showToast('Error loading financial data: ' + error.message, 'danger');
        }
    }
    
    // --- 5. HELPER FUNCTIONS ---
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

    // --- 6. SUMMARY & CHART FUNCTIONS ---
    function updateSummary(summary) {
        document.getElementById('totalIncomeYTD').textContent = `₱${summary.totalIncomeYTD.toFixed(2).toLocaleString('en-US')}`;
        document.getElementById('incomeThisMonth').textContent = `₱${summary.incomeThisMonth.toFixed(2).toLocaleString('en-US')}`;
        document.getElementById('attentionCount').textContent = `${summary.pendingCount} Pending / ${summary.deferredCount} Deferred`;
    }
    
    function renderIncomeChart(chartData) {
        const ctx = document.getElementById('monthlyIncomeChart');
        if (!ctx) return;

        const labels = chartData.map(item => item.month);
        const dataValues = chartData.map(item => item.amount);
        
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

    // --- 7. TABLE RENDERING & FILTERING ---
    function populateMonthFilter() {
        const months = [...new Set(paymentRecords.map(r => r.monthDue))].sort();
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
            
            const hasProof = record.document && record.method !== 'Cash' && record.method !== 'N/A';
            
            row.innerHTML = `
                <td>${record.clientName}</td>
                <td>${record.lot}</td>
                <td>${record.monthDue}</td>
                <td>₱${record.amountPaid.toFixed(2).toLocaleString('en-US')}</td>
                <td>${record.method}</td>
                <td>${record.reference}</td>
                <td><span class="${getStatusClass(record.status)}">${record.status}</span></td>
                <td class="text-center">
                    <button class="action-btn btn-view-proof" title="View Proof" data-id="${record.id}" ${!hasProof ? 'disabled' : ''}><i class="fas fa-eye"></i></button>
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
    
    // --- 8. MODAL HANDLERS ---
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

        // Logic for the proof elements
        const hasProof = record.document && record.method !== 'Cash' && record.method !== 'N/A';
        proofStatusValue.textContent = hasProof ? 'Document Available' : (record.method === 'Cash' ? 'N/A (Cash/OR)' : 'None Uploaded');
        viewProofBtn.disabled = !hasProof;
        viewProofBtn.onclick = hasProof ? () => openProofViewerModal(record.id) : null;

        paymentModal.show();
    }

    function openProofViewerModal(id) {
        const record = getRecordById(id);
        if (!record || !record.document || record.method === 'Cash' || record.method === 'N/A') {
            alert("No digital proof available for this record.");
            return; 
        }

        console.log('Opening proof viewer for record:', record);

        const img = document.getElementById('proofImage');
        const canvas = document.getElementById('proofCanvas');
        const placeholder = document.getElementById('proofPlaceholder');
        const downloadLink = document.getElementById('proofDownloadLink');
        const loadingMessage = document.getElementById('proofLoadingMessage');

        // Reset all view elements
        img.classList.add('d-none');
        canvas.classList.add('d-none');
        placeholder.classList.add('d-none');
        loadingMessage.classList.add('d-none');
        
        // Update modal header/details
        document.getElementById('proofClientName').textContent = record.clientName;

        // Setup download link
        downloadLink.disabled = false;
        downloadLink.href = `/stJohnCmsApp/cms.api/getDocument.php?doc=payment&id=${record.id}`;
        downloadLink.download = record.document;

        console.log('Document URL:', downloadLink.href);
        console.log('Document filename:', record.document);

        // Handle file preview based on file extension
        const fileExtension = record.document.split('.').pop().toLowerCase();
        console.log('File extension:', fileExtension);
        
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
            // Image preview
            img.src = `/stJohnCmsApp/cms.api/getDocument.php?doc=payment&id=${record.id}`;
            img.classList.remove('d-none');
            
            // Add error handling for image loading
            img.onerror = function() {
                console.error('Failed to load image:', img.src);
                img.classList.add('d-none');
                placeholder.textContent = `Failed to load image: ${record.document}. Click Download to view.`;
                placeholder.classList.remove('d-none');
            };
            
            img.onload = function() {
                console.log('Image loaded successfully');
            };
            
        } else if (fileExtension === 'pdf') {
            // PDF preview - show placeholder for now
            placeholder.textContent = `PDF Document: ${record.document}. Click Download to view.`;
            placeholder.classList.remove('d-none');
            
        } else {
            // Other file types
            placeholder.textContent = `File uploaded: ${record.document}. Document type not viewable inline. Click Download.`;
            placeholder.classList.remove('d-none');
        }

        proofViewerModal.show();
    }

    // --- 9. Modal Save Logic ---
    async function savePaymentChanges(e) {
        e.preventDefault();
        
        const id = parseInt(document.getElementById('editPaymentId').value);
        const record = getRecordById(id);
        if (!record) return;

        const newMethod = document.getElementById('editPaymentMethod').value;
        const newReference = document.getElementById('editReference').value.trim();
        const newStatus = document.getElementById('editStatus').value; 
        const newAmountPaid = parseFloat(document.getElementById('editAmountPaid').value);

        // Simple validation
        if ((newStatus === 'Paid' || newStatus === 'Partially Paid' || newStatus === 'Completed') && newAmountPaid <= 0) {
            alert("Successful status requires a payment amount greater than zero.");
            return;
        }
        if (newStatus === 'Deferred' && newAmountPaid > 0) {
             if (!confirm("Warning: Status is 'Deferred' but amount paid is greater than zero. Are you sure you want to proceed?")) {
                 return;
             }
        }
        
        try {
            // Show loading state
            const saveBtn = document.querySelector('#paymentModal .btn-primary');
            const originalText = saveBtn.textContent;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            
            // Send update to server
            const response = await fetch('/stJohnCmsApp/cms.api/updatePayment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    paymentId: id,
                    status: newStatus,
                    amountPaid: newAmountPaid,
                    paymentMethod: newMethod,
                    reference: newReference
                })
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                paymentModal.hide();
                
                // Refresh data from server to get updated status
                await fetchFinancialData();
                
                showToast(`Record ${id} updated to status: ${newStatus}`, newStatus === 'Paid' || newStatus === 'Completed' ? 'success' : 'warning');
            } else {
                throw new Error(result.message || 'Failed to update payment record');
            }
            
        } catch (error) {
            console.error('Error updating payment:', error);
            showToast('Error updating payment: ' + error.message, 'danger');
        } finally {
            // Restore button state
            const saveBtn = document.querySelector('#paymentModal .btn-primary');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    // --- 10. EVENT LISTENERS ---
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

    // --- 11. INITIALIZATION ---
    fetchFinancialData();
});