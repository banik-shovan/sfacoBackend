<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_status_endpoint_returns_successful_response(): void
    {
        $response = $this->get('/api/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'app',
                'status',
                'version',
                'timestamp',
            ]);
    }
}
