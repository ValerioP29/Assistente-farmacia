// State management for Adesione Terapie module.

const state = {
    patients: [],
    therapies: [],
    checks: [],
    reminders: [],
    reports: [],
    timeline: [],
    stats: {},
    selectedPatient: null,
    submissionFlags: {},
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
        selectedPatient: null,
        submissionFlags: {},
    });
}
