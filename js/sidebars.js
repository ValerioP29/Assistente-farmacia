/* global bootstrap: false */
(() => {
  'use strict'
  const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(tooltipTriggerEl => {
    new bootstrap.Tooltip(tooltipTriggerEl)
  })
})

// This file is part of the CodeIgniter framework.
document.addEventListener('DOMContentLoaded', () => {

  logout = document.getElementById('logout-link');

  if (logout) {
    logout.addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm('Vuoi davvero uscire?')) {
        window.location.href = logout.href;
      }
    });
  }

});