/* global bootstrap: false */
(() => {
  'use strict'
  const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(tooltipTriggerEl => {
    new bootstrap.Tooltip(tooltipTriggerEl)
  })
})

// Gestione logout nel sidebar
document.addEventListener('DOMContentLoaded', () => {
  console.log('Sidebar JS loaded');
  
  const logoutLinks = document.querySelectorAll('.logout-link');
  console.log('Found logout links:', logoutLinks.length);

  logoutLinks.forEach(link => {
    console.log('Adding click listener to logout link:', link);
    
    link.addEventListener('click', (e) => {
      console.log('Logout link clicked');
      e.preventDefault();
      
      if (typeof logout === 'function') {
        console.log('Logout function found, calling it');
        logout(); // Usa la funzione logout definita nel footer (che già include la conferma)
      } else {
        console.log('Logout function not found, using fallback');
        // Fallback se la funzione logout non è disponibile
        if (confirm('Vuoi davvero uscire?')) {
          window.location.href = 'logout.php';
        }
      }
    });
  });
});