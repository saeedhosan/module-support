<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use SaeedHosan\Module\Support\Module;
use SaeedHosan\Module\Support\ModuleCollection;
use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('can be instantiated', function () {
    $app = new Application(__DIR__);
    $files = new Filesystem;
    $config = ['lowercase' => true, 'directory' => 'modules'];

    $manager = new ModuleManager($app, $files, $config);

    expect($manager)->toBeInstanceOf(ModuleManager::class);
});

it('returns module instance', function () {
    $basePath = sys_get_temp_dir().'/module-manager-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode(['name' => 'vendor/blog']));

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module)->toBeInstanceOf(Module::class);
    expect($module->name())->toBe('blog');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('caches module instances', function () {
    $basePath = sys_get_temp_dir().'/module-manager-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode(['name' => 'vendor/blog']));

    $manager = app(ModuleManager::class);
    $module1 = $manager->module('blog');
    $module2 = $manager->module('blog');

    expect($module1)->toBe($module2);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns loaded modules collection', function () {
    $basePath = sys_get_temp_dir().'/module-manager-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode(['name' => 'vendor/blog']));

    $files->ensureDirectoryExists($basePath.'/modules/auth');
    $files->put($basePath.'/modules/auth/composer.json', json_encode(['name' => 'vendor/auth']));

    $manager = app(ModuleManager::class);
    $manager->module('blog');
    $manager->module('auth');
    $loaded = $manager->loaded();

    expect($loaded)->toBeInstanceOf(ModuleCollection::class);
    expect($loaded->count())->toBe(2);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns null when module does not exist', function () {
    $basePath = sys_get_temp_dir().'/module-manager-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    $manager = app(ModuleManager::class);
    $found = $manager->find('nonexistent');

    expect($found)->toBeNull();

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns module when it exists', function () {
    $basePath = sys_get_temp_dir().'/module-manager-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode(['name' => 'vendor/blog']));

    $manager = app(ModuleManager::class);
    $found = $manager->find('blog');

    expect($found)->toBeInstanceOf(Module::class);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});
