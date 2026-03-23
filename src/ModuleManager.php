<?php

declare(strict_types=1);

namespace SaeedHosan\Module\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SaeedHosan\Module\Support\Contracts\ModuleFinderContract;

class ModuleManager implements ModuleFinderContract
{
    /**
     * @var array<string, Module>
     */
    protected array $modules = [];

    /**
     * @var array<string, bool>
     */
    protected array $registeredProviders = [];

    protected ?ModuleCollection $allModulesCache = null;

    /**
     * @var array<string, array<int, string>>|null
     */
    protected ?array $autoloadPsr4Cache = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected Application $app,
        protected Filesystem $files,
        protected array $config
    ) {}

    /**
     * Get a module instance by name.
     */
    public function module(string $name): Module
    {
        $normalized = $this->normalizeName($name);

        if (! isset($this->modules[$normalized])) {
            $repository = new ModuleRepository(
                name: $name,
                files: $this->files,
                basePath: (string) Arr::get($this->config, 'directory', 'modules'),
                lowercase: (bool) Arr::get($this->config, 'lowercase', true)
            );

            $this->modules[$normalized] = new Module($this->app, $repository, $this);
        }

        return $this->modules[$normalized];
    }

    /**
     * Find a module by name.
     */
    public function find(string $name): ?Module
    {
        $module = $this->module($name);

        if (! $module->exists()) {
            return null;
        }

        return $module;
    }

    /**
     * Get all resolved modules.
     */
    public function loaded(): ModuleCollection
    {
        /** @var Collection<int, Module> $collection */
        $collection = new ModuleCollection(array_values($this->modules));

        return $collection;
    }

    /**
     * Get all resolved modules (alias of loaded).
     */
    public function all(): ModuleCollection
    {
        if ($this->allModulesCache instanceof ModuleCollection) {
            return $this->allModulesCache;
        }

        $directory = mb_rtrim(base_path((string) Arr::get($this->config, 'directory', 'modules')), '/');

        if (! $this->files->exists($directory)) {
            $this->allModulesCache = new ModuleCollection();

            return $this->allModulesCache;
        }

        $modules = [];

        foreach ($this->files->directories($directory) as $path) {
            $name = basename($path);

            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }

            $modules[] = $this->module($name);
        }

        $this->allModulesCache = new ModuleCollection($modules);

        return $this->allModulesCache;
    }

    /**
     * Register all providers for a module.
     *
     * @param  array<int, string>  $providers
     */
    public function registerProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            if (! is_string($provider) || $provider === '') {
                continue;
            }

            if (isset($this->registeredProviders[$provider])) {
                continue;
            }

            $this->registeredProviders[$provider] = true;
            $this->app->register($provider);
        }
    }

    /**
     * Determine if a namespace is autoloaded by the root composer autoload.
     */
    public function isNamespaceAutoloaded(?string $namespace): bool
    {
        if ($namespace === null || $namespace === '') {
            return false;
        }

        $normalized = mb_rtrim($namespace, '\\').'\\';

        foreach ($this->autoloadPsr4() as $prefix => $paths) {
            $prefix = mb_rtrim((string) $prefix, '\\').'\\';

            if (Str::startsWith($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize a module name for cache keys.
     */
    protected function normalizeName(string $name): string
    {
        $normalized = Str::studly($name);

        if ((bool) Arr::get($this->config, 'lowercase', true)) {
            return Str::lower($normalized);
        }

        return $normalized;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function autoloadPsr4(): array
    {
        if (is_array($this->autoloadPsr4Cache)) {
            return $this->autoloadPsr4Cache;
        }

        $path = base_path('vendor/composer/autoload_psr4.php');

        if (! $this->files->exists($path)) {
            $this->autoloadPsr4Cache = [];

            return $this->autoloadPsr4Cache;
        }

        $data = require $path;

        if (! is_array($data)) {
            $this->autoloadPsr4Cache = [];

            return $this->autoloadPsr4Cache;
        }

        $this->autoloadPsr4Cache = $data;

        return $this->autoloadPsr4Cache;
    }
}
