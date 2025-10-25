// lotReservation.js
if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
}

document.addEventListener("DOMContentLoaded", () => {

    // ---- Elements ----
    const form = document.querySelector(".lot-reservation-form");
    const logoutLinks = [document.getElementById("logoutLinkDesktop"), document.getElementById("logoutLinkMobile")];

    const preferredLot = document.getElementById("preferred_lot");
    const depthOption = document.getElementById("depth_option");
    const burialDepthSelect = document.getElementById("burial_depth");

    // Elements to be auto-filled
    const clientNameInput = document.getElementById("client_name");
    const clientAddressInput = document.getElementById("client_address");
    const clientContactInput = document.getElementById("client_contact");

    const lotIdInput = document.getElementById("lotId");
    const areaInput = document.getElementById("area");
    const blockInput = document.getElementById("block");
    const rowNumberInput = document.getElementById("rowNumber");
    const lotNumberInput = document.getElementById("lot_number");

    const docModalEl = document.getElementById("docModal");
    // Ensure Bootstrap is loaded before attempting this
    const docModal = docModalEl ? new bootstrap.Modal(docModalEl) : null; 
    const docFilename = document.getElementById("docFilename");
    const imgPreview = document.getElementById("img-preview");
    const pdfCanvas = document.getElementById("pdf-canvas");
    const pdfControls = document.getElementById("pdfControls");
    const pageInfo = document.getElementById("pageInfo");
    const prevPageBtn = document.getElementById("prevPage");
    const nextPageBtn = document.getElementById("nextPage");
    const downloadLink = document.getElementById("downloadLink");
    const deleteBtn = document.getElementById("deleteBtn");
    const replaceFileInput = document.getElementById("replaceFileInput");
    const replaceFileLabel = document.querySelector('label[for="replaceFileInput"]');
    const historyTableBody = document.querySelector(".lot-history-table tbody");
    
    // Get the modal body for centering content
    const modalBody = document.querySelector('.modal-body');
    
    // Pagination Element
    const historyTable = document.querySelector(".lot-history-table");
    const paginationContainer = document.getElementById("reservation-pagination");

    // ---- State ----
    const fileMap = {};
    let currentFileInput = null, currentFileObj = null;
    let pdfDoc = null, currentPage = 1, totalPages = 1;

    // PAGINATION STATE
    const recordsPerPage = 10;
    let currentPageNumber = 1;
    let totalPagesCount = 0;
    let fullReservationData = []; // Store the full fetched list


    // ====================================================================
    // START: LOT DATA TRANSFER LOGIC (Retrieve from localStorage)
    // ====================================================================

    const storedLotData = localStorage.getItem('selectedLotData');

    if (storedLotData) {
        try {
            const lot = JSON.parse(storedLotData);
            console.log("Lot data successfully received from Cemetery Map:", lot);

            // 1. Populate Lot Identification Fields
            if (lotIdInput) lotIdInput.value = lot.lotId || '';
            if (areaInput) areaInput.value = lot.area || '';
            if (blockInput) blockInput.value = lot.block || '';
            if (rowNumberInput) rowNumberInput.value = lot.rowNumber || '';
            if (lotNumberInput) lotNumberInput.value = lot.lotNumber || '';
            
            // 2. Populate Lot Type (Select Field)
            if (preferredLot) {
                // Set the lot type using the stored lotTypeId
                preferredLot.value = lot.lotTypeId || '';
                // Trigger change event to run the toggleDepthOption function immediately
                preferredLot.dispatchEvent(new Event('change')); 
            }
            
            // 3. Populate Burial Depth (Select Field)
            if (burialDepthSelect) {
                // Set the burial depth
                burialDepthSelect.value = lot.buryDepth || '';
            }

            // 4. Show confirmation and clear storage
            alert("Lot " + lot.lotNumber + " (Block: " + lot.block + ") has been successfully pre-selected for reservation.");
            localStorage.removeItem('selectedLotData');

        } catch (e) {
            console.error("Error parsing stored lot data:", e);
            alert("Warning: Error loading pre-selected lot details.");
        }
    }
    
    // ====================================================================
    // END: LOT DATA TRANSFER LOGIC
    // ====================================================================


    // ---- Logout handling ----
    logoutLinks.forEach(link => {
        if (!link) return;
        link.addEventListener("click", e => {
            e.preventDefault();
            localStorage.removeItem("user_token");
            window.location.href = "../../auth/login/login.php";
        });
    });

    // ====================================================================
    // MODIFIED: Load logged-in client details and pre-fill form
    // ====================================================================
    async function loadClientDetails() {
        try {
            const res = await fetch(`/stJohnCmsApp/cms.api/displayname.php`, {
                method: "GET", credentials: "include"
            });
            const data = await res.json();
            
            const desktopNameEl = document.getElementById("user-name-display-desktop");

            if (data.status === "success" && data.fullName) {
                const displayName = data.fullName;
                if (desktopNameEl) desktopNameEl.textContent = displayName;
                
                // Auto-fill form fields
                const userFullName = data.firstName && data.lastName ? `${data.firstName} ${data.lastName}` : displayName;
                
                // Only pre-fill if the fields are empty (to avoid overwriting pre-filled data)
                if (clientNameInput && !clientNameInput.value) clientNameInput.value = userFullName || '';
                if (clientAddressInput && data.address && !clientAddressInput.value) clientAddressInput.value = data.address;
                if (clientContactInput && data.contactNumber && !clientContactInput.value) clientContactInput.value = data.contactNumber;

            } else {
                if (desktopNameEl) desktopNameEl.textContent = "Guest";
            }

        } catch (err) {
            console.error("Error fetching client details:", err);
            const desktopNameEl = document.getElementById("user-name-display-desktop");
            if (desktopNameEl) desktopNameEl.textContent = "Error";
        }
    }
    loadClientDetails();
    // ====================================================================
    // END: Load logged-in client details
    // ====================================================================

    // ====================================================================
    // START: PAGINATION LOGIC FUNCTIONS
    // ====================================================================

    function renderTableRows(dataForPage) {
        if (!historyTableBody) return;
        historyTableBody.innerHTML = "";

        if (dataForPage.length > 0) {
            dataForPage.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.clientName || "-"}</td>
                    <td>${row.address || "-"}</td>
                    <td>${row.contactNumber || "-"}</td>
                    <td class="text-center">
                        ${row.clientValidId ? 
                            // FIX APPLIED HERE: Correctly using template literal ${}
                            `<a href="#" class="view-history-doc text-decoration-underline text-primary" data-url="/stJohnCmsApp/cms.api/${row.clientValidId}" title="View Document">
                                View
                                </a>` 
                            : "N/A"}
                    </td>
                    <td>${row.reservationDate || "-"}</td>
                    <td>${row.area || "-"}</td>
                    <td>${row.block || "-"}</td>
                    <td>${row.rowNumber || "-"}</td>
                    <td>${row.lotNumber || "-"}</td>
                    <td>${row.lotType || "N/A"}</td>
                    <td>${row.price ? "P" + Number(row.price).toLocaleString() : "-"}</td>
                    <td>${row.status || "N/A"}</span></td>
                `;
                historyTableBody.appendChild(tr);
            });
        } else {
            historyTableBody.innerHTML = `<tr><td colspan="12" class="text-center">No reservation history found.</td></tr>`;
        }
    }

    function renderPaginationControls() {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        
        if (totalPagesCount <= 1) return;

        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-center mt-3';

        // --- Previous Button ---
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPageNumber === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPageNumber - 1}">Previous</a>`;
        ul.appendChild(prevLi);

        // --- Page Number Buttons ---
        for (let i = 1; i <= totalPagesCount; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${currentPageNumber === i ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            ul.appendChild(pageLi);
        }

        // --- Next Button ---
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPageNumber === totalPagesCount ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPageNumber + 1}">Next</a>`;
        ul.appendChild(nextLi);

        paginationContainer.appendChild(ul);
    }
    
    // ---- Load reservation history (The main function is now modified) ----
    async function loadReservationHistory(page = 1) {
        // 1. Fetch data only if it hasn't been fetched yet
        if (fullReservationData.length === 0 && page === 1) {
            try {
                const res = await fetch(`/stJohnCmsApp/cms.api/clientLotReservation.php`, {
                    method: "GET", credentials: "include"
                });
                const data = await res.json();
                
                if (data.status === "success" && data.data.length > 0) {
                    fullReservationData = data.data;
                } else {
                    fullReservationData = [];
                }
            } catch (err) {
                console.error("Error loading history:", err);
                if (historyTableBody) {
                    historyTableBody.innerHTML = `<tr><td colspan="12" class="text-center text-danger">Failed to load history.</td></tr>`;
                }
                return; // Exit on fetch error
            }
        }

        // 2. Set pagination state
        currentPageNumber = page;
        const totalRecords = fullReservationData.length;
        totalPagesCount = Math.ceil(totalRecords / recordsPerPage);
        
        // Clamp current page number
        if (currentPageNumber > totalPagesCount) currentPageNumber = totalPagesCount;
        if (currentPageNumber < 1) currentPageNumber = 1;

        // 3. Slice data for the current page
        const startIndex = (currentPageNumber - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const dataForPage = fullReservationData.slice(startIndex, endIndex);

        // 4. Render the table rows and pagination controls
        renderTableRows(dataForPage);
        renderPaginationControls();
    }
    
    // ====================================================================
    // END: PAGINATION LOGIC FUNCTIONS
    // ====================================================================


    // Initial load
    loadReservationHistory();
    
    // Pagination Click Handler
    paginationContainer?.addEventListener('click', (e) => {
        e.preventDefault();
        const pageLink = e.target.closest('.page-link');
        if (pageLink) {
            const pageNum = parseInt(pageLink.dataset.page);
            if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPagesCount && pageNum !== currentPageNumber) {
                // Scroll to top of table when changing pages (optional but helpful UX)
                historyTable?.scrollIntoView({ behavior: 'smooth', block: 'start' }); 
                loadReservationHistory(pageNum);
            }
        }
    });

    // Event listener is now delegated to the table wrapper since tbody content is replaced
    historyTable?.addEventListener("click", e => {
        const link = e.target.closest('.view-history-doc');
        if (link) {
            e.preventDefault(); 
            const docUrl = link.dataset.url;
            if (docUrl) {
                // The 'true' here signifies this is from the history view, hiding unnecessary buttons
                openFileInModal(null, docUrl, null, true); 
            }
        }
    });


    // ---- Autodetect lot from URL and Set Hidden Fields (Original logic preserved) ----
    const urlParams = new URLSearchParams(window.location.search);
    
    ["lotId", "area", "block", "lot_number", "rowNumber"].forEach(param => {
        const value = urlParams.get(param); 
        const el = document.getElementById(param);
        if (value && el && !el.value) el.value = value; 
    });

    if (lotIdInput?.value && !storedLotData) { 
        const alertDiv = document.createElement("div");
        alertDiv.className = "alert alert-info alert-dismissible fade show mt-3";
        alertDiv.innerHTML = `
            <i class="fas fa-info-circle me-2"></i> Lot (ID: ${lotIdInput.value}) was pre-selected. Please complete the form.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector(".lot-reservation-section")?.prepend(alertDiv);
    }

    // Default reservation date = today
    document.getElementById("reservation_date").valueAsDate = new Date();

    // ---- Show/hide burial depth (Original logic preserved) ----
    function toggleDepthOption() {
        if (!preferredLot || !depthOption) return;
        const selected = preferredLot.value;
        // Hide for Mausoleum options (IDs 4 and 5) and Exhumation (ID 7)
        if (["4", "5", "7"].includes(selected)) {
            depthOption.style.display = "none";
            burialDepthSelect?.removeAttribute("required");
        } else {
            depthOption.style.display = "block";
            burialDepthSelect?.setAttribute("required", "required");
        }
    }
    preferredLot?.addEventListener("change", toggleDepthOption);
    toggleDepthOption(); // Run on load (and after lot pre-selection)

    // ---- File input handling (Original logic preserved) ----
    document.querySelectorAll(".file-input-wrapper").forEach(wrapper => {
        const targetId = wrapper.querySelector(".file-actions")?.getAttribute("data-target");
        const input = document.getElementById(targetId);
        const viewIcon = wrapper.querySelector(".view-icon");
        const filenameSpan = document.getElementById(targetId + "_filename");

        if (!input || !viewIcon) return;
        viewIcon.style.display = "inline-block";

        input.addEventListener("change", () => {
            const file = input.files?.[0];
            if (fileMap[targetId]?.url) URL.revokeObjectURL(fileMap[targetId].url);
            if (file) {
                const url = URL.createObjectURL(file);
                fileMap[targetId] = { file, url };
                if (filenameSpan) {
                    filenameSpan.textContent = file.name;
                    filenameSpan.style.color = "#000";
                }
            } else {
                delete fileMap[targetId];
                if (filenameSpan) {
                    filenameSpan.textContent = "No file chosen";
                    filenameSpan.style.color = "#6c757d";
                }
            }
        });

        viewIcon.addEventListener("click", e => {
            e.preventDefault();
            const file = input.files?.[0];
            if (!file) return alert("No file selected.");
            const url = URL.createObjectURL(file);
            // 'false' here means it's from the form, so all buttons are shown
            openFileInModal(file, url, input, false);
            docModalEl?.addEventListener("hidden.bs.modal", () => URL.revokeObjectURL(url), { once: true });
        });
    });

    // ---- File preview modal (Original logic preserved) ----
    function openFileInModal(file, url, inputEl, isHistoryView = false) {
        // 🚨 Debugging: Confirm the function is called
        console.log("Attempting to open modal for URL:", url); 
        
        currentFileInput = inputEl;
        currentFileObj = file;

        const fileName = file ? file.name : url.split('/').pop();
        if (docFilename) docFilename.textContent = fileName;
        
        // Hide/show footer buttons based on context
        if(deleteBtn) deleteBtn.style.display = isHistoryView ? 'none' : 'inline-block';
        if(replaceFileLabel) replaceFileLabel.style.display = isHistoryView ? 'none' : 'inline-block';

        pdfDoc = null; currentPage = 1; totalPages = 1;
        imgPreview.style.display = "none";
        pdfCanvas.style.display = "none";
        if (pdfControls) pdfControls.style.display = "none";
        
        // ADDED: Center the content in the modal body to fix the left-alignment issue
        if(modalBody) modalBody.classList.add('text-center');


        const fileType = file ? file.type : '';
        // If file object is null (from history view), we rely on URL extension
        const isImage = fileType.startsWith("image/") || /\.(jpg|jpeg|png|gif)$/i.test(fileName);
        const isPdf = fileType === "application/pdf" || /\.pdf$/i.test(fileName);

        if (isImage) {
            imgPreview.src = url;
            imgPreview.style.display = "block";
            // Ensure image takes full available width in the center-aligned modal
            imgPreview.style.maxWidth = "100%"; 
            imgPreview.style.height = "auto";
            if (downloadLink) { downloadLink.href = url; downloadLink.download = fileName; }
            docModal?.show();
        } else if (isPdf) {
            // Need to handle both blob URL (local file) and history URL (server path)
            pdfjsLib.getDocument({ url }).promise.then(loadedPdf => {
                pdfDoc = loadedPdf;
                totalPages = pdfDoc.numPages;
                renderPage(currentPage);
                if (downloadLink) { downloadLink.href = url; downloadLink.download = fileName; }
                if (pdfControls) pdfControls.style.display = "flex";
                docModal?.show();
            }).catch(err => {
                console.error("PDF load error", err);
                alert("Unable to preview PDF. Please use the download button.");
            });
        } else {
            alert("Unsupported file type for preview. Use the download button instead.");
            if (downloadLink) { downloadLink.href = url; downloadLink.download = fileName; }
            
            // For unsupported types, still show the modal if possible, just without preview
            if (docModal) {
                 // Hide canvas/image in case it was showing something previously
                imgPreview.style.display = "none"; 
                pdfCanvas.style.display = "none";
                docModal.show();
            }
        }
    }

    function renderPage(num) {
        if (!pdfDoc) return;
        pdfDoc.getPage(num).then(page => {
            // Use the parent element's width, which is centered in the modal body
            const containerWidth = pdfCanvas.parentElement.clientWidth; 
            const viewport = page.getViewport({ scale: 1.2 });
            const scale = containerWidth / viewport.width;
            const scaledViewport = page.getViewport({ scale });

            pdfCanvas.height = scaledViewport.height;
            pdfCanvas.width = scaledViewport.width;

            page.render({ canvasContext: pdfCanvas.getContext("2d"), viewport: scaledViewport }).promise.then(() => {
                pdfCanvas.style.display = "block";
                if (pageInfo) pageInfo.textContent = `Page ${num} of ${totalPages}`;
                if(prevPageBtn) prevPageBtn.disabled = num <= 1;
                if(nextPageBtn) nextPageBtn.disabled = num >= totalPages;
            });
        });
    }

    prevPageBtn?.addEventListener("click", () => { if (currentPage > 1) renderPage(--currentPage); });
    nextPageBtn?.addEventListener("click", () => { if (currentPage < totalPages) renderPage(++currentPage); });

    deleteBtn?.addEventListener("click", () => {
        if (!currentFileInput || !currentFileObj) return;
        if (confirm(`Are you sure you want to delete ${currentFileObj.name}?`)) {
            currentFileInput.value = "";
            const changeEvent = new Event('change', { bubbles: true });
            currentFileInput.dispatchEvent(changeEvent);
            docModal?.hide();
        }
    });

    replaceFileInput?.addEventListener("change", () => {
        const newFile = replaceFileInput.files?.[0];
        if (newFile && currentFileInput) {
            const dt = new DataTransfer();
            dt.items.add(newFile);
            currentFileInput.files = dt.files;
            
            const changeEvent = new Event('change', { bubbles: true });
            currentFileInput.dispatchEvent(changeEvent);

            const newUrl = URL.createObjectURL(newFile);
            docModal?.hide();
            openFileInModal(newFile, newUrl, currentFileInput);
            docModalEl?.addEventListener("hidden.bs.modal", () => URL.revokeObjectURL(newUrl), { once: true });
        }
        replaceFileInput.value = ""; // Reset for next use
    });

    docModalEl?.addEventListener("hidden.bs.modal", () => {
        // Remove centering when modal closes
        if(modalBody) modalBody.classList.remove('text-center'); 
        
        imgPreview.src = "";
        pdfDoc = null;
        currentFileInput = null;
        currentFileObj = null;
    });
});