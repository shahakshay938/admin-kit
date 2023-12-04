<?php

namespace Tests\Feature\Common;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeneralTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_run_checksum_generate_command(): void
    {
        $this->artisan('checksum:generate')->assertExitCode(0);
    }

    public function test_it_can_show_checksum_generate_command(): void
    {
        $this->artisan('checksum:generate --show')->assertExitCode(0);
    }

    public function test_it_can_get_contact_number_required_validation_while_generating_checksun(): void
    {
        $response = $this->postJson(route('api.generate-checksum'));

        $response->assertUnprocessable()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJson([
                'data' => null,
                'meta' => [
                    'message' => trans('validation.required', ['attribute' => 'contact number']),
                ]
            ]);
    }

    public function test_it_can_generate_checksun(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.generate-checksum'), [
            'contact_number' => $user->contact_number,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['checksum']]);
    }
}
