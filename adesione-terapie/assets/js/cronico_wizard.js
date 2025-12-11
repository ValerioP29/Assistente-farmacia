// Wizard Terapia Cronico - logica completa
let therapyWizardState = {
    patient: {},
    primary_condition: null,
    initial_notes: null,
    general_anamnesis: {},
    detailed_intake: {},
    therapy_assistants: [],
    adherence_base: {},
    condition_survey: {},
    risk_score: null,
    flags: {},
    notes_initial: null,
    follow_up_date: null,
    consent: {}
};

let currentWizardStep = 1;
const totalWizardSteps = 6;
let currentTherapyId = null;
let wizardModalInstance = null;

function getDefaultWizardState() {
    return {
        patient: {},
        primary_condition: null,
        initial_notes: null,
        general_anamnesis: {},
        detailed_intake: {},
        therapy_assistants: [],
        adherence_base: {},
        condition_survey: {
            condition_type: null,
            level: 'base',
            answers: {},
            compiled_at: null
        },
        risk_score: null,
        flags: {},
        notes_initial: null,
        follow_up_date: null,
        consent: {
            signer_name: '',
            signer_relation: 'patient',
            signed_at: '',
            pharmacist_name: '',
            place: '',
            scopes: {
                care_followup: false,
                contact_for_reminders: false,
                anonymous_stats: false,
                contact_channel_preference: ''
            },
            signer_role: null
        }
    };
}

function openTherapyWizard(therapyId = null) {
    initWizard(therapyId);
}

async function initWizard(therapyId = null) {
    currentTherapyId = therapyId;
    therapyWizardState = getDefaultWizardState();
    currentWizardStep = 1;

    buildWizardModalShell();
    showWizardModal();

    if (therapyId) {
        await loadTherapyForEdit(therapyId);
    }

    renderCurrentStep();
}

function buildWizardModalShell() {
    const container = document.getElementById('therapyWizardModal');
    if (!container) return;

    container.className = 'modal fade';
    container.tabIndex = -1;
    container.setAttribute('aria-hidden', 'true');

    container.innerHTML = `
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-md me-2"></i>Wizard Terapia Cronico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="wizardStepIndicator" class="mb-3 small text-muted"></div>
                    <div id="wizardContent"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-outline-secondary" id="wizardPrevBtn"><i class="fas fa-arrow-left me-2"></i>Indietro</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-primary" id="wizardNextBtn">Avanti<i class="fas fa-arrow-right ms-2"></i></button>
                        <button type="button" class="btn btn-primary d-none" id="wizardSubmitBtn"><i class="fas fa-save me-2"></i>Conferma e salva</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.addEventListener('hidden.bs.modal', () => {
        container.innerHTML = '';
    });

    const prevBtn = container.querySelector('#wizardPrevBtn');
    const nextBtn = container.querySelector('#wizardNextBtn');
    const submitBtn = container.querySelector('#wizardSubmitBtn');

    if (prevBtn) prevBtn.addEventListener('click', prevStep);
    if (nextBtn) nextBtn.addEventListener('click', nextStep);
    if (submitBtn) submitBtn.addEventListener('click', submitTherapy);
}

function showWizardModal() {
    const container = document.getElementById('therapyWizardModal');
    if (!container) return;
    wizardModalInstance = new bootstrap.Modal(container);
    wizardModalInstance.show();
}

async function loadTherapyForEdit(therapyId) {
    try {
        const response = await fetch(`api/therapies.php?id=${therapyId}`);
        const result = await response.json();
        if (result.success && Array.isArray(result.data?.items) && result.data.items.length) {
            const t = result.data.items[0];
            therapyWizardState.patient = {
                id: t.patient_id,
                first_name: t.first_name,
                last_name: t.last_name,
                birth_date: t.birth_date || '',
                codice_fiscale: t.codice_fiscale || '',
                phone: t.phone || '',
                email: t.email || '',
                notes: t.notes || ''
            };
            therapyWizardState.primary_condition = t.primary_condition || t.therapy_title || null;
            therapyWizardState.initial_notes = t.therapy_description || null;
            therapyWizardState.notes_initial = t.notes_initial || therapyWizardState.notes_initial;
            therapyWizardState.follow_up_date = t.follow_up_date || null;
            therapyWizardState.condition_survey.condition_type = therapyWizardState.primary_condition;
        }
    } catch (error) {
        console.error('Errore caricamento terapia', error);
    }
}

function renderCurrentStep() {
    updateStepIndicator();
    switch (currentWizardStep) {
        case 1:
            renderStep1();
            break;
        case 2:
            renderStep2();
            break;
        case 3:
            renderStep3();
            break;
        case 4:
            renderStep4();
            break;
        case 5:
            renderStep5();
            break;
        case 6:
            renderStep6();
            break;
        default:
            renderStep1();
    }
    updateNavigationButtons();
}

function updateStepIndicator() {
    const indicator = document.getElementById('wizardStepIndicator');
    if (!indicator) return;
    indicator.textContent = `Step ${currentWizardStep} di ${totalWizardSteps}`;
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('wizardPrevBtn');
    const nextBtn = document.getElementById('wizardNextBtn');
    const submitBtn = document.getElementById('wizardSubmitBtn');

    if (prevBtn) prevBtn.disabled = currentWizardStep === 1;
    if (nextBtn) nextBtn.classList.toggle('d-none', currentWizardStep === totalWizardSteps);
    if (submitBtn) submitBtn.classList.toggle('d-none', currentWizardStep !== totalWizardSteps);
}

function nextStep() {
    if (!validateStep(currentWizardStep)) return;
    if (currentWizardStep < totalWizardSteps) {
        currentWizardStep += 1;
        renderCurrentStep();
    }
}

function prevStep() {
    if (currentWizardStep > 1) {
        currentWizardStep -= 1;
        renderCurrentStep();
    }
}

function validateStep(step) {
    if (step === 1) {
        collectStep1Data();
        const patientValid = therapyWizardState.patient && (therapyWizardState.patient.id || (therapyWizardState.patient.first_name && therapyWizardState.patient.last_name));
        const conditionValid = !!therapyWizardState.primary_condition;
        if (!patientValid) {
            alert('Seleziona o inserisci un paziente.');
            return false;
        }
        if (!conditionValid) {
            alert('Seleziona la patologia principale.');
            return false;
        }
    }
    if (step === 6) {
        const gdprCareFollowup = document.getElementById('consentCareFollowup')?.checked ?? false;
        const gdprContact = document.getElementById('consentContact')?.checked ?? false;
        const gdprAnonymous = document.getElementById('consentAnonymous')?.checked ?? false;

        therapyWizardState.consent = therapyWizardState.consent || {};
        therapyWizardState.consent.scopes = therapyWizardState.consent.scopes || {};
        therapyWizardState.consent.scopes.care_followup = gdprCareFollowup;
        therapyWizardState.consent.scopes.contact_for_reminders = gdprContact;
        therapyWizardState.consent.scopes.anonymous_stats = gdprAnonymous;

        if (!gdprCareFollowup || !gdprContact || !gdprAnonymous) {
            alert('Il consenso GDPR è obbligatorio per procedere.');
            return false;
        }
    }
    return true;
}

function renderStep1() {
    const content = document.getElementById('wizardContent');
    if (!content) return;

    const patient = therapyWizardState.patient || {};
    const general = therapyWizardState.general_anamnesis || {};
    const detailed = therapyWizardState.detailed_intake || {};
    const primary_condition = therapyWizardState.primary_condition || '';

    content.innerHTML = `
        <div class="mb-4">
            <h5 class="mb-3">Dati paziente</h5>
            <div class="mb-3">
                <label class="form-label">Cerca paziente</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="patientSearchInput" placeholder="Nome, cognome o codice fiscale">
                    <button class="btn btn-outline-secondary" type="button" id="patientSearchBtn">Cerca</button>
                </div>
                <div class="mt-2" id="patientSearchResults"></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="patientFirstName" value="${escapeHtml(patient.first_name || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cognome *</label>
                    <input type="text" class="form-control" id="patientLastName" value="${escapeHtml(patient.last_name || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data di nascita</label>
                    <input type="date" class="form-control" id="patientBirthDate" value="${escapeHtml(patient.birth_date || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Codice fiscale</label>
                    <input type="text" class="form-control" id="patientCF" value="${escapeHtml(patient.codice_fiscale || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sesso</label>
                    <select class="form-select" id="patientGender">
                        <option value="">Seleziona</option>
                        <option value="M">M</option>
                        <option value="F">F</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-control" id="patientPhone" value="${escapeHtml(patient.phone || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="patientEmail" value="${escapeHtml(patient.email || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Caregiver (facoltativo)</label>
                    <input type="text" class="form-control" id="patientCaregiverNote" placeholder="es. figlia Lucia">
                </div>
                <div class="col-12">
                    <label class="form-label">Note iniziali</label>
                    <textarea class="form-control" id="patientNotes" rows="2">${escapeHtml(therapyWizardState.initial_notes || '')}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Patologia seguita *</label>
                    <select class="form-select" id="primaryCondition">
                        <option value="">Seleziona</option>
                        <option value="Diabete" ${primary_condition === 'Diabete' ? 'selected' : ''}>Diabete</option>
                        <option value="BPCO" ${primary_condition === 'BPCO' ? 'selected' : ''}>BPCO</option>
                        <option value="Ipertensione" ${primary_condition === 'Ipertensione' ? 'selected' : ''}>Ipertensione</option>
                        <option value="Dislipidemia" ${primary_condition === 'Dislipidemia' ? 'selected' : ''}>Dislipidemia</option>
                        <option value="Altro" ${primary_condition === 'Altro' ? 'selected' : ''}>Altro</option>
                    </select>
                    <input type="text" class="form-control mt-2" id="primaryConditionOther" placeholder="Specificare se Altro" value="${primary_condition && !['Diabete','BPCO','Ipertensione','Dislipidemia','Altro'].includes(primary_condition) ? escapeHtml(primary_condition) : ''}">
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="mb-3">Contesto familiare / sociale</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Membri famiglia</label>
                    <input type="number" class="form-control" id="familyMembers" value="${escapeHtml(general.family_members_count || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supporto esterno</label>
                    <select class="form-select" id="externalSupport">
                        <option value="">Seleziona</option>
                        <option value="true" ${general.has_external_support ? 'selected' : ''}>Sì</option>
                        <option value="false" ${general.has_external_support === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Titolo di studio</label>
                    <input type="text" class="form-control" id="educationLevel" value="${escapeHtml(general.education_level || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Presenza caregiver</label>
                    <select class="form-select" id="hasCaregiver">
                        <option value="">Seleziona</option>
                        <option value="true" ${general.has_caregiver ? 'selected' : ''}>Sì</option>
                        <option value="false" ${general.has_caregiver === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="mb-3">Anamnesi terapeutica e comportamentale</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Allergie (farmaci/lattice/alimenti/vaccini)</label>
                    <textarea class="form-control" id="anaAllergies" rows="2">${escapeHtml(general.allergies || '')}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Accessi PS</label>
                    <input type="text" class="form-control" id="anaErAccess" value="${escapeHtml(general.er_access || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Esami eseguiti (ECG/Holter)</label>
                    <input type="text" class="form-control" id="anaExams" value="${escapeHtml(general.exams || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Storico vaccini/reazioni</label>
                    <input type="text" class="form-control" id="anaVaccines" value="${escapeHtml(general.vaccines || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Aiuto nella gestione terapia</label>
                    <select class="form-select" id="intakeHelper">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.has_helper_for_medication ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.has_helper_for_medication === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modifiche dosaggio ultimo mese</label>
                    <select class="form-select" id="doseChanges">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.dose_changes_last_month ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.dose_changes_last_month === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modifiche di propria iniziativa</label>
                    <select class="form-select" id="doseChangesSelf">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.dose_changes_self_initiated ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.dose_changes_self_initiated === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipologia farmaci assunti</label>
                    <input type="text" class="form-control" id="drugTypes" value="${escapeHtml(detailed.drug_types || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dimenticanze</label>
                    <input type="text" class="form-control" id="forgetsMeds" value="${escapeHtml(detailed.forgets_medications || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Comportamento quando dimentica</label>
                    <input type="text" class="form-control" id="behaviourWhenForgets" value="${escapeHtml(detailed.behaviour_when_forgets || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Interruzioni intenzionali</label>
                    <select class="form-select" id="intentionalSkips">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.intentional_skips ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.intentional_skips === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Motivo interruzione</label>
                    <input type="text" class="form-control" id="intentionalStopReason" value="${escapeHtml(detailed.intentional_stop_reason || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Uso integratori/omeopatici/fitoterapici</label>
                    <select class="form-select" id="usesSupplements">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.uses_supplements ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.uses_supplements === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dettagli integratori</label>
                    <input type="text" class="form-control" id="supplementsDetails" value="${escapeHtml(detailed.supplements_details || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Frequenza integratori</label>
                    <input type="text" class="form-control" id="supplementsFrequency" value="${escapeHtml(detailed.supplements_frequency || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Uso device BPCO</label>
                    <select class="form-select" id="usesBPCODevice">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.uses_bpcop_device ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.uses_bpcop_device === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sa usare il device</label>
                    <select class="form-select" id="knowsDevice">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.knows_how_to_use_device ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.knows_how_to_use_device === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Problemi device</label>
                    <input type="text" class="form-control" id="deviceProblems" value="${escapeHtml(detailed.device_problems || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Uso automisurazione pressione</label>
                    <select class="form-select" id="selfMeasureBP">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.uses_self_measure_bp ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.uses_self_measure_bp === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Frequenza in farmacia (pressione)</label>
                    <input type="text" class="form-control" id="pharmacyBPFrequency" value="${escapeHtml(detailed.pharmacy_bp_frequency || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ha misurato la glicemia</label>
                    <select class="form-select" id="everMeasuredGlycemia">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.ever_measured_glycemia ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.ever_measured_glycemia === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reazioni allergiche farmaci/integratori</label>
                    <select class="form-select" id="drugAllergicReactions">
                        <option value="">Seleziona</option>
                        <option value="true" ${detailed.drug_or_supplement_allergic_reactions ? 'selected' : ''}>Sì</option>
                        <option value="false" ${detailed.drug_or_supplement_allergic_reactions === false ? 'selected' : ''}>No</option>
                    </select>
                </div>
            </div>
        </div>
    `;

    bindStep1Events();
}

function bindStep1Events() {
    const searchBtn = document.getElementById('patientSearchBtn');
    const searchInput = document.getElementById('patientSearchInput');
    if (searchBtn) searchBtn.addEventListener('click', searchPatients);
    if (searchInput) searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchPatients();
        }
    });
}

async function searchPatients() {
    const query = document.getElementById('patientSearchInput')?.value || '';
    const resultsBox = document.getElementById('patientSearchResults');
    if (!resultsBox) return;
    resultsBox.innerHTML = '<div class="text-muted">Ricerca in corso...</div>';
    try {
        const resp = await fetch(`api/patients.php?q=${encodeURIComponent(query)}`);
        const data = await resp.json();
        if (!data.success) {
            resultsBox.innerHTML = `<div class="text-danger">${escapeHtml(data.error || 'Errore ricerca pazienti')}</div>`;
            return;
        }
        const items = data.data?.items || [];
        if (!items.length) {
            resultsBox.innerHTML = '<div class="text-muted">Nessun paziente trovato</div>';
            return;
        }
        resultsBox.innerHTML = items.map((p) => `
            <div class="form-check">
                <input class="form-check-input" type="radio" name="patientSelect" value="${p.id}" data-patient='${JSON.stringify(p)}'>
                <label class="form-check-label">${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)} - ${escapeHtml(p.codice_fiscale || '')}</label>
            </div>
        `).join('');
        resultsBox.querySelectorAll('input[name="patientSelect"]').forEach((radio) => {
            radio.addEventListener('change', (e) => {
                const payload = JSON.parse(e.target.getAttribute('data-patient'));
                therapyWizardState.patient = payload;
                therapyWizardState.primary_condition = therapyWizardState.primary_condition || null;
                renderStep1();
            });
        });
    } catch (err) {
        resultsBox.innerHTML = '<div class="text-danger">Errore di rete</div>';
    }
}

function collectStep1Data() {
    const patient = therapyWizardState.patient || {};
    patient.first_name = document.getElementById('patientFirstName')?.value?.trim() || '';
    patient.last_name = document.getElementById('patientLastName')?.value?.trim() || '';
    patient.birth_date = document.getElementById('patientBirthDate')?.value || '';
    patient.codice_fiscale = document.getElementById('patientCF')?.value?.trim() || '';
    patient.phone = document.getElementById('patientPhone')?.value?.trim() || '';
    patient.email = document.getElementById('patientEmail')?.value?.trim() || '';
    patient.notes = document.getElementById('patientNotes')?.value || '';
    therapyWizardState.patient = patient;

    therapyWizardState.initial_notes = document.getElementById('patientNotes')?.value || '';
    const primary = document.getElementById('primaryCondition')?.value || '';
    const other = document.getElementById('primaryConditionOther')?.value || '';
    therapyWizardState.primary_condition = primary === 'Altro' && other ? other : (primary || other || null);

    therapyWizardState.general_anamnesis = {
        family_members_count: toNumberOrNull(document.getElementById('familyMembers')?.value),
        has_external_support: toBool(document.getElementById('externalSupport')?.value),
        education_level: document.getElementById('educationLevel')?.value || '',
        has_caregiver: toBool(document.getElementById('hasCaregiver')?.value),
        allergies: document.getElementById('anaAllergies')?.value || '',
        er_access: document.getElementById('anaErAccess')?.value || '',
        exams: document.getElementById('anaExams')?.value || '',
        vaccines: document.getElementById('anaVaccines')?.value || ''
    };

    therapyWizardState.detailed_intake = {
        has_helper_for_medication: toBool(document.getElementById('intakeHelper')?.value),
        dose_changes_last_month: toBool(document.getElementById('doseChanges')?.value),
        dose_changes_self_initiated: toBool(document.getElementById('doseChangesSelf')?.value),
        drug_types: document.getElementById('drugTypes')?.value || '',
        forgets_medications: document.getElementById('forgetsMeds')?.value || '',
        behaviour_when_forgets: document.getElementById('behaviourWhenForgets')?.value || '',
        intentional_skips: toBool(document.getElementById('intentionalSkips')?.value),
        intentional_stop_reason: document.getElementById('intentionalStopReason')?.value || '',
        uses_supplements: toBool(document.getElementById('usesSupplements')?.value),
        supplements_details: document.getElementById('supplementsDetails')?.value || '',
        supplements_frequency: document.getElementById('supplementsFrequency')?.value || '',
        uses_bpcop_device: toBool(document.getElementById('usesBPCODevice')?.value),
        knows_how_to_use_device: toBool(document.getElementById('knowsDevice')?.value),
        device_problems: document.getElementById('deviceProblems')?.value || '',
        uses_self_measure_bp: toBool(document.getElementById('selfMeasureBP')?.value),
        pharmacy_bp_frequency: document.getElementById('pharmacyBPFrequency')?.value || '',
        ever_measured_glycemia: toBool(document.getElementById('everMeasuredGlycemia')?.value),
        drug_or_supplement_allergic_reactions: toBool(document.getElementById('drugAllergicReactions')?.value)
    };
}

function renderStep2() {
    collectStep1Data();
    const content = document.getElementById('wizardContent');
    if (!content) return;

    const assistants = therapyWizardState.therapy_assistants || [];

    content.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Caregiver e familiari</h5>
            <button class="btn btn-sm btn-outline-primary" id="btnAddAssistant">Aggiungi nuovo assistente</button>
        </div>

        <div id="assistantsList" class="mb-4"></div>
        <div id="assistantForms" class="mb-4"></div>

        <div id="newAssistantForm" class="card mb-4 d-none">
            <div class="card-body">
                <h6 class="mb-3">Nuovo assistente</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="assistantFirstName">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cognome</label>
                        <input type="text" class="form-control" id="assistantLastName">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono</label>
                        <input type="text" class="form-control" id="assistantPhone">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="assistantEmail">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" id="assistantType">
                            <option value="familiare">Familiare</option>
                            <option value="caregiver">Caregiver</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Relazione con il paziente</label>
                        <input type="text" class="form-control" id="assistantRelation">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Canale di contatto preferito</label>
                        <select class="form-select" id="assistantPreferredContact">
                            <option value="">Seleziona</option>
                            <option value="phone">Telefono</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" id="assistantNotes" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" id="cancelAssistantBtn">Annulla</button>
                <button type="button" class="btn btn-primary" id="saveAssistantBtn">Salva</button>
            </div>
        </div>
    `;

    fetchAssistants();

    const addBtn = document.getElementById('btnAddAssistant');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            const formCard = document.getElementById('newAssistantForm');
            if (formCard) {
                formCard.classList.remove('d-none');
            }
        });
    }

    const cancelBtn = document.getElementById('cancelAssistantBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            hideAndResetAssistantForm();
        });
    }

    const saveBtn = document.getElementById('saveAssistantBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveNewAssistant);
    }

        renderAssistantForms(assistants);
    }

function renderAssistantForms(list) {
    const container = document.getElementById('assistantForms');
    if (!container) return;
    if (!list.length) {
        container.innerHTML = '<div class="text-muted">Nessun assistente selezionato</div>';
        return;
    }
    container.innerHTML = list.map((a, idx) => `
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong>${escapeHtml(a.full_name || `${a.first_name || ''} ${a.last_name || ''}`)}</strong>
                    <span class="badge bg-light text-dark">${escapeHtml(a.role || a.type || '')}</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ruolo</label>
                        <select class="form-select" data-assistant-index="${idx}" data-field="role">
                            <option value="caregiver" ${a.role === 'caregiver' ? 'selected' : ''}>Caregiver</option>
                            <option value="familiare" ${a.role === 'familiare' ? 'selected' : ''}>Familiare</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Canale contatto</label>
                        <select class="form-select" data-assistant-index="${idx}" data-field="contact_channel">
                            <option value="">Seleziona</option>
                            <option value="phone" ${a.contact_channel === 'phone' ? 'selected' : ''}>Telefono</option>
                            <option value="email" ${a.contact_channel === 'email' ? 'selected' : ''}>Email</option>
                            <option value="whatsapp" ${a.contact_channel === 'whatsapp' ? 'selected' : ''}>WhatsApp</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Report mensile</label>
                        <select class="form-select" data-assistant-index="${idx}" data-field="wants_monthly_report">
                            <option value="">Seleziona</option>
                            <option value="true" ${a.preferences_json?.wants_monthly_report ? 'selected' : ''}>Sì</option>
                            <option value="false" ${a.preferences_json && a.preferences_json.wants_monthly_report === false ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Canale report</label>
                        <select class="form-select" data-assistant-index="${idx}" data-field="report_channel">
                            <option value="">Seleziona</option>
                            <option value="email" ${a.preferences_json?.report_channel === 'email' ? 'selected' : ''}>Email</option>
                            <option value="whatsapp" ${a.preferences_json?.report_channel === 'whatsapp' ? 'selected' : ''}>WhatsApp</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Soddisfazione questionario</label>
                        <input type="text" class="form-control" data-assistant-index="${idx}" data-field="questionnaire_satisfaction" value="${escapeHtml(a.consents_json?.questionnaire_satisfaction || '')}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Info utili al farmacista</label>
                        <input type="text" class="form-control" data-assistant-index="${idx}" data-field="extra_info_for_pharmacist" value="${escapeHtml(a.consents_json?.extra_info_for_pharmacist || '')}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Autorizza contatto medico</label>
                        <select class="form-select" data-assistant-index="${idx}" data-field="can_contact_doctor">
                            <option value="">Seleziona</option>
                            <option value="true" ${a.consents_json?.can_contact_doctor ? 'selected' : ''}>Sì</option>
                            <option value="false" ${a.consents_json && a.consents_json.can_contact_doctor === false ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Autorizza ritiro documenti</label>
                        <select class="form-select" data-assistant-index="${idx}" data-field="can_collect_documents">
                            <option value="">Seleziona</option>
                            <option value="true" ${a.consents_json?.can_collect_documents ? 'selected' : ''}>Sì</option>
                            <option value="false" ${a.consents_json && a.consents_json.can_collect_documents === false ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.querySelectorAll('select, input').forEach((el) => {
        el.addEventListener('change', (e) => {
            const idx = parseInt(e.target.getAttribute('data-assistant-index'), 10);
            const field = e.target.getAttribute('data-field');
            if (Number.isNaN(idx) || !field) return;
            const val = e.target.value;
            const assistant = therapyWizardState.therapy_assistants[idx] || {};
            if (['role', 'contact_channel'].includes(field)) {
                assistant[field] = val || null;
            } else if (['wants_monthly_report', 'report_channel'].includes(field)) {
                assistant.preferences_json = assistant.preferences_json || {};
                assistant.preferences_json[field] = field === 'wants_monthly_report' ? toBool(val) : (val || null);
            } else {
                assistant.consents_json = assistant.consents_json || {};
                if (['can_contact_doctor', 'can_collect_documents'].includes(field)) {
                    assistant.consents_json[field] = toBool(val);
                } else {
                    assistant.consents_json[field] = val || '';
                }
            }
            therapyWizardState.therapy_assistants[idx] = assistant;
        });
    });
}

async function fetchAssistants() {
    const listContainer = document.getElementById('assistantsList');
    if (!listContainer) return;
    listContainer.innerHTML = '<div class="text-muted">Caricamento assistenti...</div>';
    try {
        const resp = await fetch('api/assistants.php');
        const data = await resp.json();
        if (!data.success) {
            listContainer.innerHTML = `<div class="text-danger">${escapeHtml(data.error || 'Errore caricamento assistenti')}</div>`;
            return;
        }
        const items = data.data?.items || data.data || [];
        if (!items.length) {
            listContainer.innerHTML = '<div class="text-muted">Nessun assistente registrato</div>';
            return;
        }
        listContainer.innerHTML = items.map((a) => `
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" value="${a.id}" id="assistant_${a.id}">
                <label class="form-check-label" for="assistant_${a.id}">${escapeHtml(a.first_name)} ${escapeHtml(a.last_name || '')} (${escapeHtml(a.type)})</label>
            </div>
        `).join('');

        // Preselect existing
        therapyWizardState.therapy_assistants.forEach((a) => {
            const cb = document.getElementById(`assistant_${a.assistant_id}`);
            if (cb) cb.checked = true;
        });

        listContainer.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
            cb.addEventListener('change', (e) => {
                const id = parseInt(e.target.value, 10);
                const found = therapyWizardState.therapy_assistants.find((x) => x.assistant_id === id);
                if (e.target.checked && !found) {
                    const assistantData = items.find((x) => x.id === id) || {};
                    therapyWizardState.therapy_assistants.push({
                        assistant_id: id,
                        first_name: assistantData.first_name,
                        last_name: assistantData.last_name,
                        role: assistantData.type || 'familiare',
                        contact_channel: assistantData.preferred_contact || null,
                        preferences_json: {},
                        consents_json: {}
                    });
                }
                if (!e.target.checked && found) {
                    therapyWizardState.therapy_assistants = therapyWizardState.therapy_assistants.filter((x) => x.assistant_id !== id);
                }
                renderAssistantForms(therapyWizardState.therapy_assistants);
            });
        });
    } catch (err) {
        listContainer.innerHTML = '<div class="text-danger">Errore di rete</div>';
    }
}

async function saveNewAssistant(event) {
   if (event) event.preventDefault();
    const payload = {
        first_name: document.getElementById('assistantFirstName')?.value?.trim() || '',
        last_name: document.getElementById('assistantLastName')?.value?.trim() || '',
        phone: document.getElementById('assistantPhone')?.value?.trim() || '',
        email: document.getElementById('assistantEmail')?.value?.trim() || '',
        type: document.getElementById('assistantType')?.value || 'familiare',
        relation_to_patient: document.getElementById('assistantRelation')?.value || '',
        preferred_contact: document.getElementById('assistantPreferredContact')?.value || '',
        notes: document.getElementById('assistantNotes')?.value || ''
    };

    if (!payload.first_name) {
        alert('Il nome assistente è obbligatorio');
        return;
    }

    try {
        const resp = await fetch('api/assistants.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.success) {
            hideAndResetAssistantForm();
            fetchAssistants();
        } else {
            alert(data.error || 'Errore salvataggio assistente');
        }
    } catch (err) {
        alert('Errore di rete');
    }
}

function hideAndResetAssistantForm() {
  const formCard = document.getElementById('newAssistantForm');
  if (formCard) {
      formCard.classList.add('d-none');
  }
  const fields = [
      'assistantFirstName',
      'assistantLastName',
      'assistantPhone',
      'assistantEmail',
      'assistantType',
      'assistantRelation',
      'assistantPreferredContact',
      'assistantNotes',
  ];
  fields.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      if (el.tagName === 'SELECT') el.value = '';
      else if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') el.value = '';
  });
}

function renderStep3() {
    const content = document.getElementById('wizardContent');
    if (!content) return;
    const a = therapyWizardState.adherence_base || {};
    content.innerHTML = `
        <h5 class="mb-3">Questionario di aderenza base</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Terapie in corso (principi attivi)</label>
                <input type="text" class="form-control" id="adCurrentTherapies" value="${escapeHtml(a.current_therapies || '')}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Dispositivo utilizzato</label>
                <input type="text" class="form-control" id="adDevicesUsed" value="${escapeHtml(a.devices_used || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Dimentica di assumere le dosi?</label>
                <select class="form-select" id="adForgets">
                    <option value="">Seleziona</option>
                    <option value="Mai" ${a.forgets_doses === 'Mai' ? 'selected' : ''}>Mai</option>
                    <option value="Talvolta" ${a.forgets_doses === 'Talvolta' ? 'selected' : ''}>Talvolta</option>
                    <option value="Spesso" ${a.forgets_doses === 'Spesso' ? 'selected' : ''}>Spesso</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Interrompe terapia quando si sente meglio?</label>
                <select class="form-select" id="adStopsBetter">
                    <option value="">Seleziona</option>
                    <option value="true" ${a.stops_when_better ? 'selected' : ''}>Sì</option>
                    <option value="false" ${a.stops_when_better === false ? 'selected' : ''}>No</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Riduce dosi senza consulto?</label>
                <select class="form-select" id="adReduces">
                    <option value="">Seleziona</option>
                    <option value="true" ${a.reduces_doses_without_consult ? 'selected' : ''}>Sì</option>
                    <option value="false" ${a.reduces_doses_without_consult === false ? 'selected' : ''}>No</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Sa usare correttamente i dispositivi?</label>
                <select class="form-select" id="adKnowDevices">
                    <option value="">Seleziona</option>
                    <option value="true" ${a.knows_how_to_use_devices ? 'selected' : ''}>Sì</option>
                    <option value="false" ${a.knows_how_to_use_devices === false ? 'selected' : ''}>No</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Esegue automisurazioni periodiche?</label>
                <select class="form-select" id="adSelfMonitoring">
                    <option value="">Seleziona</option>
                    <option value="true" ${a.does_self_monitoring ? 'selected' : ''}>Sì</option>
                    <option value="false" ${a.does_self_monitoring === false ? 'selected' : ''}>No</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Ultimo controllo</label>
                <input type="date" class="form-control" id="adLastCheck" value="${escapeHtml(a.last_check_date || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Accessi PS/ricoveri ultimo anno</label>
                <select class="form-select" id="adErVisits">
                    <option value="">Seleziona</option>
                    <option value="Nessuno" ${a.er_visits_last_year === 'Nessuno' ? 'selected' : ''}>Nessuno</option>
                    <option value="1" ${a.er_visits_last_year === '1' ? 'selected' : ''}>1</option>
                    <option value=">1" ${a.er_visits_last_year === '>1' ? 'selected' : ''}>>1</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Reazioni avverse note?</label>
                <select class="form-select" id="adAdverse">
                    <option value="">Seleziona</option>
                    <option value="true" ${a.known_adverse_reactions ? 'selected' : ''}>Sì</option>
                    <option value="false" ${a.known_adverse_reactions === false ? 'selected' : ''}>No</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Note aggiuntive</label>
                <textarea class="form-control" id="adNotes" rows="2">${escapeHtml(a.extra_notes || '')}</textarea>
            </div>
        </div>
    `;
}

function collectStep3Data() {
    therapyWizardState.adherence_base = {
        current_therapies: document.getElementById('adCurrentTherapies')?.value || '',
        devices_used: document.getElementById('adDevicesUsed')?.value || '',
        forgets_doses: document.getElementById('adForgets')?.value || '',
        stops_when_better: toBool(document.getElementById('adStopsBetter')?.value),
        reduces_doses_without_consult: toBool(document.getElementById('adReduces')?.value),
        knows_how_to_use_devices: toBool(document.getElementById('adKnowDevices')?.value),
        does_self_monitoring: toBool(document.getElementById('adSelfMonitoring')?.value),
        last_check_date: document.getElementById('adLastCheck')?.value || '',
        er_visits_last_year: document.getElementById('adErVisits')?.value || '',
        known_adverse_reactions: toBool(document.getElementById('adAdverse')?.value),
        extra_notes: document.getElementById('adNotes')?.value || ''
    };
}

function renderStep4() {
    collectStep3Data();
    const content = document.getElementById('wizardContent');
    if (!content) return;
    const survey = therapyWizardState.condition_survey || {};
    const condition = therapyWizardState.primary_condition || survey.condition_type || '';
    const answers = survey.answers || {};
    const compiledAt = survey.compiled_at || new Date().toISOString().slice(0, 19).replace('T', ' ');

    const answerRows = Object.keys(answers).map((key, idx) => `
        <div class="row g-2 mb-2 answer-row" data-index="${idx}">
            <div class="col-md-4"><input type="text" class="form-control answer-key" value="${escapeHtml(key)}" placeholder="Chiave"></div>
            <div class="col-md-8"><input type="text" class="form-control answer-value" value="${escapeHtml(answers[key] ?? '')}" placeholder="Valore"></div>
        </div>
    `).join('');

    content.innerHTML = `
        <h5 class="mb-3">Questionario specifico patologia</h5>
        <div class="mb-3">
            <label class="form-label">Patologia</label>
            <select class="form-select" id="surveyCondition">
                <option value="Diabete" ${condition === 'Diabete' ? 'selected' : ''}>Diabete</option>
                <option value="BPCO" ${condition === 'BPCO' ? 'selected' : ''}>BPCO</option>
                <option value="Ipertensione" ${condition === 'Ipertensione' ? 'selected' : ''}>Ipertensione</option>
                <option value="Dislipidemia" ${condition === 'Dislipidemia' ? 'selected' : ''}>Dislipidemia</option>
                <option value="Altro" ${condition === 'Altro' ? 'selected' : ''}>Altro</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Risposte (chiave → valore)</label>
            <div id="surveyAnswers">
                ${answerRows || '<div class="text-muted">Aggiungi domande specifiche</div>'}
            </div>
            <button class="btn btn-sm btn-outline-primary mt-2" id="addAnswerRow"><i class="fas fa-plus me-1"></i>Aggiungi campo</button>
            <div class="mt-2 text-muted small">Sezione commentata per domande specifiche per patologia (Diabete, BPCO, Ipertensione, Dislipidemia, Altro)</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Compiled at</label>
            <input type="text" class="form-control" id="compiledAt" value="${escapeHtml(compiledAt)}">
        </div>
    `;

    const addBtn = document.getElementById('addAnswerRow');
    if (addBtn) addBtn.addEventListener('click', () => {
        const container = document.getElementById('surveyAnswers');
        if (!container) return;
        const idx = container.querySelectorAll('.answer-row').length;
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 answer-row';
        row.setAttribute('data-index', idx);
        row.innerHTML = `
            <div class="col-md-4"><input type="text" class="form-control answer-key" placeholder="Chiave"></div>
            <div class="col-md-8"><input type="text" class="form-control answer-value" placeholder="Valore"></div>
        `;
        container.appendChild(row);
    });
}

function collectStep4Data() {
    const answers = {};
    document.querySelectorAll('#surveyAnswers .answer-row').forEach((row) => {
        const key = row.querySelector('.answer-key')?.value;
        const val = row.querySelector('.answer-value')?.value;
        if (key) answers[key] = val;
    });
    therapyWizardState.condition_survey = {
        condition_type: document.getElementById('surveyCondition')?.value || therapyWizardState.primary_condition,
        level: 'base',
        answers,
        compiled_at: document.getElementById('compiledAt')?.value || new Date().toISOString().slice(0, 19).replace('T', ' ')
    };
}

function renderStep5() {
    collectStep4Data();
    const content = document.getElementById('wizardContent');
    if (!content) return;
    content.innerHTML = `
        <h5 class="mb-3">Pianificazione, rischio, note</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Risk score</label>
                <input type="number" class="form-control" id="riskScore" value="${escapeHtml(therapyWizardState.risk_score || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Data follow-up</label>
                <input type="date" class="form-control" id="followUpDate" value="${escapeHtml(therapyWizardState.follow_up_date || '')}">
            </div>
            <div class="col-12">
                <label class="form-label">Flags (JSON)</label>
                <textarea class="form-control" id="flagsJson" rows="2">${escapeHtml(JSON.stringify(therapyWizardState.flags || {}))}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Note iniziali</label>
                <textarea class="form-control" id="notesInitial" rows="3">${escapeHtml(therapyWizardState.notes_initial || '')}</textarea>
            </div>
        </div>
    `;
}

function collectStep5Data() {
    therapyWizardState.risk_score = toNumberOrNull(document.getElementById('riskScore')?.value);
    therapyWizardState.follow_up_date = document.getElementById('followUpDate')?.value || null;
    therapyWizardState.notes_initial = document.getElementById('notesInitial')?.value || '';
    try {
        therapyWizardState.flags = JSON.parse(document.getElementById('flagsJson')?.value || '{}');
    } catch (e) {
        therapyWizardState.flags = {};
    }
}

function renderStep6() {
    collectStep5Data();
    const content = document.getElementById('wizardContent');
    if (!content) return;
    const consent = therapyWizardState.consent || {};
    const scopes = consent.scopes || {};
    content.innerHTML = `
        <h5 class="mb-3">Consenso informato e firme</h5>
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="consentCareFollowup" ${scopes.care_followup ? 'checked' : ''}>
                <label class="form-check-label">Acconsento al trattamento dati personali (GDPR) per finalità di cura, monitoraggio e follow-up</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="consentContact" ${scopes.contact_for_reminders ? 'checked' : ''}>
                <label class="form-check-label">Acconsento a essere contattato dalla farmacia per promemoria e aggiornamenti terapeutici</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="consentAnonymous" ${scopes.anonymous_stats ? 'checked' : ''}>
                <label class="form-check-label">Acconsento all'uso anonimo dei miei dati per fini statistici e miglioramento servizio</label>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Firma paziente/caregiver</label>
                <input type="text" class="form-control" id="signerName" value="${escapeHtml(consent.signer_name || '')}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Relazione firmatario</label>
                <select class="form-select" id="signerRelation">
                    <option value="patient" ${consent.signer_relation === 'patient' ? 'selected' : ''}>Paziente</option>
                    <option value="caregiver" ${consent.signer_relation === 'caregiver' ? 'selected' : ''}>Caregiver</option>
                    <option value="familiare" ${consent.signer_relation === 'familiare' ? 'selected' : ''}>Familiare</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Data firma</label>
                <input type="date" class="form-control" id="signedAt" value="${escapeHtml(consent.signed_at || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Canale contatto preferito</label>
                <select class="form-select" id="contactChannelPref">
                    <option value="">Seleziona</option>
                    <option value="whatsapp" ${scopes.contact_channel_preference === 'whatsapp' ? 'selected' : ''}>WhatsApp</option>
                    <option value="email" ${scopes.contact_channel_preference === 'email' ? 'selected' : ''}>Email</option>
                    <option value="phone" ${scopes.contact_channel_preference === 'phone' ? 'selected' : ''}>Telefono</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Luogo</label>
                <input type="text" class="form-control" id="consentPlace" value="${escapeHtml(consent.place || '')}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nome farmacista</label>
                <input type="text" class="form-control" id="pharmacistName" value="${escapeHtml(consent.pharmacist_name || '')}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Ruolo firmatario (testo libero)</label>
                <input type="text" class="form-control" id="signerRole" value="${escapeHtml(consent.signer_role || '')}">
            </div>
        </div>
    `;
}

function collectStep6Data() {
    therapyWizardState.consent = {
        signer_name: document.getElementById('signerName')?.value || '',
        signer_relation: document.getElementById('signerRelation')?.value || 'patient',
        signed_at: document.getElementById('signedAt')?.value || '',
        pharmacist_name: document.getElementById('pharmacistName')?.value || '',
        place: document.getElementById('consentPlace')?.value || '',
        scopes: {
            care_followup: document.getElementById('consentCareFollowup')?.checked || false,
            contact_for_reminders: document.getElementById('consentContact')?.checked || false,
            anonymous_stats: document.getElementById('consentAnonymous')?.checked || false,
            contact_channel_preference: document.getElementById('contactChannelPref')?.value || ''
        },
        signer_role: document.getElementById('signerRole')?.value || ''
    };
}

function assemblePayload() {
    collectStep6Data();
    return {
        patient: therapyWizardState.patient,
        primary_condition: therapyWizardState.primary_condition,
        initial_notes: therapyWizardState.initial_notes,
        general_anamnesis: therapyWizardState.general_anamnesis,
        detailed_intake: therapyWizardState.detailed_intake,
        therapy_assistants: therapyWizardState.therapy_assistants,
        adherence_base: therapyWizardState.adherence_base,
        condition_survey: therapyWizardState.condition_survey,
        risk_score: therapyWizardState.risk_score,
        flags: therapyWizardState.flags,
        notes_initial: therapyWizardState.notes_initial,
        follow_up_date: therapyWizardState.follow_up_date,
        consent: therapyWizardState.consent
    };
}

async function submitTherapy() {
    if (!validateStep(6)) return;
    const payload = assemblePayload();
    const isEdit = !!currentTherapyId;
    const url = isEdit ? `api/therapies.php?id=${currentTherapyId}` : 'api/therapies.php';
    const method = isEdit ? 'PUT' : 'POST';
    try {
        const resp = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.success) {
            alert('Terapia salvata con successo');
            wizardModalInstance?.hide();
            if (typeof loadTherapies === 'function') {
                loadTherapies();
            }
        } else {
            alert(data.error || 'Errore salvataggio terapia');
        }
    } catch (err) {
        alert('Errore di rete');
    }
}

function toBool(val) {
    if (val === 'true' || val === true) return true;
    if (val === 'false' || val === false) return false;
    return null;
}

function toNumberOrNull(val) {
    if (val === undefined || val === null || val === '') return null;
    const n = Number(val);
    return Number.isNaN(n) ? null : n;
}

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function sanitizeNullish(obj) {
    return obj || {};
}

window.openTherapyWizard = openTherapyWizard;
