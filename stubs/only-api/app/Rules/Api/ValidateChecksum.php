<?php

namespace App\Rules\Api;

use Closure;
use App\Traits\Api\ChecksumTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateChecksum implements ValidationRule
{
    use ChecksumTrait;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->validatePayload($value, request()->contact_number)) {
            $fail('validation.checksum.invalid')->translate();
        }
    }
}
