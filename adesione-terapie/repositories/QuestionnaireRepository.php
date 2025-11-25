<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class QuestionnaireRepository
{
    private string $questionnairesTable;
    private array $questionnaireCols;

    public function __construct(string $questionnairesTable, array $questionnaireCols)
    {
        $this->questionnairesTable = $questionnairesTable;
        $this->questionnaireCols = $questionnaireCols;
    }

    public function deleteByTherapy(int $therapyId): void
    {
        if (!$this->questionnaireCols['therapy']) {
            return;
        }

        db()->delete($this->questionnairesTable, "{$this->questionnaireCols['therapy']} = ?", [$therapyId]);
    }

    public function insert(array $data): void
    {
        db()->insert($this->questionnairesTable, $data);
    }

    public function listByTherapy(int $therapyId): array
    {
        if (!$this->questionnaireCols['therapy']) {
            return [];
        }

        return db_fetch_all(
            "SELECT * FROM `{$this->questionnairesTable}` WHERE `{$this->questionnaireCols['therapy']}` = ? ORDER BY `{$this->questionnaireCols['id']}` ASC",
            [$therapyId]
        );
    }

    public function filterData(array $data): array
    {
        return AdesioneTableResolver::filterData($this->questionnairesTable, $data);
    }

    public function getTable(): string
    {
        return $this->questionnairesTable;
    }
}
