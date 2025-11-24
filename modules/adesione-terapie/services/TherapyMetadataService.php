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
        if ($therapyCols['metadata']) {
            $therapyData[$therapyCols['metadata']] = json_encode($metadataPayload, JSON_UNESCAPED_UNICODE);
        }
        if ($therapyCols['questionnaire']) {
            $therapyData[$therapyCols['questionnaire']] = json_encode($metadataPayload['questionnaire'], JSON_UNESCAPED_UNICODE);
        }
        if ($therapyCols['caregivers']) {
            $therapyData[$therapyCols['caregivers']] = json_encode($metadataPayload['caregivers'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function extractMetadata(array $therapy, array $therapyCols): array
    {
        $metadata = [];
        if ($therapyCols['metadata'] && !empty($therapy[$therapyCols['metadata']])) {
            $decoded = json_decode($therapy[$therapyCols['metadata']], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $caregivers = $metadata['caregivers'] ?? [];
        if ($therapyCols['caregivers'] && !empty($therapy[$therapyCols['caregivers']])) {
            $decoded = json_decode($therapy[$therapyCols['caregivers']], true);
            if (is_array($decoded)) {
                $caregivers = $decoded;
            }
        }

        $questionnaire = $metadata['questionnaire'] ?? [];
        if ($therapyCols['questionnaire'] && !empty($therapy[$therapyCols['questionnaire']])) {
            $decoded = json_decode($therapy[$therapyCols['questionnaire']], true);
            if (is_array($decoded)) {
                $questionnaire = $decoded;
            }
        }

        return [
            'metadata' => $metadata,
            'caregivers' => $caregivers,
            'questionnaire' => $questionnaire,
        ];
    }
}
