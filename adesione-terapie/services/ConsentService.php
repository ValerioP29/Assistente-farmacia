<?php

namespace Modules\AdesioneTerapie\Services;

class ConsentService
{
    private string $table = 'jta_therapy_consents';

    public function save($therapyId, array $payload): array
    {
        $data = [
            'therapy_id' => $therapyId,
            'signer_name' => $this->cleanString($payload['signer_name'] ?? ''),
            'signer_relation' => $this->cleanString($payload['signer_relation'] ?? 'patient'),
            'signer_role' => $this->cleanString($payload['signer_role'] ?? ''),
            'consent_text' => $this->cleanString($payload['consent_text'] ?? 'Consenso informato registrato'),
            'scopes_json' => $this->encodeJson($payload['scopes'] ?? ($payload['scopes_json'] ?? [])),
            'ip_address' => $this->cleanString($payload['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '')),
            'signed_at' => $payload['signed_at'] ?? date('Y-m-d H:i:s'),
        ];

        $signatureBinary = $this->normalizeSignatureImage($payload['signature_image'] ?? null);
        if ($signatureBinary !== null) {
            $data['signature_image'] = $signatureBinary;
        }

        if (!empty($payload['created_at'])) {
            $data['created_at'] = $payload['created_at'];
        }

        db()->insert($this->table, $data);

        return $this->latest($therapyId);
    }

    public function list($therapyId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE therapy_id = ? ORDER BY signed_at DESC, id DESC";
        $rows = db_fetch_all($sql, [$therapyId]);
        return array_map(fn($row) => $this->format($row), $rows ?: []);
    }

    private function latest($therapyId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE therapy_id = ? ORDER BY signed_at DESC, id DESC LIMIT 1";
        $row = db_fetch_one($sql, [$therapyId]);
        return $row ? $this->format($row) : [];
    }

    private function format(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'therapy_id' => (int)($row['therapy_id'] ?? 0),
            'signer_name' => $row['signer_name'] ?? '',
            'signer_relation' => $row['signer_relation'] ?? '',
            'signer_role' => $row['signer_role'] ?? '',
            'consent_text' => $row['consent_text'] ?? '',
            'scopes' => $this->decodeJson($row['scopes_json'] ?? null),
            'signature_image' => $this->buildSignatureDataUrl($row['signature_image'] ?? null),
            'ip_address' => $row['ip_address'] ?? '',
            'signed_at' => $row['signed_at'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    private function normalizeSignatureImage($raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $clean = trim((string)$raw);
        if (strpos($clean, ',') !== false) {
            [$maybeMeta, $maybeData] = explode(',', $clean, 2);
            if (str_starts_with($maybeMeta, 'data:image')) {
                $clean = $maybeData;
            }
        }

        $binary = base64_decode($clean, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            return null;
        }

        return $binary;
    }

    private function buildSignatureDataUrl($stored): string
    {
        if (!$stored) {
            return '';
        }

        $info = @getimagesizefromstring($stored);
        if ($info === false) {
            return '';
        }

        $mime = $info['mime'] ?? 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($stored);
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
