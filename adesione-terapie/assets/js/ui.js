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
    conditionSelector: scopedAll('[name="patologia_selezionata"]'),
    signatureImageInput: scoped('#signatureImageInput'),
    signatureCanvas: scoped('#consentSignaturePad'),
    signatureTypeSelect: scoped('#signatureType'),
    signatureCanvasWrapper: scoped('#signatureCanvasWrapper'),
    digitalSignatureWrapper: scoped('#digitalSignatureWrapper'),
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
