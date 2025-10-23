// ✅ Configure PDF.js worker globally
if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
}

document.addEventListener("DOMContentLoaded", () => {
    // ---- API Base URL ----
    const API_BASE_URL = "http://localhost/stJohnCmsApp/cms.api/";

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
    const logoutLinks = [
        document.getElementById("logoutLinkDesktop"),
        document.getElementById("logoutLinkMobile"),
    ];

    // Modal and Viewer Elements
    const docModalEl = document.getElementById("docModal");
    const docModal = new bootstrap.Modal(docModalEl);
    const modalDeceasedName = document.getElementById("modalDeceasedName");
    const pdfCanvas = document.getElementById("pdf-canvas");
    const imageContainer = document.getElementById("image-container");
    const pdfControls = document.getElementById("pdfControls");
    const prevPageBtn = document.getElementById("prevPage");
    const nextPageBtn = document.getElementById("nextPage");
    const pageInfo = document.getElementById("pageInfo");
    const downloadLink = document.getElementById("downloadLink");

    // ---- State ----
    let allBurials = [];
    let currentPdf = null;
    let currentPage = 1;

    // ✅ Load logged-in user's name
    async function loadUserName() {
        try {
            const res = await fetch(`${API_BASE_URL}displayname.php`, { credentials: "include" });
            const data = await res.json();
            const displayName = data.status === "success" && data.fullName ? data.fullName : "Guest";
            document.getElementById("user-name-display-mobile").textContent = displayName;
            document.getElementById("user-name-display-desktop").textContent = displayName;
        } catch (err) {
            console.error("Error fetching user name:", err);
        }
    }

    // ✅ Fetch burial records
    async function loadBurials() {
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
            tr.innerHTML = `
                <td>${row.deceasedName || "-"}</td>
                <td>${row.burialDate || "-"}</td>
                <td>${row.area || "-"}</td>
                <td>${row.block || "-"}</td>
                <td>${row.rowNumber || "-"}</td>
                <td>${row.lotNumber || "-"}</td>
                <td class="text-center">${
                    row.deceasedValidId
                        ? `<a href="#" class="view-doc" data-id="${row.reservationId}" data-type="valid_id" data-name="${row.deceasedName}">View</a>`
                        : "N/A"
                }</td>
                <td class="text-center">${
                    row.deathCertificate
                        ? `<a href="#" class="view-doc" data-id="${row.reservationId}" data-type="death_cert" data-name="${row.deceasedName}">View</a>`
                        : "N/A"
                }</td>
                <td class="text-center">${
                    row.burialPermit
                        ? `<a href="#" class="view-doc" data-id="${row.reservationId}" data-type="burial_permit" data-name="${row.deceasedName}">View</a>`
                        : "N/A"
                }</td>
            `;
            resultsBody.appendChild(tr);
        });
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

        try {
            const res = await fetch(url, { credentials: "include" });
            if (!res.ok) throw new Error("File not found or inaccessible.");

            const blob = await res.blob();
            const fileUrl = URL.createObjectURL(blob);
            const mime = blob.type;

            // PDF file
            if (mime === "application/pdf") {
                pdfCanvas.style.display = "block";
                const loadingTask = pdfjsLib.getDocument(fileUrl);
                currentPdf = await loadingTask.promise;
                await renderPdfPage(currentPage);
                pdfControls.style.display = "flex";
            }
            // Image file
            else if (mime.startsWith("image/")) {
                imageContainer.style.display = "block";
                imageContainer.innerHTML = `<img src="${fileUrl}" class="img-fluid rounded shadow" alt="Document Preview">`;
            } else {
                throw new Error("Unsupported file type.");
            }
        } catch (err) {
            console.error("Error loading document:", err);
            alert("Unable to preview document. It may be missing, corrupt, or not supported.");
            docModal.hide();
        }
    }

    // ✅ Render PDF page
    async function renderPdfPage(num) {
        const page = await currentPdf.getPage(num);
        const viewport = page.getViewport({ scale: 1.5 });
        const context = pdfCanvas.getContext("2d");
        pdfCanvas.height = viewport.height;
        pdfCanvas.width = viewport.width;
        await page.render({ canvasContext: context, viewport }).promise;
        pageInfo.textContent = `Page ${num} of ${currentPdf.numPages}`;
        prevPageBtn.disabled = num <= 1;
        nextPageBtn.disabled = num >= currentPdf.numPages;
    }

    // ---- Event Listeners ----
    Object.values(searchInputs).forEach((input) => input.addEventListener("input", renderBurials));

    clearBtn.addEventListener("click", () => {
        Object.values(searchInputs).forEach((input) => (input.value = ""));
        renderBurials();
    });

    // ✅ View document links
    resultsBody.addEventListener("click", (e) => {
        if (e.target.classList.contains("view-doc")) {
            e.preventDefault();
            const { id, type, name } = e.target.dataset;
            openDocument(id, type, name);
        }
    });

    // ✅ Logout buttons (call logout API then redirect)
    logoutLinks.forEach((link) => {
        if (link)
            link.addEventListener("click", async (e) => {
                e.preventDefault();
                try {
                    const res = await fetch("/stJohnCmsApp/cms.api/logout.php", {
                        method: "GET",
                        credentials: "include"
                    });
                    // Always redirect to login page after logout
                    window.location.href = "../../auth/login/login.php";
                } catch (err) {
                    // Fallback: still redirect
                    window.location.href = "../../auth/login/login.php";
                }
            });
    });

    // ✅ PDF page navigation
    prevPageBtn.addEventListener("click", () => {
        if (currentPdf && currentPage > 1) renderPdfPage(--currentPage);
    });
    nextPageBtn.addEventListener("click", () => {
        if (currentPdf && currentPage < currentPdf.numPages) renderPdfPage(++currentPage);
    });

    // ---- Initial Load ----
    loadUserName();
    loadBurials();
});
