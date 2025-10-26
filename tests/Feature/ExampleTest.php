<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    public function returns_successful_response_for_application(): void
    {
        $this->markTestIncomplete();
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
