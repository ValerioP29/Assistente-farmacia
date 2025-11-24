<?php

namespace Modules\AdesioneTerapie\Services;

class FormattingService
{
    public function formatPatient(array $patient, array $patientCols): array
    {
        $id = (int)($patient[$patientCols['id']] ?? 0);
        $first = $patientCols['first_name'] ? ($patient[$patientCols['first_name']] ?? '') : '';
        $last = $patientCols['last_name'] ? ($patient[$patientCols['last_name']] ?? '') : '';
        $fullName = trim($first . ' ' . $last);
        if ($fullName === '') {
            $fullName = $first ?: $last;
        }

        return [
            'id' => $id,
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => $fullName,
            'phone' => $patientCols['phone'] ? ($patient[$patientCols['phone']] ?? '') : '',
            'email' => $patientCols['email'] ? ($patient[$patientCols['email']] ?? '') : '',
            'birth_date' => $patientCols['birth_date'] ? ($patient[$patientCols['birth_date']] ?? null) : null,
            'notes' => $patientCols['notes'] ? ($patient[$patientCols['notes']] ?? '') : '',
        ];
    }
}
