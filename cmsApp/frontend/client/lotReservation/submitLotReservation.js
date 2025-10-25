$(document).ready(function() {
    $(".lot-reservation-form").on("submit", function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        // Add status field for reservation
        formData.append('status', 'For Reservation');

        // Validate required fields (client name, address, contact, id, reservation date, lot type)
        var clientName = $("#client_name").val().trim();
        var clientAddress = $("#client_address").val().trim();
        var clientContact = $("#client_contact").val().trim();
        var clientIdFile = $("#client_id_upload")[0].files.length;
        var reservationDate = $("#reservation_date").val().trim();
        var lotTypeId = $("#preferred_lot").val();

        if (!clientName || !clientAddress || !clientContact || !clientIdFile || !reservationDate || !lotTypeId) {
            alert("Please fill in all required fields and upload the required files.");
            return;
        }

        // Validate contact number (Philippines format)
        if (!/^(09)\d{9}$/.test(clientContact)) {
            alert("Warning: Invalid Contact Number. It must be 11 digits and start with '09'.");
            return;
        }

        if (!confirm("Are you sure you want to submit this reservation request?")) {
            return;
        }

        $.ajax({
            url: $(form).attr("action"),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(result) {
                if (result.status === "success") {
                    alert("Reservation submitted successfully! The lot is now marked Pending. Redirecting to the map...");
                    form.reset();
                    // Reset file input labels
                    $(".file-name").text("No file chosen").css("color", "#6c757d");
                    setTimeout(function() {
                        window.location.href = "../cemeteryMap/cemeteryMap.php";
                    }, 1500);
                } else {
                    alert("Error: " + (result.message || "Unknown error occurred."));
                }
            },
            error: function(xhr, status, error) {
                let msg = "Reservation submission failed: ";
                if (xhr.responseText) {
                    msg += xhr.responseText;
                } else {
                    msg += error;
                }
                alert(msg);
            }
        });
    });
});
