<?php

namespace Tests;

use AwStudio\MigrationsMerger\MigrationsMergerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MigrationsMergerServiceProvider::class,
            TestsServiceProvider::class,
        ];
    }
}
