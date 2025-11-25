<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class ReportRepository
{
    private string $reportsTable;
    private array $reportCols;

    public function __construct(string $reportsTable, array $reportCols)
    {
        $this->reportsTable = $reportsTable;
        $this->reportCols = $reportCols;
    }

    public function filterData(array $data): array
    {
        return AdesioneTableResolver::filterData($this->reportsTable, $data);
    }

    public function insert(array $data): int
    {
        return (int)db()->insert($this->reportsTable, $data);
    }

    public function list(?int $therapyId, ?int $pharmacyId): array
    {
        $sql = "SELECT * FROM `{$this->reportsTable}`";
        $params = [];
        $conditions = [];

        if ($this->reportCols['pharmacy'] && $pharmacyId !== null) {
            $conditions[] = "`{$this->reportCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }

        if ($therapyId && $this->reportCols['therapy']) {
            $conditions[] = "`{$this->reportCols['therapy']}` = ?";
            $params[] = $therapyId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($this->reportCols['created_at']) {
            $sql .= " ORDER BY `{$this->reportCols['created_at']}` DESC";
        }

        return db_fetch_all($sql, $params);
    }

    public function find(int $reportId, ?int $pharmacyId): ?array
    {
        $sql = "SELECT * FROM `{$this->reportsTable}` WHERE `{$this->reportCols['id']}` = ?";
        $params = [$reportId];

        if ($this->reportCols['pharmacy'] && $pharmacyId !== null) {
            $sql .= " AND `{$this->reportCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }

        return db_fetch_one($sql, $params) ?: null;
    }
}
