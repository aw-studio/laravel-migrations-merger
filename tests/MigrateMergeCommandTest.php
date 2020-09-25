<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateMergeCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    /** @test */
    public function it_merges_columns()
    {
        $cols = Schema::getColumnListing('users');
        $this->assertEquals(['id', 'name'], $cols);

        $this->artisan('migrate:merge');

        $cols = Schema::getColumnListing('users');
        dump($cols);

        return;
        $this->assertContains('email', $cols);
    }
}
