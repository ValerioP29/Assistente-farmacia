// Event bindings for the chronic-care wizard.
import * as api from './api.js';
import * as ui from './ui.js';
import * as logic from './logic.js';
import * as signature from './signature.js';
import { getState, resetWizardData, setState, WIZARD_STEPS } from './state.js';

function createModalInstance(element) {
  if (!element || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
    return null;
  }
  return new bootstrap.Modal(element);
}

function getBaseMetadata(dom) {
  return {
    therapy_id: dom.therapyIdInput?.value || '',
    patient_id: dom.patientIdInput?.value || '',
  };
}

function syncSignatureField(dom) {
  if (!dom.signatureImageInput) return;
  const target = dom.moduleRoot.querySelector('[name="firma_paziente_caregiver"]');
  if (target) {
    target.value = dom.signatureImageInput.value || '';
  }
}

async function persistStep(stepKey, payload, dom) {
  const meta = getBaseMetadata(dom);
  switch (stepKey) {
    case 'm1':
      return api.saveChronicM1({ ...meta, payload });
    case '1':
      return api.saveAnamnesiGenerale({ ...meta, payload });
    case '2':
      return api.saveAnamnesiSpecifica({ ...meta, payload });
    case '3':
      return api.saveAderenzaBase({ ...meta, payload });
    case '4':
      return api.saveConditionBase({ ...meta, payload });
    case '5':
      return api.saveConditionApprofondita({ ...meta, payload });
    case '6': {
      const followupPayload = payload.followup || {};
      const caregiverPayload = payload.caregiver_block || {};
      await api.saveFollowupIniziale({ ...meta, payload: followupPayload });
      return api.saveCaregiverPreferences({ ...meta, payload: caregiverPayload });
    }
    case '7':
      return api.saveConsensiFirma({ ...meta, payload });
    default:
      return Promise.resolve();
  }
}

function handleConditionSelection(dom) {
  const condition = dom.conditionSelector.find((input) => input.checked)?.value || '';
  setState({ selectedCondition: condition });
  ui.toggleConditionBlocks(dom, condition);
}

function bindConditionSelectors(dom) {
  if (!dom.conditionSelector) return;
  dom.conditionSelector.forEach((input) => {
    input.addEventListener('change', () => handleConditionSelection(dom));
  });
  handleConditionSelection(dom);
}

function navigateTo(stepKey, dom) {
  const state = getState();
  setState({ currentStep: stepKey });
  ui.showStep(stepKey, dom);
}

async function proceed(stepDelta, dom) {
  const state = getState();
  const currentIndex = WIZARD_STEPS.indexOf(state.currentStep);
  if (currentIndex === -1) return;
  const targetIndex = currentIndex + stepDelta;
  if (targetIndex < 0 || targetIndex >= WIZARD_STEPS.length) return;

  const currentStepKey = state.currentStep;
  const currentContainer = dom.stepMap[currentStepKey];
  if (!currentContainer) return;

  if (currentStepKey === '7') {
    signature.captureSignatureImage({ dom, state });
    syncSignatureField(dom);
  }

  const payload = logic.collectStepPayload(currentStepKey, currentContainer);
  const validation = logic.validateStep(currentStepKey, payload);
  if (!validation.valid) {
    ui.showAlert(validation.message || 'Compila i campi richiesti', 'warning');
    return;
  }

  const wizardData = { ...state.wizardData };
  if (currentStepKey === '6') {
    wizardData.followup = payload.followup || {};
    wizardData.caregiver_block = payload.caregiver_block || {};
  } else {
    const keyMap = {
      m1: 'm1',
      '1': 'anamnesi_generale',
      '2': 'anamnesi_specifica',
      '3': 'aderenza_base',
      '4': 'patologia_base',
      '5': 'patologia_approfondita',
      '7': 'consensi',
    };
    const targetKey = keyMap[currentStepKey];
    if (targetKey) {
      wizardData[targetKey] = payload;
    }
  }
  setState({ wizardData });

  try {
    await persistStep(currentStepKey, payload, dom);
  } catch (error) {
    ui.showAlert(error.message || 'Errore durante il salvataggio', 'danger');
    return;
  }

  const nextStepKey = WIZARD_STEPS[targetIndex];
  navigateTo(nextStepKey, dom);
}

export function initializeEvents({ routesBase, csrfToken, dom }) {
  api.configureApi({ routesBase, csrfToken });
  const state = getState();
  setState({ routesBase, csrfToken });

  const modals = {
    patient: createModalInstance(dom.patientModal),
    therapy: createModalInstance(dom.therapyModal),
    check: createModalInstance(dom.checkModal),
    reminder: createModalInstance(dom.reminderModal),
    report: createModalInstance(dom.reportModal),
  };

  if (dom.newPatientButton && modals.patient) {
    dom.newPatientButton.addEventListener('click', (e) => {
      e.preventDefault();
      modals.patient.show();
    });
  }

  if (dom.newTherapyButton && modals.therapy) {
    dom.newTherapyButton.addEventListener('click', (e) => {
      e.preventDefault();
      resetWizardData();
      ui.showStep(WIZARD_STEPS[0], dom);
      signature.clearSignature({ dom, state });
      signature.updateSignatureMode({ dom });
      modals.therapy.show();
    });
  }

  if (dom.newCheckButton && modals.check) {
    dom.newCheckButton.addEventListener('click', (e) => {
      e.preventDefault();
      modals.check.show();
    });
  }

  if (dom.newReminderButton && modals.reminder) {
    dom.newReminderButton.addEventListener('click', (e) => {
      e.preventDefault();
      modals.reminder.show();
    });
  }

  if (dom.exportReportButton && modals.report) {
    dom.exportReportButton.addEventListener('click', (e) => {
      e.preventDefault();
      modals.report.show();
    });
  }

  if (dom.prevButton) {
    dom.prevButton.addEventListener('click', (e) => {
      e.preventDefault();
      proceed(-1, dom);
    });
  }
  if (dom.nextButton) {
    dom.nextButton.addEventListener('click', (e) => {
      e.preventDefault();
      proceed(1, dom);
    });
  }
  if (dom.submitButton) {
    dom.submitButton.addEventListener('click', async (e) => {
      e.preventDefault();
      await proceed(0, dom);
      const finalPayload = logic.buildFinalPayload(getState());
      console.debug('Final payload ready', finalPayload);
    });
  }

  if (dom.clearSignatureButton) {
    dom.clearSignatureButton.addEventListener('click', (e) => {
      e.preventDefault();
      signature.clearSignature({ dom, state });
    });
  }

  if (dom.saveSignatureButton) {
    dom.saveSignatureButton.addEventListener('click', (e) => {
      e.preventDefault();
      signature.saveSignature({ dom, state, showAlert: ui.showAlert });
    });
  }

  if (dom.signatureTypeSelect) {
    dom.signatureTypeSelect.addEventListener('change', () => {
      signature.updateSignatureMode({ dom });
    });
    signature.updateSignatureMode({ dom });
  }

  bindConditionSelectors(dom);
  ui.showStep(state.currentStep, dom);
  signature.initializeSignaturePad({ dom, state });
}
