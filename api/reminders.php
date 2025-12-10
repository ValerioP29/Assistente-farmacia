<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');
requireApiAuth(['admin', 'pharmacist']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function respondReminders($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

$pharmacy_id = get_panel_pharma_id(true);

switch ($method) {
    case 'GET':
        $therapy_id = $_GET['therapy_id'] ?? null;
        if (!$therapy_id) {
            respondReminders(false, null, 'therapy_id richiesto', 400);
        }
        $sql = "SELECT r.* FROM jta_therapy_reminders r JOIN jta_therapies t ON r.therapy_id = t.id WHERE r.therapy_id = ? AND t.pharmacy_id = ? ORDER BY r.scheduled_at DESC";
        try {
            $rows = db_fetch_all($sql, [$therapy_id, $pharmacy_id]);
            respondReminders(true, ['items' => $rows]);
        } catch (Exception $e) {
            respondReminders(false, null, 'Errore recupero promemoria', 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $therapy_id = $input['therapy_id'] ?? null;
        if (!$therapy_id || empty($input['title']) || empty($input['message']) || empty($input['scheduled_at'])) {
            respondReminders(false, null, 'Campi obbligatori mancanti', 400);
        }
        $therapy = db_fetch_one("SELECT id FROM jta_therapies WHERE id = ? AND pharmacy_id = ?", [$therapy_id, $pharmacy_id]);
        if (!$therapy) {
            respondReminders(false, null, 'Terapia non trovata per la farmacia', 400);
        }
        try {
            db_query(
                "INSERT INTO jta_therapy_reminders (therapy_id, title, message, type, scheduled_at, channel, status) VALUES (?,?,?,?,?,?,?)",
                [
                    $therapy_id,
                    sanitize($input['title']),
                    sanitize($input['message']),
                    $input['type'] ?? 'one-shot',
                    $input['scheduled_at'],
                    $input['channel'] ?? 'email',
                    $input['status'] ?? 'scheduled'
                ]
            );
            $reminder_id = db()->getConnection()->lastInsertId();
            respondReminders(true, ['reminder_id' => $reminder_id]);
        } catch (Exception $e) {
            respondReminders(false, null, 'Errore creazione promemoria', 500);
        }
        break;

    default:
        respondReminders(false, null, 'Metodo non consentito', 405);
}
?>
