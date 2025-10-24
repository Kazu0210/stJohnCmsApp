// Add event listener for the submit appointment button

document.addEventListener('DOMContentLoaded', function() {
    // Use the button with class 'submit-appointment' inside the appointment form
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Collect form data by referencing input 'name' attributes
            const formData = new FormData(appointmentForm);
            const formEntries = {};
            for (const [key, value] of formData.entries()) {
                formEntries[key] = value;
            }
            // If formEntries is empty, likely missing 'name' attributes on inputs
            if (Object.keys(formEntries).length === 0) {
                // Try to collect values manually as fallback
                formEntries.user_name = document.getElementById('user_name')?.value || '';
                formEntries.user_email = document.getElementById('user_email')?.value || '';
                formEntries.user_address = document.getElementById('user_address')?.value || '';
                formEntries.user_phone = document.getElementById('user_phone')?.value || '';
                formEntries.appointment_date = document.getElementById('appointment_date')?.value || '';
                formEntries.appointment_start_time = document.getElementById('appointment_start_time')?.value || '';
                formEntries.appointment_end_time = document.getElementById('appointment_end_time')?.value || '';
                formEntries.appointment_purpose = document.getElementById('appointment_purpose')?.value || '';
            }
            console.log('Form Data:', formEntries);

            // Send data to backend to store in appointments table
            fetch('submitAppointment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formEntries)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment submitted successfully!');
                    appointmentForm.reset();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(async err => {
                // Try to get and log the raw response text for debugging
                try {
                    const res = await fetch('submitAppointment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formEntries)
                    });
                    const text = await res.text();
                    console.error('Raw response:', text);
                } catch(fetchErr) {
                    console.error('Fetch error:', fetchErr);
                }
                alert('Submission failed. Please try again.');
                console.error(err);
            });
            
        });
    }
});
