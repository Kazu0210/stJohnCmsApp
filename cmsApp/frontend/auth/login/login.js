document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();

    // Clear old error messages
    document.getElementById("emailError").textContent = "";
    document.getElementById("passwordError").textContent = "";
    document.getElementById("serverMessage").textContent = "";

    // Get form values
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();
    let hasError = false;

    // Email validation
    if (email === "") {
        document.getElementById("emailError").textContent = "Email is required";
        hasError = true;
    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
        document.getElementById("emailError").textContent = "Invalid email format";
        hasError = true;
    }

    // Password validation
    if (password === "") {
        document.getElementById("passwordError").textContent = "Password is required";
        hasError = true;
    } else if (password.length < 6) {
        document.getElementById("passwordError").textContent = "Password must be at least 6 characters";
        hasError = true;
    }

    if (hasError) return;

    // Send request to PHP
    const formData = new FormData();
    formData.append("email", email);
    formData.append("password", password);

    // This path remains absolute to your web server root
    fetch("http://localhost/stJohnCmsApp/cms.api/login.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            document.getElementById("serverMessage").style.color = "green";
            document.getElementById("serverMessage").textContent = data.message;

      setTimeout(() => {
        // If server provided an absolute redirect URL, use it (preferred)
        if (data.redirect) {
          window.location.href = data.redirect;
          return;
        }

        // Otherwise fall back to role-based redirects (relative paths)
        const userRole = data.role ? data.role.toLowerCase() : '';

        if (userRole === "admin") {
          window.location.href = "../../admin/adminDashboard/adminDashboard.php";
        } else if (userRole === "secretary") {
          window.location.href = "../../secretary/secretary.php";
        } else if (userRole === "client") {
          window.location.href = "../../client/clientDashboard/clientDashboard.php";
        } else {
          console.error("Unknown user role:", data.role);
          alert("Unknown user role. Cannot redirect.");
        }
      }, 1000);
        } else {
            document.getElementById("serverMessage").style.color = "red";
            document.getElementById("serverMessage").textContent = data.message;
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        document.getElementById("serverMessage").style.color = "red";
        document.getElementById("serverMessage").textContent = "An error occurred. Please try again.";
    });
});

// Function to toggle password visibility
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

// Image Modal functionality (assuming images are still relative to stJohnCmsApp root)
function openModal(element) {
    document.getElementById("modal").style.display = "block";
    document.getElementById("modal-img").src = element.src;
    document.getElementById("caption").textContent = element.alt;
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
}

// New Appointment Form Submission (client-side validation for now)
document.getElementById("appointmentForm").addEventListener("submit", function(e) {
    e.preventDefault();

    // Clear previous messages
    document.getElementById("appointmentMessage").textContent = "";

    const userName = document.getElementById("user_name").value.trim();
    const userEmail = document.getElementById("user_email").value.trim();
    const userPhone = document.getElementById("user_phone").value.trim();
    const appointmentDate = document.getElementById("appointment_date").value.trim();
    const appointmentTime = document.getElementById("appointment_time").value.trim();
    const appointmentPurpose = document.getElementById("appointment_purpose").value.trim();

    let hasError = false;

    // Validate all fields are filled
    if (!userName || !userEmail || !userPhone || !appointmentDate || !appointmentTime || !appointmentPurpose) {
        document.getElementById("appointmentMessage").style.color = "red";
        document.getElementById("appointmentMessage").textContent = "Please fill in all required fields.";
        hasError = true;
    }
    // Validate email format
    else if (!/^\S+@\S+\.\S+$/.test(userEmail)) {
        document.getElementById("appointmentMessage").style.color = "red";
        document.getElementById("appointmentMessage").textContent = "Please enter a valid email address.";
        hasError = true;
    }
    // Basic phone number validation (can be more robust if needed)
    else if (!/^\d{10,15}$/.test(userPhone)) {
        document.getElementById("appointmentMessage").style.color = "red";
        document.getElementById("appointmentMessage").textContent = "Please enter a valid phone number (10-15 digits).";
        hasError = true;
    }
    // Validate appointment time range (7 AM to 4 PM)
    else {
        const selectedTime = appointmentTime; // e.g., "07:00", "16:00"
        const minTime = "07:00";
        const maxTime = "16:00";

        if (selectedTime < minTime || selectedTime > maxTime) {
            document.getElementById("appointmentMessage").style.color = "red";
            document.getElementById("appointmentMessage").textContent = "Appointment time must be between 7 AM and 4 PM.";
            hasError = true;
        }
    }


    if (hasError) return;

    // For demonstration, we'll just show a success message.
    // In a real application, you would send this data to a server (e.g., via Fetch API).
    document.getElementById("appointmentMessage").style.color = "green";
    document.getElementById("appointmentMessage").textContent = "Appointment request submitted successfully! We will contact you soon.";

    // Optionally clear the form after successful submission
    document.getElementById("appointmentForm").reset();

    console.log({
        userName,
        userEmail,
        userPhone,
        appointmentDate,
        appointmentTime,
        appointmentPurpose
    });
});

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

// DOM refs
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
let selectedDate = null; // yyyy-mm-dd

// Render calendar for month/year
function renderCalendar(month, year) {
  Array.from(calendarGrid.querySelectorAll('.day')).forEach(n => n.remove());

  const firstDayIndex = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  const monthNames = [
    "January","February","March","April","May","June","July",
    "August","September","October","November","December"
  ];
  monthLabel.textContent = `${monthNames[month]} ${year}`;

  // blank placeholders
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

    // 🔦 Check appointments for this date
    const dayAppts = appointments.filter(a => a.date === dateStr);
    if (dayAppts.length > 0) {
      const indicator = document.createElement('div');
      indicator.className = 'light-indicator';

      // Determine color based on status or proximity
      const now = new Date();
      const dayDate = new Date(dateStr);
      const diffDays = Math.ceil((dayDate - now) / (1000 * 60 * 60 * 24));

      let color = '';
      if (dayAppts.some(a => !a.time)) {
        color = 'red'; // 🔴 missing time (uncertain)
      } else if (diffDays === 0) {
        color = 'green'; // 🟢 ongoing (today)
      } else if (diffDays <= 2 && diffDays > 0) {
        color = 'blue'; // 🔵 almost upcoming (within 2 days)
      } else if (diffDays < 0) {
        color = 'gray'; // past appointments
      } else {
        color = 'lightblue'; // far future
      }

      indicator.style.backgroundColor = color;
      dayEl.appendChild(indicator);

      // subtle background glow for active days
      dayEl.style.boxShadow = `0 0 6px ${color}80`;
    }

    // Highlight today’s box border
    if (dateStr === todayIso) {
      dayEl.style.border = '2px solid #4caf50';
    }

    dayEl.addEventListener('click', () => openDay(dateStr));
    calendarGrid.appendChild(dayEl);
  }

  if (selectedDate) {
    updateSidebar(selectedDate);
  } else {
    apptCountEl.textContent = appointments.length;
  }
}

// open day: either show list in sidebar and open modal new if desired
function openDay(dateStr){
  selectedDate = dateStr;
  // highlight selection visually
  Array.from(calendarGrid.querySelectorAll('.day'))
    .forEach(d => d.classList.toggle('selected', d.dataset.date === dateStr));

  selectedDayHeading.textContent = new Date(dateStr).toLocaleDateString();
  updateSidebar(dateStr);
}

// update sidebar with appointments for date
function updateSidebar(dateStr){
  const appts = appointments.filter(a => a.date === dateStr);
  dayApptsEl.innerHTML = '';
  if(appts.length === 0){
    dayApptsEl.innerHTML = `<div class="empty">No appointments for this day. Click "+ New" to add one, or click a day to add.</div>`;
  } else {
    appts.forEach(a => {
      const card = document.createElement('div');
      card.className = 'card mb-2';
      card.innerHTML = `<div class="card-body p-2">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:700">${a.client || '—'}</div>
            <div class="muted small">${a.time ? a.time + ' • ' : ''}${a.notes ? a.notes : ''}</div>
          </div>
          <div style="display:flex;flex-direction:column;gap:6px">
            <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${a.id}">Edit</button>
            <button class="btn btn-sm btn-outline-danger btn-del" data-id="${a.id}">Delete</button>
          </div>
        </div>
      </div>`;
      dayApptsEl.appendChild(card);
    });

    // wire edit/delete buttons
    dayApptsEl.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', (e)=>{
        const id = btn.dataset.id;
        openEditById(id);
      });
    });
    dayApptsEl.querySelectorAll('.btn-del').forEach(btn => {
      btn.addEventListener('click', (e)=>{
        const id = btn.dataset.id;
        if(confirm('Delete this appointment?')){
          appointments = appointments.filter(a => a.id !== id);
          saveAppointments(appointments);
          renderCalendar(currentMonth, currentYear);
          updateSidebar(selectedDate);
        }
      });
    });
  }
  apptCountEl.textContent = appointments.length;
}

// show modal
function showModal(){
  modalBackdrop.style.display = 'flex';
  modalBackdrop.setAttribute('aria-hidden', 'false');
  deleteBtn.style.display = 'none';
  editingId.value = '';
  // focus first input
  setTimeout(()=>apptClient.focus(), 200);
}

// hide modal
function hideModal(){
  modalBackdrop.style.display = 'none';
  modalBackdrop.setAttribute('aria-hidden', 'true');
  apptFormReset();
}

// reset modal inputs
function apptFormReset(){
  apptClient.value = '';
  apptDate.value = '';
  apptTime.value = '';
  apptNotes.value = '';
  editingId.value = '';
  deleteBtn.style.display = 'none';
}

// open edit modal for appointment id
function openEditById(id){
  const a = appointments.find(x => x.id === id);
  if(!a) return;
  editingId.value = a.id;
  apptClient.value = a.client || '';
  apptDate.value = a.date || '';
  apptTime.value = a.time || '';
  apptNotes.value = a.notes || '';
  deleteBtn.style.display = 'inline-block';
  showModal();
}

// save appointment (from modal)
function saveAppointmentFromModal(){
  const id = editingId.value || uid();
  const obj = {
    id,
    client: apptClient.value.trim(),
    date: apptDate.value,
    time: apptTime.value,
    notes: apptNotes.value.trim()
  };
  // simple validation
  if(!obj.client || !obj.date){
    alert('Please add client name and date.');
    return false;
  }

  // if editing replace, else push
  const idx = appointments.findIndex(a => a.id === id);
  if(idx > -1){
    appointments[idx] = obj;
  } else {
    appointments.push(obj);
  }
  saveAppointments(appointments);
  renderCalendar(currentMonth, currentYear);
  updateSidebar(obj.date);
  hideModal();
  return true;
}

// delete appointment from modal
function deleteAppointmentFromModal(){
  const id = editingId.value;
  if(!id) return;
  if(confirm('Delete this appointment?')){
    appointments = appointments.filter(a => a.id !== id);
    saveAppointments(appointments);
    renderCalendar(currentMonth, currentYear);
    hideModal();
  }
}

// modal cancel / delete / save wiring
cancelBtn.addEventListener('click', hideModal);
deleteBtn.addEventListener('click', deleteAppointmentFromModal);
apptForm.addEventListener('submit', (e)=>{
  e.preventDefault();
  saveAppointmentFromModal();
});

// calendar nav
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

// clicking outside modal to close (backdrop)
modalBackdrop.addEventListener('click', (e)=>{
  if(e.target === modalBackdrop){
    hideModal();
  }
});

// integrate quick-click on calendar days to populate main appointment form date
calendarGrid.addEventListener('click', (e)=>{
  const dayEl = e.target.closest('.day');
  if(!dayEl || !dayEl.dataset.date) return;
  const date = dayEl.dataset.date;
  // set main appointment form date input
  const mainDateInput = document.getElementById('appointment_date');
  if(mainDateInput) mainDateInput.value = date;

  // Also open modal for adding appointment (optional) — keep conservative: only show modal on double-click
});

// allow double-click to open modal for that date
calendarGrid.addEventListener('dblclick', (e)=>{
  const dayEl = e.target.closest('.day');
  if(!dayEl || !dayEl.dataset.date) return;
  apptFormReset();
  apptDate.value = dayEl.dataset.date;
  showModal();
});

// initial render
renderCalendar(currentMonth, currentYear);

// Local submit handler for main appointment form - this saves a quick appointment to local storage
function handleLocalAppointmentSubmit(e){
  e.preventDefault();
  // create a simple appointment from main form (this won't interfere with other process)
  const client = document.getElementById('user_name').value.trim();
  const date = document.getElementById('appointment_date').value;
  const time = document.getElementById('appointment_time').value;
  const notes = document.getElementById('appointment_purpose').value.trim();
  if(!client || !date){
    document.getElementById('appointmentMessage').textContent = 'Please fill name and date.';
    return;
  }
  const newAppt = { id: uid(), client, date, time, notes };
  appointments.push(newAppt);
  saveAppointments(appointments);
  renderCalendar(currentMonth, currentYear);
  updateSidebar(date);
  document.getElementById('appointmentMessage').textContent = 'Appointment saved locally.';
  // call original handleAppointmentSubmit if it exists (so existing processes run)
  if(typeof window.handleAppointmentSubmit === 'function'){
    try { window.handleAppointmentSubmit(e); } catch(err){ console.warn('existing handleAppointmentSubmit failed', err); }
  }
}

// Attach local handler but do not override existing global function
const mainForm = document.getElementById('appointmentForm');
if(mainForm){
  // If the form already has onsubmit inline, our extra listener will not cancel it.
  mainForm.addEventListener('submit', handleLocalAppointmentSubmit);
}

// Load appointments from MySQL (XAMPP)
async function loadAppointmentsFromDB() {
  try {
    const res = await fetch("http://localhost/stJohnCmsApp/cms.api/clientAppointment.php");
    if (!res.ok) throw new Error("Failed to connect to MySQL backend");
    const data = await res.json();
    // console.log("Fetched appointments from MySQL:", data);
    appointments = Array.isArray(data) ? data : [];
    renderCalendar(currentMonth, currentYear);
  } catch (err) {
    console.error("Error loading appointments from database:", err);
    renderCalendar(currentMonth, currentYear);
  }
}

// Refresh appointments right after page load
loadAppointmentsFromDB();

// After submitting an appointment, refresh calendar from DB
document.getElementById("appointmentForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const formData = {
    user_name: document.getElementById("user_name").value,
    user_email: document.getElementById("user_email").value, // ✅ corrected
    user_phone: document.getElementById("user_phone").value,
    appointment_date: document.getElementById("appointment_date").value,
    appointment_time: document.getElementById("appointment_time").value,
    appointment_purpose: document.getElementById("appointment_purpose").value,
  };

  try {
    const res = await fetch("http://localhost/stJohnCmsApp/cms.api/clientAppointment.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(formData),
    });

    const data = await res.json();
    document.getElementById("appointmentMessage").textContent = data.message;

    if (data.status === "success") {
      document.getElementById("appointmentForm").reset();
      await loadAppointmentsFromDB(); // 🔁 reload appointments from MySQL
    }
  } catch (err) {
    console.error("Database connection error:", err);
    document.getElementById("appointmentMessage").textContent = "Error connecting to database.";
  }
});
