<?php

namespace Modules\AdesioneTerapie\Services;

use Modules\AdesioneTerapie\Repositories\CheckAnswerRepository;

class CheckAnswerService
{
    private CheckAnswerRepository $checkAnswerRepository;
    private array $checkAnswerCols;
    private $nowCallback;

    public function __construct(CheckAnswerRepository $checkAnswerRepository, array $checkAnswerCols, callable $nowCallback)
    {
        $this->checkAnswerRepository = $checkAnswerRepository;
        $this->checkAnswerCols = $checkAnswerCols;
        $this->nowCallback = $nowCallback;
    }

    public function normalizeCheckAnswers($answers): array
    {
        if (!is_array($answers)) {
            return [];
        }
        $normalized = [];
        foreach ($answers as $key => $answer) {
            $questionKey = is_string($key) ? trim($key) : (is_array($answer) ? trim((string)($answer['question'] ?? '')) : '');
            if ($questionKey === '') {
                continue;
            }
            if (is_array($answer)) {
                $value = $answer['answer'] ?? ($answer['value'] ?? '');
            } else {
                $value = $answer;
            }
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $normalized[$questionKey] = trim((string)$value);
        }
        return $normalized;
    }

    public function storeCheckAnswers(int $checkId, array $answers): void
    {
        if (!$checkId || !$this->checkAnswerCols['check'] || !$this->checkAnswerCols['question'] || !$this->checkAnswerCols['answer']) {
            return;
        }

        $this->checkAnswerRepository->deleteByCheck($checkId);

        foreach ($answers as $question => $answer) {
            if ($answer === '') {
                continue;
            }
            $payload = [
                $this->checkAnswerCols['check'] => $checkId,
                $this->checkAnswerCols['question'] => $question,
                $this->checkAnswerCols['answer'] => $answer,
            ];
            if ($this->checkAnswerCols['created_at']) {
                $payload[$this->checkAnswerCols['created_at']] = $this->now();
            }
            $filtered = $this->checkAnswerRepository->filterData($payload);
            $this->checkAnswerRepository->insert($filtered);
        }
    }

    public function getAnswersForChecks(array $checks): array
    {
        if (empty($checks) || !$this->checkAnswerCols['check']) {
            return [];
        }
        $ids = array_unique(array_filter(array_map(static function ($check) {
            return (int)($check['id'] ?? 0);
        }, $checks)));
        if (empty($ids)) {
            return [];
        }

        $rows = $this->checkAnswerRepository->listByCheckIds($ids);

        $grouped = [];
        foreach ($rows as $row) {
            $checkId = (int)($row[$this->checkAnswerCols['check']] ?? 0);
            $grouped[$checkId][] = [
                'id' => $this->checkAnswerCols['id'] ? (int)($row[$this->checkAnswerCols['id']] ?? 0) : 0,
                'question' => $row[$this->checkAnswerCols['question']] ?? '',
                'answer' => $row[$this->checkAnswerCols['answer']] ?? '',
                'created_at' => $this->checkAnswerCols['created_at'] ? ($row[$this->checkAnswerCols['created_at']] ?? null) : null,
            ];
        }

        return $grouped;
    }

    private function now(): string
    {
        return call_user_func($this->nowCallback);
    }
}
