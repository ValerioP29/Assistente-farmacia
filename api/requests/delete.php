<?php
/**
 * API - Elimina Richiesta (Soft Delete)
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth_middleware.php';

// Verifica autenticazione
requireApiAuth(['admin', 'pharmacist']);

$userRole = $_SESSION['user_role'] ?? null;

if ($userRole === 'pharmacist') {
    $pharmacyId = current_pharmacy_id();
} else {
    $pharmacyId = null;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['request_id'])) {
        throw new Exception('ID richiesta mancante');
    }
    
    $requestId = (int)$input['request_id'];
    $reason = $input['reason'] ?? 'Eliminata dall\'amministratore';
    $now = date('Y-m-d H:i:s');
    
    $db = Database::getInstance();

    // Recupera la richiesta (anche se già eliminata) per gestire idempotenza e vincoli farmacia
    $baseConditions = "id = ?";
    $params = [$requestId];

    if ($pharmacyId !== null) {
        $baseConditions .= " AND pharma_id = ?";
        $params[] = $pharmacyId;
    }

    $request = $db->fetchOne("SELECT * FROM jta_requests WHERE {$baseConditions}", $params);
    
    if (!$request) {
        throw new Exception('Richiesta non trovata');
    }

    if (!empty($request['deleted_at'])) {
        throw new Exception('Richiesta già eliminata');
    }

    // Aggiungi nota di eliminazione ai metadata
    $metadata = json_decode($request['metadata'], true) ?: [];
    if (!isset($metadata['notes']) || !is_array($metadata['notes'])) {
        $metadata['notes'] = [];
    }
    $metadata['notes'][] = [
        'text' => "Richiesta eliminata: {$reason}",
        'status' => 'deleted',
        'updated_by' => $_SESSION['user_id'] ?? 'admin',
        'updated_at' => $now
    ];

    $updateData = [
        'deleted_at' => $now,
        'metadata' => json_encode($metadata)
    ];

    // Aggiorna con controllo deleted_at IS NULL per garantire idempotenza
    $updateWhere = "id = ? AND deleted_at IS NULL";
    $updateParams = [$requestId];

    if ($pharmacyId !== null) {
        $updateWhere = "id = ? AND pharma_id = ? AND deleted_at IS NULL";
        $updateParams[] = $pharmacyId;
    }

    if ($pharmacyId !== null) {
        $updated = $db->update('jta_requests', $updateData, $updateWhere, $updateParams);
    } else {
        $updated = $db->update('jta_requests', $updateData, $updateWhere, $updateParams);
    }

    if ($updated <= 0) {
        throw new Exception('Errore durante l\'eliminazione della richiesta: nessuna riga aggiornata');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Richiesta eliminata con successo',
        'data' => [
            'request_id' => $requestId,
            'deleted_at' => $now
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getCode() === 404 ? 404 : 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 
