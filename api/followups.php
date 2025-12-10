<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');
requireApiAuth(['admin', 'pharmacist']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function respondFollowups($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

$pharmacy_id = $_SESSION['pharmacy_id'] ?? null;
if (!$pharmacy_id) {
    respondFollowups(false, null, 'Farmacia non disponibile', 400);
}

switch ($method) {
    case 'GET':
        $therapy_id = $_GET['therapy_id'] ?? null;
        if (!$therapy_id) {
            respondFollowups(false, null, 'therapy_id richiesto', 400);
        }
        $sql = "SELECT f.* FROM jta_therapy_followups f JOIN jta_therapies t ON f.therapy_id = t.id WHERE f.therapy_id = ? AND t.pharmacy_id = ? ORDER BY f.follow_up_date DESC";
        try {
            $rows = db_fetch_all($sql, [$therapy_id, $pharmacy_id]);
            respondFollowups(true, ['items' => $rows]);
        } catch (Exception $e) {
            respondFollowups(false, null, 'Errore recupero follow-up', 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $therapy_id = $input['therapy_id'] ?? null;
        if (!$therapy_id) {
            respondFollowups(false, null, 'therapy_id richiesto', 400);
        }
        $therapy = db_fetch_one("SELECT id FROM jta_therapies WHERE id = ? AND pharmacy_id = ?", [$therapy_id, $pharmacy_id]);
        if (!$therapy) {
            respondFollowups(false, null, 'Terapia non trovata per la farmacia', 400);
        }
        try {
            db_query(
                "INSERT INTO jta_therapy_followups (therapy_id, risk_score, pharmacist_notes, education_notes, snapshot, follow_up_date) VALUES (?,?,?,?,?,?)",
                [
                    $therapy_id,
                    $input['risk_score'] ?? null,
                    $input['pharmacist_notes'] ?? null,
                    $input['education_notes'] ?? null,
                    isset($input['snapshot']) ? json_encode($input['snapshot']) : null,
                    $input['follow_up_date'] ?? null
                ]
            );
            $followup_id = db()->getConnection()->lastInsertId();
            respondFollowups(true, ['followup_id' => $followup_id]);
        } catch (Exception $e) {
            respondFollowups(false, null, 'Errore creazione follow-up', 500);
        }
        break;

    default:
        respondFollowups(false, null, 'Metodo non consentito', 405);
}
?>
