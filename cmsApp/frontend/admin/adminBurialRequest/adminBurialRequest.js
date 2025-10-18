// DataTable initialization for adminBurialRequest.php
$(document).ready(function() {
    $('#burialRequestsTable').DataTable({
        "ajax": {
            "url": "../../../../cms.api/fetchAllBurialRequests.php",
            "dataSrc": "requests"
        },
        "columns": [
            { "data": "requestId" },
            { "data": "userId" },
            { "data": "lotId" },
            { "data": "reservationId" },
            { "data": "deceasedName" },
            { "data": "burialDate" },
            { "data": "deceasedValidId" },
            { "data": "deathCertificate" },
            { "data": "burialPermit" },
            { "data": "status" },
            { "data": "createdAt" },
            { "data": "updatedAt" },
            {
                "data": null,
                "render": function(data, type, row) {
                    return '<button class="btn btn-primary btn-sm">View</button>';
                }
            }
        ]
    });
});
