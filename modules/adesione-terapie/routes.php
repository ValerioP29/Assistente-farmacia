<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/controllers/AdesioneTerapieController.php';

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors',1);
error_reporting(E_ALL);


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

$controller = new AdesioneTerapieController($pharmacyId);

try {
    switch ($action) {
        case 'init':
            $data = $controller->getInitialData();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save_patient':
            $payload = sanitize($_POST);
            $patient = $controller->savePatient($payload);
            echo json_encode(['success' => true, 'patient' => $patient]);
            break;

        case 'save_therapy':
            $payload = $_POST;
            if (!empty($payload['signature_image']) && strpos($payload['signature_image'], 'data:image') === 0) {
                $payload['signature_image'] = $payload['signature_image'];
            }
            $therapy = $controller->saveTherapy($payload);
            echo json_encode(['success' => true, 'therapy' => $therapy]);
            break;

        case 'save_check':
            $payload = sanitize($_POST);
            $check = $controller->saveCheck($payload);
            echo json_encode(['success' => true, 'check' => $check]);
            break;

        case 'save_reminder':
            $payload = sanitize($_POST);
            $reminder = $controller->saveReminder($payload);
            echo json_encode(['success' => true, 'reminder' => $reminder]);
            break;

        case 'generate_report':
            $payload = sanitize($_POST);
            $report = $controller->generateReport($payload);
            echo json_encode(['success' => true, 'report' => $report]);
            break;

        case 'get_therapy':
            $therapyId = isset($_GET['therapy_id']) ? (int)$_GET['therapy_id'] : 0;
            $therapy = $controller->findTherapy($therapyId);
            echo json_encode(['success' => true, 'therapy' => $therapy]);
            break;

        case 'get_patient':
            $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
            $patient = $controller->findPatient($patientId);
            echo json_encode(['success' => true, 'patient' => $patient]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Azione non riconosciuta']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
