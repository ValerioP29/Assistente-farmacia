<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class AssistantRepository
{
    private string $assistantsTable;
    private array $assistantCols;
    private string $assistantPivotTable;
    private array $assistantPivotCols;

    public function __construct(string $assistantsTable, array $assistantCols, string $assistantPivotTable, array $assistantPivotCols)
    {
        $this->assistantsTable = $assistantsTable;
        $this->assistantCols = $assistantCols;
        $this->assistantPivotTable = $assistantPivotTable;
        $this->assistantPivotCols = $assistantPivotCols;
    }

    public function deleteByTherapy(int $therapyId): void
    {
        if (!$this->assistantPivotCols['therapy']) {
            return;
        }

        db_query(
            "DELETE FROM `{$this->assistantPivotTable}` WHERE `{$this->assistantPivotCols['therapy']}` = ?",
            [$therapyId]
        );
    }

    public function insertAssistant(array $data): int
    {
        db()->insert($this->assistantsTable, AdesioneTableResolver::filterData($this->assistantsTable, $data));
        return (int)db()->lastInsertId();
    }

    public function insertPivot(array $data): void
    {
        db()->insert($this->assistantPivotTable, AdesioneTableResolver::filterData($this->assistantPivotTable, $data));
    }

    public function upsertPivot(int $therapyId, int $assistantId, array $data): void
    {
        if (!$this->assistantPivotCols['therapy'] || !$this->assistantPivotCols['assistant']) {
            return;
        }

        $therapyColumn = $this->assistantPivotCols['therapy'];
        $assistantColumn = $this->assistantPivotCols['assistant'];
        $idColumn = $this->assistantPivotCols['id'];

        $existing = db_fetch_one(
            "SELECT `{$idColumn}` FROM `{$this->assistantPivotTable}` WHERE `{$therapyColumn}` = ? AND `{$assistantColumn}` = ? LIMIT 1",
            [$therapyId, $assistantId]
        );

        $payload = $data;
        $payload[$therapyColumn] = $therapyId;
        $payload[$assistantColumn] = $assistantId;

        $filtered = AdesioneTableResolver::filterData($this->assistantPivotTable, $payload);

        if ($existing) {
            unset($filtered[$therapyColumn], $filtered[$assistantColumn]);
            db()->update(
                $this->assistantPivotTable,
                $filtered,
                "`{$therapyColumn}` = ? AND `{$assistantColumn}` = ?",
                [$therapyId, $assistantId]
            );
            return;
        }

        db()->insert($this->assistantPivotTable, $filtered);
    }

    public function listByTherapy(int $therapyId, ?int $pharmacyId): array
    {
        if (!$this->assistantPivotCols['therapy'] || !$this->assistantPivotCols['assistant']) {
            return [];
        }

        $sql = "SELECT a.*, ta.`{$this->assistantPivotCols['role']}` AS pivot_role
                FROM `{$this->assistantPivotTable}` ta
                JOIN `{$this->assistantsTable}` a ON ta.`{$this->assistantPivotCols['assistant']}` = a.`{$this->assistantCols['id']}`
                WHERE ta.`{$this->assistantPivotCols['therapy']}` = ?";
        $params = [$therapyId];
        if ($this->assistantCols['pharmacy'] && $pharmacyId !== null) {
            $sql .= " AND a.`{$this->assistantCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }
        if ($this->assistantCols['created_at']) {
            $sql .= " ORDER BY a.`{$this->assistantCols['created_at']}` DESC";
        }

        return db_fetch_all($sql, $params);
    }
}
