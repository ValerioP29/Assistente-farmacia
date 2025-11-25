<?php

namespace Modules\AdesioneTerapie\Services;

use AdesioneTableResolver;
use RuntimeException;
use Modules\AdesioneTerapie\Repositories\ConsentRepository;

class ConsentService
{
    private ConsentRepository $consentRepository;
    private array $consentCols;
    private $cleanCallback;
    private $nowCallback;

    public function __construct(ConsentRepository $consentRepository, array $consentCols, callable $cleanCallback, callable $nowCallback)
    {
        $this->consentRepository = $consentRepository;
        $this->consentCols = $consentCols;
        $this->cleanCallback = $cleanCallback;
        $this->nowCallback = $nowCallback;
    }

    public function storeConsent(int $therapyId, int $patientId, array $payload): void
    {
        if (!$this->consentCols['therapy']) {
            return;
        }

        $existing = $this->consentRepository->findLatestByTherapy($therapyId);

        $data = [];
        $data[$this->consentCols['therapy']] = $therapyId;

        if ($this->consentCols['signer_name']) {
            $data[$this->consentCols['signer_name']] = $this->clean($payload['signer_name'] ?? '');
        }
        if ($this->consentCols['signer_relation']) {
            $data[$this->consentCols['signer_relation']] = $this->clean($payload['signer_relation'] ?? 'patient');
        }
        if ($this->consentCols['consent_text']) {
            $text = $payload['consent_text'] ?? '';
            $data[$this->consentCols['consent_text']] = $this->clean($text !== '' ? $text : 'Consenso informato registrato');
        }
        if ($this->consentCols['signature_image'] && !empty($payload['signature_image'])) {
            $signatureBinary = $this->normalizeSignatureImage($payload['signature_image']);
            if ($signatureBinary !== null) {
                $data[$this->consentCols['signature_image']] = $signatureBinary;
            }
        }
        if ($this->consentCols['ip']) {
            $data[$this->consentCols['ip']] = $this->clean($payload['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
        }
        if ($this->consentCols['signed_at']) {
            $data[$this->consentCols['signed_at']] = $payload['signed_at'] ?? $this->now();
        }
        if ($this->consentCols['created_at'] && !$existing) {
            $data[$this->consentCols['created_at']] = $this->now();
        }

        $filtered = $this->consentRepository->filterData($data);

        if ($existing) {
            $existingId = $existing[$this->consentCols['id']] ?? $existing['id'] ?? null;
            if ($existingId) {
                $this->consentRepository->update((int)$existingId, $filtered, $this->consentCols['id']);
            }
        } else {
            $this->consentRepository->insert($filtered);
        }
    }

    public function getConsent(int $therapyId): array
    {
        if (!$this->consentCols['therapy']) {
            return [];
        }
        $row = $this->consentRepository->findLatestByTherapy($therapyId);
        if (!$row) {
            return [];
        }

        return $this->formatConsent($row);
    }

    public function formatConsent(array $consent): array
    {
        return [
            'signer_name' => $this->consentCols['signer_name'] ? ($consent[$this->consentCols['signer_name']] ?? '') : '',
            'signer_relation' => $this->consentCols['signer_relation'] ? ($consent[$this->consentCols['signer_relation']] ?? '') : '',
            'consent_text' => $this->consentCols['consent_text'] ? ($consent[$this->consentCols['consent_text']] ?? '') : '',
            'signature_image' => $this->consentCols['signature_image'] ? $this->buildSignatureDataUrl($consent[$this->consentCols['signature_image']] ?? '') : '',
            'ip_address' => $this->consentCols['ip'] ? ($consent[$this->consentCols['ip']] ?? '') : '',
            'signed_at' => $this->consentCols['signed_at'] ? ($consent[$this->consentCols['signed_at']] ?? '') : '',
        ];
    }

    private function normalizeSignatureImage(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $clean = trim($raw);
        if (str_contains($clean, ',')) {
            [$maybeMeta, $maybeData] = explode(',', $clean, 2);
            if (str_starts_with($maybeMeta, 'data:image')) {
                $clean = $maybeData;
            }
        }

        $binary = base64_decode($clean, true);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('Formato della firma non valido.');
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            throw new RuntimeException('La firma non è un\'immagine valida.');
        }

        return $binary;
    }

    private function buildSignatureDataUrl(?string $stored): string
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

    private function clean(?string $value): string
    {
        return call_user_func($this->cleanCallback, $value);
    }

    private function now(): string
    {
        return call_user_func($this->nowCallback);
    }
}
