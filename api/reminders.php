<?php
session_start();
ob_start();
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err !== null) {
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($err['type'] ?? null, $fatalTypes, true)) {
            return;
        }
        
        if (!headers_sent()) header('Content-Type: application/json');
        http_response_code(500);
        $buffer = ob_get_clean();
        echo json_encode([
            'success' => false,
            'data' => null,
            'error' => 'Fatal error',
            'details' => $err,
            'buffer' => $buffer
        ]);
    }
});
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');
requireApiAuth(['admin', 'pharmacist']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? null;

function respondReminders($success, $data = null, $error = null, $code = 200, $meta = []) {
    http_response_code($code);
    $response = ['success' => $success, 'status' => $success, 'data' => $data, 'error' => $error];
    if (isset($meta['code'])) {
        $response['code'] = $meta['code'];
    }
    if (isset($meta['message'])) {
        $response['message'] = $meta['message'];
    }
    if (array_key_exists('details', $meta)) {
        $response['details'] = $meta['details'];
    }
    echo json_encode($response);
    exit;
}

$pharmacy_id = get_panel_pharma_id(true);

switch ($method) {
    case 'GET':
        $therapy_id = $_GET['therapy_id'] ?? null;
        if (!$therapy_id) {
            respondReminders(false, null, 'therapy_id richiesto', 400);
        }

        $includeCanceled = isset($_GET['include_canceled']) && $_GET['include_canceled'] === '1';
        $includeHistory = isset($_GET['include_history']) && $_GET['include_history'] === '1';
        $sql = "SELECT r.* FROM jta_therapy_reminders r JOIN jta_therapies t ON r.therapy_id = t.id WHERE r.therapy_id = ? AND t.pharmacy_id = ?";
        if (!$includeHistory) {
            if ($includeCanceled) {
                $sql .= " AND r.status IN ('scheduled', 'canceled')";
            } else {
                $sql .= " AND r.status = 'scheduled'";
            }
        }
        $sql .= " ORDER BY r.scheduled_at ASC";

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
        $timezone = new DateTimeZone('Europe/Rome');
        $scheduledRaw = trim((string) $input['scheduled_at']);
        $scheduledAt = DateTime::createFromFormat('Y-m-d\\TH:i', $scheduledRaw, $timezone);
        if (!$scheduledAt) {
            $scheduledAt = DateTime::createFromFormat('Y-m-d H:i:s', $scheduledRaw, $timezone);
        }
        if (!$scheduledAt) {
            $scheduledAt = DateTime::createFromFormat('Y-m-d H:i', $scheduledRaw, $timezone);
        }
        $parseErrors = DateTime::getLastErrors();
        if ($parseErrors === false) {
            $parseErrors = ['warning_count' => 0, 'error_count' => 0];
        }
        if (!$scheduledAt || $parseErrors['warning_count'] > 0 || $parseErrors['error_count'] > 0) {
            respondReminders(false, null, 'Formato data/ora non valido', 422, [
                'code' => 'REMINDER_INVALID_DATETIME',
                'message' => 'Formato data/ora non valido.',
                'details' => ['scheduled_at' => $scheduledRaw]
            ]);
        }
        $now = new DateTime('now', $timezone);
        if ($scheduledAt <= $now) {
            respondReminders(false, null, 'Impossibile salvare: data/ora nel passato.', 422, [
                'code' => 'REMINDER_PAST_DATETIME',
                'message' => 'Impossibile salvare: data/ora nel passato.',
                'details' => [
                    'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                    'now' => $now->format('Y-m-d H:i:s'),
                    'timezone' => $timezone->getName()
                ]
            ]);
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
                    $scheduledAt->format('Y-m-d H:i:s'),
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
