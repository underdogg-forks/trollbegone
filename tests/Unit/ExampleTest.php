<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    public function it_validates_that_true_is_true(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        // No arrangement needed
        /** #endregion */

        /** #region Act */
        // No action needed
        /** #endregion */

        /** #region Assert */
        $this->assertTrue(true);
        /** #endregion */
    }
}
