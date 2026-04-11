<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\ViewServiceProvider;
use PHPUnit\Framework\TestCase;
use SaeedHosan\Module\Support\Module;
use SaeedHosan\Module\Support\ModuleCollection;
use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\ModuleRepository;
use SaeedHosan\Module\Support\ServiceProvider;
use Tests\Fixtures\TestModuleProvider;

uses(TestCase::class);

function createModule(Filesystem $files, string $basePath, string $name, array $composerData): void
{
    $modulePath = $basePath.'/modules/'.$name;

    $files->ensureDirectoryExists($modulePath);
    $files->put($modulePath.'/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));
}

function createAutoloadPsr4(Filesystem $files, string $basePath, array $map): void
{
    $autoloadPath = $basePath.'/vendor/composer';

    $files->ensureDirectoryExists($autoloadPath);
    $files->put(
        $autoloadPath.'/autoload_psr4.php',
        "<?php\n\nreturn ".var_export($map, true).";\n"
    );
}

it('returns a module manager from the helper', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);
    $app = createApp($basePath, $files);

    expect(module())->toBeInstanceOf(ModuleManager::class);

    cleanup($basePath, $files, $app);
});

it('returns a module instance from the helper', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);
    $app = createApp($basePath, $files);

    expect(module('Blog'))->toBeInstanceOf(Module::class);

    cleanup($basePath, $files, $app);
});

it('detects module existence', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    expect(module('Blog')->exists())->toBeFalse();

    createModule($files, $basePath, 'Blog', []);

    expect(module('Blog')->exists())->toBeTrue();

    cleanup($basePath, $files, $app);
});

it('binds module manager as a singleton', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);
    $app = createApp($basePath, $files);

    $first = app(ModuleManager::class);
    $second = app(ModuleManager::class);

    expect($first)->toBe($second);

    cleanup($basePath, $files, $app);
});

it('returns a module collection from loaded', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);
    $app = createApp($basePath, $files);

    $collection = module()->loaded();

    expect($collection)->toBeInstanceOf(ModuleCollection::class);

    cleanup($basePath, $files, $app);
});

it('returns a module collection from all', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);
    $app = createApp($basePath, $files);

    $collection = module()->all();

    expect($collection)->toBeInstanceOf(ModuleCollection::class);

    cleanup($basePath, $files, $app);
});

it('converts module collection to array data', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'Blog', ['version' => '1.0.0']);

    $data = module()->all()->toArray();

    expect($data)->toBeArray();
    expect($data[0])->toHaveKey('name');
    expect($data[0])->toHaveKey('version');

    cleanup($basePath, $files, $app);
});

it('finds modules only when they exist', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    $manager = module();

    expect($manager->find('Blog'))->toBeNull();

    createModule($files, $basePath, 'Blog', []);

    expect($manager->find('Blog'))->toBeInstanceOf(Module::class);

    cleanup($basePath, $files, $app);
});

it('returns namespace and app path when psr-4 is present', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

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

    cleanup($basePath, $files, $app);
});

it('returns null namespace and app path when psr-4 is missing', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'Blog', ['name' => 'modules/blog']);

    $module = module('Blog');

    expect($module->namespace())->toBeNull();
    expect($module->appPath())->toBeNull();

    cleanup($basePath, $files, $app);
});

it('renders blade module directive content when active', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

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

    cleanup($basePath, $files, $app);
});

it('renders blade module directive else content when inactive', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

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

    cleanup($basePath, $files, $app);
});

it('returns false for active when module is not autoloaded', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    expect(module('Blog')->active())->toBeFalse();

    cleanup($basePath, $files, $app);
});

it('returns true for active when autoloaded and no providers are defined', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

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

    cleanup($basePath, $files, $app);
});

it('returns empty composer data when composer json is missing', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    $files->ensureDirectoryExists($basePath.'/modules/Blog');

    $module = module('Blog');

    expect($module->composer())->toBe([]);
    expect($module->providers())->toBe([]);

    cleanup($basePath, $files, $app);
});

it('uses the module repository alias for backward compatibility', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'Blog', []);

    $alias = new ModuleRepository(
        name: 'Blog',
        files: $files,
        basePath: 'modules',
        lowercase: false
    );

    expect($alias->path())->toBe(base_path('modules/Blog'));

    cleanup($basePath, $files, $app);
});

it('returns empty composer data when composer json is invalid', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    $modulePath = $basePath.'/modules/Blog';
    $files->ensureDirectoryExists($modulePath);
    $files->put($modulePath.'/composer.json', '{invalid-json');

    $module = module('Blog');

    expect($module->composer())->toBe([]);
    expect($module->namespace())->toBeNull();

    cleanup($basePath, $files, $app);
});

it('returns providers from composer extra section', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'Blog', [
        'extra' => [
            'laravel' => [
                'providers' => [TestModuleProvider::class],
            ],
        ],
    ]);

    expect(module('Blog')->providers())->toBe([TestModuleProvider::class]);

    cleanup($basePath, $files, $app);
});

it('builds a full module array representation', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

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

    cleanup($basePath, $files, $app);
});

it('respects lowercase module name option in repository', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'blog', []);

    $repository = new ModuleRepository(
        name: 'Blog',
        files: $files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->name())->toBe('blog');

    cleanup($basePath, $files, $app);
});

it('registers providers only once', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

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

    cleanup($basePath, $files, $app);
});

it('returns only resolved modules from loaded', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'Blog', []);
    createModule($files, $basePath, 'Shop', []);

    module('Blog');

    $names = module()->loaded()->names();

    expect($names)->toBe(['Blog']);

    cleanup($basePath, $files, $app);
});

it('returns only resolved modules from all', function (): void {
    $basePath = sys_get_temp_dir().'/module-system-test-'.uniqid();
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath);

    $app = createApp($basePath, $files);

    createModule($files, $basePath, 'Blog', []);
    createModule($files, $basePath, 'Shop', []);

    $names = module()->all()->names();

    sort($names);

    expect($names)->toBe(['Blog', 'Shop']);

    cleanup($basePath, $files, $app);
});

function createApp(string $basePath, Filesystem $files): Application
{
    $app = new Application($basePath);
    Container::setInstance($app);
    Facade::setFacadeApplication($app);

    $app->instance('config', new ConfigRepository([]));
    $app->singleton(Filesystem::class, fn () => $files);
    $app->instance('files', $files);
    $app->alias('files', Filesystem::class);
    $app['config']->set('view.paths', [
        $basePath.'/resources/views',
        __DIR__.'/../resources/views',
    ]);
    $app['config']->set('view.compiled', $basePath.'/storage/framework/views');
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $app['config']->set('cache.default', 'array');
    $app['config']->set('module.lowercase', false);
    $app['config']->set('module.directory', 'modules');

    $app->register(ViewServiceProvider::class);
    (new ServiceProvider($app))->register();
    (new ServiceProvider($app))->boot();

    return $app;
}

function cleanup(string $basePath, Filesystem $files, Application $app): void
{
    if ($files->exists($basePath)) {
        $files->deleteDirectory($basePath);
    }

    Facade::clearResolvedInstances();
    Container::setInstance(null);
}
