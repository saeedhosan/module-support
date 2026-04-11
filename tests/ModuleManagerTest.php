<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use SaeedHosan\Module\Support\Module;
use SaeedHosan\Module\Support\ModuleCollection;
use SaeedHosan\Module\Support\ModuleManager;
use Tests\TestCase;

uses(TestCase::class);

it('can be instantiated', function () {
    $files = new Filesystem;
    $config = ['lowercase' => true, 'directory' => 'modules'];
    $app = new Application(__DIR__);

    $manager = new ModuleManager($app, $files, $config);

    expect($manager)->toBeInstanceOf(ModuleManager::class);
});

it('returns module instance', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', ['name' => 'vendor/blog']);

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module)->toBeInstanceOf(Module::class);
    expect($module->name())->toBe('blog');

    cleanupTestApp($basePath, $files);
});

it('caches module instances', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', ['name' => 'vendor/blog']);

    $manager = app(ModuleManager::class);
    $module1 = $manager->module('blog');
    $module2 = $manager->module('blog');

    expect($module1)->toBe($module2);

    cleanupTestApp($basePath, $files);
});

it('returns loaded modules collection', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', ['name' => 'vendor/blog']);
    createModule($files, $basePath, 'auth', ['name' => 'vendor/auth']);

    $manager = app(ModuleManager::class);
    $manager->module('blog');
    $manager->module('auth');
    $loaded = $manager->loaded();

    expect($loaded)->toBeInstanceOf(ModuleCollection::class);
    expect($loaded->count())->toBe(2);

    cleanupTestApp($basePath, $files);
});

it('returns null when module does not exist', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    $manager = app(ModuleManager::class);
    $found = $manager->find('nonexistent');

    expect($found)->toBeNull();

    cleanupTestApp($basePath, $files);
});

it('returns module when it exists', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', ['name' => 'vendor/blog']);

    $manager = app(ModuleManager::class);
    $found = $manager->find('blog');

    expect($found)->toBeInstanceOf(Module::class);

    cleanupTestApp($basePath, $files);
});
