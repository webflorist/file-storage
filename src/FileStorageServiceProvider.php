<?php

namespace Webflorist\FileStorage;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Webflorist\StaticRoutes\Commands\FileStorageCommand;

class FileStorageServiceProvider extends ServiceProvider
{

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfig();
        $this->registerService();
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
        $this->loadMigrations();
        $this->loadTranslations();
    }

    protected function mergeConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/file-storage.php', 'file-storage');
    }

    protected function registerService()
    {
        $this->app->singleton(FileStorage::class, function () {
            return new FileStorage();
        });
    }

    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/file-storage.php' => config_path('validation.php'),
        ]);
    }

    private function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    private function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . "/../resources/lang", "Webflorist-FileStorage");
    }
}