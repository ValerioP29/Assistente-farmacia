<?php
require_once __DIR__ . '/../models/TableResolver.php';
require_once __DIR__ . '/../repositories/PatientRepository.php';
require_once __DIR__ . '/../repositories/TherapyRepository.php';
require_once __DIR__ . '/../repositories/AssistantRepository.php';
require_once __DIR__ . '/../repositories/ConsentRepository.php';
require_once __DIR__ . '/../repositories/QuestionnaireRepository.php';
require_once __DIR__ . '/../repositories/CheckRepository.php';
require_once __DIR__ . '/../repositories/CheckAnswerRepository.php';
require_once __DIR__ . '/../repositories/ReminderRepository.php';
require_once __DIR__ . '/../services/FormattingService.php';
require_once __DIR__ . '/../services/TherapyMetadataService.php';
require_once __DIR__ . '/../services/QuestionnaireService.php';
require_once __DIR__ . '/../services/ConsentService.php';
require_once __DIR__ . '/../services/ChecklistService.php';
require_once __DIR__ . '/../services/CheckAnswerService.php';
require_once __DIR__ . '/PatientsController.php';
require_once __DIR__ . '/TherapiesController.php';
require_once __DIR__ . '/ChecksController.php';
require_once __DIR__ . '/RemindersController.php';

class AdesioneTerapieController
{
    private int $pharmacyId;

    private string $patientsTable;
    private string $therapiesTable;
    private string $assistantsTable;
    private string $assistantPivotTable;
    private string $consentsTable;
    private string $questionnairesTable;
    private string $checksTable;
    private string $checkAnswersTable;
    private string $remindersTable;
    private string $reportsTable;

    private array $patientCols = [];
    private array $therapyCols = [];
    private array $assistantCols = [];
    private array $assistantPivotCols = [];
    private array $consentCols = [];
    private array $questionnaireCols = [];
    private array $checkCols = [];
    private array $checkAnswerCols = [];
    private array $reminderCols = [];
    private array $reportCols = [];

    private \Modules\AdesioneTerapie\Controllers\PatientsController $patientsController;
    private \Modules\AdesioneTerapie\Controllers\TherapiesController $therapiesController;
    private \Modules\AdesioneTerapie\Controllers\ChecksController $checksController;
    private \Modules\AdesioneTerapie\Controllers\RemindersController $remindersController;
    private \Modules\AdesioneTerapie\Services\QuestionnaireService $questionnaireService;
    private \Modules\AdesioneTerapie\Services\ConsentService $consentService;
    private \Modules\AdesioneTerapie\Services\FormattingService $formattingService;

    public function __construct(int $pharmacyId)
    {
        $this->pharmacyId = $pharmacyId;
        $this->patientsTable = 'jta_patients';
        $this->therapiesTable = 'jta_therapies';
        $this->assistantsTable = 'jta_assistants';
        $this->assistantPivotTable = 'jta_therapy_assistant';
        $this->consentsTable = 'jta_therapy_consents';
        $this->questionnairesTable = 'jta_therapy_questionnaire';
        $this->checksTable = 'jta_therapy_checks';
        $this->checkAnswersTable = 'jta_therapy_check_answers';
        $this->remindersTable = 'jta_therapy_reminders';
        $this->reportsTable = 'jta_therapy_reports';

        $this->bootstrapColumns();

        $this->formattingService = new \Modules\AdesioneTerapie\Services\FormattingService();
        $this->questionnaireService = $this->makeQuestionnaireService();
        $this->consentService = $this->makeConsentService();
        $this->patientsController = $this->makePatientsController();
        $this->checksController = $this->makeChecksController();
        $this->remindersController = $this->makeRemindersController();
        $this->therapiesController = $this->makeTherapiesController();
    }

    private function bootstrapColumns(): void
    {
        $this->patientCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['id', 'patient_id', 'id_patient']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['pharmacy_id', 'pharma_id', 'farmacia_id', 'id_pharmacy']),
            'first_name' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['first_name', 'nome', 'name']),
            'last_name' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['last_name', 'cognome', 'surname']),
            'phone' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['phone', 'telefono', 'cellulare']),
            'email' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['email', 'mail']),
            'birth_date' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['birth_date', 'data_nascita', 'dob']),
            'notes' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['notes', 'note', 'metadata', 'extra_data', 'data', 'details']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['updated_at', 'modificato_il']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->patientsTable, ['created_at', 'creato_il'])
        ];

        $this->therapyCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['id', 'therapy_id', 'id_therapy']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['pharmacy_id', 'pharma_id', 'farmacia_id']),
            'patient' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['patient_id', 'id_patient', 'paziente_id']),
            'title' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['therapy_title', 'title', 'nome', 'name']),
            'description' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['therapy_description', 'description', 'descrizione', 'details', 'note']),
            'status' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['status', 'stato']),
            'start_date' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['start_date', 'data_inizio', 'inizio']),
            'end_date' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['end_date', 'data_fine', 'fine']),
            'metadata' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['metadata', 'extra_data', 'payload', 'data', 'details_json']),
            'questionnaire' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['questionnaire', 'questionario', 'questionnaire_data', 'questionario_data']),
            'caregivers' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['caregivers', 'caregiver_json', 'assistenti']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['updated_at']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['created_at'])
        ];

        $this->assistantCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['id']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['pharma_id', 'pharmacy_id']),
            'first_name' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['first_name']),
            'last_name' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['last_name']),
            'type' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['type']),
            'phone' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['phone']),
            'email' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['email']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['created_at']),
        ];

        $this->assistantPivotCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->assistantPivotTable, ['id']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->assistantPivotTable, ['therapy_id']),
            'assistant' => AdesioneTableResolver::firstAvailableColumn($this->assistantPivotTable, ['assistant_id']),
            'role' => AdesioneTableResolver::firstAvailableColumn($this->assistantPivotTable, ['role']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->assistantPivotTable, ['created_at']),
        ];

        $this->consentCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['id', 'consent_id', 'id_consent']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['therapy_id', 'id_therapy']),
            'signer_name' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signer_name']),
            'signer_relation' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signer_relation']),
            'consent_text' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['consent_text']),
            'signature_image' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signature_image', 'firma_grafica']),
            'ip' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['ip_address', 'ip']),
            'signed_at' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signed_at']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['created_at'])
        ];

        $this->questionnaireCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['id', 'questionnaire_id']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['therapy_id', 'id_therapy']),
            'question' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['question']),
            'answer' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['answer']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['updated_at'])
        ];

        $this->checkCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['id', 'check_id', 'id_check']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['therapy_id', 'id_therapy']),
            'scheduled_at' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['scheduled_at', 'check_date', 'data_controllo', 'data_visita']),
            'notes' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['notes']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['created_at'])
        ];

        $this->checkAnswerCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->checkAnswersTable, ['id', 'answer_id', 'id_answer']),
            'check' => AdesioneTableResolver::firstAvailableColumn($this->checkAnswersTable, ['check_id', 'id_check']),
            'question' => AdesioneTableResolver::firstAvailableColumn($this->checkAnswersTable, ['question', 'question_key']),
            'answer' => AdesioneTableResolver::firstAvailableColumn($this->checkAnswersTable, ['answer', 'value']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->checkAnswersTable, ['created_at']),
        ];

        $this->reminderCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['id', 'reminder_id', 'id_reminder']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['therapy_id', 'id_therapy']),
            'title' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['title', 'titolo']),
            'message' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['message', 'messaggio', 'testo']),
            'scheduled_at' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['scheduled_at', 'data_promemoria']),
            'channel' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['channel', 'canale']),
            'status' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['status', 'stato']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['updated_at'])
        ];

        $this->reportCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['id', 'report_id', 'id_report']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['therapy_id', 'id_therapy']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['pharmacy_id', 'pharma_id']),
            'content' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['content', 'contenuto', 'data', 'payload']),
            'share_token' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['share_token', 'token', 'public_token']),
            'valid_until' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['valid_until', 'scadenza', 'expires_at']),
            'recipients' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['recipients', 'destinatari']),
            'pin_code' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['pin_code', 'pin']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->reportsTable, ['updated_at'])
        ];
    }

    private function makePatientsController(): \Modules\AdesioneTerapie\Controllers\PatientsController
    {
        return new \Modules\AdesioneTerapie\Controllers\PatientsController(
            new \Modules\AdesioneTerapie\Repositories\PatientRepository($this->patientsTable, $this->patientCols),
            $this->formattingService,
            $this->pharmacyId,
            $this->patientCols,
            [$this, 'clean'],
            [$this, 'now']
        );
    }

    private function makeChecksController(): \Modules\AdesioneTerapie\Controllers\ChecksController
    {
        $checkRepository = new \Modules\AdesioneTerapie\Repositories\CheckRepository(
            $this->checksTable,
            $this->checkCols,
            $this->therapiesTable,
            $this->therapyCols
        );

        return new \Modules\AdesioneTerapie\Controllers\ChecksController(
            $checkRepository,
            new \Modules\AdesioneTerapie\Services\CheckAnswerService(
                new \Modules\AdesioneTerapie\Repositories\CheckAnswerRepository($this->checkAnswersTable, $this->checkAnswerCols),
                $this->checkAnswerCols,
                [$this, 'now']
            ),
            new \Modules\AdesioneTerapie\Services\ChecklistService($checkRepository, $this->questionnaireService, $this->checkCols),
            $this->formattingService,
            $this->questionnaireService,
            $this->pharmacyId,
            $this->checkCols,
            $this->therapyCols,
            [$this, 'clean'],
            [$this, 'now'],
            [$this, 'verifyTherapyOwnership']
        );
    }

    private function makeRemindersController(): \Modules\AdesioneTerapie\Controllers\RemindersController
    {
        return new \Modules\AdesioneTerapie\Controllers\RemindersController(
            new \Modules\AdesioneTerapie\Repositories\ReminderRepository(
                $this->remindersTable,
                $this->reminderCols,
                $this->therapiesTable,
                $this->therapyCols
            ),
            $this->formattingService,
            $this->pharmacyId,
            $this->reminderCols,
            $this->therapyCols,
            [$this, 'clean'],
            [$this, 'now'],
            [$this, 'verifyTherapyOwnership']
        );
    }

    private function makeTherapiesController(): \Modules\AdesioneTerapie\Controllers\TherapiesController
    {
        return new \Modules\AdesioneTerapie\Controllers\TherapiesController(
            new \Modules\AdesioneTerapie\Repositories\TherapyRepository($this->therapiesTable, $this->therapyCols, $this->patientsTable, $this->patientCols),
            new \Modules\AdesioneTerapie\Repositories\AssistantRepository($this->assistantsTable, $this->assistantCols, $this->assistantPivotTable, $this->assistantPivotCols),
            $this->formattingService,
            new \Modules\AdesioneTerapie\Services\TherapyMetadataService(),
            $this->questionnaireService,
            $this->consentService,
            $this->pharmacyId,
            $this->therapyCols,
            $this->patientCols,
            $this->assistantCols,
            $this->assistantPivotCols,
            [$this, 'clean'],
            [$this, 'now'],
            [$this->patientsController, 'savePatient'],
            [$this, 'listChecks'],
            [$this, 'listReminders'],
            [$this, 'listReports'],
            [$this, 'getLastCheck'],
            [$this, 'getUpcomingReminder']
        );
    }

    private function makeQuestionnaireService(): \Modules\AdesioneTerapie\Services\QuestionnaireService
    {
        return new \Modules\AdesioneTerapie\Services\QuestionnaireService(
            new \Modules\AdesioneTerapie\Repositories\QuestionnaireRepository($this->questionnairesTable, $this->questionnaireCols),
            $this->questionnaireCols,
            [$this, 'clean'],
            [$this, 'now']
        );
    }

    private function makeConsentService(): \Modules\AdesioneTerapie\Services\ConsentService
    {
        return new \Modules\AdesioneTerapie\Services\ConsentService(
            new \Modules\AdesioneTerapie\Repositories\ConsentRepository($this->consentsTable, $this->consentCols),
            $this->consentCols,
            [$this, 'clean'],
            [$this, 'now']
        );
    }

    public function getInitialData(): array
    {
        $patients = $this->listPatients();
        $therapies = $this->listTherapies();
        $checks = $this->listChecks();
        $reminders = $this->listReminders();
        $reports = $this->listReports();

        $timeline = $this->buildTimeline($checks, $reminders);

        $executionChecks = array_filter($checks, static function ($check) {
            return ($check['type'] ?? 'execution') !== 'checklist';
        });

        $stats = [
            'patients' => count($patients),
            'therapies' => count($therapies),
            'checks' => count(array_filter($executionChecks, static function ($check) {
                return isset($check['scheduled_at']) && strtotime($check['scheduled_at']) >= time();
            })),
            'reminders' => count(array_filter($reminders, static function ($reminder) {
                return isset($reminder['scheduled_at']) && strtotime($reminder['scheduled_at']) >= time();
            })),
        ];

        return [
            'patients' => $patients,
            'therapies' => $therapies,
            'checks' => $checks,
            'reminders' => $reminders,
            'reports' => $reports,
            'timeline' => $timeline,
            'stats' => $stats,
        ];
    }

    public function savePatient(array $payload): array
    {
        return $this->patientsController->savePatient($payload);
    }

    public function saveTherapy(array $payload): array
    {
        return $this->therapiesController->saveTherapy($payload);
    }

    public function saveCheck(array $payload): array
    {
        return $this->checksController->saveCheck($payload);
    }

    public function saveChecklist(array $payload): array
    {
        return $this->checksController->saveChecklist($payload);
    }

    public function saveCheckExecution(array $payload): array
    {
        return $this->checksController->saveCheckExecution($payload);
    }

    public function saveReminder(array $payload): array
    {
        return $this->remindersController->saveReminder($payload);
    }

    public function generateReport(array $payload): array
    {
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);
        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per generare il report.');
        }

        // Verifica che la terapia appartenga alla farmacia corrente
        $this->verifyTherapyOwnership($therapyId);

        if (!$this->reportCols['share_token']) {
            throw new RuntimeException('La condivisione del report non è disponibile.');
        }

        $includeChecks = !empty($payload['include_checks']);
        $pinCode = $this->clean($payload['pin_code'] ?? '');
        $validUntil = !empty($payload['valid_until']) ? $payload['valid_until'] . ' 23:59:59' : null;

        $therapy = $this->findTherapy($therapyId);
        if (!$therapy) {
            throw new RuntimeException('Terapia non trovata.');
        }

        $checks = $includeChecks ? $this->listChecks($therapyId) : [];
        $content = [
            'generated_at' => $this->now(),
            'therapy' => $therapy,
            'checks' => $checks,
        ];

        $reportData = [];
        if ($this->reportCols['pharmacy']) {
            $reportData[$this->reportCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->reportCols['therapy']) {
            $reportData[$this->reportCols['therapy']] = $therapyId;
        }
        if ($this->reportCols['content']) {
            $reportData[$this->reportCols['content']] = json_encode($content, JSON_UNESCAPED_UNICODE);
        }
        if ($this->reportCols['recipients'] && isset($payload['recipients'])) {
            $reportData[$this->reportCols['recipients']] = $this->clean($payload['recipients']);
        }
        if ($this->reportCols['pin_code'] && $pinCode !== '') {
            $reportData[$this->reportCols['pin_code']] = password_hash($pinCode, PASSWORD_DEFAULT);
        }
        if ($this->reportCols['valid_until'] && $validUntil) {
            $reportData[$this->reportCols['valid_until']] = $validUntil;
        }
        if ($this->reportCols['created_at']) {
            $reportData[$this->reportCols['created_at']] = $this->now();
        }

        $token = bin2hex(random_bytes(16));
        if ($this->reportCols['share_token']) {
            $reportData[$this->reportCols['share_token']] = $token;
        }

        $reportId = (int)db()->insert($this->reportsTable, AdesioneTableResolver::filterData($this->reportsTable, $reportData));

        return [
            'id' => $reportId,
            'token' => $token,
            'url' => $this->buildReportUrl($token, $pinCode !== ''),
            'valid_until' => $validUntil,
            'pin_required' => $pinCode !== ''
        ];
    }

    /**
     * Verifica che una terapia appartenga alla farmacia corrente
     */
    private function verifyTherapyOwnership(int $therapyId): void
    {
        $this->therapiesController->verifyTherapyOwnership($therapyId);
    }

    public function findPatient(int $patientId): array
    {
        return $this->patientsController->findPatient($patientId);
    }

    public function findTherapy(int $therapyId): array
    {
        return $this->therapiesController->findTherapy($therapyId);
    }

    public function findCheck(int $checkId): array
    {
        return $this->checksController->findCheck($checkId);
    }

    public function findReminder(int $reminderId): array
    {
        return $this->remindersController->findReminder($reminderId);
    }

    private function listPatients(): array
    {
        return $this->patientsController->listPatients();
    }

    private function listTherapies(): array
    {
        return $this->therapiesController->listTherapies();
    }

    private function listChecks(?int $therapyId = null): array
    {
        return $this->checksController->listChecks($therapyId);
    }

    private function listReminders(?int $therapyId = null): array
    {
        return $this->remindersController->listReminders($therapyId);
    }

    private function listReports(?int $therapyId = null): array
    {
        $sql = "SELECT * FROM `{$this->reportsTable}`";
        $params = [];
        $conditions = [];
        if ($this->reportCols['pharmacy']) {
            $conditions[] = "`{$this->reportCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        if ($therapyId && $this->reportCols['therapy']) {
            $conditions[] = "`{$this->reportCols['therapy']}` = ?";
            $params[] = $therapyId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->reportCols['created_at']) {
            $sql .= " ORDER BY `{$this->reportCols['created_at']}` DESC";
        }

        $rows = db_fetch_all($sql, $params);
        return array_map([$this, 'formatReport'], $rows);
    }

    private function getLastCheck(array $checks): ?array
    {
        if (empty($checks)) {
            return null;
        }
        $executions = array_filter($checks, static function ($check) {
            return ($check['type'] ?? 'execution') !== 'checklist';
        });
        if (empty($executions)) {
            return null;
        }
        usort($executions, static function ($a, $b) {
            return strtotime($b['scheduled_at'] ?? '1970-01-01') <=> strtotime($a['scheduled_at'] ?? '1970-01-01');
        });
        return $executions[0];
    }

    private function getUpcomingReminder(array $reminders): ?array
    {
        $upcoming = array_filter($reminders, static function ($reminder) {
            return isset($reminder['scheduled_at']) && strtotime($reminder['scheduled_at']) >= time();
        });
        if (empty($upcoming)) {
            return null;
        }
        usort($upcoming, static function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });
        return $upcoming[0];
    }

    private function formatReport(array $report): array
    {
        $content = [];
        if ($this->reportCols['content'] && !empty($report[$this->reportCols['content']])) {
            $decoded = json_decode($report[$this->reportCols['content']], true);
            if (is_array($decoded)) {
                $content = $decoded;
            }
        }
        return [
            'id' => (int)($report[$this->reportCols['id']] ?? 0),
            'therapy_id' => $this->reportCols['therapy'] ? (int)($report[$this->reportCols['therapy']] ?? 0) : 0,
            'token' => $this->reportCols['share_token'] ? ($report[$this->reportCols['share_token']] ?? '') : '',
            'valid_until' => $this->reportCols['valid_until'] ? ($report[$this->reportCols['valid_until']] ?? null) : null,
            'recipients' => $this->reportCols['recipients'] ? ($report[$this->reportCols['recipients']] ?? '') : '',
            'pin_required' => $this->reportCols['pin_code'] ? !empty($report[$this->reportCols['pin_code']]) : false,
            'content' => $content,
            'url' => isset($report[$this->reportCols['share_token']]) ? $this->buildReportUrl($report[$this->reportCols['share_token']], $this->reportCols['pin_code'] && !empty($report[$this->reportCols['pin_code']])) : null,
        ];
    }

    private function buildTimeline(array $checks, array $reminders): array
    {
        $timeline = [];
        foreach ($checks as $check) {
            if (($check['type'] ?? 'execution') === 'checklist') {
                continue;
            }
            $answersPreview = '';
            if (!empty($check['answers'])) {
                $first = $check['answers'][0];
                $previewParts = [];
                if (!empty($first['question'])) {
                    $previewParts[] = trim((string)$first['question']);
                }
                if (!empty($first['answer'])) {
                    $previewParts[] = trim((string)$first['answer']);
                }
                $answersPreview = trim(implode(': ', $previewParts));
                if (function_exists('mb_substr')) {
                    $answersPreview = mb_substr($answersPreview, 0, 60, 'UTF-8');
                } else {
                    $answersPreview = substr($answersPreview, 0, 60);
                }
            }
            $timeline[] = [
                'type' => 'check',
                'title' => 'Visita di controllo',
                'scheduled_at' => $check['scheduled_at'] ?? null,
                'details' => $check['assessment'] ?? '',
                'has_answers' => !empty($check['answers']),
                'answers_preview' => $answersPreview,
                'therapy_id' => $check['therapy_id'] ?? null,
            ];
        }
        foreach ($reminders as $reminder) {
            $timeline[] = [
                'type' => 'reminder',
                'title' => $reminder['title'] ?? 'Promemoria terapia',
                'scheduled_at' => $reminder['scheduled_at'] ?? null,
                'details' => $reminder['message'] ?? '',
                'therapy_id' => $reminder['therapy_id'] ?? null,
            ];
        }

        usort($timeline, static function ($a, $b) {
            return strtotime($a['scheduled_at'] ?? '1970-01-01') <=> strtotime($b['scheduled_at'] ?? '1970-01-01');
        });
        return $timeline;
    }

    private function buildReportUrl(string $token, bool $protected): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $relative = 'adesione-terapie/report.php?token=' . urlencode($token);
        if ($protected) {
            $relative .= '&secure=1';
        }

        $isLocal = $host && (str_contains($host, 'localhost') || str_starts_with($host, '127.0.0.1'));

        if ($isLocal) {
            return rtrim($scheme . '://' . $host, '/') . '/' . ltrim($relative, '/');
        }

        return 'https://panel.assistentefarmacia.it/' . ltrim($relative, '/');
    }

    private function clean(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim(htmlspecialchars_decode((string)$value, ENT_QUOTES));
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}