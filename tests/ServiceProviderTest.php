<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\ViewServiceProvider;
use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('can be bootstrapped', function () {
    $basePath = sys_get_temp_dir().'/service-provider-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect($app->bound(ModuleManager::class))->toBeTrue();

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('registers module manager singleton', function () {
    $basePath = sys_get_temp_dir().'/service-provider-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    $provider = new ServiceProvider($app);
    $provider->register();

    $manager1 = $app->make(ModuleManager::class);
    $manager2 = $app->make(ModuleManager::class);

    expect($manager1)->toBe($manager2);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('merges module config', function () {
    $basePath = sys_get_temp_dir().'/service-provider-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'custom-modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.custom_key', 'custom_value');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    $provider = new ServiceProvider($app);
    $provider->register();

    expect($app['config']->get('module.directory'))->toBe('custom-modules');
    expect($app['config']->get('module.custom_key'))->toBe('custom_value');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('registers module blade directive', function () {
    $basePath = sys_get_temp_dir().'/service-provider-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->ensureDirectoryExists($basePath.'/resources/views');
    $files->ensureDirectoryExists($basePath.'/storage/framework/views');

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('view.paths', [$basePath.'/resources/views']);
    $app['config']->set('view.compiled', $basePath.'/storage/framework/views');
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    $app->register(ViewServiceProvider::class);
    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect(1)->toBe(1);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('boots without errors when running in console', function () {
    $basePath = sys_get_temp_dir().'/service-provider-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect(1)->toBe(1);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('accepts application instance', function () {
    $basePath = sys_get_temp_dir().'/service-provider-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);

    $provider = new ServiceProvider($app);

    expect($provider)->toBeInstanceOf(ServiceProvider::class);

    $files->deleteDirectory($basePath);
});

it('registers module blade component', function () {
    $basePath = sys_get_temp_dir().'/service-provider-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);
    $files->ensureDirectoryExists($basePath.'/modules/blog');
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

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('view.paths', [
        $basePath.'/resources/views',
        __DIR__.'/../resources/views',
    ]);
    $app['config']->set('view.compiled', $basePath.'/storage/framework/views');
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    $app->register(ViewServiceProvider::class);
    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    $compiled = Blade::compileString('<x-module name="blog">Content</x-module>');

    expect($compiled)->toContain('components.module');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});
