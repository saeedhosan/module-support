<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use SaeedHosan\Module\Support\Module;
use SaeedHosan\Module\Support\ModuleCollection;
use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\ModuleRepository;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\Fixtures\TestModuleProvider;
use Tests\TestCase;

uses(TestCase::class);

it('returns a module manager from the helper', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    expect(module())->toBeInstanceOf(ModuleManager::class);

    cleanupTestApp($basePath, $files);
});

it('returns a module instance from the helper', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    expect(module('Blog'))->toBeInstanceOf(Module::class);

    cleanupTestApp($basePath, $files);
});

it('detects module existence', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    expect(module('Blog')->exists())->toBeFalse();

    createModule($files, $basePath, 'Blog', []);

    expect(module('Blog')->exists())->toBeTrue();

    cleanupTestApp($basePath, $files);
});

it('binds module manager as a singleton', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    $first = app(ModuleManager::class);
    $second = app(ModuleManager::class);

    expect($first)->toBe($second);

    cleanupTestApp($basePath, $files);
});

it('returns a module collection from loaded', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    $collection = module()->loaded();

    expect($collection)->toBeInstanceOf(ModuleCollection::class);

    cleanupTestApp($basePath, $files);
});

it('returns a module collection from all', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    $collection = module()->all();

    expect($collection)->toBeInstanceOf(ModuleCollection::class);

    cleanupTestApp($basePath, $files);
});

it('converts module collection to array data', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files);

    createModule($files, $basePath, 'Blog', ['version' => '1.0.0']);

    $data = module()->all()->toArray();

    expect($data)->toBeArray();
    expect($data[0])->toHaveKey('name');
    expect($data[0])->toHaveKey('version');

    cleanupTestApp($basePath, $files);
});

it('finds modules only when they exist', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    $manager = module();

    expect($manager->find('Blog'))->toBeNull();

    createModule($files, $basePath, 'Blog', []);

    expect($manager->find('Blog'))->toBeInstanceOf(Module::class);

    cleanupTestApp($basePath, $files);
});

it('returns namespace and app path when psr-4 is present', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    createModule($files, $basePath, 'Blog', [
        'version' => '1.2.3',
        'description' => 'Blog module',
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    $module = module('Blog');

    expect($module->namespace())->toBe('Modules\\Blog');
    expect($module->appPath())->toBe(base_path('modules/Blog/src'));
    expect($module->version())->toBe('1.2.3');
    expect($module->description())->toBe('Blog module');

    cleanupTestApp($basePath, $files);
});

it('returns null namespace and app path when psr-4 is missing', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    createModule($files, $basePath, 'Blog', ['name' => 'modules/blog']);

    $module = module('Blog');

    expect($module->namespace())->toBeNull();
    expect($module->appPath())->toBeNull();

    cleanupTestApp($basePath, $files);
});

it('renders blade module directive content when active', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithViews($basePath, $files, [
        __DIR__.'/../resources/views',
    ]);

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    createModule($files, $basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    createAutoloadPsr4($files, $basePath, [
        'Modules\\Blog\\' => [$basePath.'/modules/Blog/src'],
    ]);

    $compiled = Blade::compileString("@module('Blog')\nActive\n@else\nInactive\n@endmodule");

    ob_start();
    eval('?>'.$compiled);
    $output = mb_trim((string) ob_get_clean());

    expect($output)->toBe('Active');

    cleanupTestApp($basePath, $files);
});

it('renders blade module directive else content when inactive', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithViews($basePath, $files, [
        __DIR__.'/../resources/views',
    ]);

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    createModule($files, $basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    $compiled = Blade::compileString("@module('Blog')\nActive\n@else\nInactive\n@endmodule");

    ob_start();
    eval('?>'.$compiled);
    $output = mb_trim((string) ob_get_clean());

    expect($output)->toBe('Inactive');

    cleanupTestApp($basePath, $files);
});

it('returns false for active when module is not autoloaded', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    createModule($files, $basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    expect(module('Blog')->active())->toBeFalse();

    cleanupTestApp($basePath, $files);
});

it('returns true for active when autoloaded and no providers are defined', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithViews($basePath, $files);

    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    createModule($files, $basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    createAutoloadPsr4($files, $basePath, [
        'Modules\\Blog\\' => [$basePath.'/modules/Blog/src'],
    ]);

    expect(module('Blog')->active())->toBeTrue();

    cleanupTestApp($basePath, $files);
});

it('returns empty composer data when composer json is missing', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    $files->ensureDirectoryExists($basePath.'/modules/Blog');

    $module = module('Blog');

    expect($module->composer())->toBe([]);
    expect($module->providers())->toBe([]);

    cleanupTestApp($basePath, $files);
});

it('uses the module repository alias for backward compatibility', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestApp($basePath, $files);

    createModule($files, $basePath, 'Blog', []);

    $alias = new ModuleRepository(
        name: 'Blog',
        files: $files,
        basePath: 'modules',
        lowercase: false
    );

    expect($alias->path())->toBe(base_path('modules/Blog'));

    cleanupTestApp($basePath, $files);
});

it('returns empty composer data when composer json is invalid', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    $modulePath = $basePath.'/modules/Blog';
    $files->ensureDirectoryExists($modulePath);
    $files->put($modulePath.'/composer.json', '{invalid-json');

    $module = module('Blog');

    expect($module->composer())->toBe([]);
    expect($module->namespace())->toBeNull();

    cleanupTestApp($basePath, $files);
});

it('returns providers from composer extra section', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    createModule($files, $basePath, 'Blog', [
        'extra' => [
            'laravel' => [
                'providers' => [TestModuleProvider::class],
            ],
        ],
    ]);

    expect(module('Blog')->providers())->toBe([TestModuleProvider::class]);

    cleanupTestApp($basePath, $files);
});

it('builds a full module array representation', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    createModule($files, $basePath, 'Blog', [
        'version' => '9.9.9',
        'description' => 'Example module',
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
        'extra' => [
            'laravel' => [
                'providers' => [TestModuleProvider::class],
            ],
        ],
    ]);

    $data = module('Blog')->toArray();

    expect($data)->toBeArray();
    expect($data['name'])->toBe('Blog');
    expect($data['namespace'])->toBe('Modules\\Blog');
    expect($data['app_path'])->toBe(base_path('modules/Blog/src'));
    expect($data['version'])->toBe('9.9.9');
    expect($data['description'])->toBe('Example module');
    expect($data['providers'])->toBe([TestModuleProvider::class]);

    cleanupTestApp($basePath, $files);
});

it('respects lowercase module name option in repository', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestApp($basePath, $files);

    createModule($files, $basePath, 'blog', []);

    $repository = new ModuleRepository(
        name: 'Blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->name())->toBe('blog');

    cleanupTestApp($basePath, $files);
});

it('registers providers only once', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    TestModuleProvider::$registerCount = 0;

    createModule($files, $basePath, 'Blog', [
        'extra' => [
            'laravel' => [
                'providers' => [TestModuleProvider::class],
            ],
        ],
    ]);

    $module = module('Blog');

    $module->register();
    $module->register();

    expect(TestModuleProvider::$registerCount)->toBe(1);

    cleanupTestApp($basePath, $files);
});

it('returns only resolved modules from loaded', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    createModule($files, $basePath, 'Blog', []);
    createModule($files, $basePath, 'Shop', []);

    module('Blog');

    $names = module()->loaded()->names();

    expect($names)->toBe(['Blog']);

    cleanupTestApp($basePath, $files);
});

it('returns only resolved modules from all', function (): void {
    $basePath = sys_get_temp_dir().'/test-'.uniqid();
    $files = new Filesystem;

    $app = createTestAppWithServiceProvider($basePath, $files, [
        'module.lowercase' => false,
    ]);

    createModule($files, $basePath, 'Blog', []);
    createModule($files, $basePath, 'Shop', []);

    $names = module()->all()->names();

    sort($names);

    expect($names)->toBe(['Blog', 'Shop']);

    cleanupTestApp($basePath, $files);
});
