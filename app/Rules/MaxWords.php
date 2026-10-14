<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxWords implements ValidationRule
{
    public function __construct(private readonly int $limit = 300)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        preg_match_all('/\S+/u', trim($value), $matches);

        if (count($matches[0]) > $this->limit) {
            $fail("The {$attribute} may not be longer than {$this->limit} words.");
        }
    }
}
