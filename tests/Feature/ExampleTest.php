<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    public function it_returns_successful_response_for_application(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        /* Arrange */

        /** #endregion */

        /** #region Act */
        /* Act */
        $response = $this->get('/');

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $response->assertStatus(200);

        /** #endregion */
    }
}
