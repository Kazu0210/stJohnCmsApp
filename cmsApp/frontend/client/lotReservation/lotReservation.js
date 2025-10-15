// ✅ Configure PDF.js worker globally
if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
}

document.addEventListener("DOMContentLoaded", () => {
    // ---- API Base URL ----
    const API_BASE_URL = "http://localhost/stJohnCmsApp/cms.api/";

    // ---- Elements ----
    const form = document.querySelector(".lot-reservation-form");
    const logoutLinks = [document.getElementById("logoutLinkDesktop"), document.getElementById("logoutLinkMobile")];

    const preferredLot = document.getElementById("preferred_lot");
    const depthOption = document.getElementById("depth_option");
    const burialDepthSelect = document.getElementById("burial_depth");

    const lotIdInput = document.getElementById("lotId"); 
    const areaInput = document.getElementById("area"); 
    const blockInput = document.getElementById("block"); 
    const rowNumberInput = document.getElementById("rowNumber"); 
    const lotNumberInput = document.getElementById("lot_number"); 

    const docModalEl = document.getElementById("docModal");
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
    
    // 🛑 NEW: Pagination Element
    const historyTable = document.querySelector(".lot-history-table");
    const paginationContainer = document.getElementById("reservation-pagination");

    // ---- State ----
    const fileMap = {};
    let currentFileInput = null, currentFileObj = null;
    let pdfDoc = null, currentPage = 1, totalPages = 1;

    // 🛑 NEW: PAGINATION STATE
    const recordsPerPage = 10;
    let currentPageNumber = 1;
    let totalPagesCount = 0;
    let fullReservationData = []; // Store the full fetched list


    // ====================================================================
    // 🛑 START: LOT DATA TRANSFER LOGIC (Retrieve from localStorage)
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
            alert(`✅ Lot ${lot.lotNumber} (Block: ${lot.block}) has been successfully pre-selected for reservation.`);
            localStorage.removeItem('selectedLotData');

        } catch (e) {
            console.error("Error parsing stored lot data:", e);
            alert("⚠️ Error loading pre-selected lot details.");
        }
    }
    
    // ====================================================================
    // 🛑 END: LOT DATA TRANSFER LOGIC
    // ====================================================================


    // ---- Logout handling ----
    logoutLinks.forEach(link => {
        if (!link) return;
        link.addEventListener("click", e => {
            e.preventDefault();
            localStorage.removeItem("user_token"); // Example: clear token on logout
            window.location.href = "../../auth/login/login.html";
        });
    });

    // ---- Load logged-in username ----
    async function loadUserName() {
        try {
            const res = await fetch(`${API_BASE_URL}displayname.php`, {
                method: "GET", credentials: "include"
            });
            const data = await res.json();
            const desktopNameEl = document.getElementById("user-name-display-desktop");

            const displayName = (data.status === "success" && data.fullName) ? data.fullName : "Guest";
            if (desktopNameEl) desktopNameEl.textContent = displayName;

        } catch (err) {
            console.error("Error fetching user:", err);
            const desktopNameEl = document.getElementById("user-name-display-desktop");
            if (desktopNameEl) desktopNameEl.textContent = "Error";
        }
    }
    loadUserName();

    // ====================================================================
    // 🛑 START: PAGINATION LOGIC FUNCTIONS
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
                            `<a href="#" class="view-history-doc text-decoration-underline text-primary" data-url="${API_BASE_URL}${row.clientValidId}" title="View Document">
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
                    <td>${row.price ? "₱" + Number(row.price).toLocaleString() : "-"}</td>
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
        
        if (totalPagesCount <= 1) return; // Hide pagination if only one page

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
                const res = await fetch(`${API_BASE_URL}clientLotReservation.php`, {
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
    // 🛑 END: PAGINATION LOGIC FUNCTIONS
    // ====================================================================


    // Initial load
    loadReservationHistory();
    
    // ✅ NEW: Pagination Click Handler (using event delegation on the container)
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

    // ✅ MODIFIED: Event listener is now delegated to the table wrapper since tbody content is replaced
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

    form?.addEventListener("submit", async (e) => {
    e.preventDefault();

    // ✅ Validate contact number
    const clientContact = document.getElementById("client_contact")?.value.trim();
    if (!/^(09)\d{9}$/.test(clientContact)) {
        alert("⚠️ Invalid Contact Number. It must be 11 digits and start with '09'.");
        return;
    }

    // ✅ Confirm submission
    if (!confirm("📌 Are you sure you want to submit this reservation request?")) {
        return;
    }

    const formData = new FormData(form);

    // ✅ CRITICAL: Ensure lotId is present in formData for the PHP lot update logic
    const lotId = lotIdInput?.value.trim();
    if (lotId) {
        formData.append('lotId', lotId); 
    }

    try {
        // --- 1. Initial Reservation Submission ---
        const res = await fetch(form.action, {
            method: "POST",
            body: formData,
            credentials: "include",
        });

        let result;
        try {
            result = await res.json();
        } catch (jsonErr) {
            // Log the raw response for debugging
            const rawText = await res.text();
            console.error("Failed to parse JSON. Raw response:", rawText);
            alert("❌ Server returned an invalid response. Check the console for details.");
            return;
        }

        if (!res.ok) {
            throw new Error(`Server error (${res.status}): ${result.message || 'Unknown server error.'}`);
        }

        if (result.status === "success") {

            // 🛑 NEW CODE BLOCK: Update Lot Status on the Server using JSON
            if (lotId) {
                const updateStatus = "Pending"; // The status to set

                // Gather all necessary data from the form to satisfy update_lot.php's logic
                const updatePayload = {
                    lotId: lotId,
                    block: document.getElementById('block')?.value.trim() || '',
                    area: document.getElementById('area')?.value.trim() || '',
                    rowNumber: document.getElementById('rowNumber')?.value.trim() || '',
                    lotNumber: document.getElementById('lot_number')?.value.trim() || '',
                    lotTypeId: preferredLot?.value.trim() || null, // preferred_lot is Lot Type ID
                    buryDepth: burialDepthSelect?.value.trim() || '',
                    status: updateStatus,
                    userId: null 
                };
                
                try {
                    // --- 2. Update Lot Status via JSON API ---
                    const updateRes = await fetch(`${API_BASE_URL}update_lot.php`, {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json' // CRITICAL for your PHP script
                        },
                        body: JSON.stringify(updatePayload), // CRITICAL: Send as JSON
                        credentials: "include",
                    });

                    const updateResult = await updateRes.json();
                    
                    if (updateRes.ok && updateResult.success) {
                        console.log(`Server lot status update successful for Lot ID ${lotId}: Pending`);
                        
                        // Set sessionStorage flag for client-side map visualization
                        sessionStorage.setItem("lotStatusUpdate", JSON.stringify({
                            id: lotId,
                            status: updateStatus 
                        }));
                    } else {
                        console.warn(`Server lot status update failed: ${updateResult.message || 'Unknown update error.'}`);
                    }
                } catch (updateError) {
                    console.error("Error calling update_lot.php:", updateError);
                }
            } 
            // 🛑 END NEW CODE BLOCK

            alert("✅ Reservation submitted successfully! The lot is now marked Pending. Redirecting to the map...");
            form.reset();

            // ✅ Reset uploaded files display
            if (typeof fileMap !== "undefined") {
                Object.keys(fileMap).forEach((key) => URL.revokeObjectURL(fileMap[key].url));
            }
            document.querySelectorAll(".file-name").forEach((span) => {
                span.textContent = "No file chosen";
                span.style.color = "#6c757d";
            });

            // ✅ Refresh history table (resets full data and reloads page 1)
            fullReservationData = [];
            loadReservationHistory(); 

            // ✅ Redirect after success
            setTimeout(() => {
                // Redirect to the map page so it can read the sessionStorage flag
                window.location.href = "../cemeteryMap/cemeteryMap.html"; 
            }, 1500);
        } else {
            alert("❌ Error: " + (result.message || "Unknown error occurred."));
        }
    } catch (err) {
        console.error("Reservation submission failed:", err);
        alert("❌ An unexpected error occurred: " + err.message);
    }
});


    // ---- File preview modal (Original logic preserved) ----
    function openFileInModal(file, url, inputEl, isHistoryView = false) {
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
        
        // ✅ ADDED: Center the content in the modal body to fix the left-alignment issue
        if(modalBody) modalBody.classList.add('text-center');


        const fileType = file ? file.type : '';
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