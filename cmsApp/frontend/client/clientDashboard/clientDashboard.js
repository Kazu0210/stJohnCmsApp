document.addEventListener("DOMContentLoaded", function () {
    async function fetchClientDashboardData() {
        try {
            const response = await fetch("../../api/dashboard.php", {
                method: "GET",
                credentials: "include", // include cookies for PHP session
                headers: {
                    "Content-Type": "application/json"
                }
            });

            if (!response.ok) {
                throw new Error("Failed to fetch dashboard data");
            }

            return await response.json();
        } catch (error) {
            console.error("Fetch error:", error);
            return null;
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
        const tbody = document.querySelector(".maintenance-history-table tbody");
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
                <td>${lot.row}</td>
                <td>${lot.lot}</td>
                <td><span class="status ${getStatusClass(lot.status)}">${lot.status}</span></td>
            `;
            tbody.appendChild(row);
        });
    }

    function renderRequests(requests) {
        const listGroup = document.querySelector(".service-requests .list-group");
        listGroup.innerHTML = "";

        if (!requests || requests.length === 0) {
            listGroup.innerHTML = `<li class="list-group-item text-center">No active requests</li>`;
            return;
        }

        requests.forEach(req => {
            const li = document.createElement("li");
            li.className = "list-group-item d-flex justify-content-between align-items-center";
            li.style.cssText = "border-left: 5px solid var(--gold); background-color: var(--light-bg); border-radius: 5px; margin-bottom: 8px;";
            li.innerHTML = `
                <div>${req.service}</div>
                <span class="status ${getStatusClass(req.status)}">${req.status}</span>
            `;
            listGroup.appendChild(li);
        });
    }

    function getStatusClass(status) {
        switch (status?.toLowerCase()) {
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



    initDashboard();
});
