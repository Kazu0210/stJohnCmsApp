// Set up PDF.js worker source globally
if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
}

document.addEventListener('DOMContentLoaded', function () {
    // --- 1. DATA STATE (empty, to be loaded dynamically) ---
    let burialData = [];
    // PDF Viewer State
    let pdfDoc = null;
    let currentPage = 1;
    let totalPages = 0;
    let currentFileURL = null;

    // --- 2. DOM ELEMENTS & MODALS ---
    const tableBody = document.getElementById('burialTableBody');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const statusFilter = document.getElementById('statusFilter');
    const docModalEl = document.getElementById('docModal');
    const docModal = new bootstrap.Modal(docModalEl);
    const editModalEl = document.getElementById('editBurialModal');
    const editBurialModal = new bootstrap.Modal(editModalEl);
    const exhumeModal = new bootstrap.Modal(document.getElementById('exhumeModal'));
    const archiveOrDeleteModal = new bootstrap.Modal(document.getElementById('archiveOrDeleteModal'));
    
    // PDF Control Elements
    const pdfControls = document.getElementById('pdfControls');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const pageInfo = document.getElementById('pageInfo');
    
    // --- 3. HELPER FUNCTIONS ---
    const getRecordById = (id) => burialData.find(r => r.id === id);
    const formatDate = (dateString) => `${dateString.split(' ')[0]}<br><small class="text-muted">${dateString.split(' ')[1]}</small>`;
    const getCurrentTimestamp = () => new Date().toISOString().slice(0, 16).replace('T', ' ');
    
    /** Shows a temporary toast notification */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
        toast.style.zIndex = '1056'; // Higher than modals
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    /** Updates the key metrics display with animation */
    function updateMetrics() {
        const totalRecords = burialData.length;
        const activeRecords = burialData.filter(record => record.status === 'active').length;
        const exhumedRecords = burialData.filter(record => record.status === 'exhumed').length;
        const archivedRecords = burialData.filter(record => record.status === 'archived').length;

        // Update metrics with animation
        animateCounter('totalRecordsMetric', totalRecords);
        animateCounter('activeRecordsMetric', activeRecords);
        animateCounter('exhumedRecordsMetric', exhumedRecords);
        animateCounter('archivedRecordsMetric', archivedRecords);
    }

    /** Animates counter from current value to target value */
    function animateCounter(elementId, targetValue) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const startValue = parseInt(element.textContent) || 0;
        const duration = 1000; // 1 second
        const startTime = Date.now();

        const updateCounter = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.round(startValue + (targetValue - startValue) * easeOutQuart);
            
            element.textContent = currentValue;
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = targetValue; // Ensure final value is exact
            }
        };

        requestAnimationFrame(updateCounter);
    }

    /**
     * Handles the user logout process.
     * Prompts for confirmation and redirects to the login page.
     * This function replaces the simple loop listener below.
     */
    const handleLogout = (e) => {
        e.preventDefault(); // Stop the link from navigating immediately
        
        if (!confirm("Are you sure you want to log out?")) {
            return; 
        }

        // Use the link's href attribute for redirection
        const clickedLink = e.currentTarget;
        const redirectPath = clickedLink.getAttribute('href');
        
        // Simple redirection based on the href attribute
        if (redirectPath && redirectPath !== '#') {
            // In a real app, Firebase signOut() would happen here.
            console.log("Simulating logout and redirect...");
            window.location.href = redirectPath; 
        } else {
            // Fallback just in case the href attribute is malformed
            window.location.href = "../../auth/login/login.php";
        }
    };

    // --- 4. CORE LOGIC ---
    function renderTable(data) {
        tableBody.innerHTML = '';
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="13" class="text-center">No records found.</td></tr>';
            return;
        }
        data.forEach(record => {
            const hasDc = record.docs && record.docs['death-cert'];
            const hasBp = record.docs && record.docs['burial-permit'];
            const hasId = record.docs && record.docs['valid-id'];
            tableBody.insertAdjacentHTML('beforeend', `<tr data-record-id="${record.id}"><td>${record.name}</td><td>${record.burialDate}</td><td class="text-center"><button class="action-btn btn-view-doc" title="Death Certificate" data-doc-type="death-cert"><i class="fas fa-file-pdf ${hasDc ? 'text-primary' : 'text-muted'}"></i></button></td><td class="text-center"><button class="action-btn btn-view-doc" title="Burial Permit" data-doc-type="burial-permit"><i class="fas fa-file-image ${hasBp ? 'text-primary' : 'text-muted'}"></i></button></td><td class="text-center"><button class="action-btn btn-view-doc" title="Valid ID" data-doc-type="valid-id"><i class="fas fa-id-card ${hasId ? 'text-primary' : 'text-muted'}"></i></button></td><td>${record.area}</td><td>${record.block}</td><td>${record.rowNumber}</td><td>${record.lotNumber}</td><td class="text-center"><span class="status-badge status-${record.status}">${record.status}</span></td><td>${formatDate(record.submittedOn)}</td><td>${formatDate(record.updatedOn)}</td><td class="text-center action-buttons"><button class="action-btn btn-edit" title="Edit"><i class="fas fa-pencil-alt"></i></button><button class="action-btn btn-exhume" title="Exhume"><i class="fas fa-box-open"></i></button><button class="action-btn btn-archive" title="Archive/Delete"><i class="fas fa-archive"></i></button></td></tr>`);
        });
        attachTableListeners();
        initializeTooltips();
        updateMetrics(); // Update metrics whenever table is rendered
    }

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const status = statusFilter.value;
        renderTable(burialData.filter(r => (status === 'all' || r.status === status) && (r.name.toLowerCase().includes(searchTerm) || String(r.lotNumber).includes(searchTerm))));
    }
    
    function initializeTooltips() {
        [...document.querySelectorAll('[data-bs-toggle="tooltip"]')].forEach(el => bootstrap.Tooltip.getInstance(el)?.dispose());
        [...document.querySelectorAll('[title]')].forEach(el => { el.setAttribute('data-bs-toggle', 'tooltip'); new bootstrap.Tooltip(el); });
    }

    // --- 5. MODAL & PDF HANDLERS ---
    /** Renders a specific page of a loaded PDF document */
    function renderPage(num) {
        pdfDoc.getPage(num).then(page => {
            const canvas = document.getElementById('pdf-canvas');
            const context = canvas.getContext('2d');
            const containerWidth = document.getElementById('preview-container').clientWidth;
            const viewport = page.getViewport({ scale: 1.0 });
            const scale = containerWidth / viewport.width;
            const scaledViewport = page.getViewport({ scale: scale });
            canvas.height = scaledViewport.height; canvas.width = scaledViewport.width;
            page.render({ canvasContext: context, viewport: scaledViewport });
            pageInfo.textContent = `Page ${num} of ${totalPages}`;
            prevPageBtn.disabled = num <= 1;
            nextPageBtn.disabled = num >= totalPages;
        });
    }

    function showDocumentModal(recordId, docType) {
        const record = getRecordById(recordId);
        const file = record.docs ? record.docs[docType] : null;
        document.getElementById('currentDocRecordId').value = recordId;
        document.getElementById('currentDocType').value = docType;
        document.getElementById('docModalLabel').textContent = `${{ 'death-cert': 'Death Certificate', 'burial-permit': 'Burial Permit', 'valid-id': 'Valid ID' }[docType]} for ${record.name}`;
        if (currentFileURL) URL.revokeObjectURL(currentFileURL);
        
        // Reset previews and controls
        ['img-preview', 'pdf-canvas'].forEach(id => document.getElementById(id).classList.add('d-none'));
        pdfControls.classList.add('d-none');
        document.getElementById('no-doc-placeholder').classList.toggle('d-none', !!file);
        
        if (file) {
            currentFileURL = URL.createObjectURL(file);
            document.getElementById('docFilename').textContent = file.name;
            if (file.type.startsWith('image/')) {
                const img = document.getElementById('img-preview');
                img.src = currentFileURL;
                img.classList.remove('d-none');
            } else if (file.type === 'application/pdf') {
                pdfjsLib.getDocument(currentFileURL).promise.then(loadedPdf => {
                    pdfDoc = loadedPdf;
                    totalPages = pdfDoc.numPages;
                    currentPage = 1;
                    renderPage(currentPage);
                    document.getElementById('pdf-canvas').classList.remove('d-none');
                    if (totalPages > 1) pdfControls.classList.remove('d-none');
                });
            }
            document.getElementById('downloadLink').href = currentFileURL;
            document.getElementById('downloadLink').download = file.name;
        } else {
            document.getElementById('docFilename').textContent = "No file uploaded.";
        }
        document.getElementById('downloadLink').classList.toggle('disabled', !file);
        document.getElementById('deleteDocBtn').disabled = !file;
        docModal.show();
    }

    function openEditModal(recordId) {
        const record = getRecordById(recordId);
        document.getElementById('editRecordId').value = record.id;
        document.getElementById('editingRecordName').textContent = record.name;
        document.getElementById('editName').value = record.name;
        document.getElementById('editBurialDate').value = record.burialDate;
        document.getElementById('editArea').value = record.area;
        document.getElementById('editBlock').value = record.block;
        document.getElementById('editRowNumber').value = record.rowNumber;
        document.getElementById('editLotNumber').value = record.lotNumber;
        document.getElementById('editStatus').value = record.status;
        editBurialModal.show();
    }
    
    function saveChanges() {
        const recordId = parseInt(document.getElementById('editRecordId').value);
        const record = getRecordById(recordId);
        Object.assign(record, {
            name: document.getElementById('editName').value, burialDate: document.getElementById('editBurialDate').value,
            area: document.getElementById('editArea').value, block: parseInt(document.getElementById('editBlock').value),
            rowNumber: parseInt(document.getElementById('editRowNumber').value), lotNumber: parseInt(document.getElementById('editLotNumber').value),
            status: document.getElementById('editStatus').value, updatedOn: getCurrentTimestamp()
        });
        applyFilters();
        document.getElementById('editBurialForm').classList.add('d-none');
        document.getElementById('editSuccessMessage').classList.remove('d-none');
        document.getElementById('saveEditBtn').classList.add('d-none');
        document.getElementById('editCancelBtn').textContent = 'Close';
    }

    function openConfirmationModal(modal, recordId, nameElId, idElId) {
        const record = getRecordById(recordId);
        document.getElementById(nameElId).textContent = record.name;
        document.getElementById(idElId).value = record.id;
        modal.show();
    }

    function updateStatus(modal, idElId, newStatus) {
        const record = getRecordById(parseInt(document.getElementById(idElId).value));
        record.status = newStatus;
        record.updatedOn = getCurrentTimestamp();
        modal.hide();
        applyFilters();
    }
    
    // --- 6. EVENT LISTENERS ---
    function attachTableListeners() {
        tableBody.querySelectorAll('tr').forEach(row => {
            const recordId = parseInt(row.dataset.recordId);
            row.querySelector('.btn-edit').addEventListener('click', () => openEditModal(recordId));
            row.querySelector('.btn-exhume').addEventListener('click', () => openConfirmationModal(exhumeModal, recordId, 'exhumeRecordName', 'exhumeRecordId'));
            row.querySelector('.btn-archive').addEventListener('click', () => openConfirmationModal(archiveOrDeleteModal, recordId, 'archiveRecordName', 'archiveRecordId'));
            row.querySelectorAll('.btn-view-doc').forEach(btn => btn.addEventListener('click', () => showDocumentModal(recordId, btn.dataset.docType)));
        });
    }
    
    // 💥 Consolidated Logout Handler (Your preferred method with added confirmation)
    const logoutLinks = document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile');
    logoutLinks.forEach(link => {
        if (link) {
            link.addEventListener("click", handleLogout);
        }
    });
    // End Consolidated Logout Handler

    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    clearSearchBtn.addEventListener('click', () => { searchInput.value = ''; applyFilters(); });
    
    document.getElementById('saveEditBtn').addEventListener('click', saveChanges);
    document.getElementById('confirmExhumeBtn').addEventListener('click', () => updateStatus(exhumeModal, 'exhumeRecordId', 'exhumed'));
    document.getElementById('confirmArchiveBtn').addEventListener('click', () => updateStatus(archiveOrDeleteModal, 'archiveRecordId', 'archived'));
    document.getElementById('confirmDeleteBtn').addEventListener('click', () => updateStatus(archiveOrDeleteModal, 'archiveRecordId', 'deleted'));
    
    // PDF Navigation
    prevPageBtn.addEventListener('click', () => { if (currentPage > 1) renderPage(--currentPage); });
    nextPageBtn.addEventListener('click', () => { if (currentPage < totalPages) renderPage(++currentPage); });
    
    // Document File Actions
    document.getElementById('docFileInput').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const recordId = parseInt(document.getElementById('currentDocRecordId').value);
        const docType = document.getElementById('currentDocType').value;
        const record = getRecordById(recordId);
        record.docs[docType] = file;
        record.updatedOn = getCurrentTimestamp();
        applyFilters();
        showDocumentModal(recordId, docType); // Refresh modal view
        showToast('Document uploaded successfully!');
        e.target.value = null;
    });

    document.getElementById('deleteDocBtn').addEventListener('click', () => {
        if (!confirm('Are you sure you want to delete this document?')) return;
        const recordId = parseInt(document.getElementById('currentDocRecordId').value);
        const docType = document.getElementById('currentDocType').value;
        const record = getRecordById(recordId);
        delete record.docs[docType];
        record.updatedOn = getCurrentTimestamp();
        applyFilters();
        showDocumentModal(recordId, docType); // Refresh modal
        showToast('Document deleted.', 'danger');
    });
    
    // Reset Edit Modal on close
    editModalEl.addEventListener('hidden.bs.modal', () => {
        document.getElementById('editBurialForm').classList.remove('d-none');
        document.getElementById('editSuccessMessage').classList.add('d-none');
        document.getElementById('saveEditBtn').classList.remove('d-none');
        document.getElementById('editCancelBtn').textContent = 'Cancel';
        document.getElementById('editBurialForm').reset();
    });

    docModalEl.addEventListener('hidden.bs.modal', () => {
        if (currentFileURL) URL.revokeObjectURL(currentFileURL);
        currentFileURL = null; pdfDoc = null;
    });

    // --- 7. INITIALIZATION ---
    updateMetrics(); // Initialize metrics immediately
    applyFilters(); // This will also update metrics after rendering table
});
