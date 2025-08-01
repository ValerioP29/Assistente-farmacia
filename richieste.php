<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Verifica autenticazione
requireLogin();

$pageTitle = "Gestione Richieste";
$current_page = "richieste";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-clipboard-list"></i> Gestione Richieste
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> Aggiorna
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filtri -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="statusFilter" class="form-label">Stato</label>
                                    <select class="form-select" id="statusFilter">
                                        <option value="">Tutti gli stati</option>
                                        <option value="0">In attesa</option>
                                        <option value="1">In lavorazione</option>
                                        <option value="2">Completata</option>
                                        <option value="3">Rifiutata</option>
                                        <option value="4">Annullata</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="typeFilter" class="form-label">Tipo Richiesta</label>
                                    <select class="form-select" id="typeFilter">
                                        <option value="">Tutti i tipi</option>
                                        <option value="event">Evento</option>
                                        <option value="service">Servizio</option>
                                        <option value="promos">Promozione</option>
                                        <option value="reservation">Prenotazione</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="searchFilter" class="form-label">Ricerca</label>
                                    <input type="text" class="form-control" id="searchFilter" placeholder="Cerca nel messaggio o farmacia...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary flex-fill" id="applyFilters">
                                            <i class="fas fa-search"></i> Filtra
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="resetFilters" title="Reset filtri">
                                            <i class="fas fa-times"></i>
                                            <span class="badge bg-secondary ms-1" id="filterCount" style="display: none;">0</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiche -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning" id="pendingCount">0</h5>
                            <p class="card-text">In Attesa</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info" id="processingCount">0</h5>
                            <p class="card-text">In Lavorazione</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success" id="completedCount">0</h5>
                            <p class="card-text">Completate</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger" id="rejectedCount">0</h5>
                            <p class="card-text">Rifiutate</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-secondary" id="cancelledCount">0</h5>
                            <p class="card-text">Annullate</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary" id="totalCount">0</h5>
                            <p class="card-text">Totale</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabella Richieste -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="requestsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Utente</th>
                                    <th>Messaggio</th>
                                    <th>Stato</th>
                                    <th>Data</th>
                                    <th style="width: 160px;">Azioni</th>
                                </tr>
                            </thead>
                            <tbody id="requestsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Caricamento...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginazione -->
                    <nav aria-label="Paginazione richieste">
                        <ul class="pagination justify-content-center" id="pagination">
                        </ul>
                    </nav>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Dettagli Richiesta -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestDetailsModalLabel">Dettagli Richiesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="requestDetailsContent">
                <!-- Contenuto caricato dinamicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aggiorna Stato -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Aggiorna Stato Richiesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <input type="hidden" id="updateRequestId">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Nuovo Stato</label>
                        <select class="form-select" id="newStatus" required>
                            <option value="0">In attesa</option>
                            <option value="1">In lavorazione</option>
                            <option value="2">Completata</option>
                            <option value="3">Rifiutata</option>
                            <option value="4">Annullata</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusNote" class="form-label">Nota (opzionale)</label>
                        <textarea class="form-control" id="statusNote" rows="3" placeholder="Aggiungi una nota per questo cambio di stato..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="saveStatusBtn">Salva</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Elimina Richiesta -->
<div class="modal fade" id="deleteRequestModal" tabindex="-1" aria-labelledby="deleteRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRequestModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Elimina Richiesta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attenzione!</strong> Stai per eliminare una richiesta. Questa azione non può essere annullata.
                </div>
                <form id="deleteRequestForm">
                    <input type="hidden" id="deleteRequestId">
                    <div class="mb-3">
                        <label for="deleteReason" class="form-label">Motivo dell'eliminazione (opzionale)</label>
                        <textarea class="form-control" id="deleteReason" rows="3" placeholder="Specifica il motivo dell'eliminazione..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>
                    Elimina
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Invia WhatsApp -->
<div class="modal fade" id="whatsappModal" tabindex="-1" aria-labelledby="whatsappModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="whatsappModalLabel">
                    <i class="fab fa-whatsapp text-success me-2"></i>
                    Invia Messaggio WhatsApp
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="whatsappForm">
                    <input type="hidden" id="whatsappRequestId">
                    <div class="mb-3">
                        <label for="whatsappPhone" class="form-label">Numero di Telefono</label>
                        <div class="input-group">
                            <span class="input-group-text">+39</span>
                            <input type="text" class="form-control" id="whatsappPhone" placeholder="320 283 8555" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="whatsappMessage" class="form-label">Messaggio</label>
                        <textarea class="form-control" id="whatsappMessage" rows="6" placeholder="Scrivi il tuo messaggio qui..." required></textarea>
                        <div class="form-text">
                            <span id="messageLength">0</span> caratteri
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" id="sendWhatsAppBtn">
                    <i class="fab fa-whatsapp me-1"></i>
                    Invia
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<link rel="stylesheet" href="assets/css/richieste.css">
<script src="assets/js/richieste.js"></script> 