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
                        return '<button class="btn btn-link btn-sm" onclick="window.open(\'' + data + '\', \"_blank\")">View</button>';
                    } else {
                        return '';
                    }
                }
            },
            {
                "data": "deathCertificate",
                "render": function(data, type, row) {
                    if (data) {
                        return '<button class="btn btn-link btn-sm" onclick="window.open(\'' + data + '\', \"_blank\")">View</button>';
                    } else {
                        return '';
                    }
                }
            },
            {
                "data": "burialPermit",
                "render": function(data, type, row) {
                    if (data) {
                        return '<button class="btn btn-link btn-sm" onclick="window.open(\'' + data + '\', \"_blank\")">View</button>';
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
});
