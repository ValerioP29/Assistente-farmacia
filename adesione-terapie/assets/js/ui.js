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

export function renderPatients({ dom, state, onSelectPatient, onEditPatient, onDeletePatient }) {
    if (!dom.patientsList) return;
    dom.patientsList.innerHTML = '';

    const patients = Array.isArray(state.patients) ? state.patients : [];

    if (!patients.length) {
        dom.patientsList.innerHTML = `
            <div class="empty-state text-center py-4">
                <i class="fas fa-user-friends mb-2"></i>
                <p class="mb-0">Nessun paziente registrato.</p>
            </div>`;
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'table-responsive';

    const table = document.createElement('table');
    table.className = 'table table-sm table-hover mb-0 patients-table';

    table.innerHTML = `
        <thead>
            <tr>
                <th>Nome paziente</th>
                <th>Contatti</th>
                <th class="text-center">Terapie</th>
                <th class="text-end">Azioni</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;

    const tbody = table.querySelector('tbody');

    patients.forEach(patient => {
        const tr = document.createElement('tr');
        tr.dataset.patientId = patient.id;
        if (patient.id === state.selectedPatientId) {
            tr.classList.add('table-active');
        }

        const therapiesCount = countTherapiesForPatient(state.therapies, patient.id);

        tr.innerHTML = `
            <td class="patient-name align-middle">
                ${sanitizeHtml(patient.full_name || 'Paziente senza nome')}
            </td>
            <td class="patient-contact align-middle">
                ${patient.phone ? `<div class="small">${sanitizeHtml(patient.phone)}</div>` : ''}
                ${patient.email ? `<div class="small text-muted">${sanitizeHtml(patient.email)}</div>` : ''}
            </td>
            <td class="text-center align-middle">
                <span class="badge bg-primary">${therapiesCount}</span>
            </td>
            <td class="text-end align-middle">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary js-edit-patient">
                        <i class="fas fa-pen me-1"></i>Modifica
                    </button>
                    <button type="button" class="btn btn-outline-danger js-delete-patient">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;

        // click riga → seleziona paziente
        tr.addEventListener('click', event => {
            if (event.target.closest('button')) return;
            onSelectPatient?.(patient.id);
        });

        const editBtn = tr.querySelector('.js-edit-patient');
        const deleteBtn = tr.querySelector('.js-delete-patient');

        editBtn.addEventListener('click', event => {
            event.stopPropagation();
            onEditPatient?.(patient);
        });

        deleteBtn.addEventListener('click', event => {
            event.stopPropagation();
            onDeletePatient?.(patient);
        });

        tbody.appendChild(tr);
    });

    wrapper.appendChild(table);
    dom.patientsList.appendChild(wrapper);
}


export function renderTimeline({ dom, state, onReminderAction }) {
    if (!dom.timelineContainer) return;
    dom.timelineContainer.innerHTML = '';

    const filter = dom.timelineFilter ? dom.timelineFilter.value : 'upcoming';
    const now = new Date();

    let items = Array.isArray(state.timeline) ? state.timeline : [];

    // filtro per periodo
    items = items.filter(item => {
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

    // filtro per paziente selezionato, se l’item espone patient_id
    if (state.selectedPatientId) {
        items = items.filter(item =>
            item.patient_id ? item.patient_id === state.selectedPatientId : true
        );
    }

    if (!items.length) {
        dom.timelineContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <p>Nessuna attività programmata.</p>
            </div>`;
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'table-responsive';

    const table = document.createElement('table');
    table.className = 'table table-sm mb-0 timeline-table';

    table.innerHTML = `
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Descrizione</th>
                <th>Data / ora</th>
                <th>Dettagli</th>
                <th class="text-end">Azioni</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;

    const tbody = table.querySelector('tbody');

    items.forEach(item => {
        const tr = document.createElement('tr');

        const typeLabel =
            item.type === 'check'
                ? 'Check periodico'
                : (item.type === 'reminder' ? 'Promemoria' : 'Attività');

        let detailsText = item.details || '';
        if (item.type === 'check' && item.answers_preview) {
            const preview = sanitizeHtml(item.answers_preview);
            detailsText = item.has_answers
                ? `📝 ${preview}` + (detailsText ? ` – ${sanitizeHtml(detailsText)}` : '')
                : sanitizeHtml(detailsText);
        } else {
            detailsText = sanitizeHtml(detailsText);
        }

        const actionsCell =
            item.type === 'reminder'
                ? `
                <div class="btn-group btn-group-sm" role="group">
                    <button
                        type="button"
                        class="btn btn-outline-primary js-edit-reminder"
                        data-reminder-id="${item.id || ''}"
                        data-therapy-id="${item.therapy_id || ''}"
                    >
                        <i class="fas fa-pen"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-danger js-delete-reminder"
                        data-reminder-id="${item.id || ''}"
                        data-therapy-id="${item.therapy_id || ''}"
                    >
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `
                : '';

        tr.innerHTML = `
            <td class="align-middle">${sanitizeHtml(typeLabel)}</td>
            <td class="align-middle">${sanitizeHtml(item.title || '')}</td>
            <td class="align-middle">${formatDateTime(item.scheduled_at)}</td>
            <td class="align-middle timeline-details-cell">${detailsText}</td>
            <td class="align-middle text-end">${actionsCell}</td>
        `;

        tr.querySelectorAll('.js-edit-reminder, .js-delete-reminder').forEach(button => {
            button.addEventListener('click', event => {
                event.stopPropagation();
                const reminderId = button.dataset.reminderId || null;
                const therapyId = button.dataset.therapyId || null;
                const action = button.classList.contains('js-edit-reminder')
                    ? 'edit'
                    : 'delete';

                onReminderAction?.(action, {
                    reminderId,
                    therapyId,
                    item,
                });
            });
        });

        tbody.appendChild(tr);
    });

    wrapper.appendChild(table);
    dom.timelineContainer.appendChild(wrapper);
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

export function renderTherapies({ dom, state, onAction }) {
    if (!dom.therapiesList) return;

    if (dom.therapySearchInput) {
        dom.therapySearchInput.value = state.therapySearchTerm || '';
    }

    dom.therapiesList.innerHTML = '';

    const therapies = Array.isArray(state.therapies) ? state.therapies : [];
    const searchTerm = (state.therapySearchTerm || '').trim().toLowerCase();

    let filtered = therapies;

    if (state.selectedPatientId && !searchTerm) {
        filtered = filtered.filter(
            item => item.patient_id ? item.patient_id === state.selectedPatientId : true
        );
    }

    if (searchTerm) {
        filtered = filtered.filter(item =>
            (item.patient_name || '').toLowerCase().includes(searchTerm)
        );
    }

    const summaryLabel = dom.therapiesSummary;
    if (summaryLabel) {
        const patientCount = new Set(filtered.map(item => item.patient_id)).size;
        const therapyCount = filtered.length;
        summaryLabel.textContent = therapyCount
            ? `${therapyCount} terapie · ${patientCount} pazienti`
            : 'Nessuna terapia da mostrare';
    }

    if (!filtered.length) {
        dom.therapiesList.innerHTML = `
            <div class="empty-state text-center py-4">
                <i class="fas fa-capsules mb-2"></i>
                <p class="mb-0">Nessuna terapia trovata.</p>
            </div>`;
        return;
    }

    const grouped = filtered.reduce((acc, therapy) => {
        const patientId = therapy.patient_id || 'unknown';
        if (!acc[patientId]) {
            acc[patientId] = {
                patientId,
                patientName: therapy.patient_name || 'Paziente sconosciuto',
                therapies: [],
            };
        }
        acc[patientId].therapies.push(therapy);
        return acc;
    }, {});

    const groups = Object.values(grouped).sort((a, b) =>
        (a.patientName || '').localeCompare(b.patientName || '')
    );

    groups.forEach(group => {
        const isExpanded = !!state.expandedPatients[group.patientId];
        const visibleTherapies = isExpanded
            ? group.therapies
            : group.therapies.slice(0, 3);
        const hiddenCount = Math.max(group.therapies.length - visibleTherapies.length, 0);

        const card = document.createElement('div');
        card.className = 'patient-therapies-card border rounded p-3 mb-3';

        const header = document.createElement('div');
        header.className = 'd-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2';
        header.innerHTML = `
            <div>
                <h5 class="mb-0">${sanitizeHtml(group.patientName)}</h5>
                <div class="text-muted small">${group.therapies.length} terapie</div>
            </div>
            ${group.therapies.length > 3 ? `
                <button class="btn btn-sm btn-outline-secondary" data-toggle-patient="${group.patientId}">
                    ${isExpanded ? '<i class="fas fa-chevron-up me-1"></i>Mostra meno terapie' : '<i class="fas fa-chevron-down me-1"></i>Mostra altre terapie'}
                </button>
            ` : ''}
        `;

        card.appendChild(header);

        const list = document.createElement('div');
        list.className = 'd-flex flex-column gap-2';

        visibleTherapies.forEach(therapy => {
            const item = document.createElement('div');
            item.className = 'therapy-item border rounded p-3';
            if (therapy.id === state.selectedTherapyId) {
                item.classList.add('border-primary', 'shadow-sm');
            }

            const periodParts = [];
            if (therapy.start_date) periodParts.push(`Inizio: ${formatDate(therapy.start_date)}`);
            if (therapy.end_date) periodParts.push(`Fine: ${formatDate(therapy.end_date)}`);
            const periodText = periodParts.join(' · ');

            const statusBadge = therapy.status
                ? `<span class="badge bg-primary-subtle text-primary">${sanitizeHtml(statusLabel(therapy.status))}</span>`
                : '';

            item.innerHTML = `
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="mb-0">${sanitizeHtml(therapy.title || therapy.description || 'Terapia senza titolo')}</h6>
                            ${statusBadge}
                        </div>
                        ${periodText ? `<div class="text-muted small">${periodText}</div>` : ''}
                        ${therapy.description ? `<div class="small mt-1 text-muted">${sanitizeHtml(therapy.description)}</div>` : ''}
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-action="edit">
                            <i class="fas fa-pen me-1"></i>Modifica terapia
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-action="delete">
                            <i class="fas fa-trash me-1"></i>Elimina terapia
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" data-action="open-check">
                            <i class="fas fa-stethoscope me-1"></i>Aggiungi check periodico
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" data-action="open-reminder">
                            <i class="fas fa-bell me-1"></i>Imposta promemoria
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-action="open-checklist">
                            <i class="fas fa-list-check me-1"></i>Configura checklist
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" data-action="open-report">
                            <i class="fas fa-file-medical me-1"></i>Genera report
                        </button>
                    </div>
                </div>
            `;

            item.querySelectorAll('button[data-action]').forEach(button => {
                button.addEventListener('click', event => {
                    event.stopPropagation();
                    const action = button.dataset.action;
                    onAction?.(action, therapy);
                });
            });

            item.addEventListener('click', event => {
                if (event.target.closest('button[data-action]')) return;
                onAction?.('select', therapy);
            });

            list.appendChild(item);
        });

        if (hiddenCount > 0) {
            const hiddenNotice = document.createElement('div');
            hiddenNotice.className = 'text-muted small';
            hiddenNotice.textContent = `${hiddenCount} terapie nascoste`;
            list.appendChild(hiddenNotice);
        }

        card.appendChild(list);

        const toggleButton = card.querySelector('[data-toggle-patient]');
        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                state.expandedPatients[group.patientId] = !isExpanded;
                renderTherapies({ dom, state, onAction });
            });
        }

        dom.therapiesList.appendChild(card);
    });
}

