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

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        $response = $this->get('/');
        /** #endregion */

        /** #region Assert */
        $response->assertStatus(200);
        /** #endregion */
    }
}
