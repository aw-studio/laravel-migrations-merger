<?php

namespace Tests;

use Illuminate\Support\ServiceProvider;

class TestsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }
}
