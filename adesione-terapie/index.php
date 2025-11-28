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
                >Compila il percorso guidato per registrare la terapia e i dati
                essenziali</small
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
                  <span data-step-indicator="m1" class="active">M1</span>
                  <span data-step-indicator="1">Anamnesi generale</span>
                  <span data-step-indicator="2">Presa in carico</span>
                  <span data-step-indicator="3">Aderenza base</span>
                  <span data-step-indicator="4">Patologia base</span>
                  <span data-step-indicator="5">Patologia approfondita</span>
                  <span data-step-indicator="6">Follow-up & Caregiver</span>
                  <span data-step-indicator="7">Consensi</span>
                </div>
              </div>

            <div class="wizard-content">
              <!-- Step M1: Contesto e riferimenti -->
              <section class="wizard-step" data-step="m1">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Di quanti membri si compone la sua famiglia?</label>
                    <input type="number" class="form-control" name="family_members_count" min="0" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">La famiglia può contare in caso di necessità sull'aiuto personale di persone non conviventi?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="support_non_cohabiting" value="si" id="supportYes" />
                        <label class="form-check-label" for="supportYes">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="support_non_cohabiting" value="no" id="supportNo" />
                        <label class="form-check-label" for="supportNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Qual è il suo titolo di studio?</label>
                    <input type="text" class="form-control" name="education_level" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">C'è un caregiver che si occupa di lei quando non può essere presente un familiare?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="has_caregiver" value="si" id="hasCaregiverYes" />
                        <label class="form-check-label" for="hasCaregiverYes">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="has_caregiver" value="no" id="hasCaregiverNo" />
                        <label class="form-check-label" for="hasCaregiverNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="border rounded p-3 bg-light">
                      <h6 class="fw-semibold mb-3">Dati anagrafici del paziente</h6>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Nome e cognome</label>
                          <input type="text" class="form-control" name="patient_name" />
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Data di nascita</label>
                          <input type="date" class="form-control" name="patient_birth_date" />
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Telefono</label>
                          <input type="tel" class="form-control" name="patient_phone" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Email</label>
                          <input type="email" class="form-control" name="patient_email" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="border rounded p-3">
                      <h6 class="fw-semibold mb-3">Dati anagrafici del Caregiver</h6>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Nome e cognome</label>
                          <input type="text" class="form-control" name="caregiver_name" />
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Relazione</label>
                          <input type="text" class="form-control" name="caregiver_relation" />
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Telefono</label>
                          <input type="tel" class="form-control" name="caregiver_phone" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Email</label>
                          <input type="email" class="form-control" name="caregiver_email" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Dati anagrafici dei familiari</label>
                    <textarea class="form-control" name="family_contacts" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Riferimenti del medico curante</label>
                    <input type="text" class="form-control" name="doctor_reference" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Riferimenti dello specialista</label>
                    <input type="text" class="form-control" name="specialist_reference" />
                  </div>
                </div>
              </section>

              <!-- Step 1: Anamnesi generale -->
              <section class="wizard-step d-none" data-step="1">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label">Età</label>
                    <input type="number" class="form-control" name="eta" min="0" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Sesso</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="sesso" value="M" id="sessoM" />
                        <label class="form-check-label" for="sessoM">M</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="sesso" value="F" id="sessoF" />
                        <label class="form-check-label" for="sessoF">F</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Se donna, specificare</label>
                    <div class="d-flex flex-wrap gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="stato_fertilita_donna" value="eta_fertile" id="fertile" />
                        <label class="form-check-label" for="fertile">Età fertile</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="stato_fertilita_donna" value="menopausa" id="menopausa" />
                        <label class="form-check-label" for="menopausa">Menopausa</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Come valuta in generale il suo stato di salute?</label>
                    <select class="form-select" name="stato_salute_generale">
                      <option value="">-- Seleziona --</option>
                      <option value="buono">Buono</option>
                      <option value="discreto">Discreto</option>
                      <option value="scarso">Scarso</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">È fumatore o ex fumatore?</label>
                    <select class="form-select" name="fumatore_status">
                      <option value="">-- Seleziona --</option>
                      <option value="no">No</option>
                      <option value="ex">Ex</option>
                      <option value="si">Sì (sigarette/giorno)</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Sigarette/giorno</label>
                    <input type="number" class="form-control" name="sigarette_per_giorno" min="0" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Pratica attività fisica regolare (almeno 30 minuti al giorno)?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="attivita_fisica_regolare" value="si" id="attFisSi" />
                        <label class="form-check-label" for="attFisSi">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="attivita_fisica_regolare" value="no" id="attFisNo" />
                        <label class="form-check-label" for="attFisNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Segue una dieta particolare o controlla sale, zuccheri o grassi?</label>
                    <select class="form-select" name="dieta_controllata">
                      <option value="">-- Seleziona --</option>
                      <option value="si">Sì</option>
                      <option value="no">No</option>
                      <option value="a_volte">A volte</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Assume farmaci in modo continuativo?</label>
                    <select class="form-select" name="farmaci_continuativi">
                      <option value="">-- Seleziona --</option>
                      <option value="no">No</option>
                      <option value="1-2">Sì 1–2</option>
                      <option value="3-5">Sì 3–5</option>
                      <option value=">5">Sì >5</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ha difficoltà a ricordare o gestire la terapia?</label>
                    <select class="form-select" name="difficolta_gestione_terapia">
                      <option value="">-- Seleziona --</option>
                      <option value="no">No</option>
                      <option value="talvolta">Talvolta</option>
                      <option value="spesso">Spesso</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Patologie diagnosticate:</label>
                    <div class="row row-cols-2 row-cols-md-3 g-2">
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologie_diagnosticate[]" value="diabete" id="diagDiabete" />
                          <label class="form-check-label" for="diagDiabete">Diabete</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologie_diagnosticate[]" value="ipertensione" id="diagIpertensione" />
                          <label class="form-check-label" for="diagIpertensione">Ipertensione</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologie_diagnosticate[]" value="bpco" id="diagBpco" />
                          <label class="form-check-label" for="diagBpco">BPCO</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologie_diagnosticate[]" value="dislipidemie" id="diagDisli" />
                          <label class="form-check-label" for="diagDisli">Dislipemie</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologie_diagnosticate[]" value="altro" id="diagAltro" />
                          <label class="form-check-label" for="diagAltro">Altro</label>
                        </div>
                      </div>
                      <div class="col">
                        <input type="text" class="form-control" name="patologie_altro" placeholder="Altro (specificare)" />
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Familiarità per infarto, ictus, diabete o colesterolo alto?</label>
                    <select class="form-select" name="familiarita_cardiometabolica">
                      <option value="">-- Seleziona --</option>
                      <option value="si">Sì</option>
                      <option value="no">No</option>
                      <option value="non_so">Non so</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ha un familiare o caregiver che la aiuta nella gestione quotidiana?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="ha_caregiver_quotidiano" value="si" id="careDailySi" />
                        <label class="form-check-label" for="careDailySi">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="ha_caregiver_quotidiano" value="no" id="careDailyNo" />
                        <label class="form-check-label" for="careDailyNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Peso (kg)</label>
                    <input type="number" class="form-control" step="0.1" name="peso" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Altezza (cm)</label>
                    <input type="number" class="form-control" step="0.1" name="altezza" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">BMI</label>
                    <input type="number" class="form-control" step="0.1" name="bmi" />
                  </div>
                </div>
              </section>

              <!-- Step 2: Scheda anagrafica e presa in carico -->
              <section class="wizard-step d-none" data-step="2">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nome e Cognome</label>
                    <input type="text" class="form-control" name="nome_cognome" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Data di nascita</label>
                    <input type="date" class="form-control" name="data_nascita" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Sesso</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="sesso" value="M" id="pcSessoM" />
                        <label class="form-check-label" for="pcSessoM">M</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="sesso" value="F" id="pcSessoF" />
                        <label class="form-check-label" for="pcSessoF">F</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Telefono</label>
                    <input type="tel" class="form-control" name="telefono" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Caregiver (facoltativo)</label>
                    <input type="text" class="form-control" name="caregiver_nome" />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Patologia seguita:</label>
                    <div class="row row-cols-2 row-cols-md-3 g-2">
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologia_segnalata[]" value="diabete" id="patSegDiabete" />
                          <label class="form-check-label" for="patSegDiabete">Diabete</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologia_segnalata[]" value="bpco" id="patSegBpco" />
                          <label class="form-check-label" for="patSegBpco">BPCO</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologia_segnalata[]" value="ipertensione" id="patSegIpertensione" />
                          <label class="form-check-label" for="patSegIpertensione">Ipertensione</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologia_segnalata[]" value="dislipidemia" id="patSegDisli" />
                          <label class="form-check-label" for="patSegDisli">Dislipidemia</label>
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="patologia_segnalata[]" value="altro" id="patSegAltro" />
                          <label class="form-check-label" for="patSegAltro">Altro</label>
                        </div>
                      </div>
                      <div class="col">
                        <input type="text" class="form-control" name="patologia_segnalata_altro" placeholder="Altro (specificare)" />
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Note iniziali</label>
                    <textarea class="form-control" name="note_iniziali" rows="2"></textarea>
                  </div>
                  <div class="col-12">
                    <h6 class="fw-semibold">Domande operative</h6>
                  </div>
                  <div class="col-12">
                    <label class="form-label">È mai stato allergico a farmaci, lattice, frutta o verdura, lattosio, frutta secca?</label>
                    <textarea class="form-control" name="allergie_varie" rows="2"></textarea>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Assume farmaci per terapie particolari?</label>
                    <textarea class="form-control" name="farmaci_terapie_particolari" rows="2"></textarea>
                  </div>
                  <div class="col-12">
                    <label class="form-label">C'è qualcuno che la aiuta nella corretta assunzione dei farmaci e negli orari stabiliti?</label>
                    <textarea class="form-control" name="aiuto_assunzione_farmaci" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nell'ultimo mese ha modificato o aumentato i dosaggi di farmaci abituali?</label>
                    <textarea class="form-control" name="modifica_dosaggi_ultimo_mese" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Se sì lo ha fatto di sua iniziativa senza consultare un medico?</label>
                    <textarea class="form-control" name="modifica_dosaggi_per_iniziativa" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ogni quanto tempo si reca dal suo medico per farsi visitare?</label>
                    <input type="text" class="form-control" name="frequenza_visite_medico" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ha avuto accesso al pronto soccorso nell'ultimo anno?</label>
                    <input type="text" class="form-control" name="accesso_pronto_soccorso" />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Che tipo di farmaci assume?</label>
                    <textarea class="form-control" name="tipo_farmaci_assunti" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Si dimentica mai di prendere i farmaci?</label>
                    <input type="text" class="form-control" name="dimenticanze_farmaci" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Se si, li assume ad orari differenti da quello stabilito o non li assume per niente il giorno in cui se ne è dimenticato?</label>
                    <input type="text" class="form-control" name="comportamento_dopo_dimenticanza" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ci sono state occasioni in cui non li ha presi intenzionalmente?</label>
                    <input type="text" class="form-control" name="non_assunzioni_intenzionali" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ha mai diminuito le dosi dei farmaci o smesso di prenderli intenzionalmente senza dirlo al suo medico?</label>
                    <input type="text" class="form-control" name="riduzione_dosi_senza_medico" />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Se si, per quale motivo? (Perché si sentiva peggio o perché si sentiva meglio dopo un periodo di terapia?)</label>
                    <textarea class="form-control" name="motivo_riduzione_dosi" rows="2"></textarea>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Assume integratori? Terapie omeopatiche o fitoterapiche?</label>
                    <input type="text" class="form-control" name="assunzione_integratori" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Se si quali ?</label>
                    <input type="text" class="form-control" name="tipologia_integratori" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Con quale frequenza ?</label>
                    <input type="text" class="form-control" name="frequenza_integratori" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Utilizza device per la terapia farmacologica della BPCO?</label>
                    <input type="text" class="form-control" name="usa_device_bpco" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Sa qual è il modo corretto di utilizzare il device?</label>
                    <input type="text" class="form-control" name="conoscenza_uso_device" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ha mai avuto problemi nell'utilizzo del device?</label>
                    <input type="text" class="form-control" name="problemi_device" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Usa il sistema per l'auto misurazione della pressione?</label>
                    <input type="text" class="form-control" name="usa_automisurazione_pressione" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Se si sa come usare il sistema</label>
                    <input type="text" class="form-control" name="conoscenza_uso_sistema_pressione" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Con quanta frequenza entra in farmacia per misurarsi la pressione?</label>
                    <input type="text" class="form-control" name="frequenza_pressione_in_farmacia" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ha mai misurato la glicemia?</label>
                    <input type="text" class="form-control" name="ha_misurato_glicemia" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ha mai avuto reazioni allergiche a qualche farmaco o integratore?</label>
                    <input type="text" class="form-control" name="reazioni_allergiche_farmaci_integratori" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ha mai effettuato un elettrocardiogramma?</label>
                    <input type="text" class="form-control" name="eseguito_ecg" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Un HOLTER cardiaco?</label>
                    <input type="text" class="form-control" name="eseguito_holter_cardiaco" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Un holter pressorio?</label>
                    <input type="text" class="form-control" name="eseguito_holter_pressorio" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Le è stato mai somministrato il vaccino antinfluenzale?</label>
                    <input type="text" class="form-control" name="vaccino_antinfluenzale" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Se si lo effettua ogni anno?</label>
                    <input type="text" class="form-control" name="vaccino_antinfluenzale_annuale" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Le è stato mai somministrato qualche altro tipo di vaccino?</label>
                    <input type="text" class="form-control" name="altri_vaccini" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Se si quale?</label>
                    <input type="text" class="form-control" name="quali_altri_vaccini" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ha mai avuto una reazione allergica a qualche vaccino?</label>
                    <input type="text" class="form-control" name="reazioni_allergiche_vaccini" />
                  </div>
                </div>
              </section>

              <!-- Step 3: Questionario di aderenza base -->
              <section class="wizard-step d-none" data-step="3">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Terapie in corso (principi attivi):</label>
                    <textarea class="form-control" name="terapie_in_corso" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Dispositivo utilizzato (es. inalatore, glucometro, sfigmomanometro):</label>
                    <input type="text" class="form-control" name="dispositivo_utilizzato" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Dimentica di assumere le dosi?</label>
                    <select class="form-select" name="dimentica_dosi">
                      <option value="">-- Seleziona --</option>
                      <option value="mai">Mai</option>
                      <option value="talvolta">Talvolta</option>
                      <option value="spesso">Spesso</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Interrompe la terapia quando si sente meglio?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="interrompe_quando_sta_meglio" value="si" id="interrompeSi" />
                        <label class="form-check-label" for="interrompeSi">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="interrompe_quando_sta_meglio" value="no" id="interrompeNo" />
                        <label class="form-check-label" for="interrompeNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Riduce le dosi senza consultare il medico/farmacista?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="riduce_dosi_senza_consulto" value="si" id="riduceSi" />
                        <label class="form-check-label" for="riduceSi">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="riduce_dosi_senza_consulto" value="no" id="riduceNo" />
                        <label class="form-check-label" for="riduceNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Sa utilizzare correttamente i dispositivi per il controllo della sua terapia?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="sa_usare_dispositivi_controllo" value="si" id="usaDispSi" />
                        <label class="form-check-label" for="usaDispSi">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="sa_usare_dispositivi_controllo" value="no" id="usaDispNo" />
                        <label class="form-check-label" for="usaDispNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Esegue automisurazioni periodiche?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="esegue_automisurazioni" value="si" id="autoMisSi" />
                        <label class="form-check-label" for="autoMisSi">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="esegue_automisurazioni" value="no" id="autoMisNo" />
                        <label class="form-check-label" for="autoMisNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ultimo controllo (data):</label>
                    <input type="date" class="form-control" name="data_ultimo_controllo" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Accessi al Pronto Soccorso o ricoveri nell’ultimo anno:</label>
                    <select class="form-select" name="accessi_ps_ultimo_anno">
                      <option value="">-- Seleziona --</option>
                      <option value="nessuno">Nessuno</option>
                      <option value="1">1</option>
                      <option value=">1">>1</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Reazioni avverse note ai farmaci?</label>
                    <div class="d-flex gap-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="reazioni_avverse_farmaci" value="si" id="reazSi" />
                        <label class="form-check-label" for="reazSi">Sì</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="reazioni_avverse_farmaci" value="no" id="reazNo" />
                        <label class="form-check-label" for="reazNo">No</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Note aggiuntive:</label>
                    <textarea class="form-control" name="note_aderenza" rows="2"></textarea>
                  </div>
                </div>
              </section>

              <!-- Step 4: Questionari specifici per patologia - Base -->
              <section class="wizard-step d-none" data-step="4">
                <div class="mb-3">
                  <label class="form-label">Seleziona patologia</label>
                  <div class="d-flex flex-wrap gap-3">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="patologia_selezionata" value="diabete" id="patBaseDiabete" />
                      <label class="form-check-label" for="patBaseDiabete">Diabete</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="patologia_selezionata" value="bpco" id="patBaseBpco" />
                      <label class="form-check-label" for="patBaseBpco">BPCO</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="patologia_selezionata" value="ipertensione" id="patBaseIpertensione" />
                      <label class="form-check-label" for="patBaseIpertensione">Ipertensione</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="patologia_selezionata" value="dislipidemia" id="patBaseDisli" />
                      <label class="form-check-label" for="patBaseDisli">Dislipidemia</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="patologia_selezionata" value="altro" id="patBaseAltro" />
                      <label class="form-check-label" for="patBaseAltro">Altro</label>
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="diabete">
                  <h6 class="fw-semibold">Diabete – Questionario Base</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Misura regolarmente la glicemia?</label>
                      <select class="form-select" name="misura_glicemia">
                        <option value="">-- Seleziona --</option>
                        <option value="tutti_i_giorni">Tutti i giorni</option>
                        <option value="qualche_volta">Qualche volta</option>
                        <option value="raramente">Raramente</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ultimo valore glicemico (se noto):</label>
                      <input type="text" class="form-control" name="ultimo_valore_glicemico" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha eseguito l’emoglobina glicata (HbA1c) negli ultimi 6 mesi?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="hbA1c_ultimi_6_mesi" value="si" id="hba1cSi" />
                          <label class="form-check-label" for="hba1cSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="hbA1c_ultimi_6_mesi" value="no" id="hba1cNo" />
                          <label class="form-check-label" for="hba1cNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Segue una dieta specifica per il diabete?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="dieta_specifica_diabete" value="si" id="dietaDiabSi" />
                          <label class="form-check-label" for="dietaDiabSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="dieta_specifica_diabete" value="no" id="dietaDiabNo" />
                          <label class="form-check-label" for="dietaDiabNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Svolge attività fisica regolare?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="attivita_fisica_diabete" value="si" id="attFisDiabSi" />
                          <label class="form-check-label" for="attFisDiabSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="attivita_fisica_diabete" value="no" id="attFisDiabNo" />
                          <label class="form-check-label" for="attFisDiabNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Assume regolarmente i farmaci prescritti (compresse o insulina)?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="assunzione_regolare_farmaci_diabete" value="si" id="assFarmDiabSi" />
                          <label class="form-check-label" for="assFarmDiabSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="assunzione_regolare_farmaci_diabete" value="no" id="assFarmDiabNo" />
                          <label class="form-check-label" for="assFarmDiabNo">No</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="bpco">
                  <h6 class="fw-semibold">BPCO – Questionario Base</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Le manca il fiato quando cammina in piano o sale una rampa di scale?</label>
                      <select class="form-select" name="dispnea_sforzo">
                        <option value="">-- Seleziona --</option>
                        <option value="mai">Mai</option>
                        <option value="a_volte">A volte</option>
                        <option value="spesso">Spesso</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Tossisce o espelle muco durante la giornata?</label>
                      <select class="form-select" name="tosse_muco">
                        <option value="">-- Seleziona --</option>
                        <option value="no">No</option>
                        <option value="saltuariamente">Saltuariamente</option>
                        <option value="frequentemente">Frequentemente</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha limitazioni nelle attività quotidiane a causa del respiro?</label>
                      <select class="form-select" name="limitazioni_attivita_respiro">
                        <option value="">-- Seleziona --</option>
                        <option value="no">No</option>
                        <option value="qualche_volta">Qualche volta</option>
                        <option value="spesso">Spesso</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Dorme bene la notte?</label>
                      <select class="form-select" name="sonno_buono">
                        <option value="">-- Seleziona --</option>
                        <option value="si">Sì</option>
                        <option value="no_respiro">No (a causa del respiro)</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Si sente spesso stanco o senza energia?</label>
                      <select class="form-select" name="stanchezza_frequente">
                        <option value="">-- Seleziona --</option>
                        <option value="mai">Mai</option>
                        <option value="a_volte">A volte</option>
                        <option value="spesso">Spesso</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Utilizza regolarmente il suo inalatore?</label>
                      <select class="form-select" name="uso_regolare_inalatore">
                        <option value="">-- Seleziona --</option>
                        <option value="sempre">Sempre</option>
                        <option value="a_volte">A volte</option>
                        <option value="raramente">Raramente</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="ipertensione">
                  <h6 class="fw-semibold">Ipertensione / Rischio cardiovascolare – Base</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Ha mai avuto la pressione alta?</label>
                      <select class="form-select" name="ha_avuto_pressione_alta">
                        <option value="">-- Seleziona --</option>
                        <option value="no">No</option>
                        <option value="si">Sì</option>
                        <option value="non_so">Non so</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Misura regolarmente la pressione arteriosa?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="misura_pressione_regolare" value="si" id="misPressSi" />
                          <label class="form-check-label" for="misPressSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="misura_pressione_regolare" value="no" id="misPressNo" />
                          <label class="form-check-label" for="misPressNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Assume farmaci antipertensivi?</label>
                      <select class="form-select" name="assume_antipertensivi">
                        <option value="">-- Seleziona --</option>
                        <option value="si">Sì</option>
                        <option value="no">No</option>
                        <option value="non_regolarmente">Non regolarmente</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Riduce il consumo di sale e cibi salati?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="riduce_sale" value="si" id="riduceSaleSi" />
                          <label class="form-check-label" for="riduceSaleSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="riduce_sale" value="no" id="riduceSaleNo" />
                          <label class="form-check-label" for="riduceSaleNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Svolge attività fisica moderata?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="attivita_fisica_moderata" value="si" id="attFisModSi" />
                          <label class="form-check-label" for="attFisModSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="attivita_fisica_moderata" value="no" id="attFisModNo" />
                          <label class="form-check-label" for="attFisModNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha mai avuto dolore al petto, palpitazioni o svenimenti?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="dolore_petto_palpitazioni_svenimenti" value="si" id="dolorePettoSi" />
                          <label class="form-check-label" for="dolorePettoSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="dolore_petto_palpitazioni_svenimenti" value="no" id="dolorePettoNo" />
                          <label class="form-check-label" for="dolorePettoNo">No</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="dislipidemia">
                  <h6 class="fw-semibold">Dislipidemia / Colesterolo – Base</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Ha mai controllato il colesterolo o i trigliceridi?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="controllo_colesterolo_trigliceridi" value="si" id="collSi" />
                          <label class="form-check-label" for="collSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="controllo_colesterolo_trigliceridi" value="no" id="collNo" />
                          <label class="form-check-label" for="collNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Segue una dieta povera di grassi saturi e zuccheri?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="dieta_povera_grassi_zuccheri" value="si" id="dietaGrassiSi" />
                          <label class="form-check-label" for="dietaGrassiSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="dieta_povera_grassi_zuccheri" value="no" id="dietaGrassiNo" />
                          <label class="form-check-label" for="dietaGrassiNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha casi in famiglia di colesterolo alto, infarto o ictus precoce?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="familiarita_colesterolo_alto" value="si" id="famColSi" />
                          <label class="form-check-label" for="famColSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="familiarita_colesterolo_alto" value="no" id="famColNo" />
                          <label class="form-check-label" for="famColNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Fa attività fisica regolare?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="attivita_fisica_regolare_disli" value="si" id="attFisDisliSi" />
                          <label class="form-check-label" for="attFisDisliSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="attivita_fisica_regolare_disli" value="no" id="attFisDisliNo" />
                          <label class="form-check-label" for="attFisDisliNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha eseguito un controllo lipidico negli ultimi 12 mesi?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="controllo_lipidico_12_mesi" value="si" id="lipidicoSi" />
                          <label class="form-check-label" for="lipidicoSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="controllo_lipidico_12_mesi" value="no" id="lipidicoNo" />
                          <label class="form-check-label" for="lipidicoNo">No</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="altro">
                  <h6 class="fw-semibold">Altra patologia – Base</h6>
                  <textarea class="form-control" name="altro_base_notes" rows="4" placeholder="Note sulla patologia"></textarea>
                </div>
              </section>

              <!-- Step 5: Questionari specifici per patologia - Approfondito -->
              <section class="wizard-step d-none" data-step="5">
                <div class="d-none" data-condition-block="diabete">
                  <h6 class="fw-semibold">Diabete – Questionario Approfondito</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Ha avuto episodi di ipoglicemia (tremori, sudore, confusione)?</label>
                      <select class="form-select" name="episodi_ipoglicemia">
                        <option value="">-- Seleziona --</option>
                        <option value="mai">Mai</option>
                        <option value="talvolta">Talvolta</option>
                        <option value="spesso">Spesso</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Controlla la glicemia prima dei pasti o a orari fissi?</label>
                      <select class="form-select" name="controllo_glicemia_orari">
                        <option value="">-- Seleziona --</option>
                        <option value="sempre">Sempre</option>
                        <option value="a_volte">A volte</option>
                        <option value="mai">Mai</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Conserva correttamente l’insulina?</label>
                      <select class="form-select" name="conservazione_insulina">
                        <option value="">-- Seleziona --</option>
                        <option value="si">Sì</option>
                        <option value="no">No</option>
                        <option value="non_uso_insulina">Non uso insulina</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Effettua controlli periodici da diabetologo / oculista / podologo?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="controlli_periodici_specialisti" value="si" id="controlliSpecSi" />
                          <label class="form-check-label" for="controlliSpecSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="controlli_periodici_specialisti" value="no" id="controlliSpecNo" />
                          <label class="form-check-label" for="controlliSpecNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha difficoltà a rispettare orari e dosaggi della terapia?</label>
                      <select class="form-select" name="difficolta_rispettare_terapia">
                        <option value="">-- Seleziona --</option>
                        <option value="no">No</option>
                        <option value="a_volte">A volte</option>
                        <option value="si">Sì</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha formicolii o ferite che guariscono lentamente?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="formicolii_ferite_lente" value="si" id="feriteLenteSi" />
                          <label class="form-check-label" for="feriteLenteSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="formicolii_ferite_lente" value="no" id="feriteLenteNo" />
                          <label class="form-check-label" for="feriteLenteNo">No</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="bpco">
                  <h6 class="fw-semibold">BPCO – Questionario Approfondito</h6>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Dispnea (scala mMRC):</label>
                      <select class="form-select" name="dispnea_mmrc">
                        <option value="">-- Seleziona --</option>
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Quante riacutizzazioni ha avuto nell’ultimo anno?</label>
                      <select class="form-select" name="riacutizzazioni_ultimo_anno">
                        <option value="">-- Seleziona --</option>
                        <option value="nessuna">Nessuna</option>
                        <option value="1">1</option>
                        <option value=">=2">≥2</option>
                        <option value="ricovero_ospedaliero">Ricovero ospedaliero</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">È in grado di usare correttamente il dispositivo inalatorio?</label>
                      <select class="form-select" name="usa_correttamente_dispositivo_inalatorio">
                        <option value="">-- Seleziona --</option>
                        <option value="si">Sì</option>
                        <option value="no">No</option>
                        <option value="da_verificare">Da verificare</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha effetti collaterali (tosse post-inalazione, secchezza, tremori)?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="effetti_collaterali_inalatori" value="si" id="collInalSi" />
                          <label class="form-check-label" for="collInalSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="effetti_collaterali_inalatori" value="no" id="collInalNo" />
                          <label class="form-check-label" for="collInalNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Ha fatto una spirometria negli ultimi 12 mesi?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="spirometria_12_mesi" value="si" id="spiroSi" />
                          <label class="form-check-label" for="spiroSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="spirometria_12_mesi" value="no" id="spiroNo" />
                          <label class="form-check-label" for="spiroNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">FEV1 (se disponibile dopo spirometria indica il volume espiatorio forzato nel 1 secondo)</label>
                      <input type="text" class="form-control" name="fev1_percentuale" />
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="ipertensione">
                  <h6 class="fw-semibold">Ipertensione – Questionario Approfondito</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Ultimi valori pressori medi: _______ / _______ mmHg</label>
                      <input type="text" class="form-control" name="ultimi_valori_pressori" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha episodi di gonfiore a gambe o caviglie?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="gonfiore_gambe_caviglie" value="si" id="gonfioreSi" />
                          <label class="form-check-label" for="gonfioreSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="gonfiore_gambe_caviglie" value="no" id="gonfioreNo" />
                          <label class="form-check-label" for="gonfioreNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha familiarità per ictus o infarto precoce?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="familiarita_ictus_infarto_precoce" value="si" id="famIctusSi" />
                          <label class="form-check-label" for="famIctusSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="familiarita_ictus_infarto_precoce" value="no" id="famIctusNo" />
                          <label class="form-check-label" for="famIctusNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Assume regolarmente la terapia antipertensiva?</label>
                      <select class="form-select" name="aderenza_terapia_antipertensiva">
                        <option value="">-- Seleziona --</option>
                        <option value="sempre">Sempre</option>
                        <option value="a_volte">A volte</option>
                        <option value="no">No</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ha effettuato ECG o telecardiologia negli ultimi 12 mesi?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="ecg_telecardiologia_12_mesi" value="si" id="ecgSi" />
                          <label class="form-check-label" for="ecgSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="ecg_telecardiologia_12_mesi" value="no" id="ecgNo" />
                          <label class="form-check-label" for="ecgNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Presenta effetti collaterali dai farmaci (capogiri, tosse, stanchezza)?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="effetti_collaterali_antipertensivi" value="si" id="collAntiSi" />
                          <label class="form-check-label" for="collAntiSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="effetti_collaterali_antipertensivi" value="no" id="collAntiNo" />
                          <label class="form-check-label" for="collAntiNo">No</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="dislipidemia">
                  <h6 class="fw-semibold">Dislipidemia – Questionario Approfondito</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Valori recenti (se disponibili): Colesterolo Totale, LDL, HDL, Trigliceridi</label>
                      <div class="row g-2">
                        <div class="col-md-6">
                          <input type="text" class="form-control" name="col_totale" placeholder="Colesterolo Totale" />
                        </div>
                        <div class="col-md-6">
                          <input type="text" class="form-control" name="ldl" placeholder="LDL" />
                        </div>
                        <div class="col-md-6">
                          <input type="text" class="form-control" name="hdl" placeholder="HDL" />
                        </div>
                        <div class="col-md-6">
                          <input type="text" class="form-control" name="trigliceridi" placeholder="Trigliceridi" />
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Diagnosi di ipercolesterolemia familiare?</label>
                      <select class="form-select" name="ipercolesterolemia_familiare">
                        <option value="">-- Seleziona --</option>
                        <option value="si">Sì</option>
                        <option value="no">No</option>
                        <option value="in_valutazione">In valutazione</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Presenza di xantomi, arco corneale o xantelasmi?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="presenza_xantomi_arco_xantelasmi" value="si" id="xantomiSi" />
                          <label class="form-check-label" for="xantomiSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="presenza_xantomi_arco_xantelasmi" value="no" id="xantomiNo" />
                          <label class="form-check-label" for="xantomiNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Assunzione regolare del farmaco?</label>
                      <select class="form-select" name="assunzione_regolare_farmaco_lipidico">
                        <option value="">-- Seleziona --</option>
                        <option value="sempre">Sempre</option>
                        <option value="a_volte">A volte</option>
                        <option value="no">No</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Effetti collaterali da statine (dolori muscolari, stanchezza)?</label>
                      <div class="d-flex gap-3">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="effetti_collaterali_statine" value="si" id="collStatineSi" />
                          <label class="form-check-label" for="collStatineSi">Sì</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="effetti_collaterali_statine" value="no" id="collStatineNo" />
                          <label class="form-check-label" for="collStatineNo">No</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Ultimo controllo medico e follow-up: ___ / ___ / ____</label>
                      <input type="text" class="form-control" name="ultimo_controllo_medico_followup" />
                    </div>
                  </div>
                </div>
                <div class="d-none" data-condition-block="altro">
                  <h6 class="fw-semibold">Altra patologia – Approfondito</h6>
                  <textarea class="form-control" name="altro_approfondito_notes" rows="4" placeholder="Note sulla patologia"></textarea>
                </div>
              </section>

              <!-- Step 6: Note farmacista, follow-up e caregiver -->
              <section class="wizard-step d-none" data-step="6">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label">Punteggio di rischio / aderenza:</label>
                    <input type="number" class="form-control" name="risk_score" />
                  </div>
                  <div class="col-md-9">
                    <label class="form-label">Criticità rilevate:</label>
                    <textarea class="form-control" name="criticita_rilevate" rows="2"></textarea>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Educazione sanitaria / interventi proposti:</label>
                    <textarea class="form-control" name="educazione_sanitaria" rows="2"></textarea>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Follow-up programmato: ___ / ___ / ____</label>
                    <input type="date" class="form-control" name="followup_programmato" />
                  </div>
                </div>
                <hr />
                <div class="row g-3 mt-2">
                  <div class="col-12">
                    <h6 class="fw-semibold">Per il Caregiver</h6>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">È soddisfatto del questionario che le è stato sottoposto?</label>
                    <textarea class="form-control" name="caregiver_soddisfazione_questionario" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ha qualche altra informazione che secondo lei può essere utile al Farmacista per migliorare il monitoraggio dell’assistito?</label>
                    <textarea class="form-control" name="caregiver_altre_informazioni" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">È interessato a ricevere un REPORT mensile sullo stato di salute del suo Familiare tramite mail o whatsapp?</label>
                    <textarea class="form-control" name="caregiver_interesse_report" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">È favorevole a rilasciare presso questa Farmacia l’autorizzazione ad interagire direttamente con il medico curante del suo familiare?</label>
                    <textarea class="form-control" name="caregiver_autorizza_interazione_medico" rows="2"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">È favorevole ad autorizzare questa Farmacia al ritiro di ricette mediche e referti medici presso lo Studio Medico del suo familiare?</label>
                    <textarea class="form-control" name="caregiver_autorizza_ritiro_referti" rows="2"></textarea>
                  </div>
                </div>
              </section>

              <!-- Step 7: Consenso informato e firme -->
              <section class="wizard-step d-none" data-step="7">
                <div class="row g-3">
                  <div class="col-12">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="consenso_gdpr_cura" id="consensoGdprCura" />
                      <label class="form-check-label" for="consensoGdprCura">Acconsento al trattamento dei dati personali ai sensi del Regolamento UE 679/2016 (GDPR) per finalità di cura, monitoraggio e follow-up.</label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="consenso_contatto" id="consensoContatto" />
                      <label class="form-check-label" for="consensoContatto">Acconsento a essere contattato dalla farmacia (WhatsApp / Email / Telefono) per promemoria e aggiornamenti terapeutici.</label>
                    </div>
                    <label class="form-label mt-2">Canale di contatto preferito</label>
                    <input type="text" class="form-control" name="canale_contatto_preferito" />
                  </div>
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="consenso_dati_anonimi" id="consensoDatiAnonimi" />
                      <label class="form-check-label" for="consensoDatiAnonimi">Acconsento in forma facoltativa all’uso anonimo dei miei dati per fini statistici e di miglioramento del servizio.</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="border rounded p-3">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Firma paziente/caregiver</label>
                          <div class="mb-2">
                            <select class="form-select" id="signatureType">
                              <option value="graphical">Firma grafica</option>
                              <option value="digital">Firma digitale</option>
                            </select>
                          </div>
                          <div id="signatureCanvasWrapper" class="signature-wrapper border rounded p-2 mb-2">
                            <canvas id="consentSignaturePad" height="180"></canvas>
                          </div>
                          <div id="digitalSignatureWrapper" class="d-none mb-2">
                            <input type="text" class="form-control" name="digital_signature" placeholder="Inserisci nome e cognome per firma digitale" />
                          </div>
                          <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="clearSignatureButton">Cancella firma</button>
                            <button type="button" class="btn btn-outline-primary" id="saveSignatureButton">Salva firma</button>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Data</label>
                          <input type="date" class="form-control" name="data_firma" />
                          <label class="form-label mt-3">Firma farmacista</label>
                          <input type="text" class="form-control" name="firma_farmacista" />
                          <label class="form-label mt-3">Luogo</label>
                          <input type="text" class="form-control" name="luogo_firma" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>
            </div>
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
