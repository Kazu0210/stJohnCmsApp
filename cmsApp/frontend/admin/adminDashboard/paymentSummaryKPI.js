// This script fetches payment summary KPI data and populates the dashboard card
fetch('../../../../cms.api/fetchPaymentSummaryKPI.php')
  .then(response => response.json())
  .then(data => {
    document.getElementById('payments-received').textContent = `₱${parseFloat(data.total_amount).toLocaleString()}`;
    document.getElementById('outstanding-balances').textContent = `₱${parseFloat(data.outstanding_balance).toLocaleString()}`;
  })
  .catch(error => {
    document.getElementById('payments-received').textContent = '₱0';
    document.getElementById('outstanding-balances').textContent = '₱0';
    console.error('Error fetching payment summary:', error);
  });
