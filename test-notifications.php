<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Verifica autenticazione
requireLogin();

$pageTitle = "Test Notifiche";
$current_page = "test";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-bell"></i> Test Sistema Notifiche
                </h1>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-cog"></i> Controlli Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Stato Notifiche Browser:</label>
                                <div id="notificationStatus" class="alert alert-info">
                                    <i class="fas fa-spinner fa-spin"></i> Verificando...
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ultimo ID Richiesta Vista:</label>
                                <div id="lastSeenId" class="form-control-plaintext">-</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Controllo API:</label>
                                <div id="apiStatus" class="alert alert-info">
                                    <i class="fas fa-spinner fa-spin"></i> Verificando...
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Audio:</label>
                                <div id="audioStatus" class="alert alert-info">
                                    <i class="fas fa-spinner fa-spin"></i> Verificando...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-play"></i> Test Manuali</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" onclick="requestNotificationPermissions()">
                                    <i class="fas fa-key"></i> Richiedi Permessi Notifiche
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" onclick="testNotification()">
                                    <i class="fas fa-bell"></i> Test Notifica Desktop
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-success" onclick="testSound()">
                                    <i class="fas fa-volume-up"></i> Test Suono
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-info" onclick="testInAppNotification()">
                                    <i class="fas fa-comment"></i> Test Notifica In-App
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-warning" onclick="testBadge()">
                                    <i class="fas fa-tag"></i> Test Badge
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-secondary" onclick="resetLastSeen()">
                                    <i class="fas fa-undo"></i> Reset Ultimo ID
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> Log Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div id="systemLog" class="bg-light p-3" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                Inizializzazione sistema notifiche...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Funzioni di test
function log(message) {
    const logDiv = document.getElementById('systemLog');
    const timestamp = new Date().toLocaleTimeString();
    logDiv.innerHTML += `[${timestamp}] ${message}\n`;
    logDiv.scrollTop = logDiv.scrollHeight;
}

function requestNotificationPermissions() {
    if (window.notificationSystem) {
        window.notificationSystem.requestPermissions();
        log('Richiesta permessi notifiche inviata');
    } else {
        log('ERRORE: Sistema notifiche non disponibile');
    }
}

function testNotification() {
    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification('Test Notifica', {
            body: 'Questa è una notifica di test del sistema',
            icon: 'images/farmacia_icon.png'
        });
        log('Notifica desktop inviata');
    } else {
        log('ERRORE: Permessi notifiche non concessi');
    }
}

function testSound() {
    if (window.notificationSystem && window.notificationSystem.audio) {
        window.notificationSystem.playNotificationSound();
        log('Suono di notifica riprodotto');
    } else {
        log('ERRORE: Audio non disponibile');
    }
}

function testInAppNotification() {
    if (window.notificationSystem) {
        const testData = {
            new_requests: [
                {
                    customer_name: 'Mario Rossi',
                    product_name: 'Paracetamolo'
                },
                {
                    customer_name: 'Giulia Bianchi',
                    product_name: 'Ibuprofene'
                }
            ]
        };
        window.notificationSystem.showInAppNotification(testData.new_requests);
        log('Notifica in-app mostrata');
    } else {
        log('ERRORE: Sistema notifiche non disponibile');
    }
}

function testBadge() {
    if (window.notificationSystem) {
        window.notificationSystem.updateBadge(5);
        log('Badge aggiornato con valore 5');
    } else {
        log('ERRORE: Sistema notifiche non disponibile');
    }
}

function resetLastSeen() {
    if (window.notificationSystem) {
        window.notificationSystem.updateLastSeenId(0);
        log('Ultimo ID resettato a 0');
        updateLastSeenDisplay();
    } else {
        log('ERRORE: Sistema notifiche non disponibile');
    }
}

function updateLastSeenDisplay() {
    const lastSeen = window.notificationSystem ? window.notificationSystem.lastSeenId : 0;
    document.getElementById('lastSeenId').textContent = lastSeen;
}

// Verifica stato sistema
document.addEventListener('DOMContentLoaded', function() {
    log('Pagina caricata, verificando sistema...');
    
    // Verifica notifiche browser
    setTimeout(() => {
        if ('Notification' in window) {
            const status = Notification.permission;
            const statusDiv = document.getElementById('notificationStatus');
            
            if (status === 'granted') {
                statusDiv.className = 'alert alert-success';
                statusDiv.innerHTML = '<i class="fas fa-check"></i> Abilitate';
                log('Notifiche browser: ABILITATE');
            } else if (status === 'denied') {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = '<i class="fas fa-times"></i> Negate';
                log('Notifiche browser: NEGATE');
            } else {
                statusDiv.className = 'alert alert-warning';
                statusDiv.innerHTML = '<i class="fas fa-question"></i> Non richieste';
                log('Notifiche browser: NON RICHIESTE');
            }
        } else {
            document.getElementById('notificationStatus').className = 'alert alert-danger';
            document.getElementById('notificationStatus').innerHTML = '<i class="fas fa-times"></i> Non supportate';
            log('Notifiche browser: NON SUPPORTATE');
        }
    }, 1000);
    
    // Verifica API
    setTimeout(() => {
        fetch('api/notifications/check-new.php?last_seen=0')
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('apiStatus');
                if (data.success) {
                    statusDiv.className = 'alert alert-success';
                    statusDiv.innerHTML = '<i class="fas fa-check"></i> Funzionante';
                    log('API notifiche: FUNZIONANTE');
                } else {
                    statusDiv.className = 'alert alert-danger';
                    statusDiv.innerHTML = '<i class="fas fa-times"></i> Errore: ' + (data.error || 'Sconosciuto');
                    log('API notifiche: ERRORE - ' + (data.error || 'Sconosciuto'));
                }
            })
            .catch(error => {
                document.getElementById('apiStatus').className = 'alert alert-danger';
                document.getElementById('apiStatus').innerHTML = '<i class="fas fa-times"></i> Errore di rete';
                log('API notifiche: ERRORE DI RETE - ' + error.message);
            });
    }, 2000);
    
    // Verifica audio
    setTimeout(() => {
        const audioStatus = document.getElementById('audioStatus');
        if (window.notificationSystem && window.notificationSystem.audio) {
            audioStatus.className = 'alert alert-success';
            audioStatus.innerHTML = '<i class="fas fa-check"></i> Disponibile';
            log('Audio: DISPONIBILE');
        } else {
            audioStatus.className = 'alert alert-warning';
            audioStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Non disponibile';
            log('Audio: NON DISPONIBILE');
        }
        
        // Aggiorna display ultimo ID
        updateLastSeenDisplay();
    }, 3000);
    
    // Log quando il sistema di notifiche è pronto
    setTimeout(() => {
        if (window.notificationSystem) {
            log('Sistema notifiche: INIZIALIZZATO');
        } else {
            log('ERRORE: Sistema notifiche non inizializzato');
        }
    }, 4000);
});
</script> 