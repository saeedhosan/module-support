<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use SaeedHosan\Module\Support\Module;
use SaeedHosan\Module\Support\ModuleManager;
use Tests\TestCase;

uses(TestCase::class);

it('can be instantiated', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = createTestAppWithServiceProvider($basePath, $files);

    $manager = app(ModuleManager::class);
    $module = $manager->module('blog');

    expect($module)->toBeInstanceOf(Module::class);

    cleanupTestApp($basePath, $files);
});

it('returns module exists status', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = createTestAppWithServiceProvider($basePath, $files);

    expect(module('blog')->exists())->toBeTrue();
    expect(module('nonexistent')->exists())->toBeFalse();

    cleanupTestApp($basePath, $files);
});

it('returns module name', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = createTestAppWithServiceProvider($basePath, $files);

    expect(module('blog')->name())->toBe('blog');

    cleanupTestApp($basePath, $files);
});

it('returns module base path', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = createTestAppWithServiceProvider($basePath, $files);

    expect(module('blog')->basePath())->toBe($basePath.'/modules/blog');

    cleanupTestApp($basePath, $files);
});

it('returns module path', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/modules/blog');

    $app = createTestAppWithServiceProvider($basePath, $files);

    expect(module('blog')->path())->toBe($basePath.'/modules/blog');

    cleanupTestApp($basePath, $files);
});

it('returns version from composer json', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', ['version' => '2.0.0']);

    expect(module('blog')->version())->toBe('2.0.0');

    cleanupTestApp($basePath, $files);
});

it('returns description from composer json', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', ['description' => 'Blog module']);

    expect(module('blog')->description())->toBe('Blog module');

    cleanupTestApp($basePath, $files);
});

it('returns providers from composer json', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', [
        'extra' => [
            'laravel' => [
                'providers' => ['BlogServiceProvider'],
            ],
        ],
    ]);

    expect(module('blog')->providers())->toBe(['BlogServiceProvider']);

    cleanupTestApp($basePath, $files);
});

it('returns composer data', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', [
        'name' => 'vendor/blog',
        'version' => '1.0.0',
    ]);

    $composer = module('blog')->composer();

    expect($composer['name'])->toBe('vendor/blog');
    expect($composer['version'])->toBe('1.0.0');

    cleanupTestApp($basePath, $files);
});

it('returns namespace from composer psr-4', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    expect(module('blog')->namespace())->toBe('Modules\\Blog');

    cleanupTestApp($basePath, $files);
});

it('returns default namespace', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', []);

    expect(module('blog')->namespace())->toBeNull();

    cleanupTestApp($basePath, $files);
});

it('returns app path from psr-4', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    expect(module('blog')->appPath())->toBe($basePath.'/modules/blog/src');

    cleanupTestApp($basePath, $files);
});

it('returns view path', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', []);

    expect(module('blog')->viewPath())->toBe($basePath.'/modules/blog/resources/views');

    cleanupTestApp($basePath, $files);
});

it('returns lang path', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', []);

    expect(module('blog')->langPath())->toBe($basePath.'/modules/blog/resources/lang');

    cleanupTestApp($basePath, $files);
});

it('returns toArray with module data', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', [
        'version' => '1.0.0',
        'description' => 'Blog module',
    ]);

    $array = module('blog')->toArray();

    expect($array)->toBeArray();
    expect($array['name'])->toBe('blog');
    expect($array['version'])->toBe('1.0.0');
    expect($array['description'])->toBe('Blog module');

    cleanupTestApp($basePath, $files);
});

it('can call basePath with segments', function () {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'blog', []);

    expect(module('blog')->basePath('src', 'Providers'))->toBe($basePath.'/modules/blog/src/Providers');

    cleanupTestApp($basePath, $files);
});
