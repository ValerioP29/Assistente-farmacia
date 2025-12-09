<?php
require_once __DIR__ . '/../models/TableResolver.php';
require_once __DIR__ . '/../repositories/PatientRepository.php';
require_once __DIR__ . '/../repositories/TherapyRepository.php';
require_once __DIR__ . '/../repositories/AssistantRepository.php';
require_once __DIR__ . '/../services/ColumnBootstrapService.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../services/ChronicCareService.php';
require_once __DIR__ . '/../services/ConditionSurveyService.php';
require_once __DIR__ . '/../services/FollowupService.php';
require_once __DIR__ . '/../services/ConsentService.php';

use Modules\AdesioneTerapie\Repositories\AssistantRepository;
use Modules\AdesioneTerapie\Repositories\PatientRepository;
use Modules\AdesioneTerapie\Repositories\TherapyRepository;
use Modules\AdesioneTerapie\Services\ChronicCareService;
use Modules\AdesioneTerapie\Services\ColumnBootstrapService;
use Modules\AdesioneTerapie\Services\ConditionSurveyService;
use Modules\AdesioneTerapie\Services\ConsentService;
use Modules\AdesioneTerapie\Services\FollowupService;
use Modules\AdesioneTerapie\Services\ValidationService;
use RuntimeException;

class AdesioneTerapieController
{
    private int $pharmacyId;

    private string $patientsTable;
    private string $therapiesTable;
    private string $assistantsTable;
    private string $assistantPivotTable;
    private string $consentsTable;
    private string $remindersTable;
    private string $reportsTable;

    private array $patientCols = [];
    private array $therapyCols = [];
    private array $assistantCols = [];
    private array $assistantPivotCols = [];
    private array $consentCols = [];

    private TherapyRepository $therapyRepository;
    private PatientRepository $patientRepository;
    private AssistantRepository $assistantRepository;

    private ValidationService $validationService;
    private ChronicCareService $chronicCareService;
    private ConditionSurveyService $conditionSurveyService;
    private FollowupService $followupService;
    private ConsentService $consentService;

    public function __construct(int $pharmacyId)
    {
        $this->pharmacyId = $pharmacyId;

        $this->patientsTable = 'jta_patients';
        $this->therapiesTable = 'jta_therapies';
        $this->assistantsTable = 'jta_assistants';
        $this->assistantPivotTable = 'jta_therapy_assistant';
        $this->consentsTable = 'jta_therapy_consents';
        $this->remindersTable = 'jta_therapy_reminders';
        $this->reportsTable = 'jta_therapy_reports';

        $this->bootstrapColumns();

        $this->validationService = new ValidationService();
        $this->chronicCareService = new ChronicCareService();
        $this->conditionSurveyService = new ConditionSurveyService();
        $this->followupService = new FollowupService();
        $this->consentService = new ConsentService();

        $this->patientRepository = new PatientRepository($this->patientsTable, $this->patientCols);
        $this->therapyRepository = new TherapyRepository(
            $this->therapiesTable,
            $this->therapyCols,
            $this->patientsTable,
            $this->patientCols
        );
        $this->assistantRepository = new AssistantRepository(
            $this->assistantsTable,
            $this->assistantCols,
            $this->assistantPivotTable,
            $this->assistantPivotCols
        );
    }

    public function createChronicTherapy(array $payload): array
    {
        $patientId = isset($payload['patient_id']) ? (int)$payload['patient_id'] : 0;
        if ($patientId <= 0) {
            throw new RuntimeException('patient_id obbligatorio per creare una terapia');
        }

        $data = [];
        if ($this->therapyCols['pharmacy']) {
            $data[$this->therapyCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->therapyCols['patient']) {
            $data[$this->therapyCols['patient']] = $patientId;
        }
        if ($this->therapyCols['status']) {
            $data[$this->therapyCols['status']] = 'active';
        }

        $therapy = $this->therapyRepository->create($data);

        return [
            'therapy_id' => (int)($therapy[$this->therapyCols['id']] ?? $therapy['id'] ?? 0),
            'therapy' => $therapy,
        ];
    }

    public function saveChronicM1(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $data = $this->chronicCareService->saveInitial($therapyId, [
            'primary_condition' => $input['primary_condition'] ?? '',
            'care_context' => $input['care_context'] ?? [],
            'general_anamnesis' => $input['general_anamnesis'] ?? [],
            'detailed_intake' => $input['detailed_intake'] ?? [],
            'adherence_base' => $input['adherence_base'] ?? [],
            'risk_score' => $input['risk_score'] ?? null,
            'flags' => $input['flags'] ?? [],
            'notes_initial' => $input['notes_initial'] ?? '',
            'follow_up_date' => $input['follow_up_date'] ?? null,
        ]);

        return ['chronic_care' => $data];
    }

    public function saveAnamnesiGenerale(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $data = $this->chronicCareService->update($therapyId, [
            'general_anamnesis' => $input['general_anamnesis'] ?? [],
        ]);

        return ['chronic_care' => $data];
    }

    public function saveAnamnesiSpecifica(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $data = $this->chronicCareService->update($therapyId, [
            'detailed_intake' => $input['detailed_intake'] ?? [],
            'primary_condition' => $input['primary_condition'] ?? null,
        ]);

        return ['chronic_care' => $data];
    }

    public function saveAderenzaBase(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $data = $this->chronicCareService->update($therapyId, [
            'adherence_base' => $input['adherence_base'] ?? [],
            'risk_score' => $input['risk_score'] ?? null,
            'flags' => $input['flags'] ?? null,
        ]);

        return ['chronic_care' => $data];
    }

    public function saveConditionBase(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $condition = $this->validationService->clean($input['condition'] ?? '');
        if ($condition === '') {
            throw new RuntimeException('Condizione patologica mancante');
        }

        $survey = $this->conditionSurveyService->save(
            $therapyId,
            $condition,
            'base',
            is_array($input['answers'] ?? null) ? $input['answers'] : []
        );

        return ['survey' => $survey];
    }

    public function saveConditionApprofondita(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $condition = $this->validationService->clean($input['condition'] ?? '');
        if ($condition === '') {
            throw new RuntimeException('Condizione patologica mancante');
        }

        $survey = $this->conditionSurveyService->save(
            $therapyId,
            $condition,
            'approfondito',
            is_array($input['answers'] ?? null) ? $input['answers'] : []
        );

        return ['survey' => $survey];
    }

    public function saveFollowupIniziale(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $followup = $this->followupService->register($therapyId, [
            'risk_score' => $input['risk_score'] ?? null,
            'pharmacist_notes' => $input['pharmacist_notes'] ?? ($input['notes_initial'] ?? ''),
            'education_notes' => $input['education_notes'] ?? '',
            'snapshot' => $input['snapshot'] ?? null,
            'follow_up_date' => $input['follow_up_date'] ?? null,
        ]);

        $chronic = $this->chronicCareService->update($therapyId, [
            'risk_score' => $input['risk_score'] ?? null,
            'flags' => $input['flags'] ?? null,
            'notes_initial' => $input['pharmacist_notes'] ?? ($input['notes_initial'] ?? ''),
            'follow_up_date' => $input['follow_up_date'] ?? null,
        ]);

        return [
            'followup' => $followup,
            'chronic_care' => $chronic,
        ];
    }

    public function saveConsensiFirma(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $consent = $this->consentService->save($therapyId, [
            'signer_name' => $input['signer_name'] ?? '',
            'signer_relation' => $input['signer_relation'] ?? '',
            'signer_role' => $input['signer_role'] ?? '',
            'consent_text' => $input['consent_text'] ?? '',
            'scopes' => $input['scopes'] ?? [],
            'signature_image' => $input['signature_image'] ?? null,
            'ip_address' => $payload['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
            'signed_at' => $input['signed_at'] ?? null,
        ]);

        return ['consent' => $consent];
    }

    public function saveCaregiverPreferences(array $payload): array
    {
        $therapyId = $this->requireTherapyId($payload);
        $this->verifyTherapyOwnership($therapyId);

        $input = $payload['payload'] ?? $payload;

        $assistantId = isset($input['assistant_id']) ? (int)$input['assistant_id'] : 0;
        if ($assistantId <= 0) {
            throw new RuntimeException('assistant_id mancante per le preferenze caregiver');
        }

        $this->assistantRepository->upsertPivot($therapyId, $assistantId, [
            'contact_channel' => $this->validationService->clean($input['contact_channel'] ?? ''),
            'preferences_json' => $this->encodeJsonField($input['preferences'] ?? null),
            'consents_json' => $this->encodeJsonField($input['consents'] ?? null),
        ]);

        return [
            'therapy_id' => $therapyId,
            'assistant_id' => $assistantId,
            'contact_channel' => $input['contact_channel'] ?? '',
            'preferences' => $input['preferences'] ?? [],
            'consents' => $input['consents'] ?? [],
        ];
    }

    private function bootstrapColumns(): void
    {
        $columns = (new ColumnBootstrapService())->bootstrap(
            $this->patientsTable,
            $this->therapiesTable,
            $this->assistantsTable,
            $this->assistantPivotTable,
            $this->consentsTable,
            $this->remindersTable,
            $this->reportsTable
        );

        $this->patientCols = $columns['patientCols'];
        $this->therapyCols = $columns['therapyCols'];
        $this->assistantCols = $columns['assistantCols'];
        $this->assistantPivotCols = $columns['assistantPivotCols'];
        $this->consentCols = $columns['consentCols'];
    }

    private function requireTherapyId(array $payload): int
    {
        $therapyId = isset($payload['therapy_id']) ? (int)$payload['therapy_id'] : 0;
        if ($therapyId <= 0) {
            throw new RuntimeException('therapy_id obbligatorio per questa operazione');
        }

        return $therapyId;
    }

    public function verifyTherapyOwnership(int $therapyId): void
    {
        if ($this->therapyCols['pharmacy'] && !$this->therapyRepository->existsForPharmacy($therapyId, $this->pharmacyId)) {
            throw new RuntimeException('Terapia non trovata o non appartenente alla farmacia corrente');
        }
    }

    private function encodeJsonField($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value);
    }
}
