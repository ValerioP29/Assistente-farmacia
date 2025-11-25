<?php

namespace Modules\AdesioneTerapie\Repositories;

class PatientRepository
{
    private string $patientsTable;
    private array $patientCols;

    public function __construct(string $patientsTable, array $patientCols)
    {
        $this->patientsTable = $patientsTable;
        $this->patientCols = $patientCols;
    }

    public function find(int $patientId, array $patientCols, ?int $pharmacyId): ?array
    {
        $columns = \AdesioneTableResolver::columns($this->patientsTable);
        $columnList = implode(', ', array_map(static function ($column) {
            return "`$column`";
        }, $columns));

        $where = "{$patientCols['id']} = ?";
        $params = [$patientId];
        if ($pharmacyId && $patientCols['pharmacy']) {
            $where .= " AND {$patientCols['pharmacy']} = ?";
            $params[] = $pharmacyId;
        }

        return db_fetch_one("SELECT {$columnList} FROM `{$this->patientsTable}` WHERE {$where}", $params);
    }

    public function list(array $patientCols, ?int $pharmacyId): array
    {
        $columns = \AdesioneTableResolver::columns($this->patientsTable);
        $columnList = implode(', ', array_map(static function ($column) {
            return "p.`$column`";
        }, $columns));

        $sql = "SELECT {$columnList} FROM `{$this->patientsTable}` p";
        $params = [];

        if ($pharmacyId && $patientCols['pharmacy']) {
            $sql .= " WHERE p.`{$patientCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }

        if ($patientCols['last_name']) {
            $sql .= " ORDER BY p.`{$patientCols['last_name']}`, p.`{$patientCols['first_name']}`";
        }

        return db_fetch_all($sql, $params);
    }

    public function insert(array $data): int
    {
        return (int)db()->insert($this->patientsTable, $data);
    }

    public function update(int $patientId, array $data, string $idColumn): void
    {
        db()->update($this->patientsTable, $data, "{$idColumn} = ?", [$patientId]);
    }

    public function filterData(array $data): array
    {
        return \AdesioneTableResolver::filterData($this->patientsTable, $data);
    }
}
