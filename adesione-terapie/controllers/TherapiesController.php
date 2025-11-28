<?php

namespace Modules\AdesioneTerapie\Controllers;

use Modules\AdesioneTerapie\Repositories\AssistantRepository;
use Modules\AdesioneTerapie\Repositories\TherapyRepository;
use Modules\AdesioneTerapie\Services\ConsentService;
use Modules\AdesioneTerapie\Services\FormattingService;
use Modules\AdesioneTerapie\Services\TherapyMetadataService;
use RuntimeException;

class TherapiesController
{
    private TherapyRepository $therapyRepository;
    private AssistantRepository $assistantRepository;
    private FormattingService $formattingService;
    private TherapyMetadataService $therapyMetadataService;
    private ConsentService $consentService;
    private int $pharmacyId;
    private array $therapyCols;
    private array $patientCols;
    private array $assistantCols;
    private array $assistantPivotCols;
    private $cleanCallback;
    private $nowCallback;
    private $savePatientCallback;
    private $listChecksCallback;
    private $listRemindersCallback;
    private $listReportsCallback;
    private $getLastCheckCallback;
    private $getUpcomingReminderCallback;

    public function __construct(
        TherapyRepository $therapyRepository,
        AssistantRepository $assistantRepository,
        FormattingService $formattingService,
        TherapyMetadataService $therapyMetadataService,
        ConsentService $consentService,
        int $pharmacyId,
        array $therapyCols,
        array $patientCols,
        array $assistantCols,
        array $assistantPivotCols,
        callable $cleanCallback,
        callable $nowCallback,
        callable $savePatientCallback,
        callable $listChecksCallback,
        callable $listRemindersCallback,
        callable $listReportsCallback,
        callable $getLastCheckCallback,
        callable $getUpcomingReminderCallback
    ) {
        $this->therapyRepository = $therapyRepository;
        $this->assistantRepository = $assistantRepository;
        $this->formattingService = $formattingService;
        $this->therapyMetadataService = $therapyMetadataService;
        $this->consentService = $consentService;
        $this->pharmacyId = $pharmacyId;
        $this->therapyCols = $therapyCols;
        $this->patientCols = $patientCols;
        $this->assistantCols = $assistantCols;
        $this->assistantPivotCols = $assistantPivotCols;
        $this->cleanCallback = $cleanCallback;
        $this->nowCallback = $nowCallback;
        $this->savePatientCallback = $savePatientCallback;
        $this->listChecksCallback = $listChecksCallback;
        $this->listRemindersCallback = $listRemindersCallback;
        $this->listReportsCallback = $listReportsCallback;
        $this->getLastCheckCallback = $getLastCheckCallback;
        $this->getUpcomingReminderCallback = $getUpcomingReminderCallback;
    }

    public function saveTherapy(array $payload): array
    {
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : null;
        $patientId = isset($payload['patient_id']) && $payload['patient_id'] !== '' ? (int)$payload['patient_id'] : null;

        if (!$patientId && !empty($payload['existing_patient'])) {
            $patientId = (int)$payload['existing_patient'];
        }

        if (!$patientId) {
            $patientPayload = [
                'first_name' => $payload['inline_first_name'] ?? '',
                'last_name' => $payload['inline_last_name'] ?? '',
                'phone' => $payload['inline_phone'] ?? '',
                'email' => $payload['inline_email'] ?? '',
                'birth_date' => $payload['inline_birth_date'] ?? null,
                'notes' => $payload['inline_notes'] ?? '',
            ];

            if (empty($patientPayload['first_name']) && empty($patientPayload['last_name'])) {
                throw new RuntimeException('Seleziona un paziente esistente o compila i dati del nuovo paziente.');
            }

            $patient = $this->savePatient($patientPayload);
            $patientId = $patient['id'];
        }

        if (!$patientId) {
            throw new RuntimeException('Impossibile determinare il paziente associato alla terapia.');
        }

        $caregivers = [];
        if (!empty($payload['caregivers_payload'])) {
            $decoded = json_decode($payload['caregivers_payload'], true);
            if (is_array($decoded)) {
                $caregivers = $decoded;
            }
        }

        $therapyData = [];
        if ($this->therapyCols['pharmacy']) {
            $therapyData[$this->therapyCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->therapyCols['patient']) {
            $therapyData[$this->therapyCols['patient']] = $patientId;
        }
        if ($this->therapyCols['title']) {
            $title = $this->clean($payload['therapy_title'] ?? '');
            if ($title === '' && !$therapyId) {
                throw new RuntimeException('Il titolo della terapia è obbligatorio.');
            }
            $therapyData[$this->therapyCols['title']] = $title;
        }
        if ($this->therapyCols['description']) {
            $therapyData[$this->therapyCols['description']] = $this->clean($payload['therapy_description'] ?? '');
        }
        if ($this->therapyCols['status']) {
            $status = $this->clean($payload['status'] ?? 'active');
            $allowedStatuses = ['active', 'planned', 'completed', 'suspended'];
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'active';
            }
            $therapyData[$this->therapyCols['status']] = $status;
        }
        if ($this->therapyCols['start_date'] && !empty($payload['start_date'])) {
            $therapyData[$this->therapyCols['start_date']] = $payload['start_date'];
        }
        if ($this->therapyCols['end_date'] && !empty($payload['end_date'])) {
            $therapyData[$this->therapyCols['end_date']] = $payload['end_date'];
        }
        if ($this->therapyCols['updated_at']) {
            $therapyData[$this->therapyCols['updated_at']] = $this->now();
        }

        // TODO: CHRONIC_REWRITE - legacy metadata/questionnaire persistence removed.

        $filtered = $this->therapyRepository->filterData($therapyData);

        if ($therapyId) {
            $this->therapyRepository->update($therapyId, $filtered, $this->therapyCols['id']);
        } else {
            if ($this->therapyCols['created_at'] && !isset($filtered[$this->therapyCols['created_at']])) {
                $filtered[$this->therapyCols['created_at']] = $this->now();
            }
            $therapyId = $this->therapyRepository->insert($filtered);
        }

        $this->syncCaregivers($therapyId, $patientId, $caregivers);
        // TODO: CHRONIC_REWRITE - legacy single-consent handler; will be replaced.
        $this->consentService->storeConsent($therapyId, $patientId, $payload);

        return $this->findTherapy($therapyId);
    }

    public function findTherapy(int $therapyId): array
    {
        $therapy = $this->therapyRepository->findWithPatient($therapyId, $this->therapyCols['pharmacy'] ? $this->pharmacyId : null);

        if (!$therapy) {
            throw new RuntimeException('Terapia non trovata.');
        }

        $formatted = $this->formattingService->formatTherapy($therapy, $this->therapyCols, $this->patientCols, $this->therapyMetadataService);
        $formatted['caregivers'] = $this->listCaregivers($therapyId);
        // TODO: CHRONIC_REWRITE - legacy questionnaire retrieval removed.
        $formatted['questionnaire'] = [];
        $formatted['consent'] = $this->consentService->getConsent($therapyId);
        $formatted['checks'] = $this->listChecks($therapyId);
        $formatted['reminders'] = $this->listReminders($therapyId);
        $formatted['reports'] = $this->listReports($therapyId);

        return $formatted;
    }

    public function listTherapies(): array
    {
        $rows = $this->therapyRepository->listWithPatient($this->therapyCols['pharmacy'] ? $this->pharmacyId : null);
        $therapies = [];
        foreach ($rows as $row) {
            $formatted = $this->formattingService->formatTherapy($row, $this->therapyCols, $this->patientCols, $this->therapyMetadataService);
            $therapyId = $formatted['id'];
            $formatted['caregivers'] = $this->listCaregivers($therapyId);
            // TODO: CHRONIC_REWRITE - legacy questionnaire retrieval removed.
            $formatted['questionnaire'] = [];
            $formatted['consent'] = $this->consentService->getConsent($therapyId);
            $formatted['checks'] = $this->listChecks($therapyId);
            $formatted['reminders'] = $this->listReminders($therapyId);
            $formatted['reports'] = $this->listReports($therapyId);
            $formatted['last_check'] = $this->getLastCheck($formatted['checks']);
            $formatted['upcoming_reminder'] = $this->getUpcomingReminder($formatted['reminders']);
            $therapies[] = $formatted;
        }
        return $therapies;
    }

    public function verifyTherapyOwnership(int $therapyId): void
    {
        if (!$this->therapyCols['pharmacy']) {
            return;
        }

        $therapy = $this->therapyRepository->existsForPharmacy($therapyId, $this->pharmacyId);

        if (!$therapy) {
            throw new RuntimeException('Terapia non trovata o non accessibile.');
        }
    }

    private function listCaregivers(int $therapyId): array
    {
        $rows = $this->assistantRepository->listByTherapy($therapyId, $this->assistantCols['pharmacy'] ? $this->pharmacyId : null);
        return array_map(function ($caregiver) {
            return $this->formattingService->formatCaregiver($caregiver, $this->assistantCols);
        }, $rows);
    }

    private function syncCaregivers(int $therapyId, int $patientId, array $caregivers): void
    {
        if (!$this->assistantPivotCols['therapy'] || !$this->assistantPivotCols['assistant']) {
            return;
        }

        $this->assistantRepository->deleteByTherapy($therapyId);

        foreach ($caregivers as $caregiver) {
            $data = [];
            if ($this->assistantCols['pharmacy']) {
                $data[$this->assistantCols['pharmacy']] = $this->pharmacyId;
            }
            if ($this->assistantCols['first_name']) {
                $data[$this->assistantCols['first_name']] = $this->clean($caregiver['first_name'] ?? '');
            }
            if ($this->assistantCols['last_name']) {
                $data[$this->assistantCols['last_name']] = $this->clean($caregiver['last_name'] ?? '');
            }
            $type = $this->clean($caregiver['type'] ?? 'familiare');
            if (!in_array($type, ['caregiver', 'familiare'], true)) {
                $type = 'familiare';
            }
            if ($this->assistantCols['type']) {
                $data[$this->assistantCols['type']] = $type;
            }
            if ($this->assistantCols['phone']) {
                $data[$this->assistantCols['phone']] = $this->clean($caregiver['phone'] ?? '');
            }
            if ($this->assistantCols['email'] && isset($caregiver['email'])) {
                $data[$this->assistantCols['email']] = $this->clean($caregiver['email']);
            }
            if ($this->assistantCols['created_at']) {
                $data[$this->assistantCols['created_at']] = $this->now();
            }

            $hasData = !empty($data[$this->assistantCols['first_name']]) ||
                       !empty($data[$this->assistantCols['last_name']]) ||
                       !empty($data[$this->assistantCols['phone']]);

            if (!$hasData) {
                continue;
            }

            $assistantId = $this->assistantRepository->insertAssistant($data);

            $pivotData = [
                $this->assistantPivotCols['therapy'] => $therapyId,
                $this->assistantPivotCols['assistant'] => $assistantId,
                $this->assistantPivotCols['role'] => $type,
            ];
            if ($this->assistantPivotCols['created_at']) {
                $pivotData[$this->assistantPivotCols['created_at']] = $this->now();
            }

            $this->assistantRepository->insertPivot($pivotData);
        }
    }

    private function listChecks(?int $therapyId = null): array
    {
        return call_user_func($this->listChecksCallback, $therapyId);
    }

    private function listReminders(?int $therapyId = null): array
    {
        return call_user_func($this->listRemindersCallback, $therapyId);
    }

    private function listReports(?int $therapyId = null): array
    {
        return call_user_func($this->listReportsCallback, $therapyId);
    }

    private function getLastCheck(array $checks): ?array
    {
        return call_user_func($this->getLastCheckCallback, $checks);
    }

    private function getUpcomingReminder(array $reminders): ?array
    {
        return call_user_func($this->getUpcomingReminderCallback, $reminders);
    }

    private function clean(?string $value): string
    {
        return call_user_func($this->cleanCallback, $value);
    }

    private function now(): string
    {
        return call_user_func($this->nowCallback);
    }

    private function savePatient(array $payload): array
    {
        return call_user_func($this->savePatientCallback, $payload);
    }
}
