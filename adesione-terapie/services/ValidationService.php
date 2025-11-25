<?php

namespace Modules\AdesioneTerapie\Services;

class ValidationService
{
    public function clean(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(htmlspecialchars_decode((string)$value, ENT_QUOTES));
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
