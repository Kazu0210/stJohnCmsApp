$(document).ready(function() {
    // Fetch payments data from backend and populate DataTable
    $.ajax({
        url: '/stJohnCmsApp/cms.api/fetchPayments.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && Array.isArray(response.data)) {
                var table = $('#paymentsTable').DataTable();
                table.clear();
                var rows = response.data.map(function(payment) {
                    // Format lot info
                    var lot = `${payment.area || ''}-${payment.block || ''}-${payment.row || ''}-${payment.lotNumber || ''}`;
                    // Format amount
                    var amountPaid = payment.amount ? `₱${parseFloat(payment.amount).toLocaleString()}` : '';
                    // Format payment method
                    var methodMap = { 1: 'GCash', 2: 'Bank Transfer', 3: 'Cash' };
                    var paymentMethod = methodMap[payment.paymentMethodId] || 'N/A';
                    return [
                        payment.paymentId || '',
                        payment.clientName || '',
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
});
