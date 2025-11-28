// State management for the chronic-care wizard of Adesione Terapie.

export const WIZARD_STEPS = [
  'm1',
  '1',
  '2',
  '3',
  '4',
  '5',
  '6',
  '7',
];

const wizardDataTemplate = {
  m1: {},
  anamnesi_generale: {},
  anamnesi_specifica: {},
  aderenza_base: {},
  patologia_base: {},
  patologia_approfondita: {},
  followup: {},
  caregiver_block: {},
  consensi: {},
};

const state = {
  currentStep: 'm1',
  wizardData: { ...wizardDataTemplate },
  selectedCondition: null,
  routesBase: '',
  csrfToken: '',
  signaturePad: null,
  signaturePadDirty: false,
  signaturePadInitialized: false,
};

export function getState() {
  return state;
}

export function setState(partial) {
  Object.assign(state, partial);
}

export function resetWizardData() {
  state.wizardData = { ...wizardDataTemplate };
  state.currentStep = 'm1';
  state.selectedCondition = null;
}
