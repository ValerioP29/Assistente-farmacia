<?php
/**
 * Dashboard Principale
 * Assistente Farmacia Panel
 */

// Carica configurazione e middleware PRIMA di qualsiasi output
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Controllo accesso: solo admin e farmacisti (DEVE essere prima di qualsiasi output)
requirePharmacistOrAdmin();

// Include header (che carica la configurazione)
require_once 'includes/header.php';

// Imposta variabili per il template
$page_title = 'Dashboard - ' . APP_NAME;
$page_description = 'Pannello di controllo principale';
$current_page = 'dashboard';

// Ottieni statistiche
$stats = getDashboardStats();
$chartData = getChartData(30);

// Ottieni farmacia corrente
$pharmacy = getCurrentPharmacy();
$isOpen = isPharmacyOpen();
$nextOpening = getNextOpeningTime();
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <!-- Logo a sinistra -->
        <div class="text-light fw-bold">
            <i class="fas fa-pills"></i> <?= APP_NAME ?>
        </div>
        
        <!-- Toggler offcanvas (mobile) -->
        <button class="offcanvas-toggler d-lg-none ms-auto" 
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebarOffcanvas"
                aria-controls="sidebarOffcanvas">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Titolo centrale -->
        <div class="navbar-center mx-auto text-light text-center">
            <?= htmlspecialchars($pharmacy['nice_name'] ?? 'Farmacia') ?>
            <?php if ($isOpen): ?>
                <span class="badge bg-success ms-2">
                    <i class="fas fa-circle"></i> Aperta
                </span>
            <?php else: ?>
                <span class="badge bg-danger ms-2">
                    <i class="fas fa-circle"></i> Chiusa
                    <?php if ($nextOpening): ?>
                        <small class="d-block">Apre: <?= $nextOpening ?></small>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Logout rimosso - ora solo nel sidebar -->
        <div class="d-none d-lg-block ms-auto">
            <!-- Pulsante logout rimosso -->
        </div>
    </div>
</nav>

<!-- Offcanvas per mobile -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include 'includes/sidebar.php'; ?>
    </div>
</div>

<!-- Layout generale -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar desktop -->
        <aside class="d-none d-lg-block sidebar-left">
            <?php include 'includes/sidebar.php'; ?>
        </aside>

        <!-- Main content -->
        <main class="col-12 col-lg-9 p-3">
            <!-- Header Dashboard -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </h1>
                    <p class="text-muted mb-0">
                        Benvenuto, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Utente') ?>!
                    </p>
                </div>
                <div class="text-end">
                    <small class="text-muted">
                        <i class="fas fa-clock"></i> 
                        Ultimo aggiornamento: <?= date('d/m/Y H:i') ?>
                    </small>
                </div>
            </div>

            <!-- Statistiche Cards -->
            <div id="card_container" class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users mb-2 text-primary"></i>
                        <h5 class="card-title mt-2"><?= number_format($stats['customers']) ?></h5>
                        <p class="card-text">Numero di clienti</p>
                    </div>
                </div>

                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock mb-2 text-warning"></i>
                        <h5 class="card-title mt-2"><?= number_format($stats['pending_requests']) ?></h5>
                        <p class="card-text">Richieste in corso</p>
                    </div>
                </div>

                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle mb-2 text-success"></i>
                        <h5 class="card-title mt-2"><?= number_format($stats['completed_requests']) ?></h5>
                        <p class="card-text">Richieste completate</p>
                    </div>
                </div>

                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-pills mb-2 text-info"></i>
                        <h5 class="card-title mt-2"><?= number_format($stats['products']) ?></h5>
                        <p class="card-text">Prodotti in catalogo</p>
                    </div>
                </div>
            </div>

            <!-- Grafico Prenotazioni -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i> Prenotazioni Ultimi 30 Giorni
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="myChart" height="100"></canvas>
                </div>
            </div>

            <!-- Informazioni Rapide -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Informazioni Farmacia
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <strong>Nome:</strong> <?= htmlspecialchars($pharmacy['nice_name'] ?? 'N/A') ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Indirizzo:</strong> <?= htmlspecialchars($pharmacy['address'] ?? 'N/A') ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Città:</strong> <?= htmlspecialchars($pharmacy['city'] ?? 'N/A') ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Telefono:</strong> 
                                    <?php if ($pharmacy['phone_number']): ?>
                                        <a href="tel:<?= $pharmacy['phone_number'] ?>">
                                            <?= htmlspecialchars($pharmacy['phone_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Email:</strong> 
                                    <?php if ($pharmacy['email']): ?>
                                        <a href="mailto:<?= $pharmacy['email'] ?>">
                                            <?= htmlspecialchars($pharmacy['email']) ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-clock"></i> Orari di Apertura
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $hours = getPharmacyHours();
                            $formattedHours = formatWorkingHours($hours);
                            ?>
                            <?php if ($formattedHours): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($formattedHours as $day => $time): ?>
                                        <li class="mb-1 d-flex justify-content-between">
                                            <span><?= $day ?>:</span>
                                            <strong><?= $time ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">Orari non disponibili</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- JavaScript per il grafico -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dati del grafico
    const chartData = <?= json_encode($chartData) ?>;
    
    // Configurazione grafico
    const ctx = document.getElementById('myChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Prenotazioni',
                data: chartData.values,
                borderColor: 'rgb(52, 152, 219)',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgb(52, 152, 219)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgb(52, 152, 219)',
                    borderWidth: 1,
                    cornerRadius: 6,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Aggiorna grafico ogni 5 minuti
    setInterval(function() {
        fetch('api/dashboard/chart-data.php')
            .then(response => response.json())
            .then(data => {
                myChart.data.labels = data.labels;
                myChart.data.datasets[0].data = data.values;
                myChart.update('none');
            })
            .catch(error => {
                console.error('Errore aggiornamento grafico:', error);
            });
    }, 300000); // 5 minuti

    // Aggiorna statistiche ogni minuto
    setInterval(function() {
        fetch('api/dashboard/stats.php')
            .then(response => response.json())
            .then(data => {
                // Aggiorna i numeri nelle card
                document.querySelectorAll('#card_container .card-title').forEach((title, index) => {
                    const values = [data.customers, data.pending_requests, data.completed_requests, data.products];
                    if (values[index] !== undefined) {
                        title.textContent = new Intl.NumberFormat('it-IT').format(values[index]);
                    }
                });
            })
            .catch(error => {
                console.error('Errore aggiornamento statistiche:', error);
            });
    }, 60000); // 1 minuto
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 