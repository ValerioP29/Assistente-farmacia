<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../services/WhatsappService.php';

header('Content-Type: application/json');
requireApiAuth(['admin', 'pharmacist']);

$pharmaId = get_panel_pharma_id(true);

function respondWhatsappLink($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

$message = $_GET['message'] ?? ($_POST['message'] ?? '');

try {
    $link = WhatsappService::linkForPharma($pharmaId, $message);
    respondWhatsappLink(true, ['link' => $link]);
} catch (Exception $e) {
    respondWhatsappLink(false, null, $e->getMessage(), 400);
}
