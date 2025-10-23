document.addEventListener("DOMContentLoaded", () => {
    const bootstrap = window.bootstrap; 

    const endpoints = {
        get: '/stJohnCmsApp/cms.api/get_lots.php', 
        update: '/stJohnCmsApp/cms.api/update_lot.php', 
    };

    /* ---------------------- DOM ELEMENTS ---------------------- */
    const elements = {
        // ASSUMES a separate logout.php exists to destroy the session
        logout: document.getElementById("logoutLinkDesktop") || document.getElementById("logoutLinkMobile"),
        expandMap: document.getElementById("expandMapBtn"),
        cemeteryMap: document.getElementById("cemeteryMap"),
        lotList: document.querySelector(".lot-list"),
        lotSearch: document.getElementById("lotSearch"),
        prevPage: document.getElementById("prevPageBtn"),
        nextPage: document.getElementById("nextPageBtn"),
        pageInfo: document.getElementById("pageInfo"),
        editModal: document.getElementById("editLotModal"),
        editForm: document.getElementById("editLotForm"),
        
        // --- TOAST ELEMENTS ---
        toastElement: document.getElementById("liveToast"),
        toastBody: document.querySelector("#liveToast .toast-body"),
        toastTime: document.querySelector("#liveToast .toast-time"),
        userNameDisplayDesktop: document.getElementById("user-name-display-desktop"),
        userNameDisplayMobile: document.getElementById("user-name-display-mobile"),
    };

    /* ---------------------- STATE ---------------------- */
    let lots = [];
    let filteredLots = [];
    let currentPage = 1;
    const lotsPerPage = 5;
    let cachedVectorSource = null;
    let currentUserId = null; // State to hold the logged-in user's ID
    
    // --- TIMER STATE ---
    let pendingCountdownInterval = null;
    let pendingLotId = null; // ID of the lot currently being tracked

    /* ---------------------- TOAST INITIALIZATION ---------------------- */
    const lotToast = (elements.toastElement && bootstrap) 
        ? new bootstrap.Toast(elements.toastElement, {
            autohide: false 
        }) 
        : null;


    /* ---------------------- MODAL HANDLERS ---------------------- */
    function setupModal(modal) {
        if (!modal) return;
        modal.querySelector(".close-button")?.addEventListener("click", () => (modal.style.display = "none"));
        window.addEventListener("click", (e) => {
            if (e.target === modal) modal.style.display = "none";
        });
    }

    setupModal(elements.editModal);


    /* ---------------------- TIMER LOGIC ---------------------- */
    
    /**
     * Starts the 24-hour countdown for the client's pending lot.
     */
    function startPendingCountdown(lotId, datePending) {
        if (pendingCountdownInterval) clearInterval(pendingCountdownInterval);
        
        pendingLotId = lotId;

        // Parse datePending (it comes from MySQL as a string YYYY-MM-DD HH:MM:SS)
        const datePendingTime = new Date(datePending).getTime();
        if (isNaN(datePendingTime)) {
            console.error("Invalid datePending value:", datePending);
            lotToast?.hide();
            return;
        }
        // Calculate the 24-hour deadline
        const deadline = new Date(datePendingTime + 24 * 60 * 60 * 1000);
        
        if (!lotToast || !elements.toastBody || !elements.toastTime) {
             console.warn("Toast elements not found. Timer is inactive.");
             return;
        }
        
        function updateCountdown() {
            const now = new Date();
            const diff = deadline - now;

            if (diff <= 0) {
                // Timer expired
                if (elements.toastBody) elements.toastBody.innerHTML = `⚠️ **Time expired!** Your reservation for Lot #${pendingLotId} is now subject to cancellation. Complete payment on the <a href="../payment/payment.php" class="text-white fw-bold">Payment page</a> before expiration.`;
                if (elements.toastTime) elements.toastTime.textContent = "Expired!";
                
                // Change toast color to red
                elements.toastElement?.classList.add('bg-danger', 'text-white');
                elements.toastElement?.classList.remove('bg-light');
                
                clearInterval(pendingCountdownInterval);
                pendingCountdownInterval = null;
                pendingLotId = null;
                return;
            }

            // Calculate remaining time
            const hours = Math.floor(diff / (1000 * 60 * 60)).toString().padStart(2, '0');
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
            const seconds = Math.floor((diff % (1000 * 60)) / 1000).toString().padStart(2, '0');

            if (elements.toastBody) elements.toastBody.textContent = `You have ${hours}:${minutes}:${seconds} remaining to secure Lot #${pendingLotId}.`;
            if (elements.toastTime) elements.toastTime.textContent = "Counting down...";
        }
        
        // Reset toast styling to default
        elements.toastElement?.classList.remove('bg-danger', 'text-white');
        elements.toastElement?.classList.add('bg-light');

        updateCountdown(); 
        lotToast?.show(); 
        pendingCountdownInterval = setInterval(updateCountdown, 1000);
    }
    
    /**
     * Checks the loaded lots for this client's Pending lot to start the timer for.
     */
    function checkForPendingLot(lotData) {
        // If user ID isn't set (e.g., they logged out or session expired), hide the timer
        if (!currentUserId) {
            lotToast?.hide();
            return;
        }

        // Find the one and only lot that is 'Pending' AND belongs to the current user AND has a start time
        const pendingLot = lotData.find(lot => 
            String(lot.status).trim().toLowerCase() === "pending" && 
            String(lot.userId) === String(currentUserId) && 
            lot.datePending 
        );

        if (pendingLot) {
            // Start the countdown if it's a new lot or the interval isn't running
            if (pendingLotId !== pendingLot.lotId || pendingCountdownInterval === null) {
                startPendingCountdown(pendingLot.lotId, pendingLot.datePending);
            }
        } else {
            // If no pending lot is found for the user, stop the timer and hide the toast
            if (pendingCountdownInterval) clearInterval(pendingCountdownInterval);
            pendingCountdownInterval = null;
            pendingLotId = null;
            lotToast?.hide();
        }
    }


    /* ---------------------- MAP HELPERS ---------------------- */
    function findVectorSource() {
        if (cachedVectorSource) return cachedVectorSource;
        if (typeof jsonSource_geo_2 !== "undefined") {
            return (cachedVectorSource = jsonSource_geo_2);
        }
        if (window.map?.getLayers) {
            for (const layer of window.map.getLayers().getArray()) {
                if (layer instanceof ol.layer.Vector) {
                    const src = layer.getSource();
                    if (src) return (cachedVectorSource = src);
                }
            }
        }
        return null;
    }

    async function ensureVectorSource(maxRetries = 20, interval = 300) {
        return new Promise((resolve) => {
            let tries = 0;
            const check = () => {
                const src = findVectorSource();
                if (src) return resolve(src);
                if (++tries >= maxRetries) return resolve(null);
                setTimeout(check, interval);
            };
            check();
        });
    }

    const normalizeStatus = (status) => {
        const s = String(status || "").trim().toLowerCase();
        if (s.includes("pending")) return "Pending";
        if (s.includes("reserved")) return "Reserved";
        if (s.includes("occupied")) return "Occupied";
        return "Available";
    };

    /**
     * Fetches user info, sets the display name, and sets the critical currentUserId state.
     */
    async function loadUserName() {
        try {
            // NOTE: Using 'include' credentials to send session cookies
            const res = await fetch(`/stJohnCmsApp/cms.api/displayname.php`, {
                method: "GET", 
                credentials: "include"
            });

            const data = await res.json();
            // Handle display name for 'Guest' if not logged in
            const displayName = (data.status === "success" && data.fullName) ? data.fullName : "Guest";
            
            if (elements.userNameDisplayDesktop) elements.userNameDisplayDesktop.textContent = displayName;
            if (elements.userNameDisplayMobile) elements.userNameDisplayMobile.textContent = displayName;

            // Set the global state for the current user ID
            if (data.status === "success" && data.userId) {
                currentUserId = data.userId;
            } else {
                currentUserId = null; // Important: Clear ID if not logged in
            }

           } catch (err) {
            console.error("Error fetching user:", err);
            if (elements.userNameDisplayDesktop) elements.userNameDisplayDesktop.textContent = "Error";
            if (elements.userNameDisplayMobile) elements.userNameDisplayMobile.textContent = "Error";
            currentUserId = null;
        }
    }

    /* ---------------------- MAP UPDATER ---------------------- */
    async function updateMap(lotData) {
        lots = lotData;
        filteredLots = [...lots];
        
        // This runs *after* lots are loaded and *after* currentUserId is set.
        checkForPendingLot(lots); 
        
        const src = await ensureVectorSource();
        if (!src) return console.warn("No vector source found.");

        const colors = {
            Available: "rgba(0,200,0,0.6)",
            Pending: "rgba(0,47,255,0.6)",
            Reserved: "rgba(255,165,0,0.6)",
            Occupied: "rgba(200,0,0,0.6)",
        };

        src.getFeatures().forEach((f) => {
            const id = f.get("lotId") || f.get("id") || f.get("LotID") || f.get("LOT_ID");
            const lot = lots.find((l) => String(l.lotId) === String(id));

            if (!lot) return;

            const status = normalizeStatus(lot.status);
            
            f.setProperties({ 
                lotId: lot.lotId, 
                status,
                userId: lot.userId || "" 
            });

            // Re-apply style to update color based on status
            f.setStyle(
                new ol.style.Style({
                    stroke: new ol.style.Stroke({ color: "#333", width: 1 }),
                    fill: new ol.style.Fill({ color: colors[status] || "rgba(180,180,180,0.6)" }),
                    text: new ol.style.Text({
                        text: lot.lotNumber ? `Lot ${lot.lotNumber}` : "",
                        font: "12px Calibri,sans-serif",
                        fill: new ol.style.Fill({ color: "#000" }),
                        stroke: new ol.style.Stroke({ color: "#fff", width: 2 }),
                    }),
                })
            );
        });

        // Force OpenLayers to re-render the layer styles
        if (typeof lyr_geo_2 !== "undefined") {
            lyr_geo_2.changed();
        }

        refreshLotList();
    }

    /* ---------------------- LOT DATA HANDLING ---------------------- */
    async function loadLots() {
        try {
            const res = await fetch(endpoints.get);
            const data = await res.json();
            if (!data.success) throw new Error(data.message);
            // This calls updateMap, which calls checkForPendingLot
            await updateMap(data.data); 
        } catch (err) {
            console.error("Load error:", err);
            console.error("Failed to load lot data."); 
        }
    }

    /* ---------------------- LOT RESERVATION ACTION (FIXED) ---------------------- */

    // Form submit for "Reserve Now"
    elements.editForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const currentStatus = document.getElementById("editStatus").value;
        if (normalizeStatus(currentStatus) !== 'Available') {
            console.error(`Lot is ${currentStatus}. Cannot reserve.`);
            return;
        }
        
        // --- FIX: REMOVED LOGIN CHECK ---
        // We no longer check for currentUserId here. The client can select an available lot.
        // The reservation form page (lotReservation.php) will handle the login/registration prompt 
        // before the final reservation submission is sent to the backend.

        // Gather all necessary lot data for transfer
        const lotDataToTransfer = {
            lotId: document.getElementById("editLotId").value,
            block: document.getElementById("editBlock").value,
            area: document.getElementById("editArea").value,
            rowNumber: document.getElementById("editRowNumber").value,
            lotNumber: document.getElementById("editLotNumber").value,
            lotTypeId: document.getElementById("editLotType").value, 
            buryDepth: document.getElementById("editDepth").value,
            status: document.getElementById("editStatus")?.value || "Available"
        };
        
        // 1. Store the data in localStorage for lotReservation.js to pick up
        localStorage.setItem('selectedLotData', JSON.stringify(lotDataToTransfer));
        
        // 2. Hide the modal
        elements.editModal.style.display = "none";

        // 3. Redirect to the reservation page
        console.log(`Lot ${lotDataToTransfer.lotNumber} selected. Redirecting to the Reservation Form...`);
        window.location.href = "../lotReservation/lotReservation.php";
    });

    /* ---------------------- PAGINATION ---------------------- */
    function updatePagination() {
        const total = Math.ceil(filteredLots.length / lotsPerPage);
        elements.pageInfo.textContent = `Page ${currentPage} of ${total || 1}`;
        elements.prevPage.disabled = currentPage === 1;
        elements.nextPage.disabled = currentPage >= total;
    }

    function renderLotList() {
        const start = (currentPage - 1) * lotsPerPage;
        const pageLots = filteredLots.slice(start, start + lotsPerPage);
        elements.lotList.innerHTML = "";

        pageLots.forEach((lot) => {
            const status = normalizeStatus(lot.status);
            const item = document.createElement("div");
            item.className = "lot-item";
            // Map the data attributes for later use in the modal
            Object.entries(lot).forEach(([k, v]) => (item.dataset[k] = v || ""));
            item.innerHTML = `
                <span>${lot.lotId} | Block ${lot.block}, Area ${lot.area}, Row ${lot.rowNumber}, Lot ${lot.lotNumber} (${status})</span>
                <div class="lot-item-actions">
                    ${status === 'Available' ? '<button class="btn-icon btn-reserve"><i class="fas fa-hand-pointer"></i> Reserve</button>' : ''}
                </div>`;
            elements.lotList.appendChild(item);
        });

        attachLotActions();
        updatePagination();
    }

    function refreshLotList() {
        const term = elements.lotSearch?.value.toLowerCase() || "";
        filteredLots = lots.filter((l) =>
            `${l.lotId} ${l.userId} ${l.block} ${l.area} ${l.rowNumber} ${l.lotNumber} ${l.status}`.toLowerCase().includes(term)
        );
        currentPage = 1;
        renderLotList();
    }

    /* ---------------------- LOT ACTIONS ---------------------- */
    function attachLotActions() {
        elements.lotList.querySelectorAll(".btn-reserve").forEach((b) =>
            b.addEventListener("click", (e) => openEditModal(e.target.closest(".lot-item")))
        );
    }

    function openEditModal(item) {
        const form = elements.editForm;
        const lotStatus = normalizeStatus(item.dataset.status);

        Object.entries(item.dataset).forEach(([k, v]) => {
            // k is a camelCase data attribute (e.g., lotId, rowNumber)
            // We convert it to match the input ID (e.g., editLotId, editRowNumber)
            const inputId = `edit${k.charAt(0).toUpperCase() + k.slice(1)}`;
            const input = form.querySelector(`#${inputId}`);
            if (input) input.value = v;
        });

        const submitBtn = form.querySelector('button[type="submit"]');
        if (lotStatus === "Available") {
            submitBtn.textContent = "Reserve Now";
            submitBtn.disabled = false;
        } else {
            submitBtn.textContent = `Lot is ${lotStatus}`;
            submitBtn.disabled = true;
        }
        
        elements.editModal.style.display = "flex";
    }

    /* ---------------------- UI EVENTS ---------------------- */
    elements.lotSearch?.addEventListener("input", refreshLotList);
    elements.prevPage?.addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            renderLotList();
        }
    });
    elements.nextPage?.addEventListener("click", () => {
        if (currentPage < Math.ceil(filteredLots.length / lotsPerPage)) {
            currentPage++;
            renderLotList();
        }
    });

    elements.expandMap?.addEventListener("click", () => {
        elements.cemeteryMap.classList.toggle("expanded");
        elements.expandMap.innerHTML = elements.cemeteryMap.classList.contains("expanded")
            ? '<i class="fas fa-compress"></i> Collapse Map'
            : '<i class="fas fa-expand"></i> Expand Map';
        setTimeout(() => window.map?.updateSize?.(), 300);
    });

    // --- FIX: IMPROVED LOGOUT LOGIC ---
    elements.logout?.addEventListener("click", (e) => {
        e.preventDefault();
        // Redirect to a dedicated logout endpoint to destroy the session server-side
        console.log("Log out requested. Redirecting to logout.");
        window.location.href = "../../auth/login/login.php"; 
    });

    /* ---------------------- INIT ---------------------- */
    // Wait for username (and ID) to load before loading lots and checking for timers
    loadUserName().then(() => {
        loadLots();
    });
});