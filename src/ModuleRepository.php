<?php

declare(strict_types=1);

namespace SaeedHosan\Module\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SaeedHosan\Module\Support\Utils\Path;

class ModuleRepository
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $composerData = null;

    public function __construct(
        protected string $name,
        protected Filesystem $files,
        protected string $basePath,
        protected bool $lowercase
    ) {}

    /**
     * Determine the module exists.
     */
    public function exists(): bool
    {
        return $this->files->exists($this->path());
    }

    /**
     * Get the name of the module.
     */
    public function name(): string
    {
        $name = $this->name;

        if ($this->lowercase) {
            return Str::lower($name);
        }

        return $name;
    }

    /**
     * Get the path of the module.
     */
    public function basePath(string ...$segments): string
    {
        return Path::join(base_path($this->basePath), $this->name(), ...$segments);
    }

    /**
     * Get the path of the module.
     */
    public function path(string ...$segments): string
    {
        return $this->basePath(...$segments);
    }

    /**
     * Get the module application path from composer.json.
     */
    public function appPath(string ...$segments): ?string
    {
        $path = mb_rtrim((string) Arr::first($this->composerPsr4()), '/');

        if ($path === '') {
            return null;
        }

        return $this->basePath($path, ...$segments);
    }

    /**
     * Get the module view path.
     */
    public function viewPath(string ...$segments): string
    {
        return $this->basePath(config('module.view_path', 'resources/views'), ...$segments);
    }

    /**
     * Get the module lang path.
     */
    public function langPath(string ...$segments): string
    {
        return $this->basePath(config('module.lang_path', 'resources/lang'), ...$segments);
    }

    /**
     * Get the module database path.
     */
    public function testPath(string ...$segments): string
    {
        return $this->basePath(config('module.test_path', 'tests'), ...$segments);
    }

    /**
     * Get the module database path.
     */
    public function databasePath(string ...$segments): string
    {
        return $this->basePath(config('module.database_path', 'database'), ...$segments);
    }

    /**
     * Get the module namespace from composer.json.
     */
    public function defaultNamespace(): string
    {
        return config('module.namespace', 'Modules').'\\'.Str::studly($this->name);
    }

    /**
     * Get the module namespace from composer.json.
     */
    public function namespace(): ?string
    {
        $namespace = (string) is_array($psr4 = $this->composerPsr4()) ? array_key_first($psr4) : null;

        if ($namespace === '' || is_null($namespace)) {
            return null;
        }

        return mb_rtrim($namespace, '\\');
    }

    /**
     * Get the version of the module.
     */
    public function version(): string
    {
        return $this->composer()['version'] ?? '0';
    }

    /**
     * Get the description of the module.
     */
    public function description(): string
    {
        return $this->composer()['description'] ?? '';
    }

    /**
     * Get the module composer data.
     *
     * @return array<string, mixed>
     */
    public function composer(): array
    {
        if ($this->composerData !== null) {
            return $this->composerData;
        }

        $path = $this->basePath('composer.json');

        if (! $this->files->exists($path)) {

            $this->composerData = [];

            return $this->composerData;
        }

        $decoded = json_decode($this->files->get($path), true);

        if (! is_array($decoded)) {

            $this->composerData = [];

            return $this->composerData;
        }

        $this->composerData = $decoded;

        return $this->composerData;
    }

    /**
     * Get the providers declared in composer.json.
     *
     * @return array<int, string>
     */
    public function providers(): array
    {
        $providers = Arr::get($this->composer(), 'extra.laravel.providers', []);

        if (! is_array($providers)) {
            return [];
        }

        return array_values(array_filter($providers, static fn ($provider): bool => is_string($provider)));
    }

    /**
     * Transform module data to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'path' => $this->path(),
            'exists' => $this->exists(),
            'version' => $this->version(),
            'app_path' => $this->appPath(),
            'base_path' => $this->basePath(),
            'view_path' => $this->viewPath(),
            'namespace' => $this->namespace(),
            'providers' => $this->providers(),
            'description' => $this->description(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function composerPsr4(): array
    {
        $psr4 = Arr::get($this->composer(), 'autoload.psr-4', []);

        if (! is_array($psr4) || $psr4 === []) {
            return [];
        }

        return $psr4;
    }
}
