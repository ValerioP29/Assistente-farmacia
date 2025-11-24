<?php

namespace Modules\AdesioneTerapie\Services;

use AdesioneTableResolver;
use Throwable;
use Modules\AdesioneTerapie\Repositories\QuestionnaireRepository;

class QuestionnaireService
{
    private QuestionnaireRepository $questionnaireRepository;
    private array $questionnaireCols;
    private $cleanCallback;
    private $nowCallback;

    public function __construct(QuestionnaireRepository $questionnaireRepository, array $questionnaireCols, callable $cleanCallback, callable $nowCallback)
    {
        $this->questionnaireRepository = $questionnaireRepository;
        $this->questionnaireCols = $questionnaireCols;
        $this->cleanCallback = $cleanCallback;
        $this->nowCallback = $nowCallback;
    }

    public function storeQuestionnaire(int $therapyId, array $answers): void
    {
        if (!is_array($answers) || empty($answers)) {
            return;
        }

        if (!$this->questionnaireCols['therapy'] || !$this->questionnaireCols['question'] || !$this->questionnaireCols['answer']) {
            error_log('[AdesioneTerapie] Colonne questionario non risolte, salvataggio saltato');
            return;
        }

        try {
            AdesioneTableResolver::columns($this->questionnaireRepository->getTable());
        } catch (Throwable $e) {
            error_log('[AdesioneTerapie] Tabella questionario non disponibile: ' . $e->getMessage());
            return;
        }

        $this->questionnaireRepository->deleteByTherapy($therapyId);

        foreach ($answers as $step => $stepAnswers) {
            if (!is_array($stepAnswers)) {
                continue;
            }

            $stepKey = $this->clean((string)$step);
            foreach ($stepAnswers as $questionKey => $answer) {
                $cleanAnswer = $this->clean($answer ?? '');
                if ($cleanAnswer === '') {
                    continue;
                }

                $questionValue = $stepKey . '|' . $this->clean((string)$questionKey);
                $row = [
                    $this->questionnaireCols['therapy'] => $therapyId,
                    $this->questionnaireCols['question'] => $questionValue,
                    $this->questionnaireCols['answer'] => $cleanAnswer,
                ];

                if ($this->questionnaireCols['created_at']) {
                    $row[$this->questionnaireCols['created_at']] = $this->now();
                }
                if ($this->questionnaireCols['updated_at']) {
                    $row[$this->questionnaireCols['updated_at']] = $this->now();
                }

                $this->questionnaireRepository->insert(
                    $this->questionnaireRepository->filterData($row)
                );
            }
        }
    }

    public function getQuestionnaire(int $therapyId): array
    {
        if (!$this->questionnaireCols['therapy']) {
            return [];
        }
        $rows = $this->questionnaireRepository->listByTherapy($therapyId);

        if (!$rows) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $questionRaw = $row[$this->questionnaireCols['question']] ?? '';
            $answerValue = $row[$this->questionnaireCols['answer']] ?? '';
            $step = '1';
            $questionKey = $questionRaw;

            if (str_contains($questionRaw, '|')) {
                [$step, $questionKey] = explode('|', $questionRaw, 2);
            }

            if (!isset($result[$step])) {
                $result[$step] = [];
            }
            $result[$step][$questionKey] = $answerValue;
        }

        return $result;
    }

    public function normalizeChecklistQuestions(array $questions): array
    {
        $normalized = [];
        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                continue;
            }
            $text = trim((string)($question['text'] ?? $question['label'] ?? ''));
            if ($text === '') {
                continue;
            }
            $key = trim((string)($question['key'] ?? ''));
            if ($key === '') {
                $key = $this->slugifyKey($text, $index);
            }
            $type = strtolower((string)($question['type'] ?? 'text'));
            if (!in_array($type, ['text', 'boolean', 'number'], true)) {
                $type = 'text';
            }

            $normalized[] = [
                'key' => $key,
                'text' => $text,
                'type' => $type,
            ];
        }

        return $normalized;
    }

    private function clean(?string $value): string
    {
        return call_user_func($this->cleanCallback, $value);
    }

    private function now(): string
    {
        return call_user_func($this->nowCallback);
    }

    private function slugifyKey(string $value, int $fallbackIndex = 0): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';
        $value = trim((string)$value, '-');
        if ($value === '') {
            $value = 'q' . ($fallbackIndex + 1);
        }
        return $value;
    }
}
