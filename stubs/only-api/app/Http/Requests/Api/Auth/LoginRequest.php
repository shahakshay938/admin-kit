<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use App\Rules\Api\ValidateChecksum;
use App\Rules\ValidatePasswordWithEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;

class LoginRequest extends FormRequest
{
    /**
     * Indicates whether validation should stop after the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = true;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required_without:contact_number', 'string', 'lowercase', 'email', 'max:255', Rule::exists('users', 'email')],
            'password' => ['required_with:email', Rules\Password::defaults(), new ValidatePasswordWithEmail],
            'contact_number' => ['required_without:email', 'string', 'min:3', Rule::exists('users', 'contact_number')],
            'security_token' => ['required_with:contact_number', new ValidateChecksum],
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function bodyParameters()
    {
        return [
            'email' => ['example' => 'john@example.com'],
            'password' => ['example' => '12345678'],
            'contact_number' => ['example' => '1234567890'],
            'security_token' => ['example' => 'yJpdiI6IlRtMVhlR3hKTjJOSWFVOVBPVVo2TWc9PSIsInZhbHVlIjoicDM0Q0RBSlwvTzNTYlFQcFRRT0tLMGRVcmZaOWRcL3JsSHpUb1J6ZkdVdlBrNkVJMEYzQkZiV2QzRHdaRXVmdkhRVmZuNkVXZE5PRnpWUUR6WWRXb0ZrZ08rR3BYcWp0M2lBZmdzb1hVWGt6FF0iLCJtYWMiOiIwNzQ0Y2VlMWE2NTJmMmM2OWVmMWI3ZTUxMGRhOWJiN2IxNWIxZWFjMmI2NDFjMDZjYTU5M2I2Y2ExZmI3NWU1In1=='],
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        response()->error($validator->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
