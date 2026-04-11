<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use SaeedHosan\Module\Support\Module;
use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('can be instantiated', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

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

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module)->toBeInstanceOf(Module::class);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns module exists status', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

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

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->exists())->toBeTrue();

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns module name', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

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

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('Blog');

    expect($module->name())->toBe('blog');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns module base path', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

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

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->basePath())->toContain('/modules/blog');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns module path', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

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

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->path())->toBe($module->basePath());

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns version from composer json', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->version())->toBe('1.0.0');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns description from composer json', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->description())->toBe('Blog module');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns providers from composer json', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->providers())->toBe(['Blog\\Providers\\BlogServiceProvider']);

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns composer data', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->composer()['version'])->toBe('1.0.0');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns namespace from composer psr-4', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode([
        'name' => 'vendor/blog',
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->namespace())->toBe('Modules\\Blog');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns default namespace', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.namespace', 'Modules');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->defaultNamespace())->toBe('Modules\\Blog');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns app path from psr-4', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');
    $files->put($basePath.'/modules/blog/composer.json', json_encode([
        'name' => 'vendor/blog',
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->appPath())->toContain('/modules/blog/src');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns view path', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.view_path', 'resources/views');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->viewPath())->toContain('/modules/blog/resources/views');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns lang path', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app['config']->set('module.directory', 'modules');
    $app['config']->set('module.lowercase', true);
    $app['config']->set('module.lang_path', 'lang');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->langPath())->toContain('/modules/blog/lang');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('returns toArray with module data', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
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
    $app['config']->set('module.lowercase', true);
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    $array = $module->toArray();

    expect($array['name'])->toBe('blog');
    expect($array['version'])->toBe('1.0.0');
    expect($array['description'])->toBe('Blog module');
    expect($array['exists'])->toBeTrue();

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});

it('can call basePath with segments', function () {
    $basePath = sys_get_temp_dir().'/module-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

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

    (new ServiceProvider($app))->register();

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module->basePath('src'))->toContain('/modules/blog/src');
    expect($module->basePath('src', 'Http'))->toContain('/modules/blog/src/Http');

    $files->deleteDirectory($basePath);
    Container::setInstance(null);
    Facade::clearResolvedInstances();
});
