<?php

namespace Modules\AdesioneTerapie\Services;

class ReportService
{
    public function buildContent(array $therapy, array $checks, string $generatedAt): array
    {
        return [
            'generated_at' => $generatedAt,
            'therapy' => $therapy,
            'checks' => $checks,
        ];
    }

    public function buildReportPayload(
        array $reportCols,
        int $pharmacyId,
        int $therapyId,
        array $content,
        string $pinCode,
        ?string $validUntil,
        ?string $recipients,
        string $createdAt
    ): array {
        $reportData = [];

        if ($reportCols['pharmacy']) {
            $reportData[$reportCols['pharmacy']] = $pharmacyId;
        }

        if ($reportCols['therapy']) {
            $reportData[$reportCols['therapy']] = $therapyId;
        }

        if ($reportCols['content']) {
            $reportData[$reportCols['content']] = json_encode($content, JSON_UNESCAPED_UNICODE);
        }

        if ($reportCols['recipients'] && $recipients !== null) {
            $reportData[$reportCols['recipients']] = $recipients;
        }

        if ($reportCols['pin_code'] && $pinCode !== '') {
            $reportData[$reportCols['pin_code']] = password_hash($pinCode, PASSWORD_DEFAULT);
        }

        if ($reportCols['valid_until'] && $validUntil) {
            $reportData[$reportCols['valid_until']] = $validUntil;
        }

        if ($reportCols['created_at']) {
            $reportData[$reportCols['created_at']] = $createdAt;
        }

        $token = bin2hex(random_bytes(16));
        if ($reportCols['share_token']) {
            $reportData[$reportCols['share_token']] = $token;
        }

        return [
            'data' => $reportData,
            'token' => $token,
            'pin_required' => $pinCode !== '',
            'valid_until' => $validUntil,
        ];
    }

    public function buildReportUrl(string $token, bool $protected): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $relative = 'adesione-terapie/report.php?token=' . urlencode($token);

        if ($protected) {
            $relative .= '&secure=1';
        }

        $isLocal = $host && (str_contains($host, 'localhost') || str_starts_with($host, '127.0.0.1'));

        if ($isLocal) {
            return rtrim($scheme . '://' . $host, '/') . '/' . ltrim($relative, '/');
        }

        return 'https://panel.assistentefarmacia.it/' . ltrim($relative, '/');
    }
}
