/**
 * JavaScript per la gestione del profilo farmacia
 * Assistente Farmacia Panel
 */

document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileSubmit);
        
        // Validazione real-time
        const inputs = profileForm.querySelectorAll('input[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
        
        // Contatore caratteri per la descrizione
        const descriptionTextarea = document.getElementById('description');
        if (descriptionTextarea) {
            descriptionTextarea.addEventListener('input', updateCharacterCount);
            updateCharacterCount(); // Inizializza il contatore
        }
    }
});

/**
 * Gestisce l'invio del form del profilo
 */
async function handleProfileSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Disabilita il pulsante e mostra loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvataggio...';
    
    try {
        // Validazione form
        if (!validateForm(form)) {
            throw new Error('Per favore correggi gli errori nel form');
        }
        
        // Prepara i dati del form
        const formData = new FormData(form);
        
        // Invia la richiesta
        const response = await fetch('api/pharmacies/update-profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Successo
            showAlert('Profilo aggiornato con successo!', 'success');
            
            // Ricarica la pagina per mostrare i dati aggiornati
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Errore dal server
            throw new Error(result.message || 'Errore durante il salvataggio');
        }
        
    } catch (error) {
        console.error('Errore:', error);
        showAlert(error.message || 'Errore durante il salvataggio del profilo', 'danger');
    } finally {
        // Ripristina il pulsante
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

/**
 * Valida un singolo campo
 */
function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    const fieldName = field.name;
    
    // Rimuovi errori precedenti
    clearFieldError(event);
    
    let isValid = true;
    let errorMessage = '';
    
    // Validazioni specifiche per campo
    switch (fieldName) {
        case 'email':
            if (value && !isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Inserisci un indirizzo email valido';
            }
            break;
            
        case 'phone_number':
            if (value && !isValidPhone(value)) {
                isValid = false;
                errorMessage = 'Inserisci un numero di telefono valido';
            }
            break;
            
        case 'business_name':
        case 'nice_name':
        case 'city':
        case 'address':
            if (!value) {
                isValid = false;
                errorMessage = 'Questo campo è obbligatorio';
            } else if (value.length < 2) {
                isValid = false;
                errorMessage = 'Inserisci almeno 2 caratteri';
            }
            break;
            
        case 'description':
            if (value && value.length > 500) {
                isValid = false;
                errorMessage = 'La descrizione non può superare i 500 caratteri';
            }
            break;
    }
    
    // Mostra errore se necessario
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

/**
 * Valida l'intero form
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField({ target: field })) {
            isValid = false;
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
 * Aggiorna il contatore di caratteri per la descrizione
 */
function updateCharacterCount() {
    const textarea = document.getElementById('description');
    const counter = textarea.parentNode.querySelector('.form-text');
    
    if (textarea && counter) {
        const currentLength = textarea.value.length;
        const maxLength = 500;
        const remaining = maxLength - currentLength;
        
        counter.textContent = `${currentLength}/${maxLength} caratteri`;
        
        // Cambia colore se si avvicina al limite
        if (remaining <= 50) {
            counter.className = 'form-text text-warning';
        } else if (remaining <= 0) {
            counter.className = 'form-text text-danger';
        } else {
            counter.className = 'form-text text-muted';
        }
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
 * Valida email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Valida telefono
 */
function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
    return phoneRegex.test(phone);
} 