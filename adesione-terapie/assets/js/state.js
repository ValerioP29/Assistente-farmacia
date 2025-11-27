// State management for Adesione Terapie module.

const state = {
    patients: [],
    therapies: [],
    checks: [],
    reminders: [],
    reports: [],
    timeline: [],
    stats: {},
    selectedPatientId: null,
    selectedTherapyId: null,
    therapySearchTerm: '',
    expandedPatients: {},
    currentTherapyStep: 1,
    signaturePad: null,
    signaturePadDirty: false,
    signaturePadInitialized: false,
    therapySubmitting: false,
    patientSubmitting: false,
    checkSubmitting: false,
    reminderSubmitting: false,
    reportGenerating: false,
};

export function getState() {
    return state;
}

export function setState(partial) {
    Object.assign(state, partial);
}

export function resetState() {
    setState({
        patients: [],
        therapies: [],
        checks: [],
        reminders: [],
        reports: [],
        timeline: [],
        stats: {},
        selectedPatientId: null,
        selectedTherapyId: null,
        therapySearchTerm: '',
        expandedPatients: {},
        currentTherapyStep: 1,
        signaturePad: null,
        signaturePadDirty: false,
        signaturePadInitialized: false,
        therapySubmitting: false,
        patientSubmitting: false,
        checkSubmitting: false,
        reminderSubmitting: false,
        reportGenerating: false,
    });
}
