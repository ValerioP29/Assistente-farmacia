<?php

namespace Modules\AdesioneTerapie\Services;

class ChronicCareService
{
    private string $table = 'jta_therapy_chronic_care';

    public function saveInitial($therapyId, array $payload): array
    {
        $existing = $this->fetchRow($therapyId);
        $data = $this->buildData($payload, false);
        $data['therapy_id'] = $therapyId;

        if ($existing) {
            $this->updateRow($therapyId, $data);
        } else {
            db()->insert($this->table, $data);
        }

        return $this->get($therapyId);
    }

    public function update($therapyId, array $payload): array
    {
        $existing = $this->fetchRow($therapyId);
        $data = $this->buildData($payload, true);

        if (!$existing) {
            $data['therapy_id'] = $therapyId;
            db()->insert($this->table, $data);
            return $this->get($therapyId);
        }

        if (!empty($data)) {
            $this->updateRow($therapyId, $data);
        }

        return $this->get($therapyId);
    }

    public function get($therapyId): array
    {
        $row = $this->fetchRow($therapyId);
        if (!$row) {
            return [];
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'therapy_id' => (int)($row['therapy_id'] ?? $therapyId),
            'primary_condition' => $row['primary_condition'] ?? '',
            'care_context' => $this->decodeJson($row['care_context'] ?? null),
            'general_anamnesis' => $this->decodeJson($row['general_anamnesis'] ?? null),
            'detailed_intake' => $this->decodeJson($row['detailed_intake'] ?? null),
            'adherence_base' => $this->decodeJson($row['adherence_base'] ?? null),
            'risk_score' => isset($row['risk_score']) ? (int)$row['risk_score'] : null,
            'flags' => $this->decodeJson($row['flags'] ?? null),
            'notes_initial' => $row['notes_initial'] ?? '',
            'follow_up_date' => $row['follow_up_date'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function fetchRow($therapyId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE therapy_id = ? LIMIT 1";
        return db_fetch_one($sql, [$therapyId]);
    }

    private function updateRow($therapyId, array $data): void
    {
        db()->update($this->table, $data, 'therapy_id = ?', [$therapyId]);
    }

    private function buildData(array $payload, bool $partial): array
    {
        $data = [];

        if (!$partial || array_key_exists('primary_condition', $payload)) {
            $data['primary_condition'] = $this->cleanString($payload['primary_condition'] ?? '');
        }
        if (!$partial || array_key_exists('care_context', $payload)) {
            $data['care_context'] = $this->encodeJson($payload['care_context'] ?? null);
        }
        if (!$partial || array_key_exists('general_anamnesis', $payload)) {
            $data['general_anamnesis'] = $this->encodeJson($payload['general_anamnesis'] ?? null);
        }
        if (!$partial || array_key_exists('detailed_intake', $payload)) {
            $data['detailed_intake'] = $this->encodeJson($payload['detailed_intake'] ?? null);
        }
        if (!$partial || array_key_exists('adherence_base', $payload)) {
            $data['adherence_base'] = $this->encodeJson($payload['adherence_base'] ?? null);
        }
        if (!$partial || array_key_exists('risk_score', $payload)) {
            $data['risk_score'] = isset($payload['risk_score']) ? (int)$payload['risk_score'] : null;
        }
        if (!$partial || array_key_exists('flags', $payload)) {
            $data['flags'] = $this->encodeJson($payload['flags'] ?? null);
        }
        if (!$partial || array_key_exists('notes_initial', $payload)) {
            $data['notes_initial'] = $this->cleanString($payload['notes_initial'] ?? '');
        }
        if (!$partial || array_key_exists('follow_up_date', $payload)) {
            $data['follow_up_date'] = $payload['follow_up_date'] ?? null;
        }

        return $data;
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
