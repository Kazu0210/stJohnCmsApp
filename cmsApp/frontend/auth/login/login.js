// login.js
const endpoints = {
    get: '/stJohnCmsApp/cms.api/get_lots.php', 
    update: '/stJohnCmsApp/cms.api/update_lot.php', 
};

let cachedVectorSource = null;

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

async function ensureVectorSource(maxRetries = 50, interval = 100) {
    return new Promise((resolve) => {
        let tries = 0;
        const check = () => {
            const src = findVectorSource();
            if (src && src.getFeatures().length > 0) return resolve(src); 
            
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

// Applies lot status colors, labels, and updates feature properties for the popup
async function updateMap(lotData) {
    const src = await ensureVectorSource();
    if (!src) {
        console.warn("Map vector source or features not found after waiting. Skipping update.");
        return;
    }

    const colors = {
        Available: "rgba(0,200,0,0.6)",
        Pending: "rgba(0,47,255,0.6)",
        Reserved: "rgba(255,165,0,0.6)",
        Occupied: "rgba(200,0,0,0.6)",
    };

    src.getFeatures().forEach((f) => {
        const id = f.get("lotId") || f.get("id") || f.get("LotID") || f.get("LOT_ID");
        const lot = lotData.find((l) => String(l.lotId) === String(id));

        if (!lot) return; 

        const status = normalizeStatus(lot.status);

        f.setProperties({
            lotId: lot.lotId,
            lotNumber: lot.lotNumber,
            status: status,
            userId: lot.userId || 'N/A',
            type: lot.type || f.get('type') || 'N/A', 
        }, true); 

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

    if (typeof lyr_geo_2 !== "undefined") {
        lyr_geo_2.changed();
    }
}

// LOT DATA HANDLING (Load and Update)
async function loadLots() {
    try {
        const res = await fetch(endpoints.get); 
        const data = await res.json();
        
        if (!data.success) throw new Error(data.message || "Server returned non-success status.");
        
        await updateMap(data.data);
        
        return data.data; 
    } catch (err) {
        console.error("Load Lots Error:", err);
        return [];
    }
}

async function updateLot(lotData) {
    try {
        const res = await fetch(endpoints.update, { 
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(lotData),
        });
        const result = await res.json();

        if (result.success) {
            console.log("Lot updated successfully:", lotData.lotId);
            await loadLots(); 
            return true;
        } else {
            console.error("Update failed:", result.message);
            return false;
        }
    } catch (err) {
        console.error("Error updating lot:", err);
        return false;
    }
}

document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();

    document.getElementById("emailError").textContent = "";
    document.getElementById("passwordError").textContent = "";
    document.getElementById("serverMessage").textContent = "";

    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();
    let hasError = false;

    if (email === "") {
        document.getElementById("emailError").textContent = "Email is required";
        hasError = true;
    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
        document.getElementById("emailError").textContent = "Invalid email format";
        hasError = true;
    }
    if (password === "") {
        document.getElementById("passwordError").textContent = "Password is required";
        hasError = true;
    } else if (password.length < 6) {
        document.getElementById("passwordError").textContent = "Password must be at least 6 characters";
        hasError = true;
    }

    if (hasError) return;

    const formData = new FormData();
    formData.append("email", email);
    formData.append("password", password);

    fetch("/stJohnCmsApp/cms.api/login.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const serverMessageEl = document.getElementById("serverMessage");
        if (data.status === "success") {
            serverMessageEl.style.color = "green";
            serverMessageEl.textContent = data.message;

            setTimeout(() => {
                const userRole = data.role ? data.role.toLowerCase() : '';
                if (userRole === "admin") {
                    window.location.href = "../../admin/adminDashboard/adminDashboard.php";
                } else if (userRole === "secretary") {
                    window.location.href = "../../secretary/secretaryDashboard.php";
                } else if (userRole === "client") {
                    window.location.href = "../../client/clientDashboard/clientDashboard.php";
                } else {
                    console.error("Unknown user role:", data.role);
                    alert("Unknown user role. Cannot redirect.");
                }
            }, 1000);
        } else {
            serverMessageEl.style.color = "red";
            serverMessageEl.textContent = data.message;
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        document.getElementById("serverMessage").style.color = "red";
        document.getElementById("serverMessage").textContent = "An error occurred. Please try again.";
    });
});

function togglePassword() {
    const passwordField = document.getElementById("password");
    const toggleIcon = document.querySelector(".password-toggle-icon");

    if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleIcon.classList.remove("bi-eye-slash");
        toggleIcon.classList.add("bi-eye");
    } else {
        passwordField.type = "password";
        toggleIcon.classList.remove("bi-eye");
        toggleIcon.classList.add("bi-eye-slash");
    }
}

function openModal(element) {
    document.getElementById("modal").style.display = "block";
    document.getElementById("modal-img").src = element.src;
    document.getElementById("caption").textContent = element.alt;
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
}

const storageKey = 'bsjAppointments';
function loadAppointments(){
    try {
        const raw = localStorage.getItem(storageKey);
        return raw ? JSON.parse(raw) : [];
    } catch(e){ return []; }
}
function saveAppointments(arr){
    localStorage.setItem(storageKey, JSON.stringify(arr));
}
function uid(){ return 'id_' + Math.random().toString(36).slice(2,9); }

const calendarGrid = document.getElementById('calendarGrid');
const monthLabel = document.getElementById('monthLabel');
const prevMonthBtn = document.getElementById('prevMonth');
const nextMonthBtn = document.getElementById('nextMonth');
const todayBtn = document.getElementById('todayBtn');

const modalBackdrop = document.getElementById('modalBackdrop');
const apptForm = document.getElementById('apptForm');
const apptClient = document.getElementById('apptClient');
const apptDate = document.getElementById('apptDate');
const apptTime = document.getElementById('apptTime');
const apptNotes = document.getElementById('apptNotes');
const editingId = document.getElementById('editingId');
const deleteBtn = document.getElementById('deleteBtn');
const cancelBtn = document.getElementById('cancelBtn');

const apptCountEl = document.getElementById('apptCount');
const dayApptsEl = document.getElementById('dayAppts');
const selectedDayHeading = document.getElementById('selectedDayHeading');

let current = new Date();
let currentMonth = current.getMonth();
let currentYear = current.getFullYear();
let appointments = loadAppointments(); 
let selectedDate = null; 

function renderCalendar(month, year) {
    Array.from(calendarGrid.querySelectorAll('.day')).forEach(n => n.remove());
    const firstDayIndex = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    monthLabel.textContent = `${monthNames[month]} ${year}`;

    for (let i = 0; i < firstDayIndex; i++) {
        const blank = document.createElement('div');
        blank.className = 'day other-month';
        calendarGrid.appendChild(blank);
    }
    const todayIso = new Date().toLocaleDateString('en-CA');

    for (let d = 1; d <= daysInMonth; d++) {
        const dateObj = new Date(year, month, d);
        const dateStr = dateObj.toLocaleDateString('en-CA');
        const dayEl = document.createElement('div');
        dayEl.className = 'day';
        dayEl.dataset.date = dateStr;

        const num = document.createElement('div');
        num.className = 'date-num';
        num.textContent = d;
        dayEl.appendChild(num);

        const dayAppts = appointments.filter(a => a.date === dateStr);
        if (dayAppts.length > 0) {
            const indicator = document.createElement('div');
            indicator.className = 'light-indicator';
            const now = new Date();
            const dayDate = new Date(dateStr);
            const diffDays = Math.ceil((dayDate - now) / (1000 * 60 * 60 * 24));
            
            let color = '';
            const hasConfirmed = dayAppts.some(a => a.status === 'confirmed'); 
            const hasCancelled = dayAppts.some(a => a.status === 'cancelled');

            if (hasCancelled) { color = 'gray'; } 
            else if (hasConfirmed) { color = 'green'; } 
            else if (dayAppts.some(a => !a.time)) { color = 'red'; } 
            else if (diffDays === 0) { color = 'green'; } 
            else if (diffDays <= 2 && diffDays > 0) { color = 'blue'; } 
            else if (diffDays < 0) { color = 'gray'; } 
            else { color = 'lightblue'; }

            indicator.style.backgroundColor = color;
            dayEl.appendChild(indicator);
            dayEl.style.boxShadow = `0 0 6px ${color}80`;
        }

        if (dateStr === todayIso) { dayEl.style.border = '2px solid #4caf50'; }

        dayEl.addEventListener('click', () => openDay(dateStr));
        calendarGrid.appendChild(dayEl);
    }

    if (selectedDate) {
        updateSidebar(selectedDate);
    } else {
        apptCountEl.textContent = appointments.length; 
    }
}

function openDay(dateStr){
    selectedDate = dateStr;
    Array.from(calendarGrid.querySelectorAll('.day'))
        .forEach(d => d.classList.toggle('selected', d.dataset.date === dateStr));

    selectedDayHeading.textContent = new Date(dateStr).toLocaleDateString();
    updateSidebar(dateStr);
}

function updateSidebar(dateStr){
    const appts = appointments.filter(a => a.date === dateStr);
    dayApptsEl.innerHTML = '';
    if(appts.length === 0){
        dayApptsEl.innerHTML = `<div class="empty">No appointments for this day. Click a day to see appointments.</div>`;
    } else {
        appts.forEach(a => {
            const card = document.createElement('div');
            card.className = 'card mb-2';
            const statusText = a.status ? `Status: <strong>${a.status}</strong>` : 'Status: Unknown (Local)';
            card.innerHTML = `<div class="card-body p-2">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <div style="font-weight:700">${a.client || '—'}</div>
                        <div class="muted small">${a.time ? a.time + ' • ' : ''}${a.notes ? a.notes : ''}</div>
                        <div class="muted small">${statusText}</div> 
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${a.id}">Edit</button>
                    </div>
                </div>
            </div>`;
            dayApptsEl.appendChild(card);
        });

        dayApptsEl.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e)=>{
                const id = btn.dataset.id;
                openEditById(id);
            });
        });
    }
    apptCountEl.textContent = appts.length; 
}

function showModal(){
    modalBackdrop.style.display = 'flex';
    modalBackdrop.setAttribute('aria-hidden', 'false');
    deleteBtn.style.display = 'none'; 
    editingId.value = '';
    setTimeout(()=>apptClient.focus(), 200);
}

function hideModal(){
    modalBackdrop.style.display = 'none';
    modalBackdrop.setAttribute('aria-hidden', 'true');
    apptFormReset();
}

function apptFormReset(){
    apptClient.value = '';
    apptDate.value = '';
    apptTime.value = '';
    apptNotes.value = '';
    editingId.value = '';
    deleteBtn.style.display = 'none';
    document.getElementById('apptFormTitle').textContent = 'Edit Local Appointment';
}

function openEditById(id){
    const a = appointments.find(x => x.id === id);
    if(!a) return;
    
    apptFormReset(); 

    editingId.value = a.id;
    apptClient.value = a.client || '';
    apptDate.value = a.date || '';
    apptTime.value = a.time || '';
    apptNotes.value = a.notes || '';
    
    deleteBtn.style.display = 'inline-block'; 
    
    showModal();
}

function saveAppointmentFromModal(){
    const id = editingId.value; 
    
    if(!id) {
        alert('Action blocked: New appointments must be submitted via the main form.');
        hideModal();
        return false;
    }

    const obj = {
        id,
        client: apptClient.value.trim(),
        date: apptDate.value,
        time: apptTime.value,
        notes: apptNotes.value.trim()
    };
    if(!obj.client || !obj.date){
        alert('Please add client name and date.');
        return false;
    }

    const idx = appointments.findIndex(a => a.id === id);
    if(idx > -1){
        appointments[idx] = obj;
    } 

    saveAppointments(appointments); 
    renderCalendar(currentMonth, currentYear);
    updateSidebar(obj.date);
    hideModal();
    return true;
}

function deleteAppointmentFromModal(){
    const id = editingId.value;
    if(!id) return;
    if(confirm('Delete this local appointment?')){
        appointments = appointments.filter(a => a.id !== id);
        saveAppointments(appointments); 
        renderCalendar(currentMonth, currentYear);
        updateSidebar(selectedDate);
        hideModal();
    }
}

cancelBtn.addEventListener('click', hideModal);
deleteBtn.addEventListener('click', deleteAppointmentFromModal);
apptForm.addEventListener('submit', (e)=>{
    e.preventDefault();
    saveAppointmentFromModal();
});

prevMonthBtn.addEventListener('click', ()=>{
    currentMonth--;
    if(currentMonth < 0){ currentMonth = 11; currentYear--; }
    renderCalendar(currentMonth, currentYear);
});
nextMonthBtn.addEventListener('click', ()=>{
    currentMonth++;
    if(currentMonth > 11){ currentMonth = 0; currentYear++; }
    renderCalendar(currentMonth, currentYear);
});
todayBtn.addEventListener('click', ()=>{
    const t = new Date();
    currentMonth = t.getMonth();
    currentYear = t.getFullYear();
    renderCalendar(currentMonth, currentYear);
});

modalBackdrop.addEventListener('click', (e)=>{
    if(e.target === modalBackdrop){
        hideModal();
    }
});

calendarGrid.addEventListener('click', (e)=>{
    const dayEl = e.target.closest('.day');
    if(!dayEl || !dayEl.dataset.date) return;
    const date = dayEl.dataset.date;
    const mainDateInput = document.getElementById('appointment_date');
    if(mainDateInput) mainDateInput.value = date;
});

renderCalendar(currentMonth, currentYear);

async function loadAppointmentsFromDB() {
    try {
        const res = await fetch("/stJohnCmsApp/cms.api/clientAppointment.php"); 
        
        if (!res.ok) {
            const errorText = await res.text();
            throw new Error(`Failed to load data. Server responded with status ${res.status}. Response: ${errorText.substring(0, 100)}...`);
        }

        const data = await res.json();
        appointments = Array.isArray(data) ? data : [];
        renderCalendar(currentMonth, currentYear);
    } catch (err) {
        console.error("Error loading appointments from database:", err);
        appointments = loadAppointments(); 
        renderCalendar(currentMonth, currentYear);
    }
}

loadAppointmentsFromDB();

loadLots();

document.getElementById("appointmentForm").addEventListener("submit", async function (e) {
    e.preventDefault(); 

    const appointmentMessage = document.getElementById("appointmentMessage");
    appointmentMessage.textContent = ""; 
    
    const userName = document.getElementById("user_name").value.trim();
    const userEmail = document.getElementById("user_email").value.trim();
    const userAddress = document.getElementById("user_address").value.trim();
    const userPhone = document.getElementById("user_phone").value.trim();
    const appointmentDate = document.getElementById("appointment_date").value.trim();
    const appointmentTime = document.getElementById("appointment_time").value.trim();
    const appointmentPurpose = document.getElementById("appointment_purpose").value.trim();

    if (!userName || !userEmail || !userAddress || !userPhone || !appointmentDate || !appointmentTime || !appointmentPurpose) {
        appointmentMessage.style.color = "red";
        appointmentMessage.textContent = "Please fill in all required fields.";
        return;
    }
    if (!/^\S+@\S+\.\S+$/.test(userEmail)) {
        appointmentMessage.style.color = "red";
        appointmentMessage.textContent = "Please enter a valid email address.";
        return;
    }
    const selectedTime = appointmentTime; 
    const minTime = "07:00";
    const maxTime = "16:00";
    if (selectedTime < minTime || selectedTime > maxTime) {
        appointmentMessage.style.color = "red";
        appointmentMessage.textContent = "Appointment time must be between 7 AM and 4 PM.";
        return;
    }
    
    const formData = {
        user_name: userName,
        user_email: userEmail,
        user_address: userAddress,
        user_phone: userPhone,
        appointment_date: appointmentDate,
        appointment_time: appointmentTime,
        appointment_purpose: appointmentPurpose,
    };

    try {
        const res = await fetch("/stJohnCmsApp/cms.api/clientAppointment.php", { 
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(formData),
        });

        if (!res.ok) {
            let errorData;
            try {
                errorData = await res.json();
                appointmentMessage.textContent = errorData.message || `Server Error: HTTP ${res.status}`;
            } catch (e) {
                const errorText = await res.text();
                appointmentMessage.textContent = `Server Execution Error (${res.status}). Check PHP file for syntax/connection errors.`;
                console.error("Raw Server Response (Likely PHP Fatal Error):", errorText.substring(0, 500));
            }
            appointmentMessage.style.color = "red";
            return;
        }

        const data = await res.json(); 
        appointmentMessage.textContent = data.message;
        
        if (data.status === "success") {
            appointmentMessage.style.color = "green";
            document.getElementById("appointmentForm").reset();
            await loadAppointmentsFromDB();
        } else {
            appointmentMessage.style.color = "red"; 
        }

    } catch (err) {
        console.error("Fetch/Parsing Error:", err);
        appointmentMessage.textContent = `Client-side error: ${err.message}. Check browser console for details.`;
        appointmentMessage.style.color = "red";
    }
});