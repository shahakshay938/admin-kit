<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_will_get_email_field_required_validation_error(): void
    {
        $response = $this->postJson(route('api.password.forgot'));

        $response->assertUnprocessable()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('validation.required', ['attribute' => 'email']),
                ],
            ]);
    }

    public function test_it_will_get_email_for_resetting_password(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson(route('api.password.forgot'), [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('passwords.sent'),
                ],
            ]);
    }
}
