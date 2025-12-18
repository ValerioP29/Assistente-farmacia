<?php
/**
 * API - Torna Admin
 * Assistente Farmacia Panel
 */

// Avvia sessione
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';
header('Content-Type: application/json');

// Controllo metodo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Verifica CSRF
$csrf_token = $_POST['csrf_token'] ?? '';

// Se non c'è un token CSRF nella sessione, ne genera uno nuovo
if (!isset($_SESSION['csrf_token'])) {
    generateCSRFToken();
}

if (!verifyCSRFToken($csrf_token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token di sicurezza non valido']);
    exit;
}

try {
    // Deve essere in modalità "login as"
    if (empty($_SESSION['login_as'])) {
        echo json_encode(['success' => false, 'message' => 'Non sei in modalità accesso come utente']);
        exit;
    }

    // Recupera i dati dell'admin originale
    $admin_id = $_SESSION['original_admin_id'] ?? null;
    $admin_name = $_SESSION['original_admin_name'] ?? null;

    if (!$admin_id || !$admin_name) {
        echo json_encode(['success' => false, 'message' => 'Dati admin originali non trovati']);
        exit;
    }

    // Verifica che l'admin esista ancora
    $admin = db_fetch_one("SELECT * FROM jta_users WHERE id = ? AND role = 'admin' AND status != 'deleted'", [$admin_id]);
    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Account admin non trovato']);
        exit;
    }

    // Ripristina la sessione dell'admin
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_role'] = $admin['role'];
    $_SESSION['user_name'] = $admin['slug_name'];

    unset($_SESSION['pharmacy_id']);

    // Rimuovi i flag di accesso "come"
    unset($_SESSION['login_as']);
    unset($_SESSION['original_admin_id']);
    unset($_SESSION['original_admin_name']);

    // Log attività
    logActivity('return_to_admin', [
        'admin_id' => $admin_id
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Ritorno all\'account admin effettuato',
        'admin_name' => $admin['name'] . ' ' . $admin['surname']
    ]);

} catch (Exception $e) {
    error_log("Errore ritorno admin: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
}
?> 