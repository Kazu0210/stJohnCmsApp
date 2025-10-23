// adminCemeteryMap.js
document.addEventListener("DOMContentLoaded", () => {

    const endpoints = {
        get: `/stJohnCmsApp/cms.api/get_lots.php`,
        update: `/stJohnCmsApp/cms.api/update_lot.php`,
        delete: `/stJohnCmsApp/cms.api/delete_lot.php`,
    };

    /* ---------------------- DOM ELEMENTS ---------------------- */
    const elements = {
        logout: document.getElementById("logoutLink"),
        expandMap: document.getElementById("expandMapBtn"),
        cemeteryMap: document.getElementById("cemeteryMap"),
        lotList: document.querySelector(".lot-list"),
        lotSearch: document.getElementById("lotSearch"),
        prevPage: document.getElementById("prevPageBtn"),
        nextPage: document.getElementById("nextPageBtn"),
        pageInfo: document.getElementById("pageInfo"),
        editModal: document.getElementById("editLotModal"),
        deleteModal: document.getElementById("deleteLotModal"),
        confirmDelete: document.getElementById("confirmDeleteBtn"),
        cancelDelete: document.getElementById("cancelDeleteBtn"),
        editForm: document.getElementById("editLotForm"),
    };

    /* ---------------------- STATE ---------------------- */
    let lots = [];
    let filteredLots = [];
    let currentPage = 1;
    const lotsPerPage = 5;
    let lotToDelete = null;
    let cachedVectorSource = null;

    /* ---------------------- MODAL HANDLERS ---------------------- */
    function setupModal(modal) {
        if (!modal) return;
        modal.querySelector(".close-button")?.addEventListener("click", () => (modal.style.display = "none"));
        window.addEventListener("click", (e) => {
            if (e.target === modal) modal.style.display = "none";
        });
    }

    setupModal(elements.editModal);
    setupModal(elements.deleteModal);

    /* ---------------------- MAP HELPERS ---------------------- */
    function findVectorSource() {
        if (cachedVectorSource) return cachedVectorSource;

        // QGIS2Web variable name (confirmed from layers.js)
        if (typeof jsonSource_geo_2 !== "undefined") {
            return (cachedVectorSource = jsonSource_geo_2);
        }

        // Fallback: get from map layers
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

    /* ---------------------- MAP UPDATER ---------------------- */
    async function updateMap(lotData) {
        lots = lotData;
        filteredLots = [...lots];

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
            
            // =========================================================
            //  ADDITION: Set userId property on the OpenLayers feature.
            //  This ensures the data is available for pop-ups and styles.
            // =========================================================
            f.setProperties({ 
                lotId: lot.lotId, 
                status,
                // Add userId from lotData to the OpenLayers feature properties
                userId: lot.userId || "" 
            });
            // =========================================================

            // Dynamically recolor polygon
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

        // Force refresh if the map layer already exists
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
            await updateMap(data.data);
        } catch (err) {
            console.error("Load error:", err);
            alert("Failed to load lot data.");
        }
    }

    /* ---------------------- UPDATE LOT HANDLER ---------------------- */
    async function updateLot(lotData) {
        try {
            const res = await fetch(endpoints.update, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(lotData),
            });
            const result = await res.json();

            if (result.success) {
                alert("Lot updated successfully!");
                elements.editModal.style.display = "none";
                await loadLots(); // Reload list and recolor polygons
            } else {
                alert("Update failed: " + (result.message || "Unknown error"));
            }
        } catch (err) {
            console.error("Error updating lot:", err);
            alert("Failed to update lot.");
        }
    }

    // Form submit for "Save Changes"
    elements.editForm?.addEventListener("submit", async (e) => {
        e.preventDefault();

        const lotData = {
            lotId: document.getElementById("editLotId").value,
            userId: document.getElementById("editUserId").value || null,
            block: document.getElementById("editBlock").value,
            area: document.getElementById("editArea").value,
            rowNumber: document.getElementById("editRowNumber").value,
            lotNumber: document.getElementById("editLotNumber").value,
            lotTypeId: document.getElementById("editLotType").value,
            buryDepth: document.getElementById("editDepth").value,
            status: document.getElementById("editStatus").value,
        };

        await updateLot(lotData);
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
            const item = document.createElement("div");
            item.className = "lot-item";
            Object.entries(lot).forEach(([k, v]) => (item.dataset[k] = v || ""));
            item.innerHTML = `
                <span>${lot.lotId} | Block ${lot.block}, Area ${lot.area}, Row ${lot.rowNumber}, Lot ${lot.lotNumber} (${normalizeStatus(lot.status)})</span>
                <div class="lot-item-actions">
                    <button class="btn-icon btn-edit"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete"><i class="fas fa-trash-alt"></i></button>
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
        elements.lotList.querySelectorAll(".btn-edit").forEach((b) =>
            b.addEventListener("click", (e) => openEditModal(e.target.closest(".lot-item")))
        );
        elements.lotList.querySelectorAll(".btn-delete").forEach((b) =>
            b.addEventListener("click", (e) => openDeleteModal(e.target.closest(".lot-item")))
        );
    }

    function openEditModal(item) {
        const form = elements.editForm;
        Object.entries(item.dataset).forEach(([k, v]) => {
            const input = form.querySelector(`#edit${k.charAt(0).toUpperCase() + k.slice(1)}`);
            if (input) input.value = v;
        });
        elements.editModal.style.display = "flex";
    }

    function openDeleteModal(item) {
        lotToDelete = { id: item.dataset.lotid }; // Note: Using lotid from dataset
        elements.deleteModal.style.display = "flex";
    }

    elements.confirmDelete.addEventListener("click", async () => {
        if (!lotToDelete) return;
        try {
            const res = await fetch(endpoints.delete, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ lotId: lotToDelete.id }),
            });
            const data = await res.json();
            if (data.success) {
                elements.deleteModal.style.display = "none";
                await loadLots(); // Reload and update polygon colors
            } else alert(data.message);
        } catch (err) {
            console.error(err);
        }
    });

    elements.cancelDelete.addEventListener("click", () => {
        elements.deleteModal.style.display = "none";
        lotToDelete = null;
    });

    /* ---------------------- UI EVENTS ---------------------- */
    elements.lotSearch?.addEventListener("input", refreshLotList);
    elements.prevPage.addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            renderLotList();
        }
    });
    elements.nextPage.addEventListener("click", () => {
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

    elements.logout?.addEventListener("click", (e) => {
        e.preventDefault();
        if (confirm("Log out?")) window.location.href = "../../auth/login/login.php";
    });

    /* ---------------------- INIT ---------------------- */
    loadLots();
});