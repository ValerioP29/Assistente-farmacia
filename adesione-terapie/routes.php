<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/controllers/AdesioneTerapieController.php';

header('Content-Type: application/json; charset=utf-8');

// DISATTIVARE IN PRODUZIONE
// ini_set('display_errors', 0);
// error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    requireApiAuth(['pharmacist', 'admin']);
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Autenticazione richiesta']);
    exit;
}

$pharmacy = getCurrentPharmacy();
$pharmacyId = (int)($pharmacy['id'] ?? ($_SESSION['pharmacy_id'] ?? 0));
if ($pharmacyId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Farmacia non rilevata.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!verifyCSRFToken($csrfToken)) {
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
        exit;
    }
}

$action = $_REQUEST['action'] ?? null;
if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Azione non specificata']);
    exit;
}

// Whitelist delle azioni consentite
$allowedActions = [
    'init',
    'save_patient',
    'save_therapy',
    'save_check',
    'save_checklist',
    'save_check_execution',
    'save_reminder',
    'generate_report',
    'get_therapy',
    'get_patient'
];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Azione non riconosciuta']);
    exit;
}

$controller = new AdesioneTerapieController($pharmacyId);

try {
    switch ($action) {
        case 'init':
            $data = $controller->getInitialData();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_patient':
            $payload = $_POST;
            // Sanitize input ma mantieni i campi necessari
            $safePayload = [
                'patient_id' => $payload['patient_id'] ?? '',
                'first_name' => $payload['first_name'] ?? '',
                'last_name' => $payload['last_name'] ?? '',
                'phone' => $payload['phone'] ?? '',
                'email' => $payload['email'] ?? '',
                'birth_date' => $payload['birth_date'] ?? '',
                'notes' => $payload['notes'] ?? ''
            ];
            $patient = $controller->savePatient($safePayload);
            echo json_encode(['success' => true, 'patient' => $patient]);
            break;

        case 'save_therapy':
        $payload = $_POST;

        foreach ($_REQUEST as $key => $value) {
            if (!isset($payload[$key])) {
                $payload[$key] = $value;
            }
        }

        $payload['questionnaire_payload'] = $_POST['questionnaire_payload'] ?? '';
        $payload['caregivers_payload'] = $_POST['caregivers_payload'] ?? '';
        $payload['signature_image'] = $_POST['signature_image'] ?? '';

        $therapy = $controller->saveTherapy($payload);
        echo json_encode(['success' => true, 'therapy' => $therapy]);
        break;

        case 'save_check':
            $payload = $_POST;
            $safePayload = [
                'check_id' => $payload['check_id'] ?? '',
                'therapy_id' => $payload['therapy_id'] ?? '',
                'therapy_reference' => $payload['therapy_reference'] ?? '',
                'scheduled_at' => $payload['scheduled_at'] ?? '',
                'check_date' => $payload['check_date'] ?? '',
                'assessment' => $payload['assessment'] ?? '',
                'notes' => $payload['notes'] ?? '',
                'actions' => $payload['actions'] ?? '',
                'answers_payload' => $payload['answers_payload'] ?? '',
            ];
            $check = $controller->saveCheck($safePayload);
            echo json_encode(['success' => true, 'check' => $check]);
            break;

        case 'save_checklist':
            $payload = $_POST;
            $safePayload = [
                'check_id' => $payload['check_id'] ?? '',
                'therapy_id' => $payload['therapy_id'] ?? '',
                'therapy_reference' => $payload['therapy_reference'] ?? '',
                'scheduled_at' => $payload['scheduled_at'] ?? '',
                'check_date' => $payload['check_date'] ?? '',
                'questions_payload' => $payload['questions_payload'] ?? '',
            ];
            $check = $controller->saveChecklist($safePayload);
            echo json_encode(['success' => true, 'check' => $check]);
            break;

        case 'save_check_execution':
            $payload = $_POST;
            $safePayload = [
                'check_id' => $payload['check_id'] ?? '',
                'therapy_id' => $payload['therapy_id'] ?? '',
                'therapy_reference' => $payload['therapy_reference'] ?? '',
                'scheduled_at' => $payload['scheduled_at'] ?? '',
                'check_date' => $payload['check_date'] ?? '',
                'assessment' => $payload['assessment'] ?? '',
                'notes' => $payload['notes'] ?? '',
                'actions' => $payload['actions'] ?? '',
                'answers_payload' => $payload['answers_payload'] ?? '',
            ];
            $check = $controller->saveCheckExecution($safePayload);
            echo json_encode(['success' => true, 'check' => $check]);
            break;

        case 'save_reminder':
            $payload = $_POST;
            $safePayload = [
                'reminder_id' => $payload['reminder_id'] ?? '',
                'therapy_id' => $payload['therapy_id'] ?? '',
                'title' => $payload['title'] ?? '',
                'message' => $payload['message'] ?? '',
                'type' => $payload['type'] ?? '',
                'scheduled_at' => $payload['scheduled_at'] ?? '',
                'channel' => $payload['channel'] ?? '',
                'status' => $payload['status'] ?? ''
            ];
            $reminder = $controller->saveReminder($safePayload);
            echo json_encode(['success' => true, 'reminder' => $reminder]);
            break;

        case 'generate_report':
            $payload = $_POST;
            $safePayload = [
                'therapy_id' => $payload['therapy_id'] ?? '',
                'therapy_reference' => $payload['therapy_reference'] ?? '',
                'include_checks' => $payload['include_checks'] ?? '',
                'recipients' => $payload['recipients'] ?? '',
                'pin_code' => $payload['pin_code'] ?? '',
                'valid_until' => $payload['valid_until'] ?? ''
            ];
            $report = $controller->generateReport($safePayload);
            echo json_encode(['success' => true, 'report' => $report]);
            break;

        case 'get_therapy':
            $therapyId = isset($_GET['therapy_id']) ? (int)$_GET['therapy_id'] : 0;
            if ($therapyId <= 0) {
                throw new RuntimeException('ID terapia non valido');
            }
            $therapy = $controller->findTherapy($therapyId);
            echo json_encode(['success' => true, 'therapy' => $therapy]);
            break;

        case 'get_patient':
            $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
            if ($patientId <= 0) {
                throw new RuntimeException('ID paziente non valido');
            }
            $patient = $controller->findPatient($patientId);
            echo json_encode(['success' => true, 'patient' => $patient]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        // In produzione rimuovere la riga seguente
        'trace' => $e->getTraceAsString()
    ]);
}