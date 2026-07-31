<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_api_campaigns_active_returns_success(): void
    {
        $this->seed();

        $response = $this->getJson('/api/v1/campaigns/active');

        $response->assertOk()->assertJsonPath('success', true);
    }
}
