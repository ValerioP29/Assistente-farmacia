# Flusso dati "Gestione Terapie Cronico" (senza API)

## Convenzioni di progetto
- Backend PHP procedurale con helper `db_query`, `db_fetch_all`, `db_fetch_one` e middleware di autenticazione caricati da `config/database.php`, `includes/functions.php`, `includes/auth_middleware.php`.
- Rotte servite come script PHP dedicati (es. `api/xyz.php`) con dispatch su `$_SERVER['REQUEST_METHOD']`, ma qui non si elencano endpoint specifici.
- Layout pannello: pagine PHP che includono `includes/header.php`, `includes/sidebar.php`, `includes/footer.php` e usano le classi CSS già presenti.

## Flusso end-to-end del wizard
1. **Selezione/creazione paziente (Step 1)**: carica/crea paziente, associa alla farmacia corrente (pivot `jta_pharma_patient`), raccoglie `primary_condition`, `initial_notes`, `general_anamnesis`, `detailed_intake`.
2. **Caregiver/Familiari (Step 2)**: lookup/creazione assistenti, selezione multipla, raccolta preferenze/consensi specifici per ogni assistente.
3. **Questionario aderenza base (Step 3)**: popola `therapyWizardState.adherence_base` (aderenza generale) che confluisce in `jta_therapy_chronic_care.adherence_base`.
4. **Questionario specifico patologia (Step 4)**: struttura dinamica chiave→valore legata a `primary_condition`; confluisce in `jta_therapy_condition_surveys`.
5. **Pianificazione/Rischio (Step 5 - placeholder)**: campi `risk_score`, `flags`, `notes_initial`, `follow_up_date` destinati a `jta_therapy_chronic_care`; includere eventuale note di presa in carico.
6. **Consensi e firme (Step 6)**: consensi obbligatori/facoltativi, dati firmatario, farmacista e luogo; confluisce in `jta_therapy_consents`.
7. **Salvataggio finale**: esecuzione sequenziale degli INSERT/UPDATE su tutte le tabelle collegate (vedi mapping dettagliato sotto). La modalità modifica deve ricaricare i dati dalle tabelle e ricostruire lo state del wizard.

## Payload consolidati
I payload qui descritti sono strutture JSON che il wizard deve generare per le operazioni di creazione (POST) e aggiornamento (PUT) di una terapia cronico. Non sono elencati gli endpoint.

### Payload POST (creazione terapia)
```json
{
  "pharmacy_id": 3,
  "patient": {
    "id": null,
    "first_name": "Mario",
    "last_name": "Rossi",
    "birth_date": "1980-05-12",
    "codice_fiscale": "RSSMRA80E12H501Z",
    "phone": "3331234567",
    "email": "mario@example.com",
    "notes": "note iniziali",
    "gender": "M"
  },
  "primary_condition": "diabete", // select Step 1
  "initial_notes": "testo libero Step 1",
  "therapy": {
    "therapy_title": "Presa in carico paziente cronico – Diabete",
    "therapy_description": "descrizione/riassunto",
    "status": "active",
    "start_date": "2024-06-01",
    "end_date": null
  },
  "chronic_care": {
    "general_anamnesis": {"family_members_count": 3, "has_external_support": true, "education_level": "diploma", "has_caregiver": true},
    "detailed_intake": {"has_helper_for_medication": true, "forgets_medications": "talvolta", "intentional_skips": false},
    "adherence_base": {"forgets_doses": "talvolta", "last_check_date": "2024-06-01", "er_visits_last_year": ">1"},
    "risk_score": 10,
    "flags": {"priority": "alta"},
    "notes_initial": "note farmacista",
    "follow_up_date": "2024-07-15"
  },
  "therapy_assistants": [
    {
      "assistant_id": 2,
      "role": "caregiver",
      "contact_channel": "whatsapp",
      "preferences_json": {"wants_monthly_report": true, "report_channel": "whatsapp"},
      "consents_json": {"questionnaire_satisfaction": "soddisfatto", "can_contact_doctor": true, "can_collect_documents": true}
    },
    {
      "first_name": "Lucia",
      "last_name": "Verdi",
      "type": "familiare",
      "relation_to_patient": "figlia",
      "preferred_contact": "email",
      "phone": "3400000000",
      "notes": "nuova assistente"
    }
  ],
  "condition_survey": {
    "condition_type": "diabete",
    "level": "base",
    "answers": {"q1": "placeholder", "note": "note patologia"},
    "compiled_at": "2024-06-01 10:30:00"
  },
  "consent": {
    "signer_name": "Mario Rossi",
    "signer_relation": "patient",
    "signed_at": "2024-06-01",
    "pharmacist_name": "Dott. Bianchi",
    "place": "Roma",
    "scopes": {
      "care_followup": true,
      "contact_for_reminders": true,
      "anonymous_stats": false,
      "contact_channel_preference": "whatsapp"
    },
    "signer_role": ""
  }
}
```

### Payload PUT (aggiornamento terapia)
- Stessa struttura del POST, con `id` terapia nella query/path e:
  - `patient.id` valorizzato se già esistente; i campi presenti possono essere aggiornati.
  - `therapy_assistants`: includere sia quelli esistenti (con `assistant_id`) sia nuovi da creare (senza `assistant_id`). Il backend dovrà gestire upsert e rimozione associazioni mancanti.
  - `condition_survey.answers` e `adherence_base` sovrascrivono la versione precedente per il livello "base".
  - `consent`: nuovo record o aggiornamento coerente con la terapia.

## Mapping completo dati → tabelle

### jta_patients
- **Insert** se `patient.id` assente allo step 1 con: `first_name`, `last_name`, `birth_date`, `codice_fiscale`, `phone`, `email`, `notes`, eventuale `gender` (se previsto), `pharmacy_id` facoltativo.
- **Update** opzionale dei campi base se il paziente esiste.

### jta_pharma_patient
- **Insert** per associare il paziente alla farmacia corrente (`pharma_id`, `patient_id`, `created_at`). Se già esiste una relazione attiva, nessuna nuova riga.

### jta_therapies
- **Insert** con `pharmacy_id`, `patient_id`, `therapy_title`, `therapy_description` (può includere `initial_notes`), `status` (default `active`), `start_date`, `end_date` opzionale.
- **Update** in modifica per gli stessi campi.

### jta_therapy_chronic_care
- **Insert** con `therapy_id`, `primary_condition` (dallo step 1), `general_anamnesis` (JSON step 1), `detailed_intake` (JSON step 1), `adherence_base` (JSON step 3), `risk_score`, `flags`, `notes_initial`, `follow_up_date`; pronti campi futuri `care_context`, `doctor_info`, `biometric_info` se necessari.
- **Update** in modifica con i medesimi campi JSON e numerici/data.

### jta_assistants
- **Insert** per ogni assistente nuovo allo step 2 con `pharma_id` corrente, `first_name`, `last_name`, `phone`, `email`, `type` (`caregiver`/`familiare`), `relation_to_patient`, `preferred_contact`, `notes`, eventuale `extra_info`.
- Nessun update automatico previsto, salvo gestione manuale.

### jta_therapy_assistant
- **Insert** per ogni assistente selezionato con `therapy_id`, `assistant_id`, `role` (`caregiver`/`familiare`), `contact_channel`, `preferences_json`, `consents_json`.
- **Upsert/cleanup** in modifica: aggiungere nuovi, aggiornare JSON esistenti, rimuovere associazioni non più presenti.

### jta_therapy_condition_surveys
- **Insert** con `therapy_id`, `condition_type = primary_condition`, `level = 'base'`, `answers` JSON chiave→valore, `compiled_at` timestamp.
- **Update/upsert** in modifica per lo stesso livello/patologia.

### jta_therapy_followups (futuro, non nel wizard)
- Non gestito dal wizard; sarà usato da "Check periodico" con `therapy_id`, `risk_score`, `pharmacist_notes`, `education_notes`, `snapshot` JSON, `follow_up_date`.

### jta_therapy_consents
- **Insert** allo step 6 con `therapy_id`, `signer_name`, `signer_relation` (`patient`/`caregiver`/`familiare`), `consent_text` standard, `signed_at`, `ip_address` (se disponibile), `scopes_json` (flag consensi, preferenze contatto, farmacista e luogo), `signer_role` opzionale.
- **Update/nuovo record** in modifica se cambiano le informazioni di consenso.

## Ricarica in modalità modifica
- Il backend deve leggere tutte le tabelle sopra per ricostruire `therapyWizardState` completo (paziente, caregiver, cronico, questionari, consensi) e ripopolare ogni step del wizard.

