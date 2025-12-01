// UI helpers for the chronic-care wizard.
import { WIZARD_STEPS } from './state.js';

export function buildStepMap(root) {
  const stepMap = {};
  if (!root) return stepMap;
  root.querySelectorAll('.wizard-step').forEach((node) => {
    const key = node.dataset.step || node.getAttribute('data-step');
    if (key) {
      stepMap[key] = node;
    }
  });
  return stepMap;
}

export function buildDomReferences(moduleRoot = document) {
  const therapyRoot =
    moduleRoot?.querySelector?.('#therapyModal') ||
    document.querySelector('#therapyModal') ||
    moduleRoot ||
    document;

  const scoped = (sel) => therapyRoot.querySelector(sel);
  const scopedAll = (sel) => Array.from(therapyRoot.querySelectorAll(sel));
  const docScoped = (sel) => document.querySelector(sel);

  return {
    moduleRoot: therapyRoot,
    stepMap: buildStepMap(therapyRoot),
    stepIndicators: scopedAll('[data-step-indicator]'),
    nextButton: scoped('#nextStepButton'),
    prevButton: scoped('#prevStepButton'),
    submitButton: scoped('#submitTherapyButton'),
    newPatientButton: docScoped('#newPatientButton'),
    newTherapyButton: docScoped('#newTherapyButton'),
    newCheckButton: docScoped('#newCheckButton'),
    newReminderButton: docScoped('#newReminderButton'),
    exportReportButton: docScoped('#exportReportButton'),
    patientModal: docScoped('#patientModal'),
    therapyModal: therapyRoot,
    checkModal: docScoped('#checkModal'),
    reminderModal: docScoped('#reminderModal'),
    reportModal: docScoped('#reportModal'),
    patientForm: docScoped('#patientForm'),
    therapyForm: scoped('#therapyForm'),
    checkForm: docScoped('#checkForm'),
    reminderForm: docScoped('#reminderForm'),
    patientsList: docScoped('#patientsList'),
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

export function rebuildStepMap(dom) {
  if (!dom?.moduleRoot) return {};
  const nextMap = buildStepMap(dom.moduleRoot);
  dom.stepMap = nextMap;
  return nextMap;
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
