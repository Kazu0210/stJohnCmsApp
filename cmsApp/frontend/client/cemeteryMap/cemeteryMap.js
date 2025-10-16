// ====== CLIENT CEMETERY MAP SCRIPT ======

document.addEventListener("DOMContentLoaded", () => {

    // ====== CONFIG & ENDPOINTS ======
    const API_BASE = '/stJohnCmsApp/cms.api/';
    const endpoints = {
        get: `${API_BASE}/get_lots.php`
    };

    // ====== DOM ELEMENTS ======
    const elements = {
        logoutDesktop: document.getElementById("logoutLinkDesktop"),
        logoutMobile: document.getElementById("logoutLinkMobile"),
        expandMap: document.getElementById("expandMapBtn"),
        cemeteryMap: document.getElementById("cemeteryMap"),
        lotList: document.querySelector(".lot-list"),
        lotSearch: document.getElementById("lotSearch"),
        prevPage: document.getElementById("prevPageBtn"),
        nextPage: document.getElementById("nextPageBtn"),
        pageInfo: document.getElementById("pageInfo"),
        editModal: document.getElementById("editLotModal"),
        editForm: document.getElementById("editLotForm"),
    };

    // ====== STATE ======
    let lots = [];
    let filteredLots = [];
    let currentPage = 1;
    const lotsPerPage = 5;
    let cachedVectorSource = null;

    // ====== UTILITY FUNCTIONS ======
    const normalizeStatus = (status) => {
        const s = String(status || "").trim().toLowerCase();
        if (s.includes("pending")) return "Pending";
        if (s.includes("reserved")) return "Reserved";
        if (s.includes("occupied")) return "Occupied";
        return "Available";
    };

    // ====== MODAL UTILITIES ======
    function setupModal(modal) {
        if (!modal) return;
        // Close on close button
        modal.querySelector(".close-button")?.addEventListener("click", () => (modal.style.display = "none"));
        // Close on click outside
        window.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });
        // Close on Esc key
        window.addEventListener("keydown", (e) => {
            if (modal.style.display !== "none" && (e.key === "Escape" || e.key === "Esc")) {
                modal.style.display = "none";
            }
        });
        // Focus trap for accessibility
        modal.addEventListener("keydown", (e) => {
            if (e.key !== "Tab") return;
            const focusable = modal.querySelectorAll('input, select, button, [tabindex]:not([tabindex="-1"])');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
    }
    setupModal(elements.editModal);

    // ====== VECTOR SOURCE HELPERS ======
    function findVectorSource() {
        if (cachedVectorSource) return cachedVectorSource;
        if (typeof jsonSource_geo_2 !== "undefined") return (cachedVectorSource = jsonSource_geo_2);
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

    // ====== API FUNCTIONS ======
    async function loadUserName() {
        try {
            const res = await fetch(`${API_BASE}displayname.php`, { method: "GET", credentials: "include" });
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

    // ====== MAP UPDATE (READ-ONLY) ======
    async function updateMap(lotData) {
        lots = lotData;
        filteredLots = [...lots];
        const src = await ensureVectorSource();
        if (!src) return console.warn("No vector source found.");
        const colors = { Available:"rgba(0,200,0,0.6)", Pending:"rgba(0,47,255,0.6)", Reserved:"rgba(255,165,0,0.6)", Occupied:"rgba(200,0,0,0.6)" };
        src.getFeatures().forEach((f) => {
            const id = f.get("lotId") || f.get("id") || f.get("LotID") || f.get("LOT_ID");
            const lot = lots.find((l) => String(l.lotId) === String(id));
            if (!lot) return;
            const status = normalizeStatus(lot.status);
            
            // =========================================================
            //  ADDITION: Set userId property on the OpenLayers feature.
            //  This makes the data available for the pop-up (based on 
            //  your separate map config file's 'fieldLabels' setting).
            // =========================================================
            f.setProperties({ 
                lotId: lot.lotId, 
                status,
                // The new property to ensure data is available for the pop-up
                // which is already configured to show label 'visible with data'.
                userId: lot.userId || ""
            });
            // =========================================================

            f.setStyle(new ol.style.Style({
                stroke: new ol.style.Stroke({ color:"#333", width:1 }),
                fill: new ol.style.Fill({ color: colors[status] || "rgba(180,180,180,0.6)" }),
                text: new ol.style.Text({
                    text: lot.lotNumber ? `Lot ${lot.lotNumber}` : "",
                    font: "12px Calibri,sans-serif",
                    fill: new ol.style.Fill({ color:"#000" }),
                    stroke: new ol.style.Stroke({ color:"#fff", width:2 }),
                }),
            }));
        });
        if (typeof lyr_geo_2 !== "undefined") lyr_geo_2.changed();
        refreshLotList();
    }

    // ====== LOT LIST & PAGINATION ======
    function renderLotList() {
        const start = (currentPage-1)*lotsPerPage;
        const pageLots = filteredLots.slice(start,start+lotsPerPage);
        elements.lotList.innerHTML="";
        pageLots.forEach((lot)=>{
            const item=document.createElement("div");
            item.className="lot-item";
            Object.entries(lot).forEach(([k,v])=>item.dataset[k]=v||"");
            item.innerHTML=`<span>${lot.lotId} | Block ${lot.block}, Area ${lot.area}, Row ${lot.rowNumber}, Lot ${lot.lotNumber} (${normalizeStatus(lot.status)})</span>
            <div class="lot-item-actions">
            <button class="btn-icon btn-edit"><i class="fas fa-edit"></i></button>
            </div>`;
            elements.lotList.appendChild(item);
        });
        attachLotActions();
        updatePagination();
    }

    function refreshLotList() {
        const term = elements.lotSearch?.value.toLowerCase()||"";
        filteredLots = lots.filter(l=>`${l.lotId} ${l.block} ${l.area} ${l.rowNumber} ${l.lotNumber} ${l.status}`.toLowerCase().includes(term));
        currentPage=1;
        renderLotList();
    }

    function updatePagination() {
        const total = Math.ceil(filteredLots.length / lotsPerPage);
        elements.pageInfo.textContent = `Page ${currentPage} of ${total||1}`;
        elements.prevPage.disabled = currentPage===1;
        elements.nextPage.disabled = currentPage>=total;
    }

    // ====== LOT ACTIONS ======
    function attachLotActions() {
        elements.lotList.querySelectorAll(".btn-edit")
        .forEach(b=>b.addEventListener("click", e=>openEditModal(e.target.closest(".lot-item"))));
    }

    function openEditModal(item){
        const form=elements.editForm;
        Object.entries(item.dataset).forEach(([k,v])=>{
            const input=form.querySelector(`#edit${k.charAt(0).toUpperCase()+k.slice(1)}`);
            if(input) {
                input.value = v;
                // Disable non-editable fields for accessibility
                if (["editLotId","editBlock","editArea","editRowNumber","editLotNumber","editStatus"].includes(input.id)) {
                    input.setAttribute("readonly","readonly");
                    input.setAttribute("tabindex","0");
                }
            }
        });
        elements.editModal.style.display="flex";
        // Focus first input for accessibility
        setTimeout(()=>{
            const firstInput = form.querySelector('input, select, button, [tabindex]:not([tabindex="-1"])');
            firstInput?.focus();
        }, 100);
        toggleDepthOption();
    }

    // ====== EDIT FORM SUBMIT (CARRY TO RESERVATION PAGE) ======
    elements.editForm?.addEventListener("submit", (e) => {
        e.preventDefault();
        const form = e.target;
        const lotData = {
            lotId: document.getElementById("editLotId").value,
            block: document.getElementById("editBlock").value,
            area: document.getElementById("editArea").value,
            rowNumber: document.getElementById("editRowNumber").value,
            lotNumber: document.getElementById("editLotNumber").value,
            lotTypeId: document.getElementById("editLotType").value,
            buryDepth: document.getElementById("editDepth").value,
            status: document.getElementById("editStatus")?.value || "Available"
        };

        if (!confirm("Proceed to Reservation?")) return;

        // ====== Save to localStorage for lotReservation page auto-populate ======
        localStorage.setItem("selectedLotData", JSON.stringify(lotData));

    // Redirect to Reservation Form page
    window.location.href = "../lotReservation/lotReservationForm.php";
    });

    // ====== TOGGLE BURIAL DEPTH ======
    const preferredLot = document.getElementById("editLotType");
    const burialDepthSelect = document.getElementById("editDepth");
    const depthLabel = document.querySelector('label[for="editDepth"]');

    function toggleDepthOption() {
        if (!preferredLot || !burialDepthSelect) return;
        const selected = preferredLot.value;
        const isMausoleum = ["4","5"].includes(selected);
        if (isMausoleum) {
            burialDepthSelect.style.display="none";
            if(depthLabel) depthLabel.style.display="none";
            burialDepthSelect.removeAttribute("required");
        } else {
            burialDepthSelect.style.display="block";
            if(depthLabel) depthLabel.style.display="block";
            burialDepthSelect.setAttribute("required","required");
        }
    }

    preferredLot?.addEventListener("change", toggleDepthOption);

    // ====== EVENT BINDINGS ======
    elements.lotSearch?.addEventListener("input", refreshLotList);
    elements.prevPage.addEventListener("click", ()=>{ if(currentPage>1){ currentPage--; renderLotList(); } });
    elements.nextPage.addEventListener("click", ()=>{ if(currentPage<Math.ceil(filteredLots.length/lotsPerPage)){ currentPage++; renderLotList(); } });
    elements.expandMap?.addEventListener("click", ()=> {
        elements.cemeteryMap.classList.toggle("expanded");
        elements.expandMap.innerHTML = elements.cemeteryMap.classList.contains("expanded")
            ? '<i class="fas fa-compress"></i> Collapse Map'
            : '<i class="fas fa-expand"></i> Expand Map';
        setTimeout(()=>window.map?.updateSize?.(),300);
    });
    function handleLogoutRedirect(link) {
        // Clear client-side data
        localStorage.clear();
        sessionStorage.clear();
        const targetUrl = link.getAttribute("href") && link.getAttribute("href") !== "javascript:void(0);"
            ? link.getAttribute("href")
            : "../../auth/login/login.php";
        window.location.href = targetUrl;
    }

    [elements.logoutDesktop, elements.logoutMobile].forEach(link => {
        if (link) {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                handleLogoutRedirect(link);
            });
        }
    });

    // ====== INIT ======
    loadUserName();
    loadLots();
});