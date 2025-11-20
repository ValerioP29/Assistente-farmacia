<?php
require_once __DIR__ . '/../models/TableResolver.php';

class AdesioneTerapieController
{
    private int $pharmacyId;

    private string $patientsTable;
    private string $therapiesTable;
    private string $assistantsTable;
    private string $consentsTable;
    private string $questionnairesTable;
    private string $checksTable;
    private string $remindersTable;
    private string $reportsTable;

    private array $patientCols = [];
    private array $therapyCols = [];
    private array $assistantCols = [];
    private array $consentCols = [];
    private array $questionnaireCols = [];
    private array $checkCols = [];
    private array $reminderCols = [];
    private array $reportCols = [];

    public function __construct(int $pharmacyId)
    {
        $this->pharmacyId = $pharmacyId;

        $this->patientsTable = AdesioneTableResolver::resolve('patients');
        $this->therapiesTable = AdesioneTableResolver::resolve('therapies');
        $this->assistantsTable = AdesioneTableResolver::resolve('assistants');
        $this->consentsTable = AdesioneTableResolver::resolve('consensi');
        $this->questionnairesTable = AdesioneTableResolver::resolve('questionari');
        $this->checksTable = AdesioneTableResolver::resolve('check_periodici');
        $this->remindersTable = AdesioneTableResolver::resolve('promemoria');
        $this->reportsTable = AdesioneTableResolver::resolve('report');

        $this->bootstrapColumns();
    }

    private function bootstrapColumns(): void
    {
        $this->patientCols = [
            'id' => 'id',
            'pharmacy' => 'pharmacy_id',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone',
            'email' => 'email',
            'birth_date' => 'birth_date',
            'notes' => 'notes',
            'updated_at' => 'updated_at',
            'created_at' => 'created_at'
        ];

        $this->therapyCols = [
            'id' => 'id',
            'pharmacy' => 'pharmacy_id',
            'patient' => 'patient_id',
            'description' => 'description',
            'status' => 'status',
            'start_date' => 'start_date',
            'end_date' => 'end_date',
            'updated_at' => 'updated_at',
            'created_at' => 'created_at'
        ];

        $this->assistantCols = [
            'id' => 'id',
            'therapy' => 'therapy_id',
            'patient' => 'patient_id',
            'pharmacy' => 'pharmacy_id',
            'name' => 'name',
            'relationship' => 'relationship',
            'phone' => 'phone',
            'email' => 'email',
            'created_at' => 'created_at',
        ];

        $this->consentCols = [
            'id' => 'id',
            'therapy' => 'therapy_id',
            'patient' => 'patient_id',
            'pharmacy' => 'pharmacy_id',
            'signature_type' => 'signature_type',
            'signature_image' => 'signature_image',
            'signature_text' => 'signature_text',
            'notes' => 'notes',
            'ip' => 'ip_address',
            'signed_at' => 'signed_at',
            'updated_at' => 'updated_at'
        ];

        $this->questionnaireCols = [
            'id' => 'id',
            'therapy' => 'therapy_id',
            'pharmacy' => 'pharmacy_id',
            'answers' => 'answers',
            'step' => 'step',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];

        $this->checkCols = [
            'id' => 'id',
            'therapy' => 'therapy_id',
            'pharmacy' => 'pharmacy_id',
            'scheduled_at' => 'scheduled_at',
            'assessment' => 'assessment',
            'notes' => 'notes',
            'actions' => 'actions',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];

        $this->reminderCols = [
            'id' => 'id',
            'therapy' => 'therapy_id',
            'pharmacy' => 'pharmacy_id',
            'type' => 'type',
            'message' => 'message',
            'scheduled_at' => 'scheduled_at',
            'recurrence' => 'recurrence_rule',
            'channel' => 'channel',
            'status' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];

        $this->reportCols = [
            'id' => 'id',
            'therapy' => 'therapy_id',
            'pharmacy' => 'pharmacy_id',
            'content' => 'content',
            'share_token' => 'share_token',
            'valid_until' => 'valid_until',
            'recipients' => 'recipients',
            'pin_code' => 'pin_code',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];
    }

    public function getInitialData(): array
    {
        $patients = $this->listPatients();
        $therapies = $this->listTherapies();
        $checks = $this->listChecks();
        $reminders = $this->listReminders();
        $reports = $this->listReports();

        $timeline = $this->buildTimeline($checks, $reminders);

        $stats = [
            'patients' => count($patients),
            'therapies' => count($therapies),
            'checks' => count($checks),
            'reminders' => count($reminders),
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
        $patientId = isset($payload['patient_id']) && $payload['patient_id'] !== '' ? (int)$payload['patient_id'] : null;

        $data = [];
        if ($this->patientCols['pharmacy']) {
            $data[$this->patientCols['pharmacy']] = $this->pharmacyId;
        }

        if ($this->patientCols['first_name']) {
            $data[$this->patientCols['first_name']] = $this->clean($payload['first_name'] ?? $payload['name'] ?? '');
        }
        if ($this->patientCols['last_name']) {
            $data[$this->patientCols['last_name']] = $this->clean($payload['last_name'] ?? '');
        }
        if ($this->patientCols['phone']) {
            $data[$this->patientCols['phone']] = $this->clean($payload['phone'] ?? '');
        }
        if ($this->patientCols['email']) {
            $data[$this->patientCols['email']] = $this->clean($payload['email'] ?? '');
        }
        if ($this->patientCols['birth_date'] && !empty($payload['birth_date'])) {
            $normalizedBirthDate = $this->normalizeDate($payload['birth_date']);
            if ($normalizedBirthDate) {
                $data[$this->patientCols['birth_date']] = $normalizedBirthDate;
            }
        }
        if ($this->patientCols['notes'] && isset($payload['notes'])) {
            $data[$this->patientCols['notes']] = $this->clean($payload['notes']);
        }
        if ($this->patientCols['updated_at']) {
            $data[$this->patientCols['updated_at']] = $this->now();
        }

        $filtered = AdesioneTableResolver::filterData($this->patientsTable, $data);

        if ($patientId) {
            db()->update($this->patientsTable, $filtered, "{$this->patientCols['id']} = ?", [$patientId]);
        } else {
            if ($this->patientCols['created_at'] && !isset($filtered[$this->patientCols['created_at']])) {
                $filtered[$this->patientCols['created_at']] = $this->now();
            }
            $patientId = (int)db()->insert($this->patientsTable, $filtered);
        }

        return $this->findPatient($patientId);
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

        $questionnaire = [];
        if (!empty($payload['questionnaire_payload'])) {
            $decoded = json_decode($payload['questionnaire_payload'], true);
            if (is_array($decoded)) {
                $questionnaire = $decoded;
            }
        }

        $therapyData = [];
        if ($this->therapyCols['pharmacy']) {
            $therapyData[$this->therapyCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->therapyCols['patient']) {
            $therapyData[$this->therapyCols['patient']] = $patientId;
        }
        if ($this->therapyCols['description']) {
            $therapyData[$this->therapyCols['description']] = $this->clean($payload['description'] ?? '');
        }
        if ($this->therapyCols['status']) {
            $therapyData[$this->therapyCols['status']] = $this->clean($payload['status'] ?? 'active');
        }
        if ($this->therapyCols['start_date'] && !empty($payload['start_date'])) {
            $startDate = $this->normalizeDate($payload['start_date']);
            if ($startDate) {
                $therapyData[$this->therapyCols['start_date']] = $startDate;
            }
        }
        if ($this->therapyCols['end_date'] && !empty($payload['end_date'])) {
            $endDate = $this->normalizeDate($payload['end_date']);
            if ($endDate) {
                $therapyData[$this->therapyCols['end_date']] = $endDate;
            }
        }
        if ($this->therapyCols['updated_at']) {
            $therapyData[$this->therapyCols['updated_at']] = $this->now();
        }

        $filtered = AdesioneTableResolver::filterData($this->therapiesTable, $therapyData);

        if ($therapyId) {
            db()->update($this->therapiesTable, $filtered, "{$this->therapyCols['id']} = ?", [$therapyId]);
        } else {
            if ($this->therapyCols['created_at'] && !isset($filtered[$this->therapyCols['created_at']])) {
                $filtered[$this->therapyCols['created_at']] = $this->now();
            }
            $therapyId = (int)db()->insert($this->therapiesTable, $filtered);
        }

        $this->syncCaregivers($therapyId, $patientId, $caregivers);
        $this->storeQuestionnaire($therapyId, $questionnaire);
        $this->storeConsent($therapyId, $patientId, $payload);

        return $this->findTherapy($therapyId);
    }

    public function saveCheck(array $payload): array
    {
        $checkId = isset($payload['check_id']) && $payload['check_id'] !== '' ? (int)$payload['check_id'] : null;
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);

        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per registrare il check periodico.');
        }

        $data = [];
        if ($this->checkCols['pharmacy']) {
            $data[$this->checkCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->checkCols['therapy']) {
            $data[$this->checkCols['therapy']] = $therapyId;
        }
        if ($this->checkCols['scheduled_at']) {
            $scheduledAt = $this->normalizeDateTime($payload['scheduled_at'] ?? null);
            if (!$scheduledAt) {
                throw new RuntimeException('Imposta una data e ora valide per il check periodico.');
            }
            $data[$this->checkCols['scheduled_at']] = $scheduledAt;
        }
        if ($this->checkCols['assessment']) {
            $data[$this->checkCols['assessment']] = $this->clean($payload['assessment'] ?? '');
        }
        if ($this->checkCols['notes']) {
            $data[$this->checkCols['notes']] = $this->clean($payload['notes'] ?? '');
        }
        if ($this->checkCols['actions']) {
            $data[$this->checkCols['actions']] = $this->clean($payload['actions'] ?? '');
        }
        if ($this->checkCols['updated_at']) {
            $data[$this->checkCols['updated_at']] = $this->now();
        }

        $filtered = AdesioneTableResolver::filterData($this->checksTable, $data);

        if ($checkId) {
            db()->update($this->checksTable, $filtered, "{$this->checkCols['id']} = ?", [$checkId]);
        } else {
            if ($this->checkCols['created_at'] && !isset($filtered[$this->checkCols['created_at']])) {
                $filtered[$this->checkCols['created_at']] = $this->now();
            }
            $checkId = (int)db()->insert($this->checksTable, $filtered);
        }

        return $this->findCheck($checkId);
    }

    public function saveReminder(array $payload): array
    {
        $reminderId = isset($payload['reminder_id']) && $payload['reminder_id'] !== '' ? (int)$payload['reminder_id'] : null;
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : 0;

        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per il promemoria.');
        }

        $data = [];
        if ($this->reminderCols['pharmacy']) {
            $data[$this->reminderCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->reminderCols['therapy']) {
            $data[$this->reminderCols['therapy']] = $therapyId;
        }
        if ($this->reminderCols['type']) {
            $data[$this->reminderCols['type']] = $this->clean($payload['type'] ?? 'one-shot');
        }
        if ($this->reminderCols['message']) {
            $data[$this->reminderCols['message']] = $this->clean($payload['message'] ?? '');
        }
        if ($this->reminderCols['scheduled_at']) {
            $scheduledAt = $this->normalizeDateTime($payload['scheduled_at'] ?? null);
            if (!$scheduledAt) {
                throw new RuntimeException('Imposta una data e ora valide per il promemoria.');
            }
            $data[$this->reminderCols['scheduled_at']] = $scheduledAt;
        }
        if ($this->reminderCols['recurrence'] && isset($payload['recurrence_rule'])) {
            $data[$this->reminderCols['recurrence']] = $this->clean($payload['recurrence_rule']);
        }
        if ($this->reminderCols['channel']) {
            $data[$this->reminderCols['channel']] = $this->clean($payload['channel'] ?? 'email');
        }
        if ($this->reminderCols['status']) {
            $data[$this->reminderCols['status']] = $this->clean($payload['status'] ?? 'scheduled');
        }
        if ($this->reminderCols['updated_at']) {
            $data[$this->reminderCols['updated_at']] = $this->now();
        }

        $filtered = AdesioneTableResolver::filterData($this->remindersTable, $data);

        if ($reminderId) {
            db()->update($this->remindersTable, $filtered, "{$this->reminderCols['id']} = ?", [$reminderId]);
        } else {
            if ($this->reminderCols['created_at'] && !isset($filtered[$this->reminderCols['created_at']])) {
                $filtered[$this->reminderCols['created_at']] = $this->now();
            }
            $reminderId = (int)db()->insert($this->remindersTable, $filtered);
        }

        return $this->findReminder($reminderId);
    }

    public function generateReport(array $payload): array
    {
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);
        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per generare il report.');
        }

        if (!$this->reportCols['share_token']) {
            throw new RuntimeException('La condivisione del report non è disponibile.');
        }

        $includeChecks = !empty($payload['include_checks']);
        $pinCode = $this->clean($payload['pin_code'] ?? '');
        $validUntil = !empty($payload['valid_until']) ? $this->normalizeDateTime($payload['valid_until'] . ' 23:59:59') : null;

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

    public function findPatient(int $patientId): array
    {
        $columns = AdesioneTableResolver::columns($this->patientsTable);
        $columnList = implode(', ', array_map(static function ($column) {
            return "`$column`";
        }, $columns));

        $where = "{$this->patientCols['id']} = ?";
        $params = [$patientId];
        if ($this->patientCols['pharmacy']) {
            $where .= " AND {$this->patientCols['pharmacy']} = ?";
            $params[] = $this->pharmacyId;
        }

        $patient = db_fetch_one("SELECT {$columnList} FROM `{$this->patientsTable}` WHERE {$where}", $params);
        if (!$patient) {
            throw new RuntimeException('Paziente non trovato.');
        }

        return $this->formatPatient($patient);
    }

    public function findTherapy(int $therapyId): array
    {
        $selectFields = ['t.*'];
        if ($this->patientCols['first_name']) {
            $selectFields[] = "p.`{$this->patientCols['first_name']}` AS patient_first_name";
        } else {
            $selectFields[] = "'' AS patient_first_name";
        }
        if ($this->patientCols['last_name']) {
            $selectFields[] = "p.`{$this->patientCols['last_name']}` AS patient_last_name";
        } else {
            $selectFields[] = "'' AS patient_last_name";
        }
        if ($this->patientCols['id']) {
            $selectFields[] = "p.`{$this->patientCols['id']}` AS patient_id";
        }
        if ($this->patientCols['phone']) {
            $selectFields[] = "p.`{$this->patientCols['phone']}` AS patient_phone";
        }
        if ($this->patientCols['email']) {
            $selectFields[] = "p.`{$this->patientCols['email']}` AS patient_email";
        }

        $sql = 'SELECT ' . implode(', ', $selectFields) . " FROM `{$this->therapiesTable}` t ";
        if ($this->therapyCols['patient'] && $this->patientCols['id']) {
            $sql .= "LEFT JOIN `{$this->patientsTable}` p ON t.`{$this->therapyCols['patient']}` = p.`{$this->patientCols['id']}` ";
        }
        $sql .= "WHERE t.`{$this->therapyCols['id']}` = ?";
        $params = [$therapyId];
        if ($this->therapyCols['pharmacy']) {
            $sql .= " AND t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }

        $therapy = db_fetch_one($sql, $params);

        if (!$therapy) {
            throw new RuntimeException('Terapia non trovata.');
        }

        $formatted = $this->formatTherapy($therapy);
        $formatted['caregivers'] = $this->listCaregivers($therapyId);
        $formatted['questionnaire'] = $this->getQuestionnaire($therapyId);
        $formatted['consent'] = $this->getConsent($therapyId);
        $formatted['checks'] = $this->listChecks($therapyId);
        $formatted['reminders'] = $this->listReminders($therapyId);
        $formatted['reports'] = $this->listReports($therapyId);

        return $formatted;
    }

    public function findCheck(int $checkId): array
    {
        $check = db_fetch_one(
            "SELECT * FROM `{$this->checksTable}` WHERE `{$this->checkCols['id']}` = ?",
            [$checkId]
        );
        if (!$check) {
            throw new RuntimeException('Check periodico non trovato.');
        }
        return $this->formatCheck($check);
    }

    public function findReminder(int $reminderId): array
    {
        $reminder = db_fetch_one(
            "SELECT * FROM `{$this->remindersTable}` WHERE `{$this->reminderCols['id']}` = ?",
            [$reminderId]
        );
        if (!$reminder) {
            throw new RuntimeException('Promemoria non trovato.');
        }
        return $this->formatReminder($reminder);
    }

    private function listPatients(): array
    {
        $columns = AdesioneTableResolver::columns($this->patientsTable);
        $columnList = implode(', ', array_map(static function ($column) {
            return "`$column`";
        }, $columns));

        $sql = "SELECT {$columnList} FROM `{$this->patientsTable}`";
        $params = [];
        if ($this->patientCols['pharmacy']) {
            $sql .= " WHERE `{$this->patientCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        if ($this->patientCols['last_name']) {
            $sql .= " ORDER BY `{$this->patientCols['last_name']}`, `{$this->patientCols['first_name']}`";
        }

        $patients = db_fetch_all($sql, $params);

        $result = [];
        foreach ($patients as $patient) {
            $result[] = $this->formatPatient($patient);
        }
        return $result;
    }

    private function listTherapies(): array
    {
        $selectFields = ['t.*'];
        if ($this->patientCols['first_name']) {
            $selectFields[] = "p.`{$this->patientCols['first_name']}` AS patient_first_name";
        } else {
            $selectFields[] = "'' AS patient_first_name";
        }
        if ($this->patientCols['last_name']) {
            $selectFields[] = "p.`{$this->patientCols['last_name']}` AS patient_last_name";
        } else {
            $selectFields[] = "'' AS patient_last_name";
        }
        if ($this->patientCols['id']) {
            $selectFields[] = "p.`{$this->patientCols['id']}` AS patient_id";
        }
        if ($this->patientCols['phone']) {
            $selectFields[] = "p.`{$this->patientCols['phone']}` AS patient_phone";
        }
        if ($this->patientCols['email']) {
            $selectFields[] = "p.`{$this->patientCols['email']}` AS patient_email";
        }

        $sql = 'SELECT ' . implode(', ', $selectFields) . " FROM `{$this->therapiesTable}` t ";
        if ($this->therapyCols['patient'] && $this->patientCols['id']) {
            $sql .= "LEFT JOIN `{$this->patientsTable}` p ON t.`{$this->therapyCols['patient']}` = p.`{$this->patientCols['id']}`";
        }
        $params = [];
        $conditions = [];
        if ($this->therapyCols['pharmacy']) {
            $conditions[] = "t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->therapyCols['start_date']) {
            $sql .= " ORDER BY t.`{$this->therapyCols['start_date']}` DESC";
        } else {
            $sql .= " ORDER BY t.`{$this->therapyCols['id']}` DESC";
        }

        $rows = db_fetch_all($sql, $params);
        $therapies = [];
        foreach ($rows as $row) {
            $formatted = $this->formatTherapy($row);
            $therapyId = $formatted['id'];
            $formatted['caregivers'] = $this->listCaregivers($therapyId);
            $formatted['questionnaire'] = $this->getQuestionnaire($therapyId);
            $formatted['consent'] = $this->getConsent($therapyId);
            $formatted['checks'] = $this->listChecks($therapyId);
            $formatted['reminders'] = $this->listReminders($therapyId);
            $formatted['reports'] = $this->listReports($therapyId);
            $formatted['last_check'] = $this->getLastCheck($formatted['checks']);
            $formatted['upcoming_reminder'] = $this->getUpcomingReminder($formatted['reminders']);
            $therapies[] = $formatted;
        }
        return $therapies;
    }

    private function listChecks(?int $therapyId = null): array
    {
        $sql = "SELECT * FROM `{$this->checksTable}`";
        $params = [];
        $conditions = [];
        if ($this->checkCols['pharmacy']) {
            $conditions[] = "`{$this->checkCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        if ($therapyId && $this->checkCols['therapy']) {
            $conditions[] = "`{$this->checkCols['therapy']}` = ?";
            $params[] = $therapyId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->checkCols['scheduled_at']) {
            $sql .= " ORDER BY `{$this->checkCols['scheduled_at']}` DESC";
        }

        $rows = db_fetch_all($sql, $params);
        return array_map([$this, 'formatCheck'], $rows);
    }

    private function listReminders(?int $therapyId = null): array
    {
        $sql = "SELECT * FROM `{$this->remindersTable}`";
        $params = [];
        $conditions = [];
        if ($this->reminderCols['pharmacy']) {
            $conditions[] = "`{$this->reminderCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        if ($therapyId && $this->reminderCols['therapy']) {
            $conditions[] = "`{$this->reminderCols['therapy']}` = ?";
            $params[] = $therapyId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->reminderCols['scheduled_at']) {
            $sql .= " ORDER BY `{$this->reminderCols['scheduled_at']}` ASC";
        }

        $rows = db_fetch_all($sql, $params);
        return array_map([$this, 'formatReminder'], $rows);
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

    private function listCaregivers(int $therapyId): array
    {
        if (!$this->assistantCols['therapy']) {
            return [];
        }

        $sql = "SELECT * FROM `{$this->assistantsTable}` WHERE `{$this->assistantCols['therapy']}` = ?";
        $params = [$therapyId];
        if ($this->assistantCols['pharmacy']) {
            $sql .= " AND `{$this->assistantCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }

        $rows = db_fetch_all($sql, $params);
        return array_map([$this, 'formatCaregiver'], $rows);
    }

    private function syncCaregivers(int $therapyId, int $patientId, array $caregivers): void
    {
        if (!$this->assistantCols['therapy']) {
            return;
        }

        db_query(
            "DELETE FROM `{$this->assistantsTable}` WHERE `{$this->assistantCols['therapy']}` = ?",
            [$therapyId]
        );

        foreach ($caregivers as $caregiver) {
            $name = $this->clean($caregiver['name'] ?? '');
            $relationship = $this->clean($caregiver['relationship'] ?? '');
            $phone = $this->clean($caregiver['phone'] ?? '');
            $email = $this->clean($caregiver['email'] ?? '');

            if ($name === '' && $relationship === '' && $phone === '' && $email === '') {
                continue;
            }

            $data = [];
            if ($this->assistantCols['therapy']) {
                $data[$this->assistantCols['therapy']] = $therapyId;
            }
            if ($this->assistantCols['patient']) {
                $data[$this->assistantCols['patient']] = $patientId;
            }
            if ($this->assistantCols['pharmacy']) {
                $data[$this->assistantCols['pharmacy']] = $this->pharmacyId;
            }
            if ($this->assistantCols['name']) {
                $data[$this->assistantCols['name']] = $name;
            }
            if ($this->assistantCols['relationship']) {
                $data[$this->assistantCols['relationship']] = $relationship;
            }
            if ($this->assistantCols['phone']) {
                $data[$this->assistantCols['phone']] = $phone;
            }
            if ($this->assistantCols['email']) {
                $data[$this->assistantCols['email']] = $email;
            }
            if ($this->assistantCols['created_at']) {
                $data[$this->assistantCols['created_at']] = $this->now();
            }

            db()->insert($this->assistantsTable, AdesioneTableResolver::filterData($this->assistantsTable, $data));
        }
    }

    private function storeQuestionnaire(int $therapyId, array $answers): void
    {
        if (!$this->questionnaireCols['therapy'] || !$this->questionnaireCols['answers']) {
            return;
        }

        if (!$this->hasQuestionnaireAnswers($answers)) {
            return;
        }

        array_walk_recursive($answers, function (&$value) {
            if (is_string($value)) {
                $value = $this->clean($value);
            }
        });

        $sql = "SELECT `{$this->questionnaireCols['id']}` FROM `{$this->questionnairesTable}` WHERE `{$this->questionnaireCols['therapy']}` = ?";
        $params = [$therapyId];
        if ($this->questionnaireCols['pharmacy']) {
            $sql .= " AND `{$this->questionnaireCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        $sql .= " ORDER BY `{$this->questionnaireCols['id']}` DESC LIMIT 1";

        $existing = db_fetch_one($sql, $params);

        $data = [];
        if ($this->questionnaireCols['therapy']) {
            $data[$this->questionnaireCols['therapy']] = $therapyId;
        }
        if ($this->questionnaireCols['pharmacy']) {
            $data[$this->questionnaireCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->questionnaireCols['answers']) {
            $data[$this->questionnaireCols['answers']] = json_encode($answers, JSON_UNESCAPED_UNICODE);
        }
        if ($this->questionnaireCols['step']) {
            $data[$this->questionnaireCols['step']] = 'initial';
        }
        if ($this->questionnaireCols['updated_at']) {
            $data[$this->questionnaireCols['updated_at']] = $this->now();
        }

        if ($existing) {
            db()->update($this->questionnairesTable, AdesioneTableResolver::filterData($this->questionnairesTable, $data), "{$this->questionnaireCols['id']} = ?", [$existing[$this->questionnaireCols['id']] ?? $existing['id'] ?? $existing]);
        } else {
            if ($this->questionnaireCols['created_at'] && !isset($data[$this->questionnaireCols['created_at']])) {
                $data[$this->questionnaireCols['created_at']] = $this->now();
            }
            db()->insert($this->questionnairesTable, AdesioneTableResolver::filterData($this->questionnairesTable, $data));
        }
    }

    private function storeConsent(int $therapyId, int $patientId, array $payload): void
    {
        if (!$this->consentCols['therapy']) {
            return;
        }

        $signatureImage = $payload['signature_image'] ?? '';
        $signatureText = $this->clean($payload['digital_signature'] ?? '');
        $consentNotes = $this->clean($payload['consent_notes'] ?? '');

        if ($signatureImage === '' && $signatureText === '' && $consentNotes === '') {
            return;
        }

        $sql = "SELECT * FROM `{$this->consentsTable}` WHERE `{$this->consentCols['therapy']}` = ?";
        $params = [$therapyId];
        if ($this->consentCols['pharmacy']) {
            $sql .= " AND `{$this->consentCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        $sql .= " ORDER BY `{$this->consentCols['id']}` DESC LIMIT 1";

        $existing = db_fetch_one($sql, $params);

        $data = [];
        if ($this->consentCols['therapy']) {
            $data[$this->consentCols['therapy']] = $therapyId;
        }
        if ($this->consentCols['patient']) {
            $data[$this->consentCols['patient']] = $patientId;
        }
        if ($this->consentCols['pharmacy']) {
            $data[$this->consentCols['pharmacy']] = $this->pharmacyId;
        }
        if ($this->consentCols['signature_type']) {
            $data[$this->consentCols['signature_type']] = $this->clean($payload['signature_type'] ?? 'graphical');
        }
        if ($this->consentCols['notes'] && $consentNotes !== '') {
            $data[$this->consentCols['notes']] = $consentNotes;
        }
        if ($this->consentCols['ip']) {
            $data[$this->consentCols['ip']] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        if ($this->consentCols['signature_image'] && $signatureImage !== '') {
            $data[$this->consentCols['signature_image']] = $signatureImage;
        }
        if ($this->consentCols['signature_text'] && $signatureText !== '') {
            $data[$this->consentCols['signature_text']] = $signatureText;
        }
        if ($this->consentCols['updated_at']) {
            $data[$this->consentCols['updated_at']] = $this->now();
        }
        if ($this->consentCols['signed_at']) {
            $data[$this->consentCols['signed_at']] = $this->now();
        }

        if ($existing) {
            db()->update($this->consentsTable, AdesioneTableResolver::filterData($this->consentsTable, $data), "{$this->consentCols['id']} = ?", [$existing[$this->consentCols['id']] ?? $existing['id'] ?? $existing]);
        } else {
            db()->insert($this->consentsTable, AdesioneTableResolver::filterData($this->consentsTable, $data));
        }
    }

    private function getQuestionnaire(int $therapyId): array
    {
        if (!$this->questionnaireCols['therapy']) {
            return [];
        }
        $sql = "SELECT * FROM `{$this->questionnairesTable}` WHERE `{$this->questionnaireCols['therapy']}` = ?";
        $params = [$therapyId];
        if ($this->questionnaireCols['pharmacy']) {
            $sql .= " AND `{$this->questionnaireCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        $sql .= " ORDER BY `{$this->questionnaireCols['id']}` DESC LIMIT 1";

        $row = db_fetch_one($sql, $params);
        if (!$row) {
            return [];
        }
        if ($this->questionnaireCols['answers'] && !empty($row[$this->questionnaireCols['answers']])) {
            $decoded = json_decode($row[$this->questionnaireCols['answers']], true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function getConsent(int $therapyId): array
    {
        if (!$this->consentCols['therapy']) {
            return [];
        }
        $sql = "SELECT * FROM `{$this->consentsTable}` WHERE `{$this->consentCols['therapy']}` = ?";
        $params = [$therapyId];
        if ($this->consentCols['pharmacy']) {
            $sql .= " AND `{$this->consentCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        $sql .= " ORDER BY `{$this->consentCols['id']}` DESC LIMIT 1";

        $row = db_fetch_one($sql, $params);
        if (!$row) {
            return [];
        }
        return $this->formatConsent($row);
    }

    private function getLastCheck(array $checks): ?array
    {
        if (empty($checks)) {
            return null;
        }
        usort($checks, static function ($a, $b) {
            return strtotime($b['scheduled_at'] ?? '1970-01-01') <=> strtotime($a['scheduled_at'] ?? '1970-01-01');
        });
        return $checks[0];
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

    private function formatPatient(array $patient): array
    {
        $id = (int)($patient[$this->patientCols['id']] ?? 0);
        $first = $this->patientCols['first_name'] ? ($patient[$this->patientCols['first_name']] ?? '') : '';
        $last = $this->patientCols['last_name'] ? ($patient[$this->patientCols['last_name']] ?? '') : '';
        $fullName = trim($first . ' ' . $last);
        if ($fullName === '') {
            $fullName = $first ?: $last;
        }

        return [
            'id' => $id,
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => $fullName,
            'phone' => $this->patientCols['phone'] ? ($patient[$this->patientCols['phone']] ?? '') : '',
            'email' => $this->patientCols['email'] ? ($patient[$this->patientCols['email']] ?? '') : '',
            'birth_date' => $this->patientCols['birth_date'] ? $this->formatDate($patient[$this->patientCols['birth_date']] ?? null) : null,
            'notes' => $this->patientCols['notes'] ? ($patient[$this->patientCols['notes']] ?? '') : '',
        ];
    }

    private function formatTherapy(array $therapy): array
    {
        $patientName = trim(($therapy['patient_first_name'] ?? '') . ' ' . ($therapy['patient_last_name'] ?? ''));

        return [
            'id' => (int)($therapy[$this->therapyCols['id']] ?? 0),
            'patient_id' => (int)($therapy[$this->therapyCols['patient']] ?? 0),
            'patient_name' => $patientName,
            'patient_phone' => $therapy['patient_phone'] ?? '',
            'patient_email' => $therapy['patient_email'] ?? '',
            'description' => $this->therapyCols['description'] ? ($therapy[$this->therapyCols['description']] ?? '') : '',
            'status' => $this->therapyCols['status'] ? ($therapy[$this->therapyCols['status']] ?? 'active') : 'active',
            'start_date' => $this->therapyCols['start_date'] ? $this->formatDate($therapy[$this->therapyCols['start_date']] ?? null) : null,
            'end_date' => $this->therapyCols['end_date'] ? $this->formatDate($therapy[$this->therapyCols['end_date']] ?? null) : null,
            'caregivers' => [],
            'questionnaire' => [],
        ];
    }

    private function formatCheck(array $check): array
    {
        return [
            'id' => (int)($check[$this->checkCols['id']] ?? 0),
            'therapy_id' => $this->checkCols['therapy'] ? (int)($check[$this->checkCols['therapy']] ?? 0) : 0,
            'scheduled_at' => $this->checkCols['scheduled_at'] ? $this->formatDateTime($check[$this->checkCols['scheduled_at']] ?? null) : null,
            'assessment' => $this->checkCols['assessment'] ? ($check[$this->checkCols['assessment']] ?? '') : '',
            'notes' => $this->checkCols['notes'] ? ($check[$this->checkCols['notes']] ?? '') : '',
            'actions' => $this->checkCols['actions'] ? ($check[$this->checkCols['actions']] ?? '') : '',
        ];
    }

    private function formatReminder(array $reminder): array
    {
        return [
            'id' => (int)($reminder[$this->reminderCols['id']] ?? 0),
            'therapy_id' => $this->reminderCols['therapy'] ? (int)($reminder[$this->reminderCols['therapy']] ?? 0) : 0,
            'type' => $this->reminderCols['type'] ? ($reminder[$this->reminderCols['type']] ?? 'one-shot') : 'one-shot',
            'message' => $this->reminderCols['message'] ? ($reminder[$this->reminderCols['message']] ?? '') : '',
            'scheduled_at' => $this->reminderCols['scheduled_at'] ? $this->formatDateTime($reminder[$this->reminderCols['scheduled_at']] ?? null) : null,
            'recurrence_rule' => $this->reminderCols['recurrence'] ? ($reminder[$this->reminderCols['recurrence']] ?? '') : '',
            'channel' => $this->reminderCols['channel'] ? ($reminder[$this->reminderCols['channel']] ?? '') : '',
            'status' => $this->reminderCols['status'] ? ($reminder[$this->reminderCols['status']] ?? '') : '',
        ];
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
            'valid_until' => $this->reportCols['valid_until'] ? $this->formatDateTime($report[$this->reportCols['valid_until']] ?? null) : null,
            'recipients' => $this->reportCols['recipients'] ? ($report[$this->reportCols['recipients']] ?? '') : '',
            'pin_required' => $this->reportCols['pin_code'] ? !empty($report[$this->reportCols['pin_code']]) : false,
            'content' => $content,
            'url' => isset($report[$this->reportCols['share_token']]) ? $this->buildReportUrl($report[$this->reportCols['share_token']], $this->reportCols['pin_code'] && !empty($report[$this->reportCols['pin_code']])) : null,
        ];
    }

    private function formatCaregiver(array $caregiver): array
    {
        return [
            'id' => (int)($caregiver[$this->assistantCols['id']] ?? 0),
            'name' => $this->assistantCols['name'] ? ($caregiver[$this->assistantCols['name']] ?? '') : '',
            'relationship' => $this->assistantCols['relationship'] ? ($caregiver[$this->assistantCols['relationship']] ?? '') : '',
            'phone' => $this->assistantCols['phone'] ? ($caregiver[$this->assistantCols['phone']] ?? '') : '',
            'email' => $this->assistantCols['email'] ? ($caregiver[$this->assistantCols['email']] ?? '') : '',
        ];
    }

    private function formatConsent(array $consent): array
    {
        return [
            'signature_type' => $this->consentCols['signature_type'] ? ($consent[$this->consentCols['signature_type']] ?? '') : '',
            'signature_image' => $this->consentCols['signature_image'] ? ($consent[$this->consentCols['signature_image']] ?? '') : '',
            'signature_text' => $this->consentCols['signature_text'] ? ($consent[$this->consentCols['signature_text']] ?? '') : '',
            'notes' => $this->consentCols['notes'] ? ($consent[$this->consentCols['notes']] ?? '') : '',
            'ip_address' => $this->consentCols['ip'] ? ($consent[$this->consentCols['ip']] ?? '') : '',
            'signed_at' => $this->consentCols['signed_at'] ? $this->formatDateTime($consent[$this->consentCols['signed_at']] ?? null) : null,
        ];
    }

    private function buildTimeline(array $checks, array $reminders): array
    {
        $timeline = [];
        foreach ($checks as $check) {
            if (empty($check['scheduled_at'])) {
                continue;
            }
            $timeline[] = [
                'type' => 'check',
                'title' => 'Visita di controllo',
                'scheduled_at' => $check['scheduled_at'] ?? null,
                'details' => $check['assessment'] ?? '',
                'therapy_id' => $check['therapy_id'] ?? null,
            ];
        }
        foreach ($reminders as $reminder) {
            if (empty($reminder['scheduled_at'])) {
                continue;
            }
            $timeline[] = [
                'type' => 'reminder',
                'title' => 'Promemoria terapia',
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
        $base = APP_URL ?: rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
        $relative = 'modules/adesione-terapie/report.php?token=' . urlencode($token);
        if ($protected) {
            $relative .= '&secure=1';
        }
        if (str_contains($base, 'http')) {
            return rtrim($base, '/') . '/' . $relative;
        }
        return '/' . ltrim($relative, '/');
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d', $timestamp);
    }

    private function normalizeDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $normalized = str_replace('T', ' ', trim($value));
        $timestamp = strtotime($normalized);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function formatDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d', $timestamp);
    }

    private function formatDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function hasQuestionnaireAnswers(array $answers): bool
    {
        $hasContent = false;
        array_walk_recursive($answers, static function ($value) use (&$hasContent) {
            if ($hasContent) {
                return;
            }
            if (is_string($value) && trim($value) !== '') {
                $hasContent = true;
            }
        });
        return $hasContent;
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
