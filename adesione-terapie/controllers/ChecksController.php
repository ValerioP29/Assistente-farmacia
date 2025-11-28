<?php

namespace Modules\AdesioneTerapie\Controllers;

use Modules\AdesioneTerapie\Repositories\CheckRepository;
use Modules\AdesioneTerapie\Services\CheckAnswerService;
use Modules\AdesioneTerapie\Services\ChecklistService;
use Modules\AdesioneTerapie\Services\FormattingService;
use Modules\AdesioneTerapie\Services\QuestionnaireService;
use RuntimeException;

class ChecksController
{
    private CheckRepository $checkRepository;
    private CheckAnswerService $checkAnswerService;
    private ChecklistService $checklistService;
    private FormattingService $formattingService;
    private QuestionnaireService $questionnaireService;
    private int $pharmacyId;
    private array $checkCols;
    private array $therapyCols;
    private $cleanCallback;
    private $nowCallback;
    private $verifyTherapyOwnershipCallback;

    public function __construct(
        CheckRepository $checkRepository,
        CheckAnswerService $checkAnswerService,
        ChecklistService $checklistService,
        FormattingService $formattingService,
        QuestionnaireService $questionnaireService,
        int $pharmacyId,
        array $checkCols,
        array $therapyCols,
        callable $cleanCallback,
        callable $nowCallback,
        callable $verifyTherapyOwnershipCallback
    ) {
        $this->checkRepository = $checkRepository;
        $this->checkAnswerService = $checkAnswerService;
        $this->checklistService = $checklistService;
        $this->formattingService = $formattingService;
        $this->questionnaireService = $questionnaireService;
        $this->pharmacyId = $pharmacyId;
        $this->checkCols = $checkCols;
        $this->therapyCols = $therapyCols;
        $this->cleanCallback = $cleanCallback;
        $this->nowCallback = $nowCallback;
        $this->verifyTherapyOwnershipCallback = $verifyTherapyOwnershipCallback;
    }

    public function saveCheck(array $payload): array
    {
        return $this->saveCheckExecution($payload);
    }

    public function saveChecklist(array $payload): array
    {
        // TODO: CHRONIC_REWRITE - legacy checklist flow slated for removal.
        $checkId = isset($payload['check_id']) && $payload['check_id'] !== '' ? (int)$payload['check_id'] : null;
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);

        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per configurare la checklist.');
        }

        $this->verifyTherapyOwnership($therapyId);

        $questionsRaw = $payload['questions_payload'] ?? '[]';
        $decoded = is_array($payload['questions'] ?? null) ? ($payload['questions'] ?? []) : json_decode($questionsRaw, true);
        $questions = $this->checklistService->normalizeChecklistQuestions($decoded);
        if (empty($questions)) {
            throw new RuntimeException('Aggiungi almeno una domanda alla checklist.');
        }

        $scheduledAt = $payload['scheduled_at'] ?? $payload['check_date'] ?? '';
        if ($scheduledAt === '') {
            $scheduledAt = $this->now();
        }

        $data = [];
        if ($this->checkCols['therapy']) {
            $data[$this->checkCols['therapy']] = $therapyId;
        }
        if ($this->checkCols['scheduled_at']) {
            $data[$this->checkCols['scheduled_at']] = str_replace('T', ' ', $scheduledAt);
        }
        $notesPayload = [
            'type' => 'checklist',
            'questions' => $questions,
        ];
        if ($this->checkCols['questions_payload']) {
            $data[$this->checkCols['questions_payload']] = json_encode($questions, JSON_UNESCAPED_UNICODE);
        }
        if ($this->checkCols['notes']) {
            $data[$this->checkCols['notes']] = json_encode($notesPayload, JSON_UNESCAPED_UNICODE);
        }

        $filtered = $this->checkRepository->filterData($data);
        if (!$checkId) {
            $checkId = $this->checklistService->findChecklistId($therapyId);
        }

        if ($checkId) {
            $this->checkRepository->update($checkId, $filtered);
        } else {
            if ($this->checkCols['created_at'] && !isset($filtered[$this->checkCols['created_at']])) {
                $filtered[$this->checkCols['created_at']] = $this->now();
            }
            $checkId = $this->checkRepository->insert($filtered);
        }

        return $this->findCheck($checkId);
    }

    public function saveCheckExecution(array $payload): array
    {
        $checkId = isset($payload['check_id']) && $payload['check_id'] !== '' ? (int)$payload['check_id'] : null;
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);

        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per registrare il check periodico.');
        }

        $this->verifyTherapyOwnership($therapyId);

        $data = [];
        if ($this->checkCols['therapy']) {
            $data[$this->checkCols['therapy']] = $therapyId;
        }
        $scheduledAt = $payload['scheduled_at'] ?? $payload['check_date'] ?? '';
        if ($this->checkCols['scheduled_at'] && $scheduledAt !== '') {
            $data[$this->checkCols['scheduled_at']] = str_replace('T', ' ', $scheduledAt);
        }

        if ($this->checkCols['scheduled_at'] && empty($data[$this->checkCols['scheduled_at']])) {
            throw new RuntimeException('Imposta data e ora del check.');
        }

        $answersPayload = $this->normalizeAnswersPayload($payload['answers_payload'] ?? '[]');
        if (empty($answersPayload)) {
            throw new RuntimeException('Compila almeno una risposta della checklist.');
        }

        if ($this->checkCols['answers_payload']) {
            $data[$this->checkCols['answers_payload']] = json_encode($answersPayload, JSON_UNESCAPED_UNICODE);
        }
        if ($this->checkCols['notes']) {
            $data[$this->checkCols['notes']] = json_encode(['type' => 'execution'], JSON_UNESCAPED_UNICODE);
        }

        $filtered = $this->checkRepository->filterData($data);

        if (!$checkId && $this->checkCols['therapy'] && $this->checkCols['scheduled_at'] && !empty($filtered[$this->checkCols['scheduled_at']])) {
            $existingCheckId = $this->checkRepository->findByTherapyAndScheduledAt($therapyId, $filtered[$this->checkCols['scheduled_at']]);
            if ($existingCheckId) {
                $existingRow = $this->checkRepository->find($existingCheckId, $this->therapyCols['pharmacy'] ? $this->pharmacyId : null);
                $formattedExisting = $existingRow ? $this->formattingService->formatCheck($existingRow, $this->checkCols, $this->questionnaireService) : null;
                if (!$formattedExisting || ($formattedExisting['type'] ?? 'execution') !== 'checklist') {
                    $checkId = $existingCheckId;
                }
            }
        }

        if ($checkId) {
            $this->checkRepository->update($checkId, $filtered);
        } else {
            if ($this->checkCols['created_at'] && !isset($filtered[$this->checkCols['created_at']])) {
                $filtered[$this->checkCols['created_at']] = $this->now();
            }
            $checkId = $this->checkRepository->insert($filtered);
        }

        $legacyAnswers = $this->flattenAnswersForLegacy($answersPayload);
        if (!empty($legacyAnswers)) {
            $this->checkAnswerService->storeCheckAnswers($checkId, $legacyAnswers);
        }

        return $this->findCheck($checkId);
    }

    public function findCheck(int $checkId): array
    {
        $check = $this->checkRepository->find($checkId, $this->therapyCols['pharmacy'] ? $this->pharmacyId : null);
        if (!$check) {
            throw new RuntimeException('Check periodico non trovato.');
        }
        $formatted = $this->formattingService->formatCheck($check, $this->checkCols, $this->questionnaireService);
        $enriched = $this->enrichChecksWithChecklistData([$formatted]);

        return $enriched[0];
    }

    public function listChecks(?int $therapyId = null): array
    {
        $rows = $this->checkRepository->list($therapyId, $this->therapyCols['pharmacy'] ? $this->pharmacyId : null);
        $checks = array_map(function ($row) {
            return $this->formattingService->formatCheck($row, $this->checkCols, $this->questionnaireService);
        }, $rows);

        return $this->enrichChecksWithChecklistData($checks);
    }

    private function clean(?string $value): string
    {
        return call_user_func($this->cleanCallback, $value);
    }

    private function now(): string
    {
        return call_user_func($this->nowCallback);
    }

    private function verifyTherapyOwnership(int $therapyId): void
    {
        call_user_func($this->verifyTherapyOwnershipCallback, $therapyId);
    }

    private function normalizeAnswersPayload($rawPayload): array
    {
        $decoded = is_array($rawPayload) ? $rawPayload : json_decode((string)$rawPayload, true);
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

    private function flattenAnswersForLegacy(array $answersPayload): array
    {
        $flattened = [];
        foreach ($answersPayload as $question => $answer) {
            $label = ($answer['value'] ?? null) === 'yes' ? 'Sì' : (($answer['value'] ?? null) === 'no' ? 'No' : '');
            $note = trim((string)($answer['note'] ?? ''));
            $flattened[$question] = trim($label . ($note !== '' ? ' - ' . $note : ''));
        }

        return $flattened;
    }

    private function enrichChecksWithChecklistData(array $checks): array
    {
        if (empty($checks)) {
            return [];
        }

        $questionsByTherapy = [];
        foreach ($checks as $check) {
            if (($check['type'] ?? 'execution') === 'checklist' && !empty($check['questions'])) {
                $questionsByTherapy[$check['therapy_id']] = $check['questions'];
            }
        }

        $answersByCheck = $this->checkAnswerService->getAnswersForChecks($checks);

        foreach ($checks as &$check) {
            $therapyQuestions = $questionsByTherapy[$check['therapy_id']] ?? ($check['questions'] ?? []);
            $check['checklist_questions'] = $therapyQuestions;

            if (($check['type'] ?? 'execution') === 'checklist') {
                continue;
            }

            $legacyAnswers = $answersByCheck[$check['id']] ?? [];
            $check['answers'] = $this->buildAnswersList($check, $therapyQuestions, $legacyAnswers);
        }

        return $checks;
    }

    private function buildAnswersList(array $check, array $therapyQuestions, array $legacyAnswers = []): array
    {
        $questionsMap = [];
        foreach ($therapyQuestions as $question) {
            if (!empty($question['key'])) {
                $questionsMap[$question['key']] = $question['text'] ?? $question['key'];
            }
        }

        $payloadAnswers = is_array($check['answers_payload'] ?? null) ? $check['answers_payload'] : [];
        if (!empty($payloadAnswers)) {
            $list = [];
            foreach ($payloadAnswers as $key => $answer) {
                $value = is_array($answer) ? ($answer['value'] ?? null) : $answer;
                $note = is_array($answer) ? trim((string)($answer['note'] ?? '')) : '';
                $valueLabel = $value === 'yes' ? 'Sì' : ($value === 'no' ? 'No' : 'Non compilato');
                $list[] = [
                    'question' => $questionsMap[$key] ?? $key,
                    'value' => in_array($value, ['yes', 'no'], true) ? $value : null,
                    'value_label' => $valueLabel,
                    'note' => $note,
                    'created_at' => $check['scheduled_at'] ?? null,
                ];
            }

            return $list;
        }

        if (!empty($legacyAnswers)) {
            return array_map(function ($answer) use ($questionsMap, $check) {
                return [
                    'question' => $questionsMap[$answer['question']] ?? ($answer['question'] ?? ''),
                    'value' => null,
                    'value_label' => $answer['answer'] ?? 'Non compilato',
                    'note' => '',
                    'created_at' => $answer['created_at'] ?? ($check['scheduled_at'] ?? null),
                ];
            }, $legacyAnswers);
        }

        return [];
    }
}
