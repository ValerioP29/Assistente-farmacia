// Event bindings and orchestration for Adesione Terapie module.
import * as api from './api.js';
import * as ui from './ui.js';
import * as logic from './logic.js';
import * as signature from './signature.js';
import { getState } from './state.js';
import { formatDate } from './utils.js';

export function buildDomReferences(moduleRoot = document) {
    const scopedQuery = selector => (moduleRoot?.querySelector ? moduleRoot.querySelector(selector) : document.querySelector(selector));
    return {
        moduleRoot,
        patientsList: scopedQuery('#patientsList'),
        therapiesContainer: scopedQuery('#therapiesContainer'),
        patientSummary: scopedQuery('#patientSummary'),
        timelineContainer: scopedQuery('#timelineContainer'),
        statElements: moduleRoot?.querySelectorAll ? moduleRoot.querySelectorAll('.stat-value') : document.querySelectorAll('.stat-value'),
        patientForm: scopedQuery('#patientForm'),
        therapyForm: scopedQuery('#therapyForm'),
        checkForm: scopedQuery('#checkForm'),
        reminderForm: scopedQuery('#reminderForm'),
        reportForm: scopedQuery('#reportForm'),
        therapyModal: scopedQuery('#therapyModal'),
        patientModal: scopedQuery('#patientModal'),
        checkModal: scopedQuery('#checkModal'),
        reminderModal: scopedQuery('#reminderModal'),
        reportModal: scopedQuery('#reportModal'),
        therapyWizardIndicator: scopedQuery('#therapyWizardIndicator'),
        therapyWizardSteps: moduleRoot?.querySelectorAll ? moduleRoot.querySelectorAll('#therapyWizardSteps span') : document.querySelectorAll('#therapyWizardSteps span'),
        wizardSteps: moduleRoot?.querySelectorAll ? moduleRoot.querySelectorAll('.wizard-step') : document.querySelectorAll('.wizard-step'),
        nextStepButton: scopedQuery('#nextStepButton'),
        prevStepButton: scopedQuery('#prevStepButton'),
        submitTherapyButton: scopedQuery('#submitTherapyButton'),
        therapyPatientSelect: scopedQuery('#therapyPatientSelect'),
        caregiversContainer: scopedQuery('#caregiversContainer'),
        caregiversTemplate: scopedQuery('.caregiver-row.template'),
        addCaregiverButton: scopedQuery('#addCaregiverButton'),
        timelineFilter: scopedQuery('#timelineFilter'),
        signatureCanvas: scopedQuery('#consentSignaturePad'),
        saveSignatureButton: scopedQuery('#saveSignatureButton'),
        clearSignatureButton: scopedQuery('#clearSignatureButton'),
        signatureTypeSelect: scopedQuery('#signatureType'),
        signatureCanvasWrapper: scopedQuery('#signatureCanvasWrapper'),
        digitalSignatureWrapper: scopedQuery('#digitalSignatureWrapper'),
        therapySummary: scopedQuery('#therapySummary'),
        generatedReportContainer: scopedQuery('#generatedReportContainer'),
        generatedReportLink: scopedQuery('#generatedReportLink'),
        generatedReportInfo: scopedQuery('#generatedReportInfo'),
        copyReportLink: scopedQuery('#copyReportLink'),
        reportTherapySelect: scopedQuery('#reportTherapySelect'),
        checkTherapySelect: scopedQuery('#checkTherapySelect'),
        reminderTherapySelect: scopedQuery('#reminderTherapySelect'),
        exportReportButton: scopedQuery('#exportReportButton'),
        newPatientButton: scopedQuery('#newPatientButton'),
        newTherapyButton: scopedQuery('#newTherapyButton'),
        newCheckButton: scopedQuery('#newCheckButton'),
        newReminderButton: scopedQuery('#newReminderButton'),
        refreshDataButton: scopedQuery('#refreshDataButton'),
        inlinePatientToggle: scopedQuery('#openInlinePatientForm'),
        inlinePatientForm: scopedQuery('#inlinePatientForm'),
        caregiversPayloadInput: scopedQuery('#caregiversPayloadInput'),
        questionnairePayloadInput: scopedQuery('#questionnairePayloadInput'),
        signatureImageInput: scopedQuery('#signatureImageInput'),
        generatedReportSection: scopedQuery('#generatedReportSection'),
    };
}

export function initializeEvents({ routesBase, csrfToken, dom }) {
    api.configureApi({ routesBase, csrfToken });
    const state = getState();
    const checkFormState = {
        eventsBound: false,
        checkModeSelect: null,
        checklistQuestionsContainer: null,
        checklistQuestionsList: null,
        checkAnswersContainer: null,
        checkAnswersList: null,
        checkQuestionsPayloadInput: null,
        checkAnswersPayloadInput: null,
        currentChecklistQuestions: [],
    };

    function toggleLoading(isLoading) {
        dom.moduleRoot.classList.toggle('is-loading', isLoading);
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

    function renderAll() {
        ui.renderStats({ dom, state });
        ui.renderPatients({ dom, state, onSelectPatient: id => { state.selectedPatientId = id; renderAll(); } });
        ui.renderTherapies({ dom, state, onAction: handleTherapyAction });
        ui.renderTimeline({ dom, state });
        ui.populateSelects({ dom, state });
    }

    function loadData() {
        toggleLoading(true);
        api.loadInitialData().then(data => {
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

    function setupEventListeners() {
        if (checkFormState.eventsBound) return;
        checkFormState.eventsBound = true;
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
            dom.timelineFilter.addEventListener('change', () => ui.renderTimeline({ dom, state }));
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
            dom.signatureTypeSelect.addEventListener('change', () => signature.updateSignatureMode({ dom }));
        }
        if (dom.saveSignatureButton) {
            dom.saveSignatureButton.addEventListener('click', () => signature.saveSignature({ dom, state, showAlert }));
        }
        if (dom.clearSignatureButton) {
            dom.clearSignatureButton.addEventListener('click', () => signature.clearSignature({ dom, state }));
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

        if (!checkFormState.checkQuestionsPayloadInput) {
            checkFormState.checkQuestionsPayloadInput = document.createElement('input');
            checkFormState.checkQuestionsPayloadInput.type = 'hidden';
            checkFormState.checkQuestionsPayloadInput.name = 'questions_payload';
            dom.checkForm.appendChild(checkFormState.checkQuestionsPayloadInput);
        }
        if (!checkFormState.checkAnswersPayloadInput) {
            checkFormState.checkAnswersPayloadInput = document.createElement('input');
            checkFormState.checkAnswersPayloadInput.type = 'hidden';
            checkFormState.checkAnswersPayloadInput.name = 'answers_payload';
            dom.checkForm.appendChild(checkFormState.checkAnswersPayloadInput);
        }

        const formBody = dom.checkForm.querySelector('.modal-body');
        const fieldRow = dom.checkForm.querySelector('.modal-body .row');

        if (!checkFormState.checkModeSelect) {
            const modeWrapper = document.createElement('div');
            modeWrapper.className = 'mb-3';
            modeWrapper.innerHTML = `
                <label class="form-label">Modalità</label>
                <select class="form-select" id="checkModeSelect">
                    <option value="execution">Registra check</option>
                    <option value="checklist">Configura checklist</option>
                </select>`;
            formBody.insertBefore(modeWrapper, fieldRow);
            checkFormState.checkModeSelect = modeWrapper.querySelector('#checkModeSelect');
            checkFormState.checkModeSelect.addEventListener('change', () => toggleCheckMode(checkFormState.checkModeSelect.value));
        }

        if (!checkFormState.checklistQuestionsContainer) {
            checkFormState.checklistQuestionsContainer = document.createElement('div');
            checkFormState.checklistQuestionsContainer.className = 'mt-3 d-none';
            checkFormState.checklistQuestionsContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Domande checklist</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addChecklistQuestionBtn">
                        <i class="fas fa-plus me-1"></i>Aggiungi domanda
                    </button>
                </div>
                <div class="checklist-questions-list"></div>
            `;
            formBody.appendChild(checkFormState.checklistQuestionsContainer);
            checkFormState.checklistQuestionsList = checkFormState.checklistQuestionsContainer.querySelector('.checklist-questions-list');
            const addButton = checkFormState.checklistQuestionsContainer.querySelector('#addChecklistQuestionBtn');
            addButton.addEventListener('click', () => ui.addChecklistQuestionRow(checkFormState.checklistQuestionsList));
        }

        if (!checkFormState.checkAnswersContainer) {
            checkFormState.checkAnswersContainer = document.createElement('div');
            checkFormState.checkAnswersContainer.className = 'mt-3';
            checkFormState.checkAnswersContainer.innerHTML = `
                <h6 class="mb-2">Risposte checklist</h6>
                <div class="check-answers-list"></div>
            `;
            formBody.appendChild(checkFormState.checkAnswersContainer);
            checkFormState.checkAnswersList = checkFormState.checkAnswersContainer.querySelector('.check-answers-list');
        }

        toggleCheckMode(checkFormState.checkModeSelect ? checkFormState.checkModeSelect.value : 'execution');
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

        if (checkFormState.checklistQuestionsContainer) {
            checkFormState.checklistQuestionsContainer.classList.toggle('d-none', !isChecklist);
        }
        if (checkFormState.checkAnswersContainer) {
            checkFormState.checkAnswersContainer.classList.toggle('d-none', isChecklist);
        }
        if (submitButton) {
            submitButton.innerHTML = isChecklist ? '<i class="fas fa-save me-2"></i>Salva checklist' : '<i class="fas fa-save me-2"></i>Salva check';
        }

        if (isChecklist) {
            const therapyId = parseInt(dom.checkTherapySelect?.value || dom.checkTherapyId?.value || '0', 10);
            const checklist = logic.getChecklistForTherapy(state.checks, therapyId);
            ui.renderChecklistQuestions(checkFormState.checklistQuestionsList, checklist?.questions || checkFormState.currentChecklistQuestions || logic.defaultChecklistQuestions);
        } else if (checkFormState.checkAnswersList) {
            const latestQuestions = logic.collectChecklistQuestions(checkFormState.checklistQuestionsList);
            if (latestQuestions.length) {
                ui.renderChecklistAnswers(checkFormState.checkAnswersList, latestQuestions);
            } else {
                ui.renderChecklistAnswers(checkFormState.checkAnswersList, checkFormState.currentChecklistQuestions);
            }
        }
    }

    function renderChecklistAnswers(questions = []) {
        checkFormState.currentChecklistQuestions = questions && questions.length ? questions : logic.defaultChecklistQuestions;
        ui.renderChecklistAnswers(checkFormState.checkAnswersList, checkFormState.currentChecklistQuestions);
    }

    function refreshChecklistUI() {
        const therapyId = parseInt(dom.checkTherapySelect?.value || dom.checkTherapyId?.value || '0', 10);
        const checklist = logic.getChecklistForTherapy(state.checks, therapyId);
        if (checklist && Array.isArray(checklist.questions)) {
            ui.renderChecklistQuestions(checkFormState.checklistQuestionsList, checklist.questions);
            renderChecklistAnswers(checklist.questions);
        } else {
            ui.renderChecklistQuestions(checkFormState.checklistQuestionsList, logic.defaultChecklistQuestions);
            renderChecklistAnswers(logic.defaultChecklistQuestions);
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
                api.savePatient(formData).then(response => {
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
                const caregivers = logic.prepareCaregiversPayload(dom.caregiversContainer, dom.caregiversPayloadInput);
                const questionnaire = logic.prepareQuestionnairePayload(dom.therapyForm, dom.questionnairePayloadInput);
                signature.captureSignatureImage({ dom, state });
                logic.updateHiddenConsentFields({ dom, state });
                updateSummaryPreview(caregivers, questionnaire);
                const formData = new FormData(dom.therapyForm);
                api.saveTherapy(formData).then(response => {
                    closeModal(dom.therapyModal);
                    showAlert('Terapia salvata con successo', 'success');
                    upsertTherapy(response.therapy);
                    state.selectedPatientId = response.therapy.patient_id;
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

                if (checkFormState.checkModeSelect?.value === 'checklist') {
                    const questions = logic.prepareChecklistPayload(checkFormState.checklistQuestionsList, checkFormState.checkQuestionsPayloadInput);
                    if (!questions.length) {
                        showAlert('Aggiungi almeno una domanda alla checklist.', 'warning');
                        state.checkSubmitting = false;
                        submitButton?.removeAttribute('disabled');
                        return;
                    }
                    if (checkFormState.checkAnswersPayloadInput) {
                        checkFormState.checkAnswersPayloadInput.value = '';
                    }
                    api.saveChecklist(formData).then(response => {
                        const existing = logic.getChecklistForTherapy(state.checks, response.check.therapy_id);
                        if (existing) {
                            Object.assign(existing, response.check);
                        } else {
                            state.checks.push(response.check);
                        }
                        showAlert('Checklist salvata', 'success');
                        loadData();
                        closeModal(dom.checkModal);
                    }).catch(handleError).finally(() => {
                        state.checkSubmitting = false;
                        submitButton?.removeAttribute('disabled');
                    });
                } else {
                    logic.prepareCheckExecutionPayload(dom.checkForm, checkFormState.checkAnswersList, checkFormState.checkAnswersPayloadInput);
                    api.saveCheck(formData).then(response => {
                        const existingIndex = state.checks.findIndex(item => item.id === response.check.id);
                        if (existingIndex >= 0) {
                            state.checks[existingIndex] = response.check;
                        } else {
                            state.checks.push(response.check);
                        }
                        showAlert('Check periodico salvato', 'success');
                        loadData();
                        closeModal(dom.checkModal);
                    }).catch(handleError).finally(() => {
                        state.checkSubmitting = false;
                        submitButton?.removeAttribute('disabled');
                    });
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
                api.saveReminder(formData).then(response => {
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
                api.generateReport(formData).then(response => {
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
        if (dom.signatureImageInput) dom.signatureImageInput.value = '';
        if (dom.questionnairePayloadInput) dom.questionnairePayloadInput.value = '';
        if (dom.caregiversPayloadInput) dom.caregiversPayloadInput.value = '';
        toggleInlinePatientForm(false);
        clearCaregivers();
        addCaregiverRow();
        signature.initializeSignaturePad({ dom, state });
        signature.updateSignatureMode({ dom });
        state.signaturePadDirty = false;
        if (state.selectedPatientId) {
            dom.therapyPatientSelect.value = String(state.selectedPatientId);
            const patientIdField = document.getElementById('therapyPatientId');
            if (patientIdField) {
                patientIdField.value = String(state.selectedPatientId);
            }
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

    function updateSummaryPreview(caregivers = [], questionnaire = {}) {
        const computedCaregivers = caregivers.length ? caregivers : JSON.parse(dom.caregiversPayloadInput?.value || '[]');
        const computedQuestionnaire = Object.keys(questionnaire).length ? questionnaire : JSON.parse(dom.questionnairePayloadInput?.value || '{}');
        logic.updateSummaryPreview({ dom, state, caregivers: computedCaregivers, questionnaire: computedQuestionnaire });
    }

    function upsertTherapy(therapy) {
        const index = state.therapies.findIndex(item => item.id === therapy.id);
        if (index >= 0) {
            state.therapies[index] = therapy;
        } else {
            state.therapies.push(therapy);
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
        if (checkFormState.checkModeSelect) {
            checkFormState.checkModeSelect.value = mode;
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

    function handleTherapyAction(action, therapyId) {
        switch (action) {
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
    }

    loadData();
    setupEventListeners();
}
