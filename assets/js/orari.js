/**
 * JavaScript per la gestione degli orari farmacia
 * Assistente Farmacia Panel
 */

document.addEventListener('DOMContentLoaded', function() {
    const orariForm = document.getElementById('orariForm');
    const turnoForm = document.getElementById('turnoForm');
    
    if (orariForm) {
        orariForm.addEventListener('submit', handleOrariSubmit);
        
        // Gestione checkbox "Chiuso"
        const closedCheckboxes = document.querySelectorAll('.day-closed-checkbox');
        closedCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', handleDayClosedChange);
        });

        // Gestione modalità orario
        const modeInputs = document.querySelectorAll('.day-mode');
        modeInputs.forEach(input => {
            input.addEventListener('change', handleDayModeChange);
        });

        // Inizializza stato righe
        document.querySelectorAll('.day-row').forEach(row => {
            initializeDayRow(row);
        });
    }
    
    if (turnoForm) {
        turnoForm.addEventListener('submit', handleTurnoSubmit);
    }
});

/**
 * Gestisce l'invio del form degli orari
 */
async function handleOrariSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Disabilita il pulsante e mostra loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvataggio...';
    
    try {
        // Validazione form
        if (!validateOrariForm(form)) {
            throw new Error('Per favore correggi gli errori nel form');
        }
        
        // Prepara i dati del form
        const formData = new FormData(form);
        
        // Invia la richiesta
        const response = await fetch('api/pharmacies/update-hours.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Successo
            showAlert('Orari aggiornati con successo!', 'success');
        } else {
            // Errore dal server
            throw new Error(result.message || 'Errore durante il salvataggio');
        }
        
    } catch (error) {
        console.error('Errore:', error);
        showAlert(error.message || 'Errore durante il salvataggio degli orari', 'danger');
    } finally {
        // Ripristina il pulsante
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

/**
 * Gestisce l'invio del form del turno
 */
async function handleTurnoSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Disabilita il pulsante e mostra loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvataggio...';
    
    try {
        // Prepara i dati del form
        const formData = new FormData(form);
        
        // Aggiungi CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        formData.append('csrf_token', csrfToken);
        
        // Invia la richiesta
        const response = await fetch('api/pharmacies/update-turno.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Successo
            showAlert('Turno aggiornato con successo!', 'success');
        } else {
            // Errore dal server
            throw new Error(result.message || 'Errore durante il salvataggio');
        }
        
    } catch (error) {
        console.error('Errore:', error);
        showAlert(error.message || 'Errore durante il salvataggio del turno', 'danger');
    } finally {
        // Ripristina il pulsante
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

/**
 * Gestisce il cambio di stato "Chiuso" per un giorno
 */
function handleDayClosedChange(event) {
    const checkbox = event.target;
    const day = checkbox.dataset.day;
    const row = checkbox.closest('tr');
    
    // Trova tutti gli input di tempo nella riga
    toggleDayClosedState(row, checkbox.checked);
}

/**
 * Valida il form degli orari
 */
function validateOrariForm(form) {
    const days = ['lun', 'mar', 'mer', 'gio', 'ven', 'sab', 'dom'];
    let isValid = true;

    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    days.forEach(day => {
        const closedCheckbox = form.querySelector(`input[name="${day}_chiuso"]`);
        const isClosed = closedCheckbox && closedCheckbox.checked;
        const modeInput = form.querySelector(`input[name="${day}_mode"]:checked`);
        const mode = modeInput ? modeInput.value : 'split';
        
        if (!isClosed) {
            if (mode === 'continuous') {
                const continuousOpen = form.querySelector(`input[name="${day}_continuato_apertura"]`);
                const continuousClose = form.querySelector(`input[name="${day}_continuato_chiusura"]`);

                if (!continuousOpen.value || !continuousClose.value) {
                    showFieldError(continuousOpen, 'Orario continuato obbligatorio');
                    isValid = false;
                } else if (continuousOpen.value >= continuousClose.value) {
                    showFieldError(continuousOpen, 'Apertura deve essere prima della chiusura');
                    isValid = false;
                }
            } else {
                // Verifica orari mattina
                const morningOpen = form.querySelector(`input[name="${day}_mattina_apertura"]`);
                const morningClose = form.querySelector(`input[name="${day}_mattina_chiusura"]`);
                
                if (!morningOpen.value || !morningClose.value) {
                    showFieldError(morningOpen, 'Orari mattina obbligatori');
                    isValid = false;
                } else if (morningOpen.value >= morningClose.value) {
                    showFieldError(morningOpen, 'Apertura deve essere prima della chiusura');
                    isValid = false;
                }
                
                // Verifica orari pomeriggio
                const afternoonOpen = form.querySelector(`input[name="${day}_pomeriggio_apertura"]`);
                const afternoonClose = form.querySelector(`input[name="${day}_pomeriggio_chiusura"]`);
                
                if (!afternoonOpen.value || !afternoonClose.value) {
                    showFieldError(afternoonOpen, 'Orari pomeriggio obbligatori');
                    isValid = false;
                } else if (afternoonOpen.value >= afternoonClose.value) {
                    showFieldError(afternoonOpen, 'Apertura deve essere prima della chiusura');
                    isValid = false;
                }

                if (morningClose && afternoonOpen && morningClose.value && afternoonOpen.value && morningClose.value > afternoonOpen.value) {
                    showFieldError(afternoonOpen, 'Gli orari non possono sovrapporsi');
                    isValid = false;
                }
            }
        }
    });
    
    return isValid;
}

/**
 * Mostra errore per un campo
 */
function showFieldError(field, message) {
    // Rimuovi errori precedenti
    clearFieldError({ target: field });
    
    // Aggiungi classe di errore
    field.classList.add('is-invalid');
    
    // Crea messaggio di errore
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    // Inserisci dopo il campo
    field.parentNode.appendChild(errorDiv);
}

/**
 * Rimuove errore da un campo
 */
function clearFieldError(event) {
    const field = event.target;
    
    // Rimuovi classe di errore
    field.classList.remove('is-invalid');
    
    // Rimuovi messaggio di errore
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Ripristina il form agli orari originali
 */
function resetForm() {
    if (confirm('Sei sicuro di voler ripristinare gli orari originali? Le modifiche non salvate andranno perse.')) {
        window.location.reload();
    }
}

/**
 * Mostra un alert
 */
function showAlert(message, type = 'info') {
    // Crea l'alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    `;
    
    // Inserisci all'inizio del contenuto principale
    const mainContent = document.getElementById('main-content');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        // Auto-rimuovi dopo 5 secondi
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

/**
 * Gestisce il cambio di modalità oraria per una riga
 */
function handleDayModeChange(event) {
    const row = event.target.closest('.day-row');
    const mode = event.target.value;
    applyDayMode(row, mode, { clearIncompatible: true });
}

/**
 * Inizializza lo stato di una riga al caricamento
 */
function initializeDayRow(row) {
    const selectedMode = row.querySelector('.day-mode:checked')?.value || 'split';
    applyDayMode(row, selectedMode);

    const isClosed = row.querySelector('.day-closed-checkbox')?.checked;
    if (isClosed) {
        toggleDayClosedState(row, true);
    }
}

/**
 * Applica la modalità oraria spezzato/continuato a una riga
 */
function applyDayMode(row, mode, options = {}) {
    const clearIncompatible = options.clearIncompatible === true;
    const isClosed = row.querySelector('.day-closed-checkbox')?.checked;
    const splitContainers = row.querySelectorAll('.split-hours');
    const continuousContainers = row.querySelectorAll('.continuous-hours');
    const splitInputs = row.querySelectorAll('.split-input');
    const continuousInputs = row.querySelectorAll('.continuous-input');

    splitContainers.forEach(container => container.classList.toggle('d-none', mode === 'continuous'));
    continuousContainers.forEach(container => container.classList.toggle('d-none', mode !== 'continuous'));

    if (isClosed) {
        toggleDayClosedState(row, true);
        return;
    }

    splitInputs.forEach(input => {
        if (clearIncompatible && mode === 'continuous') {
            input.value = '';
        }
        input.disabled = mode === 'continuous';
        if (mode === 'continuous') {
            clearInlineError(input);
        }
    });
    continuousInputs.forEach(input => {
        if (clearIncompatible && mode !== 'continuous') {
            input.value = '';
        }
        input.disabled = mode !== 'continuous';
        if (mode !== 'continuous') {
            clearInlineError(input);
        }
    });
}

/**
 * Gestisce stato chiuso/aperto di una riga
 */
function toggleDayClosedState(row, isClosed) {
    const timeInputs = row.querySelectorAll('input[type="time"]');

    if (isClosed) {
        timeInputs.forEach(input => {
            input.disabled = true;
            input.classList.add('text-muted');
            clearInlineError(input);
        });
    } else {
        const selectedMode = row.querySelector('.day-mode:checked')?.value || 'split';
        timeInputs.forEach(input => input.classList.remove('text-muted'));
        applyDayMode(row, selectedMode);
    }
}

/**
 * Rimuove eventuali errori mostrati su un campo
 */
function clearInlineError(field) {
    if (!field) return;
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode?.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}
