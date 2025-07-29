<?php
/**
 * Index - Reindirizzamento
 * Assistente Farmacia Panel
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
session_start();

if (isLoggedIn()) {
    $user_role = $_SESSION['user_role'] ?? 'user';
    
    // Reindirizzamento in base al ruolo
    switch ($user_role) {
        case 'admin':
            header('Location: utenti.php');
            break;
        case 'pharmacist':
        case 'user':
        default:
            header('Location: dashboard.php');
            break;
    }
} else {
    header('Location: login.php');
}
exit;
?> 