<?php

namespace AwStudio\MigrationsMerger;

use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;

class MigrateMergeCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:merge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merges existings migrations.';

    /**
     * DatabaseManager instance.
     *
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Migrator instance.
     *
     * @var Migrator
     */
    protected $migrator;

    /**
     * Filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param  DatabaseManager $db
     * @param  Migrator        $migrator
     * @param  Filesystem      $files
     * @return void
     */
    public function __construct(DatabaseManager $db,
                                Migrator $migrator,
                                Filesystem $files)
    {
        parent::__construct();

        $this->db = $db;
        $this->files = $files;
        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->schema = Schema::getFacadeRoot();

        $files = collect($this->getMigrationPaths())
            ->flatMap(function ($path) {
                return $this->files->glob($path.'/*.php');
            })->toArray();

        $this->migrator->requireFiles(
            $files = $this->migrator->getMigrationFiles($files)
        );

        $mock = new SchemaMock($this->schema, $this);

        $this->laravel->bind('db', fn () => $mock);

        foreach ($files as $file) {
            $migration = $this->migrator->resolve(
                $this->migrator->getMigrationName($file)
            );

            $migration->up();
        }

        $this->laravel->bind('db', fn () => $this->db);
    }
}
