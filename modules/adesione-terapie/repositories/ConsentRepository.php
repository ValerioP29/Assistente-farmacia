<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class ConsentRepository
{
    private string $consentsTable;
    private array $consentCols;

    public function __construct(string $consentsTable, array $consentCols)
    {
        $this->consentsTable = $consentsTable;
        $this->consentCols = $consentCols;
    }

    public function findLatestByTherapy(int $therapyId): ?array
    {
        if (!$this->consentCols['therapy']) {
            return null;
        }

        return db_fetch_one(
            "SELECT * FROM `{$this->consentsTable}` WHERE `{$this->consentCols['therapy']}` = ? ORDER BY `{$this->consentCols['id']}` DESC LIMIT 1",
            [$therapyId]
        ) ?: null;
    }

    public function insert(array $data): void
    {
        db()->insert($this->consentsTable, $data);
    }

    public function update(int $id, array $data, ?string $idColumn): void
    {
        if (!$idColumn) {
            return;
        }

        db()->update($this->consentsTable, $data, "`{$idColumn}` = ?", [$id]);
    }

    public function filterData(array $data): array
    {
        return AdesioneTableResolver::filterData($this->consentsTable, $data);
    }
}
