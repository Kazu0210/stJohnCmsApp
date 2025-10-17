document.addEventListener('DOMContentLoaded', () => {
    // Add event listener for payment method dropdown
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const gcashQrContainer = document.getElementById('gcashQrContainer');
    const bankQrContainer = document.getElementById('bankQrContainer');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            // Show/hide QR code containers based on selected numeric method id
            // 1 = GCash, 2 = Bank Transfer
            if (gcashQrContainer) {
                if (this.value === '1') {
                    gcashQrContainer.style.display = 'flex';
                    const img = gcashQrContainer.querySelector('img');
                    const label = gcashQrContainer.querySelector('div');
                    if (img) img.style.display = 'block';
                    if (label) label.style.display = 'block';
                } else {
                    gcashQrContainer.style.display = 'none';
                    const img = gcashQrContainer.querySelector('img');
                    const label = gcashQrContainer.querySelector('div');
                    if (img) img.style.display = 'none';
                    if (label) label.style.display = 'none';
                }
            }
            if (bankQrContainer) {
                if (this.value === '2') {
                    bankQrContainer.style.display = 'flex';
                    const img = bankQrContainer.querySelector('img');
                    const label = bankQrContainer.querySelector('div');
                    if (img) img.style.display = 'block';
                    if (label) label.style.display = 'block';
                } else {
                    bankQrContainer.style.display = 'none';
                    const img = bankQrContainer.querySelector('img');
                    const label = bankQrContainer.querySelector('div');
                    if (img) img.style.display = 'none';
                    if (label) label.style.display = 'none';
                }
            }
        });
    }
    // --- Auto-select lot if lotId is in URL ---
    function getLotIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('lot') || params.get('reservationId') || null;
    }
    let autoSelectLotId = getLotIdFromUrl();

    // --- API Base URL ---
    const API_BASE_URL = "http://localhost/stJohnCmsApp/cms.api/";

    // --- DOM ELEMENTS ---
    const lotPriceDisplay = document.getElementById('lot-price');
    const monthlyPaymentDisplay = document.getElementById('monthly-payment');
    const monthlyPaymentDesc = document.getElementById('monthly-payment-description');
    const totalPaidDisplay = document.getElementById('total-paid');
    const remainingBalanceDisplay = document.getElementById('remaining-balance');
    const paymentTypeSelect = document.getElementById('payment-type'); // may be absent after removing from form
    const advancePaymentOptions = document.getElementById('advance-payment-options');
    const monthsToPayInput = document.getElementById('months-to-pay');
    const customAmountInput = document.getElementById('custom-amount');
    const calculatedAmountInput = document.getElementById('calculated-amount');

    const gcashDetails = document.getElementById('gcash-details');
    const bankDetails = document.getElementById('bank-details');
    const onlinePaymentFields = document.getElementById('online-payment-fields');
    const paymentForm = document.getElementById('payment-form');
    const logoutLinks = document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile');
    const submitBtn = document.querySelector('button[type="submit"]');
    const lotSelect = document.getElementById("lot-select");
    // Removed toast and reservation/payment history related DOM elements
    
    // --- Modal Elements ---
    let docModal = null;
    const docModalElem = document.getElementById('docModal');
    if (docModalElem && window.bootstrap && bootstrap.Modal) {
        docModal = new bootstrap.Modal(docModalElem);
    }
    const docFilename = document.getElementById('docFilename');
    const imgPreview = document.getElementById('img-preview');
    const pdfCanvas = document.getElementById('pdf-canvas');
    const pdfControls = document.getElementById('pdfControls');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const pageInfoSpan = document.getElementById('pageInfo');
    const downloadLink = document.getElementById('downloadLink');
    const deleteBtn = document.getElementById('deleteBtn');
    const replaceLabel = document.querySelector('label[for="replaceFileInput"]');
    const replaceFileInput = document.getElementById('replaceFileInput');




    

    // --- Global State ---
    let currentSelectedLot = null;
    let pdfDoc = null;
    let pageNum = 1;
    let currentFileInput = null;


    // --- Logout ---
    logoutLinks.forEach(link => link.addEventListener('click', e => {
        e.preventDefault();
        window.location.href = '../../auth/login/login.php';
    }));


    // --- Load User Name ---
    async function loadUserName() {
        try {
            const res = await fetch(`${API_BASE_URL}displayname.php`, { credentials: "include" });
            const data = await res.json();
            const nameEl = document.getElementById("user-name-display-desktop");
            nameEl.textContent = (data.status === "success" && data.fullName) ? data.fullName : "Guest";
        } catch (err) {
            console.error("Error fetching user name:", err);
        }
    }
    



    // --- Update Reservation Status ---
    async function updateReservationStatus(reservationId, newStatus) {
        try {
            const res = await fetch(`${API_BASE_URL}updateReservationStatus.php`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include",
                body: JSON.stringify({ reservationId, status: newStatus })
            });

            const data = await res.json();
            if (data.status === "success") {
                console.log(`✅ Reservation #${reservationId} updated to "${newStatus}"`);
            } else {
                console.warn("⚠️ Failed to update status:", data.message);
            }
        } catch (err) {
            console.error("Error updating status:", err);
        }
    }



     window.selectMethod = function(element, method) {
        document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        if (onlinePaymentFields) onlinePaymentFields.style.display = 'block';

            // normalize method: accept 'gcash'/'bank' or numeric ids 1 and 2
            const m = String(method).toLowerCase();
            if (m === 'gcash' || m === '1') {
                if (gcashDetails) gcashDetails.style.display = 'block';
                if (bankDetails) bankDetails.style.display = 'none';
            } else if (m === 'bank' || m === '2') {
                if (gcashDetails) gcashDetails.style.display = 'none';
                if (bankDetails) bankDetails.style.display = 'block';
            }
    };

     function formatCurrency(num) {
        return new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(num);
    }




    // --- Payment Summary + Other Functions ---


    function updateCalculatedAmount() {
        if (!currentSelectedLot) return;

    const paymentType = (paymentTypeSelect && paymentTypeSelect.value) ? paymentTypeSelect.value : 'exact';
        let calculatedAmount = 0;

        advancePaymentOptions.style.display = 'none';
        submitBtn.disabled = false;
        calculatedAmountInput.style.display = 'block';

        if (paymentType === 'exact') {
            calculatedAmount = currentSelectedLot.monthlyPayment;
            calculatedAmountInput.value = formatCurrency(calculatedAmount);
        } else if (paymentType === 'advance') {
            advancePaymentOptions.style.display = 'block';

            const customAmountValue = parseFloat(customAmountInput.value);
            const months = parseInt(monthsToPayInput.value, 10);

            if (!isNaN(customAmountValue) && customAmountValue > 0) {
                if (customAmountValue < currentSelectedLot.monthlyPayment) {
                    calculatedAmountInput.value = formatCurrency(customAmountValue);
                    submitBtn.disabled = true;
                } else {
                    calculatedAmount = customAmountValue;
                    calculatedAmountInput.value = formatCurrency(calculatedAmount);
                    submitBtn.disabled = false;
                }
            } else {
                if (!isNaN(months) && months > 0) {
                    calculatedAmount = currentSelectedLot.monthlyPayment * months;
                } else {
                    calculatedAmount = 0;
                }
                calculatedAmountInput.value = formatCurrency(calculatedAmount);
            }
        } else if (paymentType === 'unable') {
            calculatedAmount = 0;
            submitBtn.disabled = true;
            calculatedAmountInput.style.display = 'none';
        }
    }


    
    // --- Modal and File Preview Functions ---
   function renderPage(num) {
    pageRendering = true;
    pdfDoc.getPage(num).then(page => {
        const context = pdfCanvas.getContext("2d");
        const viewport = page.getViewport({ scale: 1.3 });
        pdfCanvas.height = viewport.height;
        pdfCanvas.width = viewport.width;

        const renderContext = { canvasContext: context, viewport: viewport };
        const renderTask = page.render(renderContext);
        renderTask.promise.then(() => {
            pageRendering = false;
            pageInfo.textContent = `Page ${pageNum} of ${pdfDoc.numPages}`;
        });
    });
}

 async function showDocument(file, fileInput = null, fromHistory = false) {
    currentFileInput = fileInput;
    let fileURL = "";
    let fileName = "";
    let fileExt = "";
    let fileType = "";

    // --- Determine Source ---
    if (file instanceof File) {
        fileURL = URL.createObjectURL(file);
        fileName = file.name;
        fileType = file.type;
        fileExt = fileName.split(".").pop().toLowerCase();
    } 
    else if (typeof file === "string") {
        fileURL = file.startsWith("http") ? file : `${API_BASE_URL}${file}`;
        fileName = file.split("/").pop();
        fileExt = fileName.split(".").pop().toLowerCase();
        fileType = fileExt === "pdf" ? "application/pdf" : `image/${fileExt}`;
    } 
    else if (file && file.url) {
        // from payment history (custom object)
        fileURL = file.url.startsWith("http") ? file.url : `${API_BASE_URL}${file.url}`;
        fileName = file.name || fileURL.split("/").pop();
        fileExt = fileName.split(".").pop().toLowerCase();
        fileType = file.type || (fileExt === "pdf" ? "application/pdf" : `image/${fileExt}`);
    } 
    else {
        alert("❌ Unsupported file type for preview.");
        return;
    }

    // --- Check File Availability (skip for blob:) ---
    if (!fileURL.startsWith("blob:")) {
        try {
            const check = await fetch(fileURL, { method: "HEAD" });
            if (!check.ok) {
                alert("⚠️ File not found or deleted from server.");
                return;
            }
        } catch (err) {
            console.warn("⚠️ File verification skipped due to CORS or network policy:", err);
            // Allow preview even if HEAD fails (CORS)
        }
    }

    // --- Reset modal elements ---
    docFilename.textContent = fileName;
    imgPreview.style.display = "none";
    pdfCanvas.style.display = "none";
    pdfControls.style.display = "none";

    // --- Handle Image Preview ---
    if (["jpg", "jpeg", "png", "gif", "webp", "bmp"].includes(fileExt)) {
        imgPreview.src = fileURL;
        imgPreview.style.display = "block";
    }

    // --- Handle PDF Preview ---
    else if (fileExt === "pdf") {
        pdfCanvas.style.display = "block";
        pdfControls.style.display = "flex";
        try {
            pdfDoc = await pdfjsLib.getDocument(fileURL).promise;
            pageNum = 1;
            renderPage(pageNum);
        } catch (err) {
            console.error("Failed to load PDF:", err);
            alert("❌ Unable to load PDF preview.");
            return;
        }
    }

    // --- Unsupported File Type ---
    else {
        alert("❌ Unsupported file type for preview.");
        return;
    }

    // --- Show Modal ---
    docModal.show();
}





    // Event listeners for file inputs
   // Handle upload icon clicks safely
document.querySelectorAll(".file-upload-icon").forEach(btn => {
    btn.addEventListener("click", e => {
        const targetId = e.currentTarget.dataset.target;
        document.getElementById(targetId).click();
    });
});


 document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener("change", e => {
        const file = e.target.files[0];
        const fileNameSpan = document.getElementById(`${input.id}-filename`);
        const viewBtn = document.querySelector(`.view-icon[data-target="${input.id}"]`);

        if (file) {
            fileNameSpan.textContent = file.name;
            if (viewBtn) viewBtn.style.display = "inline-block";
        } else {
            fileNameSpan.textContent = "No file chosen";
            if (viewBtn) viewBtn.style.display = "none";
        }
    });
});


    // View buttons on the main form
document.querySelectorAll(".view-icon").forEach(btn => {
    btn.addEventListener("click", e => {
        const inputId = e.currentTarget.dataset.target;
        const inputEl = document.getElementById(inputId);

        if (inputEl && inputEl.files.length > 0) {
            showDocument(inputEl.files[0], inputEl);
        } else {
            alert("Please select a file to preview first.");
        }
    });
});

    // PDF Navigation

if (typeof prevPageBtn !== 'undefined' && prevPageBtn) {
    prevPageBtn.addEventListener("click", () => {
        if (pageNum <= 1) return;
        pageNum--;
        renderPage(pageNum);
    });
}

if (typeof nextPageBtn !== 'undefined' && nextPageBtn) {
    nextPageBtn.addEventListener("click", () => {
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        renderPage(pageNum);
    });
}

// Modal Action Buttons (for files from local input)
if (typeof replaceFileInput !== 'undefined' && replaceFileInput) {
    replaceFileInput.addEventListener('change', e => {
        if (!currentFileInput) return;
        const file = e.target.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            currentFileInput.files = dt.files;

            const fileNameSpan = document.getElementById(currentFileInput.id + '-filename');
            fileNameSpan.textContent = file.name;

            docModal.hide();
            replaceFileInput.value = '';
        }
    });
}
   // --- Form Submission (PHP Connection) ---





    function selectMethod(element, method) {
        document.querySelectorAll('.payment-method').forEach(card => card.classList.remove('selected'));
        element.classList.add('selected');

        var onlinePaymentFields = document.getElementById('online-payment-fields');
        var gcashDetails = document.getElementById('gcash-details');
        var bankDetails = document.getElementById('bank-details');
        if (onlinePaymentFields) onlinePaymentFields.style.display = 'block';
        if (gcashDetails) gcashDetails.style.display = (method === 'gcash') ? 'block' : 'none';
        if (bankDetails) bankDetails.style.display = (method === 'bank') ? 'block' : 'none';
    }
    
    function fetchPaymentData() {
        updatePaymentSummary();
    }

    // Event listeners
    if (typeof lotSelect !== 'undefined' && lotSelect) lotSelect.addEventListener('change', updatePaymentSummary);
    if (typeof paymentTypeSelect !== 'undefined' && paymentTypeSelect) paymentTypeSelect.addEventListener('change', updateCalculatedAmount);
    if (typeof monthsToPayInput !== 'undefined' && monthsToPayInput) monthsToPayInput.addEventListener('input', () => {
        if (typeof customAmountInput !== 'undefined' && customAmountInput) customAmountInput.value = '';
        updateCalculatedAmount();
    });
    if (typeof customAmountInput !== 'undefined' && customAmountInput) customAmountInput.addEventListener('input', () => {
        if (typeof monthsToPayInput !== 'undefined' && monthsToPayInput) monthsToPayInput.value = '';
        updateCalculatedAmount();
    });
    
    // Initial calls
    loadUserName();

    // --- Form validation: require payment method ---
    (function enforcePaymentMethodSelection() {
        // support both possible form ids
        const form = document.getElementById('paymentForm') || document.getElementById('payment-form');
        const methodSelect = document.getElementById('paymentMethod');
        const alertsContainer = document.createElement('div');
        alertsContainer.id = 'payment-alerts';
        if (form) form.insertBefore(alertsContainer, form.firstChild);

        if (!form || !methodSelect) return;

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (!methodSelect.value || methodSelect.value === '') {
                    // show inline Bootstrap alert
                    alertsContainer.innerHTML = `<div class="alert alert-danger alert-dismissible" role="alert">Please select a payment method before submitting.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                    methodSelect.focus();
                    return false;
                }

                // Clear any previous alerts
                alertsContainer.innerHTML = '';

                // Build FormData and POST via fetch so we can handle JSON response and redirect on success
                const fd = new FormData(form);

                try {
                    submitBtn.disabled = true;
                    const res = await fetch('../../../../cms.api/save_payment.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'include'
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        // Redirect back to client reservations after successful payment
                        window.location.href = '../clientReservations.php';
                        return true;
                    } else {
                        // show error message
                        alertsContainer.innerHTML = `<div class="alert alert-danger alert-dismissible" role="alert">${data.message || 'Payment failed. Please try again.'}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                        submitBtn.disabled = false;
                        return false;
                    }
                } catch (err) {
                    console.error('Payment request failed', err);
                    alertsContainer.innerHTML = `<div class="alert alert-danger alert-dismissible" role="alert">Network error. Please try again later.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                    submitBtn.disabled = false;
                    return false;
                }
            });
    })();

});
