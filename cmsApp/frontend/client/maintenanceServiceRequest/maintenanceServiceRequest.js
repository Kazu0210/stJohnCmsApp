document.addEventListener("DOMContentLoaded", async () => {
    const API_BASE_URL = "../../../../cms.api/";

    const reservationSelect = document.getElementById("reservationId");
    const requestHistoryBody = document.getElementById("requestHistoryBody");
    const form = document.getElementById("maintenance-form");
    const logoutLink = document.getElementById("logoutLink");
    const userNameDisplay = document.getElementById("user-name-display-desktop");
    const reservedLotsCount = document.querySelector(".panel span.fw-bold");

    // ================================
    // Load Logged-in User Name
    // ================================
    async function loadUserName() {
        try {
            const response = await fetch(`${API_BASE_URL}displayname.php`, {
                credentials: "include",
            });
            const data = await response.json();

            console.log("displayname.php response:", data);

            if (data.status === "success" && data.fullName) {
                userNameDisplay.textContent = data.fullName;
            } else {
                console.warn("No valid user name found:", data);
            }
        } catch (error) {
            console.error("Error fetching user name:", error);
        }
    }

    // ================================
    // Load Reserved Lots (for dropdown)
    // ================================
    async function loadReservedLots() {
        try {
            const sessionUserIdInput = document.getElementById('sessionUserId');
            const sessionUserId = sessionUserIdInput ? sessionUserIdInput.value : null;
            if (!sessionUserId) {
                reservationSelect.innerHTML = '<option value="">-- Select Lot --</option>';
                return;
            }
            const res = await fetch(`/stJohnCmsApp/cms.api/fetchClientLots.php?userId=${sessionUserId}`);
            const result = await res.json();
            const data = result.data || [];

            reservationSelect.innerHTML = '<option value="">-- Select Lot --</option>';

            if (Array.isArray(data) && data.length > 0) {
                data.forEach(lot => {
                    const option = document.createElement("option");
                    option.value = lot.reservationId;
                    option.textContent = `Area ${lot.area}, Block ${lot.block}, Row ${lot.rowNumber}, Lot ${lot.lotNumber}`;
                    reservationSelect.appendChild(option);
                });
                if (reservedLotsCount) reservedLotsCount.textContent = data.length;
            } else {
                const option = document.createElement("option");
                option.textContent = "No lots reserved";
                option.disabled = true;
                reservationSelect.appendChild(option);
                if (reservedLotsCount) reservedLotsCount.textContent = "0";
            }
        } catch (err) {
            console.error("Error loading reserved lots:", err);
        }
    }

    // ================================
    // Load Maintenance Request History
    // ================================
    async function loadHistory() {
        try {
            const res = await fetch(`${API_BASE_URL}getmaintenanceRequest.php?mode=history`, {
                credentials: "include",
            });
            const result = await res.json();
            const data = result.data || [];

            requestHistoryBody.innerHTML = "";

            if (Array.isArray(data) && data.length > 0) {
                data.forEach(req => {
                    const row = `
                        <tr>
                            <td>${req.area}</td>
                            <td>${req.block}</td>
                            <td>${req.rowNumber}</td>
                            <td>${req.lotNumber}</td>
                            <td>${req.serviceType}</td>
                            <td>${req.status}</td>
                            <td>${req.requestedDate}</td>
                            <td>${req.notes || ""}</td>
                        </tr>
                    `;
                    requestHistoryBody.insertAdjacentHTML("beforeend", row);
                });
            } else {
                requestHistoryBody.innerHTML =
                    `<tr><td colspan="8" class="text-center">No maintenance requests found.</td></tr>`;
            }
        } catch (err) {
            console.error("Error loading maintenance history:", err);
            requestHistoryBody.innerHTML =
                `<tr><td colspan="8" class="text-center text-danger">Error loading data.</td></tr>`;
        }
    }

    // ================================
    // Handle Maintenance Form Submission
    // ================================
    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            if (!confirm("Are you sure you want to submit this maintenance request?")) return;

            try {
                const formData = new FormData(form);
                const res = await fetch(`${API_BASE_URL}clientMaintenanceRequest.php`, {
                    method: "POST",
                    body: formData,
                    credentials: "include",
                });

                const result = await res.json();
                alert(result.message);

                form.reset();
                loadHistory(); // Refresh request history
            } catch (err) {
                console.error("Error submitting maintenance request:", err);
            }
        });
    }

    // ================================
    // Logout Handler
    // ================================
    if (logoutLink) {
        logoutLink.addEventListener("click", async () => {
            if (confirm("Are you sure you want to log out?")) {
                try {
                    await fetch(`${API_BASE_URL}logout.php`, { credentials: "include" });
                    window.location.href = "../../auth/login/login.php";
                } catch (err) {
                    console.error("Error logging out:", err);
                }
            }
        });
    }

    // ================================
    // Initial Load
    // ================================
    loadUserName();
    loadReservedLots();
    loadHistory();

});
