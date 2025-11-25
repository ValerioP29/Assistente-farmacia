<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class CheckAnswerRepository
{
    private string $checkAnswersTable;
    private array $checkAnswerCols;

    public function __construct(string $checkAnswersTable, array $checkAnswerCols)
    {
        $this->checkAnswersTable = $checkAnswersTable;
        $this->checkAnswerCols = $checkAnswerCols;
    }

    public function filterData(array $data): array
    {
        return AdesioneTableResolver::filterData($this->checkAnswersTable, $data);
    }

    public function deleteByCheck(int $checkId): void
    {
        db()->delete($this->checkAnswersTable, "`{$this->checkAnswerCols['check']}` = ?", [$checkId]);
    }

    public function insert(array $data): void
    {
        db()->insert($this->checkAnswersTable, $data);
    }

    public function listByCheckIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return db_fetch_all(
            "SELECT * FROM `{$this->checkAnswersTable}` WHERE `{$this->checkAnswerCols['check']}` IN ({$placeholders}) ORDER BY `{$this->checkAnswerCols['id']}` ASC",
            $ids
        );
    }
}
