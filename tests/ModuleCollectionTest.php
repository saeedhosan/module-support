<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use SaeedHosan\Module\Support\ModuleCollection;
use SaeedHosan\Module\Support\ModuleManager;
use Tests\TestCase;

uses(TestCase::class);

it('can be instantiated with no items', function () {
    $collection = new ModuleCollection;

    expect($collection)->toBeInstanceOf(ModuleCollection::class);
    expect($collection->names())->toBe([]);
});

it('returns module names as array', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', ['name' => 'vendor/blog']);
    createModule($files, $basePath, 'auth', ['name' => 'vendor/auth']);

    $manager = app(ModuleManager::class);
    $manager->module('blog');
    $manager->module('auth');
    $loaded = $manager->loaded();

    expect($loaded->names())->toBe(['blog', 'auth']);

    cleanupTestApp($basePath, $files);
});
