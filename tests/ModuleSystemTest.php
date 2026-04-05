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

require_once __DIR__.'/../src/helpers.php';
require_once __DIR__.'/Fixtures/TestModuleProvider.php';
require_once __DIR__.'/../src/ModuleRepository.php';

class ModuleSupportTestCase extends TestCase
{
    protected Application $app;

    protected Filesystem $files;

    protected string $basePath;

    protected string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/module-support-tests-'.uniqid();
        $this->files = new Filesystem;
        $this->files->ensureDirectoryExists($this->basePath);

        $this->app = new Application($this->basePath);
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        $this->app->instance('config', new ConfigRepository([]));
        $this->app->singleton(Filesystem::class, fn (): Filesystem => $this->files);
        $this->app->instance('files', $this->files);
        $this->app->alias('files', Filesystem::class);
        $this->app['config']->set('view.paths', [$this->basePath.'/resources/views']);
        $this->app['config']->set('view.compiled', $this->basePath.'/storage/framework/views');

        $this->app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('module.lowercase', false);
        $this->app['config']->set('module.directory', 'modules');

        $this->app->register(ViewServiceProvider::class);
        (new ServiceProvider($this->app))->register();
        (new ServiceProvider($this->app))->boot();

        $this->modulesPath = base_path('modules');
        $this->files->ensureDirectoryExists($this->modulesPath);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->basePath)) {
            $this->files->deleteDirectory($this->basePath);
        }

        Facade::clearResolvedInstances();
        Container::setInstance(null);

        parent::tearDown();
    }
}

uses(ModuleSupportTestCase::class);

function createModule(Filesystem $files, string $basePath, string $name, array $composerData): void
{
    $modulePath = $basePath.'/modules/'.$name;

    $files->ensureDirectoryExists($modulePath);
    $files->put($modulePath.'/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));
}

/**
 * @param  array<string, array<int, string>>  $map
 */
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
    expect(module())->toBeInstanceOf(ModuleManager::class);
});

it('returns a module instance from the helper', function (): void {
    expect(module('Blog'))->toBeInstanceOf(Module::class);
});

it('detects module existence', function (): void {
    expect(module('Blog')->exists())->toBeFalse();

    createModule($this->files, $this->basePath, 'Blog', []);

    expect(module('Blog')->exists())->toBeTrue();
});

it('binds module manager as a singleton', function (): void {
    $first = app(ModuleManager::class);
    $second = app(ModuleManager::class);

    expect($first)->toBe($second);
});

it('returns a module collection from loaded', function (): void {
    $collection = module()->loaded();

    expect($collection)->toBeInstanceOf(ModuleCollection::class);
});

it('returns a module collection from all', function (): void {
    $collection = module()->all();

    expect($collection)->toBeInstanceOf(ModuleCollection::class);
});

it('converts module collection to array data', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'version' => '1.0.0',
    ]);

    $data = module()->all()->toArray();

    expect($data)->toBeArray();
    expect($data[0])->toHaveKey('name');
    expect($data[0])->toHaveKey('version');
});

it('finds modules only when they exist', function (): void {
    $manager = module();

    expect($manager->find('Blog'))->toBeNull();

    createModule($this->files, $this->basePath, 'Blog', []);

    expect($manager->find('Blog'))->toBeInstanceOf(Module::class);
});

it('returns namespace and app path when psr-4 is present', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
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
});

it('returns null namespace and app path when psr-4 is missing', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'name' => 'modules/blog',
    ]);

    $module = module('Blog');

    expect($module->namespace())->toBeNull();
    expect($module->appPath())->toBeNull();
});

it('renders blade module directive content when active', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    createAutoloadPsr4($this->files, $this->basePath, [
        'Modules\\Blog\\' => [
            $this->basePath.'/modules/Blog/src',
        ],
    ]);

    $compiled = Blade::compileString("@module('Blog')\nActive\n@else\nInactive\n@endmodule");

    ob_start();
    eval('?>'.$compiled);
    $output = mb_trim((string) ob_get_clean());

    expect($output)->toBe('Active');
});

it('renders blade module directive else content when inactive', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
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
});

it('renders blade module else content when inactive', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    $compiled = Blade::compileString("@module('Blog')\nActive\n@else\nElse\n@endmodule");

    ob_start();
    eval('?>'.$compiled);
    $output = mb_trim((string) ob_get_clean());

    expect($output)->toBe('Else');
});

it('returns false for active when module is not autoloaded', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    expect(module('Blog')->active())->toBeFalse();
});

it('returns true for active when autoloaded and providers are loaded', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
        'extra' => [
            'laravel' => [
                'providers' => [
                    TestModuleProvider::class,
                ],
            ],
        ],
    ]);

    createAutoloadPsr4($this->files, $this->basePath, [
        'Modules\\Blog\\' => [
            $this->basePath.'/modules/Blog/src',
        ],
    ]);

    $this->app->register(TestModuleProvider::class);

    expect(module('Blog')->active())->toBeTrue();
});

it('returns true for active when autoloaded and no providers are defined', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
    ]);

    createAutoloadPsr4($this->files, $this->basePath, [
        'Modules\\Blog\\' => [
            $this->basePath.'/modules/Blog/src',
        ],
    ]);

    expect(module('Blog')->active())->toBeTrue();
});

it('returns empty composer data when composer json is missing', function (): void {
    $this->files->ensureDirectoryExists($this->basePath.'/modules/Blog');

    $module = module('Blog');

    expect($module->composer())->toBe([]);
    expect($module->providers())->toBe([]);
});

it('uses the module repository alias for backward compatibility', function (): void {
    createModule($this->files, $this->basePath, 'Blog', []);

    $alias = new ModuleRepository(
        name: 'Blog',
        files: $this->files,
        basePath: 'modules',
        lowercase: false
    );

    $support = new ModuleRepository(
        name: 'Blog',
        files: $this->files,
        basePath: 'modules',
        lowercase: false
    );

    expect($alias->path())->toBe($support->path());
});

it('returns empty composer data when composer json is invalid', function (): void {
    $modulePath = $this->basePath.'/modules/Blog';
    $this->files->ensureDirectoryExists($modulePath);
    $this->files->put($modulePath.'/composer.json', '{invalid-json');

    $module = module('Blog');

    expect($module->composer())->toBe([]);
    expect($module->namespace())->toBeNull();
});

it('returns providers from composer extra section', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'extra' => [
            'laravel' => [
                'providers' => [
                    TestModuleProvider::class,
                ],
            ],
        ],
    ]);

    expect(module('Blog')->providers())->toBe([TestModuleProvider::class]);
});

it('builds a full module array representation', function (): void {
    createModule($this->files, $this->basePath, 'Blog', [
        'version' => '9.9.9',
        'description' => 'Example module',
        'autoload' => [
            'psr-4' => [
                'Modules\\Blog\\' => 'src/',
            ],
        ],
        'extra' => [
            'laravel' => [
                'providers' => [
                    TestModuleProvider::class,
                ],
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
});

it('respects lowercase module name option in repository', function (): void {
    createModule($this->files, $this->basePath, 'blog', []);

    $repository = new ModuleRepository(
        name: 'Blog',
        files: $this->files,
        basePath: 'modules',
        lowercase: true
    );

    expect($repository->name())->toBe('blog');
});

it('registers providers only once', function (): void {
    TestModuleProvider::$registerCount = 0;

    createModule($this->files, $this->basePath, 'Blog', [
        'extra' => [
            'laravel' => [
                'providers' => [
                    TestModuleProvider::class,
                ],
            ],
        ],
    ]);

    $module = module('Blog');

    $module->register();
    $module->register();

    expect(TestModuleProvider::$registerCount)->toBe(1);
});

it('returns only resolved modules from loaded', function (): void {
    createModule($this->files, $this->basePath, 'Blog', []);
    createModule($this->files, $this->basePath, 'Shop', []);

    module('Blog');

    $names = module()->loaded()->names();

    expect($names)->toBe(['Blog']);
});

it('returns only resolved modules from all', function (): void {
    createModule($this->files, $this->basePath, 'Blog', []);
    createModule($this->files, $this->basePath, 'Shop', []);

    $names = module()->all()->names();

    sort($names);

    expect($names)->toBe(['Blog', 'Shop']);
});
