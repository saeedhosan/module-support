<?php

declare(strict_types=1);

namespace SaeedHosan\Module\Support;

use Composer\InstalledVersions;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // config
        $this->mergeConfigFrom(__DIR__.'/../config/module.php', 'module');

        $this->app->singleton(ModuleManager::class, function ($app): ModuleManager {
            return new ModuleManager(
                app: $app,
                files: $app->make(Filesystem::class),
                config: $app['config']->get('module', [])
            );
        });

        if (! function_exists('module')) {
            require_once __DIR__.'/helpers.php';
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->withAbout();
        $this->bootRunningInConsole();
        $this->bootBaladeDirectives();
    }

    private function bootRunningInConsole(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/module.php' => config_path('module.php'),
            ], 'module-config');
        }
    }

    private function bootBaladeDirectives(): void
    {
        Blade::if('module', function (?string $name = null): bool {
            if ($name === null || $name === '') {
                return false;
            }

            return module($name)->active();
        });
    }

    private function withAbout(): void
    {
        if (! class_exists(InstalledVersions::class) || ! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('Module', static fn () => [
            'Support version' => InstalledVersions::getPrettyVersion('saeedhosan/module-support'),
        ]);
    }
}
