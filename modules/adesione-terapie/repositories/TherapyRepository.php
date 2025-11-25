<?php

namespace Modules\AdesioneTerapie\Repositories;

use AdesioneTableResolver;

class TherapyRepository
{
    private string $therapiesTable;
    private array $therapyCols;
    private string $patientsTable;
    private array $patientCols;

    public function __construct(string $therapiesTable, array $therapyCols, string $patientsTable, array $patientCols)
    {
        $this->therapiesTable = $therapiesTable;
        $this->therapyCols = $therapyCols;
        $this->patientsTable = $patientsTable;
        $this->patientCols = $patientCols;
    }

    public function filterData(array $data): array
    {
        return AdesioneTableResolver::filterData($this->therapiesTable, $data);
    }

    public function insert(array $data): int
    {
        return (int)db()->insert($this->therapiesTable, $data);
    }

    public function update(int $therapyId, array $data, string $idColumn): void
    {
        db()->update($this->therapiesTable, $data, "{$idColumn} = ?", [$therapyId]);
    }

    public function findWithPatient(int $therapyId, ?int $pharmacyId): ?array
    {
        $selectFields = ['t.*'];
        if ($this->patientCols['first_name']) {
            $selectFields[] = "p.`{$this->patientCols['first_name']}` AS patient_first_name";
        } else {
            $selectFields[] = "'' AS patient_first_name";
        }
        if ($this->patientCols['last_name']) {
            $selectFields[] = "p.`{$this->patientCols['last_name']}` AS patient_last_name";
        } else {
            $selectFields[] = "'' AS patient_last_name";
        }
        if ($this->patientCols['id']) {
            $selectFields[] = "p.`{$this->patientCols['id']}` AS patient_id";
        }
        if ($this->patientCols['phone']) {
            $selectFields[] = "p.`{$this->patientCols['phone']}` AS patient_phone";
        }
        if ($this->patientCols['email']) {
            $selectFields[] = "p.`{$this->patientCols['email']}` AS patient_email";
        }

        $sql = 'SELECT ' . implode(', ', $selectFields) . " FROM `{$this->therapiesTable}` t ";
        if ($this->therapyCols['patient'] && $this->patientCols['id']) {
            $sql .= "LEFT JOIN `{$this->patientsTable}` p ON t.`{$this->therapyCols['patient']}` = p.`{$this->patientCols['id']}` ";
        }
        $sql .= "WHERE t.`{$this->therapyCols['id']}` = ?";
        $params = [$therapyId];

        if ($this->therapyCols['pharmacy'] && $pharmacyId !== null) {
            $sql .= " AND t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }

        return db_fetch_one($sql, $params) ?: null;
    }

    public function listWithPatient(?int $pharmacyId): array
    {
        $selectFields = ['t.*'];
        if ($this->patientCols['first_name']) {
            $selectFields[] = "p.`{$this->patientCols['first_name']}` AS patient_first_name";
        } else {
            $selectFields[] = "'' AS patient_first_name";
        }
        if ($this->patientCols['last_name']) {
            $selectFields[] = "p.`{$this->patientCols['last_name']}` AS patient_last_name";
        } else {
            $selectFields[] = "'' AS patient_last_name";
        }
        if ($this->patientCols['id']) {
            $selectFields[] = "p.`{$this->patientCols['id']}` AS patient_id";
        }
        if ($this->patientCols['phone']) {
            $selectFields[] = "p.`{$this->patientCols['phone']}` AS patient_phone";
        }
        if ($this->patientCols['email']) {
            $selectFields[] = "p.`{$this->patientCols['email']}` AS patient_email";
        }

        $sql = 'SELECT ' . implode(', ', $selectFields) . " FROM `{$this->therapiesTable}` t ";
        if ($this->therapyCols['patient'] && $this->patientCols['id']) {
            $sql .= "LEFT JOIN `{$this->patientsTable}` p ON t.`{$this->therapyCols['patient']}` = p.`{$this->patientCols['id']}`";
        }
        $params = [];
        $conditions = [];
        if ($this->therapyCols['pharmacy'] && $pharmacyId !== null) {
            $conditions[] = "t.`{$this->therapyCols['pharmacy']}` = ?";
            $params[] = $pharmacyId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if ($this->therapyCols['start_date']) {
            $sql .= " ORDER BY t.`{$this->therapyCols['start_date']}` DESC";
        } else {
            $sql .= " ORDER BY t.`{$this->therapyCols['id']}` DESC";
        }

        return db_fetch_all($sql, $params);
    }

    public function existsForPharmacy(int $therapyId, int $pharmacyId): bool
    {
        $therapy = db_fetch_one(
            "SELECT `{$this->therapyCols['id']}` FROM `{$this->therapiesTable}` WHERE `{$this->therapyCols['id']}` = ? AND `{$this->therapyCols['pharmacy']}` = ?",
            [$therapyId, $pharmacyId]
        );

        return (bool)$therapy;
    }
}
