<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewServiceProvider;
use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('can be bootstrapped', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestApp($basePath, $files);

    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect($app->bound(ModuleManager::class))->toBeTrue();

    cleanupTestApp($basePath, $files);
});

it('registers module manager singleton', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestApp($basePath, $files);

    $provider = new ServiceProvider($app);
    $provider->register();

    $manager1 = $app->make(ModuleManager::class);
    $manager2 = $app->make(ModuleManager::class);

    expect($manager1)->toBe($manager2);

    cleanupTestApp($basePath, $files);
});

it('merges module config', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestApp($basePath, $files, [
        'module.directory' => 'custom-modules',
        'module.custom_key' => 'custom_value',
    ]);

    $provider = new ServiceProvider($app);
    $provider->register();

    expect($app['config']->get('module.directory'))->toBe('custom-modules');
    expect($app['config']->get('module.custom_key'))->toBe('custom_value');

    cleanupTestApp($basePath, $files);
});

it('registers module blade directive', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = createTestApp($basePath, $files, [
        'view.paths' => [$basePath.'/resources/views'],
        'view.compiled' => $basePath.'/storage/framework/views',
    ]);

    $app->register(ViewServiceProvider::class);

    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect(1)->toBe(1);

    cleanupTestApp($basePath, $files);
});

it('boots without errors when running in console', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestApp($basePath, $files);

    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect(1)->toBe(1);

    cleanupTestApp($basePath, $files);
});

it('accepts application instance', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = new Application($basePath);

    $provider = new ServiceProvider($app);

    expect($provider)->toBeInstanceOf(ServiceProvider::class);

    cleanupTestApp($basePath, $files);
});

it('registers module blade component', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithViews($basePath, $files, [
        __DIR__.'/../resources/views',
    ]);

    $provider = new ServiceProvider($app);
    $provider->register();
    $provider->boot();

    $compiled = Blade::compileString('<x-module name="blog">Content</x-module>');

    expect($compiled)->toContain('components.module');

    cleanupTestApp($basePath, $files);
});
