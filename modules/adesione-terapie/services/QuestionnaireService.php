<?php

namespace Modules\AdesioneTerapie\Services;

use AdesioneTableResolver;
use Throwable;

class QuestionnaireService
{
    private string $questionnairesTable;
    private array $questionnaireCols;
    private $cleanCallback;
    private $nowCallback;

    public function __construct(string $questionnairesTable, array $questionnaireCols, callable $cleanCallback, callable $nowCallback)
    {
        $this->questionnairesTable = $questionnairesTable;
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
            AdesioneTableResolver::columns($this->questionnairesTable);
        } catch (Throwable $e) {
            error_log('[AdesioneTerapie] Tabella questionario non disponibile: ' . $e->getMessage());
            return;
        }

        db()->delete($this->questionnairesTable, "{$this->questionnaireCols['therapy']} = ?", [$therapyId]);

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

                db()->insert($this->questionnairesTable, AdesioneTableResolver::filterData($this->questionnairesTable, $row));
            }
        }
    }

    public function getQuestionnaire(int $therapyId): array
    {
        if (!$this->questionnaireCols['therapy']) {
            return [];
        }
        $rows = db_fetch_all(
            "SELECT * FROM `{$this->questionnairesTable}` WHERE `{$this->questionnaireCols['therapy']}` = ? ORDER BY `{$this->questionnaireCols['id']}` ASC",
            [$therapyId]
        );

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

    private function clean(?string $value): string
    {
        return call_user_func($this->cleanCallback, $value);
    }

    private function now(): string
    {
        return call_user_func($this->nowCallback);
    }
}
