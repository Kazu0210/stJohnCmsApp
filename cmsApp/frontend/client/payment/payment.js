document.addEventListener('DOMContentLoaded', () => {
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
    const paymentTypeSelect = document.getElementById('payment-type');
    const advancePaymentOptions = document.getElementById('advance-payment-options');
    const monthsToPayInput = document.getElementById('months-to-pay');
    const customAmountInput = document.getElementById('custom-amount');
    const calculatedAmountInput = document.getElementById('calculated-amount');
    const paymentHistoryBody = document.getElementById('paymentHistoryBody');
    const gcashDetails = document.getElementById('gcash-details');
    const bankDetails = document.getElementById('bank-details');
    const onlinePaymentFields = document.getElementById('online-payment-fields');
    const paymentForm = document.getElementById('payment-form');
    const logoutLinks = document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile');
    const submitBtn = document.querySelector('button[type="submit"]');
    const lotSelect = document.getElementById("lot-select");
    const toastElement = document.getElementById("liveToast");
    const toastBody = toastElement.querySelector(".toast-body");
    const toastTime = toastElement.querySelector("small");
    const reservationSelect = document.getElementById("reservationSelect");
const amountInput = document.getElementById("amountInput");
    
    // --- Modal Elements ---
     const docModal = new bootstrap.Modal(document.getElementById('docModal'));
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


    // --- Toast ---
    const toast = new bootstrap.Toast(toastElement, {
        delay: 24 * 60 * 60 * 1000, // 24 hours
        autohide: false
    });

    

    // --- Global State ---
    let countdownInterval = null;
    let currentSelectedLot = null;
    let pdfDoc = null;
    let pageNum = 1;
    const TOTAL_MONTHS = 50;
    // let allLots = [];
    let paymentHistoryData = {};
    const CONTRACT_START_DATE = new Date();
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

    // --- Countdown Toast ---
    function startPaymentCountdown(reservationId) {
        const deadline = new Date(Date.now() + 24 * 60 * 60 * 1000);

        if (countdownInterval) clearInterval(countdownInterval);

        function updateCountdown() {
            const now = new Date();
            const diff = deadline - now;

            if (diff <= 0) {
                toastBody.textContent = "Your payment time has expired!";
                toastTime.textContent = "Now";
                clearInterval(countdownInterval);
                updateReservationStatus(reservationId, "Cancelled");
                if (typeof loadReservationHistory === "function") loadReservationHistory();
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            toastBody.textContent = `You have ${hours}h ${minutes}m ${seconds}s left to pay this reservation!`;
            toastTime.textContent = "Counting down...";
        }

        updateCountdown();
        toast.show();
        countdownInterval = setInterval(updateCountdown, 1000);
    }

     window.selectMethod = function(element, method) {
        document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        if (onlinePaymentFields) onlinePaymentFields.style.display = 'block';

        if (method === 'gcash') {
            if (gcashDetails) gcashDetails.style.display = 'block';
            if (bankDetails) bankDetails.style.display = 'none';
        } else if (method === 'bank') {
            if (gcashDetails) gcashDetails.style.display = 'none';
            if (bankDetails) bankDetails.style.display = 'block';
        }
    };

     function formatCurrency(num) {
        return new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(num);
    }


    // --- Lot Selection Change ---
    if (lotSelect) lotSelect.addEventListener("change", async () => {
        const selectedId = lotSelect.value;
    // const selectedLot = allLots.find(lot => String(lot.reservationId) === String(selectedId));

        if (!selectedLot) {
            currentSelectedLot = null;
            lotPriceDisplay.textContent = "₱0.00";
            monthlyPaymentDisplay.textContent = "₱0.00";
            monthlyPaymentDesc.textContent = "";
            totalPaidDisplay.textContent = "₱0.00";
            remainingBalanceDisplay.textContent = "₱0.00";
            return;
        }


    });

    // --- Payment Summary + Other Functions ---
    function updatePaymentSummary() {
        if (!currentSelectedLot) {
            totalPaidDisplay.textContent = formatCurrency(0);
            remainingBalanceDisplay.textContent = formatCurrency(0);
            paymentHistoryBody.innerHTML =
                '<tr><td colspan="7" class="text-center text-muted">Please select a lot to view its payment history.</td></tr>';
            return;
        }
 const reservationId = currentSelectedLot.reservationId;
        const history = paymentHistoryData[reservationId] || [];
        const totalPaid = history.reduce((sum, payment) => sum + payment.amount, 0);
        const remainingBalance = Math.max(0, currentSelectedLot.price - totalPaid);

        totalPaidDisplay.textContent = formatCurrency(totalPaid);
        remainingBalanceDisplay.textContent = formatCurrency(remainingBalance);

        updateCalculatedAmount();
    }

    function updateCalculatedAmount() {
        if (!currentSelectedLot) return;

        const paymentType = paymentTypeSelect.value;
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

   async function renderPaymentSchedule(paymentId = null) {
    try {
        let url = `${API_BASE_URL}save_payment.php?mode=getPayments`;
        if (paymentId) url += `&paymentId=${paymentId}`;

        const response = await fetch(url, { method: "GET", credentials: "include" });
        const data = await response.json();

        console.log("📜 Payment history data:", data);

        if (data.status !== "success" || !Array.isArray(data.data) || data.data.length === 0) {
            paymentHistoryBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        No payment history found.
                    </td>
                </tr>`;
            return;
        }

        let html = "";
        data.data.forEach(payment => {
            const datePaid = payment.datePaid
                ? new Date(payment.datePaid).toLocaleDateString()
                : "N/A";

            const amount = payment.amount ? formatCurrency(payment.amount) : "₱0.00";
            const method = payment.methodName || "N/A";
            const paymentType = payment.paymentType ? payment.paymentType.charAt(0).toUpperCase() + payment.paymentType.slice(1) : "N/A";
            const reference = payment.reference || "N/A";
            const status = payment.status || "Pending";

            const statusClass =
                status === "Confirmed"
                    ? "paid"
                    : status === "Pending"
                    ? "pending"
                    : "unpaid";

            const docButton = payment.document
                ? `<button type="button" class="btn btn-sm btn-info view-doc-btn"
                    data-file-url="${payment.document}"
                    data-file-name="${payment.document.split('/').pop()}">
                    View
                </button>`
                : "N/A";

            html += `
                <tr>
                    <td>${payment.month}</td>
                    <td>${datePaid}</td>
                    <td>${amount}</td>
                    <td>${method}</td>
                    <td>${paymentType}</td>
                    <td>${reference}</td>
                    <td><span class="status ${statusClass}">${status}</span></td>
                    <td>${docButton}</td>
                </tr>
            `;
        });

        paymentHistoryBody.innerHTML = html;

    } catch (err) {
        console.error("Fetch error:", err);
        paymentHistoryBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger">
                    Error loading payment data.
                </td>
            </tr>`;
    }
}

    renderPaymentSchedule();
    
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



    // View button in the payment history table
  paymentHistoryBody.addEventListener("click", async (e) => {
    if (e.target.classList.contains("view-doc-btn")) {
        const fileURL = e.target.dataset.fileUrl;
        const fileName = e.target.dataset.fileName || "document";

        if (!fileURL) {
            alert("⚠️ No document available to view.");
            return;
        }

        try {
            // Detect file type by extension if possible
            const fileExt = fileURL.split('.').pop().toLowerCase();
            const isImage = ["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(fileExt);
            const isPdf = fileExt === "pdf";

            // Directly preview without fetch for images/PDFs
            if (isImage || isPdf) {
                showDocument({ 
                    name: fileName, 
                    type: isPdf ? "application/pdf" : `image/${fileExt}`,
                    url: fileURL 
                }, null, true);
                return;
            }

            // Otherwise, fallback to fetch (for unknown types)
            const response = await fetch(fileURL);
            if (!response.ok) throw new Error("File not found");

            const blob = await response.blob();
            const file = new File([blob], fileName, { type: blob.type });
            showDocument(file, null, true);

        } catch (err) {
            console.error("Error fetching file:", err);
            alert("❌ Failed to load document preview.");
        }
    }
});


    // PDF Navigation
 prevPageBtn.addEventListener("click", () => {
    if (pageNum <= 1) return;
    pageNum--;
    renderPage(pageNum);
});

nextPageBtn.addEventListener("click", () => {
    if (pageNum >= pdfDoc.numPages) return;
    pageNum++;
    renderPage(pageNum);
});


    // Modal Action Buttons (for files from local input)
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

  deleteBtn.addEventListener('click', () => {
    if (!currentFileInput) return;

    currentFileInput.value = null;
    const fileNameSpan = document.getElementById(currentFileInput.id + '-filename');
    fileNameSpan.textContent = 'No file chosen';

    const viewBtn = document.querySelector(`.view-icon[data-target="${currentFileInput.id}"]`);
    if (viewBtn) viewBtn.style.display = 'none';

    docModal.hide();
    currentFileInput = null;
});


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

     updatePaymentSummary();
});
