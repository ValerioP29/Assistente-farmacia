<?php
require_once __DIR__ . '/../models/TableResolver.php';

class AdesioneTerapieController
{
    private int $pharmacyId;

    private string $patientsTable;
    private string $therapiesTable;
    private string $assistantsTable;
    private string $therapyAssistTable;
    private string $questionnaireTable;
    private string $checksTable;
    private string $remindersTable;
    private string $reportsTable;
    private string $consentsTable;

    public function __construct(int $pharmacyId)
    {
        $this->pharmacyId = $pharmacyId;

        $this->patientsTable       = 'jta_patients';
        $this->therapiesTable     = 'jta_therapies';
        $this->assistantsTable    = 'jta_assistants';
        $this->therapyAssistTable = 'jta_therapy_assistant';
        $this->questionnaireTable = 'jta_therapy_questionnaire';
        $this->checksTable        = 'jta_therapy_checks';
        $this->remindersTable     = 'jta_therapy_reminders';
        $this->reportsTable       = 'jta_therapy_reports';
        $this->consentsTable      = 'jta_therapy_consents';
    }

        public function getInitialData(): array
    {
        // Pazienti (collegati via jta_pharma_patient)
        $patients = $this->listPatients();

        // Terapie complete (con caregivers, questionario JSON, consensi, checks, reminders, reports)
        $therapies = $this->listTherapies();

        // Check periodici di TUTTE le terapie della farmacia
        $checks = $this->listChecks();

        // Promemoria di TUTTE le terapie della farmacia
        $reminders = $this->listReminders();

        // Report condivisi
        $reports = $this->listReports();

        // Timeline cronologica combinata
        $timeline = $this->buildTimeline($checks, $reminders);

        // Statistiche rapide
        $stats = [
            'patients'  => count($patients),
            'therapies' => count($therapies),
            'checks'    => count($checks),
            'reminders' => count($reminders),
        ];

        return [
            'patients'  => $patients,
            'therapies' => $therapies,
            'checks'    => $checks,
            'reminders' => $reminders,
            'reports'   => $reports,
            'timeline'  => $timeline,
            'stats'     => $stats,
        ];
    }

    /* ---------------------------------------------------
     * LISTA PAZIENTI (jta_pharma_patient)
     * --------------------------------------------------- */
    private function listPatients(): array
    {
        $sql = "
            SELECT p.*
            FROM jta_patients p
            JOIN jta_pharma_patient pp ON pp.patient_id = p.id
            WHERE pp.pharma_id = ?
              AND pp.deleted_at IS NULL
            ORDER BY p.last_name, p.first_name
        ";

        $rows = db_fetch_all($sql, [$this->pharmacyId]);

        foreach ($rows as &$p) {
            $p = [
                'id' => (int)$p['id'],
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'full_name' => trim($p['first_name'].' '.$p['last_name']),
                'phone' => $p['phone'],
                'email' => $p['email'],
                'birth_date' => $p['birth_date'],
                'notes' => $p['note'],
            ];
        }
        return $rows;
    }

    /* ---------------------------------------------------
     * LISTA TERAPIE
     * --------------------------------------------------- */
    private function listTherapies(): array
    {
        $sql = "
            SELECT t.*, 
                p.first_name AS patient_first_name,
                p.last_name AS patient_last_name,
                p.phone AS patient_phone,
                p.email AS patient_email
            FROM {$this->therapiesTable} t
            JOIN jta_patients p ON p.id = t.patient_id
            WHERE t.pharma_id = ?
            ORDER BY t.start_date DESC
        ";

        $rows = db_fetch_all($sql, [$this->pharmacyId]);

        foreach ($rows as &$t) {
            $t = $this->formatTherapy($t);
            $t['caregivers'] = $this->listCaregivers($t['id']);
            $t['questionnaire'] = $this->getQuestionnaire($t['id']);
            $t['consent'] = $this->getConsent($t['id']);
            $t['checks'] = $this->listChecks($t['id']);
            $t['reminders'] = $this->listReminders($t['id']);
            $t['reports'] = $this->listReports($t['id']);
        }

        return $rows;
    }

    /* ---------------------------------------------------
     * TROVA TERAPIA
     * --------------------------------------------------- */
    public function findTherapy(int $therapyId): array
    {
        $sql = "
            SELECT t.*,
                p.first_name AS patient_first_name,
                p.last_name AS patient_last_name,
                p.phone AS patient_phone,
                p.email AS patient_email
            FROM {$this->therapiesTable} t
            JOIN jta_patients p ON p.id = t.patient_id
            WHERE t.id = ? AND t.pharma_id = ?
            LIMIT 1
        ";

        $row = db_fetch_one($sql, [$therapyId, $this->pharmacyId]);
        if (!$row) {
            throw new RuntimeException("Terapia non trovata.");
        }

        $t = $this->formatTherapy($row);
        $t['caregivers'] = $this->listCaregivers($therapyId);
        $t['questionnaire'] = $this->getQuestionnaire($therapyId);
        $t['consent'] = $this->getConsent($therapyId);
        $t['checks'] = $this->listChecks($therapyId);
        $t['reminders'] = $this->listReminders($therapyId);
        $t['reports'] = $this->listReports($therapyId);

        return $t;
    }

    /* ---------------------------------------------------
     * FORMATTING
     * --------------------------------------------------- */
    private function formatTherapy(array $t): array
    {
        return [
            'id' => (int)$t['id'],
            'patient_id' => (int)$t['patient_id'],
            'patient_name' => trim($t['patient_first_name'].' '.$t['patient_last_name']),
            'patient_phone' => $t['patient_phone'],
            'patient_email' => $t['patient_email'],

            'title' => $t['therapy_title'],
            'description' => $t['therapy_description'],
            'start_date' => $t['start_date'],
            'end_date' => $t['end_date'],
            'active' => (int)$t['active'],
        ];
    }
    /* ---------------------------------------------------
     * CAREGIVERS (assistants + pivot)
     * --------------------------------------------------- */
    private function listCaregivers(int $therapyId): array
    {
        $sql = "
            SELECT a.*
            FROM {$this->therapyAssistTable} ta
            JOIN {$this->assistantsTable} a ON a.id = ta.assistant_id
            WHERE ta.therapy_id = ?
        ";

        $rows = db_fetch_all($sql, [$therapyId]);

        foreach ($rows as &$cg) {
            $cg = [
                'id' => (int)$cg['id'],
                'first_name' => $cg['first_name'],
                'last_name' => $cg['last_name'],
                'full_name' => trim($cg['first_name'].' '.$cg['last_name']),
                'phone' => $cg['phone'],
                'email' => $cg['email'],
                'type' => $cg['type'],
                'note' => $cg['note'],
            ];
        }

        return $rows;
    }

    private function syncCaregivers(int $therapyId, array $caregivers): void
    {
        db_query("DELETE FROM {$this->therapyAssistTable} WHERE therapy_id = ?", [$therapyId]);

        foreach ($caregivers as $cg) {
            $first = trim($cg['first_name'] ?? '');
            $last  = trim($cg['last_name'] ?? '');

            if ($first === '' && $last === '') {
                continue;
            }

            $assistantId = db()->insert($this->assistantsTable, [
                'pharma_id' => $this->pharmacyId,
                'first_name' => $first,
                'last_name' => $last,
                'phone' => $cg['phone'] ?? '',
                'email' => $cg['email'] ?? '',
                'type' => $cg['type'] ?? 'familiare',
                'note' => $cg['note'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            db()->insert($this->therapyAssistTable, [
                'therapy_id' => $therapyId,
                'assistant_id' => $assistantId,
                'role' => $cg['type'] ?? 'familiare'
            ]);
        }
    }

    /* ---------------------------------------------------
     * QUESTIONARIO (domande/risposte)
     * --------------------------------------------------- */
    private function getQuestionnaire(int $therapyId): array
    {
        $sql = "
            SELECT question, answer
            FROM {$this->questionnaireTable}
            WHERE therapy_id = ?
            ORDER BY id ASC
        ";

        $rows = db_fetch_all($sql, [$therapyId]);
        if (!$rows) {
            return [];
        }

        $structured = [];

        foreach ($rows as $row) {
            $questionRaw = (string)($row['question'] ?? '');
            $answer = $this->clean($row['answer'] ?? '');

            if ($questionRaw === '' || $answer === '') {
                continue;
            }

            $step = '1';
            $questionKey = $questionRaw;

            if (str_contains($questionRaw, ':')) {
                [$stepPart, $questionKey] = explode(':', $questionRaw, 2);
                $stepNumber = (int)$stepPart;
                $step = $stepNumber > 0 ? (string)$stepNumber : '1';
                $questionKey = $questionKey !== '' ? $questionKey : $questionRaw;
            }

            $structured[$step][$questionKey] = $answer;
        }

        return $structured;
    }

    private function storeQuestionnaire(int $therapyId, array $answers): void
    {
        if (!$this->hasQuestionnaireAnswers($answers)) {
            return;
        }

        db_query("DELETE FROM {$this->questionnaireTable} WHERE therapy_id = ?", [$therapyId]);

        $now = $this->now();

        foreach ($answers as $step => $stepAnswers) {
            if (!is_array($stepAnswers)) {
                continue;
            }

            $stepNumber = is_numeric($step) ? (int)$step : 1;
            $stepPrefix = $stepNumber > 0 ? (string)$stepNumber : '1';

            foreach ($stepAnswers as $questionKey => $answer) {
                $cleanAnswer = trim((string)$answer);
                if ($cleanAnswer === '') {
                    continue;
                }

                $questionLabel = $stepPrefix . ':' . (string)$questionKey;

                db()->insert($this->questionnaireTable, [
                    'therapy_id' => $therapyId,
                    'question' => $questionLabel,
                    'answer' => $cleanAnswer,
                    'created_at' => $now,
                ]);
            }
        }
    }

    /* ---------------------------------------------------
     * CONSENSO (firma grafica o testo)
     * --------------------------------------------------- */
    private function getConsent(int $therapyId): array
    {
        $sql = "
            SELECT *
            FROM {$this->consentsTable}
            WHERE therapy_id = ?
            ORDER BY id DESC
            LIMIT 1
        ";

        $row = db_fetch_one($sql, [$therapyId]);
        if (!$row) {
            return [];
        }

        return [
            'signer_name' => $row['signer_name'],
            'signer_relation' => $row['signer_relation'],
            'consent_text' => $row['consent_text'],
            'signed_at' => $row['signed_at'],
            'ip_address' => $row['ip_address'],
            'signature_image' => $row['signature_image'],
        ];
    }

    private function storeConsent(int $therapyId, array $payload): void
    {
        $signerName = trim($payload['signer_name'] ?? '');
        $relation   = trim($payload['signer_relation'] ?? '');
        $text       = trim($payload['consent_text'] ?? '');
        $img        = $payload['signature_image'] ?? '';

        if ($signerName === '' && $text === '' && $img === '') {
            return;
        }

        db()->insert($this->consentsTable, [
            'therapy_id' => $therapyId,
            'signer_name' => $signerName,
            'signer_relation' => $relation ?: 'patient',
            'consent_text' => $text,
            'signature_image' => $img,
            'signed_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    }

    /* ---------------------------------------------------
     * CHECK PERIODICI
     * --------------------------------------------------- */
    private function listChecks(?int $therapyId = null): array
    {
        $sql = "
            SELECT c.*
            FROM {$this->checksTable} c
            JOIN {$this->therapiesTable} t ON t.id = c.therapy_id
            WHERE t.pharma_id = ?
        ";

        $params = [$this->pharmacyId];

        if ($therapyId !== null) {
            $sql .= " AND c.therapy_id = ?";
            $params[] = $therapyId;
        }

        $sql .= " ORDER BY c.check_date DESC";

        $rows = db_fetch_all($sql, $params);
        return array_map([$this, 'formatCheck'], $rows);
    }


    private function formatCheck(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'therapy_id' => (int)$row['therapy_id'],
            'scheduled_at' => $row['check_date'],
            'notes' => $row['notes'],
        ];
    }


    private function saveCheck(array $payload): array
    {
        $therapyId = (int)($payload['therapy_id'] ?? 0);
        if (!$therapyId) {
            throw new RuntimeException("Terapia mancante per check.");
        }

        $id = isset($payload['check_id']) && $payload['check_id'] !== '' ? (int)$payload['check_id'] : null;

        $date = $this->normalizeDateTime($payload['scheduled_at'] ?? '');
        if (!$date) {
            throw new RuntimeException("Data del check non valida.");
        }

        $data = [
            'therapy_id' => $therapyId,
            'check_date' => $date,
            'notes' => trim($payload['notes'] ?? ''),
        ];

        if ($id) {
            db()->update($this->checksTable, $data, "id = ?", [$id]);
        } else {
            $data['created_at'] = $this->now();
            $id = (int)db()->insert($this->checksTable, $data);
        }

        return $this->findCheck($id);
    }

    private function findCheck(int $id): array
    {
        $sql = "
            SELECT c.*
            FROM {$this->checksTable} c
            JOIN {$this->therapiesTable} t ON t.id = c.therapy_id
            WHERE c.id = ? AND t.pharma_id = ?
            LIMIT 1
        ";

        $row = db_fetch_one($sql, [$id, $this->pharmacyId]);
        if (!$row) {
            throw new RuntimeException("Check non trovato.");
        }

        return [
            'id' => (int)$row['id'],
            'therapy_id' => (int)$row['therapy_id'],
            'scheduled_at' => $row['check_date'],
            'notes' => $row['notes'],
        ];
    }
    /* ---------------------------------------------------
     * PROMEMORIA (jta_therapy_reminders)
     * --------------------------------------------------- */
    private function listReminders(?int $therapyId = null): array
    {
        $sql = "
            SELECT r.*
            FROM {$this->remindersTable} r
            JOIN {$this->therapiesTable} t ON t.id = r.therapy_id
            WHERE t.pharma_id = ?
        ";

        $params = [$this->pharmacyId];

        if ($therapyId !== null) {
            $sql .= " AND r.therapy_id = ?";
            $params[] = $therapyId;
        }

        $sql .= " ORDER BY r.scheduled_at ASC";

        $rows = db_fetch_all($sql, $params);

        foreach ($rows as &$r) {
            $r = [
                'id' => (int)$r['id'],
                'therapy_id' => (int)$r['therapy_id'],
                'title' => $r['title'],
                'message' => $r['message'],
                'scheduled_at' => $r['scheduled_at'],
                'channel' => $r['channel'],
                'status' => $r['status'],
            ];
        }

        return $rows;
    }

    private function findReminder(int $id): array
    {
        $row = db_fetch_one("SELECT * FROM {$this->remindersTable} WHERE id = ?", [$id]);
        if (!$row) {
            throw new RuntimeException("Promemoria non trovato.");
        }

        return [
            'id' => (int)$row['id'],
            'therapy_id' => (int)$row['therapy_id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'scheduled_at' => $row['scheduled_at'],
            'channel' => $row['channel'],
            'status' => $row['status'],
        ];
    }

    private function saveReminder(array $payload): array
    {
        $therapyId = (int)($payload['therapy_id'] ?? 0);
        if (!$therapyId) {
            throw new RuntimeException("Terapia mancante per promemoria.");
        }

        $id = isset($payload['reminder_id']) && $payload['reminder_id'] !== '' ? (int)$payload['reminder_id'] : null;

        $date = trim($payload['scheduled_at'] ?? '');
        if ($date === '') {
            throw new RuntimeException("Data promemoria mancante.");
        }

        $data = [
            'therapy_id' => $therapyId,
            'title' => trim($payload['title'] ?? ''),
            'message' => trim($payload['message'] ?? ''),
            'scheduled_at' => $date,
            'channel' => trim($payload['channel'] ?? 'email'),
            'status' => trim($payload['status'] ?? 'scheduled'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            db()->update($this->remindersTable, $data, "id = ?", [$id]);
        } else {
            $id = (int)db()->insert($this->remindersTable, $data);
        }

        return $this->findReminder($id);
    }

    /* ---------------------------------------------------
     * REPORT (JSON)
     * --------------------------------------------------- */
    private function listReports(?int $therapyId = null): array
    {
        $sql = "
            SELECT *
            FROM {$this->reportsTable}
            WHERE pharma_id = ?
        ";

        $params = [$this->pharmacyId];

        if ($therapyId !== null) {
            $sql .= " AND therapy_id = ?";
            $params[] = $therapyId;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = db_fetch_all($sql, $params);

        foreach ($rows as &$r) {
            $r['content'] = json_decode($r['content'], true) ?: [];
            $r['pin_required'] = !empty($r['pin_code']);
            $r['url'] = $this->buildReportUrl($r['share_token'], $r['pin_required']);
        }

        return $rows;
    }

    public function generateReport(array $payload): array
    {
        $therapyId = (int)($payload['therapy_id'] ?? 0);
        if (!$therapyId) {
            throw new RuntimeException("Terapia mancante.");
        }

        $includeChecks = !empty($payload['include_checks']);
        $pin = trim($payload['pin_code'] ?? '');
        $valid = trim($payload['valid_until'] ?? '');

        $therapy = $this->findTherapy($therapyId);
        $checks  = $includeChecks ? $this->listChecks($therapyId) : [];

        $content = [
            'generated_at' => date('Y-m-d H:i:s'),
            'therapy' => $therapy,
            'checks' => $checks,
        ];

        $token = bin2hex(random_bytes(16));
        $data = [
            'therapy_id' => $therapyId,
            'pharma_id' => $this->pharmacyId,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            'share_token' => $token,
            'pin_code' => $pin !== '' ? password_hash($pin, PASSWORD_DEFAULT) : null,
            'valid_until' => $valid !== '' ? ($valid . ' 23:59:59') : null,
            'recipients' => trim($payload['recipients'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $id = (int)db()->insert($this->reportsTable, $data);

        return [
            'id' => $id,
            'token' => $token,
            'url' => $this->buildReportUrl($token, $pin !== ''),
            'pin_required' => $pin !== ''
        ];
    }

    /* ---------------------------------------------------
     * SALVATAGGIO PAZIENTE (crea + associa farmacia)
     * --------------------------------------------------- */
    public function savePatient(array $payload): array
    {
        $id = isset($payload['patient_id']) && $payload['patient_id'] !== ''
            ? (int)$payload['patient_id']
            : null;

        $data = [
            'first_name' => trim($payload['first_name'] ?? ''),
            'last_name' => trim($payload['last_name'] ?? ''),
            'birth_date' => trim($payload['birth_date'] ?? '') ?: null,
            'phone' => trim($payload['phone'] ?? ''),
            'email' => trim($payload['email'] ?? ''),
            'note' => trim($payload['notes'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            db()->update($this->patientsTable, $data, "id = ?", [$id]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = (int)db()->insert($this->patientsTable, $data);

            db()->insert("jta_pharma_patient", [
                'pharma_id' => $this->pharmacyId,
                'patient_id' => $id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->findPatient($id);
    }

    public function findPatient(int $id): array
    {
        $sql = "
            SELECT p.*
            FROM jta_patients p
            JOIN jta_pharma_patient pp ON pp.patient_id = p.id
            WHERE p.id = ? AND pp.pharma_id = ? AND pp.deleted_at IS NULL
            LIMIT 1
        ";

        $p = db_fetch_one($sql, [$id, $this->pharmacyId]);
        if (!$p) {
            throw new RuntimeException("Paziente non trovato.");
        }

        return [
            'id' => (int)$p['id'],
            'first_name' => $p['first_name'],
            'last_name' => $p['last_name'],
            'full_name' => trim($p['first_name'].' '.$p['last_name']),
            'phone' => $p['phone'],
            'email' => $p['email'],
            'birth_date' => $p['birth_date'],
            'notes' => $p['note'],
        ];
    }

    /* ---------------------------------------------------
     * SALVATAGGIO TERAPIA (crea / aggiorna)
     * --------------------------------------------------- */
    public function saveTherapy(array $payload): array
    {
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== ''
            ? (int)$payload['therapy_id']
            : null;

        $patientId = (int)($payload['patient_id'] ?? 0);
        if (!$patientId && !empty($payload['existing_patient'])) {
            $patientId = (int)$payload['existing_patient'];
        }

        if (!$patientId) {
            $patient = $this->savePatient([
                'first_name' => $payload['inline_first_name'] ?? '',
                'last_name' => $payload['inline_last_name'] ?? '',
                'phone' => $payload['inline_phone'] ?? '',
                'email' => $payload['inline_email'] ?? '',
                'birth_date' => $payload['inline_birth_date'] ?? null,
                'notes' => $payload['inline_notes'] ?? '',
            ]);
            $patientId = $patient['id'];
        }

        $data = [
            'pharma_id' => $this->pharmacyId,
            'patient_id' => $patientId,
            'therapy_title' => trim($payload['title'] ?? ''),
            'therapy_description' => trim($payload['description'] ?? ''),
            'start_date' => trim($payload['start_date'] ?? '') ?: null,
            'end_date' => trim($payload['end_date'] ?? '') ?: null,
            'active' => isset($payload['status']) && $payload['status'] === 'inactive' ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($therapyId) {
            db()->update($this->therapiesTable, $data, "id = ?", [$therapyId]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $therapyId = (int)db()->insert($this->therapiesTable, $data);
        }

        /* caregivers */
        if (!empty($payload['caregivers_payload'])) {
            $cg = json_decode($payload['caregivers_payload'], true) ?: [];
            $this->syncCaregivers($therapyId, $cg);
        }

        /* questionario */
        if (!empty($payload['questionnaire_payload'])) {
            $q = json_decode($payload['questionnaire_payload'], true) ?: [];
            $this->storeQuestionnaire($therapyId, $q);
        }

        /* consenso */
        $this->storeConsent($therapyId, $payload);

        return $this->findTherapy($therapyId);
    }
    /* ---------------------------------------------------
     * TIMELINE (checks + reminders)
     * --------------------------------------------------- */
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
        $upcoming = array_filter($reminders, static function ($r) {
            if (empty($r['scheduled_at'])) {
                return false;
            }
            return strtotime($r['scheduled_at']) >= time();
        });

        if (empty($upcoming)) {
            return null;
        }

        usort($upcoming, static function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $upcoming[0];
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
                'scheduled_at' => $check['scheduled_at'],
                'details' => $check['notes'] ?? '',
                'therapy_id' => $check['therapy_id'] ?? null,
            ];
        }

        foreach ($reminders as $reminder) {
            if (empty($reminder['scheduled_at'])) {
                continue;
            }

            $timeline[] = [
                'type' => 'reminder',
                'title' => $reminder['title'] ?? 'Promemoria terapia',
                'scheduled_at' => $reminder['scheduled_at'],
                'details' => $reminder['message'] ?? '',
                'therapy_id' => $reminder['therapy_id'] ?? null,
            ];
        }

        usort($timeline, static function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $timeline;
    }

    /* ---------------------------------------------------
     * URL REPORT PUBBLICO
     * --------------------------------------------------- */
    private function buildReportUrl(string $token, bool $protected): string
    {
        // Base URL: se hai APP_URL usalo, altrimenti deduci dal path dello script
        $base = defined('APP_URL') && APP_URL
            ? rtrim(APP_URL, '/') . '/'
            : rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') . '/';

        $relative = 'modules/adesione-terapie/report.php?token=' . urlencode($token);

        if ($protected) {
            $relative .= '&secure=1';
        }

        // Se $base contiene già schema, non toccarlo troppo
        if (preg_match('#^https?://#i', $base)) {
            return rtrim($base, '/') . '/' . ltrim($relative, '/');
        }

        // Fallback URL relativo
        return '/' . ltrim($relative, '/');
    }

    /* ---------------------------------------------------
     * HELPERS DATE / STRINGHE
     * --------------------------------------------------- */
    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    private function normalizeDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = str_replace('T', ' ', trim($value));
        $ts = strtotime($normalized);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function formatDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    private function formatDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
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
