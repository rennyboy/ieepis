<?php

namespace Tests\Feature;

use Tests\TestCase;

class BootSmokeTest extends TestCase
{
    public function test_app_boots(): void
    {
        $this->assertTrue(true);
        $this->assertNotNull($this->app);
    }
}

