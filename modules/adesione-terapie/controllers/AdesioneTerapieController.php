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
        $this->patientsTable        = 'jta_patients';
        $this->therapiesTable       = 'jta_therapies';
        $this->assistantsTable      = 'jta_assistants';
        $this->consentsTable        = 'jta_therapy_consents';
        $this->questionnairesTable  = 'jta_therapy_questionnaire';
        $this->checksTable          = 'jta_therapy_checks';
        $this->remindersTable       = 'jta_therapy_reminders';
        $this->reportsTable         = 'jta_therapy_reports';

        $this->bootstrapColumns();
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
            'description' => AdesioneTableResolver::firstAvailableColumn($this->therapiesTable, ['description', 'descrizione', 'details', 'note']),
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
            'id' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['id', 'assistant_id', 'id_assistant']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['therapy_id', 'id_therapy']),
            'patient' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['patient_id', 'id_patient']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['pharmacy_id', 'pharma_id', 'farmacia_id']),
            'name' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['name', 'nome']),
            'relationship' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['relationship', 'relazione', 'parentela']),
            'phone' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['phone', 'telefono']),
            'email' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['email', 'mail']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->assistantsTable, ['created_at']),
        ];

        $this->consentCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['id', 'consent_id', 'id_consent']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['therapy_id', 'id_therapy']),
            'patient' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['patient_id', 'id_patient']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['pharmacy_id', 'pharma_id', 'farmacia_id']),
            'signature_type' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signature_type', 'tipo_firma']),
            'signature_image' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signature_image', 'firma_grafica']),
            'signature_text' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signature_text', 'firma_digitale', 'firma_testo']),
            'notes' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['notes', 'note']),
            'ip' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['ip_address', 'ip']),
            'signed_at' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['signed_at', 'firmato_il', 'created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->consentsTable, ['updated_at'])
        ];

        $this->questionnaireCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['id', 'questionnaire_id']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['therapy_id', 'id_therapy']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['pharmacy_id', 'pharma_id']),
            'answers' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['answers', 'risposte', 'data', 'payload', 'metadata']),
            'step' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['step', 'fase', 'type']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->questionnairesTable, ['updated_at'])
        ];

        $this->checkCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['id', 'check_id', 'id_check']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['therapy_id', 'id_therapy']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['pharmacy_id', 'pharma_id']),
            'scheduled_at' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['scheduled_at', 'data_controllo', 'data_visita']),
            'assessment' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['assessment', 'valutazione', 'esito']),
            'notes' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['notes', 'note', 'osservazioni']),
            'actions' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['actions', 'azioni', 'raccomandazioni']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($this->checksTable, ['updated_at'])
        ];

        $this->reminderCols = [
            'id' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['id', 'reminder_id', 'id_reminder']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['therapy_id', 'id_therapy']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['pharmacy_id', 'pharma_id']),
            'type' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['type', 'tipologia']),
            'message' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['message', 'messaggio', 'testo']),
            'scheduled_at' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['scheduled_at', 'data_promemoria']),
            'recurrence' => AdesioneTableResolver::firstAvailableColumn($this->remindersTable, ['recurrence_rule', 'recurrence', 'ripetizione']),
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
            'checks' => count(array_filter($checks, static function ($check) {
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
            $data[$this->patientCols['birth_date']] = $payload['birth_date'];
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
            $therapyData[$this->therapyCols['start_date']] = $payload['start_date'];
        }
        if ($this->therapyCols['end_date'] && !empty($payload['end_date'])) {
            $therapyData[$this->therapyCols['end_date']] = $payload['end_date'];
        }
        if ($this->therapyCols['updated_at']) {
            $therapyData[$this->therapyCols['updated_at']] = $this->now();
        }

        $metadataPayload = [
            'caregivers' => $caregivers,
            'questionnaire' => $questionnaire,
            'consent_notes' => $payload['consent_notes'] ?? '',
        ];

        if (!empty($payload['signature_type'])) {
            $metadataPayload['signature_type'] = $payload['signature_type'];
        }

        if ($this->therapyCols['metadata']) {
            $therapyData[$this->therapyCols['metadata']] = json_encode($metadataPayload, JSON_UNESCAPED_UNICODE);
        }
        if ($this->therapyCols['questionnaire']) {
            $therapyData[$this->therapyCols['questionnaire']] = json_encode($questionnaire, JSON_UNESCAPED_UNICODE);
        }
        if ($this->therapyCols['caregivers']) {
            $therapyData[$this->therapyCols['caregivers']] = json_encode($caregivers, JSON_UNESCAPED_UNICODE);
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
        if ($this->checkCols['scheduled_at'] && !empty($payload['scheduled_at'])) {
            $data[$this->checkCols['scheduled_at']] = $payload['scheduled_at'];
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
        if ($this->reminderCols['scheduled_at'] && !empty($payload['scheduled_at'])) {
            $data[$this->reminderCols['scheduled_at']] = $payload['scheduled_at'];
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

        $therapy = db_fetch_one($sql, [$therapyId]);

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
                $data[$this->assistantCols['name']] = $this->clean($caregiver['name'] ?? '');
            }
            if ($this->assistantCols['relationship']) {
                $data[$this->assistantCols['relationship']] = $this->clean($caregiver['relationship'] ?? '');
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

            if (!empty(array_filter($data))) {
                db()->insert($this->assistantsTable, AdesioneTableResolver::filterData($this->assistantsTable, $data));
            }
        }
    }

    private function storeQuestionnaire(int $therapyId, array $answers): void
    {
        if (!$this->questionnaireCols['therapy'] || !$this->questionnaireCols['answers']) {
            return;
        }

        $existing = db_fetch_one(
            "SELECT `{$this->questionnaireCols['id']}` FROM `{$this->questionnairesTable}` WHERE `{$this->questionnaireCols['therapy']}` = ? ORDER BY `{$this->questionnaireCols['id']}` DESC LIMIT 1",
            [$therapyId]
        );

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

        $existing = db_fetch_one(
            "SELECT * FROM `{$this->consentsTable}` WHERE `{$this->consentCols['therapy']}` = ? ORDER BY `{$this->consentCols['id']}` DESC LIMIT 1",
            [$therapyId]
        );

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
        if ($this->consentCols['notes'] && isset($payload['consent_notes'])) {
            $data[$this->consentCols['notes']] = $this->clean($payload['consent_notes']);
        }
        if ($this->consentCols['ip']) {
            $data[$this->consentCols['ip']] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        $signatureImage = $payload['signature_image'] ?? '';
        if ($this->consentCols['signature_image'] && $signatureImage) {
            $data[$this->consentCols['signature_image']] = $signatureImage;
        }
        if ($this->consentCols['signature_text'] && !empty($payload['digital_signature'])) {
            $data[$this->consentCols['signature_text']] = $this->clean($payload['digital_signature']);
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
        $row = db_fetch_one(
            "SELECT * FROM `{$this->questionnairesTable}` WHERE `{$this->questionnaireCols['therapy']}` = ? ORDER BY `{$this->questionnaireCols['id']}` DESC LIMIT 1",
            [$therapyId]
        );
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
        $row = db_fetch_one(
            "SELECT * FROM `{$this->consentsTable}` WHERE `{$this->consentCols['therapy']}` = ? ORDER BY `{$this->consentCols['id']}` DESC LIMIT 1",
            [$therapyId]
        );
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
            'birth_date' => $this->patientCols['birth_date'] ? ($patient[$this->patientCols['birth_date']] ?? null) : null,
            'notes' => $this->patientCols['notes'] ? ($patient[$this->patientCols['notes']] ?? '') : '',
        ];
    }

    private function formatTherapy(array $therapy): array
    {
        $metadata = [];
        if ($this->therapyCols['metadata'] && !empty($therapy[$this->therapyCols['metadata']])) {
            $decoded = json_decode($therapy[$this->therapyCols['metadata']], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $caregivers = $metadata['caregivers'] ?? [];
        if ($this->therapyCols['caregivers'] && !empty($therapy[$this->therapyCols['caregivers']])) {
            $decoded = json_decode($therapy[$this->therapyCols['caregivers']], true);
            if (is_array($decoded)) {
                $caregivers = $decoded;
            }
        }

        $questionnaire = $metadata['questionnaire'] ?? [];
        if ($this->therapyCols['questionnaire'] && !empty($therapy[$this->therapyCols['questionnaire']])) {
            $decoded = json_decode($therapy[$this->therapyCols['questionnaire']], true);
            if (is_array($decoded)) {
                $questionnaire = $decoded;
            }
        }

        $patientName = trim(($therapy['patient_first_name'] ?? '') . ' ' . ($therapy['patient_last_name'] ?? ''));

        return [
            'id' => (int)($therapy[$this->therapyCols['id']] ?? 0),
            'patient_id' => (int)($therapy[$this->therapyCols['patient']] ?? 0),
            'patient_name' => $patientName,
            'patient_phone' => $therapy['patient_phone'] ?? '',
            'patient_email' => $therapy['patient_email'] ?? '',
            'description' => $this->therapyCols['description'] ? ($therapy[$this->therapyCols['description']] ?? '') : '',
            'status' => $this->therapyCols['status'] ? ($therapy[$this->therapyCols['status']] ?? 'active') : 'active',
            'start_date' => $this->therapyCols['start_date'] ? ($therapy[$this->therapyCols['start_date']] ?? null) : null,
            'end_date' => $this->therapyCols['end_date'] ? ($therapy[$this->therapyCols['end_date']] ?? null) : null,
            'caregivers' => $caregivers,
            'questionnaire' => $questionnaire,
            'metadata' => $metadata,
        ];
    }

    private function formatCheck(array $check): array
    {
        return [
            'id' => (int)($check[$this->checkCols['id']] ?? 0),
            'therapy_id' => $this->checkCols['therapy'] ? (int)($check[$this->checkCols['therapy']] ?? 0) : 0,
            'scheduled_at' => $this->checkCols['scheduled_at'] ? ($check[$this->checkCols['scheduled_at']] ?? null) : null,
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
            'scheduled_at' => $this->reminderCols['scheduled_at'] ? ($reminder[$this->reminderCols['scheduled_at']] ?? null) : null,
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
            'valid_until' => $this->reportCols['valid_until'] ? ($report[$this->reportCols['valid_until']] ?? null) : null,
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
            'signed_at' => $this->consentCols['signed_at'] ? ($consent[$this->consentCols['signed_at']] ?? '') : '',
        ];
    }

    private function buildTimeline(array $checks, array $reminders): array
    {
        $timeline = [];
        foreach ($checks as $check) {
            $timeline[] = [
                'type' => 'check',
                'title' => 'Visita di controllo',
                'scheduled_at' => $check['scheduled_at'] ?? null,
                'details' => $check['assessment'] ?? '',
                'therapy_id' => $check['therapy_id'] ?? null,
            ];
        }
        foreach ($reminders as $reminder) {
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
        $relative = 'adesione-terapie/report.php?token=' . urlencode($token);
        if ($protected) {
            $relative .= '&secure=1';
        }
        if (str_contains($base, 'http')) {
            return rtrim($base, '/') . '/' . $relative;
        }
        return '/' . ltrim($relative, '/');
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