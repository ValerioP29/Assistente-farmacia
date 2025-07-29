<?php
/**
 * Sidebar di Navigazione
 * Assistente Farmacia Panel
 */

$current_page = $current_page ?? 'dashboard';
$user_role = $_SESSION['user_role'] ?? 'user';

$menu_items = [
    'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['admin', 'pharmacist']],
    'utenti' => ['icon' => 'fas fa-users', 'label' => 'Gestione Utenti', 'url' => 'utenti.php', 'roles' => ['admin']],
    'farmacie' => ['icon' => 'fas fa-clinic-medical', 'label' => 'Gestione Farmacie', 'url' => 'farmacie.php', 'roles' => ['admin']],
    'whatsapp' => ['icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp', 'url' => 'whatsapp.php', 'roles' => ['pharmacist']],
    'clienti' => ['icon' => 'fas fa-user-friends', 'label' => 'Clienti', 'url' => 'clienti.php', 'roles' => ['pharmacist']],
    'profilo' => ['icon' => 'fas fa-user-cog', 'label' => 'Profilo', 'url' => 'profilo.php', 'roles' => ['pharmacist']],
    'orari' => ['icon' => 'fas fa-clock', 'label' => 'Modifica Orari', 'url' => 'orari.php', 'roles' => ['pharmacist']],
    'prodotti' => ['icon' => 'fas fa-pills', 'label' => 'Promo', 'url' => 'prodotti.php', 'roles' => ['pharmacist']],
    'richieste' => ['icon' => 'fas fa-calendar-check', 'label' => 'Richieste', 'url' => 'richieste.php', 'roles' => ['pharmacist']],
];

// Filtra menu items in base al ruolo
$visible_menu_items = array_filter($menu_items, function($item) use ($user_role) {
    return in_array($user_role, $item['roles']);
});
?>

<div class="sidebar-left">
    <ul class="nav nav-pills flex-column mb-auto">
        <?php foreach ($visible_menu_items as $page => $item): ?>
            <li>
                <a href="<?= $item['url'] ?>" class="nav-link <?= $current_page === $page ? 'selected' : '' ?>">
                    <i class="<?= $item['icon'] ?> me-2"></i>
                    <?= $item['label'] ?>
                </a>
            </li>
        <?php endforeach; ?>
        <li><hr class="border-light"></li>
        <li><a href="#" class="nav-link exit logout-link"><i class="fas fa-sign-out-alt me-2"></i> Esci</a></li>
    </ul>
</div>

<style>
.sidebar-left {
    background: var(--primary-color, #2c3e50);
    min-height: calc(100vh - 60px);
    padding: 1rem 0;
    position: sticky;
    top: 60px;
    overflow-y: auto;
}

.sidebar-left .nav-link {
    color: rgba(255, 255, 255, 0.8);
    padding: 1rem 1.5rem;
    border-radius: 0;
    transition: all 0.3s ease;
    font-weight: 500;
    border-left: 3px solid transparent;
}

.sidebar-left .nav-link:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
    border-left-color: rgba(255, 255, 255, 0.3);
}

.sidebar-left .nav-link.selected {
    color: white;
    background-color: var(--accent-color, #3498db);
    border-left-color: white;
    font-weight: 600;
}

.sidebar-left .nav-link.exit {
    color: white;
    margin-top: 1rem;
}

.sidebar-left .nav-link.exit:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-left-color: white;
}

.sidebar-left .nav-link i {
    width: 20px;
    text-align: center;
}

/* Responsive improvements */
@media (min-width: 1200px) {
    .sidebar-left {
        width: 250px;
    }
}

@media (min-width: 992px) and (max-width: 1199px) {
    .sidebar-left {
        width: 200px;
    }
    
    .sidebar-left .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
}

/* Scrollbar personalizzata per sidebar */
.sidebar-left::-webkit-scrollbar {
    width: 6px;
}

.sidebar-left::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar-left::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.sidebar-left::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style> 