<?php

namespace AwStudio\MigrationsMerger;

use Illuminate\Support\ServiceProvider;

class MigrationsMergerServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMigrateMergeCommand();
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMergeCommand()
    {
        $this->app->singleton('aw-studio.commands.migrate.merge', function ($app) {
            return new MigrateMergeCommand(
                $app['db'], $app['migrator'], $app['files']
            );
        });
        $this->commands(['aw-studio.commands.migrate.merge']);
    }
}
