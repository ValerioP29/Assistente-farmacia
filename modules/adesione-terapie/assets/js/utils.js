// Utility helpers for Adesione Terapie module.

export function sanitizeHtml(input) {
    const div = document.createElement('div');
    div.textContent = input || '';
    return div.innerHTML;
}

export function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('it-IT');
}

export function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return dateString;
    return date.toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' });
}

export function statusLabel(status) {
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

export function addDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

export function caregiverFullName(caregiver = {}) {
    const parts = [caregiver.first_name, caregiver.last_name].filter(Boolean);
    const fullName = parts.join(' ').trim();
    return fullName || 'Caregiver';
}
