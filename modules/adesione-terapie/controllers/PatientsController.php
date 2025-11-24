<?php

namespace Modules\AdesioneTerapie\Controllers;

use Modules\AdesioneTerapie\Repositories\PatientRepository;
use Modules\AdesioneTerapie\Services\FormattingService;
use RuntimeException;

class PatientsController
{
    private PatientRepository $patientRepository;
    private FormattingService $formattingService;
    private int $pharmacyId;
    private array $patientCols;
    private $cleanCallback;
    private $nowCallback;

    public function __construct(
        PatientRepository $patientRepository,
        FormattingService $formattingService,
        int $pharmacyId,
        array $patientCols,
        callable $cleanCallback,
        callable $nowCallback
    ) {
        $this->patientRepository = $patientRepository;
        $this->formattingService = $formattingService;
        $this->pharmacyId = $pharmacyId;
        $this->patientCols = $patientCols;
        $this->cleanCallback = $cleanCallback;
        $this->nowCallback = $nowCallback;
    }

    public function savePatient(array $payload): array
    {
        $patientId = isset($payload['patient_id']) && $payload['patient_id'] !== '' ? (int)$payload['patient_id'] : null;

        $data = [];
        if ($this->patientCols['pharmacy']) {
            $data[$this->patientCols['pharmacy']] = $this->pharmacyId;
        }

        if ($this->patientCols['first_name']) {
            $firstName = $this->clean($payload['first_name'] ?? $payload['name'] ?? '');
            if ($firstName === '' && !$patientId) {
                throw new RuntimeException('Il nome del paziente è obbligatorio.');
            }
            $data[$this->patientCols['first_name']] = $firstName;
        }
        if ($this->patientCols['last_name']) {
            $data[$this->patientCols['last_name']] = $this->clean($payload['last_name'] ?? '');
        }
        if ($this->patientCols['phone']) {
            $data[$this->patientCols['phone']] = $this->clean($payload['phone'] ?? '');
        }
        if ($this->patientCols['email']) {
            $email = $this->clean($payload['email'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Indirizzo email non valido.');
            }
            $data[$this->patientCols['email']] = $email;
        }
        if ($this->patientCols['birth_date'] && !empty($payload['birth_date'])) {
            $data[$this->patientCols['birth_date']] = $payload['birth_date'];
        }
        if ($this->patientCols['notes'] && isset($payload['notes'])) {
            $data[$this->patientCols['notes']] = $this->clean($payload['notes']);
        }
        if ($this->patientCols['updated_at']) {
            $data[$this->patientCols['updated_at']] = $this->now();
        }

        $filtered = $this->patientRepository->filterData($data);

        if ($patientId) {
            $this->patientRepository->update($patientId, $filtered, $this->patientCols['id']);
        } else {
            if ($this->patientCols['created_at'] && !isset($filtered[$this->patientCols['created_at']])) {
                $filtered[$this->patientCols['created_at']] = $this->now();
            }
            $patientId = $this->patientRepository->insert($filtered);
        }

        return $this->findPatient($patientId);
    }

    public function findPatient(int $patientId): array
    {
        $patient = $this->patientRepository->find($patientId, $this->patientCols, $this->patientCols['pharmacy'] ? $this->pharmacyId : null);
        if (!$patient) {
            throw new RuntimeException('Paziente non trovato.');
        }

        return $this->formattingService->formatPatient($patient, $this->patientCols);
    }

    public function listPatients(): array
    {
        $patients = $this->patientRepository->list($this->patientCols, $this->patientCols['pharmacy'] ? $this->pharmacyId : null);

        $result = [];
        foreach ($patients as $patient) {
            $result[] = $this->formattingService->formatPatient($patient, $this->patientCols);
        }

        return $result;
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
