document.addEventListener("DOMContentLoaded", function () {
    // Fetch maintenance requests for the logged-in user
    async function fetchMaintenanceRequests(userId) {
        try {
            const response = await fetch(`../../../../cms.api/fetchClientMaintenanceRequests.php?userId=${userId}`, {
                method: "GET",
                credentials: "include"
            });
            if (!response.ok) {
                throw new Error("Failed to fetch maintenance requests");
            }
            const result = await response.json();
            if (result.success) {
                return result.data;
            } else {
                return [];
            }
        } catch (error) {
            console.error("Fetch error:", error);
            return [];
        }
    }
    // Fetch user's reserved lots from the database
    async function fetchUserLots() {
        try {
            const response = await fetch("../../../../cms.api/fetchUserLots.php", {
                method: "GET",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                }
            });

            if (!response.ok) {
                throw new Error("Failed to fetch user lots");
            }

            return await response.json();
        } catch (error) {
            console.error("Fetch error:", error);
            return { status: "error", data: [] };
        }
    }
    
    function renderStats(stats) {
        if (!stats) return;

        // Top overview cards
        document.querySelector(".dashboard-overview .col-sm-6:nth-child(1) .fs-4").textContent = stats.reservedLots;
        document.querySelector(".dashboard-overview .col-sm-6:nth-child(2) .fs-4").textContent = `₱${stats.totalPaid.toLocaleString()}`;
        document.querySelector(".dashboard-overview .col-sm-6:nth-child(3) .fs-4").textContent = `₱${stats.outstandingBalance.toLocaleString()}`;
        document.querySelector(".dashboard-overview .col-sm-6:nth-child(4) .fs-4").textContent = stats.activeRequests;
        document.querySelector(".dashboard-overview .col-sm-12 .fs-4").textContent = stats.upcomingPayment;

        // Payment progress
        const progressPercent = Math.round((stats.totalPaid / stats.totalAmount) * 100);
        const progressBar = document.querySelector(".progress-bar");
        progressBar.style.width = progressPercent + "%";
        progressBar.setAttribute("aria-valuenow", progressPercent);
        progressBar.textContent = progressPercent + "%";
        document.querySelector(".payment-progress p").textContent =
            `₱${stats.totalPaid.toLocaleString()} Paid / ₱${stats.totalAmount.toLocaleString()} Total`;
    }

    function renderReservedLots(reservedLots) {
        const tbody = document.querySelector(".custom-table tbody");
        tbody.innerHTML = "";

        if (!reservedLots || reservedLots.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">No reserved lots found</td></tr>`;
            return;
        }

        reservedLots.forEach(lot => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${lot.clientName}</td>
                <td>${lot.area}</td>
                <td>${lot.block}</td>
                <td>1</td>
                <td>${lot.lotNumber}</td>
                <td><span class="status ${getStatusClass(lot.status)}">${lot.status}</span></td>
            `;
            tbody.appendChild(row);
        });

        // Update the reserved lots count in the dashboard panels
        document.getElementById('reservedLotsCount').textContent = reservedLots.length;
    }

    function renderServiceRequests(requests) {
        const listGroup = document.getElementById("serviceRequestsList");
        listGroup.innerHTML = "";
        if (!requests || requests.length === 0) {
            listGroup.innerHTML = `<li class='list-group-item text-center'>No active requests</li>`;
            return;
        }
        requests.forEach(req => {
            const li = document.createElement("li");
            li.className = "list-group-item d-flex justify-content-between align-items-center";
            li.innerHTML = `
                <span>${req.serviceType} ${(req.lotNumber ? `(Lot ${req.lotNumber})` : '')}</span>
                <span class="status ${getStatusClass(req.status)}">${req.status}</span>
            `;
            listGroup.appendChild(li);
        });
    }

    function getStatusClass(status) {
        switch (status?.toLowerCase()) {
            case "reserved": return "in-progress";
            case "occupied": return "completed";
            case "available": return "pending";
            case "for reservation": return "pending";
            case "paid": return "completed";
            case "partially paid": return "pending";
            case "pending": return "pending";
            case "in progress": return "in-progress";
            case "completed": return "completed";
            case "cancelled": return "cancelled";
            default: return "";
        }
    }


    // LOGOUT HANDLER
    // =============================
    function handleLogoutRedirect(link) {
        // Clear client-side data
        localStorage.clear();
        sessionStorage.clear();

        const targetUrl = link.getAttribute("href") && link.getAttribute("href") !== "javascript:void(0);"
            ? link.getAttribute("href")
            : "../../auth/login/login.php";

        window.location.href = targetUrl;
    }

    ["logoutLinkDesktop", "logoutLinkMobile"].forEach(id => {
        const link = document.getElementById(id);
        if (link) {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                handleLogoutRedirect(link);
            });
        }
    });



    // Initialize dashboard
    async function initDashboard() {
        const lotsResponse = await fetchUserLots();
        if (lotsResponse.status === 'success') {
            renderReservedLots(lotsResponse.data);
        } else {
            console.error("Failed to load lots:", lotsResponse.message);
            renderReservedLots([]); // Show empty table
        }

        // Get userId from PHP session (injected into JS)
        const userId = window.userId;
        if (userId) {
            const requests = await fetchMaintenanceRequests(userId);
            renderServiceRequests(requests);
        } else {
            renderServiceRequests([]);
        }
    }

    // Expose to global scope in case it's called from outside
    window.initDashboard = initDashboard;
    initDashboard();
});
