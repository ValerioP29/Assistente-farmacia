/**
 * Gestione Promozioni JavaScript
 * Assistente Farmacia Panel
 */

let currentPage = 1;
let totalPages = 1;
let currentFilters = {};
let productsList = [];
let selectedPromotionId = null;

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
    loadPromotions();
    loadProducts();
    setupEventListeners();
    loadStatistics();
});

// Setup event listeners
function setupEventListeners() {
    // Filtri
    document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 300));
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    document.getElementById('discountFilter').addEventListener('change', applyFilters);
    
    // Form promozione
    document.getElementById('promotionForm').addEventListener('submit', handlePromotionSubmit);
    document.getElementById('productSelect').addEventListener('change', handleProductSelect);
    document.getElementById('salePrice').addEventListener('input', calculateDiscount);
    document.getElementById('saleStartDate').addEventListener('change', validateDates);
    document.getElementById('saleEndDate').addEventListener('change', validateDates);
    
    // Import form
    document.getElementById('importForm').addEventListener('submit', handleImportSubmit);
}

// Caricamento promozioni
function loadPromotions(page = 1) {
    currentPage = page;
    showLoading();
    
    const params = new URLSearchParams({
        page: page,
        ...currentFilters
    });
    
    fetch(`api/pharma-products/list.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPromotions(data.promotions || data.products || []);
                updatePagination(data.total_pages || 1, page);
                totalPages = data.total_pages || 1;
            } else {
                showError('Errore nel caricamento delle promozioni: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showError('Errore di connessione');
        })
        .finally(() => {
            hideLoading();
        });
}

// Visualizzazione promozioni in layout a schede
function displayPromotions(promotions) {
    const grid = document.getElementById('promotionsGrid');
    
    if (!promotions || promotions.length === 0) {
        grid.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h4>Nessuna promozione trovata</h4>
                    <p>Non ci sono promozioni attive al momento. Crea la tua prima promozione!</p>
                    <button class="btn btn-primary" onclick="showAddPromotionModal()">
                        <i class="fas fa-plus me-1"></i> Crea Promozione
                    </button>
                </div>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = promotions.map(promotion => createPromotionCard(promotion)).join('');
}

// Creazione scheda promozione
function createPromotionCard(promotion) {
    const now = new Date();
    const startDate = new Date(promotion.sale_start_date);
    const endDate = new Date(promotion.sale_end_date);
    
    // Calcolo stato promozione
    let status = 'inactive';
    let statusClass = 'status-inactive';
    let statusText = 'Inattiva';
    
    if (promotion.is_on_sale == 1) {
        if (now < startDate) {
            status = 'upcoming';
            statusClass = 'status-upcoming';
            statusText = 'In arrivo';
        } else if (now >= startDate && now <= endDate) {
            status = 'active';
            statusClass = 'status-active';
            statusText = 'Attiva';
        } else {
            status = 'expired';
            statusClass = 'status-expired';
            statusText = 'Scaduta';
        }
    }
    
    // Calcolo sconto
    const originalPrice = parseFloat(promotion.price);
    const salePrice = parseFloat(promotion.sale_price || 0);
    const discount = salePrice > 0 ? Math.round(((originalPrice - salePrice) / originalPrice) * 100) : 0;
    
    // Calcolo tempo rimanente (solo giorni)
    const timeRemaining = calculateTimeRemaining(endDate);
    const progressPercentage = calculateProgressPercentage(startDate, endDate);
    
    // Immagine prodotto
    const productImage = promotion.image || promotion.global_image || 'images/default-product.png';
    
    return `
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card promotion-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-tag me-2"></i>
                        ${promotion.name}
                    </h5>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
                <div class="card-body">
                    <div class="product-image-container">
                        <img src="${productImage}" alt="${promotion.name}" class="product-image" 
                             onerror="this.src='images/default-product.png'">
                    </div>
                    
                    <div class="product-info">
                        <div class="product-name">${promotion.name}</div>
                        <div class="product-category">
                            <i class="fas fa-layer-group me-1"></i>
                            ${promotion.category || 'N/A'}
                        </div>
                        <div class="product-brand">
                            <i class="fas fa-building me-1"></i>
                            ${promotion.brand || 'N/A'}
                        </div>
                    </div>
                    
                    <div class="price-section">
                        <span class="original-price">€${originalPrice.toFixed(2)}</span>
                        <span class="sale-price">€${salePrice.toFixed(2)}</span>
                        <span class="discount-badge">-${discount}%</span>
                    </div>
                    
                    <div class="promotion-dates">
                        <div class="date-item">
                            <span class="date-label">Inizio:</span>
                            <span class="date-value">${formatDate(startDate)}</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Fine:</span>
                            <span class="date-value">${formatDate(endDate)}</span>
                        </div>
                    </div>
                    
                    ${status === 'active' ? `
                        <div class="time-remaining">
                            <small class="text-muted">Tempo rimanente: ${timeRemaining}</small>
                            <div class="progress mt-1">
                                <div class="progress-bar ${progressPercentage > 70 ? 'danger' : progressPercentage > 30 ? 'warning' : 'success'}" 
                                     style="width: ${progressPercentage}%"></div>
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="card-actions">
                        <button class="btn btn-action btn-edit" onclick="editPromotion(${promotion.id})">
                            <i class="fas fa-edit"></i> Modifica
                        </button>
                        <button class="btn btn-action btn-toggle" onclick="togglePromotion(${promotion.id}, ${promotion.is_on_sale})">
                            <i class="fas fa-${promotion.is_on_sale == 1 ? 'pause' : 'play'}"></i> 
                            ${promotion.is_on_sale == 1 ? 'Disattiva' : 'Attiva'}
                        </button>
                        <button class="btn btn-action btn-delete" onclick="deletePromotion(${promotion.id})">
                            <i class="fas fa-trash"></i> Elimina
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Calcolo tempo rimanente (solo giorni)
function calculateTimeRemaining(endDate) {
    const now = new Date();
    const end = new Date(endDate);
    const diff = end - now;
    
    if (diff <= 0) return 'Scaduta';
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days > 0) {
        return `${days} giorni`;
    } else {
        return 'Scade oggi';
    }
}

// Calcolo percentuale progresso (solo giorni)
function calculateProgressPercentage(startDate, endDate) {
    const now = new Date();
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (now < start) return 0;
    if (now > end) return 100;
    
    const total = end - start;
    const elapsed = now - start;
    return Math.round((elapsed / total) * 100);
}

// Formattazione data
function formatDate(date) {
    return new Date(date).toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Caricamento prodotti per selezione
function loadProducts() {
    fetch('api/pharma-products/list.php?limit=1000')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                productsList = data.products || [];
                populateProductSelect();
            }
        })
        .catch(error => console.error('Errore caricamento prodotti:', error));
}

// Popolamento select prodotti
function populateProductSelect() {
    const select = document.getElementById('productSelect');
    select.innerHTML = '<option value="">Scegli un prodotto...</option>';
    
    productsList.forEach(product => {
        const option = document.createElement('option');
        option.value = product.id;
        option.textContent = `${product.name} - €${parseFloat(product.price).toFixed(2)}`;
        select.appendChild(option);
    });
}

// Gestione selezione prodotto
function handleProductSelect() {
    const productId = document.getElementById('productSelect').value;
    const productInfo = document.getElementById('productInfo');
    
    if (!productId) {
        productInfo.style.display = 'none';
        return;
    }
    
    const product = productsList.find(p => p.id == productId);
    if (product) {
        document.getElementById('productImage').src = product.image || product.global_image || 'images/default-product.png';
        document.getElementById('productName').textContent = product.name;
        document.getElementById('productDescription').textContent = product.description || '';
        document.getElementById('currentPrice').textContent = `€${parseFloat(product.price).toFixed(2)}`;
        document.getElementById('productCategory').textContent = product.category || 'N/A';
        document.getElementById('productBrand').textContent = product.brand || 'N/A';
        document.getElementById('productInfo').style.display = 'block';
        
        // Imposta prezzo scontato iniziale
        document.getElementById('salePrice').value = product.price;
        calculateDiscount();
    }
}

// Calcolo sconto
function calculateDiscount() {
    const productId = document.getElementById('productSelect').value;
    const salePrice = parseFloat(document.getElementById('salePrice').value) || 0;
    
    if (!productId || salePrice <= 0) {
        document.getElementById('discountPercentage').textContent = '';
        return;
    }
    
    const product = productsList.find(p => p.id == productId);
    if (product) {
        const originalPrice = parseFloat(product.price);
        const discount = Math.round(((originalPrice - salePrice) / originalPrice) * 100);
        document.getElementById('discountPercentage').textContent = `${discount}%`;
    }
}

// Validazione date
function validateDates() {
    const startDate = new Date(document.getElementById('saleStartDate').value);
    const endDate = new Date(document.getElementById('saleEndDate').value);
    
    if (startDate && endDate && startDate >= endDate) {
        document.getElementById('saleEndDate').setCustomValidity('La data di fine deve essere successiva alla data di inizio');
    } else {
        document.getElementById('saleEndDate').setCustomValidity('');
    }
}

// Applicazione filtri
function applyFilters() {
    currentFilters = {
        search: document.getElementById('searchInput').value,
        status: document.getElementById('statusFilter').value,
        category: document.getElementById('categoryFilter').value,
        discount: document.getElementById('discountFilter').value
    };
    
    loadPromotions(1);
}

// Pulizia filtri
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('discountFilter').value = '';
    
    currentFilters = {};
    loadPromotions(1);
}

// Caricamento statistiche
function loadStatistics() {
    fetch('api/pharma-products/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('activePromotionsCount').textContent = data.active_promotions || 0;
                document.getElementById('upcomingPromotionsCount').textContent = data.upcoming_promotions || 0;
                document.getElementById('expiringPromotionsCount').textContent = data.expiring_promotions || 0;
                document.getElementById('averageDiscount').textContent = `${data.average_discount || 0}%`;
            }
        })
        .catch(error => console.error('Errore caricamento statistiche:', error));
}

// Modal promozione
function showAddPromotionModal() {
    document.getElementById('promotionModalLabel').innerHTML = '<i class="fas fa-plus me-1"></i> Nuova Promozione';
    document.getElementById('promotionForm').reset();
    document.getElementById('promotionId').value = '';
    document.getElementById('productInfo').style.display = 'none';
    document.getElementById('discountPercentage').textContent = '';
    
    const modal = new bootstrap.Modal(document.getElementById('promotionModal'));
    modal.show();
}

// Modifica promozione
function editPromotion(id) {
    fetch(`api/pharma-products/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const promotion = data.product;
                selectedPromotionId = id;
                
                document.getElementById('promotionModalLabel').innerHTML = '<i class="fas fa-edit me-1"></i> Modifica Promozione';
                document.getElementById('promotionId').value = promotion.id;
                document.getElementById('productSelect').value = promotion.product_id || '';
                document.getElementById('salePrice').value = promotion.sale_price || '';
                document.getElementById('saleStartDate').value = formatDate(promotion.sale_start_date);
                document.getElementById('saleEndDate').value = formatDate(promotion.sale_end_date);
                document.getElementById('isOnSale').checked = promotion.is_on_sale == 1;
                
                handleProductSelect();
                calculateDiscount();
                
                const modal = new bootstrap.Modal(document.getElementById('promotionModal'));
                modal.show();
            } else {
                showError('Errore nel caricamento della promozione');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showError('Errore di connessione');
        });
}

// Formattazione date
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 10);
}

// Gestione submit form
function handlePromotionSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const promotionId = formData.get('id');
    
    const url = promotionId ? 
        `api/pharma-products/update.php` : 
        `api/pharma-products/add.php`;
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(promotionId ? 'Promozione aggiornata con successo!' : 'Promozione creata con successo!');
            bootstrap.Modal.getInstance(document.getElementById('promotionModal')).hide();
            loadPromotions(currentPage);
            loadStatistics();
        } else {
            showError('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        showError('Errore di connessione');
    });
}

// Toggle promozione
function togglePromotion(id, currentStatus) {
    const newStatus = currentStatus == 1 ? 0 : 1;
    const action = newStatus == 1 ? 'attivare' : 'disattivare';
    
    if (confirm(`Sei sicuro di voler ${action} questa promozione?`)) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('is_on_sale', newStatus);
        
        fetch('api/pharma-products/update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(`Promozione ${newStatus == 1 ? 'attivata' : 'disattivata'} con successo!`);
                loadPromotions(currentPage);
                loadStatistics();
            } else {
                showError('Errore: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showError('Errore di connessione');
        });
    }
}

// Eliminazione promozione
function deletePromotion(id) {
    selectedPromotionId = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Conferma eliminazione
function confirmDelete() {
    if (!selectedPromotionId) return;
    
    fetch('api/pharma-products/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: selectedPromotionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Promozione eliminata con successo!');
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            loadPromotions(currentPage);
            loadStatistics();
        } else {
            showError('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        showError('Errore di connessione');
    });
}

// Export promozioni
function exportPromotions() {
    const params = new URLSearchParams(currentFilters);
    window.open(`api/pharma-products/export-promotions.php?${params}`, '_blank');
}

// Import promozioni
function showImportModal() {
    const modal = new bootstrap.Modal(document.getElementById('importModal'));
    modal.show();
}

// Gestione import
function handleImportSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    fetch('api/pharma-products/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(`Import completato! ${data.imported || 0} promozioni importate.`);
            bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
            loadPromotions(currentPage);
            loadStatistics();
        } else {
            showError('Errore import: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        showError('Errore di connessione');
    });
}

// Download template
function downloadTemplate() {
    window.open('api/pharma-products/template.php', '_blank');
}

// Paginazione
function updatePagination(totalPages, currentPage) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Pagina precedente
    if (currentPage > 1) {
        pagination.innerHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadPromotions(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
    // Pagine
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            pagination.innerHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadPromotions(${i})">${i}</a>
                </li>
            `;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            pagination.innerHTML += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            `;
        }
    }
    
    // Pagina successiva
    if (currentPage < totalPages) {
        pagination.innerHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadPromotions(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
}

// Utility functions
function showLoading() {
    const grid = document.getElementById('promotionsGrid');
    grid.innerHTML = `
        <div class="col-12">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Caricamento...</span>
                </div>
                <p class="mt-2">Caricamento promozioni...</p>
            </div>
        </div>
    `;
}

function hideLoading() {
    // Loading viene gestito dal displayPromotions
}

function showSuccess(message) {
    showAlert(message, 'success');
}

function showError(message) {
    showAlert(message, 'danger');
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('main');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
} 