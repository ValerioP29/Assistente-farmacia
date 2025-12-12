<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth_middleware.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Non autorizzato']);
    exit;
}

$pharmacy_id = get_panel_pharma_id(true);

try {
    // Usa la classe Database esistente
    $db = db();

    // Ottieni l'ID dell'ultima richiesta vista dall'utente
    $last_seen = isset($_GET['last_seen']) ? (int)$_GET['last_seen'] : 0;

    // Controlla se ci sono nuove richieste usando la stessa struttura delle altre API
    $result = $db->fetchOne("
        SELECT COUNT(*) as new_count, MAX(id) as latest_id
        FROM jta_requests
        WHERE id > ? AND deleted_at IS NULL AND pharma_id = ?
    ", [$last_seen, $pharmacy_id]);

    $new_count = (int)$result['new_count'];
    $latest_id = (int)$result['latest_id'];

    // Se ci sono nuove richieste, ottieni i dettagli
    $new_requests = [];
    if ($new_count > 0) {
        $requests = $db->fetchAll("
            SELECT r.id, r.request_type, r.status, r.created_at, r.message,
                   CONCAT(u.name, ' ', u.surname) as customer_name,
                   u.phone_number as customer_phone,
                   p.business_name as pharmacy_name
            FROM jta_requests r
            LEFT JOIN jta_users u ON r.user_id = u.id
            LEFT JOIN jta_pharmas p ON r.pharma_id = p.id
            WHERE r.id > ? AND r.deleted_at IS NULL AND r.pharma_id = ?
            ORDER BY r.created_at DESC
            LIMIT 10
        ", [$last_seen, $pharmacy_id]);

        // Formatta le richieste come nelle altre API
        foreach ($requests as $request) {
            $new_requests[] = [
                'id' => $request['id'],
                'customer_name' => $request['customer_name'],
                'customer_phone' => $request['customer_phone'],
                'status' => $request['status'],
                'created_at' => $request['created_at'],
                'product_name' => $request['request_type'], // Usa il tipo di richiesta come "prodotto"
                'pharmacy_name' => $request['pharmacy_name']
            ];
        }
    }

    // Recupera promemoria in scadenza per la farmacia
    $due_reminders = $db->fetchAll("
        SELECT r.id, r.title, r.message, r.scheduled_at, r.therapy_id, t.therapy_title,
               p.first_name, p.last_name
        FROM jta_therapy_reminders r
        JOIN jta_therapies t ON r.therapy_id = t.id
        LEFT JOIN jta_patients p ON t.patient_id = p.id
        WHERE r.status = 'scheduled' AND r.scheduled_at <= NOW() AND t.pharmacy_id = ?
        ORDER BY r.scheduled_at ASC
        LIMIT 20
    ", [$pharmacy_id]);

    $reminders = [];
    if (!empty($due_reminders)) {
        foreach ($due_reminders as $reminder) {
            $patient_name = trim(($reminder['first_name'] ?? '') . ' ' . ($reminder['last_name'] ?? ''));
            $reminders[] = [
                'id' => $reminder['id'],
                'title' => $reminder['title'],
                'message' => $reminder['message'],
                'scheduled_at' => $reminder['scheduled_at'],
                'therapy_id' => $reminder['therapy_id'],
                'therapy_title' => $reminder['therapy_title'],
                'patient_name' => $patient_name,
            ];
        }

        // Marca come inviati per evitare duplicati
        $ids = array_column($due_reminders, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        db_query("UPDATE jta_therapy_reminders SET status = 'sent' WHERE id IN ($placeholders)", $ids);
    }

    echo json_encode([
        'success' => true,
        'new_count' => $new_count,
        'latest_id' => $latest_id,
        'new_requests' => $new_requests,
        'reminders_count' => count($reminders),
        'reminders' => $reminders,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Errore: ' . $e->getMessage()]);
}