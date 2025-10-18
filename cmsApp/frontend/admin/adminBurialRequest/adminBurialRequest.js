// DataTable initialization for adminBurialRequest.php
$(document).ready(function() {
    $('#burialRequestsTable').DataTable({
        "ajax": {
            "url": "../../../../cms.api/fetchAllBurialRequests.php",
            "dataSrc": "requests"
        },
        "columns": [
            { "data": "userName", "title": "Client Name" },
            { "data": "lotId" },
            { "data": "reservationId" },
            { "data": "deceasedName" },
            { "data": "burialDate" },
            {
                "data": "deceasedValidId",
                "render": function(data, type, row) {
                    if (data) {
                        return '<button class="btn btn-link btn-sm view-doc-btn" data-doc-url="' + data + '" data-doc-type="validId">View</button>';
                    } else {
                        return '';
                    }
                }
            },
            {
                "data": "deathCertificate",
                "render": function(data, type, row) {
                    if (data) {
                        return '<button class="btn btn-link btn-sm view-doc-btn" data-doc-url="' + data + '" data-doc-type="deathCertificate">View</button>';
                    } else {
                        return '';
                    }
                }
            },
            {
                "data": "burialPermit",
                "render": function(data, type, row) {
                    if (data) {
                        return '<button class="btn btn-link btn-sm view-doc-btn" data-doc-url="' + data + '" data-doc-type="burialPermit">View</button>';
                    } else {
                        return '';
                    }
                }
            },
            { "data": "status" },
            {
                "data": null,
                "render": function(data, type, row) {
                    return '<button class="btn btn-primary btn-sm">View</button>';
                }
            }
        ]
    });
    // Handle view document button click
    $('#burialRequestsTable').on('click', '.view-doc-btn', function() {
        var url = $(this).data('doc-url');
        var type = $(this).data('doc-type');
        var modalBody = $('#documentModalBody');
        var modalTitle = $('#documentModalLabel');

        // Always use /stJohnCmsApp/uploads/burial_requests/ as base path
        if (url && !url.match(/^https?:\/\//i)) {
            // Remove any leading slashes and folder names
            url = url.replace(/^\/+/, '');
            url = url.replace(/^(uploads\/burial_requests\/)+/, '');
            url = '/stJohnCmsApp/uploads/burial_requests/' + url;
        }

        // Set modal title
        if (type === 'validId') {
            modalTitle.text('Deceased Valid ID');
        } else if (type === 'deathCertificate') {
            modalTitle.text('Death Certificate');
        } else if (type === 'burialPermit') {
            modalTitle.text('Burial Permit');
        } else {
            modalTitle.text('View Document');
        }

        // Display image or file
        if (url.match(/\.(jpg|jpeg|png|gif)$/i)) {
            modalBody.html('<img src="' + url + '" class="img-fluid" alt="Document">');
        } else {
            modalBody.html('<a href="' + url + '" target="_blank">Open Document</a>');
        }

        var modalEl = new bootstrap.Modal(document.getElementById('documentModal'));
        modalEl.show();
    });
});
