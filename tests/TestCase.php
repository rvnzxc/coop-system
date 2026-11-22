<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\CreatesScenarioData;

abstract class TestCase extends BaseTestCase
{
    use CreatesScenarioData;
}
