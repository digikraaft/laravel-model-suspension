<?php

namespace Digikraaft\ModelSuspension\Tests;

use Digikraaft\ModelSuspension\ModelSuspensionServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [ModelSuspensionServiceProvider::class];
    }

    protected function setUpDatabase()
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('custom_model_key_suspensions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('is_suspended')->default(true)->nullable();
            $table->text('reason')->nullable();
            $table->date('suspended_until')->nullable();

            $table->string('model_type');
            $table->unsignedBigInteger('model_custom_fk');
            $table->index(['model_type', 'model_custom_fk']);

            $table->timestamps();
            $table->softDeletes();
        });

        include_once __DIR__ . '/../database/migrations/create_suspensions_table.php.stub';

        (new \CreateSuspensionsTable)->up();
    }
}
