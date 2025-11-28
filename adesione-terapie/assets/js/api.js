// API layer for chronic-care Adesione Terapie module.

let routesBase = '';
let csrfToken = '';

export function configureApi(config = {}) {
  routesBase = config.routesBase || routesBase;
  csrfToken = config.csrfToken || csrfToken;
}

async function fetchJSON(url, options = {}) {
  const config = Object.assign(
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken,
      },
      credentials: 'same-origin',
    },
    options
  );

  const response = await fetch(url, config);
  const data = await response.json().catch(() => ({}));
  if (!response.ok || data.success === false) {
    const errorMessage = data.message || 'Errore durante la richiesta';
    throw new Error(errorMessage);
  }
  return data;
}

function postAction(action, payload = {}) {
  return fetchJSON(`${routesBase}?action=${encodeURIComponent(action)}`, {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export function saveChronicM1(payload) {
  return postAction('save_chronic_M1', payload);
}

export function saveAnamnesiGenerale(payload) {
  return postAction('save_anamnesi_generale', payload);
}

export function saveAnamnesiSpecifica(payload) {
  return postAction('save_anamnesi_specifica', payload);
}

export function saveAderenzaBase(payload) {
  return postAction('save_aderenza_base', payload);
}

export function saveConditionBase(payload) {
  return postAction('save_condition_base', payload);
}

export function saveConditionApprofondita(payload) {
  return postAction('save_condition_approfondita', payload);
}

export function saveFollowupIniziale(payload) {
  return postAction('save_followup_iniziale', payload);
}

export function saveCaregiverPreferences(payload) {
  return postAction('save_caregiver_preferences', payload);
}

export function saveConsensiFirma(payload) {
  return postAction('save_consensi_firma', payload);
}
