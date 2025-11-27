<?php
/**
 * Modulo Adesione Terapie - Entry Point
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requirePharmacistOrAdmin();

$pharmacy = getCurrentPharmacy();
if (!$pharmacy) {
    redirect('dashboard.php', 'Impossibile accedere al modulo: farmacia non trovata.', 'danger');
}

$page_title = 'Adesione Terapie';
$page_description = 'Gestione completa dei percorsi terapeutici e monitoraggio dell\'aderenza.';
$current_page = 'adesione_terapie';
$body_class = 'adesione-terapie-page';

$additional_css = $additional_css ?? [];
$additional_css[] = 'adesione-terapie/assets/css/module.css';

$additional_js = $additional_js ?? [];
$additional_js[] = 'adesione-terapie/assets/js/module.js';

require_once __DIR__ . '/../includes/header.php';
?>

<div
  class="container-fluid adesione-terapie-module"
  data-pharmacy="<?= htmlspecialchars($pharmacy['id']) ?>"
>
  <div class="row">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="page-header mb-4">
        <div
          class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3"
        >
          <div>
            <h1 class="h3 mb-1">
              <i class="fas fa-hand-holding-medical me-2"></i>
              Programma di Adesione Terapie
            </h1>
            <p class="text-muted mb-0">
              Gestisci pazienti, percorsi terapeutici, consensi, controlli
              periodici e promemoria.
            </p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" id="exportReportButton">
              <i class="fas fa-file-export me-2"></i>Genera Report Completo
            </button>
            <button class="btn btn-outline-secondary" id="newCheckButton">
              <i class="fas fa-stethoscope me-2"></i>Nuovo Check Periodico
            </button>
            <button class="btn btn-outline-info" id="newReminderButton">
              <i class="fas fa-bell me-2"></i>Nuovo Promemoria
            </button>
            <button class="btn btn-primary" id="newTherapyButton">
              <i class="fas fa-plus me-2"></i>Nuova Terapia
            </button>
            <button class="btn btn-success" id="newPatientButton">
              <i class="fas fa-user-plus me-2"></i>Nuovo Paziente
            </button>
          </div>
        </div>
      </div>

      <section class="dashboard-stats mb-4">
        <div class="row g-3">
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-icon bg-primary-subtle text-primary">
                <i class="fas fa-users"></i>
              </div>
              <div>
                <span class="stat-value" data-stat="patients">0</span>
                <span class="stat-label">Pazienti attivi</span>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-icon bg-success-subtle text-success">
                <i class="fas fa-prescription-bottle-alt"></i>
              </div>
              <div>
                <span class="stat-value" data-stat="therapies">0</span>
                <span class="stat-label">Terapie in corso</span>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-icon bg-warning-subtle text-warning">
                <i class="fas fa-bell"></i>
              </div>
              <div>
                <span class="stat-value" data-stat="reminders">0</span>
                <span class="stat-label">Promemoria attivi</span>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-icon bg-info-subtle text-info">
                <i class="fas fa-stethoscope"></i>
              </div>
              <div>
                <span class="stat-value" data-stat="checks">0</span>
                <span class="stat-label">Check programmati</span>
              </div>
            </div>
          </div>
        </div>
      </section>

            <!-- PAZIENTI: sezione a tutta larghezza -->
      <section class="therapies-dashboard mb-4">
        <div class="module-card">
          <div
            class="module-card-header d-flex justify-content-between align-items-center"
          >
            <div>
              <h2 class="h5 mb-1">Pazienti</h2>
            </div>
            <button
              class="btn btn-sm btn-outline-primary"
              id="refreshDataButton"
            >
              <i class="fas fa-rotate"></i>
            </button>
          </div>
          <div class="module-card-body patient-list" id="patientsList">
            <div class="empty-state">
              <i class="fas fa-user-friends"></i>
              <p>
                Nessun paziente registrato. Aggiungi un nuovo paziente per
                iniziare.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- TERAPIE E MONITORAGGIO: sezione a tutta larghezza -->
      <section class="therapies-dashboard mb-4">
        <div class="module-card">
          <div class="module-card-header">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <h2 class="h5 mb-1">Terapie e Monitoraggio</h2>
              </div>
              <div id="patientSummary" class="text-end small text-muted"></div>
            </div>
          </div>
          <div class="module-card-body" id="therapiesContainer">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 mb-3">
              <div class="flex-grow-1">
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-search"></i>
                  </span>
                  <input
                    type="search"
                    class="form-control"
                    id="therapySearchInput"
                    placeholder="Cerca paziente per nome"
                    aria-label="Cerca paziente per nome"
                  />
                </div>
              </div>
            </div>
            <div id="therapiesList">
              <div class="empty-state">
                <i class="fas fa-prescription-bottle-alt"></i>
                <p>
                  Seleziona un paziente per visualizzare le terapie associate.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- TIMELINE CONTROLLI E PROMEMORIA: sezione a tutta larghezza -->
      <section class="timeline-section mb-4">
        <div class="module-card">
          <div
            class="module-card-header d-flex justify-content-between align-items-center"
          >
            <div>
              <h2 class="h5 mb-1">Timeline Controlli e Promemoria</h2>
            </div>
            <div class="d-flex gap-2 align-items-center">
              <select class="form-select form-select-sm" id="timelineFilter">
                <option value="upcoming">Prossimi 30 giorni</option>
                <option value="all">Tutte le attività</option>
                <option value="past">Storico completato</option>
              </select>
            </div>
          </div>
          <div class="module-card-body">
            <div id="timelineContainer">
              <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <p>
                  Nessuna attività pianificata. Aggiungi un promemoria o un
                  check periodico.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- Modale Nuovo Paziente -->
  <div
    class="modal fade"
    id="patientModal"
    tabindex="-1"
    aria-labelledby="patientModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form id="patientForm">
          <div class="modal-header">
            <h5 class="modal-title" id="patientModalLabel">
              <i class="fas fa-user me-2"></i><span>Nuovo Paziente</span>
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Chiudi"
            ></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="patient_id" id="patientId" />
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input
                  type="text"
                  class="form-control"
                  name="first_name"
                  required
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Cognome</label>
                <input
                  type="text"
                  class="form-control"
                  name="last_name"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Data di nascita</label>
                <input type="date" class="form-control" name="birth_date" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Telefono</label>
                <input type="tel" class="form-control" name="phone" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" />
              </div>
              <div class="col-12">
                <label class="form-label">Note cliniche</label>
                <textarea
                  class="form-control"
                  name="notes"
                  rows="3"
                  placeholder="Allergie, patologie, informazioni utili..."
                ></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-secondary"
              data-bs-dismiss="modal"
            >
              Annulla
            </button>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save me-2"></i>Salva Paziente
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modale Nuova Terapia -->
  <div
    class="modal fade"
    id="therapyModal"
    tabindex="-1"
    aria-labelledby="therapyModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <form id="therapyForm">
          <div class="modal-header">
            <div>
              <h5 class="modal-title" id="therapyModalLabel">
                <i class="fas fa-prescription-bottle-alt me-2"></i
                ><span>Nuova Terapia</span>
              </h5>
              <small class="text-muted"
                >Compila il percorso guidato per registrare la terapia e il
                questionario iniziale</small
              >
            </div>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Chiudi"
            ></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="therapy_id" id="therapyId" />
            <input type="hidden" name="patient_id" id="therapyPatientId" />
            <input
              type="hidden"
              name="signature_image"
              id="signatureImageInput"
            />
            <input
              type="hidden"
              name="questionnaire_payload"
              id="questionnairePayloadInput"
            />
            <input
              type="hidden"
              name="caregivers_payload"
              id="caregiversPayloadInput"
            />

            <div class="wizard-progress">
              <div class="wizard-progress-bar">
                <div
                  class="wizard-progress-indicator"
                  id="therapyWizardIndicator"
                ></div>
              </div>
              <div class="wizard-steps" id="therapyWizardSteps">
                <span data-step="1" class="active">Paziente</span>
                <span data-step="2">Dettagli Terapia</span>
                <span data-step="3">Questionario Stile di Vita</span>
                <span data-step="4">Questionario Monitoraggio</span>
                <span data-step="5">Consenso</span>
                <span data-step="6">Riepilogo</span>
              </div>
            </div>

            <div class="wizard-content">
              <!-- Step 1: Paziente -->
              <section class="wizard-step active" data-step="1">
                <div class="row g-3">
                  <div class="col-lg-6">
                    <label class="form-label">Seleziona Paziente</label>
                    <select
                      class="form-select"
                      id="therapyPatientSelect"
                      name="existing_patient"
                    >
                      <option value="">-- Seleziona paziente --</option>
                    </select>
                  </div>
                  <div class="col-lg-6">
                    <label class="form-label"
                      >Oppure crea un nuovo paziente</label
                    >
                    <div class="d-grid">
                      <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="openInlinePatientForm"
                      >
                        <i class="fas fa-user-plus me-2"></i>Compila dati
                        paziente
                      </button>
                    </div>
                  </div>
                  <div class="col-12 collapse" id="inlinePatientForm">
                    <div class="border rounded p-3 bg-light">
                      <h6 class="fw-semibold mb-3">Nuovo paziente</h6>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Nome</label>
                          <input
                            type="text"
                            class="form-control"
                            name="inline_first_name"
                          />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Cognome</label>
                          <input
                            type="text"
                            class="form-control"
                            name="inline_last_name"
                          />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Data di nascita</label>
                          <input
                            type="date"
                            class="form-control"
                            name="inline_birth_date"
                          />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Telefono</label>
                          <input
                            type="tel"
                            class="form-control"
                            name="inline_phone"
                          />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Email</label>
                          <input
                            type="email"
                            class="form-control"
                            name="inline_email"
                          />
                        </div>
                        <div class="col-12">
                          <label class="form-label">Note</label>
                          <textarea
                            class="form-control"
                            name="inline_notes"
                            rows="2"
                          ></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Step 2: Dettagli Terapia -->
              <section class="wizard-step" data-step="2">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Titolo terapia</label>
                    <input
                      type="text"
                      class="form-control"
                      name="therapy_title"
                      required
                      placeholder="Es. Terapia ipertensione"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Descrizione terapia</label>
                    <textarea
                      class="form-control"
                      name="therapy_description"
                      rows="3"
                      required
                      placeholder="Es. Piano farmacologico, obiettivi, farmaci coinvolti..."
                    ></textarea>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Stato</label>
                    <select class="form-select" name="status">
                      <option value="active">Attiva</option>
                      <option value="planned">Pianificata</option>
                      <option value="completed">Completata</option>
                      <option value="suspended">Sospesa</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Data inizio</label>
                    <input
                      type="date"
                      class="form-control"
                      name="start_date"
                      required
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Data fine (stimata)</label>
                    <input type="date" class="form-control" name="end_date" />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Caregiver e familiari</label>
                    <div id="caregiversContainer" class="caregivers-container">
                      <div class="caregiver-row template d-none">
                        <div class="row g-2 align-items-end">
                          <div class="col-md-4">
                            <label class="form-label">Nome</label>
                            <input
                              type="text"
                              class="form-control"
                              data-field="name"
                            />
                          </div>
                          <div class="col-md-3">
                            <label class="form-label">Ruolo</label>
                            <select class="form-select" data-field="role">
                              <option value="caregiver">Caregiver</option>
                              <option value="familiare">Familiare</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label">Telefono</label>
                            <input
                              type="tel"
                              class="form-control"
                              data-field="phone"
                            />
                          </div>
                          <div class="col-md-2">
                            <button
                              type="button"
                              class="btn btn-outline-danger w-100 remove-caregiver"
                            >
                              <i class="fas fa-times"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="btn btn-outline-primary btn-sm mt-3"
                      id="addCaregiverButton"
                    >
                      <i class="fas fa-user-plus me-1"></i>Aggiungi caregiver
                    </button>
                  </div>
                </div>
              </section>

              <!-- Steps 3-4: Questionario (DB steps 2 e 4) -->
              <?php
                        $questionnaireSteps = [
                            [
                                'ui_step' => 3,
                                'db_step' => 2,
                                'title' => 'Stile di Vita',
                                'questions' => [
                                    'alimentazione' => 'Com\'è l\'alimentazione abituale?',
                                    'attivita' => 'Svolge attività fisica regolare?',
                                    'riposo' => 'Qualità del sonno e riposo?'
                                ]
                            ],
                            [
                                'ui_step' => 4,
                                'db_step' => 4,
                                'title' => 'Monitoraggio',
                                'questions' => [
                                    'parametri' => 'Vengono monitorati parametri clinici? Quali?',
                                    'visite' => 'Frequenza delle visite mediche di controllo?',
                                    'autonomia' => 'Livello di autonomia nella gestione della terapia?'
                                ]
                            ],
                        ];
                        foreach ($questionnaireSteps as $stepData): ?>
              <section class="wizard-step" data-step="<?= $stepData['ui_step'] ?>" data-db-step="<?= $stepData['db_step'] ?>">
                <h6 class="fw-semibold mb-3">
                  <i class="fas fa-list-check me-2 text-primary"></i
                  ><?= htmlspecialchars($stepData['title']) ?>
                </h6>
                <?php foreach ($stepData['questions'] as $key =>
                $label): ?>
                <div class="mb-3">
                  <label class="form-label"
                    ><?= htmlspecialchars($label) ?></label
                  >
                  <textarea
                    class="form-control questionnaire-input"
                    data-question="<?= htmlspecialchars($key) ?>"
                    rows="2"
                  ></textarea>
                </div>
                <?php endforeach; ?>
              </section>
              <?php endforeach; ?>

              <!-- Step 5: Consenso -->
              <section class="wizard-step" data-step="5">
                <div class="row g-4">
                  <div class="col-lg-6">
                    <label class="form-label">Tipologia di firma</label>
                    <select
                      class="form-select"
                      name="signature_type"
                      id="signatureType"
                    >
                      <option value="graphical">Firma grafica</option>
                      <option value="digital">Firma digitale semplice</option>
                    </select>
                  </div>
                  <div class="col-12" id="signatureCanvasWrapper">
                    <label class="form-label"
                      >Firma grafica del paziente / caregiver</label
                    >
                    <div class="signature-pad">
                      <canvas id="consentSignaturePad"></canvas>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        id="clearSignatureButton"
                      >
                        <i class="fas fa-eraser me-1"></i>Pulisci firma
                      </button>
                      <button
                        type="button"
                        class="btn btn-outline-primary btn-sm"
                        id="saveSignatureButton"
                      >
                        <i class="fas fa-save me-1"></i>Salva firma
                      </button>
                    </div>
                    <small class="text-muted d-block mt-2"
                      >La firma verrà salvata con timestamp, indirizzo IP e
                      associata alla terapia.</small
                    >
                  </div>
                  <div class="col-lg-6 d-none" id="digitalSignatureWrapper">
                    <label class="form-label">Firma digitale semplice</label>
                    <input
                      type="text"
                      class="form-control"
                      name="digital_signature"
                      placeholder="Inserisci nome e cognome del firmatario"
                    />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Note consenso informato</label>
                    <textarea
                      class="form-control"
                      name="consent_notes"
                      rows="3"
                      placeholder="Note aggiuntive o condizioni specifiche"
                    ></textarea>
                  </div>
                </div>
              </section>

              <!-- Step 6: Riepilogo -->
              <section class="wizard-step" data-step="6">
                <div class="summary-preview" id="therapySummary"></div>
              </section>
              <div class="wizard-controls d-flex justify-content-between mt-4">
                <button
                  type="button"
                  id="prevStepButton"
                  class="btn btn-outline-secondary"
                >
                  <i class="fas fa-arrow-left me-1"></i> Indietro
                </button>

                <button
                  type="button"
                  id="nextStepButton"
                  class="btn btn-primary"
                >
                  Avanti <i class="fas fa-arrow-right ms-1"></i>
                </button>

                <button
                  type="submit"
                  id="submitTherapyButton"
                  class="btn btn-success d-none"
                >
                  <i class="fas fa-check me-1"></i> Salva Terapia
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modale Check Periodico -->
  <div
    class="modal fade"
    id="checkModal"
    tabindex="-1"
    aria-labelledby="checkModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form id="checkForm">
          <div class="modal-header">
            <h5 class="modal-title" id="checkModalLabel">
              <i class="fas fa-stethoscope me-2"></i>Visita di controllo
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Chiudi"
            ></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="check_id" id="checkId" />
            <input type="hidden" name="therapy_id" id="checkTherapyId" />
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Terapia</label>
                <select
                  class="form-select"
                  name="therapy_reference"
                  id="checkTherapySelect"
                  required
                ></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Data e ora</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  name="scheduled_at"
                  required
                />
              </div>
              <div class="col-12">
                <p class="text-muted mb-0">
                  Compila le risposte della checklist per ogni domanda con Sì/No e
                  aggiungi eventuali note.
                </p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-secondary"
              data-bs-dismiss="modal"
            >
              Annulla
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-2"></i>Salva check
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modale Promemoria -->
  <div
    class="modal fade"
    id="reminderModal"
    tabindex="-1"
    aria-labelledby="reminderModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form id="reminderForm">
          <div class="modal-header">
            <h5 class="modal-title" id="reminderModalLabel">
              <i class="fas fa-bell me-2"></i>Promemoria terapia
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Chiudi"
            ></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="reminder_id" id="reminderId" />
            <input type="hidden" name="therapy_id" id="reminderTherapyId" />
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Terapia associata</label>
                <select
                  class="form-select"
                  id="reminderTherapySelect"
                  required
                ></select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Titolo</label>
                <input
                  type="text"
                  class="form-control"
                  name="title"
                  placeholder="Es. Promemoria assunzione"
                  required
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">Tipologia</label>
                <select class="form-select" name="type">
                  <option value="one-shot">Singolo</option>
                  <option value="daily">Giornaliero</option>
                  <option value="weekly">Settimanale</option>
                  <option value="monthly">Mensile</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Data e ora</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  name="scheduled_at"
                  required
                />
              </div>
              <div class="col-12">
                <label class="form-label">Messaggio</label>
                <textarea
                  class="form-control"
                  name="message"
                  rows="2"
                  required
                ></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Canale</label>
                <select class="form-select" name="channel">
                  <option value="email">Email</option>
                  <option value="sms">SMS</option>
                  <option value="push">Notifica interna</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-secondary"
              data-bs-dismiss="modal"
            >
              Annulla
            </button>
            <button type="submit" class="btn btn-warning">
              <i class="fas fa-save me-2"></i>Salva promemoria
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Report -->
  <div
    class="modal fade"
    id="reportModal"
    tabindex="-1"
    aria-labelledby="reportModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form id="reportForm">
          <div class="modal-header">
            <h5 class="modal-title" id="reportModalLabel">
              <i class="fas fa-file-medical me-2"></i>Genera Report Terapia
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Chiudi"
            ></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="therapy_id" id="reportTherapyId" />
            <div class="mb-3">
              <label class="form-label">Seleziona terapia</label>
              <select
                class="form-select"
                id="reportTherapySelect"
                name="therapy_reference"
                required
              ></select>
            </div>
            <div class="mb-3">
              <label class="form-label">Destinatari del link</label>
              <input
                type="text"
                class="form-control"
                name="recipients"
                placeholder="Email o note sul destinatario del link"
              />
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Valido fino al</label>
                <input type="date" class="form-control" name="valid_until" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Protezione con PIN (opzionale)</label>
                <input
                  type="text"
                  class="form-control"
                  name="pin_code"
                  placeholder="Inserisci un PIN per proteggere l\'accesso"
                />
              </div>
            </div>
            <div class="form-check mt-3">
              <input
                class="form-check-input"
                type="checkbox"
                value="1"
                id="includeChecks"
                name="include_checks"
                checked
              />
              <label class="form-check-label" for="includeChecks">
                Includi storico check periodici nel report
              </label>
            </div>
            <div class="alert alert-info mt-3">
              Il report generato sarà disponibile tramite link pubblico
              condivisibile con medici e caregiver.
            </div>
            <div class="generated-report d-none" id="generatedReportContainer">
              <h6 class="fw-semibold">Link pubblico generato</h6>
              <div class="input-group">
                <input
                  type="text"
                  class="form-control"
                  id="generatedReportLink"
                  readonly
                />
                <button
                  class="btn btn-outline-primary"
                  type="button"
                  id="copyReportLink"
                >
                  <i class="fas fa-copy"></i>
                </button>
              </div>
              <div class="small text-muted mt-2" id="generatedReportInfo"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-secondary"
              data-bs-dismiss="modal"
            >
              Chiudi
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-file-medical me-2"></i>Genera report
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
