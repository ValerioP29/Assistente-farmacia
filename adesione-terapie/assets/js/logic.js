// Business and payload normalization helpers for Adesione Terapie module.
import { formatDate, statusLabel, caregiverFullName, sanitizeHtml } from './utils.js';

export const defaultChecklistQuestions = [
    { key: 'aderenza', text: 'Il paziente sta seguendo la terapia come prescritto?', type: 'text' },
    { key: 'effetti_collaterali', text: 'Sono presenti effetti collaterali?', type: 'text' },
    { key: 'supporto', text: 'Il paziente necessita di supporto aggiuntivo?', type: 'text' },
    { key: 'aderenza_bool', text: 'Aderenza confermata', type: 'boolean' },
    { key: 'note_generali', text: 'Note generali', type: 'text' },
];

export function countTherapiesForPatient(therapies, patientId) {
    return therapies.filter(therapy => therapy.patient_id === patientId).length;
}

export function getChecklistForTherapy(checks, therapyId) {
    if (!therapyId) return null;
    return checks.find(check => check.therapy_id === therapyId && check.type === 'checklist') || null;
}

export function getChecklistQuestionsForTherapy(checks, therapyId) {
    const checklist = getChecklistForTherapy(checks, therapyId);
    return (checklist && Array.isArray(checklist.questions)) ? checklist.questions : [];
}

export function getCheckExecutions(checks, therapyId) {
    return checks.filter(check => check.therapy_id === therapyId && check.type !== 'checklist');
}

export function getLatestAnswersByQuestion(checks, therapyId) {
    const executions = getCheckExecutions(checks, therapyId);
    const latest = {};
    executions.forEach(execution => {
        (execution.answers || []).forEach(answer => {
            const key = answer.question || '';
            if (!key) return;
            const answeredAt = answer.created_at || execution.scheduled_at || '';
            if (!latest[key] || new Date(answeredAt) > new Date(latest[key].created_at || 0)) {
                latest[key] = { answer: answer.answer || '', created_at: answeredAt };
            }
        });
    });
    return latest;
}

export function slugifyQuestion(text, index = 0) {
    const cleaned = (text || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    if (cleaned) return cleaned;
    return `q${index + 1}`;
}

export function collectChecklistQuestions(checklistQuestionsList) {
    if (!checklistQuestionsList) return [];
    const rows = checklistQuestionsList.querySelectorAll('.checklist-question-row');
    const questions = [];
    rows.forEach((row, index) => {
        const text = row.querySelector('input')?.value.trim() || '';
        if (!text) return;
        const type = row.querySelector('select')?.value || 'text';
        const key = row.dataset.key || slugifyQuestion(text, index);
        questions.push({ key, text, type });
    });
    return questions;
}

export function prepareChecklistPayload(checklistQuestionsList, payloadInput) {
    const questions = collectChecklistQuestions(checklistQuestionsList);
    if (payloadInput) {
        payloadInput.value = JSON.stringify(questions);
    }
    return questions;
}

export function prepareCheckExecutionPayload(form, checkAnswersList, payloadInput) {
    const answers = {};
    if (form) {
        const assessmentField = form.querySelector('[name="assessment"]');
        const notesField = form.querySelector('[name="notes"]');
        const actionsField = form.querySelector('[name="actions"]');
        if (assessmentField) answers.assessment = assessmentField.value.trim();
        if (notesField) answers.notes = notesField.value.trim();
        if (actionsField) answers.actions = actionsField.value.trim();
    }

    if (checkAnswersList) {
        checkAnswersList.querySelectorAll('[data-question-key]').forEach(node => {
            const key = node.dataset.questionKey;
            const input = node.querySelector('input, textarea, select');
            if (!key || !input) return;
            answers[key] = input.value;
        });
    }

    if (payloadInput) {
        payloadInput.value = JSON.stringify(answers);
    }
    return answers;
}

export function prepareCaregiversPayload(container, hiddenInput) {
    if (!container || !hiddenInput) return [];
    const caregivers = [];
    container.querySelectorAll('.caregiver-row:not(.template)').forEach(row => {
        const caregiver = {
            first_name: row.querySelector('[data-field="name"]').value.trim(),
            last_name: row.querySelector('[data-field="last_name"]')?.value.trim() || '',
            type: row.querySelector('[data-field="role"]').value,
            phone: row.querySelector('[data-field="phone"]').value.trim(),
            email: row.querySelector('[data-field="email"]')?.value.trim() || '',
        };
        if (caregiver.first_name || caregiver.last_name || caregiver.phone) {
            caregivers.push(caregiver);
        }
    });
    hiddenInput.value = JSON.stringify(caregivers);
    return caregivers;
}

export function prepareQuestionnairePayload(form, hiddenInput) {
    if (!hiddenInput || !form) return {};
    const questionnaireInputs = form.querySelectorAll('.questionnaire-input');
    const questionnaire = {};
    questionnaireInputs.forEach(input => {
        const step = input.closest('.wizard-step');
        if (!step) return;
        const stepNumber = step.dataset.step;
        const questionKey = input.dataset.question;
        if (!questionnaire[stepNumber]) {
            questionnaire[stepNumber] = {};
        }
        questionnaire[stepNumber][questionKey] = input.value.trim();
    });
    hiddenInput.value = JSON.stringify(questionnaire);
    return questionnaire;
}

export function getSelectedPatientName(state) {
    if (!state.selectedPatientId) return '';
    const patient = state.patients.find(item => item.id === state.selectedPatientId);
    if (!patient) return '';
    if (patient.full_name) return patient.full_name;
    return `${patient.first_name || ''} ${patient.last_name || ''}`.trim();
}

export function setHiddenField(form, name, value) {
    if (!form) return;
    let input = form.querySelector(`[name="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }
    input.value = value;
}

export function updateSummaryPreview({
    dom,
    state,
    caregivers,
    questionnaire,
}) {
    if (!dom?.therapySummary || !dom?.therapyForm) return;

    const patientId = dom.therapyPatientSelect.value || state.selectedPatientId;
    const patient = state.patients.find(p => String(p.id) === String(patientId));
    const title = dom.therapyForm.querySelector('[name="therapy_title"]').value;
    const description = dom.therapyForm.querySelector('[name="therapy_description"]').value;
    const startDate = dom.therapyForm.querySelector('[name="start_date"]').value;
    const endDate = dom.therapyForm.querySelector('[name="end_date"]').value;
    const status = dom.therapyForm.querySelector('[name="status"]').value;

    let questionnaireHtml = '<ul class="list-unstyled mb-0">';
    Object.entries(questionnaire).forEach(([step, answers]) => {
        questionnaireHtml += `<li><strong>Step ${step}</strong><ul class="list-unstyled">`;
        Object.entries(answers).forEach(([question, answer]) => {
            if (!answer) return;
            questionnaireHtml += `<li><span class="text-muted">${sanitizeHtml(question)}</span>: ${sanitizeHtml(answer)}</li>`;
        });
        questionnaireHtml += '</ul></li>';
    });
    questionnaireHtml += '</ul>';

    dom.therapySummary.innerHTML = `
        <div class="summary-section">
            <h6>Paziente</h6>
            <p>${sanitizeHtml(patient ? patient.full_name : 'Nuovo paziente')}</p>
        </div>
        <div class="summary-section">
            <h6>Dettagli terapia</h6>
            <p><strong>Titolo:</strong> ${sanitizeHtml(title || 'Terapia')}</p>
            <p>${sanitizeHtml(description)}</p>
            <p><strong>Inizio:</strong> ${formatDate(startDate)} | <strong>Fine:</strong> ${formatDate(endDate)}</p>
            <p><strong>Stato:</strong> ${statusLabel(status)}</p>
        </div>
        <div class="summary-section">
            <h6>Caregiver</h6>
            ${caregivers.length ? caregivers.map(c => `<p>${sanitizeHtml(caregiverFullName(c))} (${sanitizeHtml(c.type || 'caregiver')}) - ${sanitizeHtml(c.phone || '')}</p>`).join('') : '<p class="text-muted">Nessun caregiver</p>'}
        </div>
        <div class="summary-section">
            <h6>Questionario</h6>
            ${questionnaireHtml}
        </div>
    `;
}

export function updateHiddenConsentFields({ dom, state }) {
    if (!dom?.therapyForm) return;
    const signerInput = dom.therapyForm.querySelector('[name="digital_signature"]');
    const consentNotesInput = dom.therapyForm.querySelector('[name="consent_notes"]');
    const ipMeta = document.querySelector('meta[name="client-ip"]');

    const signerName = (signerInput?.value.trim()) || getSelectedPatientName(state) || 'Paziente';
    const consentText = (consentNotesInput?.value.trim()) || 'Consenso informato registrato';
    const signatureImage = dom.signatureImageInput?.value || '';
    const signedAt = new Date().toISOString().replace('T', ' ').slice(0, 19);
    const ipAddress = (ipMeta?.content || window.CLIENT_IP || '').toString();

    setHiddenField(dom.therapyForm, 'signer_name', signerName);
    setHiddenField(dom.therapyForm, 'signer_relation', 'patient');
    setHiddenField(dom.therapyForm, 'consent_text', consentText);
    setHiddenField(dom.therapyForm, 'signature_image', signatureImage);
    setHiddenField(dom.therapyForm, 'signed_at', signedAt);
    setHiddenField(dom.therapyForm, 'ip_address', ipAddress);
}
