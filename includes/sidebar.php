<?php
/**
 * Sidebar di Navigazione
 * Assistente Farmacia Panel
 */

$current_page = $current_page ?? 'dashboard';
$user_role = $_SESSION['user_role'] ?? 'user';
$is_login_as = isset($_SESSION['login_as']) && $_SESSION['login_as'];

$menu_items = [
    'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'roles' => ['admin', 'pharmacist']],
    'utenti' => ['icon' => 'fas fa-users', 'label' => 'Gestione Utenti', 'url' => 'utenti.php', 'roles' => ['admin']],
    'farmacie' => ['icon' => 'fas fa-clinic-medical', 'label' => 'Gestione Farmacie', 'url' => 'farmacie.php', 'roles' => ['admin']],
    'prodotti_globali' => ['icon' => 'fas fa-boxes', 'label' => 'Gestione Prodotti', 'url' => 'prodotti_globali.php', 'roles' => ['admin']],
    'richieste' => ['icon' => 'fas fa-calendar-check', 'label' => 'Richieste', 'url' => 'richieste.php', 'roles' => ['pharmacist']],
    'prodotti' => ['icon' => 'fas fa-boxes', 'label' => 'Gestione Prodotti', 'url' => 'prodotti.php', 'roles' => ['pharmacist']],
    'promo' => ['icon' => 'fas fa-pills', 'label' => 'Promo', 'url' => 'promo.php', 'roles' => ['pharmacist']],
    'orari' => ['icon' => 'fas fa-clock', 'label' => 'Modifica Orari', 'url' => 'orari.php', 'roles' => ['pharmacist']],
    'whatsapp' => ['icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp', 'url' => 'whatsapp.php', 'roles' => ['pharmacist']],
    'profilo' => ['icon' => 'fas fa-user-cog', 'label' => 'Profilo', 'url' => 'profilo.php', 'roles' => ['pharmacist']],
];

// Filtra menu items in base al ruolo
$visible_menu_items = array_filter($menu_items, function($item) use ($user_role) {
    return in_array($user_role, $item['roles']);
});
?>


<div class="sidebar-left">
    <?php if ($is_login_as): ?>
        <!-- Indicatore modalità accesso come -->
        <div class="login-as-indicator">
            <div class="alert alert-info mb-3 mx-2">
                <i class="fas fa-user-secret me-2"></i>
                <strong>Accesso come Farmacia</strong>
                <br><small>Stai operando come: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Farmacia') ?></small>
            </div>
        </div>
    <?php endif; ?>
    
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
        
        <?php if ($is_login_as): ?>
            <!-- Pulsante per tornare admin -->
            <li>
                <a href="#" class="nav-link return-admin-link" onclick="returnToAdmin()">
                    <i class="fas fa-arrow-left me-2"></i> Torna Admin
                </a>
            </li>
            <li><hr class="border-light"></li>
        <?php endif; ?>
        
        <li><a href="logout.php" class="nav-link exit logout-link"><i class="fas fa-sign-out-alt me-2"></i> Esci</a></li>
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

.sidebar-left .nav-link.selected,
.sidebar-left .nav-link.active {
    color: white;
    background-color: var(--accent-color, #2c3e50);
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

/* Stili per l'indicatore di accesso come */
.login-as-indicator .alert {
    border: none;
    background: rgba(52, 152, 219, 0.2);
    color: white;
    font-size: 0.85rem;
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.login-as-indicator .alert strong {
    color: white;
    font-weight: 600;
}

.login-as-indicator .alert small {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.8rem;
}

/* Stili per il pulsante torna admin */
.sidebar-left .nav-link.return-admin-link {
    color: #f39c12;
    background-color: rgba(243, 156, 18, 0.1);
    border-left-color: #f39c12;
    font-weight: 600;
}

.sidebar-left .nav-link.return-admin-link:hover {
    color: #e67e22;
    background-color: rgba(243, 156, 18, 0.2);
    border-left-color: #e67e22;
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

 