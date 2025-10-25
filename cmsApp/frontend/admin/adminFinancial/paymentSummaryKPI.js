// Fetch payment summary KPI and populate financial cards
fetch('../../../../cms.api/fetchPaymentSummaryKPI.php')
  .then(response => response.json())
  .then(data => {
    // Total Income (YTD) - using total_amount
    document.getElementById('totalIncomeYTD').textContent = `₱${parseFloat(data.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    // Income This Month - for now, use total_amount (replace with monthly logic if needed)
    document.getElementById('incomeThisMonth').textContent = `₱${parseFloat(data.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    // Attention Needed - using outstanding_balance and upcoming_due
    document.getElementById('attentionCount').textContent = `${parseInt(data.outstanding_balance) || 0} Pending / ${parseInt(data.upcoming_due) || 0} Deferred`;
  })
  .catch(error => {
    document.getElementById('totalIncomeYTD').textContent = '₱0.00';
    document.getElementById('incomeThisMonth').textContent = '₱0.00';
    document.getElementById('attentionCount').textContent = '0 Pending / 0 Deferred';
    // console.error('Error fetching payment summary:', error);
  });
