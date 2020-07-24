<?php

namespace Digikraaft\ModelSuspension;

use Digikraaft\ModelSuspension\Exceptions\InvalidSuspensionModel;
use Illuminate\Support\ServiceProvider;

class ModelSuspensionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPublishables();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/model-suspension.php', 'model-suspension');
    }

    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');
        }

        if (! class_exists('CreateSuspensionsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/../database/migrations/create_suspensions_table.php.stub' => database_path('migrations/'.$timestamp.'_create_suspensions_table.php'),
            ], 'migrations');
        }

        $this->publishes([
            __DIR__.'/../config/model-suspension.php' => config_path('model-suspension.php'),
        ], 'config');

        $this->guardAgainstInvalidSuspensionModel();
    }

    public function guardAgainstInvalidSuspensionModel()
    {
        $modelClassName = config('model-suspension.suspension_model');

        if (! is_a($modelClassName, Suspension::class, true)) {
            throw InvalidSuspensionModel::create($modelClassName);
        }
    }
}
