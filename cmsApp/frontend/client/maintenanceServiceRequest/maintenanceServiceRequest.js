document.addEventListener("DOMContentLoaded", async () => {
    const API_BASE_URL = `http://localhost/stJohnCmsApp/cms.api/`;

    const reservationSelect = document.getElementById("reservationId");
    const requestHistoryBody = document.getElementById("requestHistoryBody");
    const form = document.getElementById("maintenance-form");
    const paginationControls = document.getElementById("paginationControls");

    // Pagination variables
    const ROWS_PER_PAGE = 10;
    let currentPage = 1;
    let fullHistoryData = []; // Store all requests

    // --- Load User Name ---
    async function loadUserName() {
        try {
            const res = await fetch(`${API_BASE_URL}displayname.php`, { credentials: "include" });
            const data = await res.json();
            
            const displayName = (data.status === "success" && data.fullName) ? data.fullName : "User Name";

            document.getElementById("user-name-display-desktop").textContent = displayName;
            document.getElementById("user-name-display-mobile").textContent = displayName; 
        } catch (err) {
            console.error("Error fetching user name:", err);
            // On error, revert to the default placeholder set in PHP
        }
    }
    

    // ============================
    // Load Reserved Lots
    // ============================
    async function loadReservedLots() {
        // Use the new API which can accept session-backed user id when none is provided
        const res = await fetch(`${API_BASE_URL}fetchClientReservedLots.php`, { credentials: "include" });
        const result = await res.json();
        const data = result.data || [];
        reservationSelect.innerHTML = '<option value="">-- Select Lot --</option>';

        if (result.status === "success" && data.length > 0) {
            data.forEach(lot => {
                const option = document.createElement("option");
                option.value = lot.reservationId;
                option.textContent = `Area ${lot.area}, Block ${lot.block}, Row ${lot.rowNumber}, Lot ${lot.lotNumber} (${lot.lot_type_name})`;
                reservationSelect.appendChild(option);
            });
        } else {
            const opt = document.createElement("option");
            opt.textContent = "No lots reserved";
            opt.disabled = true;
            reservationSelect.appendChild(opt);
        }
    }

    // ============================
    // Display Current History Page
    // ============================
    function displayHistoryPage(page) {
        currentPage = page;
        requestHistoryBody.innerHTML = "";

        if (fullHistoryData.length === 0) {
            requestHistoryBody.innerHTML = `<tr><td colspan="8" class="text-center">No maintenance requests found.</td></tr>`;
            setupPagination(); // Clear pagination
            return;
        }

        const start = (currentPage - 1) * ROWS_PER_PAGE;
        const end = start + ROWS_PER_PAGE;
        const pageRequests = fullHistoryData.slice(start, end);

        pageRequests.forEach(req => {
            requestHistoryBody.insertAdjacentHTML(
                "beforeend",
                `<tr>
                    <td>${req.area || 'N/A'}</td>
                    <td>${req.block || 'N/A'}</td>
                    <td>${req.rowNumber || 'N/A'}</td>
                    <td>${req.lotNumber || 'N/A'}</td>
                    <td>${req.serviceType || 'N/A'}</td>
                    <td><span class="status ${req.status ? req.status.toLowerCase().replace(/\s/g, '-') : 'pending'}">${req.status || 'N/A'}</span></td>
                    <td>${req.requestedDate || 'N/A'}</td>
                    <td>${req.notes || ''}</td>
                </tr>`
            );
        });

        setupPagination();
    }

    // ============================
    // Setup Pagination Controls
    // ============================
    function setupPagination() {
        paginationControls.innerHTML = "";
        const pageCount = Math.ceil(fullHistoryData.length / ROWS_PER_PAGE);

        if (pageCount <= 1) return;

        // Previous button
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        paginationControls.insertAdjacentHTML('beforeend',
            `<li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>`
        );

        // Page buttons
        for (let i = 1; i <= pageCount; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            paginationControls.insertAdjacentHTML('beforeend',
                `<li class="page-item ${activeClass}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`
            );
        }

        // Next button
        const nextDisabled = currentPage === pageCount ? 'disabled' : '';
        paginationControls.insertAdjacentHTML('beforeend',
            `<li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>`
        );
        
        // Add event listener to pagination links
        paginationControls.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(e.currentTarget.dataset.page);
                if (page > 0 && page <= pageCount && page !== currentPage) {
                    displayHistoryPage(page);
                }
            });
        });
    }

    // ============================
    // Load Maintenance History
    // ============================
    async function loadHistory() {
        const res = await fetch(`${API_BASE_URL}getMaintenanceRequest.php`, { credentials: "include" });
        const result = await res.json();
        fullHistoryData = result.data || [];
        
        // Display the first page upon load
        displayHistoryPage(1);
    }

    // ============================
    // Submit Maintenance Request
    // ============================
    if (form) {
        form.addEventListener("submit", async e => {
            e.preventDefault();
            if (!confirm("Are you sure you want to submit this maintenance request?")) return;

            const formData = new FormData(form);
            const res = await fetch(`${API_BASE_URL}clientMaintenanceRequest.php`, {
                method: "POST",
                body: formData,
                credentials: "include"
            });
            const result = await res.json();

            if (result.success) {
                alert(result.message);
                form.reset();
                loadHistory(); // Reload history after successful submission
            } else {
                alert(`Submission Failed: ${result.message}`);
            }
        });
    }

    // ============================
    // Initial Load
    // ============================
    loadUserName(); 
    loadReservedLots();
    loadHistory();
});