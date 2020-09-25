<?php

namespace AwStudio\MigrationsMerger;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use ReflectionProperty;

class SchemaMock
{
    use ForwardsCalls;

    protected $schema;

    public function __construct(Builder $schema, $console)
    {
        $this->schema = $schema;
        $this->console = $console;
        // $this->schema = Schema::getFacadeRoot();
    }

    public function connection()
    {
        return $this;
    }

    public function getSchemaBuilder()
    {
        return $this;
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

            $method = fn (Blueprint $schema) => $this->setUnaccessibleProperty(
                $schema, 'columns', [$column]
            );

            try {
                $this->schema->table($table, $method);
            } catch (QueryException $e) {
                if (! Str::contains($e->getMessage(), 'duplicate column name:')) {
                    throw $e;
                }

                continue;
            }
            $this->console->info("[DB]: Added column ($column->name) to $table");
        }
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
        // TODO:
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
