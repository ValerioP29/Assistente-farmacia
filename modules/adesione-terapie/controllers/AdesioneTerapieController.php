<?php
require_once __DIR__ . '/../models/TableResolver.php';

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
        $patientId = isset($payload['patient_id']) && $payload['patient_id'] !== '' ? (int)$payload['patient_id'] : null;

        $data = [];
        if ($this->patientCols['pharmacy']) {
            $data[$this->patientCols['pharmacy']] = $this->pharmacyId;
        }

        if ($this->patientCols['first_name']) {
            $firstName = $this->clean($payload['first_name'] ?? $payload['name'] ?? '');
            if ($firstName === '' && !$patientId) {
                throw new RuntimeException('Il nome del paziente è obbligatorio.');
            }
            $data[$this->patientCols['first_name']] = $firstName;
        }
        if ($this->patientCols['last_name']) {
            $data[$this->patientCols['last_name']] = $this->clean($payload['last_name'] ?? '');
        }
        if ($this->patientCols['phone']) {
            $data[$this->patientCols['phone']] = $this->clean($payload['phone'] ?? '');
        }
        if ($this->patientCols['email']) {
            $email = $this->clean($payload['email'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Indirizzo email non valido.');
            }
            $data[$this->patientCols['email']] = $email;
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
        if ($therapyId && is_array($questionnaire) && !empty($questionnaire)) {
            $this->storeQuestionnaire($therapyId, $questionnaire);
        }
        $this->storeConsent($therapyId, $patientId, $payload);

        return $this->findTherapy($therapyId);
    }

    public function saveCheck(array $payload): array
    {
        return $this->saveCheckExecution($payload);
    }

    public function saveChecklist(array $payload): array
    {
        $checkId = isset($payload['check_id']) && $payload['check_id'] !== '' ? (int)$payload['check_id'] : null;
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);

        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per configurare la checklist.');
        }

        $this->verifyTherapyOwnership($therapyId);

        $questionsRaw = $payload['questions_payload'] ?? '[]';
        $decoded = is_array($payload['questions'] ?? null) ? ($payload['questions'] ?? []) : json_decode($questionsRaw, true);
        $questions = $this->normalizeChecklistQuestions($decoded);
        if (empty($questions)) {
            throw new RuntimeException('Aggiungi almeno una domanda alla checklist.');
        }

        $scheduledAt = $payload['scheduled_at'] ?? $payload['check_date'] ?? '';
        if ($scheduledAt === '') {
            $scheduledAt = $this->now();
        }

        $data = [];
        if ($this->checkCols['therapy']) {
            $data[$this->checkCols['therapy']] = $therapyId;
        }
        if ($this->checkCols['scheduled_at']) {
            $data[$this->checkCols['scheduled_at']] = str_replace('T', ' ', $scheduledAt);
        }
        $notesPayload = [
            'type' => 'checklist',
            'questions' => $questions,
        ];
        if ($this->checkCols['notes']) {
            $data[$this->checkCols['notes']] = json_encode($notesPayload, JSON_UNESCAPED_UNICODE);
        }

        $filtered = AdesioneTableResolver::filterData($this->checksTable, $data);
        if (!$checkId) {
            $checkId = $this->findChecklistId($therapyId);
        }

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

    public function saveCheckExecution(array $payload): array
    {
        $checkId = isset($payload['check_id']) && $payload['check_id'] !== '' ? (int)$payload['check_id'] : null;
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);

        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per registrare il check periodico.');
        }

        // Verifica che la terapia appartenga alla farmacia corrente
        $this->verifyTherapyOwnership($therapyId);

        $data = [];
        if ($this->checkCols['therapy']) {
            $data[$this->checkCols['therapy']] = $therapyId;
        }
        $scheduledAt = $payload['scheduled_at'] ?? $payload['check_date'] ?? '';
        if ($this->checkCols['scheduled_at'] && $scheduledAt !== '') {
            $data[$this->checkCols['scheduled_at']] = str_replace('T', ' ', $scheduledAt);
        }

        if ($this->checkCols['scheduled_at'] && empty($data[$this->checkCols['scheduled_at']])) {
            throw new RuntimeException('Imposta data e ora del check.');
        }

        $notesPayload = [
            'type' => 'execution',
            'assessment' => $this->clean($payload['assessment'] ?? ''),
            'notes' => $this->clean($payload['notes'] ?? ''),
            'actions' => $this->clean($payload['actions'] ?? ''),
        ];
        if (empty($notesPayload['assessment'])) {
            throw new RuntimeException('Compila almeno la valutazione del check.');
        }
        if ($this->checkCols['notes']) {
            $data[$this->checkCols['notes']] = json_encode($notesPayload, JSON_UNESCAPED_UNICODE);
        }

        $filtered = AdesioneTableResolver::filterData($this->checksTable, $data);

        if (!$checkId && $this->checkCols['therapy'] && $this->checkCols['scheduled_at'] && !empty($filtered[$this->checkCols['scheduled_at']])) {
            $existingCheckId = (int)db()->fetchValue(
                "SELECT {$this->checkCols['id']} FROM {$this->checksTable} WHERE {$this->checkCols['therapy']} = ? AND {$this->checkCols['scheduled_at']} = ? LIMIT 1",
                [$therapyId, $filtered[$this->checkCols['scheduled_at']]]
            );
            if ($existingCheckId) {
                $existingRow = db_fetch_one(
                    "SELECT * FROM `{$this->checksTable}` WHERE `{$this->checkCols['id']}` = ? LIMIT 1",
                    [$existingCheckId]
                );
                $formattedExisting = $existingRow ? $this->formatCheck($existingRow) : null;
                if (!$formattedExisting || ($formattedExisting['type'] ?? 'execution') !== 'checklist') {
                    $checkId = $existingCheckId;
                }
            }
        }

        if ($checkId) {
            db()->update($this->checksTable, $filtered, "{$this->checkCols['id']} = ?", [$checkId]);
        } else {
            if ($this->checkCols['created_at'] && !isset($filtered[$this->checkCols['created_at']])) {
                $filtered[$this->checkCols['created_at']] = $this->now();
            }
            $checkId = (int)db()->insert($this->checksTable, $filtered);
        }

        $answersRaw = $payload['answers_payload'] ?? '[]';
        $decodedAnswers = is_array($payload['answers'] ?? null) ? ($payload['answers'] ?? []) : json_decode($answersRaw, true);
        $answers = $this->normalizeCheckAnswers($decodedAnswers);
        if (!empty($notesPayload['assessment'])) {
            $answers['assessment'] = $notesPayload['assessment'];
        }
        if (!empty($notesPayload['notes'])) {
            $answers['notes'] = $notesPayload['notes'];
        }
        if (!empty($notesPayload['actions'])) {
            $answers['actions'] = $notesPayload['actions'];
        }

        if (!empty($answers)) {
            $this->storeCheckAnswers($checkId, $answers);
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

        // Verifica che la terapia appartenga alla farmacia corrente
        $this->verifyTherapyOwnership($therapyId);

        $data = [];
        if ($this->reminderCols['therapy']) {
            $data[$this->reminderCols['therapy']] = $therapyId;
        }
        if ($this->reminderCols['title']) {
            $title = $this->clean($payload['title'] ?? '');
            if ($title === '' && !empty($payload['message'])) {
                $title = mb_substr($this->clean($payload['message']), 0, 120);
            }
            $data[$this->reminderCols['title']] = $title !== '' ? $title : 'Promemoria terapia';
        }

        if ($this->reminderCols['scheduled_at'] && !empty($payload['scheduled_at'])) {
            $data[$this->reminderCols['scheduled_at']] = str_replace('T', ' ', $payload['scheduled_at']);
        }

        if ($this->reminderCols['scheduled_at'] && empty($data[$this->reminderCols['scheduled_at']])) {
            throw new RuntimeException('Imposta data e ora del promemoria.');
        }

        $allowedChannels = ['sms', 'email', 'push'];
        $channel = strtolower($payload['channel'] ?? 'email');
        if (!in_array($channel, $allowedChannels, true)) {
            $channel = 'email';
        }

        $messageText = $this->clean($payload['message'] ?? '');
        if ($messageText === '') {
            throw new RuntimeException('Inserisci il messaggio del promemoria.');
        }

        $messagePayload = [
            'text' => $messageText,
            'type' => $this->clean($payload['type'] ?? 'one-shot'),
            'recurrence' => $this->clean($payload['recurrence_rule'] ?? ''),
        ];

        if ($this->reminderCols['message']) {
            $data[$this->reminderCols['message']] = json_encode($messagePayload, JSON_UNESCAPED_UNICODE);
        }

        if ($this->reminderCols['channel']) {
            $data[$this->reminderCols['channel']] = $channel;
        }
        if ($this->reminderCols['status']) {
            $status = $this->clean($payload['status'] ?? 'scheduled');
            $data[$this->reminderCols['status']] = $status ?: 'scheduled';
        }
        if ($this->reminderCols['updated_at']) {
            $data[$this->reminderCols['updated_at']] = $this->now();
        }

        $filtered = AdesioneTableResolver::filterData($this->remindersTable, $data);

        if (!$reminderId && $this->reminderCols['therapy'] && $this->reminderCols['scheduled_at'] && !empty($filtered[$this->reminderCols['scheduled_at']])) {
            $lookupParams = [$therapyId, $filtered[$this->reminderCols['scheduled_at']]];
            $where = "{$this->reminderCols['therapy']} = ? AND {$this->reminderCols['scheduled_at']} = ?";
            if ($this->reminderCols['title'] && isset($filtered[$this->reminderCols['title']])) {
                $where .= " AND {$this->reminderCols['title']} = ?";
                $lookupParams[] = $filtered[$this->reminderCols['title']];
            }

            $existingReminderId = (int)db()->fetchValue(
                "SELECT {$this->reminderCols['id']} FROM {$this->remindersTable} WHERE {$where} ORDER BY {$this->reminderCols['id']} DESC LIMIT 1",
                $lookupParams
            );
            if ($existingReminderId) {
                $reminderId = $existingReminderId;
            }
        }

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
        if (!$this->therapyCols['pharmacy']) {
            return;
        }
        
        $therapy = db_fetch_one(
            "SELECT `{$this->therapyCols['id']}` FROM `{$this->therapiesTable}` WHERE `{$this->therapyCols['id']}` = ? AND `{$this->therapyCols['pharmacy']}` = ?",
            [$therapyId, $this->pharmacyId]
        );
        
        if (!$therapy) {
            throw new RuntimeException('Terapia non trovata o non accessibile.');
        }
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
        
        // Filtra per farmacia
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
        // JOIN con therapies per verificare l'ownership
        $sql = "SELECT c.* FROM `{$this->checksTable}` c ";
        if ($this->checkCols['therapy'] && $this->therapyCols['pharmacy']) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON c.`{$this->checkCols['therapy']}` = t.`{$this->therapyCols['id']}` ";
            $sql .= "WHERE c.`{$this->checkCols['id']}` = ? AND t.`{$this->therapyCols['pharmacy']}` = ?";
            $params = [$checkId, $this->pharmacyId];
        } else {
            $sql .= "WHERE c.`{$this->checkCols['id']}` = ?";
            $params = [$checkId];
        }
        
        $check = db_fetch_one($sql, $params);
        if (!$check) {
            throw new RuntimeException('Check periodico non trovato.');
        }
        $formatted = $this->formatCheck($check);
        $answers = $this->getAnswersForChecks([$formatted]);
        if (($formatted['type'] ?? 'execution') !== 'checklist') {
            $formatted['answers'] = $answers[$formatted['id']] ?? [];
        }

        return $formatted;
    }

    public function findReminder(int $reminderId): array
    {
        // JOIN con therapies per verificare l'ownership
        $sql = "SELECT r.* FROM `{$this->remindersTable}` r ";
        if ($this->reminderCols['therapy'] && $this->therapyCols['pharmacy']) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON r.`{$this->reminderCols['therapy']}` = t.`{$this->therapyCols['id']}` ";
            $sql .= "WHERE r.`{$this->reminderCols['id']}` = ? AND t.`{$this->therapyCols['pharmacy']}` = ?";
            $params = [$reminderId, $this->pharmacyId];
        } else {
            $sql .= "WHERE r.`{$this->reminderCols['id']}` = ?";
            $params = [$reminderId];
        }
        
        $reminder = db_fetch_one($sql, $params);
        if (!$reminder) {
            throw new RuntimeException('Promemoria non trovato.');
        }
        return $this->formatReminder($reminder);
    }

    private function listPatients(): array
    {
        $columns = AdesioneTableResolver::columns($this->patientsTable);
        $columnList = implode(', ', array_map(static function ($column) {
            return "p.`$column`";
        }, $columns));

        $sql = "SELECT {$columnList} FROM `{$this->patientsTable}` p";
        $params = [];
        
        // Filtra per pharmacy_id se la colonna esiste
        if ($this->patientCols['pharmacy']) {
            $sql .= " WHERE p.`{$this->patientCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        
        if ($this->patientCols['last_name']) {
            $sql .= " ORDER BY p.`{$this->patientCols['last_name']}`, p.`{$this->patientCols['first_name']}`";
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
        // JOIN con therapies per filtrare per farmacia
        $sql = "SELECT c.* FROM `{$this->checksTable}` c ";
        $params = [];
        $conditions = [];
        
        if ($this->checkCols['therapy'] && $this->therapyCols['pharmacy']) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON c.`{$this->checkCols['therapy']}` = t.`{$this->therapyCols['id']}` ";
            $conditions[] = "t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        
        if ($therapyId && $this->checkCols['therapy']) {
            $conditions[] = "c.`{$this->checkCols['therapy']}` = ?";
            $params[] = $therapyId;
        }
        
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->checkCols['scheduled_at']) {
            $sql .= " ORDER BY c.`{$this->checkCols['scheduled_at']}` DESC";
        }

        $rows = db_fetch_all($sql, $params);
        $checks = array_map([$this, 'formatCheck'], $rows);
        $answersByCheck = $this->getAnswersForChecks($checks);

        foreach ($checks as &$check) {
            if (($check['type'] ?? 'execution') !== 'checklist') {
                $check['answers'] = $answersByCheck[$check['id']] ?? [];
            }
        }

        return $checks;
    }

    private function listReminders(?int $therapyId = null): array
    {
        // JOIN con therapies per filtrare per farmacia
        $sql = "SELECT r.* FROM `{$this->remindersTable}` r ";
        $params = [];
        $conditions = [];
        
        if ($this->reminderCols['therapy'] && $this->therapyCols['pharmacy']) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON r.`{$this->reminderCols['therapy']}` = t.`{$this->therapyCols['id']}` ";
            $conditions[] = "t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        
        if ($therapyId && $this->reminderCols['therapy']) {
            $conditions[] = "r.`{$this->reminderCols['therapy']}` = ?";
            $params[] = $therapyId;
        }
        
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->reminderCols['scheduled_at']) {
            $sql .= " ORDER BY r.`{$this->reminderCols['scheduled_at']}` ASC";
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
        if (!$this->assistantPivotCols['therapy'] || !$this->assistantPivotCols['assistant']) {
            return [];
        }

        $sql = "SELECT a.*, ta.`{$this->assistantPivotCols['role']}` AS pivot_role 
                FROM `{$this->assistantPivotTable}` ta 
                JOIN `{$this->assistantsTable}` a ON ta.`{$this->assistantPivotCols['assistant']}` = a.`{$this->assistantCols['id']}` 
                WHERE ta.`{$this->assistantPivotCols['therapy']}` = ?";
        $params = [$therapyId];
        if ($this->assistantCols['pharmacy']) {
            $sql .= " AND a.`{$this->assistantCols['pharmacy']}` = ?";
            $params[] = $this->pharmacyId;
        }
        if ($this->assistantCols['created_at']) {
            $sql .= " ORDER BY a.`{$this->assistantCols['created_at']}` DESC";
        }

        $rows = db_fetch_all($sql, $params);
        return array_map([$this, 'formatCaregiver'], $rows);
    }

    private function syncCaregivers(int $therapyId, int $patientId, array $caregivers): void
    {
        if (!$this->assistantPivotCols['therapy'] || !$this->assistantPivotCols['assistant']) {
            return;
        }

        db_query(
            "DELETE FROM `{$this->assistantPivotTable}` WHERE `{$this->assistantPivotCols['therapy']}` = ?",
            [$therapyId]
        );

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
                $data[$this->assistantCols['phone']] = $this->
            
                clean($caregiver['phone'] ?? '');
            }
            if ($this->assistantCols['email'] && isset($caregiver['email'])) {
                $data[$this->assistantCols['email']] = $this->clean($caregiver['email']);
            }
            if ($this->assistantCols['created_at']) {
                $data[$this->assistantCols['created_at']] = $this->now();
            }

            // Salta caregiver vuoti
            $hasData = !empty($data[$this->assistantCols['first_name']]) || 
                       !empty($data[$this->assistantCols['last_name']]) || 
                       !empty($data[$this->assistantCols['phone']]);
            
            if (!$hasData) {
                continue;
            }

            db()->insert($this->assistantsTable, AdesioneTableResolver::filterData($this->assistantsTable, $data));
            $assistantId = (int)db()->lastInsertId();

            $pivotData = [
                $this->assistantPivotCols['therapy'] => $therapyId,
                $this->assistantPivotCols['assistant'] => $assistantId,
                $this->assistantPivotCols['role'] => $type,
            ];
            if ($this->assistantPivotCols['created_at']) {
                $pivotData[$this->assistantPivotCols['created_at']] = $this->now();
            }

            db()->insert($this->assistantPivotTable, AdesioneTableResolver::filterData($this->assistantPivotTable, $pivotData));
        }
    }

    private function storeQuestionnaire(int $therapyId, array $answers): void
    {
        if (!is_array($answers) || empty($answers)) {
            return;
        }

        if (!$this->questionnaireCols['therapy'] || !$this->questionnaireCols['question'] || !$this->questionnaireCols['answer']) {
            error_log('[AdesioneTerapie] Colonne questionario non risolte, salvataggio saltato');
            return;
        }

        try {
            AdesioneTableResolver::columns($this->questionnairesTable);
        } catch (Throwable $e) {
            error_log('[AdesioneTerapie] Tabella questionario non disponibile: ' . $e->getMessage());
            return;
        }

        db()->delete($this->questionnairesTable, "{$this->questionnaireCols['therapy']} = ?", [$therapyId]);

        foreach ($answers as $step => $stepAnswers) {
            if (!is_array($stepAnswers)) {
                continue;
            }

            $stepKey = $this->clean((string)$step);
            foreach ($stepAnswers as $questionKey => $answer) {
                $cleanAnswer = $this->clean($answer ?? '');
                if ($cleanAnswer === '') {
                    continue;
                }

                $questionValue = $stepKey . '|' . $this->clean((string)$questionKey);
                $row = [
                    $this->questionnaireCols['therapy'] => $therapyId,
                    $this->questionnaireCols['question'] => $questionValue,
                    $this->questionnaireCols['answer'] => $cleanAnswer,
                ];

                if ($this->questionnaireCols['created_at']) {
                    $row[$this->questionnaireCols['created_at']] = $this->now();
                }
                if ($this->questionnaireCols['updated_at']) {
                    $row[$this->questionnaireCols['updated_at']] = $this->now();
                }

                db()->insert($this->questionnairesTable, AdesioneTableResolver::filterData($this->questionnairesTable, $row));
            }
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
        $data[$this->consentCols['therapy']] = $therapyId;

        if ($this->consentCols['signer_name']) {
            $data[$this->consentCols['signer_name']] = $this->clean($payload['signer_name'] ?? '');
        }
        if ($this->consentCols['signer_relation']) {
            $data[$this->consentCols['signer_relation']] = $this->clean($payload['signer_relation'] ?? 'patient');
        }
        if ($this->consentCols['consent_text']) {
            $text = $payload['consent_text'] ?? '';
            $data[$this->consentCols['consent_text']] = $this->clean($text !== '' ? $text : 'Consenso informato registrato');
        }
        if ($this->consentCols['signature_image'] && !empty($payload['signature_image'])) {
            $signatureBinary = $this->normalizeSignatureImage($payload['signature_image']);
            if ($signatureBinary !== null) {
                $data[$this->consentCols['signature_image']] = $signatureBinary;
            }
        }
        if ($this->consentCols['ip']) {
            $data[$this->consentCols['ip']] = $this->clean($payload['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
        }
        if ($this->consentCols['signed_at']) {
            $data[$this->consentCols['signed_at']] = $payload['signed_at'] ?? $this->now();
        }
        if ($this->consentCols['created_at'] && !$existing) {
            $data[$this->consentCols['created_at']] = $this->now();
        }

        $filtered = AdesioneTableResolver::filterData($this->consentsTable, $data);
        
        if ($existing) {
            $existingId = $existing[$this->consentCols['id']] ?? $existing['id'] ?? null;
            if ($existingId) {
                db()->update($this->consentsTable, $filtered, "{$this->consentCols['id']} = ?", [$existingId]);
            }
        } else {
            db()->insert($this->consentsTable, $filtered);
        }
    }

    private function getQuestionnaire(int $therapyId): array
    {
        if (!$this->questionnaireCols['therapy']) {
            return [];
        }
        $rows = db_fetch_all(
            "SELECT * FROM `{$this->questionnairesTable}` WHERE `{$this->questionnaireCols['therapy']}` = ? ORDER BY `{$this->questionnaireCols['id']}` ASC",
            [$therapyId]
        );

        if (!$rows) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $questionRaw = $row[$this->questionnaireCols['question']] ?? '';
            $answerValue = $row[$this->questionnaireCols['answer']] ?? '';
            $step = '1';
            $questionKey = $questionRaw;

            if (str_contains($questionRaw, '|')) {
                [$step, $questionKey] = explode('|', $questionRaw, 2);
            }

            if (!isset($result[$step])) {
                $result[$step] = [];
            }
            $result[$step][$questionKey] = $answerValue;
        }

        return $result;
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

    private function findChecklistId(int $therapyId): ?int
    {
        if (!$this->checkCols['therapy']) {
            return null;
        }
        $rows = db_fetch_all(
            "SELECT * FROM `{$this->checksTable}` WHERE `{$this->checkCols['therapy']}` = ? ORDER BY `{$this->checkCols['id']}` DESC",
            [$therapyId]
        );
        foreach ($rows as $row) {
            $notes = $this->checkCols['notes'] ? ($row[$this->checkCols['notes']] ?? '') : '';
            $decoded = json_decode($notes, true);
            if (is_array($decoded) && (!empty($decoded['questions']) || ($decoded['type'] ?? '') === 'checklist')) {
                return (int)($row[$this->checkCols['id']] ?? 0);
            }
        }
        return null;
    }

    private function normalizeChecklistQuestions(array $questions): array
    {
        $normalized = [];
        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                continue;
            }
            $text = trim((string)($question['text'] ?? $question['label'] ?? ''));
            if ($text === '') {
                continue;
            }
            $key = trim((string)($question['key'] ?? ''));
            if ($key === '') {
                $key = $this->slugifyKey($text, $index);
            }
            $type = strtolower((string)($question['type'] ?? 'text'));
            if (!in_array($type, ['text', 'boolean', 'number'], true)) {
                $type = 'text';
            }

            $normalized[] = [
                'key' => $key,
                'text' => $text,
                'type' => $type,
            ];
        }

        return $normalized;
    }

    private function slugifyKey(string $value, int $fallbackIndex = 0): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';
        $value = trim((string)$value, '-');
        if ($value === '') {
            $value = 'q' . ($fallbackIndex + 1);
        }
        return $value;
    }

    private function normalizeCheckAnswers($answers): array
    {
        if (!is_array($answers)) {
            return [];
        }
        $normalized = [];
        foreach ($answers as $key => $answer) {
            $questionKey = is_string($key) ? trim($key) : (is_array($answer) ? trim((string)($answer['question'] ?? '')) : '');
            if ($questionKey === '') {
                continue;
            }
            if (is_array($answer)) {
                $value = $answer['answer'] ?? ($answer['value'] ?? '');
            } else {
                $value = $answer;
            }
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $normalized[$questionKey] = trim((string)$value);
        }
        return $normalized;
    }

    private function storeCheckAnswers(int $checkId, array $answers): void
    {
        if (!$checkId || !$this->checkAnswerCols['check'] || !$this->checkAnswerCols['question'] || !$this->checkAnswerCols['answer']) {
            return;
        }

        db()->delete($this->checkAnswersTable, "`{$this->checkAnswerCols['check']}` = ?", [$checkId]);

        foreach ($answers as $question => $answer) {
            if ($answer === '') {
                continue;
            }
            $payload = [
                $this->checkAnswerCols['check'] => $checkId,
                $this->checkAnswerCols['question'] => $question,
                $this->checkAnswerCols['answer'] => $answer,
            ];
            if ($this->checkAnswerCols['created_at']) {
                $payload[$this->checkAnswerCols['created_at']] = $this->now();
            }
            $filtered = AdesioneTableResolver::filterData($this->checkAnswersTable, $payload);
            db()->insert($this->checkAnswersTable, $filtered);
        }
    }

    private function getAnswersForChecks(array $checks): array
    {
        if (empty($checks) || !$this->checkAnswerCols['check']) {
            return [];
        }
        $ids = array_unique(array_filter(array_map(static function ($check) {
            return (int)($check['id'] ?? 0);
        }, $checks)));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = db_fetch_all(
            "SELECT * FROM `{$this->checkAnswersTable}` WHERE `{$this->checkAnswerCols['check']}` IN ({$placeholders}) ORDER BY `{$this->checkAnswerCols['id']}` ASC",
            $ids
        );

        $grouped = [];
        foreach ($rows as $row) {
            $checkId = (int)($row[$this->checkAnswerCols['check']] ?? 0);
            $grouped[$checkId][] = [
                'id' => $this->checkAnswerCols['id'] ? (int)($row[$this->checkAnswerCols['id']] ?? 0) : 0,
                'question' => $row[$this->checkAnswerCols['question']] ?? '',
                'answer' => $row[$this->checkAnswerCols['answer']] ?? '',
                'created_at' => $this->checkAnswerCols['created_at'] ? ($row[$this->checkAnswerCols['created_at']] ?? null) : null,
            ];
        }

        return $grouped;
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
            'title' => $this->therapyCols['title'] ? ($therapy[$this->therapyCols['title']] ?? '') : '',
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
        $assessment = '';
        $notesText = '';
        $actions = '';
        $type = 'execution';
        $questions = [];
        $rawNotes = $this->checkCols['notes'] ? ($check[$this->checkCols['notes']] ?? '') : '';
        $decoded = json_decode($rawNotes, true);
        if (is_array($decoded)) {
            $type = $decoded['type'] ?? ($decoded['questions'] ?? false ? 'checklist' : 'execution');
            $assessment = $decoded['assessment'] ?? '';
            $notesText = $decoded['notes'] ?? '';
            $actions = $decoded['actions'] ?? '';
            if (!empty($decoded['questions']) && is_array($decoded['questions'])) {
                $questions = $this->normalizeChecklistQuestions($decoded['questions']);
            }
        } elseif ($rawNotes !== '') {
            $assessment = $rawNotes;
            $notesText = $rawNotes;
        }

        return [
            'id' => (int)($check[$this->checkCols['id']] ?? 0),
            'therapy_id' => $this->checkCols['therapy'] ? (int)($check[$this->checkCols['therapy']] ?? 0) : 0,
            'scheduled_at' => $this->checkCols['scheduled_at'] ? ($check[$this->checkCols['scheduled_at']] ?? null) : null,
            'assessment' => $assessment,
            'notes' => $notesText,
            'actions' => $actions,
            'type' => $type,
            'questions' => $questions,
        ];
    }

    private function formatReminder(array $reminder): array
    {
        $messageRaw = $this->reminderCols['message'] ? ($reminder[$this->reminderCols['message']] ?? '') : '';
        $decoded = json_decode($messageRaw, true);
        $messageText = $decoded['text'] ?? ($messageRaw ?? '');
        $type = $decoded['type'] ?? 'one-shot';
        $recurrence = $decoded['recurrence'] ?? '';

        return [
            'id' => (int)($reminder[$this->reminderCols['id']] ?? 0),
            'therapy_id' => $this->reminderCols['therapy'] ? (int)($reminder[$this->reminderCols['therapy']] ?? 0) : 0,
            'title' => $this->reminderCols['title'] ? ($reminder[$this->reminderCols['title']] ?? 'Promemoria terapia') : 'Promemoria terapia',
            'type' => $type,
            'message' => $messageText,
            'scheduled_at' => $this->reminderCols['scheduled_at'] ? ($reminder[$this->reminderCols['scheduled_at']] ?? null) : null,
            'recurrence_rule' => $recurrence,
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
        $type = $caregiver[$this->assistantCols['type']] ?? $caregiver['pivot_role'] ?? 'familiare';
        $firstName = $this->assistantCols['first_name'] ? ($caregiver[$this->assistantCols['first_name']] ?? '') : '';
        $lastName = $this->assistantCols['last_name'] ? ($caregiver[$this->assistantCols['last_name']] ?? '') : '';
        $fullName = trim($firstName . ' ' . $lastName);

        return [
            'id' => (int)($caregiver[$this->assistantCols['id']] ?? 0),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $fullName !== '' ? $fullName : ($firstName !== '' ? $firstName : $lastName),
            'type' => $this->assistantCols['type'] ? $type : '',
            'phone' => $this->assistantCols['phone'] ? ($caregiver[$this->assistantCols['phone']] ?? '') : '',
            'email' => $this->assistantCols['email'] ? ($caregiver[$this->assistantCols['email']] ?? '') : '',
            'relationship' => $type,
        ];
    }

    private function formatConsent(array $consent): array
    {
        return [
            'signer_name' => $this->consentCols['signer_name'] ? ($consent[$this->consentCols['signer_name']] ?? '') : '',
            'signer_relation' => $this->consentCols['signer_relation'] ? ($consent[$this->consentCols['signer_relation']] ?? '') : '',
            'consent_text' => $this->consentCols['consent_text'] ? ($consent[$this->consentCols['consent_text']] ?? '') : '',
            'signature_image' => $this->consentCols['signature_image'] ? $this->buildSignatureDataUrl($consent[$this->consentCols['signature_image']] ?? '') : '',
            'ip_address' => $this->consentCols['ip'] ? ($consent[$this->consentCols['ip']] ?? '') : '',
            'signed_at' => $this->consentCols['signed_at'] ? ($consent[$this->consentCols['signed_at']] ?? '') : '',
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

    private function normalizeSignatureImage(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $clean = trim($raw);
        if (str_contains($clean, ',')) {
            [$maybeMeta, $maybeData] = explode(',', $clean, 2);
            if (str_starts_with($maybeMeta, 'data:image')) {
                $clean = $maybeData;
            }
        }

        $binary = base64_decode($clean, true);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('Formato della firma non valido.');
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            throw new RuntimeException('La firma non è un\'immagine valida.');
        }

        return $binary;
    }

    private function buildSignatureDataUrl(?string $stored): string
    {
        if (!$stored) {
            return '';
        }

        $info = @getimagesizefromstring($stored);
        if ($info === false) {
            return '';
        }

        $mime = $info['mime'] ?? 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($stored);
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