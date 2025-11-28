<?php

namespace Modules\AdesioneTerapie\Services;

class ConditionSurveyService
{
    private string $table = 'jta_therapy_condition_surveys';

    public function save($therapyId, string $condition, string $level, array $answers): array
    {
        $data = [
            'therapy_id' => $therapyId,
            'condition' => $this->cleanString($condition),
            'level' => $this->cleanString($level),
            'answers' => $this->encodeJson($answers),
            'compiled_at' => date('Y-m-d H:i:s'),
        ];

        db()->insert($this->table, $data);

        return $this->latest($therapyId, $condition);
    }

    public function latest($therapyId, string $condition): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE therapy_id = ? AND condition = ? ORDER BY compiled_at DESC, id DESC LIMIT 1";
        $row = db_fetch_one($sql, [$therapyId, $condition]);
        return $row ? $this->format($row) : [];
    }

    public function all($therapyId, string $condition): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE therapy_id = ? AND condition = ? ORDER BY compiled_at DESC, id DESC";
        $rows = db_fetch_all($sql, [$therapyId, $condition]);
        return array_map(fn($row) => $this->format($row), $rows ?: []);
    }

    private function format(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'therapy_id' => (int)($row['therapy_id'] ?? 0),
            'condition' => $row['condition'] ?? '',
            'level' => $row['level'] ?? '',
            'answers' => $this->decodeJson($row['answers'] ?? null),
            'compiled_at' => $row['compiled_at'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function encodeJson($value): ?string
    {
        if ($value === null) {
            return null;
        }
        return json_encode($value);
    }

    private function decodeJson($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function cleanString(?string $value): string
    {
        return trim((string)($value ?? ''));
    }
}
