<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');
requireApiAuth(['admin', 'pharmacist']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? null;

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
        $sql = "SELECT r.* FROM jta_therapy_reminders r JOIN jta_therapies t ON r.therapy_id = t.id WHERE r.therapy_id = ? AND t.pharmacy_id = ? ORDER BY r.scheduled_at ASC";
        try {
            $rows = db_fetch_all($sql, [$therapy_id, $pharmacy_id]);
            respondReminders(true, ['items' => $rows]);
        } catch (Exception $e) {
            respondReminders(false, null, 'Errore recupero promemoria', 500);
        }
        break;

    case 'POST':
        if ($action === 'cancel') {
            $reminder_id = $_GET['id'] ?? null;
            if (!$reminder_id) {
                respondReminders(false, null, 'id promemoria richiesto', 400);
            }

            try {
                $reminder = db_fetch_one(
                    "SELECT r.* FROM jta_therapy_reminders r JOIN jta_therapies t ON r.therapy_id = t.id WHERE r.id = ? AND t.pharmacy_id = ?",
                    [$reminder_id, $pharmacy_id]
                );

                if (!$reminder) {
                    respondReminders(false, null, 'Promemoria non trovato', 404);
                }

                db_query(
                    "UPDATE jta_therapy_reminders SET status = 'canceled' WHERE id = ? AND therapy_id IN (SELECT id FROM jta_therapies WHERE pharmacy_id = ?)",
                    [$reminder_id, $pharmacy_id]
                );

                $updated = db_fetch_one(
                    "SELECT r.* FROM jta_therapy_reminders r JOIN jta_therapies t ON r.therapy_id = t.id WHERE r.id = ? AND t.pharmacy_id = ?",
                    [$reminder_id, $pharmacy_id]
                );

                respondReminders(true, ['item' => $updated]);
            } catch (Exception $e) {
                respondReminders(false, null, 'Errore annullamento promemoria', 500);
            }
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $therapy_id = $input['therapy_id'] ?? null;
        if (!$therapy_id || empty($input['title']) || empty($input['message']) || empty($input['scheduled_at'])) {
            respondReminders(false, null, 'Campi obbligatori mancanti', 400);
        }
        try {
            $therapy = db_fetch_one("SELECT id FROM jta_therapies WHERE id = ? AND pharmacy_id = ?", [$therapy_id, $pharmacy_id]);
            if (!$therapy) {
                respondReminders(false, null, 'Terapia non trovata per la farmacia', 400);
            }

            db_query(
                "INSERT INTO jta_therapy_reminders (therapy_id, title, message, type, scheduled_at, channel, status) VALUES (?,?,?,?,?,?,?)",
                [
                    $therapy_id,
                    sanitize($input['title']),
                    sanitize($input['message']),
                    $input['type'] ?? 'one-shot',
                    $input['scheduled_at'],
                    $input['channel'] ?? 'email',
                    'scheduled'
                ]
            );
            $reminder_id = db()->getConnection()->lastInsertId();
            $reminder = db_fetch_one(
                "SELECT r.* FROM jta_therapy_reminders r JOIN jta_therapies t ON r.therapy_id = t.id WHERE r.id = ? AND t.pharmacy_id = ?",
                [$reminder_id, $pharmacy_id]
            );
            respondReminders(true, ['item' => $reminder]);
        } catch (Exception $e) {
            respondReminders(false, null, 'Errore creazione promemoria', 500);
        }
        break;

    default:
        respondReminders(false, null, 'Metodo non consentito', 405);
}
?>
