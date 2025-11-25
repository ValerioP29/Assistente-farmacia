<?php

namespace Modules\AdesioneTerapie\Controllers;

use Modules\AdesioneTerapie\Repositories\ReportRepository;
use Modules\AdesioneTerapie\Services\FormattingService;
use Modules\AdesioneTerapie\Services\ReportService;
use RuntimeException;

class ReportsController
{
    private ReportRepository $reportRepository;
    private FormattingService $formattingService;
    private ReportService $reportService;
    private int $pharmacyId;
    private array $reportCols;
    private $cleanCallback;
    private $nowCallback;
    private $verifyTherapyOwnershipCallback;
    private $findTherapyCallback;
    private $listChecksCallback;

    public function __construct(
        ReportRepository $reportRepository,
        FormattingService $formattingService,
        ReportService $reportService,
        int $pharmacyId,
        array $reportCols,
        callable $cleanCallback,
        callable $nowCallback,
        callable $verifyTherapyOwnershipCallback,
        callable $findTherapyCallback,
        callable $listChecksCallback
    ) {
        $this->reportRepository = $reportRepository;
        $this->formattingService = $formattingService;
        $this->reportService = $reportService;
        $this->pharmacyId = $pharmacyId;
        $this->reportCols = $reportCols;
        $this->cleanCallback = $cleanCallback;
        $this->nowCallback = $nowCallback;
        $this->verifyTherapyOwnershipCallback = $verifyTherapyOwnershipCallback;
        $this->findTherapyCallback = $findTherapyCallback;
        $this->listChecksCallback = $listChecksCallback;
    }

    public function generateReport(array $payload): array
    {
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : (int)($payload['therapy_reference'] ?? 0);
        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per generare il report.');
        }

        $this->verifyTherapyOwnership($therapyId);

        if (!$this->reportCols['share_token']) {
            throw new RuntimeException('La condivisione del report non è disponibile.');
        }

        $includeChecks = !empty($payload['include_checks']);
        $pinCode = $this->clean($payload['pin_code'] ?? '');
        $validUntil = !empty($payload['valid_until']) ? $payload['valid_until'] . ' 23:59:59' : null;

        $therapy = $this->findTherapy($therapyId);
        if (!$therapy) {
            throw new RuntimeException('Terapia non trovata.');
        }

        $checks = $includeChecks ? $this->listChecks($therapyId) : [];
        $content = $this->reportService->buildContent($therapy, $checks, $this->now());

        $payloadData = $this->reportService->buildReportPayload(
            $this->reportCols,
            $this->pharmacyId,
            $therapyId,
            $content,
            $pinCode,
            $validUntil,
            $this->reportCols['recipients'] && isset($payload['recipients']) ? $this->clean($payload['recipients']) : null,
            $this->now()
        );

        $filtered = $this->reportRepository->filterData($payloadData['data']);
        $reportId = $this->reportRepository->insert($filtered);

        return [
            'id' => $reportId,
            'token' => $payloadData['token'],
            'url' => $this->reportService->buildReportUrl($payloadData['token'], $payloadData['pin_required']),
            'valid_until' => $payloadData['valid_until'],
            'pin_required' => $payloadData['pin_required']
        ];
    }

    public function findReport(int $reportId): array
    {
        $report = $this->reportRepository->find($reportId, $this->reportCols['pharmacy'] ? $this->pharmacyId : null);

        if (!$report) {
            throw new RuntimeException('Report non trovato.');
        }

        return $this->formattingService->formatReport($report, $this->reportCols, [$this->reportService, 'buildReportUrl']);
    }

    public function listReports(?int $therapyId = null): array
    {
        $reports = $this->reportRepository->list($therapyId, $this->reportCols['pharmacy'] ? $this->pharmacyId : null);

        return array_map(function ($report) {
            return $this->formattingService->formatReport($report, $this->reportCols, [$this->reportService, 'buildReportUrl']);
        }, $reports);
    }

    private function verifyTherapyOwnership(int $therapyId): void
    {
        call_user_func($this->verifyTherapyOwnershipCallback, $therapyId);
    }

    private function findTherapy(int $therapyId): array
    {
        return call_user_func($this->findTherapyCallback, $therapyId);
    }

    private function listChecks(int $therapyId): array
    {
        return call_user_func($this->listChecksCallback, $therapyId);
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
