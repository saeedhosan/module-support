<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use SaeedHosan\Module\Support\ModuleCollection;
use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('can be instantiated with no items', function () {
    $collection = new ModuleCollection;

    expect($collection)->toBeInstanceOf(ModuleCollection::class);
    expect($collection->names())->toBe([]);
});

it('returns module names as array', function () {
    $basePath = sys_get_temp_dir().'/module-collection-test-'.uniqid();
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

    expect($loaded->names())->toBe(['blog', 'auth']);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});
