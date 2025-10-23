/**
 * Payment Module (payment.js)
 * Manages lot selection, payment summary, history display, document preview, and form submission.
 */
document.addEventListener('DOMContentLoaded', () => {

    // --- 1. CONFIGURATION & CONSTANTS ---
    const API_BASE_URL = "/stJohnCmsApp/cms.api/";
    const RECORDS_PER_PAGE = 10;
    const MAX_PAYMENT_MONTHS = 50;

    // Payment Plan Constants
    const ANNUAL_INTEREST_RATE = 24; // 24% annual interest
    const MONTHLY_INTEREST_RATE = ANNUAL_INTEREST_RATE / 12; // 2% monthly

    // Initialize PDF.js worker
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = '//cdnjs.cloudflare.com/ajax/libs/pdf.js/4.0.379/pdf.worker.min.js';
    }

    // --- 2. UTILITY FUNCTIONS ---

    /** @param {string} id */
    const $ = id => document.getElementById(id);
    const errLog = (...args) => console.error('[PaymentModule]', ...args);
    const safeText = (el, text) => { if(el) el.textContent = text; };
    const formatCurrency = (num) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(num || 0));
    
    /** @param {string} methodId */
    const getMethodName = (methodId) => {
        switch(String(methodId)) {
            case '1': return 'GCash';
            case '2': return 'Bank Transfer';
            case '3': return 'Cash';
            default: return 'N/A';
        }
    };

    /**
     * Shows a Bootstrap toast notification.
     * @param {string} message 
     * @param {'success'|'danger'|'warning'|'info'} type 
     */
    function showToast(message, type = 'success') {
        if (!toastElement || !toastBody) return;
        
        toastElement.className = 'toast hide position-fixed top-0 end-0 p-3';
        const classes = { 'success': 'text-bg-success', 'danger': 'text-bg-danger', 'warning': 'text-bg-warning' };
        toastElement.classList.add(classes[type] || 'bg-light');
        toastBody.innerHTML = message;
        
        const bsToast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
        bsToast.show();
    };

    // --- 3. DOM ELEMENTS (CACHING) ---
    const lotPriceDisplay = $('lot-price');
    const monthlyPaymentDisplay = $('monthly-payment');
    const monthlyPaymentDesc = $('monthly-payment-description');
    const totalPaidDisplay = $('total-paid');
    const remainingBalanceDisplay = $('remaining-balance');
    const paymentTypeSelect = $('payment-type');
    const advancePaymentOptions = $('advance-payment-options');
    const monthsToPayInput = $('months-to-pay');
    const calculatedAmountInput = $('calculated-amount'); 
    const paymentHistoryBody = $('paymentHistoryBody');
    const paymentHistoryPagination = $('paymentHistoryPagination');
    const gcashDetails = $('gcash-details');
    const bankDetails = $('bank-details');
    const onlinePaymentFields = $('online-payment-fields');
    const paymentForm = $('payment-form');
    let lotSelect = $('lot-select') || document.querySelector('select[name="lot-select"]') || $('lotSelect') || null;
    const submitBtn = document.querySelector('button[type="submit"]');

    // Progress Bar
    const paymentProgressBar = $('paymentProgressBar');
    const paymentProgressText = $('paymentProgressText');

    // Toast
    const toastElement = document.getElementById("liveToast");
    const toastBody = toastElement ? toastElement.querySelector(".toast-body") : null;
    const toastTime = toastElement ? toastElement.querySelector("small") : null;
    const toast = toastElement ? new bootstrap.Toast(toastElement, { delay: 24 * 60 * 60 * 1000, autohide: false }) : null;

    // Modal / PDF Preview
    const docModalEl = document.getElementById('docModal');
    const docModal = docModalEl ? new bootstrap.Modal(docModalEl) : null;
    const docFilename = $('docFilename');
    const imgPreview = $('img-preview');
    const pdfCanvas = $('pdf-canvas');
    const pdfControls = $('pdfControls');
    const prevPageBtn = $('prevPage');
    const nextPageBtn = $('nextPage');
    const pageInfoSpan = $('pageInfo');
    const deleteBtn = $('deleteBtn');
    const replaceFileInput = $('replaceFileInput');
    const logoutLinks = document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile');


    // --- 4. GLOBAL STATE ---
    let allLots = [];
    let currentSelectedLot = null;
    let paymentHistoryData = {};
    let currentPage = 1;
    let countdownInterval = null;
    let pdfDoc = null;
    let pageNum = 1;
    let currentFileInput = null;

    // --- 5. INITIALIZATION & HELPERS ---
    
    logoutLinks.forEach(link => link.addEventListener('click', e => {
        e.preventDefault();
        window.location.href = '../../auth/login/login.php';
    }));

    async function loadUserName(){
        try{
            const res = await fetch(`${API_BASE_URL}displayname.php`, { credentials: 'include' });
            const data = await res.json();
            const nameEl = document.getElementById("user-name-display-desktop");
            if (nameEl) nameEl.textContent = (data && data.status === 'success' && data.fullName) ? data.fullName : 'Guest';
        } catch (e) {
            errLog('loadUserName error', e);
        }
    };
    
    function resetSummary(){
        safeText(lotPriceDisplay, '₱0.00');
        safeText(monthlyPaymentDisplay, '₱0.00');
        if (monthlyPaymentDesc) monthlyPaymentDesc.textContent = '';
        safeText(totalPaidDisplay, '₱0.00');
        safeText(remainingBalanceDisplay, '₱0.00');
        if (paymentHistoryBody) paymentHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Please select a lot to view its payment history.</td></tr>`;
        if (paymentHistoryPagination) paymentHistoryPagination.innerHTML = '';
        if (paymentProgressBar) paymentProgressBar.style.width = '0%';
        if (paymentProgressText) paymentProgressText.textContent = '0 Payments Done';
        currentSelectedLot = null;
    };

    /** Countdown to enforce 24-hour payment for pending reservations. */
    function startPaymentCountdown(reservationId) {
        const deadline = new Date(Date.now() + 24 * 60 * 60 * 1000); 

        if (countdownInterval) clearInterval(countdownInterval);

        function updateCountdown() {
            const diff = deadline - new Date();

            if (diff <= 0) {
                if (toastBody) toastBody.textContent = "Your payment time has expired!";
                clearInterval(countdownInterval);
                if(toast) toast.show();
                return;
            }

            const h = Math.floor(diff / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((diff % (1000 * 60)) / 1000);

            if (toastBody) toastBody.innerHTML = `You have **${h}h ${m}m ${s}s** left to pay this reservation!`;
            if (toastTime) toastTime.textContent = "Time Remaining";
        }

        updateCountdown();
        if (toast) toast.show();
        countdownInterval = setInterval(updateCountdown, 1000);
    };

    /** Stops the payment countdown timer. */
    function stopPaymentTimer() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
            if (toast) toast.hide();
        }
    };

    /** Calculates interest for overdue payments. */
    function calculateInterest(principal, monthsOverdue) {
        if (monthsOverdue <= 0) return 0;
        return principal * (MONTHLY_INTEREST_RATE / 100) * monthsOverdue;
    };
    
    /** Gets overall payment status including interest calculation. */
    function getPaymentStatusInfo(totalPaid, monthlyPayment, lotPrice, reservationDate) {
        const monthsPaid = monthlyPayment > 0 ? Math.floor(totalPaid / monthlyPayment) : 0;
        const monthsOverdue = Math.max(0, monthsPaid - MAX_PAYMENT_MONTHS);
        const remainingBalance = Math.max(0, lotPrice - totalPaid);
        const interest = monthsOverdue > 0 ? calculateInterest(remainingBalance, monthsOverdue) : 0;
        
        return {
            monthsPaid,
            monthsOverdue,
            remainingBalance,
            interest,
            totalWithInterest: remainingBalance + interest,
            isOverdue: monthsOverdue > 0
        };
    };

    // --- 6. CORE DATA LOADING & UI UPDATES ---

    async function loadReservedLots(){
        if (!lotSelect) return;
        lotSelect.innerHTML = `<option value="">Loading lots...</option>`;
        try {
            const res = await fetch(`${API_BASE_URL}getReservedLots.php`, { credentials: 'include' });
            const json = await res.json();
            
            let data = Array.isArray(json) ? json : (json && Array.isArray(json.data) ? json.data : []);
            allLots = data || [];

            if (allLots.length === 0){
                lotSelect.innerHTML = `<option value="">No lots reserved</option>`;
                resetSummary();
                return;
            }

            lotSelect.innerHTML = `<option value="">-- Select a Lot --</option>`;
            allLots.forEach(lot => {
                const id = lot.reservationId || lot.id || lot.reservation_id;
                if (!id) return;
                const status = String(lot.status).toLowerCase();
                const clientName = lot.clientName || lot.client_name || 'Client';
                const text = `${clientName} - ${lot.area} B${lot.block} R${lot.rowNumber} L${lot.lotNumber} (${lot.status})`;
                const opt = document.createElement('option');
                opt.value = id;
                opt.textContent = text;
                if (status === 'pending') opt.classList.add('text-danger', 'fw-bold');
                lotSelect.appendChild(opt);
            });
            
            // Auto-select the first lot if only one exists
            if (allLots.length === 1 && lotSelect.options.length > 1) {
                lotSelect.value = lotSelect.options[1].value;
                lotSelect.dispatchEvent(new Event('change'));
            }
        } catch (e) {
            errLog('loadReservedLots error', e);
            lotSelect.innerHTML = `<option value="">Error loading lots</option>`;
            resetSummary();
        }
    };

    /** Fetches payment history and updates the summary. */
    async function loadPaymentHistoryForReservation(reservationId){
        if (!reservationId) return;
        try {
            const url = `${API_BASE_URL}save_payment.php?mode=getPayments&reservationId=${encodeURIComponent(reservationId)}`;
            const res = await fetch(url, { credentials: 'include' });
            const json = await res.json();
            
            let payments = json && Array.isArray(json.data) ? json.data : (Array.isArray(json) ? json : []);
            
            payments = payments.map(p => ({
                ...p,
                amount: Number(p.amount || 0),
                datePaid: p.datePaid || p.date_paid || p.createdAt || p.created_at || null,
                document: p.document || p.file || p.document_path || null,
                methodId: p.paymentMethodId || p.methodId || p.paymentMethod || null,
                reference: p.reference || p.ref || '',
                status: p.status || 'Paid'
            })).sort((a, b) => new Date(b.datePaid).getTime() - new Date(a.datePaid).getTime());

            paymentHistoryData[reservationId] = payments;
            updatePaymentSummaryAndSchedule();

        } catch (e) {
            errLog('loadPaymentHistoryForReservation error', e);
            paymentHistoryData[reservationId] = [];
            updatePaymentSummaryAndSchedule();
        }
    };

    /** Updates financial displays, progress bar, and timer. */
    function updatePaymentSummaryAndSchedule(){
        if (!currentSelectedLot) return;

        const reservationId = String(currentSelectedLot.reservationId || currentSelectedLot.id || '');
        const payments = paymentHistoryData[reservationId] || [];
        const price = Number(currentSelectedLot.price || currentSelectedLot.lotPrice || 0);
        const monthly = Number(currentSelectedLot.monthlyPayment || currentSelectedLot.monthly_payment || 0);
        
        const totalPaid = payments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0);
        const remaining = Math.max(0, price - totalPaid);

        safeText(totalPaidDisplay, formatCurrency(totalPaid));
        safeText(remainingBalanceDisplay, formatCurrency(remaining));

        if (paymentProgressBar && paymentProgressText) {
            const amountProgressPercentage = price > 0 ? Math.min(100, (totalPaid / price) * 100) : 0;
            const paymentStatus = getPaymentStatusInfo(totalPaid, monthly, price, currentSelectedLot.reservationDate);
            
            paymentProgressBar.style.width = `${amountProgressPercentage}%`;
            
            let progressText = `${formatCurrency(totalPaid)} Paid (${formatCurrency(price)})`;
            progressText += ` | ${paymentStatus.monthsPaid} Payment${paymentStatus.monthsPaid !== 1 ? 's' : ''} Done (${MAX_PAYMENT_MONTHS} Total)`;
            
            if (paymentStatus.isOverdue) {
                progressText += ` | Interest: ${formatCurrency(paymentStatus.interest)}`;
            }
            if(amountProgressPercentage >= 100) {
              progressText = `Completed! ${formatCurrency(totalPaid)} Paid`;
            }
            paymentProgressText.textContent = progressText;
        }

        // Timer control based on payment status
        if (currentSelectedLot.reservationDate && payments.length === 0) {
            startPaymentCountdown(currentSelectedLot.reservationDate);
        } else if (payments.length > 0) {
            stopPaymentTimer();
        }

        renderPaginatedHistory(payments);
    };

    function renderPaginatedHistory(payments) {
        if (!paymentHistoryBody) return;

        const totalPages = Math.ceil(payments.length / RECORDS_PER_PAGE) || 1;
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * RECORDS_PER_PAGE;
        const end = start + RECORDS_PER_PAGE;
        const pagePayments = payments.slice(start, end);

        let html = '';
        if (pagePayments.length === 0) {
            html = `<tr><td colspan="6" class="text-center text-muted">No payments recorded yet.</td></tr>`;
        } else {
            pagePayments.forEach(p => {
                const dateText = p.datePaid ? new Date(p.datePaid).toLocaleDateString() : 'N/A';
                const status = p.status || 'Paid';
                const statusClass = status === 'Confirmed' || status === 'Paid' ? 'badge bg-success' : status === 'Pending' ? 'badge bg-warning' : 'badge bg-danger';
                const docUrl = p.document && !p.document.startsWith('http') ? API_BASE_URL + p.document : p.document; 

                const docText = p.document ? 
                    `<button type="button" class="btn btn-sm btn-info view-doc-btn" data-file-url="${docUrl}" data-file-name="${(p.document||'document').split('/').pop()}">View</button>` 
                    : '—';

                html += `
                    <tr>
                        <td>${dateText}</td>
                        <td><span class="${statusClass}">${status}</span></td>
                        <td>${formatCurrency(p.amount)}</td>
                        <td>${getMethodName(p.methodId)}</td>
                        <td>${p.reference || '—'}</td>
                        <td>${docText}</td>
                    </tr>
                `;
            });
        }
        paymentHistoryBody.innerHTML = html;

        // Pagination rendering logic would go here
    };

    // --- 7. PAYMENT CALCULATION & METHOD SELECTION ---

    function updateCalculatedAmount(){
        if (!currentSelectedLot || !calculatedAmountInput) {
            if (calculatedAmountInput) calculatedAmountInput.value = '₱0.00';
            return;
        }

        const type = paymentTypeSelect ? paymentTypeSelect.value : 'exact';
        const monthly = Number(currentSelectedLot.monthlyPayment || currentSelectedLot.monthly_payment || 0);
        // Use regex to safely extract number from the remaining balance display
        const remaining = parseFloat(remainingBalanceDisplay.textContent.replace(/[^\d\.]/g, '')) || 0; 

        if (advancePaymentOptions) advancePaymentOptions.style.display = 'none';
        
        let calculatedAmount = 0;
        let months = 1;

        if (type === 'exact') {
            calculatedAmount = monthly;
            months = 1;
        } else if (type === 'advance') {
            if (advancePaymentOptions) advancePaymentOptions.style.display = 'block';
            months = Math.max(1, parseInt(monthsToPayInput?.value || '1', 10));
            calculatedAmount = monthly * months;
            
            // Limit payment to the remaining balance
            if (calculatedAmount > remaining) {
                calculatedAmount = remaining;
                months = monthly > 0 ? Math.ceil(remaining / monthly) : 1; 
                if (monthsToPayInput) monthsToPayInput.value = months; 
            }
        }
        
        calculatedAmountInput.value = formatCurrency(calculatedAmount);
        
        // Ensure hidden fields for submission exist and are populated
        const ensureHiddenField = (id, name, value) => {
            let field = $(id);
            if (!field) {
                 field = document.createElement('input');
                 field.type = 'hidden';
                 field.id = id;
                 field.name = name;
                 paymentForm.appendChild(field);
            }
            field.value = value;
        };

        ensureHiddenField('raw-amount-to-pay', 'raw_amount_to_pay', calculatedAmount.toFixed(2));
        ensureHiddenField('months-being-paid', 'months_being_paid', months);
    };

    /**
     * Toggles the display of payment method details and sets the hidden input value.
     * Exposed globally for use in HTML onclick handlers.
     * @param {HTMLElement} element The clicked payment method card element.
     * @param {string} method 'gcash', 'bank', or 'cash'.
     */
    window.selectMethod = function(element, method) {
        document.querySelectorAll('.payment-method').forEach(card => {
            card.classList.remove('active', 'selected', 'border-primary', 'border-3');
            card.classList.add('border');
        });
        if (element) {
            element.classList.add('active', 'selected', 'border-primary', 'border-3');
            element.classList.remove('border');
        }

        const methodId = (method === 'gcash') ? '1' : ((method === 'bank') ? '2' : '3');
        const showOnlineFields = (method === 'gcash' || method === 'bank');
        
        if (onlinePaymentFields) onlinePaymentFields.style.display = showOnlineFields ? 'block' : 'none';
        if (gcashDetails) gcashDetails.style.display = (method === 'gcash') ? 'block' : 'none';
        if (bankDetails) bankDetails.style.display = (method === 'bank') ? 'block' : 'none';
        
        // Set hidden input for form submission
        let methodField = $('paymentMethodId');
        if (!methodField) {
            methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.id = 'paymentMethodId';
            methodField.name = 'paymentMethodId';
            paymentForm.appendChild(methodField);
        }
        methodField.value = methodId;
    };


    // --- 8. DOCUMENT PREVIEW LOGIC ---

    async function renderPdfPage(num){
        if (!pdfDoc || !pdfCanvas) return;
        
        const scale = 1.5; 
        
        try {
            const page = await pdfDoc.getPage(num);
            const viewport = page.getViewport({ scale });
            const ctx = pdfCanvas.getContext('2d');
            
            pdfCanvas.height = viewport.height;
            pdfCanvas.width = viewport.width;

            const renderCtx = { canvasContext: ctx, viewport: viewport };
            await page.render(renderCtx).promise;
            
            pageNum = num;
            if (pageInfoSpan) pageInfoSpan.textContent = `Page ${num} of ${pdfDoc.numPages}`;
            if (prevPageBtn) prevPageBtn.disabled = pageNum <= 1;
            if (nextPageBtn) nextPageBtn.disabled = pageNum >= pdfDoc.numPages;
        } catch (e) {
            errLog('Error rendering PDF page', e);
            showToast('Failed to render PDF page.', 'danger');
        }
    };

    /** * Shows the image or PDF preview in the modal.
     * @param {File|object|string} file - The File object, a {url, name} object, or a URL string.
     * @param {HTMLInputElement} [inputElement=null] - The originating form input element, if any.
     */
    async function showDocument(file, inputElement = null){
        if (!docModal) return showToast('Preview modal not available', 'error');
        
        let fileURL = '';
        let fileName = '';
        
        if (file instanceof File) {
            fileURL = URL.createObjectURL(file);
            fileName = file.name;
        } else if (typeof file === 'object' && file.url) { 
            fileURL = file.url;
            fileName = file.name;
        } else if (typeof file === 'string') {
             fileURL = file.startsWith('http') ? file : API_BASE_URL + file;
             fileName = fileURL.split('/').pop();
        } else {
             return showToast('Unsupported file for preview.', 'warning');
        }
        
        const ext = fileName.split('.').pop().toLowerCase();
        currentFileInput = inputElement; 

        if (docFilename) safeText(docFilename, fileName);
        if (imgPreview) { imgPreview.style.display = 'none'; imgPreview.src = ''; }
        if (pdfCanvas) pdfCanvas.style.display = 'none';
        if (pdfControls) pdfControls.style.display = 'none';

        // Toggle form-related controls (Delete/Replace)
        if (deleteBtn) deleteBtn.style.display = inputElement ? 'inline-block' : 'none';
        if (replaceFileInput && replaceFileInput.parentElement) replaceFileInput.parentElement.style.display = inputElement ? 'block' : 'none';

        if (['jpg','jpeg','png','gif','webp','bmp'].includes(ext)){
            if (imgPreview) {
                imgPreview.src = fileURL;
                imgPreview.style.display = 'block';
            }
            pdfDoc = null;
            docModal.show();
            return;
        }
        
        if (ext === 'pdf'){
            if (typeof pdfjsLib === 'undefined') {
                showToast('PDF preview unavailable (pdf.js not loaded).', 'error');
                return;
            }
            try {
                pdfDoc = await pdfjsLib.getDocument({ url: fileURL }).promise;
                pageNum = 1;
                await renderPdfPage(pageNum);
                if (pdfCanvas) pdfCanvas.style.display = 'block';
                if (pdfControls) pdfControls.style.display = 'flex';
                docModal.show();
            } catch (e) {
                errLog('PDF load error', e);
                showToast('Unable to load PDF preview.', 'error');
            }
            return;
        }
        showToast('Unsupported preview file type: ' + ext, 'warning');
    };


    // --- 9. EVENT LISTENERS ---

    // Lot selection listener
    lotSelect && lotSelect.addEventListener('change', async () => {
        const selectedId = lotSelect.value;
        if (!selectedId){ resetSummary(); return; }

        let selectedLot = allLots.find(l => String(l.reservationId || l.id || l.reservation_id || '') === String(selectedId));
        if (!selectedLot) { resetSummary(); return; }

        currentSelectedLot = selectedLot;
        currentPage = 1;

        const monthly = Number(selectedLot.monthlyPayment || selectedLot.monthly_payment || 0);
        const price = Number(selectedLot.price || selectedLot.lotPrice || 0);
        safeText(monthlyPaymentDisplay, formatCurrency(monthly));
        if (monthlyPaymentDesc) monthlyPaymentDesc.textContent = selectedLot.paymentDescription || '';
        safeText(lotPriceDisplay, formatCurrency(price));

        await loadPaymentHistoryForReservation(selectedId);
        updateCalculatedAmount();

        if (String(selectedLot.status).toLowerCase() === 'pending') {
            startPaymentCountdown(selectedId);
        } else {
             stopPaymentTimer();
        }
    });

    // Payment Calculation listeners
    paymentTypeSelect && paymentTypeSelect.addEventListener('change', updateCalculatedAmount);
    monthsToPayInput && monthsToPayInput.addEventListener('input', updateCalculatedAmount);

    // PDF Modal Navigation
    prevPageBtn && prevPageBtn.addEventListener('click', () => {
        if (pdfDoc && pageNum > 1) renderPdfPage(pageNum - 1);
    });
    nextPageBtn && nextPageBtn.addEventListener('click', () => {
        if (pdfDoc && pageNum < pdfDoc.numPages) renderPdfPage(pageNum + 1);
    });

    // Modal File Deletion Handler
    deleteBtn && deleteBtn.addEventListener('click', () => {
        if (!currentFileInput) return;
        currentFileInput.value = '';
        
        const fileNameSpan = document.getElementById(`${currentFileInput.id}-filename`);
        if (fileNameSpan) fileNameSpan.textContent = 'No file chosen';

        const viewBtn = document.querySelector(`.view-icon[data-target="${currentFileInput.id}"]`);
        if (viewBtn) viewBtn.style.display = 'none';

        docModal.hide();
        currentFileInput = null;
        showToast('Document cleared from form.', 'warning');
    });

    // Modal File Replacement Handler
    replaceFileInput && replaceFileInput.addEventListener('change', e => {
        if (!currentFileInput) return;
        const file = e.target.files[0];
        if (file) {
            // Transfer file to the main form input
            const dt = new DataTransfer();
            dt.items.add(file);
            currentFileInput.files = dt.files;

            const fileNameSpan = document.getElementById(`${currentFileInput.id}-filename`);
            if (fileNameSpan) fileNameSpan.textContent = file.name;
            
            const viewBtn = document.querySelector(`.view-icon[data-target="${currentFileInput.id}"]`);
            if (viewBtn) viewBtn.style.display = 'inline-block';

            docModal.hide();
            replaceFileInput.value = '';
            showToast('File replaced.', 'success');
        }
    });

    // Main form file input change listeners
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', e => {
            const f = e.target.files[0];
            const span = document.getElementById(`${input.id}-filename`);
            const viewBtn = document.querySelector(`.view-icon[data-target="${input.id}"]`);
            if (f) {
                if (span) span.textContent = f.name;
                if (viewBtn) viewBtn.style.display = 'inline-block';
                if (viewBtn) viewBtn.disabled = false;
            } else {
                if (span) span.textContent = 'No file chosen';
                if (viewBtn) viewBtn.style.display = 'none';
                if (viewBtn) viewBtn.disabled = true;
            }
        });
    });

    // Main form "View" button listener
    document.querySelectorAll(".view-icon").forEach(btn => {
        btn.addEventListener('click', (e) => {
            const target = e.currentTarget.dataset.target;
            const input = document.getElementById(target);
            if (!input || !input.files || input.files.length === 0) {
                return showToast('Please choose a file first.', 'warning');
            }
            showDocument(input.files[0], input);
        });
    });

    // History table "View" button delegated listener
    paymentHistoryBody && paymentHistoryBody.addEventListener('click', async (e) => {
        const btn = e.target.closest('.view-doc-btn');
        if (!btn) return;
        const fileUrl = btn.dataset.fileUrl;
        const fileName = btn.dataset.fileName || 'document';
        if (!fileUrl) {
            return showToast('Document URL not available.', 'danger');
        }
        showDocument({ url: fileUrl, name: fileName }, null);
    });

    // File upload icon click handler
    document.querySelectorAll(".file-upload-icon").forEach(btn => {
        btn.addEventListener('click', e => {
            const target = e.currentTarget.dataset.target;
            const input = document.getElementById(target);
            if (input) input.click();
        });
    });

    // Pagination Event Listener
    paymentHistoryPagination && paymentHistoryPagination.addEventListener('click', (e) => {
        const link = e.target.closest('.page-link');
        if (link) {
            e.preventDefault();
            const page = parseInt(link.dataset.page, 10);
            const reservationId = currentSelectedLot?.reservationId || currentSelectedLot?.id;
            const totalRecords = paymentHistoryData[reservationId]?.length || 0;
            const totalPages = Math.ceil(totalRecords / RECORDS_PER_PAGE) || 1;

            if (!isNaN(page) && page > 0 && page <= totalPages) {
                currentPage = page;
                updatePaymentSummaryAndSchedule();
            }
        }
    });

    // --- 10. FORM SUBMISSION ---

    paymentForm && paymentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        submitBtn && (submitBtn.disabled = true);
        submitBtn && (submitBtn.textContent = 'Submitting...');
        
        // Validation checks
        if (!currentSelectedLot) return showToast('Please select a lot first.', 'danger');
        const reservationId = String(currentSelectedLot.reservationId || currentSelectedLot.id || '');
        if (!reservationId) return showToast('Invalid reservation selected.', 'danger');
        
        const amount = parseFloat($('raw-amount-to-pay')?.value || '0');
        const monthsToPay = parseInt($('months-being-paid')?.value || '1', 10);
        const paymentMethodId = $('paymentMethodId')?.value;
        const reference = ($('gcash-ref')?.value || $('bank-ref')?.value || $('cash-ref')?.value || '').trim();
        const proofFileEl = (paymentMethodId === '1') ? $('gcash-proof') : (paymentMethodId === '2' ? $('bank-proof') : null);
        const proofFile = proofFileEl?.files?.[0] || null;

        if (amount <= 0) return showToast('Payment amount must be greater than zero.', 'danger');
        if (!paymentMethodId) return showToast('Please choose a payment method.', 'danger');
        if (paymentMethodId !== '3' && !proofFile) return showToast('Please upload proof of payment for GCash/Bank.', 'danger');

        const formData = new FormData();
        formData.append('reservationId', reservationId);
        formData.append('paymentMethodId', paymentMethodId);
        formData.append('month', new Date().toLocaleString('default', { month: 'long', year: 'numeric' }));
        formData.append('amount', amount.toFixed(2));
        formData.append('reference', reference);
        formData.append('monthsToPay', monthsToPay);
        if (proofFile) formData.append('proofFile', proofFile);
        
        try {
            const res = await fetch(`${API_BASE_URL}save_payment.php`, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const json = await res.json();
            
            if (json && json.status === 'success') {
                showToast(`✅ Payment submitted successfully! Status: ${json.data?.status || 'Pending'}.`, 'success');
                
                paymentForm.reset();
                updateCalculatedAmount();
                await loadPaymentHistoryForReservation(reservationId);

                // Refresh lot list to reflect status changes
                const lotIdToRefresh = lotSelect.value;
                await loadReservedLots();
                if (lotSelect.value === lotIdToRefresh) {
                    lotSelect.dispatchEvent(new Event('change'));
                }
                
                // Reset payment method selection UI
                window.selectMethod(null, 'none'); // Use the exposed helper to clear UI
                
            } else {
                throw new Error(json && (json.message || json.error) ? (json.message || json.error) : 'Unknown server error.');
            }
        } catch (err) {
            errLog('payment submit error', err);
            showToast(`❌ An error occurred while submitting your payment: ${err.message}`, 'danger');
        } finally {
            submitBtn && (submitBtn.disabled = false);
            submitBtn && (submitBtn.textContent = 'Submit Payment');
        }
    });


    // --- 11. INITIAL CALLS (Run on DOMContentLoaded) ---
    loadUserName().catch(errLog);
    loadReservedLots().catch(errLog);
    updateCalculatedAmount();

}); // The final closing block should now be correct.