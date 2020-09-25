<?php

namespace AwStudio\MigrationsMerger;

use Closure;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;

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

        $this->laravel->bind('db', fn () => $this);

        foreach ($files as $file) {
            $migration = $this->migrator->resolve(
                $this->migrator->getMigrationName($file)
            );

            $migration->up();
        }

        $this->laravel->bind('db', fn () => $this->db);
    }

    /**
     * Schema create method mock.
     *
     * @param  string  $table
     * @param  Closure $closure
     * @return void
     */
    public function create($table, Closure $closure)
    {
        $closure($schema = new Blueprint($table));

        foreach ($columns = $schema->getColumns() as $key => $column) {
            if (array_key_exists($key - 1, $columns)) {
                $column->after($columns[$key - 1]->name);
            }

            try {
                $this->schema->table(
                    $table,
                    fn (Blueprint $schema) => $this->setUnaccessibleProperty($schema, 'columns', [$column])
                );
            } catch (QueryException $e) {
                continue;
            }
            dump("[DB]: Added column ($column->name) to $table");
            $this->info("[DB]: Added column ($column->name) to $table");
        }
    }

    /**
     * Get connection.
     *
     * @return void
     */
    public function connection()
    {
        return $this;
    }

    /**
     * Get schema builder.
     *
     * @return void
     */
    public function getSchemaBuilder()
    {
        return $this;
    }

    /**
     * Schema table method mock.
     *
     * @param  string $headers
     * @param  array  $rows
     * @param  string $tableStyle
     * @param  array  $columnStyles
     * @return void
     */
    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = [])
    {
        // return $this->schema->table($headers, $rows);
    }

    /**
     * Set protected or private class property value.
     *
     * @param  mixed  $instance
     * @param  string $property
     * @param  mixed  $value
     * @return void
     */
    protected function setUnaccessibleProperty($instance, string $property, $value)
    {
        $reflection = new ReflectionProperty(get_class($instance), $property);
        $reflection->setAccessible(true);
        $value = $reflection->setValue($instance, $value);
    }

    /**
     * Call method.
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->forwardCallTo($this->schema, $method, $arguments);
    }
}
