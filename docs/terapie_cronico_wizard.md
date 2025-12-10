# Wizard Terapie Paziente Cronico — struttura definitiva (logica e dati)

## Struttura complessiva del wizard
- **State principale**: `therapyWizardState` con chiavi
  - `patient`, `primary_condition`, `initial_notes`
  - `general_anamnesis`, `detailed_intake` (Step 1)
  - `therapy_assistants` (Step 2)
  - `adherence_base` (Step 3)
  - `condition_survey` (Step 4)
  - `risk_score`, `flags`, `notes_initial`, `follow_up_date` (Step 5 – pianificazione/rischio)
  - `consent` (Step 6)
- **Sequenza step**: 1) Anagrafica + anamnesi, 2) Caregiver/familiari, 3) Aderenza base, 4) Questionario patologia, 5) Pianificazione/rischio (placeholder), 6) Consenso e firme.
- **Persistenza**: nessuna scrittura durante i singoli step; tutto viene scritto al submit finale con INSERT/UPDATE sulle tabelle mappate.

## Step 1 – Scheda di presa in carico e anamnesi iniziale

### Obiettivo
Raccogliere dati anagrafici e di contesto del paziente, con anamnesi generale e comportamentale.

### Dati coinvolti
- `jta_patients`
- `jta_pharma_patient`
- `jta_therapy_chronic_care.general_anamnesis`
- `jta_therapy_chronic_care.detailed_intake`

### Sezioni UI

#### 1.1 Dati anagrafici paziente
- Selezione paziente esistente (ricerca per nome/cognome/codice_fiscale) o creazione nuovo paziente.
- Campi nuovo paziente: nome (obbl.), cognome (obbl.), data di nascita, sesso (M/F), telefono, email, note iniziali, campo testo libero "Caregiver (facoltativo)" per supporto allo step 2.
- Patologia seguita (select con opzioni: Diabete, BPCO, Ipertensione, Dislipidemia, Altro + testo libero).
- Stato JS da popolare: `therapyWizardState.patient`, `therapyWizardState.initial_notes`, `therapyWizardState.primary_condition`.
- Submit finale: se `patient.id` mancante → `INSERT` in `jta_patients` e `INSERT` in `jta_pharma_patient`; se esistente → opzionale `UPDATE` dei campi base. `primary_condition` → `jta_therapy_chronic_care.primary_condition`.

#### 1.2 Contesto familiare/sociale (general_anamnesis – parte 1)
- Domande: numero membri famiglia; supporto da non conviventi; titolo di studio; presenza caregiver.
- Mappare in `general_anamnesis` JSON, es.:
```json
{
  "family_members_count": 3,
  "has_external_support": true,
  "education_level": "diploma",
  "has_caregiver": true
}
```

#### 1.3 Anamnesi terapeutica e comportamentale (general_anamnesis/detailed_intake – parte 2)
- Domande su allergie (farmaci, lattice, alimenti, vaccini), accessi PS, esami (ECG, Holter cardiaco/pressorio), vaccini (influenza, altri, reazioni), aiuto gestione terapia, modifiche dosaggi e iniziativa, tipologia farmaci assunti, dimenticanze e comportamento, interruzioni intenzionali e motivi, integratori/omeopatici/fitoterapici (quali e frequenza), uso device BPCO/automisurazione pressione/glicemia, problemi device, misurazione glicemia, reazioni allergiche a farmaci o integratori.
- Suggerimento di mappatura:
  - `general_anamnesis` JSON: allergie, accessi PS, esami (ECG, Holter), storico vaccini e reazioni.
  - `detailed_intake` JSON: aiuto nella terapia; modifiche dosaggio e iniziativa; tipologia farmaci; dimenticanze e comportamento; interruzioni intenzionali e motivi; uso integratori/omeopatici/fitoterapici (quali, frequenza); uso device (BPCO, automisurazione pressione, glicemia) e problemi; misurazione glicemia; reazioni allergiche a farmaci/integratori.
- Esempio `detailed_intake`:
```json
{
  "has_helper_for_medication": true,
  "dose_changes_last_month": true,
  "dose_changes_self_initiated": true,
  "drug_types": "antipertensivi, ipolipemizzanti",
  "forgets_medications": "talvolta",
  "behaviour_when_forgets": "assume ad orario differente",
  "intentional_skips": true,
  "intentional_stop_reason": "si_sentiva_meglio",
  "uses_supplements": true,
  "supplements_details": "vitamina D, omega 3",
  "supplements_frequency": "quotidiana",
  "uses_bpcop_device": false,
  "knows_how_to_use_device": true,
  "device_problems": "nessuno",
  "uses_self_measure_bp": true,
  "pharmacy_bp_frequency": "mensile",
  "ever_measured_glycemia": true,
  "drug_or_supplement_allergic_reactions": true
}
```

### Validazioni di fine step
- Verificare che `therapyWizardState.patient` e `therapyWizardState.primary_condition` siano valorizzati.
- Nessuna scrittura DB allo step: `general_anamnesis` e `detailed_intake` saranno inseriti in `jta_therapy_chronic_care` solo al submit finale.

## Step 2 – Caregiver e familiari

### Obiettivo
Associare uno o più assistenti (caregiver/familiari) alla terapia, raccogliendo preferenze e consensi specifici.

### Dati coinvolti
- `jta_assistants`
- `jta_therapy_assistant`

### Sezioni UI

#### 2.1 Selezione assistenti esistenti
- Elenco assistenti della farmacia corrente (`jta_assistants.pharma_id = farmacia corrente`) con nome, cognome, tipo, telefono, email.
- Checkbox per selezionare più assistenti da associare alla terapia.

#### 2.2 Aggiunta nuovo assistente
- Pulsante "Aggiungi nuovo assistente" con form/modale: `first_name` (obbl.), `last_name` (opz.), `phone` (opz.), `email` (opz.), `type` (caregiver/familiare, default familiare), `relation_to_patient` (testo libero), `preferred_contact` (phone/email/whatsapp, opz.), `notes` (textarea opz.).
- Alla conferma: `INSERT` in `jta_assistants`; aggiornare la lista per poter selezionare il nuovo assistente.

#### 2.3 Domande specifiche per il caregiver
- Per ogni assistente selezionato, raccogliere risposte/consensi:
  - Soddisfazione per il questionario.
  - Informazioni aggiuntive utili al farmacista.
  - Interesse a ricevere un report mensile (mail o WhatsApp) e relativo canale.
  - Autorizzazione a interagire con il medico curante.
  - Autorizzazione al ritiro di ricette/referti presso lo studio medico.
- Esempio di struttura nello state JS:
```json
therapyWizardState.therapy_assistants = [
  {
    "assistant_id": 2,
    "role": "caregiver",
    "contact_channel": "whatsapp",
    "preferences_json": {
      "wants_monthly_report": true,
      "report_channel": "whatsapp"
    },
    "consents_json": {
      "questionnaire_satisfaction": "soddisfatto",
      "extra_info_for_pharmacist": "nota libera...",
      "can_contact_doctor": true,
      "can_collect_documents": true
    }
  }
]
```

### Scrittura su DB (submit finale)
- Per ciascun assistente selezionato: `INSERT` in `jta_therapy_assistant` con `therapy_id`, `assistant_id`, `role`, `contact_channel`, `preferences_json`, `consents_json`.

## Step 3 – Questionario di aderenza base (comune)

### Obiettivo
Raccogliere il questionario di aderenza comune e salvarlo in `jta_therapy_chronic_care.adherence_base` (JSON).

### Domande/UI
- Terapie in corso (principi attivi)
- Dispositivo utilizzato (es. inalatore, glucometro, sfigmomanometro)
- Dimentica di assumere le dosi? ☐ Mai / ☐ Talvolta / ☐ Spesso
- Interrompe la terapia quando si sente meglio? ☐ Sì / ☐ No
- Riduce le dosi senza consultare il medico/farmacista? ☐ Sì / ☐ No
- Sa utilizzare correttamente i dispositivi per il controllo della sua terapia? ☐ Sì / ☐ No
- Esegue automisurazioni periodiche? ☐ Sì / ☐ No
- Ultimo controllo (data)
- Accessi al Pronto Soccorso o ricoveri nell’ultimo anno: ☐ Nessuno / ☐ 1 / ☐ >1
- Reazioni avverse note ai farmaci? ☐ Sì / ☐ No
- Note aggiuntive (textarea)

### Mappatura JSON
Salvare in `therapyWizardState.adherence_base` (e al submit in `jta_therapy_chronic_care.adherence_base`). Esempio:

```json
{
  "current_therapies": "metformina, ACE-inibitore",
  "devices_used": "glucometro, sfigmomanometro",
  "forgets_doses": "talvolta",
  "stops_when_better": true,
  "reduces_doses_without_consult": false,
  "knows_how_to_use_devices": true,
  "does_self_monitoring": true,
  "last_check_date": "2024-06-01",
  "er_visits_last_year": ">1",
  "known_adverse_reactions": false,
  "extra_notes": "note libere..."
}
```

## Step 4 – Questionario specifico per patologia

### Obiettivo
Raccogliere un questionario dedicato alla patologia principale scelta nello step 1 e salvarlo in `jta_therapy_condition_surveys`.

### Dati coinvolti
- `jta_therapy_condition_surveys`

### Sezioni UI
- Tab o radio con patologie: Diabete, BPCO, Ipertensione, Dislipidemia, Altro.
- La tab attiva deve corrispondere a `primary_condition` selezionata nello step 1.
- Per ogni patologia, predisporre un blocco di domande dinamico (placeholder) che supporti N domande con chiave→valore nel JSON.
- Il form deve consentire di aggiungere/gestire campi generici ora e domande specifiche in futuro tramite una sezione commentata nel codice/JS per:
  - Diabete
  - BPCO
  - Ipertensione
  - Dislipidemia
  - Altro

### Stato JS
Salvare le risposte nello state:

```json
therapyWizardState.condition_survey = {
  "condition_type": "diabete", // valorizzato con primary_condition
  "level": "base",
  "answers": {
    "q1": "risposta...",
    "q2": 120,
    "note": "note aggiuntive"
  },
  "compiled_at": "YYYY-MM-DD HH:MM:SS"
}
```

### Scrittura su DB (submit finale)
- `INSERT` in `jta_therapy_condition_surveys` con `therapy_id`, `condition_type`, `level='base'`, `answers` (JSON key→value), `compiled_at`.

## Step 5 – Pianificazione, rischio e note iniziali (placeholder)

### Obiettivo
Raccogliere le informazioni di pianificazione del follow-up e la valutazione di rischio, tenendo spazio per flags e note iniziali del farmacista.

### Dati coinvolti
- `jta_therapy_chronic_care` (campi `risk_score`, `flags` JSON, `notes_initial`, `follow_up_date`).

### Stato JS
Integrare in `therapyWizardState` le chiavi:
- `risk_score`: punteggio o valutazione numerica.
- `flags`: oggetto JSON per evidenziare priorità o alert (es. `{ "priority": "alta" }`).
- `notes_initial`: note di presa in carico del farmacista.
- `follow_up_date`: data prevista per il primo controllo.

### Scrittura su DB (submit finale)
- Valorizzare i campi corrispondenti in `jta_therapy_chronic_care` durante l'INSERT/UPDATE complessivo della terapia cronico.

## Step 6 – Consenso informato e firme

### Obiettivo
Acquisire i consensi obbligatori/facoltativi e registrare le firme, salvando i dati in `jta_therapy_consents`.

### UI
- Checkbox consensi:
  - Obbligatorio: consenso al trattamento dati personali (GDPR) per cura/monitoraggio/follow-up.
  - Facoltativi: contatto per promemoria/aggiornamenti (WhatsApp/Email/Telefono); uso anonimo dei dati per fini statistici/miglioramento servizio.
- Dati firme:
  - Firma paziente/caregiver (testo per nome del firmatario).
  - Selettore relazione firmatario: paziente / caregiver / familiare.
  - Data firma (default data odierna).
  - Firma farmacista (testo nome e cognome).
  - Luogo (testo, es. città/farmacia).

### Stato JS
Salvare in `therapyWizardState.consent`:

```json
{
  "signer_name": "...",
  "signer_relation": "patient | caregiver | familiare",
  "signed_at": "YYYY-MM-DD",
  "pharmacist_name": "...",
  "place": "...",
  "scopes": {
    "care_followup": true,
    "contact_for_reminders": true,
    "anonymous_stats": false,
    "contact_channel_preference": "whatsapp"
  }
}
```

### Mappatura DB (submit finale)
- `INSERT` in `jta_therapy_consents` con:
  - `therapy_id` (legato alla terapia creata/aggiornata).
  - `signer_name` (campo firma paziente/caregiver).
  - `signer_relation` (`patient` | `caregiver` | `familiare`).
  - `consent_text` (stringa standard riassuntiva dei consensi).
  - `signed_at` (data/ora indicata o `NOW()`).
  - `ip_address` (se disponibile lato server).
  - `scopes_json` con i flag dei consensi e preferenze, es.:
    ```json
    {
      "care_followup": true,
      "contact_for_reminders": true,
      "anonymous_stats": false,
      "contact_channel_preference": "whatsapp",
      "pharmacist_name": "Nome Cognome",
      "place": "Roma"
    }
    ```
  - `signer_role` (testo libero aggiuntivo, es. "figlio del paziente").

