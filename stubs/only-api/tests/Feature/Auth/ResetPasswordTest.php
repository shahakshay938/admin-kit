<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_cannot_reset_password_by_email_when_fields_are_empty(): void
    {
        $response = $this->postJson(route('api.password.update.email'));

        $response->assertUnprocessable()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('validation.required', ['attribute' => 'email'])
                ],
            ]);
    }

    public function test_it_can_reset_password_by_email(): void
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $newPassword = 'newpassword123';

        $response = $this->postJson(route('api.password.update.email'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_it_can_get_invalid_security_token_error_while_resetting_password_by_contact_number(): void
    {
        $user = User::factory()->create();

        $newPassword = 'newpassword123';

        $response = $this->postJson(route('api.password.update.contact'), [
            'contact_number' => $user->contact_number,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'security_token' => 'invalid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('validation.checksum.invalid')
                ],
            ]);
    }

    public function test_it_can_reset_password_by_contact_number(): void
    {
        $user = User::factory()->create();

        $checksumResponse = $this->postJson(route('api.generate-checksum'), [
            'contact_number' => $user->contact_number,
        ]);

        $newPassword = 'newpassword123';

        $response = $this->postJson(route('api.password.update.contact'), [
            'contact_number' => $user->contact_number,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'security_token' => $checksumResponse->json()['data']['checksum'],
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }
}
