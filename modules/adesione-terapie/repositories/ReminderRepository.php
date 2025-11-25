<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class ReminderRepository
{
    private string $remindersTable;
    private array $reminderCols;
    private string $therapiesTable;
    private array $therapyCols;

    public function __construct(string $remindersTable, array $reminderCols, string $therapiesTable, array $therapyCols)
    {
        $this->remindersTable = $remindersTable;
        $this->reminderCols = $reminderCols;
        $this->therapiesTable = $therapiesTable;
        $this->therapyCols = $therapyCols;
    }

    public function filterData(array $data): array
    {
        return AdesioneTableResolver::filterData($this->remindersTable, $data);
    }

    public function findExistingReminder(int $therapyId, string $scheduledAt, ?string $title, array $reminderCols): ?int
    {
        $lookupParams = [$therapyId, $scheduledAt];
        $where = "{$reminderCols['therapy']} = ? AND {$reminderCols['scheduled_at']} = ?";

        if ($reminderCols['title'] && $title !== null) {
            $where .= " AND {$reminderCols['title']} = ?";
            $lookupParams[] = $title;
        }

        $existingReminderId = (int)db()->fetchValue(
            "SELECT {$reminderCols['id']} FROM {$this->remindersTable} WHERE {$where} ORDER BY {$reminderCols['id']} DESC LIMIT 1",
            $lookupParams
        );

        return $existingReminderId ?: null;
    }

    public function update(int $reminderId, array $data, string $idColumn): void
    {
        db()->update($this->remindersTable, $data, "{$idColumn} = ?", [$reminderId]);
    }

    public function insert(array $data): int
    {
        return (int)db()->insert($this->remindersTable, $data);
    }

    public function find(int $reminderId, array $reminderCols, array $therapyCols, ?int $pharmacyId): ?array
    {
        $sql = "SELECT r.* FROM `{$this->remindersTable}` r ";
        $params = [$reminderId];

        if ($reminderCols['therapy'] && $therapyCols['pharmacy'] && $pharmacyId !== null) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON r.`{$reminderCols['therapy']}` = t.`{$therapyCols['id']}` ";
            $sql .= "WHERE r.`{$reminderCols['id']}` = ? AND t.`{$therapyCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        } else {
            $sql .= "WHERE r.`{$reminderCols['id']}` = ?";
        }

        return db_fetch_one($sql, $params) ?: null;
    }

    public function list(?int $therapyId, array $reminderCols, array $therapyCols, ?int $pharmacyId): array
    {
        $sql = "SELECT r.* FROM `{$this->remindersTable}` r ";
        $params = [];
        $conditions = [];

        if ($reminderCols['therapy'] && $therapyCols['pharmacy'] && $pharmacyId !== null) {
            $sql .= "JOIN `{$this->therapiesTable}` t ON r.`{$reminderCols['therapy']}` = t.`{$therapyCols['id']}` ";
            $conditions[] = "t.`{$therapyCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }

        if ($therapyId && $reminderCols['therapy']) {
            $conditions[] = "r.`{$reminderCols['therapy']}` = ?";
            $params[] = $therapyId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($reminderCols['scheduled_at']) {
            $sql .= " ORDER BY r.`{$reminderCols['scheduled_at']}` ASC";
        }

        return db_fetch_all($sql, $params);
    }
}
