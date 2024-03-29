<?php

namespace Tutus\Adminkit;

use Illuminate\Support\ServiceProvider;

class AdminKitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\InstallCommand::class,
            Core\Console\Commands\ChecksumKeyGenerateCommand::class,
            Core\Console\Commands\StorageClearCommand::class,
        ]);
    }
}
