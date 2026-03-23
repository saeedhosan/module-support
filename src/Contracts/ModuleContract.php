<?php

declare(strict_types=1);

namespace SaeedHosan\Module\Support\Contracts;

interface ModuleContract
{
    /**
     * Determine the module exists.
     */
    public function exists(): bool;

    /**
     * Determine the module is active (autoloaded and discovered).
     */
    public function active(): bool;

    /**
     * Get the name of the module.
     */
    public function name(): string;

    /**
     * Get the path of the module.
     */
    public function path(): string;

    /**
     * Get the module application path from composer.json.
     */
    public function appPath(): ?string;

    /**
     * Get the module namespace from composer.json.
     */
    public function namespace(): ?string;

    /**
     * Get the version of the module.
     */
    public function version(): ?string;

    /**
     * Get the description of the module.
     */
    public function description(): ?string;

    /**
     * Get the module composer data.
     *
     * @return array<string, mixed>
     */
    public function composer(): array;

    /**
     * Get the providers of the module.
     *
     * @return array<int, string>
     */
    public function providers(): array;
}
