<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$publicToken = $_GET['token'] ?? null;

if (!$publicToken) {
    requireApiAuth(['admin', 'pharmacist']);
}

function respondReports($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

switch ($method) {
    case 'GET':
        if ($publicToken) {
            $report = db_fetch_one("SELECT * FROM jta_therapy_reports WHERE share_token = ? AND (valid_until IS NULL OR valid_until >= NOW())", [$publicToken]);
            if (!$report) {
                respondReports(false, null, 'Report non trovato', 404);
            }
            respondReports(true, ['report' => $report]);
        }

        $pharmacy_id = get_panel_pharma_id(true);
        $therapy_id = $_GET['therapy_id'] ?? null;
        if (!$therapy_id) {
            respondReports(false, null, 'therapy_id o farmacia mancanti', 400);
        }
        $sql = "SELECT * FROM jta_therapy_reports WHERE therapy_id = ? AND pharmacy_id = ? ORDER BY created_at DESC";
        try {
            $rows = db_fetch_all($sql, [$therapy_id, $pharmacy_id]);
            respondReports(true, ['items' => $rows]);
        } catch (Exception $e) {
            respondReports(false, null, 'Errore recupero report', 500);
        }
        break;

    case 'POST':
        $pharmacy_id = get_panel_pharma_id(true);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $therapy_id = $input['therapy_id'] ?? null;
        if (!$therapy_id) {
            respondReports(false, null, 'therapy_id o farmacia mancanti', 400);
        }
        $content = $input['content'] ?? [];
        try {
            db_query(
                "INSERT INTO jta_therapy_reports (therapy_id, pharmacy_id, content, share_token, pin_code, valid_until, recipients) VALUES (?,?,?,?,?,?,?)",
                [
                    $therapy_id,
                    $pharmacy_id,
                    json_encode($content),
                    $input['share_token'] ?? null,
                    $input['pin_code'] ?? null,
                    $input['valid_until'] ?? null,
                    $input['recipients'] ?? null
                ]
            );
            $report_id = db()->getConnection()->lastInsertId();
            respondReports(true, ['report_id' => $report_id]);
        } catch (Exception $e) {
            respondReports(false, null, 'Errore creazione report', 500);
        }
        break;

    default:
        respondReports(false, null, 'Metodo non consentito', 405);
}
?>
