

$(document).ready(function() {
    // Fetch users and reservations first
    $.ajax({
        url: '/stJohnCmsApp/cms.api/fetchUsers.php',
        method: 'GET',
        dataType: 'json',
        success: function(userResponse) {
            var userMap = {};
            if (userResponse.success && Array.isArray(userResponse.data)) {
                userResponse.data.forEach(function(user) {
                    userMap[user.userId] = `${user.firstName || ''} ${user.lastName || ''}`.trim();
                });
            }
            // Fetch reservations
            $.ajax({
                url: '/stJohnCmsApp/cms.api/fetchReservations.php',
                method: 'GET',
                dataType: 'json',
                success: function(reservationResponse) {
                    var reservationMap = {};
                    if (reservationResponse.success && Array.isArray(reservationResponse.data)) {
                        reservationResponse.data.forEach(function(reservation) {
                            reservationMap[reservation.reservationId] = reservation.lotNumber || '';
                        });
                    }
                    // Now fetch payments
                    $.ajax({
                        url: '/stJohnCmsApp/cms.api/fetchPayments.php',
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && Array.isArray(response.data)) {
                                var table = $('#paymentsTable').DataTable();
                                table.clear();
                                var rows = response.data.map(function(payment) {
                                    // Get lotNumber using reservationId
                                    var lotNumber = reservationMap[payment.reservationId] || payment.reservationId || '';
                                    // Format amount
                                    var amountPaid = payment.amount ? `₱${parseFloat(payment.amount).toLocaleString()}` : '';
                                    // Format payment method
                                    var methodMap = { 1: 'GCash', 2: 'Bank Transfer', 3: 'Cash' };
                                    var paymentMethod = methodMap[payment.paymentMethodId] || 'N/A';
                                    // Get client name from userId
                                    var clientName = userMap[payment.userId] || payment.userId || '';
                                    return [
                                        clientName,
                                        lotNumber,
                                        amountPaid,
                                        payment.status || '',
                                        paymentMethod,
                                        payment.reference || '',
                                        payment.datePaid || '',
                                        '' // Actions column (empty)
                                    ];
                                });
                                table.clear();
                                table.rows.add(rows).draw();
                                // Hide Payment ID column and add Actions column if not already set
                                if (!table.settings()[0].aoColumns || table.settings()[0].aoColumns.length !== 8) {
                                    table.destroy();
                                    $('#paymentsTable').DataTable({
                                        data: rows,
                                        columns: [
                                            { title: 'Client Name' },
                                            { title: 'Lot' },
                                            { title: 'Amount Paid' },
                                            { title: 'Status' },
                                            { title: 'Payment Method' },
                                            { title: 'Reference/OR No.' },
                                            { title: 'Date Paid' },
                                            { title: 'Actions', orderable: false, searchable: false }
                                        ]
                                    });
                                }
                            } else {
                                $('#paymentsTable').DataTable().clear().draw();
                            }
                        },
                        error: function() {
                            $('#paymentsTable').DataTable().clear().draw();
                        }
                    });
                },
                error: function() {
                    // If reservation fetch fails, fallback to payments only
                    $.ajax({
                        url: '/stJohnCmsApp/cms.api/fetchPayments.php',
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && Array.isArray(response.data)) {
                                var table = $('#paymentsTable').DataTable();
                                table.clear();
                                var rows = response.data.map(function(payment) {
                                    var amountPaid = payment.amount ? `₱${parseFloat(payment.amount).toLocaleString()}` : '';
                                    var methodMap = { 1: 'GCash', 2: 'Bank Transfer', 3: 'Cash' };
                                    var paymentMethod = methodMap[payment.paymentMethodId] || 'N/A';
                                    var clientName = userMap[payment.userId] || payment.userId || '';
                                    return [
                                        payment.paymentId || '',
                                        clientName,
                                        payment.reservationId || '',
                                        amountPaid,
                                        payment.status || '',
                                        paymentMethod,
                                        payment.reference || '',
                                        payment.datePaid || ''
                                    ];
                                });
                                table.rows.add(rows).draw();
                            } else {
                                $('#paymentsTable').DataTable().clear().draw();
                            }
                        },
                        error: function() {
                            $('#paymentsTable').DataTable().clear().draw();
                        }
                    });
                }
            });
        },
        error: function() {
            // If user fetch fails, fallback to payments only
            $.ajax({
                url: '/stJohnCmsApp/cms.api/fetchPayments.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && Array.isArray(response.data)) {
                        var table = $('#paymentsTable').DataTable();
                        table.clear();
                        var rows = response.data.map(function(payment) {
                            var amountPaid = payment.amount ? `₱${parseFloat(payment.amount).toLocaleString()}` : '';
                            var methodMap = { 1: 'GCash', 2: 'Bank Transfer', 3: 'Cash' };
                            var paymentMethod = methodMap[payment.paymentMethodId] || 'N/A';
                            return [
                                payment.paymentId || '',
                                payment.userId || '',
                                payment.reservationId || '',
                                amountPaid,
                                payment.status || '',
                                paymentMethod,
                                payment.reference || '',
                                payment.datePaid || ''
                            ];
                        });
                        table.rows.add(rows).draw();
                    } else {
                        $('#paymentsTable').DataTable().clear().draw();
                    }
                },
                error: function() {
                    $('#paymentsTable').DataTable().clear().draw();
                }
            });
        }
    });
});
