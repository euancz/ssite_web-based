<?php

namespace App\Rules;

use App\Support\AcademicYear;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ConsecutiveAcademicYear implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! AcademicYear::isValid($value) || (int) substr($value, 0, 4) < 1900) {
            $fail('Enter consecutive years in YYYY-YYYY format, for example 2026-2027 (1900 or later).');
        }
    }
}
