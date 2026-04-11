<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use SaeedHosan\Module\Support\ModuleRepository;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('can be instantiated', function () {
    $files = new Filesystem;

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository)->toBeInstanceOf(ModuleRepository::class);
});

it('returns module path with relative basePath', function () {
    $basePath = sys_get_temp_dir().'/module-repo-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->path())->toBe($basePath.'/modules/blog');
    expect($repository->exists())->toBeTrue();

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns lowercase name when lowercase is true', function () {
    $files = new Filesystem;

    $repository = new ModuleRepository(
        name: 'Blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->name())->toBe('blog');
});

it('returns original name when lowercase is false', function () {
    $files = new Filesystem;

    $repository = new ModuleRepository(
        name: 'Blog',
        files: $files,
        basePath: 'modules',
        lowercase: false
    );

    expect($repository->name())->toBe('Blog');
});

it('returns version from composer.json', function () {
    $basePath = sys_get_temp_dir().'/module-repo-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode([
        'name' => 'vendor/blog',
        'version' => '1.0.0',
    ]));

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->version())->toBe('1.0.0');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns default version when not in composer.json', function () {
    $basePath = sys_get_temp_dir().'/module-repo-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode(['name' => 'vendor/blog']));

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->version())->toBe('0');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns description from composer.json', function () {
    $basePath = sys_get_temp_dir().'/module-repo-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode([
        'name' => 'vendor/blog',
        'description' => 'Blog module',
    ]));

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->description())->toBe('Blog module');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns providers from composer.json', function () {
    $basePath = sys_get_temp_dir().'/module-repo-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode([
        'name' => 'vendor/blog',
        'extra' => [
            'laravel' => [
                'providers' => ['Blog\\Providers\\BlogServiceProvider'],
            ],
        ],
    ]));

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->providers())->toBe(['Blog\\Providers\\BlogServiceProvider']);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns empty array when composer.json does not exist', function () {
    $basePath = sys_get_temp_dir().'/module-repo-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->composer())->toBe([]);
    expect($repository->providers())->toBe([]);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns toArray with module data', function () {
    $basePath = sys_get_temp_dir().'/module-repo-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode([
        'name' => 'vendor/blog',
        'version' => '1.0.0',
        'description' => 'Blog module',
    ]));

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $repository = new ModuleRepository(
        name: 'blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    $array = $repository->toArray();

    expect($array['name'])->toBe('blog');
    expect($array['version'])->toBe('1.0.0');
    expect($array['description'])->toBe('Blog module');
    expect($array['exists'])->toBeTrue();

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});
