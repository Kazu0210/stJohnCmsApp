

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
                                        let actions = '';
                                        if ((payment.status || '').toLowerCase() !== 'rejected') {
                                            actions = `<button class="btn btn-success btn-sm confirm-payment" data-id="${payment.paymentId}" title="Confirm Payment"><i class="bi bi-check-circle"></i></button> <button class="btn btn-danger btn-sm reject-payment" data-id="${payment.paymentId}" title="Reject Payment"><i class="bi bi-x-circle"></i></button>`;
                                        }
                                        return [
                                            clientName,
                                            lotNumber,
                                            amountPaid,
                                            payment.status || '',
                                            paymentMethod,
                                            payment.reference || '',
                                            payment.datePaid || '',
                                            actions
                                        ];
                                });
                                table.clear();
                                table.rows.add(rows).draw();
                                // Add click handler for reject button
                                $('#paymentsTable').off('click', '.reject-payment').on('click', '.reject-payment', function() {
                                    var paymentId = $(this).data('id');
                                    if (confirm('Are you sure you want to reject this payment?')) {
                                        $.ajax({
                                            url: '/stJohnCmsApp/cms.api/updatePaymentStatus.php',
                                            method: 'POST',
                                            data: { paymentId: paymentId, status: 'Rejected' },
                                            dataType: 'json',
                                            success: function(res) {
                                                if (res.success) {
                                                    alert('Payment rejected successfully.');
                                                    // Optionally reload the table
                                                    location.reload();
                                                } else {
                                                    alert(res.message || 'Failed to reject payment.');
                                                }
                                            },
                                            error: function() {
                                                alert('Error updating payment status.');
                                            }
                                        });
                                    }
                                });
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
