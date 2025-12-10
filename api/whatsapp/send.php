<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../services/WhatsappService.php';

header('Content-Type: application/json');
requireApiAuth(['admin', 'pharmacist']);

$pharmaId = get_panel_pharma_id(true);

function respondWhatsapp($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST ?? [];
}

$rawPhone = trim($input['phone'] ?? '');
$message = trim($input['message'] ?? '');

if ($message === '') {
    respondWhatsapp(false, null, 'Messaggio mancante', 400);
}

$normalizedPhone = preg_replace('/\D+/', '', $rawPhone);
if ($normalizedPhone === '') {
    respondWhatsapp(false, null, 'Numero destinatario non valido', 400);
}

if (isset($_SESSION['last_whatsapp_send']) && (time() - $_SESSION['last_whatsapp_send'] < 2)) {
    respondWhatsapp(false, null, 'Richiesta troppo frequente, riprova tra pochi secondi', 429);
}

$endpoint = WhatsappService::service('send');
$payload = json_encode([
    'phone' => $normalizedPhone,
    'message' => $message,
]);

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    respondWhatsapp(false, null, 'Errore di connessione al servizio WhatsApp');
}

$decoded = json_decode($response, true);
$success = is_array($decoded) ? ($decoded['success'] ?? $decoded['status'] ?? false) : false;
$data = is_array($decoded) ? ($decoded['data'] ?? $decoded) : $response;
$errorMessage = is_array($decoded) ? ($decoded['error'] ?? $decoded['message'] ?? null) : null;

if ($success) {
    $_SESSION['last_whatsapp_send'] = time();
    respondWhatsapp(true, $data);
}

respondWhatsapp(false, null, $errorMessage ?: 'Errore imprevisto nel servizio WhatsApp', 502);
