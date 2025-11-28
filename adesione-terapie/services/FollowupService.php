<?php

namespace Modules\AdesioneTerapie\Services;

class FollowupService
{
    private string $table = 'jta_therapy_followups';

    public function register($therapyId, array $payload): array
    {
        $data = [
            'therapy_id' => $therapyId,
            'risk_score' => isset($payload['risk_score']) ? (int)$payload['risk_score'] : null,
            'pharmacist_notes' => $this->cleanString($payload['pharmacist_notes'] ?? ''),
            'education_notes' => $this->cleanString($payload['education_notes'] ?? ''),
            'snapshot' => $this->encodeJson($payload['snapshot'] ?? null),
            'follow_up_date' => $payload['follow_up_date'] ?? null,
            'created_at' => $payload['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        db()->insert($this->table, $data);

        return $this->latestByDate($therapyId);
    }

    public function list($therapyId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE therapy_id = ? ORDER BY created_at DESC, id DESC";
        $rows = db_fetch_all($sql, [$therapyId]);
        return array_map(fn($row) => $this->format($row), $rows ?: []);
    }

    private function latestByDate($therapyId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE therapy_id = ? ORDER BY created_at DESC, id DESC LIMIT 1";
        $row = db_fetch_one($sql, [$therapyId]);
        return $row ? $this->format($row) : [];
    }

    private function format(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'therapy_id' => (int)($row['therapy_id'] ?? 0),
            'risk_score' => isset($row['risk_score']) ? (int)$row['risk_score'] : null,
            'pharmacist_notes' => $row['pharmacist_notes'] ?? '',
            'education_notes' => $row['education_notes'] ?? '',
            'snapshot' => $this->decodeJson($row['snapshot'] ?? null),
            'follow_up_date' => $row['follow_up_date'] ?? null,
            'created_at' => $row['created_at'] ?? null,
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
