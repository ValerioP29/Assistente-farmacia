let currentTherapyPage = 1;
const therapiesPerPage = 20;

const therapySearchInput = document.getElementById('therapySearch');
const therapyStatusSelect = document.getElementById('therapyStatus');
const therapyTableBody = document.getElementById('therapiesTableBody');
const therapyPagination = document.getElementById('therapyPagination');
const therapyResultsInfo = document.getElementById('therapyResultsInfo');
const reminderModalContainer = document.getElementById('reminderModal');
const reportModalContainer = document.getElementById('reportModal');
const followupModalContainer = document.getElementById('followupModal');

function initCronicoTerapiePage() {
    attachToolbarActions();
    attachFilterActions();
    loadTherapies();
}

document.addEventListener('DOMContentLoaded', initCronicoTerapiePage);

function attachToolbarActions() {
    const btnNewTherapy = document.getElementById('btnNewTherapy');
    const btnReminder = document.getElementById('btnReminder');
    const btnReport = document.getElementById('btnReport');
    const btnFollowup = document.getElementById('btnFollowup');

    if (btnNewTherapy) btnNewTherapy.addEventListener('click', () => openTherapyWizard());
    if (btnReminder) btnReminder.addEventListener('click', () => openRemindersModal());
    if (btnReport) btnReport.addEventListener('click', () => openReportsModal());
    if (btnFollowup) btnFollowup.addEventListener('click', () => openFollowupModal());
}

function attachFilterActions() {
    const btnApplyFilters = document.getElementById('btnApplyFilters');
    if (btnApplyFilters) {
        btnApplyFilters.addEventListener('click', () => {
            currentTherapyPage = 1;
            loadTherapies();
        });
    }

    if (therapySearchInput) {
        therapySearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                currentTherapyPage = 1;
                loadTherapies();
            }
        });
    }
}

async function loadTherapies(page = 1) {
    currentTherapyPage = page;
    setLoadingState();

    const params = new URLSearchParams({
        page: currentTherapyPage,
        per_page: therapiesPerPage
    });

    if (therapyStatusSelect && therapyStatusSelect.value) {
        params.append('status', therapyStatusSelect.value);
    }
    if (therapySearchInput && therapySearchInput.value) {
        params.append('q', therapySearchInput.value.trim());
    }

    try {
        const response = await fetch(`api/therapies.php?${params.toString()}`);
        const result = await response.json();

        if (!result.success) {
            renderErrorRow(result.error || 'Errore nel caricamento delle terapie');
            return;
        }

        renderTherapyRows(result.data?.items || []);
        renderPagination(result.data?.items || []);
    } catch (error) {
        console.error(error);
        renderErrorRow('Errore di rete durante il caricamento delle terapie');
    }
}

function setLoadingState() {
    if (!therapyTableBody) return;
    therapyTableBody.innerHTML = `<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Caricamento...</span></div></td></tr>`;
    if (therapyResultsInfo) therapyResultsInfo.textContent = '';
}

function renderTherapyRows(items) {
    if (!therapyTableBody) return;

    if (!items.length) {
        therapyTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Nessuna terapia trovata</td></tr>';
        if (therapyResultsInfo) therapyResultsInfo.textContent = '0 risultati';
        return;
    }

    const rows = items.map((item) => {
        const patientName = `${item.first_name || ''} ${item.last_name || ''}`.trim();
        const statusBadge = renderStatusBadge(item.status);

        return `<tr>
            <td>${sanitizeHtml(patientName)}</td>
            <td>${sanitizeHtml(item.codice_fiscale || '-')}</td>
            <td>${sanitizeHtml(item.primary_condition || item.therapy_title || '-')}</td>
            <td>${sanitizeHtml(item.therapy_title || '-')}</td>
            <td>${statusBadge}</td>
            <td>${sanitizeHtml(item.start_date || '-')}</td>
            <td>${sanitizeHtml(item.end_date || '-')}</td>
            <td class="text-end">
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" data-therapy-id="${item.id}" title="Modifica" onclick="openTherapyWizard(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" data-therapy-id="${item.id}" title="Promemoria" onclick="openRemindersModal(${item.id})">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" data-therapy-id="${item.id}" title="Report" onclick="openReportsModal(${item.id})">
                        <i class="fas fa-file-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" data-therapy-id="${item.id}" title="Check periodico" onclick="openFollowupModal(${item.id})">
                        <i class="fas fa-stethoscope"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-therapy-id="${item.id}" title="Sospendi" onclick="suspendTherapy(${item.id})">
                        <i class="fas fa-ban"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    });

    therapyTableBody.innerHTML = rows.join('');
    if (therapyResultsInfo) therapyResultsInfo.textContent = `${items.length} risultati`;
}

function renderErrorRow(message) {
    if (!therapyTableBody) return;
    therapyTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${sanitizeHtml(message)}</td></tr>`;
    if (therapyResultsInfo) therapyResultsInfo.textContent = '';
}

function renderStatusBadge(status) {
    const map = {
        active: 'bg-success',
        planned: 'bg-info',
        completed: 'bg-secondary',
        suspended: 'bg-warning text-dark'
    };
    const label = status || '-';
    const cls = map[status] || 'bg-light text-dark';
    return `<span class="badge ${cls}">${sanitizeHtml(label)}</span>`;
}

function renderPagination(items) {
    if (!therapyPagination) return;

    const hasPrev = currentTherapyPage > 1;
    const hasNext = items.length === therapiesPerPage;

    const prevItem = `<li class="page-item ${hasPrev ? '' : 'disabled'}"><button class="page-link" ${hasPrev ? `onclick="loadTherapies(${currentTherapyPage - 1})"` : ''}>Precedente</button></li>`;
    const currentItem = `<li class="page-item active"><span class="page-link">${currentTherapyPage}</span></li>`;
    const nextItem = `<li class="page-item ${hasNext ? '' : 'disabled'}"><button class="page-link" ${hasNext ? `onclick="loadTherapies(${currentTherapyPage + 1})"` : ''}>Successiva</button></li>`;

    therapyPagination.innerHTML = `${prevItem}${currentItem}${nextItem}`;
}

function attachReminderForm(therapySelect) {
    const form = document.getElementById('reminderForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const therapyId = therapySelect?.value;
        if (!therapyId) {
            alert('Seleziona una terapia.');
            return;
        }
        const payload = {
            therapy_id: therapyId,
            title: form.title.value.trim(),
            message: form.message.value.trim(),
            type: form.type.value,
            scheduled_at: form.scheduled_at.value,
            channel: form.channel.value
        };

        try {
            const response = await fetch('api/reminders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (result.success) {
                form.reset();
                loadReminders(therapyId);
            } else {
                alert(result.error || 'Errore nel salvataggio del promemoria');
            }
        } catch (error) {
            console.error(error);
            alert('Errore di rete nel salvataggio del promemoria');
        }
    });
}

async function loadReminders(therapyId) {
    const list = document.getElementById('reminderList');
    if (!list) return;
    if (!therapyId) {
        list.innerHTML = '<div class="text-muted">Seleziona una terapia per visualizzare i promemoria</div>';
        return;
    }
    list.innerHTML = '<div class="text-center text-muted">Caricamento...</div>';
    try {
        const response = await fetch(`api/reminders.php?therapy_id=${therapyId}`);
        const result = await response.json();
        if (!result.success) {
            list.innerHTML = `<div class="text-danger">${sanitizeHtml(result.error || 'Errore nel caricamento')}</div>`;
            return;
        }
        const items = result.data || [];
        if (!items.length) {
            list.innerHTML = '<div class="text-muted">Nessun promemoria</div>';
            return;
        }
        const rows = items.map((r) => `
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between">
                    <strong>${sanitizeHtml(r.title || '-')}</strong>
                    <span class="badge bg-light text-dark">${sanitizeHtml(r.type || '')}</span>
                </div>
                <div class="small text-muted">${sanitizeHtml(r.channel || '')} • ${sanitizeHtml(r.scheduled_at || '')}</div>
                <div>${sanitizeHtml(r.message || '')}</div>
            </div>
        `);
        list.innerHTML = rows.join('');
    } catch (error) {
        console.error(error);
        list.innerHTML = '<div class="text-danger">Errore di rete</div>';
    }
}

function attachReportForm(therapySelect) {
    const form = document.getElementById('reportForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const therapyId = therapySelect?.value;
        if (!therapyId) {
            alert('Seleziona una terapia.');
            return;
        }
        const payload = {
            therapy_id: therapyId,
            content: safeJsonString(form.content.value),
            share_token: form.share_token.value.trim() || null,
            pin_code: form.pin_code.value.trim() || null,
            valid_until: form.valid_until.value || null,
            recipients: form.recipients.value.trim() || null
        };

        try {
            const response = await fetch('api/reports.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (result.success) {
                form.reset();
                loadReports(therapyId);
            } else {
                alert(result.error || 'Errore nel salvataggio del report');
            }
        } catch (error) {
            console.error(error);
            alert('Errore di rete nel salvataggio del report');
        }
    });
}

async function loadReports(therapyId) {
    const list = document.getElementById('reportList');
    if (!list) return;
    if (!therapyId) {
        list.innerHTML = '<div class="text-muted">Seleziona una terapia per visualizzare i report</div>';
        return;
    }
    list.innerHTML = '<div class="text-center text-muted">Caricamento...</div>';
    try {
        const response = await fetch(`api/reports.php?therapy_id=${therapyId}`);
        const result = await response.json();
        if (!result.success) {
            list.innerHTML = `<div class="text-danger">${sanitizeHtml(result.error || 'Errore nel caricamento')}</div>`;
            return;
        }
        const items = result.data || [];
        if (!items.length) {
            list.innerHTML = '<div class="text-muted">Nessun report</div>';
            return;
        }
        const rows = items.map((r) => `
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between">
                    <strong>Report #${sanitizeHtml(r.id)}</strong>
                    <span class="small text-muted">${sanitizeHtml(r.created_at || '')}</span>
                </div>
                <div class="small">Token: ${sanitizeHtml(r.share_token || '-')}</div>
                <div class="small">PIN: ${sanitizeHtml(r.pin_code || '-')}</div>
                <pre class="bg-light p-2 mt-2 mb-0" style="white-space: pre-wrap;">${sanitizeHtml(formatJson(r.content))}</pre>
            </div>
        `);
        list.innerHTML = rows.join('');
    } catch (error) {
        console.error(error);
        list.innerHTML = '<div class="text-danger">Errore di rete</div>';
    }
}

function attachFollowupForm(therapySelect) {
    const form = document.getElementById('followupForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const therapyId = therapySelect?.value;
        if (!therapyId) {
            alert('Seleziona una terapia.');
            return;
        }
        const payload = {
            therapy_id: therapyId,
            risk_score: form.risk_score.value || null,
            pharmacist_notes: form.pharmacist_notes.value.trim() || null,
            education_notes: form.education_notes.value.trim() || null,
            snapshot: safeJsonString(form.snapshot.value),
            follow_up_date: form.follow_up_date.value || null
        };

        try {
            const response = await fetch('api/followups.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (result.success) {
                form.reset();
                loadFollowups(therapyId);
            } else {
                alert(result.error || 'Errore nel salvataggio del follow-up');
            }
        } catch (error) {
            console.error(error);
            alert('Errore di rete nel salvataggio del follow-up');
        }
    });
}

async function loadFollowups(therapyId) {
    const list = document.getElementById('followupList');
    if (!list) return;
    if (!therapyId) {
        list.innerHTML = '<div class="text-muted">Seleziona una terapia per visualizzare i follow-up</div>';
        return;
    }
    list.innerHTML = '<div class="text-center text-muted">Caricamento...</div>';
    try {
        const response = await fetch(`api/followups.php?therapy_id=${therapyId}`);
        const result = await response.json();
        if (!result.success) {
            list.innerHTML = `<div class="text-danger">${sanitizeHtml(result.error || 'Errore nel caricamento')}</div>`;
            return;
        }
        const items = result.data || [];
        if (!items.length) {
            list.innerHTML = '<div class="text-muted">Nessun follow-up</div>';
            return;
        }
        const rows = items.map((r) => `
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between">
                    <strong>Follow-up #${sanitizeHtml(r.id)}</strong>
                    <span class="small text-muted">${sanitizeHtml(r.follow_up_date || '')}</span>
                </div>
                <div class="small">Rischio: ${sanitizeHtml(r.risk_score || '-')}</div>
                <div class="small">Note farmacista: ${sanitizeHtml(r.pharmacist_notes || '')}</div>
                <div class="small">Note educazione: ${sanitizeHtml(r.education_notes || '')}</div>
                <pre class="bg-light p-2 mt-2 mb-0" style="white-space: pre-wrap;">${sanitizeHtml(formatJson(r.snapshot))}</pre>
            </div>
        `);
        list.innerHTML = rows.join('');
    } catch (error) {
        console.error(error);
        list.innerHTML = '<div class="text-danger">Errore di rete</div>';
    }
}

function safeJsonString(value) {
    try {
        return JSON.stringify(JSON.parse(value));
    } catch (e) {
        return value;
    }
}

function formatJson(value) {
    if (typeof value === 'string') {
        try {
            return JSON.stringify(JSON.parse(value), null, 2);
        } catch (e) {
            return value;
        }
    }
    try {
        return JSON.stringify(value || {}, null, 2);
    } catch (e) {
        return '';
    }
}

function sanitizeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function populateTherapySelect(selectEl, selectedId = null, placeholder = 'Seleziona una terapia') {
    if (!selectEl) return [];
    selectEl.innerHTML = `<option value="">${sanitizeHtml(placeholder)}</option>`;
    try {
        const response = await fetch('api/therapies.php?status=active');
        const result = await response.json();
        if (!result.success) return [];
        const items = result.data?.items || result.data || [];
        items.forEach((therapy) => {
            const option = document.createElement('option');
            option.value = therapy.id;
            option.textContent = formatTherapyOption(therapy);
            if (selectedId && String(selectedId) === String(therapy.id)) {
                option.selected = true;
            }
            selectEl.appendChild(option);
        });
        return items;
    } catch (error) {
        console.error('Errore caricamento terapie', error);
        return [];
    }
}

function formatTherapyOption(therapy) {
    const patientName = `${therapy.first_name || ''} ${therapy.last_name || ''}`.trim() || 'Paziente';
    const condition = therapy.primary_condition || therapy.condition || therapy.therapy_title || '-';
    const title = therapy.therapy_title || '-';
    return `${patientName} – ${condition} – ${title}`;
}

function openTherapyWizard(therapyId = null) {
    console.log('openTherapyWizard', therapyId);
    const modalEl = document.getElementById('therapyWizardModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

async function openRemindersModal(therapyId = null) {
    if (!reminderModalContainer) return;

    reminderModalContainer.innerHTML = `
        <div class="modal fade" id="reminderModalDialog" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Promemoria terapia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Terapia</label>
                            <select class="form-select" id="reminderTherapySelect"></select>
                        </div>
                        <div id="reminderList" class="mb-3"></div>
                        <form id="reminderForm" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Titolo</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Canale</label>
                                <select class="form-select" name="channel" required>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="push">Push</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Messaggio</label>
                                <textarea class="form-control" name="message" rows="3" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="type" required>
                                    <option value="one-shot">One-shot</option>
                                    <option value="daily">Giornaliero</option>
                                    <option value="weekly">Settimanale</option>
                                    <option value="monthly">Mensile</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data/Ora</label>
                                <input type="datetime-local" class="form-control" name="scheduled_at" required>
                            </div>
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                                <button type="submit" class="btn btn-primary">Salva promemoria</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;

    const therapySelect = document.getElementById('reminderTherapySelect');
    await populateTherapySelect(therapySelect, therapyId);
    therapySelect?.addEventListener('change', () => {
        loadReminders(therapySelect.value);
    });

    const modal = new bootstrap.Modal(document.getElementById('reminderModalDialog'));
    attachReminderForm(therapySelect);
    loadReminders(therapySelect?.value);
    modal.show();
}

async function openReportsModal(therapyId = null) {
    if (!reportModalContainer) return;

    reportModalContainer.innerHTML = `
        <div class="modal fade" id="reportModalDialog" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Report terapia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Terapia</label>
                            <select class="form-select" id="reportTherapySelect"></select>
                        </div>
                        <div id="reportList" class="mb-3"></div>
                        <form id="reportForm" class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Contenuto JSON</label>
                                <textarea class="form-control" name="content" rows="4" required>{}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Validità fino al</label>
                                <input type="datetime-local" class="form-control" name="valid_until">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Token condivisibile</label>
                                <input type="text" class="form-control" name="share_token" placeholder="opzionale">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PIN (opzionale)</label>
                                <input type="text" class="form-control" name="pin_code" placeholder="opzionale">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Destinatari (email/sms)</label>
                                <input type="text" class="form-control" name="recipients" placeholder="es. email1, email2">
                            </div>
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                                <button type="submit" class="btn btn-primary">Crea report</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;

    const therapySelect = document.getElementById('reportTherapySelect');
    await populateTherapySelect(therapySelect, therapyId);
    therapySelect?.addEventListener('change', () => {
        loadReports(therapySelect.value);
    });

    const modal = new bootstrap.Modal(document.getElementById('reportModalDialog'));
    attachReportForm(therapySelect);
    loadReports(therapySelect?.value);
    modal.show();
}

async function openFollowupModal(therapyId = null) {
    if (!followupModalContainer) return;

    followupModalContainer.innerHTML = `
        <div class="modal fade" id="followupModalDialog" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Follow-up terapia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Terapia</label>
                            <select class="form-select" id="followupTherapySelect"></select>
                        </div>
                        <div id="followupList" class="mb-3"></div>
                        <form id="followupForm" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Rischio aggiornato</label>
                                <input type="number" class="form-control" name="risk_score">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note farmacista</label>
                                <textarea class="form-control" name="pharmacist_notes" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note educazione sanitaria</label>
                                <textarea class="form-control" name="education_notes" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Snapshot JSON</label>
                                <textarea class="form-control" name="snapshot" rows="3">{}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data follow-up</label>
                                <input type="date" class="form-control" name="follow_up_date">
                            </div>
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                                <button type="submit" class="btn btn-primary">Salva follow-up</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;

    const therapySelect = document.getElementById('followupTherapySelect');
    await populateTherapySelect(therapySelect, therapyId);
    therapySelect?.addEventListener('change', () => {
        loadFollowups(therapySelect.value);
    });

    const modal = new bootstrap.Modal(document.getElementById('followupModalDialog'));
    attachFollowupForm(therapySelect);
    loadFollowups(therapySelect?.value);
    modal.show();
}

async function suspendTherapy(therapyId) {
    if (!therapyId) return;
    const confirmed = confirm('Sei sicuro di voler sospendere questa terapia?');
    if (!confirmed) return;

    try {
        const response = await fetch(`api/therapies.php?id=${therapyId}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        if (result.success) {
            loadTherapies(currentTherapyPage);
        } else {
            alert(result.error || 'Errore nella sospensione della terapia');
        }
    } catch (error) {
        console.error(error);
        alert('Errore di rete nella sospensione della terapia');
    }
}
