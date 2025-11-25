// API layer for Adesione Terapie module.

let routesBase = '';
let csrfToken = '';

export function configureApi(config = {}) {
    routesBase = config.routesBase || routesBase;
    csrfToken = config.csrfToken || csrfToken;
}

export async function fetchJSON(url, options = {}) {
    const config = Object.assign({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    }, options);

    if (config.method && config.method.toUpperCase() === 'POST') {
        config.headers['X-CSRF-Token'] = csrfToken;
    }

    const response = await fetch(url, config);
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
        const errorMessage = data.message || 'Errore durante la richiesta';
        throw new Error(errorMessage);
    }
    return data;
}

export async function loadInitialData() {
    const response = await fetchJSON(`${routesBase}?action=init`);
    return response?.data || {};
}

function buildFormData(payload) {
    if (payload instanceof FormData) {
        return payload;
    }
    const formData = new FormData();
    Object.entries(payload || {}).forEach(([key, value]) => formData.append(key, value));
    return formData;
}

async function postAction(action, payload) {
    const formData = buildFormData(payload);
    if (!formData.has('action')) {
        formData.append('action', action);
    }
    return fetchJSON(routesBase, { method: 'POST', body: formData });
}

export function savePatient(payload) {
    return postAction('save_patient', payload);
}

export function saveTherapy(payload) {
    return postAction('save_therapy', payload);
}

export function saveCheck(payload) {
    return postAction('save_check_execution', payload);
}

export function saveChecklist(payload) {
    return postAction('save_checklist', payload);
}

export function saveReminder(payload) {
    return postAction('save_reminder', payload);
}

export function generateReport(payload) {
    return postAction('generate_report', payload);
}
