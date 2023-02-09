<?php

namespace Hybridly\Tests;

use Hybridly\ServiceProvider;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__ . '/stubs');
    }
}
