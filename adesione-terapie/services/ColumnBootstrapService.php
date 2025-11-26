<?php

namespace Modules\AdesioneTerapie\Services;

use AdesioneTableResolver;

class ColumnBootstrapService
{
    public function bootstrap(
        string $patientsTable,
        string $therapiesTable,
        string $assistantsTable,
        string $assistantPivotTable,
        string $consentsTable,
        string $questionnairesTable,
        string $checksTable,
        string $checkAnswersTable,
        string $remindersTable,
        string $reportsTable
    ): array {
        return [
            'patientCols' => $this->resolvePatientColumns($patientsTable),
            'therapyCols' => $this->resolveTherapyColumns($therapiesTable),
            'assistantCols' => $this->resolveAssistantColumns($assistantsTable),
            'assistantPivotCols' => $this->resolveAssistantPivotColumns($assistantPivotTable),
            'consentCols' => $this->resolveConsentColumns($consentsTable),
            'questionnaireCols' => $this->resolveQuestionnaireColumns($questionnairesTable),
            'checkCols' => $this->resolveCheckColumns($checksTable),
            'checkAnswerCols' => $this->resolveCheckAnswerColumns($checkAnswersTable),
            'reminderCols' => $this->resolveReminderColumns($remindersTable),
            'reportCols' => $this->resolveReportColumns($reportsTable),
        ];
    }

    private function resolvePatientColumns(string $patientsTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['id', 'patient_id', 'id_patient']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['pharmacy_id', 'pharma_id', 'farmacia_id', 'id_pharmacy']),
            'first_name' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['first_name', 'nome', 'name']),
            'last_name' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['last_name', 'cognome', 'surname']),
            'phone' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['phone', 'telefono', 'cellulare']),
            'email' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['email', 'mail']),
            'birth_date' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['birth_date', 'data_nascita', 'dob']),
            'notes' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['notes', 'note', 'metadata', 'extra_data', 'data', 'details']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['updated_at', 'modificato_il']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($patientsTable, ['created_at', 'creato_il'])
        ];
    }

    private function resolveTherapyColumns(string $therapiesTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['id', 'therapy_id', 'id_therapy']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['pharmacy_id', 'pharma_id', 'farmacia_id']),
            'patient' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['patient_id', 'id_patient', 'paziente_id']),
            'title' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['therapy_title', 'title', 'nome', 'name']),
            'description' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['therapy_description', 'description', 'descrizione', 'details', 'note']),
            'status' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['status', 'stato']),
            'start_date' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['start_date', 'data_inizio', 'inizio']),
            'end_date' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['end_date', 'data_fine', 'fine']),
            'metadata' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['metadata', 'extra_data', 'payload', 'data', 'details_json']),
            'questionnaire' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['questionnaire', 'questionario', 'questionnaire_data', 'questionario_data']),
            'caregivers' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['caregivers', 'caregiver_json', 'assistenti']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['updated_at']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($therapiesTable, ['created_at'])
        ];
    }

    private function resolveAssistantColumns(string $assistantsTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['id']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['pharma_id', 'pharmacy_id']),
            'first_name' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['first_name']),
            'last_name' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['last_name']),
            'type' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['type']),
            'phone' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['phone']),
            'email' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['email']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($assistantsTable, ['created_at']),
        ];
    }

    private function resolveAssistantPivotColumns(string $assistantPivotTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($assistantPivotTable, ['id']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($assistantPivotTable, ['therapy_id']),
            'assistant' => AdesioneTableResolver::firstAvailableColumn($assistantPivotTable, ['assistant_id']),
            'role' => AdesioneTableResolver::firstAvailableColumn($assistantPivotTable, ['role']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($assistantPivotTable, ['created_at']),
        ];
    }

    private function resolveConsentColumns(string $consentsTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['id', 'consent_id', 'id_consent']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['therapy_id', 'id_therapy']),
            'signer_name' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['signer_name']),
            'signer_relation' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['signer_relation']),
            'consent_text' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['consent_text']),
            'signature_image' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['signature_image', 'firma_grafica']),
            'ip' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['ip_address', 'ip']),
            'signed_at' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['signed_at']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($consentsTable, ['created_at'])
        ];
    }

    private function resolveQuestionnaireColumns(string $questionnairesTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($questionnairesTable, ['id', 'questionnaire_id']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($questionnairesTable, ['therapy_id', 'id_therapy']),
            'question' => AdesioneTableResolver::firstAvailableColumn($questionnairesTable, ['question']),
            'answer' => AdesioneTableResolver::firstAvailableColumn($questionnairesTable, ['answer']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($questionnairesTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($questionnairesTable, ['updated_at'])
        ];
    }

    private function resolveCheckColumns(string $checksTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($checksTable, ['id', 'check_id', 'id_check']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($checksTable, ['therapy_id', 'id_therapy']),
            'scheduled_at' => AdesioneTableResolver::firstAvailableColumn($checksTable, ['scheduled_at', 'check_date', 'data_controllo', 'data_visita']),
            'notes' => AdesioneTableResolver::firstAvailableColumn($checksTable, ['notes']),
            'questions_payload' => AdesioneTableResolver::firstAvailableColumn($checksTable, ['questions_payload', 'questions_json']),
            'answers_payload' => AdesioneTableResolver::firstAvailableColumn($checksTable, ['answers_payload', 'answers_json']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($checksTable, ['created_at'])
        ];
    }

    private function resolveCheckAnswerColumns(string $checkAnswersTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($checkAnswersTable, ['id', 'answer_id', 'id_answer']),
            'check' => AdesioneTableResolver::firstAvailableColumn($checkAnswersTable, ['check_id', 'id_check']),
            'question' => AdesioneTableResolver::firstAvailableColumn($checkAnswersTable, ['question', 'question_key']),
            'answer' => AdesioneTableResolver::firstAvailableColumn($checkAnswersTable, ['answer', 'value']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($checkAnswersTable, ['created_at']),
        ];
    }

    private function resolveReminderColumns(string $remindersTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['id', 'reminder_id', 'id_reminder']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['therapy_id', 'id_therapy']),
            'title' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['title', 'titolo']),
            'message' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['message', 'messaggio', 'testo']),
            'scheduled_at' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['scheduled_at', 'data_promemoria']),
            'channel' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['channel', 'canale']),
            'status' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['status', 'stato']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($remindersTable, ['updated_at'])
        ];
    }

    private function resolveReportColumns(string $reportsTable): array
    {
        return [
            'id' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['id', 'report_id', 'id_report']),
            'therapy' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['therapy_id', 'id_therapy']),
            'pharmacy' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['pharmacy_id', 'pharma_id']),
            'content' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['content', 'contenuto', 'data', 'payload']),
            'share_token' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['share_token', 'token', 'public_token']),
            'valid_until' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['valid_until', 'scadenza', 'expires_at']),
            'recipients' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['recipients', 'destinatari']),
            'pin_code' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['pin_code', 'pin']),
            'created_at' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['created_at']),
            'updated_at' => AdesioneTableResolver::firstAvailableColumn($reportsTable, ['updated_at'])
        ];
    }
}
