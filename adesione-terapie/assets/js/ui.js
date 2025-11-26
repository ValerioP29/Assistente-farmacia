// UI rendering helpers for Adesione Terapie module.
import {
    sanitizeHtml,
    formatDate,
    formatDateTime,
    statusLabel,
    addDays,
    caregiverFullName,
} from './utils.js';
import {
    defaultChecklistQuestions,
    countTherapiesForPatient,
    getChecklistQuestionsForTherapy,
    getCheckExecutions,
    getLatestAnswersByQuestion,
    slugifyQuestion,
} from './logic.js';

export function renderStats({ dom, state }) {
    dom.statElements.forEach(element => {
        const key = element.dataset.stat;
        element.textContent = state.stats[key] ?? 0;
    });
}

export function renderPatients({ dom, state, onSelectPatient }) {
    if (!dom.patientsList) return;
    dom.patientsList.innerHTML = '';

    if (!state.patients.length) {
        dom.patientsList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-user-friends"></i>
                <p>Nessun paziente registrato.</p>
            </div>`;
        return;
    }

    const list = document.createElement('div');
    list.className = 'list-group patient-items';

    state.patients.forEach(patient => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'list-group-item list-group-item-action patient-item';
        if (patient.id === state.selectedPatientId) {
            item.classList.add('active');
        }
        item.dataset.patientId = patient.id;
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1">${sanitizeHtml(patient.full_name || 'Paziente senza nome')}</h6>
                    <small class="text-muted d-block">${patient.phone || ''}</small>
                    <small class="text-muted d-block">${patient.email || ''}</small>
                </div>
                <span class="badge bg-primary rounded-pill">${countTherapiesForPatient(state.therapies, patient.id)}</span>
            </div>`;
        item.addEventListener('click', () => onSelectPatient?.(patient.id));
        list.appendChild(item);
    });

    dom.patientsList.appendChild(list);
}

function renderQuestionnaireSummary(questionnaire) {
    if (!questionnaire || typeof questionnaire !== 'object') {
        return '<small class="text-muted">Questionario non disponibile</small>';
    }
    const items = Object.keys(questionnaire).reduce((acc, stepKey) => {
        const step = questionnaire[stepKey];
        if (!step) return acc;
        const answered = Object.values(step).filter(answer => (answer || '').toString().trim() !== '').length;
        if (!answered) return acc;
        acc.push(`<li><i class="fas fa-circle text-success me-2"></i>Step ${stepKey}: ${answered} risposte</li>`);
        return acc;
    }, []);

    if (!items.length) {
        return '<small class="text-muted">Nessuna risposta al questionario</small>';
    }

    return `<ul class="list-unstyled mb-0 questionnaire-summary">${items.join('')}</ul>`;
}

function formatChecklistAnswerView(answer) {
    const value = answer && typeof answer === 'object' ? answer.value ?? null : null;
    let valueLabel = 'Non compilato';
    if (value === 'yes') valueLabel = 'Sì';
    if (value === 'no') valueLabel = 'No';
    const note = answer && typeof answer === 'object' ? (answer.note || '').trim() : '';
    return { valueLabel, note };
}

export function buildChecklistSummaryHtml({ state, therapyId }) {
    const questions = getChecklistQuestionsForTherapy(state.checks, therapyId);
    if (!questions.length) {
        return `
            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-0">
                <div>
                    <strong>Checklist periodica della terapia</strong>
                    <div class="text-muted small mb-0">Nessuna checklist configurata</div>
                </div>
                <button class="btn btn-sm btn-outline-primary" data-action="open-checklist" data-therapy="${therapyId}">
                    <i class="fas fa-list-check me-1"></i>Configura checklist
                </button>
            </div>`;
    }

    const latestAnswers = getLatestAnswersByQuestion(state.checks, therapyId);
    let html = `
        <div class="card card-body border-0 shadow-sm mb-0 checklist-summary-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Checklist periodica della terapia</h6>
                <button class="btn btn-sm btn-outline-secondary" data-action="open-checklist" data-therapy="${therapyId}">
                    <i class="fas fa-pen me-1"></i>Modifica
                </button>
            </div>
            <ul class="list-group list-group-flush">
    `;

    questions.forEach(question => {
        const latest = latestAnswers[question.key];
        const parsed = formatChecklistAnswerView(latest?.answer || latest || {});
        const answerSafe = sanitizeHtml(parsed.valueLabel);
        const answeredAt = latest && latest.created_at ? formatDateTime(latest.created_at) : '';
        const noteBlock = parsed.note ? `<div class="text-muted small">Nota: ${sanitizeHtml(parsed.note)}</div>` : '';
        html += `
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">${sanitizeHtml(question.text || question.key)}</div>
                    <div class="text-muted small">${sanitizeHtml(question.key)}</div>
                </div>
                <div class="text-end">
                    <div>${answerSafe}</div>
                    ${noteBlock}
                    ${answeredAt ? `<small class="text-muted">${answeredAt}</small>` : ''}
                </div>
            </li>
        `;
    });

    html += '</ul></div>';
    return html;
}

export function renderAnswersList(answers = []) {
    if (!answers.length) {
        return '<p class="text-muted mb-1">Nessuna risposta registrata.</p>';
    }
    let html = '<ul class="list-group list-group-flush mb-2">';
    answers.forEach(answer => {
        const parsed = formatChecklistAnswerView(answer);
        const noteBlock = parsed.note ? `<div class="text-muted small">Nota: ${sanitizeHtml(parsed.note)}</div>` : '';
        html += `
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="fw-semibold">${sanitizeHtml(answer.question || 'Domanda')}</div>
                <div class="text-end">
                    <div>${sanitizeHtml(answer.value_label || parsed.valueLabel)}</div>
                    ${noteBlock}
                    ${answer.created_at ? `<small class="text-muted">${formatDateTime(answer.created_at)}</small>` : ''}
                </div>
            </li>
        `;
    });
    html += '</ul>';
    return html;
}

export function buildCheckHistoryHtml({ state, therapyId }) {
    const executions = getCheckExecutions(state.checks, therapyId);
    if (!executions.length) {
        return '<p class="text-muted mb-0">Nessuna esecuzione registrata.</p>';
    }

    let html = `<div class="accordion" id="checkHistory-${therapyId}">`;
    executions.forEach((check, index) => {
        const itemId = `check-${therapyId}-${index}`;
        const headerId = `heading-${itemId}`;
        const collapseId = `collapse-${itemId}`;
        const answers = renderAnswersList(check.answers || []);
        const scheduled = formatDateTime(check.scheduled_at || new Date().toISOString());
        const notesBlock = check.notes ? `<p class="mb-1"><strong>Note:</strong> ${sanitizeHtml(check.notes)}</p>` : '';
        const actionsBlock = check.actions ? `<p class="mb-1"><strong>Azioni:</strong> ${sanitizeHtml(check.actions)}</p>` : '';
        html += `
            <div class="accordion-item">
                <h2 class="accordion-header" id="${headerId}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                        Check del ${scheduled}
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse" aria-labelledby="${headerId}" data-bs-parent="#checkHistory-${therapyId}">
                    <div class="accordion-body">
                        ${answers}
                        ${notesBlock}
                        ${actionsBlock}
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

export function renderTherapies({ dom, state, onAction }) {
    if (!dom.therapiesContainer) return;
    dom.therapiesContainer.innerHTML = '';

    if (!state.selectedPatientId) {
        dom.therapiesContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-prescription-bottle-alt"></i>
                <p>Seleziona un paziente per visualizzare le terapie.</p>
            </div>`;
        dom.patientSummary.textContent = '';
        return;
    }

    const patient = state.patients.find(p => p.id === state.selectedPatientId);
    if (patient && dom.patientSummary) {
        dom.patientSummary.innerHTML = `
            <strong>${sanitizeHtml(patient.full_name)}</strong><br>
            <small>${patient.phone ? '📞 ' + sanitizeHtml(patient.phone) : ''}</small>
        `;
    }

    const therapies = state.therapies.filter(therapy => therapy.patient_id === state.selectedPatientId);

    if (!therapies.length) {
        dom.therapiesContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-notes-medical"></i>
                <p>Nessuna terapia registrata per questo paziente.</p>
            </div>`;
        return;
    }

    therapies.forEach(therapy => {
        const card = document.createElement('div');
        card.className = 'therapy-card';
        const caregivers = (therapy.caregivers || []).map(c => `<span class="badge bg-light text-dark me-1">${sanitizeHtml(caregiverFullName(c))}</span>`).join(' ');
        const lastCheck = therapy.last_check ? formatDateTime(therapy.last_check.scheduled_at) : 'Nessun controllo';
        const upcomingReminder = therapy.upcoming_reminder ? formatDateTime(therapy.upcoming_reminder.scheduled_at) : 'Nessun promemoria';

        const checklistSummary = buildChecklistSummaryHtml({ state, therapyId: therapy.id });
        const answersHistory = buildCheckHistoryHtml({ state, therapyId: therapy.id });
        card.innerHTML = `
            <div class="therapy-card-header">
                <div>
                    <h5>${sanitizeHtml(therapy.title || therapy.description || 'Terapia senza titolo')}</h5>
                    <p class="text-muted mb-1 small">${sanitizeHtml(therapy.description || '')}</p>
                    <span class="badge status-${sanitizeHtml(therapy.status || 'active')}">${statusLabel(therapy.status)}</span>
                </div>
                <div class="therapy-dates">
                    <small>Inizio: ${formatDate(therapy.start_date)}</small>
                    <small>Fine: ${therapy.end_date ? formatDate(therapy.end_date) : 'N/A'}</small>
                </div>
            </div>
            <div class="therapy-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>Caregiver</h6>
                        <div>${caregivers || '<small class="text-muted">Nessun caregiver registrato</small>'}</div>
                    </div>
                    <div class="col-md-6">
                        <h6>Questionario</h6>
                        ${renderQuestionnaireSummary(therapy.questionnaire)}
                    </div>
                    <div class="col-md-6">
                        <h6>Ultimo check</h6>
                        <p class="mb-0">${lastCheck}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Prossimo promemoria</h6>
                        <p class="mb-0">${upcomingReminder}</p>
                    </div>
                    <div class="col-12 mt-2">
                        ${checklistSummary}
                        <div class="mt-3">
                            <h6 class="mb-2">Storico risposte</h6>
                            ${answersHistory}
                        </div>
                    </div>
                </div>
            </div>
            <div class="therapy-card-footer">
                <button class="btn btn-sm btn-outline-primary me-2" data-action="open-check" data-therapy="${therapy.id}">
                    <i class="fas fa-stethoscope me-1"></i>Nuovo check
                </button>
                <button class="btn btn-sm btn-outline-secondary me-2" data-action="open-checklist" data-therapy="${therapy.id}">
                    <i class="fas fa-list-check me-1"></i>Configura checklist
                </button>
                <button class="btn btn-sm btn-outline-warning me-2" data-action="open-reminder" data-therapy="${therapy.id}">
                    <i class="fas fa-bell me-1"></i>Promemoria
                </button>
                <button class="btn btn-sm btn-outline-success" data-action="open-report" data-therapy="${therapy.id}">
                    <i class="fas fa-file-medical me-1"></i>Genera report
                </button>
            </div>`;

        card.querySelectorAll('button[data-action]').forEach(button => {
            button.addEventListener('click', () => {
                const therapyId = parseInt(button.dataset.therapy, 10);
                onAction?.(button.dataset.action, therapyId);
            });
        });

        dom.therapiesContainer.appendChild(card);
    });
}

export function renderTimeline({ dom, state }) {
    if (!dom.timelineContainer) return;
    dom.timelineContainer.innerHTML = '';

    const filter = dom.timelineFilter ? dom.timelineFilter.value : 'upcoming';
    const now = new Date();

    const items = state.timeline.filter(item => {
        if (!item.scheduled_at) return false;
        const itemDate = new Date(item.scheduled_at);
        switch (filter) {
            case 'upcoming':
                return itemDate >= now && itemDate <= addDays(now, 30);
            case 'past':
                return itemDate < now;
            case 'all':
            default:
                return true;
        }
    });

    if (!items.length) {
        dom.timelineContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <p>Nessuna attività programmata.</p>
            </div>`;
        return;
    }

    items.forEach(item => {
        const entry = document.createElement('div');
        entry.className = `timeline-entry timeline-${item.type}`;
        const hasChecklistAnswers = item.type === 'check' && item.has_answers;
        let detailsText = item.details || '';
        if (item.type === 'check' && item.answers_preview) {
            const preview = sanitizeHtml(item.answers_preview);
            detailsText = hasChecklistAnswers ? `📝 ${preview}` + (detailsText ? ` – ${sanitizeHtml(detailsText)}` : '') : sanitizeHtml(detailsText);
        } else {
            detailsText = sanitizeHtml(detailsText);
        }
        entry.innerHTML = `
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <h6>${sanitizeHtml(item.title)}</h6>
                <span class="timeline-date">${formatDateTime(item.scheduled_at)}</span>
                <p>${detailsText}</p>
            </div>`;
        dom.timelineContainer.appendChild(entry);
    });
}

export function populateSelects({ dom, state }) {
    const patientOptions = state.patients.map(patient => `<option value="${patient.id}">${sanitizeHtml(patient.full_name || 'Paziente')}</option>`).join('');
    if (dom.therapyPatientSelect) {
        dom.therapyPatientSelect.innerHTML = '<option value="">-- Seleziona paziente --</option>' + patientOptions;
        dom.therapyPatientSelect.value = state.selectedPatientId || '';
    }

    const therapyOptions = state.therapies.map(therapy => {
        const label = (therapy.patient_name ? therapy.patient_name + ' - ' : '') + (therapy.title || therapy.description || 'Terapia');
        return `<option value="${therapy.id}">${sanitizeHtml(label)}</option>`;
    }).join('');
    if (dom.checkTherapySelect) {
        dom.checkTherapySelect.innerHTML = '<option value="">-- Seleziona terapia --</option>' + therapyOptions;
    }
    if (dom.reminderTherapySelect) {
        dom.reminderTherapySelect.innerHTML = '<option value="">-- Seleziona terapia --</option>' + therapyOptions;
    }
    if (dom.reportTherapySelect) {
        dom.reportTherapySelect.innerHTML = '<option value="">-- Seleziona terapia --</option>' + therapyOptions;
    }
}

export function renderChecklistQuestions(listElement, questions = []) {
    if (!listElement) return;
    listElement.innerHTML = '';
    const items = questions.length ? questions : defaultChecklistQuestions;
    items.forEach((question, index) => addChecklistQuestionRow(listElement, question, index));
}

export function addChecklistQuestionRow(listElement, question = {}, index = 0) {
    if (!listElement) return;
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2 checklist-question-row';
    const key = question.key || slugifyQuestion(question.text || '', index);
    row.dataset.key = key;
    row.innerHTML = `
        <div class="col-md-10">
            <input type="text" class="form-control" value="${question.text || ''}" placeholder="Testo domanda" />
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    row.querySelector('button').addEventListener('click', () => row.remove());
    listElement.appendChild(row);
}

export function renderChecklistAnswers(listElement, questions = []) {
    if (!listElement) return;
    const currentQuestions = questions && questions.length ? questions : defaultChecklistQuestions;
    listElement.innerHTML = '';
    if (!currentQuestions.length) {
        listElement.innerHTML = '<p class="text-muted">Nessuna checklist configurata. Passa alla modalità "Configura checklist" per aggiungere domande.</p>';
        return;
    }
    currentQuestions.forEach((question, index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3';
        const inputName = `check_answer_${question.key || slugifyQuestion(question.text || '', index)}`;
        wrapper.dataset.questionKey = question.key || slugifyQuestion(question.text || '', index);
        const value = (question.answer || {}).value || null;
        const note = (question.answer || {}).note || '';
        wrapper.innerHTML = `
            <label class="form-label mb-1">${sanitizeHtml(question.text || 'Domanda')}</label>
            <div class="d-flex flex-wrap gap-3 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="${inputName}" value="yes" ${value === 'yes' ? 'checked' : ''}>
                    <label class="form-check-label">Sì</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="${inputName}" value="no" ${value === 'no' ? 'checked' : ''}>
                    <label class="form-check-label">No</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="${inputName}" value="" ${value === null ? 'checked' : ''}>
                    <label class="form-check-label">Non compilato</label>
                </div>
            </div>
            <textarea class="form-control" name="${inputName}_note" rows="2" placeholder="Note opzionali">${note}</textarea>
        `;
        listElement.appendChild(wrapper);
    });
}
