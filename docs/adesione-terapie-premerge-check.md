# Pre-merge verification — Adesione Terapie

> Obiettivo: validare 4 rischi di regressione con riferimenti reali (snippet <= 20 righe). Nessun merge.

## CHECK 1 — Reports: check_followups/manual_followups sempre presenti
**Esito:** ✅

**Verifica payload (preview/generate):** `buildReportContent()` è usato sia per `action=preview` che per `action=generate` e inizializza sempre `check_followups` e `manual_followups` (anche vuoti).

```php
// api/reports.php
$followupData = fetchFollowupsForReport($therapy_id, $pharmacy_id, $mode, $followup_id);
$checkFollowups = array_values(array_filter($followupData, function ($row) {
    $type = $row['entry_type'] ?? null;
    $snapshot = $row['snapshot'] ?? null;
    $hasSnapshot = is_array($snapshot) && !empty($snapshot);
    if ($type === 'check') {
        return true;
    }
    return $type === null && $hasSnapshot;
}));
$manualFollowups = array_values(array_filter($followupData, function ($row) {
    $type = $row['entry_type'] ?? null;
    $snapshot = $row['snapshot'] ?? null;
    $hasSnapshot = is_array($snapshot) && !empty($snapshot);
    if ($type === 'followup') {
        return true;
    }
    return $type === null && !$hasSnapshot;
}));

$reportContent = [
    // ...
    'followups' => $followupData,
    'check_followups' => $checkFollowups,
    'manual_followups' => $manualFollowups
];
```

**Verifica template (no uso di `content.followups`):**
```js
// assets/js/cronico_terapie.js
const checkFollowups = Array.isArray(content.check_followups) ? content.check_followups : [];
const manualFollowups = Array.isArray(content.manual_followups) ? content.manual_followups : [];

const checkHtml = checkFollowups.length
    ? checkFollowups.map((f) => buildFollowupHtml(f, condition)).join('')
    : '<div class="text-muted small">Nessun check periodico</div>';

const followupHtml = manualFollowups.length
    ? manualFollowups.map((f) => buildFollowupHtml(f, condition)).join('')
    : '<div class="text-muted small">Nessun follow-up</div>';
```

```php
// includes/pdf_templates/therapy_report.php
$chronic = $reportData['chronic_care'] ?? [];
$survey = $reportData['survey_base']['answers'] ?? [];
$checkFollowups = $reportData['check_followups'] ?? [];
$manualFollowups = $reportData['manual_followups'] ?? [];
```

**Patch necessaria:** none.

---

## CHECK 2 — Schema: colonne chronic care esistono davvero
**Esito:** ✅

**DDL conferma presenza colonne `doctor_info`, `biometric_info`, `care_context`:**
```sql
-- QUERY.sql
CREATE TABLE IF NOT EXISTS `jta_therapy_chronic_care` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `primary_condition` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `care_context` json DEFAULT NULL,
  `doctor_info` json DEFAULT NULL,
  `general_anamnesis` json DEFAULT NULL,
  `biometric_info` json DEFAULT NULL,
  -- ...
  `consent` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Patch necessaria:** none.

---

## CHECK 3 — Naming pharmacy_id vs pharma_id (pivot e assistants)
**Esito:** ⚠️ (schema incompleto in repo per `jta_pharma_patient`)

**Assistants (schema presente, usa `pharma_id`):**
```sql
-- QUERY.sql
CREATE TABLE IF NOT EXISTS `jta_assistants` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pharma_id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  -- ...
  CONSTRAINT `fk_assistant_pharma`
    FOREIGN KEY (`pharma_id`) REFERENCES `jta_pharmas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Pivot `jta_pharma_patient` (schema non trovato nel repo):**
```php
// api/therapies.php
return db_fetch_one(
    "SELECT p.id FROM jta_patients p JOIN jta_pharma_patient pp ON p.id = pp.patient_id WHERE p.id = ? AND pp.pharma_id = ? AND pp.deleted_at IS NULL",
    [$patient_id, $pharmacy_id]
);
```

**Nota:** non esiste DDL/migration nel repo per `jta_pharma_patient`, quindi non è possibile verificare il nome reale della colonna (`pharma_id` vs `pharmacy_id`) o l’esistenza di `deleted_at`. Nessuna modifica applicata.

**Patch necessaria:** none (serve schema reale).

---

## CHECK 4 — Scoping forte update paziente
**Esito:** ✅

**Update aggiornato (con filtro su `pharmacy_id` o NULL):**
```php
// api/therapies.php
executeQueryWithTypes($pdo, 
    "UPDATE jta_patients SET first_name = ?, last_name = ?, birth_date = ?, codice_fiscale = ?, phone = ?, email = ?, notes = ? WHERE id = ? AND (pharmacy_id = ? OR pharmacy_id IS NULL)",
    [
        $patient['first_name'] ?? '',
        $patient['last_name'] ?? '',
        $patient['birth_date'] ?? null,
        $patient['codice_fiscale'] ?? null,
        $patient['phone'] ?? null,
        $patient['email'] ?? null,
        $patient['notes'] ?? null,
        $patient_id,
        $pharmacy_id
    ]
);
```

**Patch necessaria:** none.
