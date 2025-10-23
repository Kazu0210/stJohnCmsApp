document.addEventListener("DOMContentLoaded", function () {

    /* -----------------------------
        RESPONSIVE CONTROLS (for a table view page)
    ------------------------------ */

    const tableWrapper = document.querySelector(".table-responsive");
    function handleTableResize() {
        if (window.innerWidth < 768) {
            tableWrapper?.classList.add("overflow-auto");
        } else {
            tableWrapper?.classList.remove("overflow-auto");
        }
    }
    
    if (tableWrapper) {
        handleTableResize();
        window.addEventListener("resize", handleTableResize);
    }
    

    /* -----------------------------
        MOCK DATA INITIALIZATION
    ------------------------------ */
    let reservationData = [
        {
            id: 1,
            client: "John Doe",
            lotNumber: "A-101",
            date: "2025-10-03",
            status: "reserved",
            document: "sample-doc.pdf"
        },
        {
            id: 2,
            client: "Jane Smith",
            lotNumber: "B-204",
            date: "2025-09-29",
            status: "pending",
            document: null
        },
        {
            id: 3,
            client: "Carlos Reyes",
            lotNumber: "C-310",
            date: "2025-08-20",
            status: "cancelled",
            document: "cancelled-reservation.pdf"
        }
    ];

    const tableBody = document.querySelector("#reservationTable tbody");
    const viewModalElement = document.getElementById("viewModal");
    const viewModal = viewModalElement ? new bootstrap.Modal(viewModalElement) : null;
    const modalTitle = document.getElementById("viewModalLabel");
    const modalBody = document.querySelector("#viewModal .modal-body");
    const pdfContainer = document.getElementById("pdfContainer");
    
    if (!tableBody) return;


    /* -----------------------------
        TABLE POPULATION
    ------------------------------ */
    function renderTable() {
        tableBody.innerHTML = "";
        reservationData.forEach((res) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${res.id}</td>
                <td>${res.client}</td>
                <td>${res.lotNumber}</td>
                <td>${res.date}</td>
                <td class="text-capitalize">${res.status}</td>
                <td>
                    <button class="btn btn-sm btn-warning view-btn" data-id="${res.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="${res.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        updateDashboardStats();
    }

    /* -----------------------------
        VIEW MODAL HANDLER
    ------------------------------ */
    tableBody.addEventListener("click", function (e) {
        const viewBtn = e.target.closest(".view-btn");
        const deleteBtn = e.target.closest(".delete-btn");

        if (viewBtn && viewModal) { 
            const id = parseInt(viewBtn.getAttribute("data-id"));
            const res = reservationData.find((r) => r.id === id);

            if (res) {
                 if (modalTitle) modalTitle.textContent = `Reservation #${res.id}`;
                 if (modalBody) modalBody.innerHTML = `
                    <p><strong>Client:</strong> ${res.client}</p>
                    <p><strong>Lot Number:</strong> ${res.lotNumber}</p>
                    <p><strong>Date:</strong> ${res.date}</p>
                    <p><strong>Status:</strong> ${res.status}</p>
                    <p><strong>Document:</strong> ${res.document ? res.document : "None"}</p>
                 `;

                if (pdfContainer) {
                    if (res.document) {
                        pdfContainer.innerHTML = `
                            <iframe src="docs/${res.document}" width="100%" height="400px"></iframe>
                        `;
                    } else {
                        pdfContainer.innerHTML = `<p class="text-muted">No document uploaded.</p>`;
                    }
                }

                viewModal.show();
            }
        }

        if (deleteBtn) {
            const id = parseInt(deleteBtn.getAttribute("data-id"));
            const confirmed = confirm("Are you sure you want to delete this record?");
            if (confirmed) {
                reservationData = reservationData.filter((r) => r.id !== id);
                renderTable();
            }
        }
    });

    /* -----------------------------
        ADD NEW RESERVATION
    ------------------------------ */
    const addForm = document.getElementById("addForm");
    if (addForm) {
        addForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const client = document.getElementById("clientName").value.trim();
            const lotNumber = document.getElementById("lotNumber").value.trim();
            const date = document.getElementById("reservationDate").value;
            const status = document.getElementById("status").value;

            const newRecord = {
                id: reservationData.length > 0 ? Math.max(...reservationData.map(r => r.id)) + 1 : 1,
                client,
                lotNumber,
                date,
                status,
                document: null
            };

            reservationData.push(newRecord);
            renderTable();
            addForm.reset();
            
            const addModalElement = document.getElementById("addModal");
            const addModalInstance = addModalElement ? bootstrap.Modal.getInstance(addModalElement) : null;
            if (addModalInstance) addModalInstance.hide();
        });
    }

    /* -----------------------------
        DASHBOARD STATS COUNTER (Minimal function for this page)
    ------------------------------ */
    function updateDashboardStats() {
        const total = reservationData.length;
        const reserved = reservationData.filter(r => r.status === "reserved").length;
        const pending = reservationData.filter(r => r.status === "pending").length;
        const cancelled = reservationData.filter(r => r.status === "cancelled").length;

        const statElements = {
            total: document.getElementById("statTotal"),
            reserved: document.getElementById("statReserved"),
            pending: document.getElementById("statPending"),
            cancelled: document.getElementById("statCancelled"),
        };

        for (const [key, el] of Object.entries(statElements)) {
            if (el) {
                let value;
                switch(key) {
                    case 'total': value = total; break;
                    case 'reserved': value = reserved; break;
                    case 'pending': value = pending; break;
                    case 'cancelled': value = cancelled; break;
                    default: value = 0;
                }
                el.textContent = value;
            }
        }
    }

    /* -----------------------------
        INITIALIZATION CALLS
    ------------------------------ */
    renderTable();

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

});
