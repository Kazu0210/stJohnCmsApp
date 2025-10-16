// secretary.js - minimal interactions for the secretary dashboard
document.addEventListener('DOMContentLoaded', function () {
  // Add click feedback to cards
  document.querySelectorAll('.card').forEach(function(card) {
    card.addEventListener('click', function() {
      // Basic visual feedback
      card.classList.add('active');
      setTimeout(function(){ card.classList.remove('active'); }, 200);
    });
  });
});
