// UI helpers for the chronic-care wizard.
import { WIZARD_STEPS } from './state.js';

export function buildDomReferences(moduleRoot = document) {
  const scoped = (sel) => moduleRoot.querySelector(sel);
  const scopedAll = (sel) => Array.from(moduleRoot.querySelectorAll(sel));
  const stepMap = {};
  scopedAll('.wizard-step').forEach((node) => {
    const key = node.dataset.step || node.getAttribute('data-step');
    if (key) {
      stepMap[key] = node;
    }
  });
  return {
    moduleRoot,
    stepMap,
    stepIndicators: scopedAll('[data-step-indicator]'),
    nextButton: scoped('#nextStepButton'),
    prevButton: scoped('#prevStepButton'),
    submitButton: scoped('#submitTherapyButton'),
    newPatientButton: scoped('#newPatientButton'),
    newTherapyButton: scoped('#newTherapyButton'),
    newCheckButton: scoped('#newCheckButton'),
    newReminderButton: scoped('#newReminderButton'),
    exportReportButton: scoped('#exportReportButton'),
    patientModal: scoped('#patientModal'),
    therapyModal: scoped('#therapyModal'),
    checkModal: scoped('#checkModal'),
    reminderModal: scoped('#reminderModal'),
    reportModal: scoped('#reportModal'),
    patientForm: scoped('#patientForm'),
    therapyForm: scoped('#therapyForm'),
    checkForm: scoped('#checkForm'),
    reminderForm: scoped('#reminderForm'),
    patientsList: scoped('#patientsList'),
    conditionSelector: scopedAll('[name="patologia_selezionata"]'),
    signatureImageInput: scoped('#signatureImageInput'),
    signatureCanvas: scoped('#consentSignaturePad'),
    signatureTypeSelect: scoped('#signatureType'),
    signatureCanvasWrapper: scoped('#signatureCanvasWrapper'),
    digitalSignatureWrapper: scoped('#digitalSignatureWrapper'),
    clearSignatureButton: scoped('#clearSignatureButton'),
    saveSignatureButton: scoped('#saveSignatureButton'),
    therapyIdInput: scoped('[name="therapy_id"]'),
    patientIdInput: scoped('[name="patient_id"]'),
  };
}

export function showStep(stepKey, dom) {
  Object.entries(dom.stepMap || {}).forEach(([key, node]) => {
    if (!node) return;
    node.classList.toggle('d-none', key !== stepKey);
  });
  if (dom.prevButton) {
    dom.prevButton.classList.toggle('disabled', stepKey === WIZARD_STEPS[0]);
  }
  if (dom.nextButton) {
    dom.nextButton.classList.toggle('d-none', stepKey === WIZARD_STEPS[WIZARD_STEPS.length - 1]);
  }
  if (dom.submitButton) {
    dom.submitButton.classList.toggle('d-none', stepKey !== WIZARD_STEPS[WIZARD_STEPS.length - 1]);
  }
  updateIndicators(stepKey, dom.stepIndicators);
}

export function updateIndicators(stepKey, indicators = []) {
  indicators.forEach((el) => {
    const key = el.dataset.stepIndicator;
    if (!key) return;
    el.classList.toggle('active', key === stepKey);
    el.classList.toggle('completed', WIZARD_STEPS.indexOf(key) < WIZARD_STEPS.indexOf(stepKey));
  });
}

export function toggleConditionBlocks(dom, condition) {
  const blocks = dom.moduleRoot.querySelectorAll('[data-condition-block]');
  blocks.forEach((block) => {
    const blockCondition = block.getAttribute('data-condition-block');
    block.classList.toggle('d-none', !!condition && blockCondition !== condition);
  });
}

export function showAlert(message, type = 'info') {
  if (typeof window.showAlert === 'function') {
    window.showAlert(message, type);
  } else {
    alert(message);
  }
}

export function renderPatientsList(dom, patients = []) {
  if (!dom.patientsList) return;
  if (!Array.isArray(patients) || patients.length === 0) {
    dom.patientsList.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-user-friends"></i>
        <p>Nessun paziente registrato. Aggiungi un nuovo paziente per iniziare.</p>
      </div>
    `;
    return;
  }

  const items = patients
    .map((patient) => {
      const name = [patient.first_name, patient.last_name].filter(Boolean).join(' ') || 'Paziente';
      const phone = patient.phone ? `<span class="text-muted small"><i class="fas fa-phone me-1"></i>${patient.phone}</span>` : '';
      const email = patient.email ? `<span class="text-muted small"><i class="fas fa-envelope me-1"></i>${patient.email}</span>` : '';
      return `
        <div class="patient-card">
          <div>
            <div class="fw-semibold">${name}</div>
            <div class="d-flex flex-wrap gap-2">${phone}${email}</div>
          </div>
        </div>
      `;
    })
    .join('');

  dom.patientsList.innerHTML = items;
}
