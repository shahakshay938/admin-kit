<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_not_login_with_email_if_password_was_incorrect(): void
    {
        $user = User::factory()->create();

        $data = [
            'email' => $user->email,
            'password' => 'wrong-password'
        ];

        $response = $this->postJson(route('api.login'), $data);

        $response->assertUnprocessable()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('auth.password'),
                ],
            ]);
    }

    public function test_it_can_login_with_email(): void
    {
        $user = User::factory()->create();

        $data = [
            'email' => $user->email,
            'password' => 'password'
        ];

        $response = $this->postJson(route('api.login'), $data);

        $validate = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ];

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => $validate]);
    }



    public function test_it_will_get_invalid_checksum_error_on_login_with_contact_number(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.generate-checksum'), [
            'contact_number' => $user->contact_number,
        ]);

        $data = [
            'contact_number' => $user->contact_number,
            'security_token' => 'invalid-checksum',
        ];

        $response = $this->postJson(route('api.login'), $data);

        $response->assertUnprocessable()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('validation.checksum.invalid'),
                ]
            ]);
    }

    public function test_it_can_login_with_contact_number(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.generate-checksum'), [
            'contact_number' => $user->contact_number,
        ]);

        $data = [
            'contact_number' => $user->contact_number,
            'security_token' => $response->json()['data']['checksum'],
        ];

        $response = $this->postJson(route('api.login'), $data);

        $validate = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ];

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => $validate]);
    }

    public function test_it_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.logout'));

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('auth.logout', ['Entity' => 'User']),
                ],
            ]);
    }
}
