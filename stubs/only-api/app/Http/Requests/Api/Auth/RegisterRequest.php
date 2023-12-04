<?php

namespace App\Http\Requests\Api\Auth;

use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;

class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required_without:contact_number', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'contact_number' => ['required_without:email', 'string', 'min:3', 'unique:' . User::class],
            'password' => ['required_with:email', 'confirmed', Rules\Password::defaults()],
            'profile_photo' => ['nullable', 'image', 'mimes:png,jpg', 'max:5120'],
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function bodyParameters()
    {
        return [
            'first_name' => ['example' => 'John'],
            'last_name' => ['example' => 'Doe'],
            'email' => ['example' => 'john@example.com'],
            'contact_number' => ['example' => '1234567890'],
            'password' => ['example' => '12345678']
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
