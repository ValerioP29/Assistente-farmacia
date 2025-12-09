<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/controllers/AdesioneTerapieController.php';
require_once __DIR__ . '/controllers/PatientsController.php';
require_once __DIR__ . '/repositories/PatientRepository.php';
require_once __DIR__ . '/services/ColumnBootstrapService.php';
require_once __DIR__ . '/services/FormattingService.php';
require_once __DIR__ . '/services/ValidationService.php';

use Modules\AdesioneTerapie\Controllers\PatientsController;
use Modules\AdesioneTerapie\Repositories\PatientRepository;
use Modules\AdesioneTerapie\Services\ColumnBootstrapService;
use Modules\AdesioneTerapie\Services\FormattingService;
use Modules\AdesioneTerapie\Services\ValidationService;

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

$rawInput = file_get_contents('php://input');
$jsonPayload = json_decode($rawInput ?: '', true);
$payload = is_array($jsonPayload) ? $jsonPayload : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? '');
    if (!verifyCSRFToken($csrfToken)) {
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
        exit;
    }
}

$action = $_REQUEST['action'] ?? ($payload['action'] ?? null);
if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Azione non specificata']);
    exit;
}

// Whitelist delle azioni consentite per il nuovo flusso cronico
$allowedActions = [
    'chronic_create_therapy',
    'save_chronic_M1',
    'save_anamnesi_generale',
    'save_anamnesi_specifica',
    'save_aderenza_base',
    'save_condition_base',
    'save_condition_approfondita',
    'save_followup_iniziale',
    'save_consensi_firma',
    'save_caregiver_preferences',
    'save_patient',
    'list_patients',
];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Azione non riconosciuta']);
    exit;
}

$controller = new AdesioneTerapieController($pharmacyId);
$columnBootstrap = new ColumnBootstrapService();
$columns = $columnBootstrap->bootstrap(
    'jta_patients',
    'jta_therapies',
    'jta_assistants',
    'jta_therapy_assistant',
    'jta_therapy_consents',
    'jta_therapy_reminders',
    'jta_therapy_reports'
);
$patientCols = $columns['patientCols'];
$formattingService = new FormattingService();
$validationService = new ValidationService();
$patientsController = new PatientsController(
    new PatientRepository('jta_patients', $patientCols),
    $formattingService,
    $pharmacyId,
    $patientCols,
    [$validationService, 'clean'],
    [$validationService, 'now']
);

try {
    switch ($action) {
        case 'chronic_create_therapy':
            $data = $controller->createChronicTherapy($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_patient':
            $data = $patientsController->savePatient($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'list_patients':
            $data = $patientsController->listPatients();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_chronic_M1':
            $data = $controller->saveChronicM1($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_anamnesi_generale':
            $data = $controller->saveAnamnesiGenerale($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_anamnesi_specifica':
            $data = $controller->saveAnamnesiSpecifica($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_aderenza_base':
            $data = $controller->saveAderenzaBase($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_condition_base':
            $data = $controller->saveConditionBase($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_condition_approfondita':
            $data = $controller->saveConditionApprofondita($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_followup_iniziale':
            $data = $controller->saveFollowupIniziale($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_consensi_firma':
            $data = $controller->saveConsensiFirma($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_caregiver_preferences':
            $data = $controller->saveCaregiverPreferences($payload);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        default:
            throw new RuntimeException('Azione non gestita');
    }
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore interno: ' . $e->getMessage()]);
}
