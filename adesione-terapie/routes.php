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
    'save_chronic_M1',
    'save_anamnesi_generale',
    'save_anamnesi_specifica',
    'save_aderenza_base',
    'save_condition_base',
    'save_condition_approfondita',
    'save_followup_iniziale',
    'save_consensi_firma',
    'save_caregiver_preferences',
];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Azione non riconosciuta']);
    exit;
}

$controller = new AdesioneTerapieController($pharmacyId);

try {
    switch ($action) {
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
