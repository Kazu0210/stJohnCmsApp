// ✅ Configure PDF.js worker globally
if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
}

document.addEventListener("DOMContentLoaded", () => {
    // ---- API Base URL ----
    const API_BASE_URL = `/stJohnCmsApp/cms.api/`;

    // ---- Element Selectors ----
    const resultsBody = document.getElementById("resultsBody");
    const searchInputs = {
        name: document.getElementById("searchName"),
        area: document.getElementById("filterArea"),
        block: document.getElementById("filterBlock"),
        row: document.getElementById("filterRow"),
        lot: document.getElementById("filterLot"),
    };
    const clearBtn = document.getElementById("clearBtn");
    
    // Modal and Viewer Elements
    const docModalEl = document.getElementById("docModal");
    // Ensure modal instance is created before use
    const docModal = new bootstrap.Modal(docModalEl, { keyboard: true }); 
    
    const modalDeceasedName = document.getElementById("modalDeceasedName");
    const pdfCanvas = document.getElementById("pdf-canvas");
    const ctx = pdfCanvas.getContext("2d"); // Get context once
    const imageContainer = document.getElementById("image-container");
    const pdfControls = document.getElementById("pdfControls");
    const prevPageBtn = document.getElementById("prevPage");
    const nextPageBtn = document.getElementById("nextPage");
    const pageInfo = document.getElementById("pageInfo");
    const downloadLink = document.getElementById("downloadLink");

    // Burial Request Elements
    const reservedLotSelect = document.getElementById("reservedLotSelect");
    const submitBurialRequestBtn = document.getElementById("submitBurialRequest");

    // ---- State ----
    let allBurials = [];
    let currentPdf = null;
    let currentPage = 1;
    let reservedLots = [];
    const PDF_RENDER_SCALE = 1.5; // Scale for PDF rendering

    // ✅ Load logged-in user's name
    async function loadUserName() {
        try {
            // Using ../../../cms.api/logout.php as a reference, adjust path for displayname.php
            const res = await fetch(`/stJohnCmsApp/cms.api/displayname.php`, { credentials: "include" });
            const data = await res.json();
            const displayName = data.status === "success" && data.fullName ? data.fullName : "Client User";
            document.getElementById("user-name-display-mobile").textContent = displayName;
            document.getElementById("user-name-display-desktop").textContent = displayName;
        } catch (err) {
            console.error("Error fetching user name:", err);
            // Defaulting to "Client User" or "Guest" on error is acceptable
        }
    }

    // --- Burial Request Functions ---

    // ✅ Load reserved lots for burial request
    async function loadReservedLots() {
        reservedLotSelect.innerHTML = '<option value="">Loading your reserved lots...</option>';
        try {
            const res = await fetch(`${API_BASE_URL}getReservedLots.php`, { credentials: "include" });
            const data = await res.json();

            if (data.status === "success" && Array.isArray(data.data)) {
                reservedLots = data.data;
                populateReservedLotSelect();
            } else {
                console.error("Failed to load reserved lots:", data.message);
                reservedLotSelect.innerHTML = '<option value="">No reserved lots found</option>';
            }
        } catch (err) {
            console.error("Error loading reserved lots:", err);
            reservedLotSelect.innerHTML = '<option value="">Error loading lots</option>';
        }
    }

    // ✅ Populate reserved lot select dropdown
    function populateReservedLotSelect() {
        reservedLotSelect.innerHTML = '<option value="">Select a reserved lot...</option>';
        
        reservedLots.forEach(lot => {
            const option = document.createElement("option");
            option.value = lot.reservationId;
            // Display Lot ID for reference, but use location details for clarity
            option.textContent = `[ID: ${lot.reservationId}] ${lot.area} - Block ${lot.block}, Row ${lot.rowNumber}, Lot ${lot.lotNumber}`; 
            option.dataset.lot = JSON.stringify(lot);
            reservedLotSelect.appendChild(option);
        });
    }

    // ✅ Handle lot selection change
    function handleLotSelection() {
        const selectedOption = reservedLotSelect.options[reservedLotSelect.selectedIndex];
        const lotInfo = document.getElementById("selectedLotInfo");
        
        if (selectedOption.value) {
            const lot = JSON.parse(selectedOption.dataset.lot);
            document.getElementById("lotArea").textContent = lot.area || "-";
            document.getElementById("lotBlock").textContent = lot.block || "-";
            document.getElementById("lotRow").textContent = lot.rowNumber || "-";
            document.getElementById("lotNumber").textContent = lot.lotNumber || "-";
            lotInfo.style.display = "block";
        } else {
            lotInfo.style.display = "none";
        }
    }

    // ✅ Submit burial request
    async function submitBurialRequest() {
        const form = document.getElementById("burialRequestForm");
        const formData = new FormData();
        
        const selectedOption = reservedLotSelect.options[reservedLotSelect.selectedIndex];
        
        if (!selectedOption.value) {
            alert("Please select a reserved lot.");
            return;
        }
        
        const selectedLot = JSON.parse(selectedOption.dataset.lot);
        
        // Use checkValidity() for native form validation appearance
        if (!form.checkValidity()) {
             form.classList.add('was-validated');
             alert("Please fill in all required fields and upload all required documents.");
             return;
        }
        
        // Prepare form data
        formData.append("reservationId", selectedLot.reservationId);
        formData.append("lotId", selectedLot.lotId); // Assuming lotId is needed by API
        formData.append("deceasedName", document.getElementById("deceasedName").value.trim());
        formData.append("burialDate", document.getElementById("burialDate").value);
        formData.append("burialDepth", document.getElementById("burialDepth").value);
        formData.append("deceasedValidId", document.getElementById("deceasedValidId").files[0]);
        formData.append("deathCertificate", document.getElementById("deathCertificate").files[0]);
        formData.append("burialPermit", document.getElementById("burialPermit").files[0]);
        formData.append("notes", document.getElementById("burialNotes").value.trim());
        
        try {
            submitBurialRequestBtn.disabled = true;
            submitBurialRequestBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';
            
            const res = await fetch(`${API_BASE_URL}submitBurialRequest.php`, {
                method: "POST",
                body: formData,
                credentials: "include"
            });
            
            const data = await res.json();
            
            if (data.status === "success") {
                alert("Burial request submitted successfully! The admin/secretary will review your request.");
                // Get the modal instance to hide it
                const burialRequestModal = bootstrap.Modal.getInstance(document.getElementById("burialRequestModal"));
                if (burialRequestModal) burialRequestModal.hide(); 
                
                form.reset();
                form.classList.remove('was-validated'); // Remove validation styles
                document.getElementById("selectedLotInfo").style.display = "none";
                loadBurials(); // Refresh burial records
            } else {
                alert("Error submitting burial request: " + (data.message || "Unknown error"));
            }
        } catch (err) {
            console.error("Error submitting burial request:", err);
            alert("Error submitting burial request. Please check your connection and try again.");
        } finally {
            submitBurialRequestBtn.disabled = false;
            submitBurialRequestBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Request';
        }
    }

    // --- Burial Record and Document Viewer Functions ---

    // ✅ Fetch burial records
    async function loadBurials() {
        resultsBody.innerHTML = '<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Loading records...</td></tr>';
        try {
            const res = await fetch(`${API_BASE_URL}fetchBurials.php`, { credentials: "include" });
            const data = await res.json();

            if (data.status === "success" && Array.isArray(data.data)) {
                allBurials = data.data;
                renderBurials();
            } else {
                resultsBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">${
                    data.message || "Failed to load records."
                }</td></tr>`;
            }
        } catch (err) {
            console.error("Error loading burials:", err);
            resultsBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">Error connecting to the server.</td></tr>`;
        }
    }

    // ✅ Render filtered burial records
    function renderBurials() {
        const filters = {
            name: searchInputs.name.value.toLowerCase().trim(),
            area: searchInputs.area.value.toLowerCase().trim(),
            block: searchInputs.block.value.toLowerCase().trim(),
            row: searchInputs.row.value.toLowerCase().trim(),
            lot: searchInputs.lot.value.toLowerCase().trim(),
        };

        const filtered = allBurials.filter(
            (b) =>
                (!filters.name || (b.deceasedName || "").toLowerCase().includes(filters.name)) &&
                (!filters.area || (b.area || "").toLowerCase().includes(filters.area)) &&
                (!filters.block || (b.block || "").toLowerCase().includes(filters.block)) &&
                (!filters.row || (b.rowNumber || "").toLowerCase().includes(filters.row)) &&
                (!filters.lot || (b.lotNumber || "").toLowerCase().includes(filters.lot))
        );

        resultsBody.innerHTML = "";

        if (filtered.length === 0) {
            resultsBody.innerHTML = `<tr><td colspan="9" class="text-center">No records found matching your criteria.</td></tr>`;
            return;
        }

        filtered.forEach((row) => {
            const tr = document.createElement("tr");
            
            // Helper function to create the document link button/text
            const createDocLink = (docPath, docType, deceasedName) => {
                if (!docPath) return '<span class="text-danger">N/A</span>';
                
                return `<a href="#" class="view-doc" data-id="${row.reservationId}" data-type="${docType}" data-name="${deceasedName}">View</a>`;
            };

            tr.innerHTML = `
                <td>${row.deceasedName || "-"}</td>
                <td>${row.burialDate || "-"}</td>
                <td>${row.area || "-"}</td>
                <td>${row.block || "-"}</td>
                <td>${row.rowNumber || "-"}</td>
                <td>${row.lotNumber || "-"}</td>
                <td class="text-center">${createDocLink(row.deceasedValidId, 'valid_id', row.deceasedName)}</td>
                <td class="text-center">${createDocLink(row.deathCertificate, 'death_cert', row.deceasedName)}</td>
                <td class="text-center">${createDocLink(row.burialPermit, 'burial_permit', row.deceasedName)}</td>
            `;
            resultsBody.appendChild(tr);
        });
    }
    
    // ✅ MISSING FUNCTION: Render PDF page
    async function renderPdfPage(num) {
        if (!currentPdf) return;

        currentPage = num;
        
        try {
            const page = await currentPdf.getPage(num);
            const viewport = page.getViewport({ scale: PDF_RENDER_SCALE });
            
            // Set canvas dimensions
            pdfCanvas.height = viewport.height;
            pdfCanvas.width = viewport.width;

            // Render the page
            await page.render({ canvasContext: ctx, viewport }).promise;
            
            // Update controls
            pageInfo.textContent = `Page ${num} of ${currentPdf.numPages}`;
            prevPageBtn.disabled = num <= 1;
            nextPageBtn.disabled = num >= currentPdf.numPages;
            
        } catch (error) {
            console.error("Error rendering PDF page:", error);
            // Optionally, show a message on the canvas or in the modal body
        }
    }


    // ✅ Open & preview uploaded document
    async function openDocument(reservationId, docType, deceasedName) {
        modalDeceasedName.textContent = deceasedName;

        const url = `${API_BASE_URL}getDocument.php?id=${reservationId}&doc=${docType}`;
        downloadLink.href = url;

        // Reset viewer
        pdfCanvas.style.display = "none";
        imageContainer.style.display = "none";
        imageContainer.innerHTML = "";
        pdfControls.style.display = "none";
        currentPdf = null;
        currentPage = 1;

        docModal.show();
        imageContainer.innerHTML = '<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading document...</p></div>';
        imageContainer.style.display = "block";


        try {
            const res = await fetch(url, { credentials: "include" });
            if (!res.ok) throw new Error("File not found or inaccessible.");

            const blob = await res.blob();
            const fileUrl = URL.createObjectURL(blob);
            const mime = blob.type;

            imageContainer.innerHTML = ""; // Clear loading message

            // PDF file
            if (mime === "application/pdf" || url.toLowerCase().includes('.pdf')) {
                pdfCanvas.style.display = "block";
                imageContainer.style.display = "none";
                
                const loadingTask = pdfjsLib.getDocument(fileUrl);
                currentPdf = await loadingTask.promise;
                await renderPdfPage(currentPage);
                pdfControls.style.display = "flex";
            }
            // Image file
            else if (mime.startsWith("image/") || /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(url)) {
                imageContainer.style.display = "block";
                imageContainer.innerHTML = `<img src="${fileUrl}" class="img-fluid rounded shadow" alt="Document Preview" style="max-height: 70vh; margin: auto; display: block;">`;
                pdfCanvas.style.display = "none";
            } 
            // Other/Unknown file type - show as download link
            else {
                imageContainer.style.display = "block";
                imageContainer.innerHTML = `
                    <div class="text-center p-4">
                        <i class="fas fa-file fa-3x text-muted mb-3"></i>
                        <p class="text-muted">This file type (${mime || 'unknown'}) cannot be previewed.</p>
                        <a href="${url}" target="_blank" download class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Download File
                        </a>
                    </div>
                `;
                pdfCanvas.style.display = "none";
            }
        } catch (err) {
            console.error("Error loading document:", err);
            imageContainer.innerHTML = `
                <div class="text-center p-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <p>Unable to preview document. It may be missing or inaccessible.</p>
                </div>`;
            pdfCanvas.style.display = "none";
            pdfControls.style.display = "none";
        }
    }

    // ---- Event Listeners ----
    
    // 1. Search/Filter
    Object.values(searchInputs).forEach((input) => input.addEventListener("input", renderBurials));

    // 2. Clear Button
    clearBtn.addEventListener("click", () => {
        Object.values(searchInputs).forEach((input) => (input.value = ""));
        renderBurials();
    });

    // 3. View document links (delegated listener)
    resultsBody.addEventListener("click", (e) => {
        if (e.target.classList.contains("view-doc")) {
            e.preventDefault();
            const { id, type, name } = e.target.dataset;
            openDocument(id, type, name);
        }
    });

    // 4. PDF page navigation
    prevPageBtn.addEventListener("click", () => {
        if (currentPdf && currentPage > 1) renderPdfPage(currentPage - 1);
    });
    nextPageBtn.addEventListener("click", () => {
        if (currentPdf && currentPage < currentPdf.numPages) renderPdfPage(currentPage + 1);
    });

    // 5. Burial request event listeners
    document.getElementById("burialRequestModal").addEventListener('show.bs.modal', loadReservedLots); // Load lots when modal opens
    reservedLotSelect.addEventListener("change", handleLotSelection);
    submitBurialRequestBtn.addEventListener("click", submitBurialRequest);

    // 6. Logout links (The HTML links are already pointing to the PHP file, but this ensures a clean redirect)
    document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile').forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            window.location.href = "../../../cms.api/logout.php";
        });
    });


    // ---- Initial Load ----
    loadUserName();
    loadBurials();
    // loadReservedLots is called when the burialRequestModal is shown

});