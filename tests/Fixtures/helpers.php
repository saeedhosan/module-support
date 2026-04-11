<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\ViewServiceProvider;
use SaeedHosan\Module\Support\ServiceProvider;

function createTestApp(?string $basePath = null, ?Filesystem $files = null, array $config = []): Application
{
    $basePath = $basePath ?? sys_get_temp_dir().'/test-'.uniqid();
    $files = $files ?? new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);

    $defaultConfig = [
        'module.directory' => 'modules',
        'module.lowercase' => true,
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        'cache.default' => 'array',
    ];

    foreach (array_merge($defaultConfig, $config) as $key => $value) {
        $app['config']->set($key, $value);
    }

    return $app;
}

function createTestAppWithViews(?string $basePath = null, ?Filesystem $files = null, array $extraViews = []): Application
{
    $basePath = $basePath ?? sys_get_temp_dir().'/test-'.uniqid();
    $files = $files ?? new Filesystem;

    $files->ensureDirectoryExists($basePath.'/resources/views');
    $files->ensureDirectoryExists($basePath.'/storage/framework/views');
    $files->ensureDirectoryExists($basePath.'/vendor/composer');

    $files->put($basePath.'/composer.json', json_encode([
        'autoload' => [
            'psr-4' => [
                'App\\' => 'app/',
            ],
        ],
    ], JSON_PRETTY_PRINT));

    $files->put(
        $basePath.'/vendor/composer/autoload_psr4.php',
        "<?php\n\nreturn ".var_export(['App\\' => [$basePath.'/app/']], true).";\n"
    );

    $viewPaths = [$basePath.'/resources/views'];
    if (! empty($extraViews)) {
        $viewPaths = array_merge($viewPaths, $extraViews);
    }

    $app = createTestApp($basePath, $files, [
        'view.paths' => $viewPaths,
        'view.compiled' => $basePath.'/storage/framework/views',
        'module.lowercase' => false,
    ]);

    $app->register(ViewServiceProvider::class);

    return $app;
}

function createTestAppWithServiceProvider(?string $basePath = null, ?Filesystem $files = null, array $config = []): Application
{
    $app = createTestApp($basePath, $files, $config);
    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    return $app;
}

function cleanupTestApp(?string $basePath = null, ?Filesystem $files = null): void
{
    if ($basePath && $files && $files->exists($basePath)) {
        $files->deleteDirectory($basePath);
    }

    Facade::clearResolvedInstances();
    Container::setInstance(null);
}

function createModule(Filesystem $files, string $basePath, string $name, array $composerData = []): void
{
    $modulePath = $basePath.'/modules/'.$name;
    $files->ensureDirectoryExists($modulePath);
    $files->put($modulePath.'/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));
}

function createAutoloadPsr4(Filesystem $files, string $basePath, array $map): void
{
    $autoloadPath = $basePath.'/vendor/composer';
    $files->ensureDirectoryExists($autoloadPath);
    $files->put(
        $autoloadPath.'/autoload_psr4.php',
        "<?php\n\nreturn ".var_export($map, true).";\n"
    );
}
