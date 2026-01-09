<?php
session_start();
ob_start();
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err !== null) {
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

function respondFollowups($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

$pharmacy_id = get_panel_pharma_id(true);

function getFollowupById($followup_id, $pharmacy_id)
{
    try {
        return db_fetch_one(
            "SELECT f.* FROM jta_therapy_followups f JOIN jta_therapies t ON f.therapy_id = t.id WHERE f.id = ? AND t.pharmacy_id = ?",
            [$followup_id, $pharmacy_id]
        );
    } catch (Exception $e) {
        respondFollowups(false, null, 'Errore recupero follow-up', 500);
    }
}

function normalizeSnapshot($snapshot)
{
    if (!is_array($snapshot)) {
        $snapshot = [];
    }

    $snapshot['condition'] = $snapshot['condition'] ?? null;
    $snapshot['questions'] = array_values($snapshot['questions'] ?? []);
    $snapshot['custom_questions'] = array_values($snapshot['custom_questions'] ?? []);

    return $snapshot;
}

function buildBaseQuestions($answers)
{
    if (!is_array($answers)) {
        return [];
    }

    $questions = [];
    foreach ($answers as $key => $value) {
        $type = is_bool($value) ? 'boolean' : 'text';
        $questions[] = [
            'key' => (string)$key,
            'text' => (string)$key,
            'label' => (string)$key,
            'type' => $type,
            'answer' => null
        ];
    }
    return $questions;
}

function withFollowupStatus(array $rows)
{
    return array_map(function ($row) {
        $snapshot = json_decode($row['snapshot'] ?? '', true);
        $isCanceled = is_array($snapshot) && !empty($snapshot['canceled']);
        $row['status'] = $isCanceled ? 'canceled' : 'scheduled';
        return $row;
    }, $rows);
}

switch ($method) {
    case 'GET':
        $therapy_id = $_GET['therapy_id'] ?? null;
        $entry_type = $_GET['entry_type'] ?? null;
        if (!$therapy_id) {
            respondFollowups(false, null, 'therapy_id richiesto', 400);
        }
        $params = [$therapy_id, $pharmacy_id];
        $sql = "SELECT f.* FROM jta_therapy_followups f JOIN jta_therapies t ON f.therapy_id = t.id WHERE f.therapy_id = ? AND t.pharmacy_id = ?";
        if ($entry_type === 'check') {
            $sql .= " AND (f.entry_type = ? OR (f.entry_type IS NULL AND COALESCE(JSON_LENGTH(f.snapshot), 0) > 0))";
            $params[] = 'check';
        } elseif ($entry_type === 'followup') {
            $sql .= " AND (f.entry_type = ? OR (f.entry_type IS NULL AND COALESCE(JSON_LENGTH(f.snapshot), 0) = 0))";
            $params[] = 'followup';
        } elseif ($entry_type) {
            $sql .= " AND f.entry_type = ?";
            $params[] = $entry_type;
        }
        $sql .= " ORDER BY f.created_at DESC";
        try {
            $rows = db_fetch_all($sql, $params);
            respondFollowups(true, ['items' => withFollowupStatus($rows)]);
        } catch (Exception $e) {
            respondFollowups(false, null, 'Errore recupero follow-up', 500);
        }
        break;

    case 'POST':
        if ($action === 'init') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $therapy_id = $input['therapy_id'] ?? null;
            if (!$therapy_id) {
                respondFollowups(false, null, 'therapy_id richiesto', 400);
            }
            try {
                $therapy = db_fetch_one("SELECT id FROM jta_therapies WHERE id = ? AND pharmacy_id = ?", [$therapy_id, $pharmacy_id]);
                if (!$therapy) {
                    respondFollowups(false, null, 'Terapia non trovata per la farmacia', 400);
                }

                $conditionRow = db_fetch_one(
                    "SELECT primary_condition FROM jta_therapy_chronic_care WHERE therapy_id = ?",
                    [$therapy_id]
                );
                $baseSurvey = db_fetch_one(
                    "SELECT answers FROM jta_therapy_condition_surveys WHERE therapy_id = ? AND level = 'base' ORDER BY compiled_at DESC LIMIT 1",
                    [$therapy_id]
                );
            } catch (Exception $e) {
                respondFollowups(false, null, 'Errore recupero dati terapia', 500);
            }

            $answers = $baseSurvey ? json_decode($baseSurvey['answers'], true) : [];
            $snapshot = [
                'condition' => $conditionRow['primary_condition'] ?? null,
                'questions' => buildBaseQuestions($answers),
                'custom_questions' => []
            ];

            try {
                db_query(
                    "INSERT INTO jta_therapy_followups (therapy_id, entry_type, snapshot) VALUES (?,?,?)",
                    [
                        $therapy_id,
                        'check',
                        json_encode($snapshot)
                    ]
                );
                $followup_id = db()->getConnection()->lastInsertId();
                $followup = db_fetch_one("SELECT * FROM jta_therapy_followups WHERE id = ?", [$followup_id]);
                if ($followup) {
                    $followup['status'] = 'scheduled';
                }
                respondFollowups(true, ['followup' => $followup, 'snapshot' => $snapshot]);
            } catch (Exception $e) {
                respondFollowups(false, null, 'Errore creazione follow-up', 500);
            }
            break;
        }

        if ($action === 'add-question') {
            $followup_id = $_GET['id'] ?? null;
            if (!$followup_id) {
                respondFollowups(false, null, 'id follow-up richiesto', 400);
            }
            $followup = getFollowupById($followup_id, $pharmacy_id);
            if (!$followup) {
                respondFollowups(false, null, 'Follow-up non trovato', 404);
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $text = trim($input['text'] ?? '');
            $type = $input['type'] ?? 'text';
            if (!$text || !in_array($type, ['text', 'boolean'], true)) {
                respondFollowups(false, null, 'Testo o tipo domanda non validi', 400);
            }

            $snapshot = normalizeSnapshot(json_decode($followup['snapshot'] ?? '', true));
                $snapshot['custom_questions'][] = [
                    'text' => $text,
                    'label' => $text,
                    'type' => $type,
                    'answer' => null
                ];

            try {
                db_query(
                    "UPDATE jta_therapy_followups SET snapshot = ? WHERE id = ?",
                    [json_encode($snapshot), $followup_id]
                );
                respondFollowups(true, ['snapshot' => $snapshot]);
            } catch (Exception $e) {
                respondFollowups(false, null, 'Errore aggiunta domanda', 500);
            }
            break;
        }

        if ($action === 'remove-question') {
            $followup_id = $_GET['id'] ?? null;
            if (!$followup_id) {
                respondFollowups(false, null, 'id follow-up richiesto', 400);
            }
            $followup = getFollowupById($followup_id, $pharmacy_id);
            if (!$followup) {
                respondFollowups(false, null, 'Follow-up non trovato', 404);
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $index = $input['index'] ?? null;
            if ($index === null || !is_numeric($index)) {
                respondFollowups(false, null, 'Indice domanda mancante', 400);
            }
            $snapshot = normalizeSnapshot(json_decode($followup['snapshot'] ?? '', true));
            if (!isset($snapshot['questions'][$index])) {
                respondFollowups(false, null, 'Domanda non trovata', 404);
            }
            array_splice($snapshot['questions'], (int)$index, 1);
            try {
                db_query(
                    "UPDATE jta_therapy_followups SET snapshot = ? WHERE id = ?",
                    [json_encode($snapshot), $followup_id]
                );
                respondFollowups(true, ['snapshot' => $snapshot]);
            } catch (Exception $e) {
                respondFollowups(false, null, 'Errore rimozione domanda', 500);
            }
            break;
        }

        if ($action === 'answer') {
            $followup_id = $_GET['id'] ?? null;
            if (!$followup_id) {
                respondFollowups(false, null, 'id follow-up richiesto', 400);
            }
            $followup = getFollowupById($followup_id, $pharmacy_id);
            if (!$followup) {
                respondFollowups(false, null, 'Follow-up non trovato', 404);
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $answers = $input['answers'] ?? [];
            if (!is_array($answers)) {
                respondFollowups(false, null, 'Formato risposte non valido', 400);
            }

            $snapshot = normalizeSnapshot(json_decode($followup['snapshot'] ?? '', true));
            foreach ($answers as $answer) {
                $index = $answer['index'] ?? null;
                $value = $answer['answer'] ?? null;
                $label = $answer['label'] ?? null;
                $isCustom = !empty($answer['custom']);
                if ($index === null || !is_numeric($index)) {
                    continue;
                }
                if ($isCustom && isset($snapshot['custom_questions'][(int)$index])) {
                    $snapshot['custom_questions'][(int)$index]['answer'] = $value;
                    if ($label !== null) {
                        $snapshot['custom_questions'][(int)$index]['label'] = $label;
                    }
                } elseif (!$isCustom && isset($snapshot['questions'][(int)$index])) {
                    $snapshot['questions'][(int)$index]['answer'] = $value;
                    if ($label !== null) {
                        $snapshot['questions'][(int)$index]['label'] = $label;
                    }
                }
            }

            try {
                db_query(
                    "UPDATE jta_therapy_followups SET snapshot = ? WHERE id = ?",
                    [json_encode($snapshot), $followup_id]
                );
                respondFollowups(true, ['snapshot' => $snapshot]);
            } catch (Exception $e) {
                respondFollowups(false, null, 'Errore salvataggio risposte', 500);
            }
            break;
        }

        if ($action === 'cancel') {
            $followup_id = $_GET['id'] ?? null;
            if (!$followup_id) {
                respondFollowups(false, null, 'id follow-up richiesto', 400);
            }
            $followup = getFollowupById($followup_id, $pharmacy_id);
            if (!$followup) {
                respondFollowups(false, null, 'Follow-up non trovato', 404);
            }
            try {
                db_query(
                    "UPDATE jta_therapy_followups SET snapshot = JSON_SET(COALESCE(snapshot, '{}'), '$.canceled', true) WHERE id = ?",
                    [$followup_id]
                );
                $updatedSnapshot = json_decode($followup['snapshot'] ?? '', true);
                if (!is_array($updatedSnapshot)) {
                    $updatedSnapshot = [];
                }
                $updatedSnapshot['canceled'] = true;
                respondFollowups(true, ['snapshot' => $updatedSnapshot, 'status' => 'canceled']);
            } catch (Exception $e) {
                respondFollowups(false, null, 'Errore annullamento follow-up', 500);
            }
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $therapy_id = $input['therapy_id'] ?? null;
        $risk_score = $input['risk_score'] ?? null;
        $follow_up_date = $input['follow_up_date'] ?? null;
        $pharmacist_notes = $input['pharmacist_notes'] ?? null;

        if (!$therapy_id || $risk_score === null || !$follow_up_date) {
            respondFollowups(false, null, 'Campi obbligatori mancanti', 400);
        }

        try {
            $therapy = db_fetch_one("SELECT id FROM jta_therapies WHERE id = ? AND pharmacy_id = ?", [$therapy_id, $pharmacy_id]);
            if (!$therapy) {
                respondFollowups(false, null, 'Terapia non trovata per la farmacia', 400);
            }

            db_query(
                "INSERT INTO jta_therapy_followups (therapy_id, entry_type, risk_score, pharmacist_notes, follow_up_date) VALUES (?,?,?,?,?)",
                [
                    $therapy_id,
                    'followup',
                    $risk_score,
                    $pharmacist_notes,
                    $follow_up_date
                ]
            );
            $followup_id = db()->getConnection()->lastInsertId();
            $followup = db_fetch_one("SELECT * FROM jta_therapy_followups WHERE id = ?", [$followup_id]);
            if ($followup) {
                $followup['status'] = 'scheduled';
            }
            respondFollowups(true, ['followup' => $followup]);
        } catch (Exception $e) {
            respondFollowups(false, null, 'Errore creazione follow-up', 500);
        }
        break;

    default:
        respondFollowups(false, null, 'Metodo non consentito', 405);
}
