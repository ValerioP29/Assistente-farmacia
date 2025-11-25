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
require_once __DIR__ . '/../repositories/ReportRepository.php';
require_once __DIR__ . '/../services/FormattingService.php';
require_once __DIR__ . '/../services/TherapyMetadataService.php';
require_once __DIR__ . '/../services/QuestionnaireService.php';
require_once __DIR__ . '/../services/ConsentService.php';
require_once __DIR__ . '/../services/ChecklistService.php';
require_once __DIR__ . '/../services/CheckAnswerService.php';
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../services/TimelineService.php';
require_once __DIR__ . '/../services/ColumnBootstrapService.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/PatientsController.php';
require_once __DIR__ . '/TherapiesController.php';
require_once __DIR__ . '/ChecksController.php';
require_once __DIR__ . '/RemindersController.php';
require_once __DIR__ . '/ReportsController.php';

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
    private \Modules\AdesioneTerapie\Controllers\ReportsController $reportsController;
    private \Modules\AdesioneTerapie\Services\QuestionnaireService $questionnaireService;
    private \Modules\AdesioneTerapie\Services\ConsentService $consentService;
    private \Modules\AdesioneTerapie\Services\FormattingService $formattingService;
    private \Modules\AdesioneTerapie\Services\ReportService $reportService;
    private \Modules\AdesioneTerapie\Services\TimelineService $timelineService;
    private \Modules\AdesioneTerapie\Services\ValidationService $validationService;

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

        $this->validationService = new \Modules\AdesioneTerapie\Services\ValidationService();
        $this->formattingService = new \Modules\AdesioneTerapie\Services\FormattingService();
        $this->reportService = new \Modules\AdesioneTerapie\Services\ReportService();
        $this->timelineService = new \Modules\AdesioneTerapie\Services\TimelineService();
        $this->questionnaireService = $this->makeQuestionnaireService();
        $this->consentService = $this->makeConsentService();
        $this->patientsController = $this->makePatientsController();
        $this->checksController = $this->makeChecksController();
        $this->remindersController = $this->makeRemindersController();
        $this->reportsController = $this->makeReportsController();
        $this->therapiesController = $this->makeTherapiesController();
    }

    private function bootstrapColumns(): void
    {
        $columns = (new \Modules\AdesioneTerapie\Services\ColumnBootstrapService())->bootstrap(
            $this->patientsTable,
            $this->therapiesTable,
            $this->assistantsTable,
            $this->assistantPivotTable,
            $this->consentsTable,
            $this->questionnairesTable,
            $this->checksTable,
            $this->checkAnswersTable,
            $this->remindersTable,
            $this->reportsTable
        );

        $this->patientCols = $columns['patientCols'];
        $this->therapyCols = $columns['therapyCols'];
        $this->assistantCols = $columns['assistantCols'];
        $this->assistantPivotCols = $columns['assistantPivotCols'];
        $this->consentCols = $columns['consentCols'];
        $this->questionnaireCols = $columns['questionnaireCols'];
        $this->checkCols = $columns['checkCols'];
        $this->checkAnswerCols = $columns['checkAnswerCols'];
        $this->reminderCols = $columns['reminderCols'];
        $this->reportCols = $columns['reportCols'];
    }

    private function makePatientsController(): \Modules\AdesioneTerapie\Controllers\PatientsController
    {
        return new \Modules\AdesioneTerapie\Controllers\PatientsController(
            new \Modules\AdesioneTerapie\Repositories\PatientRepository($this->patientsTable, $this->patientCols),
            $this->formattingService,
            $this->pharmacyId,
            $this->patientCols,
            [$this->validationService, 'clean'],
            [$this->validationService, 'now']
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
            [$this->validationService, 'clean'],
            [$this->validationService, 'now'],
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
            [$this->validationService, 'clean'],
            [$this->validationService, 'now'],
            [$this, 'verifyTherapyOwnership']
        );
    }

    private function makeReportsController(): \Modules\AdesioneTerapie\Controllers\ReportsController
    {
        return new \Modules\AdesioneTerapie\Controllers\ReportsController(
            new \Modules\AdesioneTerapie\Repositories\ReportRepository($this->reportsTable, $this->reportCols),
            $this->formattingService,
            $this->reportService,
            $this->pharmacyId,
            $this->reportCols,
            [$this->validationService, 'clean'],
            [$this->validationService, 'now'],
            [$this, 'verifyTherapyOwnership'],
            [$this, 'findTherapy'],
            [$this, 'listChecks']
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
            [$this->validationService, 'clean'],
            [$this->validationService, 'now'],
            [$this->patientsController, 'savePatient'],
            [$this, 'listChecks'],
            [$this, 'listReminders'],
            [$this, 'listReports'],
            [$this->timelineService, 'getLastCheck'],
            [$this->timelineService, 'getUpcomingReminder']
        );
    }

    private function makeQuestionnaireService(): \Modules\AdesioneTerapie\Services\QuestionnaireService
    {
        return new \Modules\AdesioneTerapie\Services\QuestionnaireService(
            new \Modules\AdesioneTerapie\Repositories\QuestionnaireRepository($this->questionnairesTable, $this->questionnaireCols),
            $this->questionnaireCols,
            [$this->validationService, 'clean'],
            [$this->validationService, 'now']
        );
    }

    private function makeConsentService(): \Modules\AdesioneTerapie\Services\ConsentService
    {
        return new \Modules\AdesioneTerapie\Services\ConsentService(
            new \Modules\AdesioneTerapie\Repositories\ConsentRepository($this->consentsTable, $this->consentCols),
            $this->consentCols,
            [$this->validationService, 'clean'],
            [$this->validationService, 'now']
        );
    }

    public function getInitialData(): array
    {
        $patients = $this->listPatients();
        $therapies = $this->listTherapies();
        $checks = $this->listChecks();
        $reminders = $this->listReminders();
        $reports = $this->listReports();

        $timeline = $this->timelineService->buildTimeline($checks, $reminders);

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
        return $this->reportsController->generateReport($payload);
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
        return $this->reportsController->listReports($therapyId);
    }

}