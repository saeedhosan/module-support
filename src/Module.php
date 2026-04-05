<?php

declare(strict_types=1);

namespace SaeedHosan\Module\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Application;
use SaeedHosan\Module\Support\Contracts\ModuleContract;

class Module implements Arrayable, ModuleContract
{
    public function __construct(
        protected Application $app,
        protected ModuleRepository $repository,
        protected ModuleManager $manager
    ) {}

    /**
     * Determine the module exists.
     */
    public function exists(): bool
    {
        return $this->repository->exists();
    }

    /**
     * Determine the module is active (autoloaded and discovered).
     */
    public function active(): bool
    {
        if (! $this->exists()) {
            return false;
        }

        $namespace = $this->namespace();

        if (! $this->manager->isNamespaceAutoloaded($namespace)) {
            return false;
        }

        $providers = $this->providers();

        if ($providers === []) {
            return true;
        }

        $loaded = $this->app->getLoadedProviders();

        foreach ($providers as $provider) {
            if (isset($loaded[$provider]) && $loaded[$provider] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the name of the module.
     */
    public function name(): string
    {
        return $this->repository->name();
    }

    /**
     * Get the path of the module.
     */
    public function basePath(string ...$segments): string
    {
        return $this->repository->basePath(...$segments);
    }

    /**
     * Get the path of the module.
     */
    public function path(string ...$segments): string
    {
        return $this->repository->path(...$segments);
    }

    /**
     * Get the module application path from composer.json.
     */
    public function appPath(string ...$segments): ?string
    {
        return $this->repository->appPath(...$segments);
    }

    /**
     * Get the view path.
     */
    public function viewPath(string ...$segments): string
    {
        return $this->repository->viewPath(...$segments);
    }

    /**
     * Get the view path.
     */
    public function langPath(string ...$segments): string
    {
        return $this->repository->langPath(...$segments);
    }

    /**
     * Get the default namespace.
     */
    public function defaultNamespace(): string
    {
        return $this->repository->defaultNamespace();
    }

    /**
     * Get the module namespace from composer.json.
     */
    public function namespace(): ?string
    {
        return $this->repository->namespace();
    }

    /**
     * Get the version of the module.
     */
    public function version(): string
    {
        return $this->repository->version();
    }

    /**
     * Get the description of the module.
     */
    public function description(): string
    {
        return $this->repository->description();
    }

    /**
     * Get the module composer data.
     *
     * @return array<string, mixed>
     */
    public function composer(): array
    {
        return $this->repository->composer();
    }

    /**
     * Get the providers of the module.
     *
     * @return array<int, string>
     */
    public function providers(): array
    {
        return $this->repository->providers();
    }

    /**
     * Register the module providers manually.
     */
    public function register(): void
    {
        $this->manager->registerProviders($this->providers());
    }

    /**
     * Transform the module data to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->repository->toArray();
    }
}
