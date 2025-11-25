(function () {
    document.addEventListener('DOMContentLoaded', async () => {
        const routesBase = 'adesione-terapie/routes.php';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const moduleRoot = document.querySelector('.adesione-terapie-module');
        if (!moduleRoot) {
            return;
        }

        const { api, state: stateModule, utils } = await import('./index.js');
        const {
            configureApi,
            loadInitialData,
            savePatient,
            saveTherapy,
            saveCheck,
            saveChecklist,
            saveReminder,
            generateReport,
        } = api;
        const { getState } = stateModule;
        const {
            sanitizeHtml,
            formatDate,
            formatDateTime,
            statusLabel,
            addDays,
            caregiverFullName,
        } = utils;

        configureApi({ routesBase, csrfToken });
        const state = getState();

    const defaultChecklistQuestions = [
        { key: 'aderenza', text: 'Il paziente sta seguendo la terapia come prescritto?', type: 'text' },
        { key: 'effetti_collaterali', text: 'Sono presenti effetti collaterali?', type: 'text' },
        { key: 'supporto', text: 'Il paziente necessita di supporto aggiuntivo?', type: 'text' },
        { key: 'aderenza_bool', text: 'Aderenza confermata', type: 'boolean' },
        { key: 'note_generali', text: 'Note generali', type: 'text' },
    ];

    const dom = {
        patientsList: document.getElementById('patientsList'),
        therapiesContainer: document.getElementById('therapiesContainer'),
        patientSummary: document.getElementById('patientSummary'),
        timelineContainer: document.getElementById('timelineContainer'),
        statElements: document.querySelectorAll('.stat-value'),
        patientForm: document.getElementById('patientForm'),
        therapyForm: document.getElementById('therapyForm'),
        checkForm: document.getElementById('checkForm'),
        reminderForm: document.getElementById('reminderForm'),
        reportForm: document.getElementById('reportForm'),
        therapyModal: document.getElementById('therapyModal'),
        patientModal: document.getElementById('patientModal'),
        checkModal: document.getElementById('checkModal'),
        reminderModal: document.getElementById('reminderModal'),
        reportModal: document.getElementById('reportModal'),
        therapyWizardIndicator: document.getElementById('therapyWizardIndicator'),
        therapyWizardSteps: document.querySelectorAll('#therapyWizardSteps span'),
        wizardSteps: document.querySelectorAll('.wizard-step'),
        nextStepButton: document.getElementById('nextStepButton'),
        prevStepButton: document.getElementById('prevStepButton'),
        submitTherapyButton: document.getElementById('submitTherapyButton'),
        therapyPatientSelect: document.getElementById('therapyPatientSelect'),
        caregiversContainer: document.getElementById('caregiversContainer'),
        caregiversTemplate: document.querySelector('.caregiver-row.template'),
        addCaregiverButton: document.getElementById('addCaregiverButton'),
        timelineFilter: document.getElementById('timelineFilter'),
        signatureCanvas: document.getElementById('consentSignaturePad'),
        saveSignatureButton: document.getElementById('saveSignatureButton'),
        clearSignatureButton: document.getElementById('clearSignatureButton'),
        signatureTypeSelect: document.getElementById('signatureType'),
        signatureCanvasWrapper: document.getElementById('signatureCanvasWrapper'),
        digitalSignatureWrapper: document.getElementById('digitalSignatureWrapper'),
        therapySummary: document.getElementById('therapySummary'),
        generatedReportContainer: document.getElementById('generatedReportContainer'),
        generatedReportLink: document.getElementById('generatedReportLink'),
        generatedReportInfo: document.getElementById('generatedReportInfo'),
        copyReportLink: document.getElementById('copyReportLink'),
        reportTherapySelect: document.getElementById('reportTherapySelect'),
        checkTherapySelect: document.getElementById('checkTherapySelect'),
        reminderTherapySelect: document.getElementById('reminderTherapySelect'),
        exportReportButton: document.getElementById('exportReportButton'),
        newPatientButton: document.getElementById('newPatientButton'),
        newTherapyButton: document.getElementById('newTherapyButton'),
        newCheckButton: document.getElementById('newCheckButton'),
        newReminderButton: document.getElementById('newReminderButton'),
        refreshDataButton: document.getElementById('refreshDataButton'),
        inlinePatientToggle: document.getElementById('openInlinePatientForm'),
        inlinePatientForm: document.getElementById('inlinePatientForm'),
        caregiversPayloadInput: document.getElementById('caregiversPayloadInput'),
        questionnairePayloadInput: document.getElementById('questionnairePayloadInput'),
        signatureImageInput: document.getElementById('signatureImageInput'),
    };

    let eventsBound = false;
    let checkModeSelect = null;
    let checklistQuestionsContainer = null;
    let checklistQuestionsList = null;
    let checkAnswersContainer = null;
    let checkAnswersList = null;
    let checkQuestionsPayloadInput = null;
    let checkAnswersPayloadInput = null;
    let currentChecklistQuestions = [];

    function loadData() {
        toggleLoading(true);
        loadInitialData().then(data => {
            if (!data) {
                throw new Error('Dati iniziali non disponibili. Riprova più tardi.');
            }

            state.patients = data.patients || [];
            state.therapies = data.therapies || [];
            state.checks = data.checks || [];
            state.reminders = data.reminders || [];
            state.reports = data.reports || [];
            state.timeline = data.timeline || [];
            state.stats = data.stats || {};

            if (!state.selectedPatientId && state.patients.length > 0) {
                state.selectedPatientId = state.patients[0].id;
            }

            renderAll();
        }).catch(handleError).finally(() => toggleLoading(false));
    }

    function renderAll() {
        renderStats();
        renderPatients();
        renderTherapies();
        renderTimeline();
        populateSelects();
    }

    function renderStats() {
        dom.statElements.forEach(element => {
            const key = element.dataset.stat;
            element.textContent = state.stats[key] ?? 0;
        });
    }

    function renderPatients() {
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
                    <span class="badge bg-primary rounded-pill">${countTherapiesForPatient(patient.id)}</span>
                </div>`;
            item.addEventListener('click', () => {
                state.selectedPatientId = patient.id;
                renderAll();
            });
            list.appendChild(item);
        });

        dom.patientsList.appendChild(list);
    }

    function countTherapiesForPatient(patientId) {
        return state.therapies.filter(therapy => therapy.patient_id === patientId).length;
    }

    function getChecklistQuestionsForTherapy(therapyId) {
        const checklist = getChecklistForTherapy(therapyId);
        return (checklist && Array.isArray(checklist.questions)) ? checklist.questions : [];
    }

    function getCheckExecutions(therapyId) {
        return state.checks.filter(check => check.therapy_id === therapyId && check.type !== 'checklist');
    }

    function getLatestAnswersByQuestion(therapyId) {
        const executions = getCheckExecutions(therapyId);
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

    function buildChecklistSummaryHtml(therapyId) {
        const questions = getChecklistQuestionsForTherapy(therapyId);
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

        const latestAnswers = getLatestAnswersByQuestion(therapyId);
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
            const answerSafe = latest && latest.answer ? sanitizeHtml(latest.answer) : '<span class="text-muted">Nessuna risposta</span>';
            const answeredAt = latest && latest.created_at ? formatDateTime(latest.created_at) : '';
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold">${sanitizeHtml(question.text || question.key)}</div>
                        <div class="text-muted small">${sanitizeHtml(question.key)}</div>
                    </div>
                    <div class="text-end">
                        <div>${answerSafe}</div>
                        ${answeredAt ? `<small class="text-muted">${answeredAt}</small>` : ''}
                    </div>
                </li>
            `;
        });

        html += '</ul></div>';
        return html;
    }

    function renderAnswersList(answers = []) {
        if (!answers.length) {
            return '<p class="text-muted mb-1">Nessuna risposta registrata.</p>';
        }
        let html = '<ul class="list-group list-group-flush mb-2">';
        answers.forEach(answer => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="fw-semibold">${sanitizeHtml(answer.question || 'Domanda')}</div>
                    <div class="text-end">
                        <div>${sanitizeHtml(answer.answer || '')}</div>
                        ${answer.created_at ? `<small class="text-muted">${formatDateTime(answer.created_at)}</small>` : ''}
                    </div>
                </li>
            `;
        });
        html += '</ul>';
        return html;
    }

    function buildCheckHistoryHtml(therapyId) {
        const executions = getCheckExecutions(therapyId);
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

    function renderTherapies() {
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
        if (patient) {
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

            const checklistSummary = buildChecklistSummaryHtml(therapy.id);
            const answersHistory = buildCheckHistoryHtml(therapy.id);
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
                    switch (button.dataset.action) {
                        case 'open-check':
                            openCheckModal(therapyId);
                            break;
                        case 'open-checklist':
                            openCheckModal(therapyId, 'checklist');
                            break;
                        case 'open-reminder':
                            openReminderModal(therapyId);
                            break;
                        case 'open-report':
                            openReportModal(therapyId);
                            break;
                    }
                });
            });

            dom.therapiesContainer.appendChild(card);
        });
    }

    function renderTimeline() {
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

    function populateSelects() {
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

    function renderQuestionnaireSummary(questionnaire) {
        if (!questionnaire || typeof questionnaire !== 'object') {
            return '<small class="text-muted">Questionario non disponibile</small>';
        }
        let html = '<ul class="list-unstyled mb-0 questionnaire-summary">';
        Object.keys(questionnaire).forEach(stepKey => {
            const step = questionnaire[stepKey];
            if (!step) return;
            const answered = Object.values(step).filter(Boolean).length;
            html += `<li><i class="fas fa-circle text-success me-2"></i>Step ${stepKey}: ${answered} risposte</li>`;
        });
        html += '</ul>';
        return html;
    }

    function toggleLoading(isLoading) {
        moduleRoot.classList.toggle('is-loading', isLoading);
    }

    function handleError(error) {
        if (typeof showAlert === 'function') {
            showAlert(error.message || 'Errore inatteso', 'danger');
        } else {
            alert(error.message || 'Errore inatteso');
        }
        console.error(error);
    }

    function openModal(element) {
        if (!element) return;
        const modal = bootstrap.Modal.getOrCreateInstance(element);
        modal.show();
    }

    function closeModal(element) {
        if (!element) return;
        const modal = bootstrap.Modal.getOrCreateInstance(element);
        modal.hide();
    }

    function resetForm(form) {
        if (!form) return;
        form.reset();
        form.querySelectorAll('input[type="hidden"]').forEach(input => input.value = '');
    }

    function setupEventListeners() {
        if (eventsBound) return;
        eventsBound = true;
        if (dom.newPatientButton) {
            dom.newPatientButton.addEventListener('click', () => {
                resetForm(dom.patientForm);
                openModal(dom.patientModal);
            });
        }
        if (dom.newTherapyButton) {
            dom.newTherapyButton.addEventListener('click', () => {
                prepareTherapyForm();
                openModal(dom.therapyModal);
            });
        }
        if (dom.newCheckButton) {
            dom.newCheckButton.addEventListener('click', () => {
                openCheckModal();
            });
        }
        if (dom.newReminderButton) {
            dom.newReminderButton.addEventListener('click', () => {
                openReminderModal();
            });
        }
        if (dom.exportReportButton) {
            dom.exportReportButton.addEventListener('click', () => {
                openReportModal();
            });
        }
        if (dom.refreshDataButton) {
            dom.refreshDataButton.addEventListener('click', loadData);
        }
        if (dom.timelineFilter) {
            dom.timelineFilter.addEventListener('change', renderTimeline);
        }
        if (dom.inlinePatientToggle && dom.inlinePatientForm) {
            dom.inlinePatientToggle.addEventListener('click', () => {
                toggleInlinePatientForm(true);
            });
        }
        if (dom.therapyPatientSelect) {
            dom.therapyPatientSelect.addEventListener('change', () => {
                const patientIdField = document.getElementById('therapyPatientId');
                if (patientIdField) {
                    patientIdField.value = dom.therapyPatientSelect.value;
                }
                if (dom.inlinePatientForm) {
                    toggleInlinePatientForm(false);
                }
            });
        }
        if (dom.addCaregiverButton) {
            dom.addCaregiverButton.addEventListener('click', addCaregiverRow);
        }
        if (dom.nextStepButton) {
            dom.nextStepButton.addEventListener('click', () => changeTherapyStep(1));
        }
        if (dom.prevStepButton) {
            dom.prevStepButton.addEventListener('click', () => changeTherapyStep(-1));
        }
        if (dom.signatureTypeSelect) {
            dom.signatureTypeSelect.addEventListener('change', updateSignatureMode);
        }
        if (dom.saveSignatureButton) {
            dom.saveSignatureButton.addEventListener('click', saveSignature);
        }
        if (dom.clearSignatureButton) {
            dom.clearSignatureButton.addEventListener('click', clearSignature);
        }
        if (dom.copyReportLink) {
            dom.copyReportLink.addEventListener('click', () => {
                navigator.clipboard.writeText(dom.generatedReportLink.value)
                    .then(() => showAlert('Link copiato negli appunti', 'success'))
                    .catch(() => showAlert('Impossibile copiare il link', 'danger'));
            });
        }

        setupForms();
    }

    function ensureCheckFormEnhancements() {
        if (!dom.checkForm) return;

        if (!checkQuestionsPayloadInput) {
            checkQuestionsPayloadInput = document.createElement('input');
            checkQuestionsPayloadInput.type = 'hidden';
            checkQuestionsPayloadInput.name = 'questions_payload';
            dom.checkForm.appendChild(checkQuestionsPayloadInput);
        }
        if (!checkAnswersPayloadInput) {
            checkAnswersPayloadInput = document.createElement('input');
            checkAnswersPayloadInput.type = 'hidden';
            checkAnswersPayloadInput.name = 'answers_payload';
            dom.checkForm.appendChild(checkAnswersPayloadInput);
        }

        const formBody = dom.checkForm.querySelector('.modal-body');
        const fieldRow = dom.checkForm.querySelector('.modal-body .row');

        if (!checkModeSelect) {
            const modeWrapper = document.createElement('div');
            modeWrapper.className = 'mb-3';
            modeWrapper.innerHTML = `
                <label class="form-label">Modalità</label>
                <select class="form-select" id="checkModeSelect">
                    <option value="execution">Registra check</option>
                    <option value="checklist">Configura checklist</option>
                </select>`;
            formBody.insertBefore(modeWrapper, fieldRow);
            checkModeSelect = modeWrapper.querySelector('#checkModeSelect');
            checkModeSelect.addEventListener('change', () => toggleCheckMode(checkModeSelect.value));
        }

        if (!checklistQuestionsContainer) {
            checklistQuestionsContainer = document.createElement('div');
            checklistQuestionsContainer.className = 'mt-3 d-none';
            checklistQuestionsContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Domande checklist</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addChecklistQuestionBtn">
                        <i class="fas fa-plus me-1"></i>Aggiungi domanda
                    </button>
                </div>
                <div class="checklist-questions-list"></div>
            `;
            formBody.appendChild(checklistQuestionsContainer);
            checklistQuestionsList = checklistQuestionsContainer.querySelector('.checklist-questions-list');
            const addButton = checklistQuestionsContainer.querySelector('#addChecklistQuestionBtn');
            addButton.addEventListener('click', () => addChecklistQuestionRow());
        }

        if (!checkAnswersContainer) {
            checkAnswersContainer = document.createElement('div');
            checkAnswersContainer.className = 'mt-3';
            checkAnswersContainer.innerHTML = `
                <h6 class="mb-2">Risposte checklist</h6>
                <div class="check-answers-list"></div>
            `;
            formBody.appendChild(checkAnswersContainer);
            checkAnswersList = checkAnswersContainer.querySelector('.check-answers-list');
        }

        toggleCheckMode(checkModeSelect ? checkModeSelect.value : 'execution');
    }

    function toggleCheckMode(mode = 'execution') {
        if (!dom.checkForm) return;
        const assessmentField = dom.checkForm.querySelector('[name="assessment"]');
        const notesField = dom.checkForm.querySelector('[name="notes"]');
        const actionsField = dom.checkForm.querySelector('[name="actions"]');
        const submitButton = dom.checkForm.querySelector('[type="submit"]');

        const isChecklist = mode === 'checklist';
        if (assessmentField) {
            if (isChecklist) {
                assessmentField.removeAttribute('required');
            } else {
                assessmentField.setAttribute('required', 'required');
            }
        }
        [notesField, actionsField].forEach(field => {
            if (!field) return;
            if (isChecklist) {
                field.removeAttribute('required');
            }
        });

        if (checklistQuestionsContainer) {
            checklistQuestionsContainer.classList.toggle('d-none', !isChecklist);
        }
        if (checkAnswersContainer) {
            checkAnswersContainer.classList.toggle('d-none', isChecklist);
        }
        if (submitButton) {
            submitButton.innerHTML = isChecklist ? '<i class="fas fa-save me-2"></i>Salva checklist' : '<i class="fas fa-save me-2"></i>Salva check';
        }

        if (isChecklist) {
            const therapyId = parseInt(dom.checkTherapySelect?.value || dom.checkTherapyId?.value || '0', 10);
            const checklist = getChecklistForTherapy(therapyId);
            renderChecklistQuestions(checklist?.questions || currentChecklistQuestions || defaultChecklistQuestions);
        } else if (checkAnswersList) {
            const latestQuestions = collectChecklistQuestions();
            if (latestQuestions.length) {
                renderChecklistAnswers(latestQuestions);
            } else {
                renderChecklistAnswers(currentChecklistQuestions);
            }
        }
    }

    function slugifyQuestion(text, index = 0) {
        const cleaned = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        if (cleaned) return cleaned;
        return `q${index + 1}`;
    }

    function getChecklistForTherapy(therapyId) {
        if (!therapyId) return null;
        return state.checks.find(check => check.therapy_id === therapyId && check.type === 'checklist') || null;
    }

    function renderChecklistQuestions(questions = []) {
        if (!checklistQuestionsList) return;
        checklistQuestionsList.innerHTML = '';
        const items = questions.length ? questions : defaultChecklistQuestions;
        items.forEach((question, index) => addChecklistQuestionRow(question, index));
    }

    function addChecklistQuestionRow(question = {}, index = 0) {
        if (!checklistQuestionsList) return;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center mb-2 checklist-question-row';
        const key = question.key || slugifyQuestion(question.text || '', index);
        row.dataset.key = key;
        row.innerHTML = `
            <div class="col-md-7">
                <input type="text" class="form-control" value="${question.text || ''}" placeholder="Testo domanda" />
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option value="text" ${question.type === 'text' ? 'selected' : ''}>Risposta testuale</option>
                    <option value="boolean" ${question.type === 'boolean' ? 'selected' : ''}>Sì/No</option>
                    <option value="number" ${question.type === 'number' ? 'selected' : ''}>Numero</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        row.querySelector('button').addEventListener('click', () => row.remove());
        checklistQuestionsList.appendChild(row);
    }

    function renderChecklistAnswers(questions = []) {
        if (!checkAnswersList) return;
        currentChecklistQuestions = questions && questions.length ? questions : defaultChecklistQuestions;
        checkAnswersList.innerHTML = '';
        if (!currentChecklistQuestions.length) {
            checkAnswersList.innerHTML = '<p class="text-muted">Nessuna checklist configurata. Passa alla modalità "Configura checklist" per aggiungere domande.</p>';
            return;
        }
        currentChecklistQuestions.forEach((question, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'mb-2';
            const inputName = `check_answer_${question.key || slugifyQuestion(question.text || '', index)}`;
            wrapper.dataset.questionKey = question.key || slugifyQuestion(question.text || '', index);
            let inputField = '';
            if (question.type === 'boolean') {
                inputField = `
                    <select class="form-select" name="${inputName}">
                        <option value="">--</option>
                        <option value="si">Sì</option>
                        <option value="no">No</option>
                    </select>`;
            } else if (question.type === 'number') {
                inputField = `<input type="number" class="form-control" name="${inputName}" step="any">`;
            } else {
                inputField = `<textarea class="form-control" name="${inputName}" rows="2"></textarea>`;
            }
            wrapper.innerHTML = `
                <label class="form-label mb-1">${sanitizeHtml(question.text || 'Domanda')}</label>
                ${inputField}
            `;
            checkAnswersList.appendChild(wrapper);
        });
    }

    function collectChecklistQuestions() {
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

    function prepareChecklistPayload() {
        const questions = collectChecklistQuestions();
        checkQuestionsPayloadInput.value = JSON.stringify(questions);
        return questions;
    }

    function prepareCheckExecutionPayload() {
        const answers = {};
        const assessmentField = dom.checkForm.querySelector('[name="assessment"]');
        const notesField = dom.checkForm.querySelector('[name="notes"]');
        const actionsField = dom.checkForm.querySelector('[name="actions"]');
        if (assessmentField) answers.assessment = assessmentField.value.trim();
        if (notesField) answers.notes = notesField.value.trim();
        if (actionsField) answers.actions = actionsField.value.trim();

        if (checkAnswersList) {
            checkAnswersList.querySelectorAll('[data-question-key]').forEach(node => {
                const key = node.dataset.questionKey;
                const input = node.querySelector('input, textarea, select');
                if (!key || !input) return;
                answers[key] = input.value;
            });
        }

        checkAnswersPayloadInput.value = JSON.stringify(answers);
        return answers;
    }

    function refreshChecklistUI() {
        const therapyId = parseInt(dom.checkTherapySelect?.value || dom.checkTherapyId?.value || '0', 10);
        const checklist = getChecklistForTherapy(therapyId);
        if (checklist && Array.isArray(checklist.questions)) {
            renderChecklistQuestions(checklist.questions);
            renderChecklistAnswers(checklist.questions);
        } else {
            renderChecklistQuestions(defaultChecklistQuestions);
            renderChecklistAnswers(defaultChecklistQuestions);
        }
    }

    function setupForms() {
        if (dom.patientForm) {
            dom.patientForm.addEventListener('submit', event => {
                event.preventDefault();
                if (state.patientSubmitting) return;
                state.patientSubmitting = true;
                const submitButton = dom.patientForm.querySelector('[type="submit"]');
                submitButton?.setAttribute('disabled', 'disabled');
                const formData = new FormData(dom.patientForm);
                savePatient(formData).then(response => {
                    closeModal(dom.patientModal);
                    showAlert('Paziente salvato con successo', 'success');
                    const patientIndex = state.patients.findIndex(p => p.id === response.patient.id);
                    if (patientIndex >= 0) {
                        state.patients[patientIndex] = response.patient;
                    } else {
                        state.patients.push(response.patient);
                    }
                    state.selectedPatientId = response.patient.id;
                    renderAll();
                }).catch(handleError).finally(() => {
                    state.patientSubmitting = false;
                    submitButton?.removeAttribute('disabled');
                });
            });
        }

        if (dom.therapyForm) {
            dom.therapyForm.addEventListener('submit', event => {
                event.preventDefault();
                if (state.therapySubmitting) return;
                state.therapySubmitting = true;
                dom.submitTherapyButton?.setAttribute('disabled', 'disabled');
                prepareCaregiversPayload();
                prepareQuestionnairePayload();
                captureSignatureImage();
                appendConsentFields();

                const formData = new FormData(dom.therapyForm);
                saveTherapy(formData).then(response => {
                    closeModal(dom.therapyModal);
                    showAlert('Terapia salvata con successo', 'success');
                    upsertTherapy(response.therapy);
                    renderAll();
                }).catch(handleError).finally(() => {
                    state.therapySubmitting = false;
                    dom.submitTherapyButton?.removeAttribute('disabled');
                });
            });
        }

        if (dom.checkForm) {
            ensureCheckFormEnhancements();
            if (dom.checkTherapySelect) {
                dom.checkTherapySelect.addEventListener('change', refreshChecklistUI);
            }

            dom.checkForm.addEventListener('submit', event => {
                event.preventDefault();
                if (state.checkSubmitting) return;
                state.checkSubmitting = true;
                const submitButton = dom.checkForm.querySelector('[type="submit"]');
                submitButton?.setAttribute('disabled', 'disabled');
                const therapyHidden = dom.checkForm.querySelector('#checkTherapyId');
                if (therapyHidden && dom.checkTherapySelect) {
                    therapyHidden.value = dom.checkTherapySelect.value;
                }

                const formData = new FormData(dom.checkForm);
                if (!formData.get('scheduled_at')) {
                    formData.set('scheduled_at', new Date().toISOString().slice(0, 16));
                }
                const mode = checkModeSelect ? checkModeSelect.value : 'execution';
                if (mode === 'checklist') {
                    const questions = prepareChecklistPayload();
                    if (!questions.length) {
                        showAlert('Aggiungi almeno una domanda alla checklist.', 'warning');
                        state.checkSubmitting = false;
                        submitButton?.removeAttribute('disabled');
                        return;
                    }
                    if (checkAnswersPayloadInput) {
                        checkAnswersPayloadInput.value = '';
                    }
                    saveChecklist(formData).then(response => {
                        closeModal(dom.checkModal);
                        showAlert('Checklist salvata', 'success');
                        const index = state.checks.findIndex(item => item.id === response.check.id);
                        if (index >= 0) {
                            state.checks[index] = response.check;
                        } else {
                            state.checks.push(response.check);
                        }
                        loadData();
                    }).catch(handleError).finally(() => {
                        state.checkSubmitting = false;
                        submitButton?.removeAttribute('disabled');
                    });
                    return;
                } else {
                    prepareCheckExecutionPayload();
                    saveCheck(formData).then(response => {
                        closeModal(dom.checkModal);
                        showAlert('Check periodico salvato', 'success');
                        const index = state.checks.findIndex(item => item.id === response.check.id);
                        if (index >= 0) {
                            state.checks[index] = response.check;
                        } else {
                            state.checks.push(response.check);
                        }
                        loadData();
                    }).catch(handleError).finally(() => {
                        state.checkSubmitting = false;
                        submitButton?.removeAttribute('disabled');
                    });
                    return;
                }

            });
        }

        if (dom.reminderForm) {
            dom.reminderForm.addEventListener('submit', event => {
                event.preventDefault();
                if (state.reminderSubmitting) return;
                state.reminderSubmitting = true;
                const submitButton = dom.reminderForm.querySelector('[type="submit"]');
                submitButton?.setAttribute('disabled', 'disabled');
                const formData = new FormData(dom.reminderForm);
                saveReminder(formData).then(response => {
                    closeModal(dom.reminderModal);
                    showAlert('Promemoria salvato', 'success');
                    const index = state.reminders.findIndex(item => item.id === response.reminder.id);
                    if (index >= 0) {
                        state.reminders[index] = response.reminder;
                    } else {
                        state.reminders.push(response.reminder);
                    }
                    loadData();
                }).catch(handleError).finally(() => {
                    state.reminderSubmitting = false;
                    submitButton?.removeAttribute('disabled');
                });
            });
        }

        if (dom.reportForm) {
            dom.reportForm.addEventListener('submit', event => {
                event.preventDefault();
                if (state.reportGenerating) return;
                state.reportGenerating = true;
                const submitButton = dom.reportForm.querySelector('[type="submit"]');
                submitButton?.setAttribute('disabled', 'disabled');
                const formData = new FormData(dom.reportForm);
                generateReport(formData).then(response => {
                    dom.generatedReportContainer.classList.remove('d-none');
                    dom.generatedReportLink.value = response.report.url;
                    dom.generatedReportInfo.textContent = response.report.valid_until ? `Valido fino al ${formatDate(response.report.valid_until)}` : 'Validità illimitata';
                    showAlert('Report generato con successo', 'success');
                    loadData();
                }).catch(handleError).finally(() => {
                    state.reportGenerating = false;
                    submitButton?.removeAttribute('disabled');
                });
            });
        }
    }

    function prepareTherapyForm() {
        resetForm(dom.therapyForm);
        state.currentTherapyStep = 1;
        updateWizardUI();
        dom.signatureImageInput.value = '';
        dom.questionnairePayloadInput.value = '';
        dom.caregiversPayloadInput.value = '';
        toggleInlinePatientForm(false);
        clearCaregivers();
        addCaregiverRow();
        initializeSignaturePad();
        updateSignatureMode();
        state.signaturePadDirty = false;
        if (state.selectedPatientId) {
            dom.therapyPatientSelect.value = String(state.selectedPatientId);
            const patientIdField = document.getElementById('therapyPatientId');
            if (patientIdField) {
                patientIdField.value = String(state.selectedPatientId);
            }
        }
    }

    function appendConsentFields() {
        if (!dom.therapyForm) return;
        const signerInput = dom.therapyForm.querySelector('[name="digital_signature"]');
        const consentNotesInput = dom.therapyForm.querySelector('[name="consent_notes"]');
        const ipMeta = document.querySelector('meta[name="client-ip"]');

        const signerName = (signerInput?.value.trim()) || getSelectedPatientName() || 'Paziente';
        const consentText = (consentNotesInput?.value.trim()) || 'Consenso informato registrato';
        const signatureImage = dom.signatureImageInput?.value || '';
        const signedAt = new Date().toISOString().replace('T', ' ').slice(0, 19);
        const ipAddress = (ipMeta?.content || window.CLIENT_IP || '').toString();

        setHiddenField('signer_name', signerName);
        setHiddenField('signer_relation', 'patient');
        setHiddenField('consent_text', consentText);
        setHiddenField('signature_image', signatureImage);
        setHiddenField('signed_at', signedAt);
        setHiddenField('ip_address', ipAddress);
    }

    function setHiddenField(name, value) {
        if (!dom.therapyForm) return;
        let input = dom.therapyForm.querySelector(`[name="${name}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            dom.therapyForm.appendChild(input);
        }
        input.value = value;
    }

    function getSelectedPatientName() {
        if (!state.selectedPatientId) return '';
        const patient = state.patients.find(item => item.id === state.selectedPatientId);
        if (!patient) return '';
        if (patient.full_name) return patient.full_name;
        return `${patient.first_name || ''} ${patient.last_name || ''}`.trim();
    }

    function upsertTherapy(therapy) {
        const index = state.therapies.findIndex(item => item.id === therapy.id);
        if (index >= 0) {
            state.therapies[index] = therapy;
        } else {
            state.therapies.push(therapy);
        }
    }

    function addCaregiverRow() {
        if (!dom.caregiversContainer || !dom.caregiversTemplate) return;
        const clone = dom.caregiversTemplate.cloneNode(true);
        clone.classList.remove('template', 'd-none');
        clone.querySelectorAll('.remove-caregiver').forEach(button => {
            button.addEventListener('click', () => {
                clone.remove();
            });
        });
        const roleField = clone.querySelector('[data-field="role"]');
        if (roleField && !roleField.value) {
            roleField.value = 'caregiver';
        }
        dom.caregiversContainer.appendChild(clone);
    }

    function clearCaregivers() {
        if (!dom.caregiversContainer) return;
        dom.caregiversContainer.querySelectorAll('.caregiver-row:not(.template)').forEach(row => row.remove());
    }

    function prepareCaregiversPayload() {
        if (!dom.caregiversContainer || !dom.caregiversPayloadInput) return;
        const caregivers = [];
        dom.caregiversContainer.querySelectorAll('.caregiver-row:not(.template)').forEach(row => {
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
        dom.caregiversPayloadInput.value = JSON.stringify(caregivers);
    }

   function prepareQuestionnairePayload() {
    if (!dom.questionnairePayloadInput || !dom.therapyForm) return;
    
    // Seleziona gli input AL MOMENTO DELL'USO, non al caricamento pagina
    const questionnaireInputs = dom.therapyForm.querySelectorAll('.questionnaire-input');
    
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
    dom.questionnairePayloadInput.value = JSON.stringify(questionnaire);
}

    function changeTherapyStep(direction) {
        const nextStep = state.currentTherapyStep + direction;
        const minStep = 1;
        const maxStep = dom.wizardSteps.length;
        if (nextStep < minStep || nextStep > maxStep) {
            return;
        }

        if (direction > 0 && !validateCurrentStep()) {
            return;
        }

        state.currentTherapyStep = nextStep;
        updateWizardUI();
    }

    function validateCurrentStep() {
        const stepElement = Array.from(dom.wizardSteps).find(step => parseInt(step.dataset.step, 10) === state.currentTherapyStep);
        if (!stepElement) return true;

        if (state.currentTherapyStep === 1) {
            const hasExistingPatient = dom.therapyPatientSelect && dom.therapyPatientSelect.value;
            const inlineFirstName = dom.therapyForm.querySelector('[name="inline_first_name"]').value.trim();
            const inlineLastName = dom.therapyForm.querySelector('[name="inline_last_name"]').value.trim();
            if (!hasExistingPatient && !inlineFirstName && !inlineLastName) {
                showAlert('Seleziona un paziente esistente o compila i dati del nuovo paziente.', 'warning');
                return false;
            }
        }

        const formElements = dom.therapyForm.querySelectorAll(`.wizard-step[data-step="${state.currentTherapyStep}"] input[required], .wizard-step[data-step="${state.currentTherapyStep}"] textarea[required], .wizard-step[data-step="${state.currentTherapyStep}"] select[required]`);
        for (const element of formElements) {
            if (!element.value) {
                element.classList.add('is-invalid');
                element.addEventListener('input', () => element.classList.remove('is-invalid'), { once: true });
                showAlert('Completa tutti i campi obbligatori prima di proseguire', 'warning');
                return false;
            }
        }
        return true;
    }

    function updateWizardUI() {
        dom.wizardSteps.forEach(step => {
            const stepNumber = parseInt(step.dataset.step, 10);
            step.classList.toggle('active', stepNumber === state.currentTherapyStep);
            step.classList.toggle('completed', stepNumber < state.currentTherapyStep);
        });

        dom.therapyWizardSteps.forEach(indicator => {
            const stepNumber = parseInt(indicator.dataset.step, 10);
            indicator.classList.toggle('active', stepNumber === state.currentTherapyStep);
            indicator.classList.toggle('completed', stepNumber < state.currentTherapyStep);
        });

        dom.wizardSteps.forEach(step => {
            const stepNumber = parseInt(step.dataset.step, 10);
            const content = document.querySelector(`.wizard-step[data-step="${stepNumber}"]`);
            if (content) {
                content.classList.toggle('active', stepNumber === state.currentTherapyStep);
            }
        });

        const progress = ((state.currentTherapyStep - 1) / (dom.wizardSteps.length - 1)) * 100;
        if (dom.therapyWizardIndicator) {
            dom.therapyWizardIndicator.style.width = `${progress}%`;
        }

        dom.prevStepButton.disabled = state.currentTherapyStep === 1;
        dom.nextStepButton.classList.toggle('d-none', state.currentTherapyStep === dom.wizardSteps.length);
        dom.submitTherapyButton.classList.toggle('d-none', state.currentTherapyStep !== dom.wizardSteps.length);

        if (state.currentTherapyStep === dom.wizardSteps.length) {
            updateSummaryPreview();
        }
    }

    function updateSummaryPreview() {
        if (!dom.therapySummary) return;
        prepareCaregiversPayload();
        prepareQuestionnairePayload();

        const patientId = dom.therapyPatientSelect.value || state.selectedPatientId;
        const patient = state.patients.find(p => String(p.id) === String(patientId));
        const title = dom.therapyForm.querySelector('[name="therapy_title"]').value;
        const description = dom.therapyForm.querySelector('[name="therapy_description"]').value;
        const startDate = dom.therapyForm.querySelector('[name="start_date"]').value;
        const endDate = dom.therapyForm.querySelector('[name="end_date"]').value;
        const status = dom.therapyForm.querySelector('[name="status"]').value;
        const caregivers = JSON.parse(dom.caregiversPayloadInput.value || '[]');
        const questionnaire = JSON.parse(dom.questionnairePayloadInput.value || '{}');

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

    function initializeSignaturePad() {
        if (!dom.signatureCanvas) return;

        if (state.signaturePadInitialized) {
            clearSignature();
            return;
        }

        const canvas = dom.signatureCanvas;
        const context = canvas.getContext('2d');

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const parent = canvas.parentElement;
            const displayWidth = parent?.offsetWidth || 600;
            const displayHeight = parent?.offsetHeight || 200;

            let existingImage = null;
            if (state.signaturePadDirty) {
                existingImage = new Image();
                existingImage.src = canvas.toDataURL('image/png');
            }

            canvas.width = displayWidth * ratio;
            canvas.height = displayHeight * ratio;
            canvas.style.width = displayWidth + 'px';
            canvas.style.height = displayHeight + 'px';

            context.setTransform(1, 0, 0, 1, 0, 0);
            context.scale(ratio, ratio);

            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, displayWidth, displayHeight);

            context.strokeStyle = '#000000';
            context.lineWidth = 2;
            context.lineCap = 'round';
            context.lineJoin = 'round';

            if (existingImage && state.signaturePadDirty) {
                existingImage.onload = () => {
                    context.drawImage(existingImage, 0, 0, displayWidth, displayHeight);
                };
            }
        }

        resizeCanvas();

        let drawing = false;
        let lastX = 0;
        let lastY = 0;

        function getCanvasPosition(event) {
            const rect = canvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;

            const clientX = event.clientX ?? (event.touches && event.touches[0]?.clientX);
            const clientY = event.clientY ?? (event.touches && event.touches[0]?.clientY);

            return {
                x: (clientX - rect.left) * (canvas.width / rect.width) / ratio,
                y: (clientY - rect.top) * (canvas.height / rect.height) / ratio
            };
        }

        function startDrawing(event) {
            drawing = true;
            const pos = getCanvasPosition(event);
            lastX = pos.x;
            lastY = pos.y;
            state.signaturePadDirty = true;
        }

        function draw(event) {
            if (!drawing) return;
            event.preventDefault();

            const pos = getCanvasPosition(event);

            context.beginPath();
            context.moveTo(lastX, lastY);
            context.lineTo(pos.x, pos.y);
            context.stroke();

            lastX = pos.x;
            lastY = pos.y;
        }

        function stopDrawing() {
            drawing = false;
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);

        canvas.addEventListener('touchstart', e => {
            e.preventDefault();
            startDrawing(e.touches[0]);
        }, { passive: false });

        canvas.addEventListener('touchmove', e => {
            e.preventDefault();
            draw(e.touches[0]);
        }, { passive: false });

        canvas.addEventListener('touchend', e => {
            e.preventDefault();
            stopDrawing();
        }, { passive: false });

        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(resizeCanvas, 250);
        });

        state.signaturePadInitialized = true;
    }

    function saveSignature() {
        if (!dom.signatureCanvas || !dom.signatureImageInput) return;

        if (dom.signatureTypeSelect.value === 'digital') {
            const digital = dom.therapyForm.querySelector('[name="digital_signature"]').value.trim();
            if (!digital) {
                showAlert('Inserisci la firma digitale', 'warning');
                return;
            }
            dom.signatureImageInput.value = '';
            showAlert('Firma digitale registrata', 'success');
            return;
        }

        if (!state.signaturePadDirty) {
            showAlert('Disegna la firma prima di salvarla', 'warning');
            return;
        }

        captureSignatureImage(true);
        if (dom.signatureImageInput.value) {
            showAlert('Firma salvata', 'success');
        }
    }

    function clearSignature() {
        if (!dom.signatureCanvas) return;

        const canvas = dom.signatureCanvas;
        const context = canvas.getContext('2d');

        context.setTransform(1, 0, 0, 1, 0, 0);
        context.clearRect(0, 0, canvas.width, canvas.height);

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.strokeStyle = '#000000';
        context.lineWidth = 2;
        context.lineCap = 'round';
        context.lineJoin = 'round';

        state.signaturePadDirty = false;
        dom.signatureImageInput.value = '';
    }

    function updateSignatureMode() {
        const mode = dom.signatureTypeSelect ? dom.signatureTypeSelect.value : 'graphical';
        if (mode === 'digital') {
            dom.signatureCanvasWrapper.classList.add('d-none');
            dom.digitalSignatureWrapper.classList.remove('d-none');
        } else {
            dom.signatureCanvasWrapper.classList.remove('d-none');
            dom.digitalSignatureWrapper.classList.add('d-none');
        }
    }

    function toggleInlinePatientForm(show) {
        if (!dom.inlinePatientForm) return;

        if (show) {
            dom.inlinePatientForm.classList.add('show');
            dom.inlinePatientForm.style.display = 'block';
            if (dom.therapyPatientSelect) {
                dom.therapyPatientSelect.value = '';
                dom.therapyPatientSelect.setAttribute('disabled', 'disabled');
            }
            const patientIdField = document.getElementById('therapyPatientId');
            if (patientIdField) {
                patientIdField.value = '';
            }
            dom.inlinePatientForm.querySelectorAll('input, textarea')
                .forEach(field => field.value = '');
            dom.inlinePatientToggle?.setAttribute('aria-expanded', 'true');
        } else {
            dom.inlinePatientForm.classList.remove('show');
            dom.inlinePatientForm.style.display = '';
            dom.therapyPatientSelect?.removeAttribute('disabled');
            dom.inlinePatientToggle?.setAttribute('aria-expanded', 'false');
        }
    }

    function captureSignatureImage(force = false) {
        if (!dom.signatureCanvas || !dom.signatureImageInput) return;

        const mode = dom.signatureTypeSelect ? dom.signatureTypeSelect.value : 'graphical';
        if (mode === 'digital') {
            dom.signatureImageInput.value = '';
            return;
        }

        if (!state.signaturePadDirty && !force) return;

        try {
            const dataUrl = dom.signatureCanvas.toDataURL('image/png');
            if (dataUrl && dataUrl !== 'data:,') {
                dom.signatureImageInput.value = dataUrl;
            }
        } catch (e) {
            console.error('Errore cattura firma:', e);
            showAlert('Errore nel salvataggio della firma. Riprova.', 'danger');
        }
    }

    function openCheckModal(therapyId = null, mode = 'execution') {
        resetForm(dom.checkForm);
        ensureCheckFormEnhancements();
        if (therapyId) {
            dom.checkTherapySelect.value = String(therapyId);
            const hiddenTherapy = dom.checkForm.querySelector('#checkTherapyId');
            if (hiddenTherapy) {
                hiddenTherapy.value = String(therapyId);
            }
        }
        if (checkModeSelect) {
            checkModeSelect.value = mode;
            toggleCheckMode(mode);

        }
        refreshChecklistUI();
        openModal(dom.checkModal);
    }

    function openReminderModal(therapyId = null) {
        resetForm(dom.reminderForm);
        if (therapyId) dom.reminderTherapySelect.value = String(therapyId);
        openModal(dom.reminderModal);
    }

    function openReportModal(therapyId = null) {
        resetForm(dom.reportForm);
        dom.generatedReportContainer.classList.add('d-none');
        if (therapyId) dom.reportTherapySelect.value = String(therapyId);
        openModal(dom.reportModal);
    }

        loadData();
        setupEventListeners();
    });
})();
