<?php

namespace Modules\AdesioneTerapie\Services;

class FormattingService
{
    public function formatPatient(array $patient, array $patientCols): array
    {
        $id = (int)($patient[$patientCols['id']] ?? 0);
        $first = $patientCols['first_name'] ? ($patient[$patientCols['first_name']] ?? '') : '';
        $last = $patientCols['last_name'] ? ($patient[$patientCols['last_name']] ?? '') : '';
        $fullName = trim($first . ' ' . $last);
        if ($fullName === '') {
            $fullName = $first ?: $last;
        }

        return [
            'id' => $id,
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => $fullName,
            'phone' => $patientCols['phone'] ? ($patient[$patientCols['phone']] ?? '') : '',
            'email' => $patientCols['email'] ? ($patient[$patientCols['email']] ?? '') : '',
            'birth_date' => $patientCols['birth_date'] ? ($patient[$patientCols['birth_date']] ?? null) : null,
            'notes' => $patientCols['notes'] ? ($patient[$patientCols['notes']] ?? '') : '',
        ];
    }

    public function formatTherapy(array $therapy, array $therapyCols, array $patientCols, TherapyMetadataService $metadataService): array
    {
        $metadata = $metadataService->extractMetadata($therapy, $therapyCols);

        $patientName = trim(($therapy['patient_first_name'] ?? '') . ' ' . ($therapy['patient_last_name'] ?? ''));

        return [
            'id' => (int)($therapy[$therapyCols['id']] ?? 0),
            'patient_id' => (int)($therapy[$therapyCols['patient']] ?? 0),
            'patient_name' => $patientName,
            'patient_phone' => $therapy['patient_phone'] ?? '',
            'patient_email' => $therapy['patient_email'] ?? '',
            'title' => $therapyCols['title'] ? ($therapy[$therapyCols['title']] ?? '') : '',
            'description' => $therapyCols['description'] ? ($therapy[$therapyCols['description']] ?? '') : '',
            'status' => $therapyCols['status'] ? ($therapy[$therapyCols['status']] ?? 'active') : 'active',
            'start_date' => $therapyCols['start_date'] ? ($therapy[$therapyCols['start_date']] ?? null) : null,
            'end_date' => $therapyCols['end_date'] ? ($therapy[$therapyCols['end_date']] ?? null) : null,
            'caregivers' => $metadata['caregivers'],
            'questionnaire' => $metadata['questionnaire'],
            'metadata' => $metadata['metadata'],
        ];
    }

    public function formatCaregiver(array $caregiver, array $assistantCols): array
    {
        $type = $caregiver[$assistantCols['type']] ?? $caregiver['pivot_role'] ?? 'familiare';
        $firstName = $assistantCols['first_name'] ? ($caregiver[$assistantCols['first_name']] ?? '') : '';
        $lastName = $assistantCols['last_name'] ? ($caregiver[$assistantCols['last_name']] ?? '') : '';
        $fullName = trim($firstName . ' ' . $lastName);

        return [
            'id' => (int)($caregiver[$assistantCols['id']] ?? 0),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $fullName !== '' ? $fullName : ($firstName !== '' ? $firstName : $lastName),
            'type' => $assistantCols['type'] ? $type : '',
            'phone' => $assistantCols['phone'] ? ($caregiver[$assistantCols['phone']] ?? '') : '',
            'email' => $assistantCols['email'] ? ($caregiver[$assistantCols['email']] ?? '') : '',
            'relationship' => $type,
        ];
    }

    public function formatCheck(array $check, array $checkCols, QuestionnaireService $questionnaireService): array
    {
        $assessment = '';
        $notesText = '';
        $actions = '';
        $type = 'execution';
        $questions = [];
        $answersPayloadRaw = $checkCols['answers_payload'] ? ($check[$checkCols['answers_payload']] ?? '') : '';
        $answersPayload = $this->normalizeAnswersPayload($answersPayloadRaw);
        $questionsPayloadRaw = $checkCols['questions_payload'] ? ($check[$checkCols['questions_payload']] ?? '') : '';
        if ($questionsPayloadRaw !== '') {
            $decodedQuestions = json_decode($questionsPayloadRaw, true);
            if (is_array($decodedQuestions)) {
                $questions = $questionnaireService->normalizeChecklistQuestions($decodedQuestions);
            }
        }
        $rawNotes = $checkCols['notes'] ? ($check[$checkCols['notes']] ?? '') : '';
        $decoded = json_decode($rawNotes, true);
        if (is_array($decoded)) {
            $type = $decoded['type'] ?? ($decoded['questions'] ?? false ? 'checklist' : 'execution');
            $assessment = $decoded['assessment'] ?? '';
            $notesText = $decoded['notes'] ?? '';
            $actions = $decoded['actions'] ?? '';
            if (!empty($decoded['questions']) && is_array($decoded['questions'])) {
                $questions = $questionnaireService->normalizeChecklistQuestions($decoded['questions']);
            }
        } elseif ($rawNotes !== '') {
            $assessment = $rawNotes;
            $notesText = $rawNotes;
        }

        if (!empty($questions)) {
            $type = 'checklist';
        }

        return [
            'id' => (int)($check[$checkCols['id']] ?? 0),
            'therapy_id' => $checkCols['therapy'] ? (int)($check[$checkCols['therapy']] ?? 0) : 0,
            'scheduled_at' => $checkCols['scheduled_at'] ? ($check[$checkCols['scheduled_at']] ?? null) : null,
            'assessment' => $assessment,
            'notes' => $notesText,
            'actions' => $actions,
            'type' => $type,
            'questions' => $questions,
            'answers_payload' => $answersPayload,
        ];
    }

    public function formatReminder(array $reminder, array $reminderCols): array
    {
        $messageRaw = $reminderCols['message'] ? ($reminder[$reminderCols['message']] ?? '') : '';
        $decoded = json_decode($messageRaw, true);
        $messageText = $decoded['text'] ?? ($messageRaw ?? '');
        $type = $decoded['type'] ?? 'one-shot';
        $recurrence = $decoded['recurrence'] ?? '';

        return [
            'id' => (int)($reminder[$reminderCols['id']] ?? 0),
            'therapy_id' => $reminderCols['therapy'] ? (int)($reminder[$reminderCols['therapy']] ?? 0) : 0,
            'title' => $reminderCols['title'] ? ($reminder[$reminderCols['title']] ?? 'Promemoria terapia') : 'Promemoria terapia',
            'type' => $type,
            'message' => $messageText,
            'scheduled_at' => $reminderCols['scheduled_at'] ? ($reminder[$reminderCols['scheduled_at']] ?? null) : null,
            'recurrence_rule' => $recurrence,
            'channel' => $reminderCols['channel'] ? ($reminder[$reminderCols['channel']] ?? '') : '',
            'status' => $reminderCols['status'] ? ($reminder[$reminderCols['status']] ?? '') : '',
        ];
    }

    public function formatReport(array $report, array $reportCols, callable $buildReportUrl): array
    {
        $content = [];
        if ($reportCols['content'] && !empty($report[$reportCols['content']])) {
            $decoded = json_decode($report[$reportCols['content']], true);
            if (is_array($decoded)) {
                $content = $decoded;
            }
        }

        $token = $reportCols['share_token'] ? ($report[$reportCols['share_token']] ?? '') : '';
        $pinRequired = $reportCols['pin_code'] ? !empty($report[$reportCols['pin_code']]) : false;

        return [
            'id' => (int)($report[$reportCols['id']] ?? 0),
            'therapy_id' => $reportCols['therapy'] ? (int)($report[$reportCols['therapy']] ?? 0) : 0,
            'token' => $token,
            'valid_until' => $reportCols['valid_until'] ? ($report[$reportCols['valid_until']] ?? null) : null,
            'recipients' => $reportCols['recipients'] ? ($report[$reportCols['recipients']] ?? '') : '',
            'pin_required' => $pinRequired,
            'content' => $content,
            'url' => $token !== '' ? call_user_func($buildReportUrl, $token, $pinRequired) : null,
        ];
    }

    private function normalizeAnswersPayload($rawPayload): array
    {
        if (is_string($rawPayload)) {
            $decoded = json_decode($rawPayload, true);
        } else {
            $decoded = $rawPayload;
        }

        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $key => $answer) {
            $questionKey = trim((string)$key);
            if ($questionKey === '') {
                continue;
            }
            $value = is_array($answer) ? ($answer['value'] ?? null) : $answer;
            $value = in_array($value, ['yes', 'no'], true) ? $value : null;
            $note = is_array($answer) ? trim((string)($answer['note'] ?? '')) : '';
            $normalized[$questionKey] = [
                'value' => $value,
                'note' => $note,
            ];
        }

        return $normalized;
    }
}
