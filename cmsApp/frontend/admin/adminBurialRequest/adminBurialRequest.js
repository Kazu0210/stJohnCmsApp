// DataTable initialization for adminBurialRequest.php
$(document).ready(function() {
    var burialTable = $('#burialRequestsTable').DataTable({
        "ajax": function(data, callback, settings) {
            // Use jQuery ajax to capture HTTP errors and responseText
            $.ajax({
                url: '../../../../cms.api/fetchAllBurialRequests.php',
                method: 'GET',
                dataType: 'json',
                success: function(json) {
                    try {
                        var rows = [];
                        if (!json) rows = [];
                        else if (Array.isArray(json.requests)) rows = json.requests;
                        else if (Array.isArray(json)) rows = json;
                        else {
                            for (var k in json) {
                                if (Array.isArray(json[k])) {
                                    rows = json[k];
                                    break;
                                }
                            }
                        }
                        // Provide DataTables with the normalized data
                        callback({ data: rows });
                    } catch (e) {
                        console.error('Error parsing burial requests response', e);
                        $('#ajaxErrorMessage').removeClass('d-none');
                        callback({ data: [] });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Ajax fetch failed:', textStatus, errorThrown);
                    console.error('Server response:', jqXHR && jqXHR.responseText ? jqXHR.responseText : '<no response>');
                    $('#ajaxErrorMessage').removeClass('d-none');
                    $('#emptyTableMessage').addClass('d-none');
                    // Give DataTables an empty data set so it doesn't break
                    callback({ data: [] });
                }
            });
        },
        "language": {
            "emptyTable": "No burial requests found."
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
                    }
                    return '';
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
            { 
                "data": "status", 
                "render": function(data, type, row) {
                    if (typeof data === 'string' && data.length > 0) {
                        return data.charAt(0).toUpperCase() + data.slice(1).toLowerCase();
                    }
                    return data;
                }
            },
            {
                "data": null,
                "render": function(data, type, row) {
                    // Defensive: ensure row is an object
                    row = row || {};
                    var status = (row.status || '').toLowerCase();
                    if (status === 'approved' || status === 'rejected') {
                        return '';
                    }
                    var id = row.requestId || '';
                    return '<button class="btn btn-success btn-sm approve-btn" data-id="' + id + '">Approve</button>' +
                           '<button class="btn btn-danger btn-sm reject-btn" data-id="' + id + '">Reject</button>';
                }
            }
        ]
    });
    var emptyMsgEl = $('#emptyTableMessage');

    // Utility to toggle the empty table message based on row count
    function updateEmptyMessage() {
        var count = burialTable.data().count ? burialTable.data().count() : burialTable.rows().count();
        if (parseInt(count, 10) === 0) {
            emptyMsgEl.removeClass('d-none');
        } else {
            emptyMsgEl.addClass('d-none');
        }
    }

    // Update after initial load and on subsequent XHRs/draws
    $('#burialRequestsTable').on('xhr.dt', function(e, settings, json, xhr) {
        // Hide any previous ajax error display
        $('#ajaxErrorMessage').addClass('d-none');
        // If xhr is present and has non-JSON content, DataTables may still call this handler.
        updateEmptyMessage();
    });
    
    // DataTables global ajax error event
    $('#burialRequestsTable').on('error.dt', function(e, settings, techNote, message) {
        console.error('DataTables error:', techNote, message);
        // Try to extract server response from settings.jqXHR or settings.oInit
        var jqXhr = settings && settings.jqXHR ? settings.jqXHR : null;
        if (jqXhr && jqXhr.responseText) {
            console.error('Server response:', jqXhr.responseText);
        }
        $('#ajaxErrorMessage').removeClass('d-none');
        // Also hide the empty message to avoid confusion
        $('#emptyTableMessage').addClass('d-none');
    });
    $('#burialRequestsTable').on('draw.dt', function() {
        updateEmptyMessage();
    });

    // Initial check (in case data is inline or cached)
    updateEmptyMessage();
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
        if (url && url.match(/\.(jpg|jpeg|png|gif)$/i)) {
            modalBody.html('<img src="' + url + '" class="img-fluid" alt="Document">');
        } else {
            if (url) {
                modalBody.html('<a href="' + url + '" target="_blank">Open Document</a>');
            } else {
                modalBody.html('<div class="text-muted">No document available.</div>');
            }
        }

        var modalEl = new bootstrap.Modal(document.getElementById('documentModal'));
        modalEl.show();
    });

    // Approve button logic with confirmation
    $('#burialRequestsTable').on('click', '.approve-btn', function() {
        var requestId = $(this).data('id');
        var rowData = burialTable.row($(this).closest('tr')).data() || {};
        if (confirm('Are you sure you want to approve this burial request?')) {
            $.ajax({
                url: '../../../../cms.api/updateBurialRequest.php',
                type: 'POST',
                data: {
                    requestId: requestId,
                    deceasedName: rowData.deceasedName || '',
                    burialDate: rowData.burialDate || '',
                    status: 'approved'
                },
                success: function(response) {
                    if (response.success) {
                        burialTable.ajax.reload(null, false);
                        updateClientReservation(rowData.reservationId || '', rowData.requestId || '');
                    } else {
                        alert('Failed to approve: ' + (response.message || 'Unknown error.'));
                    }
                },
                error: function() {
                    alert('Error occurred while approving request.');
                }
            });
        }
    });

    // Reject button logic with confirmation
    $('#burialRequestsTable').on('click', '.reject-btn', function() {
        var requestId = $(this).data('id');
        var rowData = burialTable.row($(this).closest('tr')).data() || {};
        if (confirm('Are you sure you want to reject this burial request?')) {
            $.ajax({
                url: '../../../../cms.api/updateBurialRequest.php',
                type: 'POST',
                data: {
                    requestId: requestId,
                    deceasedName: rowData.deceasedName || '',
                    burialDate: rowData.burialDate || '',
                    status: 'rejected'
                },
                success: function(response) {
                    if (response.success) {
                        burialTable.ajax.reload(null, false);
                    } else {
                        alert('Failed to reject: ' + (response.message || 'Unknown error.'));
                    }
                },
                error: function() {
                    alert('Error occurred while rejecting request.');
                }
            });
        }
    });

    const updateClientReservation = (reservationId, requestId) => {
        console.log('Updating client reservation for ID:', reservationId);
        console.log('Associated burial request ID:', requestId);
        $.ajax({
            url: '../../../../cms.api/updateClientReservation.php',
            type: 'POST',
            data: {
                reservationId: reservationId,
                requestId: requestId,
            }
        })
    }
});
