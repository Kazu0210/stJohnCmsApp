
$(document).ready(function() {
    // Fetch users first to map userId to client name
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
                            // Format lot info (if available)
                            var lot = `${payment.area || ''}-${payment.block || ''}-${payment.row || ''}-${payment.lotNumber || ''}`;
                            // Format amount
                            var amountPaid = payment.amount ? `₱${parseFloat(payment.amount).toLocaleString()}` : '';
                            // Format payment method
                            var methodMap = { 1: 'GCash', 2: 'Bank Transfer', 3: 'Cash' };
                            var paymentMethod = methodMap[payment.paymentMethodId] || 'N/A';
                            // Get client name from userId
                            var clientName = userMap[payment.userId] || payment.userId || '';
                            return [
                                payment.paymentId || '',
                                clientName,
                                lot,
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
                            var lot = `${payment.area || ''}-${payment.block || ''}-${payment.row || ''}-${payment.lotNumber || ''}`;
                            var amountPaid = payment.amount ? `₱${parseFloat(payment.amount).toLocaleString()}` : '';
                            var methodMap = { 1: 'GCash', 2: 'Bank Transfer', 3: 'Cash' };
                            var paymentMethod = methodMap[payment.paymentMethodId] || 'N/A';
                            return [
                                payment.paymentId || '',
                                payment.userId || '',
                                lot,
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
