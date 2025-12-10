<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');
requireApiAuth(['admin', 'pharmacist']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function respond($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

function getPharmacyId() {
    return $_SESSION['pharmacy_id'] ?? null;
}

$pdo = db()->getConnection();

switch ($method) {
    case 'GET':
        $pharmacy_id = getPharmacyId();
        $therapy_id = $_GET['id'] ?? null;
        $status = $_GET['status'] ?? null;
        $patient_id = $_GET['patient_id'] ?? null;
        $search = $_GET['q'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;

        $params = [];
        $where = [];

        if ($therapy_id) {
            $where[] = 't.id = ?';
            $params[] = $therapy_id;
        }
        if ($status) {
            $where[] = 't.status = ?';
            $params[] = sanitize($status);
        }
        if ($patient_id) {
            $where[] = 't.patient_id = ?';
            $params[] = $patient_id;
        }
        if ($pharmacy_id) {
            $where[] = 't.pharmacy_id = ?';
            $params[] = $pharmacy_id;
        }
        if ($search) {
            $where[] = '(p.first_name LIKE ? OR p.last_name LIKE ? OR p.codice_fiscale LIKE ? OR t.therapy_title LIKE ?)';
            $like = '%' . sanitize($search) . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT t.*, p.first_name, p.last_name, p.codice_fiscale, ph.nice_name AS pharmacy_name
                FROM jta_therapies t
                JOIN jta_patients p ON t.patient_id = p.id
                JOIN jta_pharmas ph ON t.pharmacy_id = ph.id
                $whereSql
                ORDER BY t.created_at DESC
                LIMIT $per_page OFFSET $offset";

        try {
            $rows = db_fetch_all($sql, $params);
            respond(true, ['items' => $rows, 'page' => $page, 'per_page' => $per_page]);
        } catch (Exception $e) {
            respond(false, null, 'Errore caricamento terapie', 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $pharmacy_id = getPharmacyId();
        if (!$pharmacy_id) {
            respond(false, null, 'Farmacia non disponibile', 400);
        }

        $patient = sanitize($input['patient'] ?? []);
        $primary_condition = sanitize($input['primary_condition'] ?? null);
        $initial_notes = $input['initial_notes'] ?? null;
        $general_anamnesis = $input['general_anamnesis'] ?? null;
        $detailed_intake = $input['detailed_intake'] ?? null;
        $therapy_assistants = $input['therapy_assistants'] ?? [];
        $adherence_base = $input['adherence_base'] ?? null;
        $condition_survey = $input['condition_survey'] ?? null;
        $risk_score = $input['risk_score'] ?? null;
        $flags = $input['flags'] ?? null;
        $notes_initial = $input['notes_initial'] ?? null;
        $follow_up_date = $input['follow_up_date'] ?? null;
        $consent = $input['consent'] ?? null;

        if (!$patient || empty($patient['first_name']) || empty($patient['last_name']) || !$primary_condition) {
            respond(false, null, 'Dati paziente o patologia mancanti', 400);
        }

        try {
            $pdo->beginTransaction();

            $patient_id = $patient['id'] ?? null;
            if (!$patient_id) {
                db_query(
                    "INSERT INTO jta_patients (pharmacy_id, first_name, last_name, birth_date, codice_fiscale, phone, email, notes) VALUES (?,?,?,?,?,?,?,?)",
                    [
                        $pharmacy_id,
                        $patient['first_name'],
                        $patient['last_name'],
                        $patient['birth_date'] ?? null,
                        $patient['codice_fiscale'] ?? null,
                        $patient['phone'] ?? null,
                        $patient['email'] ?? null,
                        $patient['notes'] ?? null
                    ]
                );
                $patient_id = $pdo->lastInsertId();
                db_query("INSERT INTO jta_pharma_patient (pharma_id, patient_id) VALUES (?, ?)", [$pharmacy_id, $patient_id]);
            } else {
                db_query(
                    "UPDATE jta_patients SET first_name = ?, last_name = ?, birth_date = ?, codice_fiscale = ?, phone = ?, email = ?, notes = ? WHERE id = ?",
                    [
                        $patient['first_name'],
                        $patient['last_name'],
                        $patient['birth_date'] ?? null,
                        $patient['codice_fiscale'] ?? null,
                        $patient['phone'] ?? null,
                        $patient['email'] ?? null,
                        $patient['notes'] ?? null,
                        $patient_id
                    ]
                );
            }

            $therapy_title = $input['therapy_title'] ?? ('Presa in carico paziente cronico – ' . $primary_condition);
            $therapy_description = $input['therapy_description'] ?? $initial_notes;
            $status = $input['status'] ?? 'active';
            $start_date = $input['start_date'] ?? date('Y-m-d');
            $end_date = $input['end_date'] ?? null;

            db_query(
                "INSERT INTO jta_therapies (pharmacy_id, patient_id, therapy_title, therapy_description, status, start_date, end_date) VALUES (?,?,?,?,?,?,?)",
                [$pharmacy_id, $patient_id, $therapy_title, $therapy_description, $status, $start_date, $end_date]
            );
            $therapy_id = $pdo->lastInsertId();

            db_query(
                "INSERT INTO jta_therapy_chronic_care (therapy_id, primary_condition, general_anamnesis, detailed_intake, adherence_base, risk_score, flags, notes_initial, follow_up_date) VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    $therapy_id,
                    $primary_condition,
                    $general_anamnesis ? json_encode($general_anamnesis) : null,
                    $detailed_intake ? json_encode($detailed_intake) : null,
                    $adherence_base ? json_encode($adherence_base) : null,
                    $risk_score,
                    $flags ? json_encode($flags) : null,
                    $notes_initial ?? $initial_notes,
                    $follow_up_date
                ]
            );

            foreach ($therapy_assistants as $assistant) {
                $assistant = sanitize($assistant);
                $assistant_id = $assistant['assistant_id'] ?? null;
                if (!$assistant_id) {
                    db_query(
                        "INSERT INTO jta_assistants (pharma_id, first_name, last_name, phone, email, type, relation_to_patient, preferred_contact, notes) VALUES (?,?,?,?,?,?,?,?,?)",
                        [
                            $pharmacy_id,
                            $assistant['first_name'] ?? '',
                            $assistant['last_name'] ?? null,
                            $assistant['phone'] ?? null,
                            $assistant['email'] ?? null,
                            $assistant['type'] ?? 'familiare',
                            $assistant['relation_to_patient'] ?? null,
                            $assistant['preferred_contact'] ?? null,
                            $assistant['notes'] ?? null
                        ]
                    );
                    $assistant_id = $pdo->lastInsertId();
                }

                db_query(
                    "INSERT INTO jta_therapy_assistant (therapy_id, assistant_id, role, contact_channel, preferences_json, consents_json) VALUES (?,?,?,?,?,?)",
                    [
                        $therapy_id,
                        $assistant_id,
                        $assistant['role'] ?? 'familiare',
                        $assistant['contact_channel'] ?? null,
                        isset($assistant['preferences_json']) ? json_encode($assistant['preferences_json']) : null,
                        isset($assistant['consents_json']) ? json_encode($assistant['consents_json']) : null
                    ]
                );
            }

            if ($condition_survey) {
                db_query(
                    "INSERT INTO jta_therapy_condition_surveys (therapy_id, condition_type, level, answers, compiled_at) VALUES (?,?,?,?,?)",
                    [
                        $therapy_id,
                        $condition_survey['condition_type'] ?? $primary_condition,
                        $condition_survey['level'] ?? 'base',
                        isset($condition_survey['answers']) ? json_encode($condition_survey['answers']) : null,
                        $condition_survey['compiled_at'] ?? date('Y-m-d H:i:s')
                    ]
                );
            }

            if ($consent) {
                db_query(
                    "INSERT INTO jta_therapy_consents (therapy_id, signer_name, signer_relation, consent_text, signed_at, ip_address, scopes_json, signer_role) VALUES (?,?,?,?,?,?,?,?)",
                    [
                        $therapy_id,
                        $consent['signer_name'] ?? '',
                        $consent['signer_relation'] ?? 'patient',
                        $consent['consent_text'] ?? 'Consenso informato e trattamento dati',
                        $consent['signed_at'] ?? date('Y-m-d H:i:s'),
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        isset($consent['scopes']) ? json_encode($consent['scopes']) : null,
                        $consent['signer_role'] ?? null
                    ]
                );
            }

            $pdo->commit();
            respond(true, ['therapy_id' => $therapy_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            respond(false, null, 'Errore creazione terapia', 500);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $therapy_id = $input['id'] ?? ($_GET['id'] ?? null);
        $pharmacy_id = getPharmacyId();
        if (!$therapy_id || !$pharmacy_id) {
            respond(false, null, 'ID terapia o farmacia mancanti', 400);
        }

        $patient = sanitize($input['patient'] ?? []);
        $primary_condition = sanitize($input['primary_condition'] ?? null);
        $initial_notes = $input['initial_notes'] ?? null;
        $general_anamnesis = $input['general_anamnesis'] ?? null;
        $detailed_intake = $input['detailed_intake'] ?? null;
        $therapy_assistants = $input['therapy_assistants'] ?? [];
        $adherence_base = $input['adherence_base'] ?? null;
        $condition_survey = $input['condition_survey'] ?? null;
        $risk_score = $input['risk_score'] ?? null;
        $flags = $input['flags'] ?? null;
        $notes_initial = $input['notes_initial'] ?? null;
        $follow_up_date = $input['follow_up_date'] ?? null;
        $consent = $input['consent'] ?? null;

        try {
            $pdo->beginTransaction();

            $patient_id = $patient['id'] ?? null;
            if ($patient_id) {
                db_query(
                    "UPDATE jta_patients SET first_name = ?, last_name = ?, birth_date = ?, codice_fiscale = ?, phone = ?, email = ?, notes = ? WHERE id = ?",
                    [
                        $patient['first_name'] ?? '',
                        $patient['last_name'] ?? '',
                        $patient['birth_date'] ?? null,
                        $patient['codice_fiscale'] ?? null,
                        $patient['phone'] ?? null,
                        $patient['email'] ?? null,
                        $patient['notes'] ?? null,
                        $patient_id
                    ]
                );
            }

            db_query(
                "UPDATE jta_therapies SET therapy_title = ?, therapy_description = ?, status = ?, start_date = ?, end_date = ? WHERE id = ? AND pharmacy_id = ?",
                [
                    $input['therapy_title'] ?? ('Presa in carico paziente cronico – ' . $primary_condition),
                    $input['therapy_description'] ?? $initial_notes,
                    $input['status'] ?? 'active',
                    $input['start_date'] ?? date('Y-m-d'),
                    $input['end_date'] ?? null,
                    $therapy_id,
                    $pharmacy_id
                ]
            );

            $existing = db_fetch_one("SELECT id FROM jta_therapy_chronic_care WHERE therapy_id = ?", [$therapy_id]);
            if ($existing) {
                db_query(
                    "UPDATE jta_therapy_chronic_care SET primary_condition = ?, general_anamnesis = ?, detailed_intake = ?, adherence_base = ?, risk_score = ?, flags = ?, notes_initial = ?, follow_up_date = ?, updated_at = NOW() WHERE therapy_id = ?",
                    [
                        $primary_condition,
                        $general_anamnesis ? json_encode($general_anamnesis) : null,
                        $detailed_intake ? json_encode($detailed_intake) : null,
                        $adherence_base ? json_encode($adherence_base) : null,
                        $risk_score,
                        $flags ? json_encode($flags) : null,
                        $notes_initial ?? $initial_notes,
                        $follow_up_date,
                        $therapy_id
                    ]
                );
            } else {
                db_query(
                    "INSERT INTO jta_therapy_chronic_care (therapy_id, primary_condition, general_anamnesis, detailed_intake, adherence_base, risk_score, flags, notes_initial, follow_up_date) VALUES (?,?,?,?,?,?,?,?,?)",
                    [
                        $therapy_id,
                        $primary_condition,
                        $general_anamnesis ? json_encode($general_anamnesis) : null,
                        $detailed_intake ? json_encode($detailed_intake) : null,
                        $adherence_base ? json_encode($adherence_base) : null,
                        $risk_score,
                        $flags ? json_encode($flags) : null,
                        $notes_initial ?? $initial_notes,
                        $follow_up_date
                    ]
                );
            }

            db_query("DELETE FROM jta_therapy_assistant WHERE therapy_id = ?", [$therapy_id]);
            foreach ($therapy_assistants as $assistant) {
                $assistant = sanitize($assistant);
                $assistant_id = $assistant['assistant_id'] ?? null;
                if (!$assistant_id) {
                    db_query(
                        "INSERT INTO jta_assistants (pharma_id, first_name, last_name, phone, email, type, relation_to_patient, preferred_contact, notes) VALUES (?,?,?,?,?,?,?,?,?)",
                        [
                            $pharmacy_id,
                            $assistant['first_name'] ?? '',
                            $assistant['last_name'] ?? null,
                            $assistant['phone'] ?? null,
                            $assistant['email'] ?? null,
                            $assistant['type'] ?? 'familiare',
                            $assistant['relation_to_patient'] ?? null,
                            $assistant['preferred_contact'] ?? null,
                            $assistant['notes'] ?? null
                        ]
                    );
                    $assistant_id = $pdo->lastInsertId();
                }
                db_query(
                    "INSERT INTO jta_therapy_assistant (therapy_id, assistant_id, role, contact_channel, preferences_json, consents_json) VALUES (?,?,?,?,?,?)",
                    [
                        $therapy_id,
                        $assistant_id,
                        $assistant['role'] ?? 'familiare',
                        $assistant['contact_channel'] ?? null,
                        isset($assistant['preferences_json']) ? json_encode($assistant['preferences_json']) : null,
                        isset($assistant['consents_json']) ? json_encode($assistant['consents_json']) : null
                    ]
                );
            }

            db_query("DELETE FROM jta_therapy_condition_surveys WHERE therapy_id = ?", [$therapy_id]);
            if ($condition_survey) {
                db_query(
                    "INSERT INTO jta_therapy_condition_surveys (therapy_id, condition_type, level, answers, compiled_at) VALUES (?,?,?,?,?)",
                    [
                        $therapy_id,
                        $condition_survey['condition_type'] ?? $primary_condition,
                        $condition_survey['level'] ?? 'base',
                        isset($condition_survey['answers']) ? json_encode($condition_survey['answers']) : null,
                        $condition_survey['compiled_at'] ?? date('Y-m-d H:i:s')
                    ]
                );
            }

            db_query("DELETE FROM jta_therapy_consents WHERE therapy_id = ?", [$therapy_id]);
            if ($consent) {
                db_query(
                    "INSERT INTO jta_therapy_consents (therapy_id, signer_name, signer_relation, consent_text, signed_at, ip_address, scopes_json, signer_role) VALUES (?,?,?,?,?,?,?,?)",
                    [
                        $therapy_id,
                        $consent['signer_name'] ?? '',
                        $consent['signer_relation'] ?? 'patient',
                        $consent['consent_text'] ?? 'Consenso informato e trattamento dati',
                        $consent['signed_at'] ?? date('Y-m-d H:i:s'),
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        isset($consent['scopes']) ? json_encode($consent['scopes']) : null,
                        $consent['signer_role'] ?? null
                    ]
                );
            }

            $pdo->commit();
            respond(true, ['therapy_id' => $therapy_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            respond(false, null, 'Errore aggiornamento terapia', 500);
        }
        break;

    case 'DELETE':
        $therapy_id = $_GET['id'] ?? null;
        $pharmacy_id = getPharmacyId();
        if (!$therapy_id || !$pharmacy_id) {
            respond(false, null, 'ID terapia o farmacia mancanti', 400);
        }
        try {
            db_query("UPDATE jta_therapies SET status = 'suspended', end_date = ? WHERE id = ? AND pharmacy_id = ?", [date('Y-m-d'), $therapy_id, $pharmacy_id]);
            respond(true, ['therapy_id' => $therapy_id]);
        } catch (Exception $e) {
            respond(false, null, 'Errore sospensione terapia', 500);
        }
        break;

    default:
        respond(false, null, 'Metodo non consentito', 405);
}
?>
