<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_will_get_method_not_allowed_exception_on_registration(): void
    {
        $response = $this->get(route('api.register'));

        $response->assertMethodNotAllowed();
    }

    public function test_it_can_get_database_exception(): void
    {
        Config::set('database.connections.mysql.password', 'new_testing_password');
        // config(['database.connections.mysql.password' => '']);
        // putenv('DB_PASSWORD=some_value');
        // dd(putenv('DB_PASSWORD', ''));

        $contact_number = Str::replace('+', '', fake()->e164PhoneNumber());

        $data = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'contact_number' => $contact_number,
        ];

        $response = $this->postJson(route('api.register'), $data);

        $validate = Arr::except($data, ['password_confirmation', 'password']);

        $response->assertCreated()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => $validate]);
    }

    public function test_it_will_get_validation_error_while_registering(): void
    {
        $response = $this->postJson(route('api.register'));

        $response->assertUnprocessable()
            ->assertJsonStructure(['data', 'meta' => ['message']])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('validation.required', ['attribute' => 'first name']),
                ]
            ]);
    }

    public function test_it_can_register_without_profile_photo(): void
    {
        $contact_number = Str::replace('+', '', fake()->e164PhoneNumber());

        $data = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'contact_number' => $contact_number,
        ];

        $response = $this->postJson(route('api.register'), $data);

        $validate = Arr::except($data, ['password_confirmation', 'password']);

        $response->assertCreated()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => $validate]);
    }

    public function test_it_can_register_with_profile_photo(): void
    {
        $profile_photo = UploadedFile::fake()->image('avatar.png');

        $contact_number = Str::replace('+', '', fake()->e164PhoneNumber());

        $data = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'contact_number' => $contact_number,
            'profile_photo' => $profile_photo,
        ];

        $response = $this->postJson(route('api.register'), $data);

        $validate = Arr::except($data, ['password_confirmation', 'password', 'profile_photo']);

        $response->assertCreated()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => $validate]);
    }
}
