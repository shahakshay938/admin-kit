<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_access_api_docs(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
    }
}
