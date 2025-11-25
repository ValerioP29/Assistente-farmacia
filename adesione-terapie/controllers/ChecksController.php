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

        $notesPayload = [
            'type' => 'execution',
            'assessment' => $this->clean($payload['assessment'] ?? ''),
            'notes' => $this->clean($payload['notes'] ?? ''),
            'actions' => $this->clean($payload['actions'] ?? ''),
        ];
        if (empty($notesPayload['assessment'])) {
            throw new RuntimeException('Compila almeno la valutazione del check.');
        }
        if ($this->checkCols['notes']) {
            $data[$this->checkCols['notes']] = json_encode($notesPayload, JSON_UNESCAPED_UNICODE);
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

        $answersRaw = $payload['answers_payload'] ?? '[]';
        $decodedAnswers = is_array($payload['answers'] ?? null) ? ($payload['answers'] ?? []) : json_decode($answersRaw, true);
        $answers = $this->checkAnswerService->normalizeCheckAnswers($decodedAnswers);
        if (!empty($notesPayload['assessment'])) {
            $answers['assessment'] = $notesPayload['assessment'];
        }
        if (!empty($notesPayload['notes'])) {
            $answers['notes'] = $notesPayload['notes'];
        }
        if (!empty($notesPayload['actions'])) {
            $answers['actions'] = $notesPayload['actions'];
        }

        if (!empty($answers)) {
            $this->checkAnswerService->storeCheckAnswers($checkId, $answers);
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
        $answers = $this->checkAnswerService->getAnswersForChecks([$formatted]);
        if (($formatted['type'] ?? 'execution') !== 'checklist') {
            $formatted['answers'] = $answers[$formatted['id']] ?? [];
        }

        return $formatted;
    }

    public function listChecks(?int $therapyId = null): array
    {
        $rows = $this->checkRepository->list($therapyId, $this->therapyCols['pharmacy'] ? $this->pharmacyId : null);
        $checks = array_map(function ($row) {
            return $this->formattingService->formatCheck($row, $this->checkCols, $this->questionnaireService);
        }, $rows);

        $answersByCheck = $this->checkAnswerService->getAnswersForChecks($checks);

        foreach ($checks as &$check) {
            if (($check['type'] ?? 'execution') !== 'checklist') {
                $check['answers'] = $answersByCheck[$check['id']] ?? [];
            }
        }

        return $checks;
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
}
