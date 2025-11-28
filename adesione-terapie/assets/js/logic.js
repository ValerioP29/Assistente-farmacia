// Business logic and payload builders for the chronic-care wizard.
import { getState } from './state.js';

function query(container, selector) {
  return container ? container.querySelector(selector) : null;
}

function queryAll(container, selector) {
  return container ? Array.from(container.querySelectorAll(selector)) : [];
}

function readValue(container, name) {
  const field = query(container, `[name="${name}"]`);
  if (!field) return '';
  if (field.type === 'checkbox') {
    return field.checked;
  }
  if (field.type === 'radio') {
    const checked = query(container, `input[name="${name}"]:checked`);
    return checked ? checked.value : '';
  }
  return field.value?.trim?.() ?? '';
}

function readCheckboxGroup(container, name) {
  return queryAll(container, `input[name="${name}"]:checked`).map((el) => el.value);
}

function readCheckboxGroupArray(container, name) {
  return queryAll(container, `input[name="${name}[]"]:checked`).map((el) => el.value);
}

function maybeNumber(value) {
  if (value === '' || value === null || value === undefined) return '';
  const num = Number(value);
  return Number.isNaN(num) ? value : num;
}

function collectM1(container) {
  return {
    family_members_count: readValue(container, 'family_members_count'),
    support_non_cohabiting: readValue(container, 'support_non_cohabiting'),
    education_level: readValue(container, 'education_level'),
    has_caregiver: readValue(container, 'has_caregiver'),
    patient_identity: {
      patient_name: readValue(container, 'patient_name'),
      patient_birth_date: readValue(container, 'patient_birth_date'),
      patient_phone: readValue(container, 'patient_phone'),
      patient_email: readValue(container, 'patient_email'),
    },
    caregiver_identity: {
      caregiver_name: readValue(container, 'caregiver_name'),
      caregiver_relation: readValue(container, 'caregiver_relation'),
      caregiver_phone: readValue(container, 'caregiver_phone'),
      caregiver_email: readValue(container, 'caregiver_email'),
    },
    family_contacts: readValue(container, 'family_contacts'),
    doctor_reference: readValue(container, 'doctor_reference'),
    specialist_reference: readValue(container, 'specialist_reference'),
  };
}

function collectAnamnesiGenerale(container) {
  const sesso = readValue(container, 'sesso');
  return {
    eta: readValue(container, 'eta'),
    sesso,
    stato_fertilita_donna: sesso === 'F' ? readValue(container, 'stato_fertilita_donna') : '',
    stato_salute_generale: readValue(container, 'stato_salute_generale'),
    fumatore_status: readValue(container, 'fumatore_status'),
    sigarette_per_giorno: readValue(container, 'sigarette_per_giorno'),
    attivita_fisica_regolare: readValue(container, 'attivita_fisica_regolare'),
    dieta_controllata: readValue(container, 'dieta_controllata'),
    farmaci_continuativi: readValue(container, 'farmaci_continuativi'),
    difficolta_gestione_terapia: readValue(container, 'difficolta_gestione_terapia'),
    patologie_diagnosticate: readCheckboxGroupArray(container, 'patologie_diagnosticate'),
    patologie_altro: readValue(container, 'patologie_altro'),
    familiarita_cardiometabolica: readValue(container, 'familiarita_cardiometabolica'),
    ha_caregiver_quotidiano: readValue(container, 'ha_caregiver_quotidiano'),
    parametri_biometrici: {
      peso: maybeNumber(readValue(container, 'peso')),
      altezza: maybeNumber(readValue(container, 'altezza')),
      bmi: maybeNumber(readValue(container, 'bmi')),
    },
  };
}

function collectAnamnesiSpecifica(container) {
  const patologie = readCheckboxGroupArray(container, 'patologia_segnalata') || [];
  return {
    nome_cognome: readValue(container, 'nome_cognome'),
    data_nascita: readValue(container, 'data_nascita'),
    sesso: readValue(container, 'sesso'),
    telefono: readValue(container, 'telefono'),
    email: readValue(container, 'email'),
    caregiver_nome: readValue(container, 'caregiver_nome'),
    patologia_segnalata: patologie,
    patologia_segnalata_altro: readValue(container, 'patologia_segnalata_altro'),
    note_iniziali: readValue(container, 'note_iniziali'),
    allergie_varie: readValue(container, 'allergie_varie'),
    farmaci_terapie_particolari: readValue(container, 'farmaci_terapie_particolari'),
    aiuto_assunzione_farmaci: readValue(container, 'aiuto_assunzione_farmaci'),
    modifica_dosaggi_ultimo_mese: readValue(container, 'modifica_dosaggi_ultimo_mese'),
    modifica_dosaggi_per_iniziativa: readValue(container, 'modifica_dosaggi_per_iniziativa'),
    frequenza_visite_medico: readValue(container, 'frequenza_visite_medico'),
    accesso_pronto_soccorso: readValue(container, 'accesso_pronto_soccorso'),
    tipo_farmaci_assunti: readValue(container, 'tipo_farmaci_assunti'),
    dimenticanze_farmaci: readValue(container, 'dimenticanze_farmaci'),
    comportamento_dopo_dimenticanza: readValue(container, 'comportamento_dopo_dimenticanza'),
    non_assunzioni_intenzionali: readValue(container, 'non_assunzioni_intenzionali'),
    riduzione_dosi_senza_medico: readValue(container, 'riduzione_dosi_senza_medico'),
    motivo_riduzione_dosi: readValue(container, 'motivo_riduzione_dosi'),
    assunzione_integratori: readValue(container, 'assunzione_integratori'),
    tipologia_integratori: readValue(container, 'tipologia_integratori'),
    frequenza_integratori: readValue(container, 'frequenza_integratori'),
    usa_device_bpco: readValue(container, 'usa_device_bpco'),
    conoscenza_uso_device: readValue(container, 'conoscenza_uso_device'),
    problemi_device: readValue(container, 'problemi_device'),
    usa_automisurazione_pressione: readValue(container, 'usa_automisurazione_pressione'),
    conoscenza_uso_sistema_pressione: readValue(container, 'conoscenza_uso_sistema_pressione'),
    frequenza_pressione_in_farmacia: readValue(container, 'frequenza_pressione_in_farmacia'),
    ha_misurato_glicemia: readValue(container, 'ha_misurato_glicemia'),
    reazioni_allergiche_farmaci_integratori: readValue(container, 'reazioni_allergiche_farmaci_integratori'),
    eseguito_ecg: readValue(container, 'eseguito_ecg'),
    eseguito_holter_cardiaco: readValue(container, 'eseguito_holter_cardiaco'),
    eseguito_holter_pressorio: readValue(container, 'eseguito_holter_pressorio'),
    vaccino_antinfluenzale: readValue(container, 'vaccino_antinfluenzale'),
    vaccino_antinfluenzale_annuale: readValue(container, 'vaccino_antinfluenzale_annuale'),
    altri_vaccini: readValue(container, 'altri_vaccini'),
    quali_altri_vaccini: readValue(container, 'quali_altri_vaccini'),
    reazioni_allergiche_vaccini: readValue(container, 'reazioni_allergiche_vaccini'),
  };
}

function collectAderenzaBase(container) {
  return {
    terapie_in_corso: readValue(container, 'terapie_in_corso'),
    dispositivo_utilizzato: readValue(container, 'dispositivo_utilizzato'),
    dimentica_dosi: readValue(container, 'dimentica_dosi'),
    interrompe_quando_sta_meglio: readValue(container, 'interrompe_quando_sta_meglio'),
    riduce_dosi_senza_consulto: readValue(container, 'riduce_dosi_senza_consulto'),
    sa_usare_dispositivi_controllo: readValue(container, 'sa_usare_dispositivi_controllo'),
    esegue_automisurazioni: readValue(container, 'esegue_automisurazioni'),
    data_ultimo_controllo: readValue(container, 'data_ultimo_controllo'),
    accessi_ps_ultimo_anno: readValue(container, 'accessi_ps_ultimo_anno'),
    reazioni_avverse_farmaci: readValue(container, 'reazioni_avverse_farmaci'),
    note_aderenza: readValue(container, 'note_aderenza'),
  };
}

function collectPatologiaBase(container) {
  const condition = readValue(container, 'patologia_selezionata');
  const answers = {};

  switch (condition) {
    case 'diabete':
      answers.misura_glicemia = readValue(container, 'misura_glicemia');
      answers.ultimo_valore_glicemico = readValue(container, 'ultimo_valore_glicemico');
      answers.hbA1c_ultimi_6_mesi = readValue(container, 'hbA1c_ultimi_6_mesi');
      answers.dieta_specifica_diabete = readValue(container, 'dieta_specifica_diabete');
      answers.attivita_fisica_diabete = readValue(container, 'attivita_fisica_diabete');
      answers.assunzione_regolare_farmaci_diabete = readValue(container, 'assunzione_regolare_farmaci_diabete');
      break;
    case 'bpco':
      answers.dispnea_sforzo = readValue(container, 'dispnea_sforzo');
      answers.tosse_muco = readValue(container, 'tosse_muco');
      answers.limitazioni_attivita_respiro = readValue(container, 'limitazioni_attivita_respiro');
      answers.sonno_buono = readValue(container, 'sonno_buono');
      answers.stanchezza_frequente = readValue(container, 'stanchezza_frequente');
      answers.uso_regolare_inalatore = readValue(container, 'uso_regolare_inalatore');
      break;
    case 'ipertensione':
      answers.ha_avuto_pressione_alta = readValue(container, 'ha_avuto_pressione_alta');
      answers.misura_pressione_regolare = readValue(container, 'misura_pressione_regolare');
      answers.assume_antipertensivi = readValue(container, 'assume_antipertensivi');
      answers.riduce_sale = readValue(container, 'riduce_sale');
      answers.attivita_fisica_moderata = readValue(container, 'attivita_fisica_moderata');
      answers.dolore_petto_palpitazioni_svenimenti = readValue(container, 'dolore_petto_palpitazioni_svenimenti');
      break;
    case 'dislipidemia':
      answers.controllo_colesterolo_trigliceridi = readValue(container, 'controllo_colesterolo_trigliceridi');
      answers.terapia_statine_ecc = readValue(container, 'terapia_statine_ecc');
      answers.dieta_povera_grassi_zuccheri = readValue(container, 'dieta_povera_grassi_zuccheri');
      answers.familiarita_colesterolo_alto = readValue(container, 'familiarita_colesterolo_alto');
      answers.attivita_fisica_regolare_disli = readValue(container, 'attivita_fisica_regolare_disli');
      answers.controllo_lipidico_12_mesi = readValue(container, 'controllo_lipidico_12_mesi');
      break;
    default:
      answers.altro_base_notes = readValue(container, 'altro_base_notes');
  }

  const state = getState();
  state.selectedCondition = condition || state.selectedCondition || null;

  return { condition, answers };
}

function collectPatologiaApprofondita(container) {
  const state = getState();
  const condition = readValue(container, 'patologia_selezionata') || state.selectedCondition || '';
  const answers = {};

  switch (condition) {
    case 'diabete':
      answers.episodi_ipoglicemia = readValue(container, 'episodi_ipoglicemia');
      answers.controllo_glicemia_orari = readValue(container, 'controllo_glicemia_orari');
      answers.conservazione_insulina = readValue(container, 'conservazione_insulina');
      answers.controlli_periodici_specialisti = readValue(container, 'controlli_periodici_specialisti');
      answers.difficolta_rispettare_terapia = readValue(container, 'difficolta_rispettare_terapia');
      answers.formicolii_ferite_lente = readValue(container, 'formicolii_ferite_lente');
      break;
    case 'bpco':
      answers.dispnea_mmrc = readValue(container, 'dispnea_mmrc');
      answers.riacutizzazioni_ultimo_anno = readValue(container, 'riacutizzazioni_ultimo_anno');
      answers.usa_correttamente_dispositivo_inalatorio = readValue(container, 'usa_correttamente_dispositivo_inalatorio');
      answers.effetti_collaterali_inalatori = readValue(container, 'effetti_collaterali_inalatori');
      answers.spirometria_12_mesi = readValue(container, 'spirometria_12_mesi');
      answers.fev1_percentuale = readValue(container, 'fev1_percentuale');
      break;
    case 'ipertensione':
      answers.ultimi_valori_pressori = readValue(container, 'ultimi_valori_pressori');
      answers.gonfiore_gambe_caviglie = readValue(container, 'gonfiore_gambe_caviglie');
      answers.familiarita_ictus_infarto_precoce = readValue(container, 'familiarita_ictus_infarto_precoce');
      answers.aderenza_terapia_antipertensiva = readValue(container, 'aderenza_terapia_antipertensiva');
      answers.ecg_telecardiologia_12_mesi = readValue(container, 'ecg_telecardiologia_12_mesi');
      answers.effetti_collaterali_antipertensivi = readValue(container, 'effetti_collaterali_antipertensivi');
      break;
    case 'dislipidemia':
      answers.valori_lipidici_recenti = {
        col_totale: readValue(container, 'col_totale'),
        ldl: readValue(container, 'ldl'),
        hdl: readValue(container, 'hdl'),
        trigliceridi: readValue(container, 'trigliceridi'),
      };
      answers.ipercolesterolemia_familiare = readValue(container, 'ipercolesterolemia_familiare');
      answers.presenza_xantomi_arco_xantelasmi = readValue(container, 'presenza_xantomi_arco_xantelasmi');
      answers.assunzione_regolare_farmaco_lipidico = readValue(container, 'assunzione_regolare_farmaco_lipidico');
      answers.effetti_collaterali_statine = readValue(container, 'effetti_collaterali_statine');
      answers.ultimo_controllo_medico_followup = readValue(container, 'ultimo_controllo_medico_followup');
      break;
    default:
      answers.altro_approfondito_notes = readValue(container, 'altro_approfondito_notes');
  }

  return { condition, answers };
}

function collectFollowup(container) {
  return {
    risk_score: readValue(container, 'risk_score'),
    criticita_rilevate: readValue(container, 'criticita_rilevate'),
    educazione_sanitaria: readValue(container, 'educazione_sanitaria'),
    followup_programmato: readValue(container, 'followup_programmato'),
  };
}

function collectCaregiverBlock(container) {
  return {
    caregiver_soddisfazione_questionario: readValue(container, 'caregiver_soddisfazione_questionario'),
    caregiver_altre_informazioni: readValue(container, 'caregiver_altre_informazioni'),
    caregiver_interesse_report: readValue(container, 'caregiver_interesse_report'),
    caregiver_autorizza_interazione_medico: readValue(container, 'caregiver_autorizza_interazione_medico'),
    caregiver_autorizza_ritiro_referti: readValue(container, 'caregiver_autorizza_ritiro_referti'),
  };
}

function collectConsensi(container) {
  return {
    consenso_gdpr_cura: !!readValue(container, 'consenso_gdpr_cura'),
    consenso_contatto: !!readValue(container, 'consenso_contatto'),
    canale_contatto_preferito: readValue(container, 'canale_contatto_preferito'),
    consenso_dati_anonimi: !!readValue(container, 'consenso_dati_anonimi'),
    firma_paziente_caregiver: readValue(container, 'firma_paziente_caregiver'),
    data_firma: readValue(container, 'data_firma'),
    firma_farmacista: readValue(container, 'firma_farmacista'),
    luogo_firma: readValue(container, 'luogo_firma'),
  };
}

export function collectStepPayload(stepKey, stepContainer) {
  switch (stepKey) {
    case 'm1':
      return collectM1(stepContainer);
    case '1':
      return collectAnamnesiGenerale(stepContainer);
    case '2':
      return collectAnamnesiSpecifica(stepContainer);
    case '3':
      return collectAderenzaBase(stepContainer);
    case '4':
      return collectPatologiaBase(stepContainer);
    case '5':
      return collectPatologiaApprofondita(stepContainer);
    case '6':
      return {
        followup: collectFollowup(stepContainer),
        caregiver_block: collectCaregiverBlock(stepContainer),
      };
    case '7':
      return collectConsensi(stepContainer);
    default:
      return {};
  }
}

export function validateStep(stepKey, payload) {
  if (stepKey === '7') {
    if (!payload.consenso_gdpr_cura) {
      return { valid: false, message: 'Il consenso GDPR per le finalità di cura è obbligatorio.' };
    }
  }
  if (stepKey === '4' || stepKey === '5') {
    const condition = payload.condition || payload?.patologia_base?.condition;
    if (!condition) {
      return { valid: false, message: 'Seleziona una patologia per proseguire.' };
    }
  }
  return { valid: true };
}

export function buildFinalPayload(state) {
  const followupBlock = state.wizardData.followup || {};
  const caregiverBlock = state.wizardData.caregiver_block || {};
  return {
    m1: state.wizardData.m1 || {},
    anamnesi_generale: state.wizardData.anamnesi_generale || {},
    anamnesi_specifica: state.wizardData.anamnesi_specifica || {},
    aderenza_base: state.wizardData.aderenza_base || {},
    patologia_base: state.wizardData.patologia_base || {},
    patologia_approfondita: state.wizardData.patologia_approfondita || {},
    followup: followupBlock,
    caregiver_block: caregiverBlock,
    consensi: state.wizardData.consensi || {},
  };
}
