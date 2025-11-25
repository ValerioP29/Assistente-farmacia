<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class CheckRepository
{
    private string $checksTable;
    private array $checkCols;
    private string $therapiesTable;
    private array $therapyCols;

    public function __construct(string $checksTable, array $checkCols, string $therapiesTable, array $therapyCols)
    {
        $this->checksTable = $checksTable;
        $this->checkCols = $checkCols;
        $this->therapiesTable = $therapiesTable;
        $this->therapyCols = $therapyCols;
    }

    public function filterData(array $data): array
    {
        return AdesioneTableResolver::filterData($this->checksTable, $data);
    }

    public function insert(array $data): int
    {
        return (int)db()->insert($this->checksTable, $data);
    }

    public function update(int $checkId, array $data): void
    {
        db()->update($this->checksTable, $data, "{$this->checkCols['id']} = ?", [$checkId]);
    }

    public function find(int $checkId, ?int $pharmacyId): ?array
    {
        $sql = "SELECT c.* FROM `{$this->checksTable}` c ";
        $params = [$checkId];

        if ($this->checkCols['therapy'] && $this->therapyCols['pharmacy'] && $pharmacyId !== null) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON c.`{$this->checkCols['therapy']}` = t.`{$this->therapyCols['id']}` ";
            $sql .= "WHERE c.`{$this->checkCols['id']}` = ? AND t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        } else {
            $sql .= "WHERE c.`{$this->checkCols['id']}` = ?";
        }

        return db_fetch_one($sql, $params) ?: null;
    }

    public function list(?int $therapyId, ?int $pharmacyId): array
    {
        $sql = "SELECT c.* FROM `{$this->checksTable}` c ";
        $params = [];
        $conditions = [];

        if ($this->checkCols['therapy'] && $this->therapyCols['pharmacy'] && $pharmacyId !== null) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON c.`{$this->checkCols['therapy']}` = t.`{$this->therapyCols['id']}` ";
            $conditions[] = "t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }

        if ($therapyId && $this->checkCols['therapy']) {
            $conditions[] = "c.`{$this->checkCols['therapy']}` = ?";
            $params[] = $therapyId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($this->checkCols['scheduled_at']) {
            $sql .= " ORDER BY c.`{$this->checkCols['scheduled_at']}` DESC";
        }

        return db_fetch_all($sql, $params);
    }

    public function listByTherapyOrdered(int $therapyId): array
    {
        if (!$this->checkCols['therapy']) {
            return [];
        }

        return db_fetch_all(
            "SELECT * FROM `{$this->checksTable}` WHERE `{$this->checkCols['therapy']}` = ? ORDER BY `{$this->checkCols['id']}` DESC",
            [$therapyId]
        );
    }

    public function findByTherapyAndScheduledAt(int $therapyId, string $scheduledAt): ?int
    {
        if (!$this->checkCols['therapy'] || !$this->checkCols['scheduled_at']) {
            return null;
        }

        $existingId = db()->fetchValue(
            "SELECT {$this->checkCols['id']} FROM {$this->checksTable} WHERE {$this->checkCols['therapy']} = ? AND {$this->checkCols['scheduled_at']} = ? LIMIT 1",
            [$therapyId, $scheduledAt]
        );

        return $existingId ? (int)$existingId : null;
    }
}
