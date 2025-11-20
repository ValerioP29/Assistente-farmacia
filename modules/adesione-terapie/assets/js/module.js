(function () {
    const routesBase = 'adesione-terapie/routes.php';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const moduleRoot = document.querySelector('.adesione-terapie-module');
    if (!moduleRoot) {
        return;
    }

    const state = {
        patients: [],
        therapies: [],
        checks: [],
        reminders: [],
        reports: [],
        timeline: [],
        stats: {},
        selectedPatientId: null,
        currentTherapyStep: 1,
        signaturePad: null,
        signaturePadDirty: false,
        signaturePadInitialized: false,
        therapySubmitting: false,
    };

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
        questionnaireInputs: document.querySelectorAll('.questionnaire-input'),
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

    function fetchJSON(url, options = {}) {
        const config = Object.assign({
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }, options);

        if (config.method && config.method.toUpperCase() === 'POST') {
            config.headers['X-CSRF-Token'] = csrfToken;
        }

        return fetch(url, config).then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success === false) {
                const errorMessage = data.message || 'Errore durante la richiesta';
                throw new Error(errorMessage);
            }
            return data;
        });
    }

    function loadData() {
        toggleLoading(true);
        fetchJSON(`${routesBase}?action=init`).then(response => {
            state.patients = response.data.patients || [];
            state.therapies = response.data.therapies || [];
            state.checks = response.data.checks || [];
            state.reminders = response.data.reminders || [];
            state.reports = response.data.reports || [];
            state.timeline = response.data.timeline || [];
            state.stats = response.data.stats || {};

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

            card.innerHTML = `
                <div class="therapy-card-header">
                    <div>
                        <h5>${sanitizeHtml(therapy.description || 'Terapia senza descrizione')}</h5>
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
                    </div>
                </div>
                <div class="therapy-card-footer">
                    <button class="btn btn-sm btn-outline-primary me-2" data-action="open-check" data-therapy="${therapy.id}">
                        <i class="fas fa-stethoscope me-1"></i>Nuovo check
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
            entry.innerHTML = `
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <h6>${sanitizeHtml(item.title)}</h6>
                    <span class="timeline-date">${formatDateTime(item.scheduled_at)}</span>
                    <p>${sanitizeHtml(item.details || '')}</p>
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

        const therapyOptions = state.therapies.map(therapy => `<option value="${therapy.id}">${sanitizeHtml((therapy.patient_name ? therapy.patient_name + ' - ' : '') + (therapy.description || 'Terapia'))}</option>`).join('');
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

    function statusLabel(status) {
        switch (status) {
            case 'completed':
                return 'Completata';
            case 'planned':
                return 'Pianificata';
            case 'suspended':
                return 'Sospesa';
            case 'active':
            default:
                return 'Attiva';
        }
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        if (Number.isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString('it-IT');
    }

    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        if (Number.isNaN(date.getTime())) return dateString;
        return date.toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' });
    }

    function addDays(date, days) {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    }

    function sanitizeHtml(input) {
        const div = document.createElement('div');
        div.textContent = input || '';
        return div.innerHTML;
    }

    function caregiverFullName(caregiver) {
        const parts = [caregiver.first_name, caregiver.last_name].filter(Boolean);
        const fullName = parts.join(' ').trim();
        return fullName || 'Caregiver';
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

    function setupForms() {
        if (dom.patientForm) {
            dom.patientForm.addEventListener('submit', event => {
                event.preventDefault();
                const formData = new FormData(dom.patientForm);
                formData.append('action', 'save_patient');
                fetchJSON(routesBase, { method: 'POST', body: formData }).then(response => {
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
                }).catch(handleError);
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
                formData.append('action', 'save_therapy');
                fetchJSON(routesBase, { method: 'POST', body: formData }).then(response => {
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
            dom.checkForm.addEventListener('submit', event => {
                event.preventDefault();
                const formData = new FormData(dom.checkForm);
                formData.append('action', 'save_check');
                fetchJSON(routesBase, { method: 'POST', body: formData }).then(response => {
                    closeModal(dom.checkModal);
                    showAlert('Check periodico salvato', 'success');
                    const index = state.checks.findIndex(item => item.id === response.check.id);
                    if (index >= 0) {
                        state.checks[index] = response.check;
                    } else {
                        state.checks.push(response.check);
                    }
                    loadData();
                }).catch(handleError);
            });
        }

        if (dom.reminderForm) {
            dom.reminderForm.addEventListener('submit', event => {
                event.preventDefault();
                const formData = new FormData(dom.reminderForm);
                formData.append('action', 'save_reminder');
                fetchJSON(routesBase, { method: 'POST', body: formData }).then(response => {
                    closeModal(dom.reminderModal);
                    showAlert('Promemoria salvato', 'success');
                    const index = state.reminders.findIndex(item => item.id === response.reminder.id);
                    if (index >= 0) {
                        state.reminders[index] = response.reminder;
                    } else {
                        state.reminders.push(response.reminder);
                    }
                    loadData();
                }).catch(handleError);
            });
        }

        if (dom.reportForm) {
            dom.reportForm.addEventListener('submit', event => {
                event.preventDefault();
                const formData = new FormData(dom.reportForm);
                formData.append('action', 'generate_report');
                fetchJSON(routesBase, { method: 'POST', body: formData }).then(response => {
                    dom.generatedReportContainer.classList.remove('d-none');
                    dom.generatedReportLink.value = response.report.url;
                    dom.generatedReportInfo.textContent = response.report.valid_until ? `Valido fino al ${formatDate(response.report.valid_until)}` : 'Validità illimitata';
                    showAlert('Report generato con successo', 'success');
                    loadData();
                }).catch(handleError);
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
        if (!dom.questionnaireInputs || !dom.questionnairePayloadInput) return;
        const questionnaire = {};
        dom.questionnaireInputs.forEach(input => {
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
        const description = dom.therapyForm.querySelector('[name="description"]').value;
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
        resizeCanvas();

        let drawing = false;
        let lastX = 0;
        let lastY = 0;

        function startDrawing(event) {
            drawing = true;
            const pos = getCanvasPosition(event);
            lastX = pos.x;
            lastY = pos.y;
            state.signaturePadDirty = true;
        }

        function draw(event) {
            if (!drawing) return;
            const pos = getCanvasPosition(event);
            context.beginPath();
            context.moveTo(lastX, lastY);
            context.lineTo(pos.x, pos.y);
            context.strokeStyle = '#000000';
            context.lineWidth = 2;
            context.lineCap = 'round';
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

        canvas.addEventListener('touchstart', event => {
            event.preventDefault();
            startDrawing(event.touches[0]);
        });
        canvas.addEventListener('touchmove', event => {
            event.preventDefault();
            draw(event.touches[0]);
        });
        canvas.addEventListener('touchend', event => {
            event.preventDefault();
            stopDrawing();
        });

        window.addEventListener('resize', resizeCanvas);
        state.signaturePadInitialized = true;

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const displayWidth = canvas.offsetWidth || canvas.parentElement?.offsetWidth || 600;
            const displayHeight = canvas.offsetHeight || canvas.parentElement?.offsetHeight || 200;
            const existing = canvas.toDataURL();

            canvas.width = displayWidth * ratio;
            canvas.height = displayHeight * ratio;
            context.setTransform(1, 0, 0, 1, 0, 0);
            context.scale(ratio, ratio);
            context.clearRect(0, 0, canvas.width, canvas.height);
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, displayWidth, displayHeight);
            context.strokeStyle = '#000000';
            context.lineWidth = 2;
            context.lineCap = 'round';

            if (existing && existing !== 'data:,') {
                const img = new Image();
                img.onload = () => {
                    context.drawImage(img, 0, 0, displayWidth, displayHeight);
                };
                img.src = existing;
            }
        }
    }

    function getCanvasPosition(event) {
        const rect = dom.signatureCanvas.getBoundingClientRect();
        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top,
        };
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
        const context = dom.signatureCanvas.getContext('2d');
        context.setTransform(1, 0, 0, 1, 0, 0);
        context.clearRect(0, 0, dom.signatureCanvas.width, dom.signatureCanvas.height);
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, dom.signatureCanvas.width, dom.signatureCanvas.height);
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
            dom.inlinePatientForm.querySelectorAll('input, textarea').forEach(field => field.value = '');
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
        if (!state.signaturePadDirty && !force) {
            return;
        }
        const dataUrl = dom.signatureCanvas.toDataURL('image/png');
        dom.signatureImageInput.value = dataUrl;
    }

    function openCheckModal(therapyId = null) {
        resetForm(dom.checkForm);
        if (therapyId) {
            dom.checkTherapySelect.value = String(therapyId);
        }
        openModal(dom.checkModal);
    }

    function openReminderModal(therapyId = null) {
        resetForm(dom.reminderForm);
        if (therapyId) {
            dom.reminderTherapySelect.value = String(therapyId);
        }
        openModal(dom.reminderModal);
    }

    function openReportModal(therapyId = null) {
        resetForm(dom.reportForm);
        dom.generatedReportContainer.classList.add('d-none');
        if (therapyId) {
            dom.reportTherapySelect.value = String(therapyId);
        }
        openModal(dom.reportModal);
    }

    loadData();
    setupEventListeners();
})();
