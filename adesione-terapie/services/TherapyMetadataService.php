<?php

namespace Modules\AdesioneTerapie\Services;

class TherapyMetadataService
{
    public function buildMetadataPayload(array $caregivers, array $questionnaire, array $payload): array
    {
        $metadataPayload = [
            'caregivers' => $caregivers,
            'questionnaire' => $questionnaire,
            'consent_notes' => $payload['consent_notes'] ?? '',
        ];

        if (!empty($payload['signature_type'])) {
            $metadataPayload['signature_type'] = $payload['signature_type'];
        }

        return $metadataPayload;
    }

    public function applyMetadataColumns(array &$therapyData, array $metadataPayload, array $therapyCols): void
    {
        // TODO: CHRONIC_REWRITE - legacy therapy JSON columns (metadata/questionnaire/caregivers) are deprecated.
    }

    public function extractMetadata(array $therapy, array $therapyCols): array
    {
        // TODO: CHRONIC_REWRITE - legacy metadata extraction disabled; returning empty payloads.
        return [
            'metadata' => [],
            'caregivers' => [],
            'questionnaire' => [],
        ];
    }
}
